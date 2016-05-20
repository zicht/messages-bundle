<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Manager;

use \Doctrine\Bundle\DoctrineBundle\Registry;
use \Symfony\Component\Translation\MessageCatalogueInterface;
use \Symfony\Component\Translation\TranslatorInterface;
use \Zicht\Bundle\MessagesBundle\Entity\Message;
use \Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;
use \Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;
use Zicht\Bundle\MessagesBundle\TranslationsRepository;

/**
 * Central management service for messages
 *
 * @package Zicht\Bundle\MessagesBundle\Manager
 */
class MessageManager
{
    /**
     * @var array
     */
    private $locales;

    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * @var FlushCatalogueCacheHelper
     */
    private $flushHelper;

    /**
     * Constructor
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine, $flushHelper)
    {
        $this->doctrine = $doctrine;
        $this->flushHelper = $flushHelper;
        $this->locales = array();
    }

    /**
     * @param array $locales
     * @return void
     */
    public function setLocales($locales)
    {
        $this->locales = $locales;
    }


    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->locales;
    }


    /**
     * Adds the missing translations for each of the locales configured.
     *
     * @param Message $message
     * @return void
     */
    public function addMissingTranslations(Message $message)
    {
        $message->addMissingTranslations($this->locales);
    }

    public function transactional($callback)
    {
        $n = 0;
        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        $callback($n);

        $em->getConnection()->commit();
        call_user_func($this->flushHelper);
        return $n;
    }

    /**
     * Imports messages into the translation repository.
     *
     * @param MessageCatalogueInterface $catalogue
     * @param bool|array $overwrite
     * @param callable $onError
     * @return int
     */
    public function loadMessages(MessageCatalogueInterface $catalogue, $overwrite, $onError, $state = MessageTranslation::STATE_IMPORT)
    {
        $n = 0;
        $overwrite = array(
            MessageTranslation::STATE_UNKNOWN => is_array($overwrite) && array_key_exists(MessageTranslation::STATE_UNKNOWN, $overwrite) ? $overwrite[MessageTranslation::STATE_UNKNOWN] : false,
            MessageTranslation::STATE_IMPORT => is_array($overwrite) && array_key_exists(MessageTranslation::STATE_IMPORT, $overwrite) ? $overwrite[MessageTranslation::STATE_IMPORT] : false,
            MessageTranslation::STATE_USER => is_array($overwrite) && array_key_exists(MessageTranslation::STATE_USER, $overwrite) ? $overwrite[MessageTranslation::STATE_USER] : false,
        );

        $em = $this->doctrine->getManager();
        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $key => $translation) {
                try {
                    $record = new Message();
                    $record->message = $key;
                    $record->domain = $domain;
                    /** @var Message $existing */
                    $existing = $this->getRepository()->findOneBy(array(
                        'message' => $key,
                        'domain' => $domain
                    ));

                    if ($existing) {
                        if ($translationEntity = $existing->hasTranslation($catalogue->getLocale())) {
                            if (array_key_exists($translationEntity->getState(), $overwrite) && $overwrite[$translationEntity->getState()]) {
                                $translationEntity->translation = $translation;
                                $translationEntity->state = $state;
                            } else {
                                continue;
                            }
                        } else {
                            $existing->addTranslations(new MessageTranslation($catalogue->getLocale(), $translation, $state));
                        }
                        $record = $existing;
                    } else {
                        $record->addTranslations(new MessageTranslation($catalogue->getLocale(), $translation, $state));
                    }
                    $em->persist($record);
                    $em->flush();

                    $n ++;
                } catch (\Exception $e) {
                    if (is_callable($onError)) {
                        $onError($e, $key);
                    }
                }
            }
        }

        return $n;
    }


    /**
     * Returns the translations repository
     *
     * @return TranslationsRepository
     */
    public function getRepository()
    {
        return $this->doctrine->getRepository('\Zicht\Bundle\MessagesBundle\Entity\Message');
    }


    /**
     * Does some sanity checks
     *
     * @param TranslatorInterface $translator
     * @param string $kernelRoot
     * @return array
     */
    public function check(TranslatorInterface $translator, $kernelRoot, $fix = false)
    {
        $issues = array();
        /** @var Message[] $messages */
        $messages = $this->getRepository()->findAll();
        foreach ($messages as $message) {
            foreach ($message->getTranslations() as $translation) {
                $translator->setLocale($translation->locale);
                $translated = $translator->trans($message->message, array(), $message->domain);

                $translationFile = 'Resources/translations/' . $message->domain . '.' . $translation->locale . '.db';
                if (!is_file($kernelRoot . '/' . $translationFile)) {
                    if ($fix) {
                        touch($kernelRoot . '/' . $translationFile);
                        $msg = 'Translation file app/' . $translationFile . ' created';
                    } else {
                        $msg = 'Translation file app/' . $translationFile . ' is missing. This probably causes some messages not to load';
                    }
                    if (!in_array($msg, $issues)) {
                        $issues[]= $msg;
                    }
                }
                if ($translated != $translation->translation) {
                    $issues[]= sprintf(
                        "Message '%s' from domain '%s' in locale '%s' translates as '%s', but '%s' expected",
                        $message->message,
                        $message->domain,
                        $translation->locale,
                        $translated,
                        $translation->translation
                    );
                }
            }
        }
        return $issues;
    }
}

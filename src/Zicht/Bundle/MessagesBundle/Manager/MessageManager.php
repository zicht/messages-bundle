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


    /**
     * Imports messages into the translation repository.
     *
     * @param MessageCatalogueInterface $catalogue
     * @param bool $overwrite
     * @param callable $onError
     * @return int
     */
    protected function loadMessages(MessageCatalogueInterface $catalogue, $overwrite, $onError)
    {
        $em = $this->doctrine->getManager();

        $n = 0;
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
                            if ($overwrite) {
                                $translationEntity->translation = $translation;
                            } else {
                                continue;
                            }
                        } else {
                            $existing->addTranslations(new MessageTranslation($catalogue->getLocale(), $translation));
                        }
                        $record = $existing;
                    } else {
                        $record->addTranslations(new MessageTranslation($catalogue->getLocale(), $translation));
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


    public function getRepository()
    {
        return $this->doctrine->getRepository('\Zicht\Bundle\MessagesBundle\Entity\Message');
    }


    public function check(TranslatorInterface $translator, $kernelRoot)
    {
        $issues = array();
        /** @var Message[] $messages */
        $messages = $this->getRepository()->findAll();
        foreach ($messages as $message) {
            foreach ($message->getTranslations() as $translation) {
                $translator->setLocale($translation->locale);
                $translated = $translator->trans($message->message, array(), $message->domain);

                if ($translated != $translation->translation) {
                    $translationFile = 'Resources/translations/' . $message->domain . '.' . $translation->locale . '.db';
                    if (!is_file($kernelRoot . '/' . $translationFile)) {
                        $msg = 'Translation file app/' . $translationFile . ' is missing. This probably causes some messages not to load';
                        if (!in_array($msg, $issues)) {
                            $issues[]= $msg;
                        }
                    } else {
                        $issues[]= sprintf(
                            "Message '%s' in locale '%s' translates as '%s', but '%s' expected",
                            $message->message,
                            $translation->locale,
                            $translated,
                            $translation->translation
                        );
                    }
                }
            }
        }
        return $issues;
    }
}
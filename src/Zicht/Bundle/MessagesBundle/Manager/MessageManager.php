<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Zicht\Bundle\MessagesBundle\Entity\Message;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;
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
     * @var Registry
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
     * @param FlushCatalogueCacheHelper $flushHelper
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
     * Transactional
     *
     * @param callable $callback
     * @return int
     */
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
     * Synchronizes `state` in the database to the actual situation:
     *
     * - set it to 'import' if the database contents are equal to the file's contents
     * - set it to 'user' if not
     *
     * Returns a tuple containing a count of 'import' and 'user' state messages that 
     * were changed 
     *
     * @param MessageCatalogueInterface $catalogue
     * @return int[] 
     */
    public function syncState(MessageCatalogueInterface $catalogue)
    {
        /* @var PDOConnection $conn */
        $conn = $this->doctrine->getConnection()->getWrappedConnection();

        $where = [];
        $where[MessageTranslation::STATE_IMPORT] = [];
        $where[MessageTranslation::STATE_USER] = [];
        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $key => $translation) {
                $where[MessageTranslation::STATE_IMPORT][]= vsprintf(
                    '(locale=%s AND domain=%s AND message=%s AND translation=%s)',
                    array_map(
                        [$conn, 'quote'],
                        [$catalogue->getLocale(), $domain, $key, $translation]
                    )
                );
                $where[MessageTranslation::STATE_USER][]= vsprintf(
                    '(locale=%s AND domain=%s AND message=%s AND translation <> %s)',
                    array_map(
                        [$conn, 'quote'],
                        [$catalogue->getLocale(), $domain, $key, $translation]
                    )
                );
            }
        }

        if (0 === array_sum(array_map('count', $where))) {
            return [0, 0];
        }

        $affected = [MessageTranslation::STATE_IMPORT => 0, MessageTranslation::STATE_USER => 0];
        foreach ($where as $newState => $whereClauses) {
            $query = sprintf(
                'UPDATE 
                    message_translation 
                        INNER JOIN message ON 
                            message.id=message_translation.message_id 
                    SET 
                        state=%s
                    WHERE
                        %s',
                $conn->quote($newState),
                join("\nOR ", $whereClauses)
            );


            $affected[$newState] += $conn->exec($query);
        }
        return array_values($affected);
    }

    /**
     * Imports messages into the translation repository.
     *
     * @param MessageCatalogueInterface $catalogue
     * @param bool|array $overwrite
     * @param callable $onError
     * @param string $state
     * @return int
     */
    public function loadMessages(
        MessageCatalogueInterface $catalogue,
        $overwrite,
        $onError,
        $state = MessageTranslation::STATE_IMPORT
    ) {
        $n = 0;
        $overwrite = array(
            MessageTranslation::STATE_UNKNOWN =>
                is_array($overwrite) && array_key_exists(MessageTranslation::STATE_UNKNOWN, $overwrite) ?
                    $overwrite[MessageTranslation::STATE_UNKNOWN] : false,
            MessageTranslation::STATE_IMPORT =>
                is_array($overwrite) && array_key_exists(MessageTranslation::STATE_IMPORT, $overwrite) ?
                    $overwrite[MessageTranslation::STATE_IMPORT] : false,
            MessageTranslation::STATE_USER =>
                is_array($overwrite) && array_key_exists(MessageTranslation::STATE_USER, $overwrite) ?
                    $overwrite[MessageTranslation::STATE_USER] : false,
        );

        $em = $this->doctrine->getManager();
        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $key => $translation) {
                try {
                    $record = new Message();
                    $record->message = $key;
                    $record->domain = $domain;
                    /** @var Message $existing */
                    $existing = $this->getRepository()->findOneBy(
                        array(
                            'message' => $key,
                            'domain' => $domain
                        )
                    );

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

                    $n++;
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
     * @param bool $fix
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
                    if ($fix === true) {
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

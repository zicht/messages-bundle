<?php
/**
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOConnection;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Zicht\Bundle\MessagesBundle\Entity\Message;
use Zicht\Bundle\MessagesBundle\Entity\MessageRepository;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;

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
                $where[MessageTranslation::STATE_IMPORT][] = vsprintf(
                    '(locale=%s AND domain=%s AND message=%s AND translation=%s COLLATE utf8_bin)',
                    array_map(
                        [$conn, 'quote'],
                        [$catalogue->getLocale(), $domain, $key, $translation]
                    )
                );
                $where[MessageTranslation::STATE_USER][] = vsprintf(
                    '(locale=%s AND domain=%s AND message=%s AND translation <> %s COLLATE utf8_bin)',
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
     * @return int[]
     */
    public function loadMessages(MessageCatalogueInterface $catalogue, $overwrite, $onError, $state = MessageTranslation::STATE_IMPORT)
    {
        $updated = 0;
        $loaded = 0;

        $overwrite = array(
            MessageTranslation::STATE_UNKNOWN => $this->getOverwriteValue(MessageTranslation::STATE_UNKNOWN, $overwrite),
            MessageTranslation::STATE_IMPORT => $this->getOverwriteValue(MessageTranslation::STATE_IMPORT, $overwrite),
            MessageTranslation::STATE_USER => $this->getOverwriteValue(MessageTranslation::STATE_USER, $overwrite),
        );

        /** @var Connection $conn */
        $conn = $this->doctrine->getConnection();

        /** Prepare some sql statements */
        $messageSelect = $conn->prepare(
            "SELECT id FROM message WHERE BINARY(message) = ? AND BINARY(domain) = ?"
        );
        $messageInsert = $conn->prepare(
            "INSERT INTO message (`message`, `domain`) VALUES (?, ?)"
        );
        $translationSelect = $conn->prepare(
            "SELECT message_translation_id, state FROM message_translation WHERE message_id = ? AND locale = ?"
        );
        $translationInsert = $conn->prepare(
            "INSERT INTO message_translation (`message_id`, `locale`, `translation`, `state`) VALUES (?, ?, ?, ?)"
        );
        $translationUpdate = $conn->prepare(
            "UPDATE message_translation SET `locale` = ?, `translation` = ?, `state` = ? WHERE message_translation_id = ? AND message_id = ?"
        );

        foreach ($catalogue->all() as $domain => $messages) {
            $loaded += count($messages);
            foreach ($messages as $key => $translation) {
                try {
                    $messageSelect->execute(array($key, $domain));
                    if (false !== ($mid = $messageSelect->fetchColumn(0))) {
                        $translationSelect->execute(array($mid, $catalogue->getLocale()));
                        $ret = $translationSelect->fetchAll();
                        if (!empty($ret)) {
                            [$tid, $translationState] = array_values(current($ret));
                            if (!empty($overwrite[$translationState])) {
                                $translationUpdate->execute(array($catalogue->getLocale(), $translation, $state, $tid, $mid));
                                $updated += $translationUpdate->rowCount();
                            }
                        } else {
                            $translationInsert->execute(array($mid, $catalogue->getLocale(), $translation, $state));
                            $updated += $translationInsert->rowCount();
                        }
                    } else {
                        $messageInsert->execute(array($key, $domain));
                        $mid = $conn->lastInsertId();
                        $translationInsert->execute(array($mid, $catalogue->getLocale(), $translation, $state));
                        $updated += $translationInsert->rowCount();
                    }
                } catch (\Exception $e) {
                    if (is_callable($onError)) {
                        $onError($e, $key);
                    }
                }
            }
        }
        return array($loaded, $updated);
    }

    /**
     * Helper function for more readable check
     *
     * @param string $state
     * @param mixed $overwrites
     * @return bool
     */
    protected function getOverwriteValue($state, $overwrites)
    {
        if (is_array($overwrites) && array_key_exists($state, $overwrites)) {
            return $overwrites[$state];
        }
        return false;
    }

    /**
     * Returns the translations repository
     *
     * @return MessageRepository
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

                $translationsDir = $kernelRoot . '/translations/';
                $translationFile = $translationsDir . $message->domain . '.' . $translation->locale . '.db';
                if (!is_file($translationFile)) {
                    if (!is_dir($translationsDir)) {
                        $msg = 'Translations directory ' . $translationsDir . ' does not exist';
                    } elseif ($fix === true) {
                        touch($translationFile);
                        $msg = 'Translation file ' . $translationFile . ' created';
                    } else {
                        $msg = 'Translation file ' . $translationFile . ' is missing. This probably causes some messages not to load';
                    }
                    if (!in_array($msg, $issues)) {
                        $issues[] = $msg;
                    }
                }
                if ($translated != $translation->translation) {
                    $issues[] = sprintf(
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

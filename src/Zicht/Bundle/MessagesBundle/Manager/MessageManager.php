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
                $where[MessageTranslation::STATE_IMPORT][]= vsprintf(
                    '(locale=%s AND domain=%s AND message=%s AND translation=%s COLLATE utf8_bin)',
                    array_map(
                        [$conn, 'quote'],
                        [$catalogue->getLocale(), $domain, $key, $translation]
                    )
                );
                $where[MessageTranslation::STATE_USER][]= vsprintf(
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
     * @return int
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
        $em = $this->doctrine->getManager();
        foreach ($catalogue->all() as $domain => $messages) {
            $loaded += count($messages);
            foreach ($messages as $key => $translation) {
                try {
                    if (null === ($record = $this->getMessage($key, $domain))) {
                        $record = new Message();
                        $record->message = $key;
                        $record->domain = $domain;
                        $record->addTranslations(new MessageTranslation($catalogue->getLocale(), $translation, $state));
                        $em->persist($record);
                        $updated++;
                    } else {
                        if (false !== ($trans = $record->hasTranslation($catalogue->getLocale()))) {
                            if (!empty($overwrite[$trans->getState()])) {
                                $trans->translation = $translation;
                                $trans->state = $state;
                                $em->persist($trans);
                                $em->persist($record);
                                $updated++;
                            } else {
                                continue;
                            }
                        } else {
                            $record->addTranslations(new MessageTranslation($catalogue->getLocale(), $translation, $state));
                            $em->persist($record);
                            $updated++;
                        }
                    }
                    if ($updated%20 === 0) {
                        $em->flush();
                        $em->clear();
                    }
                } catch (\Exception $e) {
                    if (is_callable($onError)) {
                        $onError($e, $key);
                    }
                }
            }
        }
        if ($updated > 0 && $updated%20 !== 0) {
            $em->flush();
            $em->clear();
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
     * Search a message
     *
     * @param string $message
     * @param string $domain
     * @return null|Message
     */
    protected function getMessage($message, $domain)
    {
        return $this->getRepository()->findOneBy(
            array(
                'message' => $message,
                'domain' => $domain
            )
        );
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

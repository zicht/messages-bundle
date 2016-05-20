<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Entity;

use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\ORM\Mapping as ORM;

/**
 * Message entity
 *
 * @ORM\Entity(repositoryClass="Zicht\Bundle\MessagesBundle\Entity\MessageRepository")
 * @ORM\Table(
 *    name="message",
 *    uniqueConstraints={
 *       @ORM\UniqueConstraint(
 *          name="message_idx",
 *          columns={"message", "domain"}
 *       )
 *    }
 * )
 */
class Message
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO");
     * @ORM\Column(type="integer")
     */
    protected $id;


    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    public $message;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    public $domain = 'messages';

    /**
     * @ORM\OneToMany(
     *     targetEntity="Zicht\Bundle\MessagesBundle\Entity\MessageTranslation",
     *     mappedBy="message",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    public $translations;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s [%d]', (string)$this->message, $this->id);
    }


    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }


    /**
     * @return MessageTranslation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }


    /**
     * Checks if the translation for the specified locale exists.
     *
     * @param string $locale
     * @return bool
     */
    public function hasTranslation($locale)
    {
        foreach ($this->translations as $translation) {
            if ($locale == $translation->locale) {
                return $translation;
            }
        }
        return false;
    }


    /**
     * Adds missing translations
     *
     * @param array $locales
     * @return void
     */
    public function addMissingTranslations($locales)
    {
        foreach ($locales as $localeCode) {
            if (!$this->hasTranslation($localeCode)) {
                $this->addTranslations(new MessageTranslation($localeCode, $this->getMessage()));
            }
        }

        foreach ($this->translations as $translation) {
            $translation->setMessage($this);
        }
    }

    /**
     * @param string $locale
     * @return bool
     */
    public function getTranslation($locale)
    {
        return $this->hasTranslation($locale);
    }


    /**
     * Add a translation
     *
     * @param MessageTranslation $translation
     * @return void
     */
    public function addTranslations(MessageTranslation $translation)
    {
        $translation->message = $this;
        $this->translations[] = $translation;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param MessageTranslation[] $translations
     * @return void
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }

    /**
     * Set domain
     *
     * @param string $domain
     * @return void
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }
}
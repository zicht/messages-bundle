<?php

namespace Zicht\Bundle\MessagesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'message')]
#[ORM\UniqueConstraint(name: 'message_idx', columns: ['message', 'domain'])]
class Message
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected $id;


    /** @var string */
    #[ORM\Column(type: 'string')]
    public $message;

    /** @var string */
    #[ORM\Column(type: 'string', length: 64)]
    public $domain = 'messages';

    /** @var Collection<int, MessageTranslation> */
    #[ORM\OneToMany(targetEntity: MessageTranslation::class, mappedBy: 'message', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function __toString(): string
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
     * @return Collection<int, MessageTranslation>
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
     * @param iterable<array-key, MessageTranslation> $translations
     * @return void
     */
    public function setTranslations($translations)
    {
        $this->translations->clear();
        foreach ($translations as $translation) {
            $this->addTranslations($translation);
        }
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

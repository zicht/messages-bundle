<?php

namespace Zicht\Bundle\MessagesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'message_translation')]
#[ORM\UniqueConstraint(name: 'message_translation_idx', columns: ['message_id', 'locale'])]
class MessageTranslation
{
    /** Unknown state */
    const STATE_UNKNOWN = 'unknown';

    /** Import state */
    const STATE_IMPORT = 'import';

    /** User state */
    const STATE_USER = 'user';

    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected $message_translation_id;

    /** @var int */
    #[ORM\Column(type: 'integer')]
    protected $message_id;

    /** @var Message */
    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    public $message;

    /** @var string */
    #[ORM\Column(type: 'string', length: 8)]
    public $locale;

    /** @var string */
    #[ORM\Column(type: 'text')]
    public $translation  = '';

    /**
     * Indicates the state of the message: unknown, import, user.
     *
     * Where import indicates the value of the message is from an import, i.e. yaml file,
     * and user indicates the value is modified by the user through the cms.  Once a message
     * is modified by the user, the import should no longer (automatically) overwrite its value.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 8)]
    public $state = MessageTranslation::STATE_UNKNOWN;

    /**
     * @param string $locale
     * @param string $translation
     * @param string $state
     */
    public function __construct($locale = null, $translation = null, $state = MessageTranslation::STATE_UNKNOWN)
    {
        $this->locale = $locale;
        $this->translation = $translation;
        $this->state = $state;
    }

    public function __toString(): string
    {
        return (string)$this->translation;
    }

    /**
     * @param Message $message
     * @return void
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return empty($this->state) ? MessageTranslation::STATE_UNKNOWN : $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return mixed
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param mixed $translation
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;
    }
}

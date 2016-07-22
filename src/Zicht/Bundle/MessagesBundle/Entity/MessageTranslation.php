<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Translation entity
 *
 * @ORM\Entity
 * @ORM\Table(name="message_translation", uniqueConstraints={
 *  @ORM\UniqueConstraint(
 *    name="message_translation_idx",
 *    columns={"message_id", "locale"}
 *  )
 * })
 */
class MessageTranslation
{
    const STATE_UNKNOWN = 'unknown';
    const STATE_IMPORT = 'import';
    const STATE_USER = 'user';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $message_translation_id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $message_id;

    /**
     * @ORM\ManyToOne(targetEntity="Zicht\Bundle\MessagesBundle\Entity\Message", inversedBy="translations")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")
     */
    public $message;

    /**
     * @ORM\Column(type="string", length=8);
     */
    public $locale;

    /**
     * @ORM\Column(type="text")
     */
    public $translation  = '';


    /**
     * Indicates the state of the message: unknown, import, user.
     *
     * Where import indicates the value of the message is from an import, i.e. yaml file,
     * and user indicates the value is modified by the user through the cms.  Once a message
     * is modified by the user, the import should no longer (automatically) overwrite its value.
     *
     * @var string
     * @ORM\Column(type="string", length=8, nullable=false)
     */
    public $state = MessageTranslation::STATE_UNKNOWN;

    /**
     * Constructor.
     *
     * @param string $locale
     * @param string $translation
     * @param string $state
     */
    function __construct($locale = null, $translation = null, $state = MessageTranslation::STATE_UNKNOWN)
    {
        $this->locale = $locale;
        $this->translation = $translation;
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function __toString()
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
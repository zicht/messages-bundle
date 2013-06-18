<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="message_translation", uniqueConstraints={
 *  @ORM\UniqueConstraint(
 *    name="message_translation_idx",
 *    columns={"message_id", "locale"}
 *  )
 * })
 */
class MessageTranslation {
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
     * @ORM\Column(type="string")
     */
    public $translation  = '';


    /**
     * @param $locale
     * @param $translation
     */
    function __construct($locale = null, $translation = null) {
        $this->locale = $locale;
        $this->translation = $translation;
    }


    function __toString() {
        return (string) $this->translation;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }
}
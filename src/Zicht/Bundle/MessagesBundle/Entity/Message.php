<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
 
namespace Zicht\Bundle\MessagesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
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
class Message {

    /** @var array
        @deprecated inject this instead.
     */
    public static $locales = array('en', 'nl', 'fr');

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO");
     * @ORM\Column(type="integer")
     */
    protected $id;


    /**
     * @ORM\Column(type="string", length=255)
     */
    public $message;


    /**
     * @ORM\Column(type="string", length=64)
     * @var
     */
    public $domain = 'messages';


    /**
     * @ORM\OneToMany(
     *     targetEntity="Zicht\Bundle\MessagesBundle\Entity\MessageTranslation",
     *     mappedBy="message",
     *     cascade={"persist", "remove"}
     * )
     */
    public $translations;

    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function __toString() {
        return sprintf('%s [%d]', (string) $this->message, $this->id);
    }


    public function getMessage() {
        return $this->message;
    }


    public function getTranslations() {
        return $this->translations;
    }


    public function hasTranslation($locale) {
        foreach ($this->translations as $translation) {
            if ($locale == $translation->locale) {
                return $translation;
            }
        }
        return false;
    }


    function addMissingTranslations($locales) {
        foreach ($locales as $localeCode) {
            if (!$this->hasTranslation($localeCode)) {
                $this->addTranslations(new MessageTranslation($localeCode, $this->getMessage()));
            }
        }

        foreach ($this->translations as $translation) {
            $translation->setMessage($this);
        }
    }



    public function getTranslation($locale) {
        return $this->hasTranslation($locale);
    }



    public function addTranslations(MessageTranslation $translation) {
        $translation->message = $this;
        $this->translations[]= $translation;
    }


    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    /**
     * @param  $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return
     */
    public function getType() {
        return $this->type;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function setTranslations($translations) {
        $this->translations = $translations;
    }

    /**
     * Set domain
     *
     * @param string $domain
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
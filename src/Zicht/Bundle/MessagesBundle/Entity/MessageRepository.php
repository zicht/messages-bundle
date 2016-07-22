<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Zicht\Bundle\MessagesBundle\TranslationsRepository;

/**
 * Class MessageRepository
 *
 * @package Zicht\Bundle\MessagesBundle\Entity
 */
class MessageRepository extends EntityRepository implements TranslationsRepository
{
    /**
     * Returns all translations for the specified domain
     *
     * @param string $locale
     * @param string $domain
     * @return array
     */
    public function getTranslations($locale, $domain)
    {
        $q = $this
            ->createQueryBuilder('m')
            ->select('m, t')
            ->join('m.translations', 't')
            ->andWhere('t.locale=:locale')
            ->andWhere('m.domain=:domain')
        ;
        $q->setParameters(array('locale' => $locale, 'domain' => $domain));

        $ret = array();
        foreach ($q->getQuery()->execute() as $message) {
            if ($translation = $message->getTranslation($locale)) {
                $ret[$message->message] = $translation->translation;
            }
        }

        return $ret;
    }

    /**
     * Returns all domains that are defined in the database
     *
     * @return array
     */
    public function getDomains()
    {
        $q = $this
            ->createQueryBuilder('m')
            ->select('m.domain')
            ->distinct()
            ->orderBy('m.domain')
        ;
        $ret = array();
        foreach ($q->getQuery()->execute() as $result) {
            $ret[$result['domain']] = $result['domain'];
        }
        return $ret;
    }
}
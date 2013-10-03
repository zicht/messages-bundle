<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Entity;

use Zicht\Bundle\MessagesBundle\TranslationsRepository;

class MessageRepository extends \Doctrine\ORM\EntityRepository implements TranslationsRepository {
    function getTranslations($locale, $domain) {
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
}
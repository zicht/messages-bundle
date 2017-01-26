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
     * @return \Generator|array
     */
    public function getTranslations($locale, $domain)
    {
        $conn = $this->$this->getEntityManager()->getConnection();
        $stmt = $conn->executeQuery(
            'SELECT m.message, t.translation FROM message m JOIN message_translation t ON (t.message_id = m.id AND t.locale = ? ) WHERE m.domain = ?',
            [$locale, $domain]
        );
        while ($row = $stmt->fetch()) {
            yield $row['message'] => $row['translation'];
        }
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
            ->orderBy('m.domain');

        $ret = array();
        foreach ($q->getQuery()->execute() as $result) {
            $ret[$result['domain']] = $result['domain'];
        }
        return $ret;
    }
}

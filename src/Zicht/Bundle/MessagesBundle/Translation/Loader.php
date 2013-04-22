<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Translation;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Loader\LoaderInterface;

use Zicht\Bundle\MessagesBundle\TranslationsRepository;

class Loader implements LoaderInterface {
    function setRepository($doctrine, $entity) {
        // TODO remove dependency on doctrine registry
        $this->repository = $doctrine->getEntityManager()->getRepository($entity);
    }


    /**
     * Loads a locale.
     *
     * @param  mixed  $resource A resource
     * @param  string $locale   A locale
     * @param  string $domain   The domain
     *
     * @return MessageCatalogue A MessageCatalogue instance
     *
     * @api
     */
    function load($resource, $locale, $domain = 'messages') {
        $catalogue = new MessageCatalogue($locale);

        foreach($this->repository->getTranslations($locale, $domain) as $id => $translation) {
            $catalogue->set($id, $translation, $domain);
        }

        return $catalogue;
    }
}
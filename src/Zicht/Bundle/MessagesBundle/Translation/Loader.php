<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Translation;

use \Doctrine\Bundle\DoctrineBundle\Registry;
use \Symfony\Component\Translation\MessageCatalogue;
use \Symfony\Component\Translation\Loader\LoaderInterface;

use \Zicht\Bundle\MessagesBundle\TranslationsRepository;

/**
 * Class Loader
 *
 * @package Zicht\Bundle\MessagesBundle\Translation
 */
class Loader implements LoaderInterface
{
    /**
     * @var TranslationsRepository
     */
    protected $repository;

    /**
     * Set the repository instance.
     *
     * @param Registry $doctrine
     * @param string $entity
     * @return void
     */
    public function setRepository($doctrine, $entity)
    {
        $this->repository = $doctrine->getManager()->getRepository($entity);
    }


    /**
     * @{inheritDoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($locale);

        foreach ($this->repository->getTranslations($locale, $domain) as $id => $translation) {
            $catalogue->set($id, $translation, $domain);
        }

        return $catalogue;
    }
}
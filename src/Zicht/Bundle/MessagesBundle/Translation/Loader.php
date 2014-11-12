<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Translation;

use \Symfony\Component\Translation\MessageCatalogue;
use \Symfony\Component\Translation\Loader\LoaderInterface;

use \Zicht\Bundle\MessagesBundle\TranslationsRepository;

/**
 * Translation loader implementation
 *
 * @package Zicht\Bundle\MessagesBundle\Translation
 */
class Loader implements LoaderInterface
{
    /**
     * Repository used to store and load messages from.
     *
     * @var TranslationsRepository
     */
    protected $repository;


    /**
     * Set the repository to load the messages from.
     *
     * @param \Zicht\Bundle\MessagesBundle\TranslationsRepository $repo
     * @return void
     */
    public function setRepository(TranslationsRepository $repo)
    {
        $this->repository = $repo;
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
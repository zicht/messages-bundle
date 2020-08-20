<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Translation;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Zicht\Bundle\MessagesBundle\TranslationsRepository;

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

    // initially enabled, but disabled during cache:warmup (see CacheHook)
    // we need to have the loader registered, but not running on cache-warmup as it should not access the DB. We run it manually.
    private $enabled = true;

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

    public function setEnabled(bool $state)
    {
        $this->enabled = $state;
    }

    /**
     * Load
     *
     * @param mixed $resource
     * @param string $locale
     * @param string $domain
     * @return MessageCatalogue
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($locale);

        if (!$this->enabled) {
            return $catalogue;
        }

        foreach ($this->repository->getTranslations($locale, $domain) as $id => $translation) {
            $catalogue->set($id, $translation, $domain);
        }

        return $catalogue;
    }
}

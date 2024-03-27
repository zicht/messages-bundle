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
     */
    public function setRepository(TranslationsRepository $repo): void
    {
        $this->repository = $repo;
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);

        foreach ($this->repository->getTranslations($locale, $domain) as $id => $translation) {
            $catalogue->set($id, $translation, $domain);
        }

        return $catalogue;
    }
}

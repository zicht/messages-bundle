<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zicht\Bundle\MessagesBundle\Entity\Message;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;
use Zicht\Bundle\MessagesBundle\Translation\Loader;

/**
 * Subscribes to the Doctrine entity manager to flush Symfony's translation cache
 */
class FlushCatalogueCacheSubscriber implements EventSubscriber
{
    protected $isDirty;

    protected $flusher;

    private $loader;

    private $translator;

    private $cacheDir;

    public function __construct(FlushCatalogueCacheHelper $flusher, Loader $loader, TranslatorInterface $translator, string $cacheDir)
    {
        $this->isDirty = false;
        $this->flusher = $flusher;
        $this->loader = $loader;
        $this->translator = $translator;
        $this->cacheDir = $cacheDir;
    }


    /**
     * Listens to the flush event and checks if any of the configured entities was inserted, updated or deleted.
     * If so, invokes the helper to
     *
     * @param mixed $args
     * @return void
     */
    public function onFlush($args)
    {
        if (!$this->isDirty) {
            /** @var $em \Doctrine\ORM\EntityManager */
            $em = $args->getEntityManager();

            /** @var $uow \Doctrine\ORM\UnitOfWork */
            $uow = $em->getUnitOfWork();

            $array = array(
                $uow->getScheduledEntityUpdates(),
                $uow->getScheduledEntityDeletions(),
                $uow->getScheduledEntityInsertions(),
                $uow->getScheduledCollectionUpdates(),
                $uow->getScheduledCollectionDeletions(),
                $uow->getScheduledCollectionDeletions(),
                $uow->getScheduledCollectionUpdates()
            );

            foreach ($array as $obj) {
                foreach ($obj as $element) {
                    if ($element instanceof Message
                        || $element instanceof MessageTranslation
                    ) {
                        $this->isDirty = true;
                        break 2;
                    }
                }
            }
        }


    }

    public function postFlush()
    {
        if ($this->isDirty) {
            $this->flushCache();
        }
    }

    /**
     * Invokes the cache flusher.
     *
     * @return void
     */
    public function flushCache()
    {
        if ($this->isDirty) {
            $this->loader->setEnabled(true);
            $this->flusher->__invoke();
            $this->translator->warmUp($this->cacheDir);
            $this->loader->setEnabled(false);
            $this->isDirty = false;
        }
    }


    /**
     * @{inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::postFlush);
    }
}

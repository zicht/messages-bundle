<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zicht\Bundle\MessagesBundle\Entity;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper as Helper;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;
use Zicht\Bundle\MessagesBundle\Translation\Loader;

/**
 * Subscribes to the Doctrine entity manager to flush Symfony's translation cache
 */
class FlushCatalogueCacheSubscriber implements EventSubscriber
{
    /**
     * @var bool
     */
    protected $isDirty;

    /**
     * @var FlushCatalogueCacheHelper
     */
    protected $helper;

    /**
     * @var array
     */
    protected $entity;

    private $loader;

    private $translator;

    private $cacheDir;

    /**
     * Construct the subscriber with the passed cachedir.
     *
     * @param FlushCatalogueCacheHelper $helper
     * @param array $entities
     */
    public function __construct(Helper $helper, Loader $loader, TranslatorInterface $translator, string $cacheDir, $entities)
    {
        $this->isDirty = false;
        $this->helper = $helper;
        $this->entity = $entities;
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
                    if ($element instanceof Entity\Message
                        || $element instanceof Entity\MessageTranslation
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
            //$this->helper->setEnabled(true);
            call_user_func($this->helper);
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

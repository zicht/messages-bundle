<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Subscriber;

use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper as Helper;

/**
 * Subscribes to the Doctrine entity manager to flush Symfony's translation cache
 */
class FlushCatalogueCacheSubscriber implements \Doctrine\Common\EventSubscriber
{
    /**
     * Construct the subscriber with the passed cachedir.
     *
     * @param callback $helper
     * @param array $entities
     */
    public function __construct(Helper $helper, $entities)
    {
        $this->isDirty = false;
        $this->helper = $helper;
        $this->entity = $entities;
    }


    /**
     * Listens to the flush event and checks if any of the configured entities was inserted, updated or deleted.
     * If so, invokes the helper to
     *
     * @param $args
     */
    public function onFlush($args)
    {
        if (!$this->isDirty) {
            /** @var $em \Doctrine\ORM\EntityManager */
            $em = $args->getEntityManager();

            /** @var $uow \Doctrine\ORM\UnitOfWork */
            $uow = $em->getUnitOfWork();

            foreach(
                array(
                    $uow->getScheduledEntityUpdates(),
                    $uow->getScheduledEntityDeletions(),
                    $uow->getScheduledEntityInsertions(),
                    $uow->getScheduledCollectionUpdates(),
                    $uow->getScheduledCollectionDeletions(),
                    $uow->getScheduledCollectionDeletions(),
                    $uow->getScheduledCollectionUpdates()
                ) as $obj) {
                foreach ($obj as $element) {
                    if (
                        $element instanceof \Zicht\Bundle\MessagesBundle\Entity\Message
                     || $element instanceof \Zicht\Bundle\MessagesBundle\Entity\MessageTranslation
                    ) {
                        $this->isDirty = true;
                        break 2;
                    }
                }
            }
        }
        $this->flushCache();
    }

    public function flushCache()
    {
        call_user_func($this->helper);
        $this->isDirty = false;
    }


    public function getSubscribedEvents()
    {
        return array(
            \Doctrine\ORM\Events::onFlush,
        );
    }
}
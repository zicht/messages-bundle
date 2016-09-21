<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Zicht\Bundle\MessagesBundle\Entity\Message;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;
use Zicht\Bundle\MessagesBundle\Manager\MessageManager;

/**
 * Admin for the messages catalogue
 */
class MessageAdmin extends Admin
{
    /**
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * @param MessageManager $messageManager
     * @return void
     */
    public function setMessageManager(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * @{inheritDoc}
     */
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper->add('message');
    }

    /**
     * @{inheritDoc}
     */
    public function configureFormFields(FormMapper $formMapper)
    {
        // add the collection type for existing messages.
        $formMapper->with('General')
            ->add('message', null, array('required' => true))
            ->add(
                'domain',
                'choice',
                array('required' => true, 'choices' => $this->messageManager->getRepository()->getDomains())
            )
            ->end();

        if ($this->getSubject()->getId()) {
            $formMapper
                ->with('General')
                ->add(
                    'translations',
                    'sonata_type_collection',
                    array(),
                    array(
                        'edit' => 'inline',
                        'inline' => 'table',
                    )
                )
                ->end();
        }
    }

    /**
     * @{inheritDoc}
     */
    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('message')
            ->add('domain')
            ->add(
                '_action',
                'actions',
                array(
                    'actions' => array(
                        'show' => array(),
                        'edit' => array(),
                        'delete' => array(),
                    )
                )
            );
    }


    /**
     * @{inheritDoc}
     */
    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add(
            'domain',
            null,
            array(),
            'choice',
            array('choices' => $this->messageManager->getRepository()->getDomains())
        )
            ->add(
                'message',
                'doctrine_orm_callback',
                array(
                    'callback' => array($this, 'filteredOnTranslations')
                )
            );
    }

    /**
     * Custom search handler
     *
     * Changes the filter behaviour to also search in the message_translation table
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $field
     * @param array $value
     *
     * @return bool
     */
    public function filteredOnTranslations($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return false;
        }
        
        $queryBuilder->leftJoin(sprintf('%s.translations', $alias), 't');

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->like('o.message', ':tr'),
                $queryBuilder->expr()->like('t.translation', ':tr')
            )
        );

        $queryBuilder->setParameter('tr', '%' . $value['value'] . '%');

        return true;
    }

    /**
     * Pre persist
     *
     * @param Message $object
     */
    public function prePersist($object)
    {
        $this->preUpdate($object);
    }

    /**
     * Pre update
     *
     * @param Message $object
     */
    public function preUpdate($object)
    {
        $this->messageManager->addMissingTranslations($object);
        foreach ($object->getTranslations() as $translation) {
            $translation->setState(MessageTranslation::STATE_USER);
            $translation->setTranslation((string)$translation->getTranslation());
        }
    }
}

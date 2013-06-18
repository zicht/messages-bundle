<?php

namespace Zicht\Bundle\MessagesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;

/**
 * Admin for the messages catalogue
 */
class MessageAdmin extends Admin
{
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('message')
         ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        // add the collection type for existing messages.
        if ($this->getSubject()->getId()) {
            $formMapper
                ->with('General')
                    ->add('message', NULL, array('required' => TRUE))
                    ->add(
                        'translations',
                        'sonata_type_collection',
                        array(),
                        array(
                            'edit' => 'inline',
                            'inline' => 'table',
                        )
                    )
                ->end()
            ;
        } else {
            $formMapper
                ->with('General')
                    ->add('message', NULL, array('required' => TRUE))
                ->end()
            ;
        }
    }


    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('message')
            ->add('_action', 'actions', array(
                'actions' => array(
                    'view' => array(),
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('message', 'doctrine_orm_callback', array(
                'callback'   => array($this, 'filteredOnTranslations')
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
        if (!$value) {
            return;
        }

        $queryBuilder->leftJoin(sprintf('%s.translations', $alias), 't');

        $queryBuilder->where(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->like('o.message', ':tr'),
                $queryBuilder->expr()->like('t.translation', ':tr')
            )
        );

        $queryBuilder->setParameter('tr', '%' . $value['value'] . '%');

        return TRUE;
    }


    public function prePersist($object) {
        $object->addMissingTranslations();
    }

    public function preUpdate($object) {
        $object->addMissingTranslations();
    }
}
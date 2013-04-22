<?php

namespace Zicht\Bundle\MessagesBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class MessageTranslationAdmin extends Admin
{
    protected $parentAssociationMapping = 'message';

    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('translation')
        ;
    }

    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('locale', null, array('required' => true))
                ->add('translation', null, array('required' => true))
            ->end()
        ;
    }

    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('translation')
        ;
    }

    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('translation')
        ;
    }
}
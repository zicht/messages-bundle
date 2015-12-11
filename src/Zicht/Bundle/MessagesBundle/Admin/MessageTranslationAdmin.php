<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Admin;

use \Sonata\AdminBundle\Admin\Admin;
use \Sonata\AdminBundle\Form\FormMapper;
use \Sonata\AdminBundle\Datagrid\DatagridMapper;
use \Sonata\AdminBundle\Datagrid\ListMapper;
use \Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class MessageTranslationAdmin
 *
 * @package Zicht\Bundle\MessagesBundle\Admin
 */
class MessageTranslationAdmin extends Admin
{
    protected $parentAssociationMapping = 'message';

    /**
     * @{inheritDoc}
     */
    public function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('translation')
        ;
    }

    /**
     * @{inheritDoc}
     */
    public function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('General')
                ->add('locale', null, array('required' => true))
                ->add('translation', null, array('required' => true))
            ->end()
        ;
    }

    /**
     * @{inheritDoc}
     */
    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('translation')
        ;
    }

    /**
     * @{inheritDoc}
     */
    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('translation')
        ;
    }
}
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
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;

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
        $showMapper->add('translation');
    }

    /**
     * @{inheritDoc}
     */
    public function configureFormFields(FormMapper $formMapper)
    {
        $translator = $this->configurationPool->getContainer()->get('translator');
        $translationDomain = $this->getTranslationDomain();
        $translate = function ($value) use ($translator, $translationDomain) {
            return $translator->trans($value, [], $translationDomain);
        };

        $formMapper
            ->with('General')
                ->add('locale', null, ['required' => true])
                ->add('translation', null, ['required' => false])
                ->add('state', 'choice', ['disabled' => true, 'choices' => array_map($translate, $this->getStateChoices())])
            ->end();
    }

    /**
     * @{inheritDoc}
     */
    public function configureListFields(ListMapper $listMapper)
    {
        $listMapper->addIdentifier('translation');
    }

    /**
     * @{inheritDoc}
     */
    public function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper->add('translation');
    }

    /**
     * Returns available states
     *
     * @return array
     */
    protected function getStateChoices()
    {
        return [
            MessageTranslation::STATE_UNKNOWN => 'message.state.unknown',
            MessageTranslation::STATE_IMPORT => 'message.state.import',
            MessageTranslation::STATE_USER => 'message.state.user',
        ];
    }
}

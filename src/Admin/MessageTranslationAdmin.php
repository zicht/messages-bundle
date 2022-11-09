<?php
/**
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;

/**
 * Class MessageTranslationAdmin
 *
 * @package Zicht\Bundle\MessagesBundle\Admin
 */
class MessageTranslationAdmin extends AbstractAdmin
{
    /**
     * Returns available states
     *
     * @return array
     */
    public static function getStateChoices()
    {
        return [
            'message.state.unknown' => MessageTranslation::STATE_UNKNOWN,
            'message.state.import' => MessageTranslation::STATE_IMPORT,
            'message.state.user' => MessageTranslation::STATE_USER,
        ];
    }

    /**
     * @{inheritDoc}
     */
    public function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper->add('translation');
    }

    /**
     * @{inheritDoc}
     */
    public function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->with('General')
                ->add('locale', null, ['required' => true])
                ->add('translation', null, ['required' => false])
                ->add(
                    'state',
                    ChoiceType::class,
                    [
                        'disabled' => true,
                        'choices' => self::getStateChoices(),
                        'choice_translation_domain' => $this->getTranslationDomain(),
                    ]
                )
            ->end();
    }

    /**
     * @{inheritDoc}
     */
    public function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('translation');
    }

    /**
     * @{inheritDoc}
     */
    public function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper->add('translation');
    }
}

<?php
/**
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Zicht\Bundle\MessagesBundle\Entity\Message;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;
use Zicht\Bundle\MessagesBundle\Manager\MessageManager;

/**
 * Admin for the messages catalogue
 */
class MessageAdmin extends AbstractAdmin
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
    public function configureShowFields(ShowMapper $show): void
    {
        $show->add('message');
    }

    /**
     * @{inheritDoc}
     */
    public function configureFormFields(FormMapper $formMapper): void
    {
        // add the collection type for existing messages.
        $formMapper->with('admin.tab.general')
            ->add('message', null, array('required' => true))
            ->add(
                'domain',
                ChoiceType::class,
                array('required' => true, 'choices' => $this->messageManager->getRepository()->getDomains())
            )
            ->end();

        if ($this->getSubject()->getId()) {
            $formMapper
                ->with('General')
                ->add(
                    'translations',
                    CollectionType::class,
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
    public function configureListFields(ListMapper $listMapper): void
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
    public function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add(
                'domain',
                null,
                [
                    'field_type' => ChoiceType::class,
                    'field_options' => ['choices' => $this->messageManager->getRepository()->getDomains()],
                ]
            )
            ->add(
                'message',
                CallbackFilter::class,
                array(
                    'callback' => array($this, 'filteredOnTranslations')
                )
            )
            ->add(
                'status',
                CallbackFilter::class,
                ['callback' => [$this, 'filteredOnStatus'], 'field_type' => ChoiceType::class, 'field_options' => ['choices' => MessageTranslationAdmin::getStateChoices(), 'translation_domain' => 'admin']],
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
     * @param FilterData $value
     *
     * @return bool
     */
    public function filteredOnTranslations($queryBuilder, $alias, $field, $value)
    {
        if (!$value->getValue()) {
            return;
        }

        $queryBuilder->leftJoin(sprintf('%s.translations', $alias), 't1');

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->like('o.message', ':tr'),
                $queryBuilder->expr()->like('t1.translation', ':tr')
            )
        );

        $queryBuilder->setParameter('tr', '%' . $value->getValue() . '%');

        return true;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     * @param string $field
     * @param FilterData $value
     * @return bool
     */
    public function filteredOnStatus($queryBuilder, $alias, $field, $value)
    {
        if (!$value->getValue()) {
            return;
        }

        $queryBuilder
            ->leftJoin(sprintf('%s.translations', $alias), 't2')
            ->andWhere($queryBuilder->expr()->eq('t2.state', ':status'))
            ->setParameter('status', $value->getValue());
        return true;
    }

    /**
     * Pre persist
     *
     * @param Message $object
     */
    public function prePersist(object $object): void
    {
        $this->preUpdate($object);
    }

    /**
     * Pre update
     *
     * @param Message $object
     */
    public function preUpdate(object $object): void
    {
        $this->messageManager->addMissingTranslations($object);
        foreach ($object->getTranslations() as $translation) {
            $translation->setState(MessageTranslation::STATE_USER);
            $translation->setTranslation((string)$translation->getTranslation());
        }
    }
}

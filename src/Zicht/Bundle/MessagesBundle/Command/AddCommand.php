<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

use \Zicht\Bundle\MessagesBundle\Entity\Message;
use \Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;

/**
 * Add a message to the database message catalogue.
 */
class AddCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:messages:add')
            ->setDescription('Add a message')
            ->addArgument('message', InputArgument::REQUIRED, "The message id")
            ->addArgument('domain', InputArgument::OPTIONAL, "The message domain", 'messages')
            ->addOption(
                'locale',
                'l',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                "Locales to add translations for",
                array()
            )
        ;
    }


    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = new Message();
        $message->message = $input->getArgument('message');
        $message->domain = $input->getArgument('domain');

        foreach ($input->getOption('locale') as $locale) {
            if ($input->isInteractive()) {
                if ($translation = $this->getHelperSet()->get('dialog')->ask($output, sprintf('Translation for %s: ', $locale))) {
                    $message->addTranslations(new MessageTranslation($locale, $translation));
                }
            }
        }
        $this->getContainer()->get('doctrine')->getManager()->persist($message);
        $this->getContainer()->get('doctrine')->getManager()->flush();
    }
}
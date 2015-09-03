<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

use \Zicht\Bundle\MessagesBundle\Entity\Message;
use \Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;

class CheckCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:messages:check')
            ->setDescription('Check whether the database translations are working');
}

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issues = $this->getContainer()->get('zicht_messages.manager')->check(
            $this->getContainer()->get('translator'),
            $this->getContainer()->getParameter('kernel.root_dir')
        );

        if (!count($issues)) {
            $output->writeln('Database translation seem to work ok');
        } else {
            $output->writeln("Some things need your attention:");
            foreach ($issues as $issue) {
                $output->writeln(" * $issue");
            }
            $output->writeln("\nPlease remember to flush the cache after any changes you make");
        }
    }
}
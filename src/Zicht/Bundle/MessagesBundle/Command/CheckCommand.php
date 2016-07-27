<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks all translations in the database (self test)
 */
class CheckCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this->setName('zicht:messages:check')
            ->setDescription('Check whether the database translations are working')
            ->addOption('fix', '', InputOption::VALUE_NONE, 'Try to fix whatever can be fixed');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issues = $this->getContainer()->get('zicht_messages.manager')->check(
            $this->getContainer()->get('translator'),
            $this->getContainer()->getParameter('kernel.root_dir'),
            $input->getOption('fix')
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

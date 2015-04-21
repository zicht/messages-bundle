<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */
namespace Zicht\Bundle\MessagesBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;


/**
 * Does some setup/sanity checks
 */
class CheckCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:messages:check')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Fix problems that can be fixed')
        ;
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
                list($description, $fix) = $issue;
                $output->writeln(" * $description");
                if ($input->getOption('fix')) {
                    if ($fix) {
                        call_user_func($fix, $output);
                    } else {
                        $output->writeln("This can not be fixed automatically. Did you flush the cache?");
                    }
                }
            }
            if (!$input->getOption('fix')) {
                $output->writeln("\nYou may pas the --fix option to try to fix these issues");
                $output->writeln("Please remember to flush the cache after any changes you make");
            }
        }
    }
}
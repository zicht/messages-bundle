<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Zicht\Bundle\SroBundle\Command\StatusCommand as SroStatusCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command checks if the guids that are present in the local database correspond with the guids that are used
 * in SRO
 */
class FlushCommand extends ContainerAwareCommand {
    /**
     * @{inheritdoc}
     */
    protected function configure() {
        $this
            ->setName('zicht:messages:flush')
            ->setDescription('Flush symfony\'s message catalogue cache')
        ;
    }


    /**
     * @{inheritdoc}
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $helper = new \Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper(
            $this->getContainer()->getParameter('kernel.cache_dir') . '/translations'
        );
        $result = $helper();

        if ($output->getVerbosity() > 1) {
            if ($result) {
                $output->writeln("Catalogue cache was flushed");
            } else {
                $output->writeln("Nothing to flush");
            }
        }
    }
}
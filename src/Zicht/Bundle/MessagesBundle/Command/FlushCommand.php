<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;

/**
 * This command checks if the guids that are present in the local database correspond with the guids that are used
 * in SRO
 */
class FlushCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:messages:flush')
            ->setDescription('Flush symfony\'s message catalogue cache');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheDir = $this->getContainer()->getParameter('kernel.cache_dir') . '/translations';

        $helper = new FlushCatalogueCacheHelper($cacheDir);
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
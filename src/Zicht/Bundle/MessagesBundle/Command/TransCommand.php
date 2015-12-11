<?php
/**
 * @author Boudewijn Schoon <boudewijn@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Takes a string and tries to translate it
 */
class TransCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:messages:translate')
            ->setDescription('Translate a message')
            ->addArgument('string', null, 'The message to translate');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translator = $this->getContainer()->get('translator');
        $domains = ['template', 'messages', 'forms'];
        foreach ($domains as $domain) {
            $output->writeln(sprintf(
                '%13s "%s" -> "%s"',
                sprintf('[%s]', $domain),
                $input->getArgument('string'),
                $translator->trans($input->getArgument('string'), [], $domain)));
        }
    }
}
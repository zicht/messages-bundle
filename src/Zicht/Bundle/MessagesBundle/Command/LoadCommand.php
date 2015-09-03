<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command loads messages from predefined message configuration files.
 */
class LoadCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:messages:load')
            ->setDescription('Load messages from a source file into the database')
            ->addArgument('file', InputArgument::REQUIRED, 'File to load the messages from')
            ->addOption(
                'overwrite',
                'o',
                InputOption::VALUE_NONE,
                'Overwrite existing translations in the database (revert to the translation file)'
            );
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('file');

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $loaders = array(
            'php' => new PhpFileLoader(),
            'yml' => new YamlFileLoader()
        );

        $overwrite = $input->getOption('overwrite');

        if (!array_key_exists($ext, $loaders) || !preg_match('/(.*)\.(\w+)\.[^.]+/', basename($filename), $m)) {
            $output->writeln('Unsupported file type: ' . $filename);
            return 1;
        } else {
            $catalogue = $loaders[$ext]->load($filename, $m[2], $m[1]);

            $numLoaded = $this->getContainer()->get('zicht_messages.manager')->loadMessages(
                $catalogue,
                $overwrite,
                function ($e, $key) use ($output) {
                    $output->writeln(sprintf("<error>%s</error> while processing message %s\n", $e->getMessage(), $key));
                }
            );

            $output->writeln(sprintf("<info>%d</info> messages loaded", $numLoaded));
        }
    }
}
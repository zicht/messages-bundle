<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;

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
            ->addArgument('file', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'File to load the messages from.  Filename MUST match the pattern: "NAME.LOCALE.EXTENTION')
            ->addOption('sync', null, InputOption::VALUE_NONE, 'Whether to sync the status in the database before loading the file (useful for migration purposes)')
            ->addOption('overwrite-unknown', null, null, 'Overwrite existing translations that have state "unknown"')
            ->addOption('overwrite-import', null, null, 'Overwrite existing translations that have state "import"')
            ->addOption('overwrite-user', null, null, 'Overwrite existing translations that have state "user"')
            ->addOption('overwrite', null, null, 'The same as --overwrite-unknown, --overwrite-import, and --overwrite-user');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $input->getArgument('file');
        $isSync = $input->getOption('sync');
        $overwrite = array(
            MessageTranslation::STATE_UNKNOWN => $input->getOption('overwrite') || $input->getOption('overwrite-unknown'),
            MessageTranslation::STATE_IMPORT => $input->getOption('overwrite') || $input->getOption('overwrite-import'),
            MessageTranslation::STATE_USER => $input->getOption('overwrite') || $input->getOption('overwrite-user'),
        );
        $loaders = array(
            'php' => new PhpFileLoader(),
            'yml' => new YamlFileLoader()
        );

        $messageManager = $this->getContainer()->get('zicht_messages.manager');

        $messageManager->transactional(
            function () use ($files, $isSync, $loaders, $overwrite, $output, $messageManager) {
                foreach ($files as $filename) {
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);

                    if (!array_key_exists($ext, $loaders) || !preg_match('/(.*)\.(\w+)\.[^.]+/', basename($filename), $m)) {
                        $output->writeln('Unsupported file type: ' . $filename);
                    } else {
                        $catalogue = $loaders[$ext]->load($filename, $m[2], $m[1]);

                        $numLoaded = $messageManager->loadMessages(
                            $catalogue,
                            $overwrite,
                            function ($e, $key) use ($output) {
                                $output->writeln(sprintf("<error>%s</error> while processing message %s\n", $e->getMessage(), $key));
                            }
                        );
                        $output->writeln(sprintf("<info>%d</info> messages loaded from <info>%s</info>", $numLoaded, $filename));

                        if ($isSync) {
                            list($import, $user) = $messageManager->syncState($catalogue);
                            $output->writeln(sprintf('    (synced state: <info>%d</info> set to \'import\'; <info>%d</info> set to \'user\')', $import, $user));
                        }
                    }
                }
            }
        );
    }
}

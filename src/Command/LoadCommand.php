<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;
use Zicht\Bundle\MessagesBundle\Manager\MessageManager;

/**
 * This command loads messages from predefined message configuration files.
 */
class LoadCommand extends Command
{
    protected static $defaultName = 'zicht:messages:load';

    /** @var MessageManager */
    private $messageManager;

    /** @var FlushCatalogueCacheHelper */
    private $cacheHelper;

    public function __construct(MessageManager $messageManager, FlushCatalogueCacheHelper $cacheHelper, string $name = null)
    {
        parent::__construct($name);
        $this->messageManager = $messageManager;
        $this->cacheHelper = $cacheHelper;
    }

    protected function configure()
    {
        $this
            ->setDescription('Load messages from a source file into the database')
            ->addArgument('file', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'File to load the messages from.  Filename MUST match the pattern: "NAME.LOCALE.EXTENTION')
            ->addOption('sync', null, InputOption::VALUE_NONE, 'Whether to sync the status in the database before loading the file (useful for migration purposes)')
            ->addOption('overwrite-unknown', null, null, 'Overwrite existing translations that have state "unknown"')
            ->addOption('overwrite-import', null, null, 'Overwrite existing translations that have state "import"')
            ->addOption('overwrite-user', null, null, 'Overwrite existing translations that have state "user"')
            ->addOption('overwrite', null, null, 'The same as --overwrite-unknown, --overwrite-import, and --overwrite-user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
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

        $messageManager = $this->messageManager;
        $cacheHelper = $this->cacheHelper;
        $totalNumUpdated = 0;
        $totalNumLoaded = 0;

        $progress = new ProgressBar($io, sizeof($files));
        $progress->setRedrawFrequency(10);

        $messageManager->transactional(
            function () use ($files, $isSync, $loaders, $overwrite, $io, $progress, $messageManager, &$totalNumUpdated, &$totalNumLoaded) {
                foreach ($files as $filename) {
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    if (!array_key_exists($ext, $loaders) || !preg_match('/(.*)\.(\w+)\.[^.]+/', basename($filename), $m)) {
                        $io->warning('Unsupported file type: ' . $filename);
                    } else {
                        $catalogue = $loaders[$ext]->load($filename, $m[2], $m[1]);

                        list($numLoaded, $numUpdated) = $messageManager->loadMessages(
                            $catalogue,
                            $overwrite,
                            function ($e, $key) use ($io) {
                                $io->error(sprintf("%s while processing message %s", $e->getMessage(), $key));
                            },
                            MessageTranslation::STATE_IMPORT
                        );
                        $progress->advance();
                        $totalNumUpdated += $numUpdated;
                        $totalNumLoaded += $numLoaded;

                        if ($isSync) {
                            list($import, $user) = $messageManager->syncState($catalogue);
                            $io->success(sprintf('synced state: %d set to \'import\'; %d set to \'user\'', $import, $user));
                        }
                    }
                }
            }
        );

        $progress->finish();
        $io->success(sprintf("%d/%d messages updated/loaded from %d files", $totalNumUpdated, $totalNumLoaded, sizeof($files)));

        if ($totalNumUpdated > 0) {
            $cacheHelper();
        }

        return 0;
    }
}

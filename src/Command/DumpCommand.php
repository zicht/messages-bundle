<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Zicht\Bundle\MessagesBundle\Translation\Loader;

/**
 * Add a message to the database message catalogue.
 */
#[AsCommand('zicht:messages:dump')]
class DumpCommand extends Command
{
    private Loader $loader;

    public function __construct(Loader $loader, string $name = null)
    {
        parent::__construct($name);
        $this->loader = $loader;
    }

    public function configure()
    {
        $this
            ->setDescription('Dump all messages from the translation files and database to stdout')
            ->addArgument('locale', InputArgument::REQUIRED, "The locale to dump messages for")
            ->addArgument('domain', InputArgument::OPTIONAL, "The domain to dump messages for", 'messages')
            ->addOption('format', '', InputOption::VALUE_REQUIRED, 'The output format to use', 'yml');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $catalogue = $this->loader->load('', $input->getArgument('locale'), $input->getArgument('domain'));

        $messages = $catalogue->all($input->getArgument('domain'));

        switch (strtolower($input->getOption('format'))) {
            case 'php':
                echo '<?php ', "\n";
                echo 'return ';
                var_export($messages);
                echo ';', "\n";
                break;
            case 'yml':
            case 'yaml':
                echo Yaml::dump($messages, 4, 4);
                break;
            default:
                $output->writeln('<error>Invalid format supplied, currently only `yml` and `php` are supported</error>');
        }

        return Command::SUCCESS;
    }
}

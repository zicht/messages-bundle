<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Add a message to the database message catalogue.
 */
class DumpCommand extends ContainerAwareCommand
{
    /**
     * @{inheritDoc}
     */
    public function configure()
    {
        $this
            ->setName('zicht:messages:dump')
            ->setDescription('Dump all messages from the translation files and database to stdout')
            ->addArgument('locale', InputArgument::REQUIRED, "The locale to dump messages for")
            ->addArgument('domain', InputArgument::OPTIONAL, "The domain to dump messages for", 'messages')
            ->addOption('format', '', InputOption::VALUE_REQUIRED, 'The output format to use', 'yml');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $loader \Zicht\Bundle\MessagesBundle\Translation\Loader */
        $loader = $this->getContainer()->get('translation.loader.zicht_messages');
        $catalogue = $loader->load('', $input->getArgument('locale'), $input->getArgument('domain'));

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
    }
}

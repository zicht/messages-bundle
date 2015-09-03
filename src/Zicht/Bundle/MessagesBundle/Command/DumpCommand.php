<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->addArgument('locale', InputArgument::REQUIRED, "The locale to dump messages for");
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $loader \Zicht\Bundle\MessagesBundle\Translation\Loader */
        $loader = $this->getContainer()->get('translation.loader.zicht_messages');
        $catalogue = $loader->load('', $input->getArgument('locale'));

        echo '<?php ', "\n";
        echo 'return ';
        var_export($catalogue->all('messages'));
        echo ';', "\n";
    }
}
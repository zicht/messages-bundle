<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\MessagesBundle\Entity\Message;
use Zicht\Bundle\MessagesBundle\Entity\MessageTranslation;

/**
 * Add a message to the database message catalogue.
 */
class AddCommand extends Command
{
    protected static $defaultName = 'zicht:messages:add';

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine, string $name = null)
    {
        parent::__construct($name);
        $this->doctrine = $doctrine;
    }

    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Add a single message to the database')
            ->addArgument('message', InputArgument::REQUIRED, "The message id")
            ->addArgument('domain', InputArgument::OPTIONAL, "The message domain", 'messages')
            ->addOption(
                'locale',
                'l',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                "Locales to add translations for",
                array()
            );
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = new Message();
        $message->message = $input->getArgument('message');
        $message->domain = $input->getArgument('domain');

        foreach ($input->getOption('locale') as $locale) {
            if ($input->isInteractive()) {
                if ($translation = $this->getHelperSet()->get('dialog')->ask($output, sprintf('Translation for %s: ', $locale))) {
                    $message->addTranslations(new MessageTranslation($locale, $translation));
                }
            }
        }
        $this->doctrine->getManager()->persist($message);
        $this->doctrine->getManager()->flush();
    }
}

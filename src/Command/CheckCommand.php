<?php
/**
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zicht\Bundle\MessagesBundle\Manager\MessageManager;

/**
 * Checks all translations in the database (self test)
 */
#[AsCommand('zicht:messages:check')]
class CheckCommand extends Command
{
    private MessageManager $messagesManager;

    private TranslatorInterface $translator;

    private string $projectDir;

    public function __construct(MessageManager $messageManager, TranslatorInterface $translator, string $projectDir, string $name = null)
    {
        parent::__construct($name);
        $this->messagesManager = $messageManager;
        $this->translator = $translator;
        $this->projectDir = $projectDir;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check whether the database translations are working')
            ->addOption('fix', '', InputOption::VALUE_NONE, 'Try to fix whatever can be fixed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issues = $this->messagesManager->check(
            $this->translator,
            $this->projectDir,
            $input->getOption('fix')
        );

        if (!count($issues)) {
            $output->writeln('Database translation seem to work ok');
        } else {
            $output->writeln("Some things need your attention:");
            foreach ($issues as $issue) {
                $output->writeln(" * $issue");
            }
            $output->writeln("\nPlease remember to flush the cache after any changes you make");
        }

        return Command::SUCCESS;
    }
}

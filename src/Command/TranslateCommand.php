<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;
use Zicht\Bundle\MessagesBundle\Translator\MessageTranslator;

class TranslateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'zicht:messages:translate';

    /** @var MessageTranslator */
    private $translator;

    public function __construct(MessageTranslator $translator, string $name = null)
    {
        parent::__construct($name);
        $this->translator = $translator;
    }

    protected function configure()
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'The full path to the file you want to have translated. The contents of this file will be updated with translated values.')
            ->addOption('--source', '-s', InputOption::VALUE_REQUIRED, 'The source-language.')
            ->addOption('--target', '-t', InputOption::VALUE_OPTIONAL, 'The target-language. Override if auto-discovered target is not in line with your translation API specs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->translator->translate(new File($input->getArgument('file')), $input->getOption('source'), $input->getOption('target'));

        return 0;
    }
}

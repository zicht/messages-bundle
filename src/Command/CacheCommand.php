<?php


namespace Zicht\Bundle\MessagesBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Reader\TranslationReaderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;

class CacheCommand extends Command
{
    protected static $defaultName = 'zicht:messages:cache';

    private $cacheDir;

    private $loader;

    private $translationReader;

    private $flusher;

    private $translator;

    public function __construct(TranslatorInterface $translator, FlushCatalogueCacheHelper $flushCatalogueCacheHelper, TranslationReaderInterface $translationReader, LoaderInterface $loader, string $cacheDir, string $name = null)
    {
        parent::__construct($name);
        $this->cacheDir = $cacheDir;
        $this->loader = $loader;
        $this->translationReader = $translationReader;
        $this->flusher = $flushCatalogueCacheHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);
        try {
            $this->loader->setEnabled(true);
            $this->flusher->__invoke();
            $this->translator->warmUp($this->cacheDir);
            $this->loader->setEnabled(false);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 1;
        }
        return 0;
    }
}

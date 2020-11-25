<?php

namespace Zicht\Bundle\MessagesBundle\Subscriber;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zicht\Bundle\MessagesBundle\Helper\FlushCatalogueCacheHelper;

class RequestListener
{
    private string $cacheDir;

    private $loader;

    private $flusher;

    private $translator;

    public function __construct(TranslatorInterface $translator, LoaderInterface $loader, FlushCatalogueCacheHelper $flusher, string $cacheDir)
    {
        $this->translator = $translator;
        $this->loader = $loader;
        $this->flusher = $flusher;
        $this->cacheDir = $cacheDir;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $fs = new Filesystem();
        $fs->mkdir($this->cacheDir . '/translations');

        $file = $this->cacheDir . '/translations/__zicht_init';
        if ($fs->exists($file)) {
            return;
        }

        $fs->dumpFile($file, (new \DateTime())->format('c'));

        $this->loader->setEnabled(true);
        $this->flusher->__invoke();
        $this->translator->warmUp($this->cacheDir);
        $this->loader->setEnabled(false);
    }
}

<?php


namespace Zicht\Bundle\MessagesBundle\Translation;


use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;

class CacheHook  implements CacheWarmerInterface
{
    private $loader;

    /**
     * CacheHook constructor.
     * @param LoaderInterface|Loader $loader
     */
    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function isOptional()
    {
        return false;
    }

    public function warmUp($cacheDir)
    {
        $this->loader->setEnabled(false);
    }
}

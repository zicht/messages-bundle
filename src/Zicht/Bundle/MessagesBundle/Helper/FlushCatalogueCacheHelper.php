<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\Helper;

use \Symfony\Component\Finder\Finder;

/**
 * Helper class to flush the catalogue cache
 */
class FlushCatalogueCacheHelper
{
    /**
     * Initialize the helper with the specified cache dir and filename
     *
     * @param string $cacheDir
     * @param string $fileNamePattern
     */
    public function __construct($cacheDir, $fileNamePattern = 'catalogue*')
    {
        $this->cacheDir = $cacheDir;
        $this->fileNamePattern = $fileNamePattern;
        $this->isEnabled = true;
    }


    /**
     * Change whether or not the helper should be enabled
     *
     * @param $enabled
     */
    public function setEnabled($enabled)
    {
        $this->isEnabled = (bool)$enabled;
    }


    /**
     * Removes all files from the cache dir, matching the configured pattern
     *
     * @return int
     */
    public function __invoke()
    {
        if (!$this->isEnabled) {
            return -1;
        }

        $removed = 0;
        if (is_dir($this->cacheDir)) {
            $finder = new Finder();

            $files = array();
            foreach ($finder->in($this->cacheDir)->files()->name($this->fileNamePattern) as $file) {
                if (is_callable('apc_delete_file')) {
                    apc_delete_file(@$file->getPathname());
                }
                if (is_callable('opcache_invalidate')) {
                    opcache_invalidate(@$file->getPathname(), true);
                }
                if (unlink(@$file->getPathname())) {
                    $files[]= @$file->getPathname();
                    $removed ++;
                }
            }
        }
        return $removed;
    }
}
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
    }


    /**
     * Removes all files from the cache dir, matching the configured pattern
     *
     * @return int
     */
    public function __invoke()
    {
        $removed = 0;
        if (is_dir($this->cacheDir)) {
            $finder = new Finder();

            foreach ($finder->in($this->cacheDir)->files()->name($this->fileNamePattern) as $file) {
                if (unlink(@$file->getPathname())) {
                    $removed ++;
                }
            }
        }
        return $removed;
    }
}
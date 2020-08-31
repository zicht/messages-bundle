<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ReorderTranslationResourceFilesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        /* This Compiler Pass is searching for .db translation files to place them at the end
           This will cause the Message Catalogue to load these resources as last and thus
           the effect of overriding messages is maintained
         */
        $translator = $container->findDefinition('translator.default');
        $options = $translator->getArgument(4);
        $resourceFilesByLocale =& $options['resource_files'];

        foreach (array_keys($resourceFilesByLocale) as $locale) {
            uksort(
                $resourceFilesByLocale[$locale],
                function ($keyA, $keyB) use ($resourceFilesByLocale, $locale) {
                    $fileA = $resourceFilesByLocale[$locale][$keyA];
                    $fileB = $resourceFilesByLocale[$locale][$keyB];
                    if (substr($fileA, -3) === '.db' && substr($fileB, -3) !== '.db') {
                        return 1;
                    }
                    if (substr($fileB, -3) === '.db' && substr($fileA, -3) !== '.db') {
                        return -1;
                    }

                    return $keyA < $keyB ? -1 : 1;
                }
            );
        }

        $translator->replaceArgument(4, $options);
    }
}

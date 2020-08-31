<?php
/**
 * @copyright Zicht Online <https://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zicht\Bundle\MessagesBundle\DependencyInjection\Compiler\ReorderTranslationResourceFilesPass;

class ZichtMessagesBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ReorderTranslationResourceFilesPass());
    }
}

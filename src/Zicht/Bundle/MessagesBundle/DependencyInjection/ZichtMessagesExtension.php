<?php

namespace Zicht\Bundle\MessagesBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ZichtMessagesExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config/')));
        $loader->load('services.xml');

        if (!empty($config['locales'])) {
            $container->getDefinition('zicht_messages.admin.message')->addMethodCall('setLocales', array($config['locales']));
        }
    }
}
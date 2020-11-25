<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\MessagesBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zicht\Bundle\MessagesBundle\Subscriber\RequestListener;

/**
 * DI extension for messages bundle
 *
 * @package Zicht\Bundle\MessagesBundle\DependencyInjection
 */
class ZichtMessagesExtension extends Extension
{
    /**
     * @{inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__ . '/../Resources/config/')));
        $loader->load('services.xml');
        $loader->load('admin.xml');

        if (!empty($config['locales'])) {
            $container->getDefinition('zicht_messages.manager')->addMethodCall('setLocales', array($config['locales']));
        }

        $container->getDefinition('zicht_messages.admin.message')
            ->addMethodCall(
                'setMessageManager',
                array(new Reference('zicht_messages.manager'))
            );

        if (!$container->getParameter('kernel.debug')) {
            $container->removeDefinition(RequestListener::class);
        }
    }
}

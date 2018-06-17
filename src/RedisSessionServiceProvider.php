<?php

declare(strict_types = 1);

namespace Drupal\redis_session;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\redis_session\Session\Storage\RedisSessionHandler;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Jens Schulze, github.com/jensschulze
 */
class RedisSessionServiceProvider extends ServiceProviderBase
{
    /**
     * Modify existing service definitions.
     *
     * @param ContainerBuilder $container
     *
     * @throws ServiceNotFoundException
     */
    public function alter(ContainerBuilder $container): void
    {
        // Use the ExtranetSessionManager as session manager
        $definition = $container->getDefinition('session_handler.storage');
        $definition->setClass(RedisSessionHandler::class);
        $requestStack = new Reference('request_stack');
        $redisFactory = new Reference('redis.factory');
        $definition->setArguments([$requestStack, $redisFactory]);
    }
}
<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ChampsLibres\WopiLib\Configuration\WopiConfiguration;
use ChampsLibres\WopiLib\Configuration\WopiConfigurationInterface;
use ChampsLibres\WopiLib\Discovery\WopiDiscovery;
use ChampsLibres\WopiLib\Discovery\WopiDiscoveryInterface;
use ChampsLibres\WopiLib\Service\Contract\DocumentLockManagerInterface;
use ChampsLibres\WopiLib\Service\DocumentLockManager;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services
        ->defaults()
        ->autoconfigure(true)
        ->autowire(true);

    $services
        ->load('ChampsLibres\\WopiBundle\\Controller\\', __DIR__ . '/../../Controller')
        ->tag('controller.service_arguments');

    $services
        ->set(WopiConfiguration::class)
        ->arg('$properties', '%wopi%');

    $services
        ->alias(WopiConfigurationInterface::class, WopiConfiguration::class);

    $services
        ->set(WopiDiscovery::class);

    $services
        ->alias(WopiDiscoveryInterface::class, WopiDiscovery::class);

    $services
        ->set(DocumentLockManager::class);

    $services
        ->alias(DocumentLockManagerInterface::class, DocumentLockManager::class);
};

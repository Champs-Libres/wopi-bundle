<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ChampsLibres\WopiBundle\EventListener\WopiDiscoveryListener;
use ChampsLibres\WopiLib\Discovery\WopiDiscovery;
use ChampsLibres\WopiLib\Discovery\WopiDiscoveryInterface;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services
        ->defaults()
        ->autoconfigure(true)
        ->autowire(true);

    $services
        ->set(WopiDiscoveryListener::class)
        ->tag(
            'kernel.event_listener',
            [
                'event' => 'kernel.request',
            ]
        );

    $services
        ->load('ChampsLibres\\WopiBundle\\Service\\', __DIR__ . '/../../Service');

    $services
        ->load('ChampsLibres\\WopiBundle\\Controller\\', __DIR__ . '/../../Controller')
        ->tag('controller.service_arguments');

    $services
        ->set(WopiDiscovery::class)
        ->arg('$configuration', '%wopi%');

    $services
        ->alias(WopiDiscoveryInterface::class, WopiDiscovery::class);
};

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
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\Psr18Client;

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
        ->set(WopiDiscovery::class)
        ->arg('$client', service('wopi_bundle.http_client'));

    $services
        ->alias(WopiDiscoveryInterface::class, WopiDiscovery::class);

    $services
        ->set('wopi_bundle.cached_http_client')
        ->class(CachingHttpClient::class)
        ->decorate('http_client')
        ->args([
            service('.inner'),
            service('http_cache.store'),
        ]);

    $services
        ->set('wopi_bundle.http_client')
        ->class(Psr18Client::class)
        ->decorate('psr18.http_client')
        ->args([
            service('wopi_bundle.cached_http_client'),
        ]);
};

<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services
        ->defaults()
        ->autoconfigure(true)
        ->autowire(true);

    $services
        ->load('ChampsLibres\\WopiBundle\\Service\\', __DIR__ . '/../../Service');

    $services
        ->load('ChampsLibres\\WopiBundle\\Controller\\', __DIR__ . '/../../Controller')
        ->tag('controller.service_arguments');
};

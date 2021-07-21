<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $container
        ->services()
        ->defaults()
        ->autoconfigure(true)
        ->autowire(true);

    $container
        ->services()->load('ChampsLibres\\WopiBundle\\Service\\', __DIR__ . '/../../Service')
        ->autowire(true)
        ->autoconfigure(true);

    $container
        ->services()->load('ChampsLibres\\WopiBundle\\Controller\\', __DIR__ . '/../../Controller')
        ->autowire(true)
        ->autoconfigure(true)
        ->tag('controller.service_arguments');
};

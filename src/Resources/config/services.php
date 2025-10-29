<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ChampsLibres\WopiBundle\Service\Wopi;
use ChampsLibres\WopiLib\Contract\Service\Clock\ClockInterface;
use ChampsLibres\WopiLib\Contract\Service\Configuration\ConfigurationInterface;
use ChampsLibres\WopiLib\Contract\Service\Discovery\DiscoveryInterface;
use ChampsLibres\WopiLib\Contract\Service\DocumentLockManagerInterface;
use ChampsLibres\WopiLib\Contract\Service\ProofValidatorInterface;
use ChampsLibres\WopiLib\Contract\Service\Utils\DotNetTimeConverterInterface;
use ChampsLibres\WopiLib\Contract\Service\WopiInterface;
use ChampsLibres\WopiLib\Service\Clock\SystemClock;
use ChampsLibres\WopiLib\Service\Configuration\Configuration;
use ChampsLibres\WopiLib\Service\Discovery\Discovery;
use ChampsLibres\WopiLib\Service\DocumentLockManager;
use ChampsLibres\WopiLib\Service\ProofValidator;
use ChampsLibres\WopiLib\Service\Utils\DotNetTimeConverter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
        ->autoconfigure(true)
        ->autowire(true);

    $services
        ->load('ChampsLibres\\WopiBundle\\Controller\\', __DIR__.'/../../Controller')
        ->tag('controller.service_arguments');

    $services
        ->set(Configuration::class)
        ->arg('$properties', '%wopi%');

    $services
        ->alias(ConfigurationInterface::class, Configuration::class);

    $services
        ->set(Discovery::class);

    $services
        ->alias(DiscoveryInterface::class, Discovery::class);

    $services
        ->set(DocumentLockManager::class);

    $services
        ->alias(DocumentLockManagerInterface::class, DocumentLockManager::class);

    $services
        ->set(SystemClock::class);

    $services
        ->alias(ClockInterface::class, SystemClock::class);

    $services
        ->set(DotNetTimeConverter::class);

    $services
        ->alias(DotNetTimeConverterInterface::class, DotNetTimeConverter::class);

    $services
        ->set(ProofValidator::class);

    $services
        ->alias(ProofValidatorInterface::class, ProofValidator::class);

    $services
        ->set(Wopi::class);

    $services
        ->set(Wopi\PutFile::class);

    $services
        ->alias(WopiInterface::class, Wopi::class);
};

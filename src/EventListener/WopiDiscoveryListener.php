<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\EventListener;

use ChampsLibres\WopiLib\Discovery\WopiDiscoveryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class WopiDiscoveryListener implements EventSubscriberInterface
{
    private WopiDiscoveryInterface $wopiDiscovery;

    public function __construct(WopiDiscoveryInterface $wopiDiscovery)
    {
        $this->wopiDiscovery = $wopiDiscovery;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->wopiDiscovery->refresh();
    }
}

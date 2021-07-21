<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\ChampsLibres\WopiBundle\Service;

use ChampsLibres\WopiBundle\Service\Wopi;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class WopiSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Wopi::class);
    }

    public function let(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->beConstructedWith($responseFactory, $streamFactory);
    }
}

<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\ChampsLibres\WopiBundle\Service;

use ChampsLibres\WopiBundle\Service\Wopi;
use loophp\psr17\Psr17Interface;
use PhpSpec\ObjectBehavior;

final class WopiSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Wopi::class);
    }

    public function let(Psr17Interface $psr17)
    {
        $this->beConstructedWith($psr17);
    }
}

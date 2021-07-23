<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Service;

use loophp\psr17\Psr17Interface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Wopi.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
final class Wopi implements WopiInterface
{
    private Psr17Interface $psr17;

    public function __construct(Psr17Interface $psr17)
    {
        $this->psr17 = $psr17;
    }

    public function checkFileInfo(string $fileId, ?string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function deleteFile(string $fileId, ?string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function enumerateAncestors(string $fileId, ?string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function getFile(string $fileId, ?string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function getLock(string $fileId, ?string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function getShareUrl(string $fileId, ?string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function lock(string $fileId, ?string $accessToken, string $xWopiLock, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function putFile(string $fileId, ?string $accessToken, string $xWopiLock, string $xWopiEditors, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function putRelativeFile(string $fileId, ?string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function putUserInfo(string $fileId, ?string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function refreshLock(string $fileId, ?string $accessToken, string $xWopiLock, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function renameFile(string $fileId, ?string $accessToken, string $xWopiLock, string $xWopiRequestedName, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function unlock(string $fileId, ?string $accessToken, string $xWopiLock, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    public function unlockAndRelock(string $fileId, ?string $accessToken, string $xWopiLock, string $xWopiOldLock, RequestInterface $request): ResponseInterface
    {
        return $this->getDebugResponse(__FUNCTION__, $request);
    }

    private function getDebugResponse(string $method, RequestInterface $request): ResponseInterface
    {
        $response = $this->psr17->createResponse();

        $data = array_merge(
            ['method' => $method],
            Uri::getParams($request->getUri()),
            $request->getHeaders()
        );

        return $response->withHeader('content', 'application/json')->withBody($this->psr17->createStream((string) json_encode($data)));
    }
}

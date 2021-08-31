<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Controller;

use ChampsLibres\WopiLib\Service\Contract\WopiInterface;
use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

use function array_key_exists;

final class Files
{
    private WopiInterface $wopi;

    public function __construct(WopiInterface $wopi)
    {
        $this->wopi = $wopi;
    }

    public function checkFileInfo(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $checkFileInfo = $this->wopi->checkFileInfo(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $checkFileInfo;
    }

    public function deleteFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $deleteFile = $this->wopi->deleteFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $deleteFile;
    }

    public function enumerateAncestors(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $enumerateAncestors = $this->wopi->enumerateAncestors(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $enumerateAncestors;
    }

    public function getFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $getFile = $this->wopi->getFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $getFile;
    }

    public function getLock(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $getLock = $this->wopi->getLock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $getLock;
    }

    public function getShareUrl(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $getShareUrl = $this->wopi->enumerateAncestors(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $getShareUrl;
    }

    public function lock(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $lock = $this->wopi->lock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine('X-WOPI-Lock'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $lock;
    }

    public function putFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $putFile = $this->wopi->putFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine('X-WOPI-Lock'),
                $request->getHeaderLine('X-WOPI-Editors'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $putFile;
    }

    public function putRelativeFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $putRelativeFile = $this->wopi->putRelativeFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $putRelativeFile;
    }

    public function putUserInfo(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $putUserInfo = $this->wopi->putUserInfo(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $putUserInfo;
    }

    public function refreshLock(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $refreshLock = $this->wopi->refreshLock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine('X-WOPI-Lock'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $refreshLock;
    }

    public function renameFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $renameFile = $this->wopi->renameFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine('X-WOPI-Lock'),
                $request->getHeaderLine('X-WOPI-RequestedName'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $renameFile;
    }

    public function unlock(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $unlock = $this->wopi->unlock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine('X-WOPI-Lock'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $unlock;
    }

    public function unlockAndRelock(string $fileId, RequestInterface $request): ResponseInterface
    {
        try {
            $unlockAndRelock = $this->wopi->unlockAndRelock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine('X-WOPI-Lock'),
                $request->getHeaderLine('X-WOPI-OldLock'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $unlockAndRelock;
    }

    private function getParam(UriInterface $uri, string $param): string
    {
        $output = [];

        parse_str($uri->getQuery(), $output);

        if (!array_key_exists($param, $output)) {
            // TODO
            throw new Exception('No param found.');
        }

        return $output[$param];
    }
}

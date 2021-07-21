<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Controller;

use ChampsLibres\WopiBundle\Service\Uri;
use ChampsLibres\WopiBundle\Service\WopiInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

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
                Uri::getParam($request->getUri(), 'access_token', ''),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
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
                Uri::getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine('X-WOPI-Lock'),
                $request->getHeaderLine('X-WOPI-OldLock'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $unlockAndRelock;
    }
}

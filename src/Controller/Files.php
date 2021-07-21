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
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Files
{
    private HttpFoundationFactoryInterface $httpFoundationFactory;

    private WopiInterface $wopi;

    public function __construct(WopiInterface $wopi, HttpFoundationFactoryInterface $httpFoundationFactory)
    {
        $this->wopi = $wopi;
        $this->httpFoundationFactory = $httpFoundationFactory;
    }

    public function checkFileInfo(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($checkFileInfo);
    }

    public function deleteFile(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($deleteFile);
    }

    public function enumerateAncestors(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($enumerateAncestors);
    }

    public function getFile(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($getFile);
        //1. convertir $fileId en $url
        //2. get $url
        //3. decrypt $content
        //4. send content
    }

    public function getLock(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($getLock);
    }

    public function getShareUrl(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($getShareUrl);
    }

    public function lock(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($lock);
    }

    public function putFile(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($putFile);
    }

    public function putRelativeFile(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($putRelativeFile);
    }

    public function putUserInfo(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($putUserInfo);
    }

    public function refreshLock(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($refreshLock);
    }

    public function renameFile(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($renameFile);
    }

    public function unlock(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($unlock);
    }

    public function unlockAndRelock(string $fileId, RequestInterface $request): Response
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

        return $this->httpFoundationFactory->createResponse($unlockAndRelock);
    }
}

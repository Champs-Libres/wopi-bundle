<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Controller;

use ChampsLibres\WopiLib\Service\Contract\WopiInterface;
use ChampsLibres\WopiLib\Service\Contract\WopiProofValidatorInterface;
use Exception;
use loophp\psr17\Psr17Interface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

use function array_key_exists;

final class Files
{
    private Psr17Interface $psr17;

    private WopiInterface $wopi;

    private WopiProofValidatorInterface $wopiProofValidator;

    public function __construct(
        WopiInterface $wopi,
        WopiProofValidatorInterface $wopiProofValidator,
        Psr17Interface $psr17
    ) {
        $this->wopi = $wopi;
        $this->wopiProofValidator = $wopiProofValidator;
        $this->psr17 = $psr17;
    }

    public function checkFileInfo(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

        $suggestedTarget = $request->hasHeader('X-WOPI-SuggestedTarget') ?
            mb_convert_encoding($request->getHeaderLine('X-WOPI-SuggestedTarget'), 'UTF-8', 'UTF-7') :
            null;
        $relativeTarget = $request->hasHeader('X-WOPI-RelativeTarget') ?
            mb_convert_encoding($request->getHeaderLine('X-WOPI-RelativeTarget'), 'UTF-8', 'UTF-7') :
            null;
        $overwriteRelativeTarget = $request->hasHeader('X-WOPI-OverwriteRelativeTarget') ?
            ('false' === strtolower($request->getHeaderLine('X-WOPI-OverwriteRelativeTarget')) ? false : true) :
            false;

        try {
            $putRelativeFile = $this->wopi->putRelativeFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $suggestedTarget,
                $relativeTarget,
                $overwriteRelativeTarget,
                (int) $request->getHeaderLine('X-WOPI-Size'),
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $putRelativeFile;
    }

    public function putUserInfo(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

        $requestedName = $request->hasHeader('X-WOPI-RequestedName') ?
            mb_convert_encoding($request->getHeaderLine('X-WOPI-RequestedName'), 'UTF-8', 'UTF-7') :
            null;

        try {
            $renameFile = $this->wopi->renameFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine('X-WOPI-Lock'),
                $requestedName,
                $request
            );
        } catch (Throwable $e) {
            throw $e;
        }

        return $renameFile;
    }

    public function unlock(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this
                ->psr17
                ->createResponse(500);
        }

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

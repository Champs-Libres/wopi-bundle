<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Controller;

use ChampsLibres\WopiLib\Contract\Service\ProofValidatorInterface;
use ChampsLibres\WopiLib\Contract\Service\WopiInterface;
use loophp\psr17\Psr17Interface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Files.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
final class Files
{
    private LoggerInterface $logger;

    private Psr17Interface $psr17;

    private WopiInterface $wopi;

    private ProofValidatorInterface $wopiProofValidator;

    public function __construct(
        LoggerInterface $logger,
        WopiInterface $wopi,
        ProofValidatorInterface $wopiProofValidator,
        Psr17Interface $psr17,
    ) {
        $this->logger = $logger;
        $this->wopi = $wopi;
        $this->wopiProofValidator = $wopiProofValidator;
        $this->psr17 = $psr17;
    }

    public function checkFileInfo(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $checkFileInfo = $this->wopi->checkFileInfo(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $checkFileInfo;
    }

    public function deleteFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $deleteFile = $this->wopi->deleteFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $deleteFile;
    }

    public function enumerateAncestors(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $enumerateAncestors = $this->wopi->enumerateAncestors(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $enumerateAncestors;
    }

    public function getFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $getFile = $this->wopi->getFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $getFile;
    }

    public function getLock(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $getLock = $this->wopi->getLock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $getLock;
    }

    public function getShareUrl(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $getShareUrl = $this->wopi->enumerateAncestors(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $getShareUrl;
    }

    public function lock(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $lock = $this->wopi->lock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine(WopiInterface::HEADER_LOCK),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $lock;
    }

    public function putFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $putFile = $this->wopi->putFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine(WopiInterface::HEADER_LOCK),
                $request->getHeaderLine(WopiInterface::HEADER_EDITORS),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $putFile;
    }

    public function putRelativeFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        $suggestedTarget = $request->hasHeader(WopiInterface::HEADER_SUGGESTED_TARGET) ?
            mb_convert_encoding($request->getHeaderLine(WopiInterface::HEADER_SUGGESTED_TARGET), 'UTF-8', 'UTF-7') :
            null;
        $relativeTarget = $request->hasHeader(WopiInterface::HEADER_RELATIVE_TARGET) ?
            mb_convert_encoding($request->getHeaderLine(WopiInterface::HEADER_RELATIVE_TARGET), 'UTF-8', 'UTF-7') :
            null;
        $overwriteRelativeTarget = $request->hasHeader(WopiInterface::HEADER_OVERWRITE_RELATIVE_TARGET) ?
            ('false' === strtolower($request->getHeaderLine(WopiInterface::HEADER_OVERWRITE_RELATIVE_TARGET)) ? false : true) :
            false;

        try {
            $putRelativeFile = $this->wopi->putRelativeFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $suggestedTarget,
                $relativeTarget,
                $overwriteRelativeTarget,
                (int) $request->getHeaderLine(WopiInterface::HEADER_SIZE),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $putRelativeFile;
    }

    public function putUserInfo(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $putUserInfo = $this->wopi->putUserInfo(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $putUserInfo;
    }

    public function refreshLock(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $refreshLock = $this->wopi->refreshLock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine(WopiInterface::HEADER_LOCK),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $refreshLock;
    }

    public function renameFile(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        $requestedName = $request->hasHeader(WopiInterface::HEADER_REQUESTED_NAME) ?
            mb_convert_encoding($request->getHeaderLine(WopiInterface::HEADER_REQUESTED_NAME), 'UTF-8', 'UTF-7') :
            false;

        if (false === $requestedName) {
            return $this
                ->psr17
                ->createResponse(400);
        }

        try {
            $renameFile = $this->wopi->renameFile(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine(WopiInterface::HEADER_LOCK),
                $requestedName,
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $renameFile;
    }

    public function unlock(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $unlock = $this->wopi->unlock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine(WopiInterface::HEADER_LOCK),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $unlock;
    }

    public function unlockAndRelock(string $fileId, RequestInterface $request): ResponseInterface
    {
        if (!$this->wopiProofValidator->isValid($request)) {
            return $this->onProofValidationFailed();
        }

        try {
            $unlockAndRelock = $this->wopi->unlockAndRelock(
                $fileId,
                $this->getParam($request->getUri(), 'access_token'),
                $request->getHeaderLine(WopiInterface::HEADER_LOCK),
                $request->getHeaderLine(WopiInterface::HEADER_OLD_LOCK),
                $request
            );
        } catch (\Throwable $e) {
            throw $e;
        }

        return $unlockAndRelock;
    }

    private function getParam(UriInterface $uri, string $param): string
    {
        $output = [];

        parse_str($uri->getQuery(), $output);

        if (!\array_key_exists($param, $output)) {
            throw new \Exception('No param found.');
        }

        $r = $output[$param];

        if (\is_array($r)) {
            throw new \Exception('Param is an array, not a string');
        }

        return $r;
    }

    private function onProofValidationFailed(): ResponseInterface
    {
        $this->logger->error('[wopi] Proof validation failed');

        return $this
            ->psr17
            ->createResponse(500)
            ->withBody($this->psr17->createStream((string) json_encode([
                'message' => 'Proof validation failed',
            ])));
    }
}

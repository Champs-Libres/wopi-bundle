<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Service;

use ChampsLibres\WopiBundle\Contracts\AuthorizationManagerInterface;
use ChampsLibres\WopiBundle\Contracts\UserManagerInterface;
use ChampsLibres\WopiBundle\Service\Wopi\PutFile;
use ChampsLibres\WopiLib\Contract\Service\DocumentManagerInterface;
use ChampsLibres\WopiLib\Contract\Service\WopiInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final readonly class Wopi implements WopiInterface
{
    private const string LOG_PREFIX = '[wopi][Wopi] ';

    private bool $enableLock;

    private PutFile $putFileExecutor;

    public function __construct(
        private AuthorizationManagerInterface $authorizationManager,
        private DocumentManagerInterface $documentManager,
        private LoggerInterface $logger,
        private ResponseFactoryInterface $responseFactory,
        private RouterInterface $router,
        private StreamFactoryInterface $streamFactory,
        private UriFactoryInterface $uriFactory,
        private UserManagerInterface $userManager,
        PutFile $putFile,
        ParameterBagInterface $parameterBag,
    ) {
        $this->putFileExecutor = $putFile;
        $this->enableLock = $parameterBag->get('wopi')['enable_lock'];
    }

    /**
     * @param array<string, string|bool|int|null> $overrideProperties
     */
    public function checkFileInfo(string $fileId, string $accessToken, RequestInterface $request, array $overrideProperties = []): ResponseInterface
    {
        $userIdentifier = $this->userManager->getUserId($accessToken, $fileId, $request);

        if (null === $userIdentifier && false === $this->userManager->isAnonymousUser($accessToken, $fileId, $request)) {
            $this->logger->error(self::LOG_PREFIX.'user not found nor anonymous');

            return $this->responseFactory
                ->createResponse(404)
                ->withBody($this->streamFactory->createStream((string) json_encode(['message' => 'user not found nor anonymous'])));
        }

        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this->makeDocumentNotFoundResponse($fileId);
        }

        if (!$this->authorizationManager->userCanRead($accessToken, $document, $request)) {
            $this->logger->info(self::LOG_PREFIX.'user is not allowed to read document', ['fileId' => $fileId, 'userIdentifier' => $userIdentifier]);

            return $this->responseFactory->createResponse(401)->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => 'user is not allowed to see this document',
            ])));
        }

        $properties = [
            'BaseFileName' => $this->documentManager->getBasename($document),
            'OwnerId' => 'Symfony',
            'Size' => $this->documentManager->getSize($document),
            'UserId' => $userIdentifier,
            'ReadOnly' => !$this->authorizationManager->userCanWrite($accessToken, $document, $request),
            'RestrictedWebViewOnly' => $this->authorizationManager->isRestrictedWebViewOnly($accessToken, $document, $request),
            'UserCanAttend' => $this->authorizationManager->userCanAttend($accessToken, $document, $request),
            'UserCanPresent' => $this->authorizationManager->userCanPresent($accessToken, $document, $request),
            'UserCanRename' => $this->authorizationManager->userCanRename($accessToken, $document, $request),
            'UserCanWrite' => $this->authorizationManager->userCanWrite($accessToken, $document, $request),
            'UserCanNotWriteRelative' => $this->authorizationManager->userCannotWriteRelative($accessToken, $document, $request),
            'SupportsUserInfo' => false,
            'SupportsDeleteFile' => true,
            'SupportsLocks' => $this->enableLock,
            'SupportsGetLock' => $this->enableLock,
            'SupportsExtendedLockLength' => $this->enableLock,
            'SupportsUpdate' => true,
            'SupportsRename' => true,
            'SupportsFolders' => false,
            'UserFriendlyName' => $this->userManager->getUserFriendlyName($accessToken, $fileId, $request),
            'DisablePrint' => false,
            'AllowExternalMarketplace' => false,
            'SupportedShareUrlTypes' => [
                'ReadOnly',
            ],
            'SHA256' => $this->documentManager->getSha256($document),
            'LastModifiedTime' => $this->documentManager->getLastModifiedDate($document)
                ->format(\DateTimeInterface::ATOM),
        ];

        return $this
            ->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream((string) json_encode(array_merge($properties, $overrideProperties))));
    }

    public function deleteFile(string $fileId, string $accessToken, RequestInterface $request): ResponseInterface
    {
        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this->makeDocumentNotFoundResponse($fileId);
        }

        if (
            false === $this->authorizationManager->userCanDelete($accessToken, $document, $request)
            || false === $this->authorizationManager->userCanWrite($accessToken, $document, $request)
        ) {
            $this->logger->info(
                self::LOG_PREFIX.'user is not authorized to delete file',
                ['fileId' => $fileId, 'userId' => $this->userManager->getUserId($accessToken, $fileId, $request)]
            );

            return $this->responseFactory
                ->createResponse(401);
        }

        $this->documentManager->remove($document);

        return $this
            ->responseFactory
            ->createResponse(200);
    }

    public function enumerateAncestors(
        string $fileId,
        string $accessToken,
        RequestInterface $request,
    ): ResponseInterface {
        return $this
            ->responseFactory
            ->createResponse(501);
    }

    public function getFile(
        string $fileId,
        string $accessToken,
        RequestInterface $request,
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this->makeDocumentNotFoundResponse($fileId);
        }

        if (!$this->authorizationManager->userCanRead($accessToken, $document, $request)) {
            $userIdentifier = $this->userManager->getUserId($accessToken, $fileId, $request);
            $this->logger->info(self::LOG_PREFIX.'user is not allowed to read document', ['fileId' => $fileId, 'userIdentifier' => $userIdentifier]);

            return $this->responseFactory->createResponse(401)->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => 'user is not allowed to see this document',
            ])));
        }

        $revision = $this->documentManager->getVersion($document);
        $content = $this->documentManager->read($document);

        return $this
            ->responseFactory
            ->createResponse()
            ->withHeader(
                WopiInterface::HEADER_ITEM_VERSION,
                sprintf('v%s', $revision)
            )
            ->withHeader(
                'Content-Type',
                'application/octet-stream',
            )
            ->withHeader(
                'Content-Length',
                (string) $this->documentManager->getSize($document)
            )
            ->withHeader(
                'Content-Disposition',
                sprintf('attachment; filename=%s', $this->documentManager->getBasename($document))
            )
            ->withBody($content);
    }

    public function getLock(string $fileId, string $accessToken, RequestInterface $request): ResponseInterface
    {
        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this->makeDocumentNotFoundResponse($fileId);
        }

        if (!$this->authorizationManager->isTokenValid($accessToken, $document, $request)) {
            $this->logger->info(self::LOG_PREFIX.'invalid access token', ['fileId' => $fileId]);

            return $this->responseFactory->createResponse(401)->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => 'invalid access token',
            ])));
        }

        if ($this->documentManager->hasLock($document)) {
            return $this
                ->responseFactory
                ->createResponse()
                ->withHeader(WopiInterface::HEADER_LOCK, $this->documentManager->getLock($document));
        }

        return $this
            ->responseFactory
            ->createResponse(404)
            ->withHeader(WopiInterface::HEADER_LOCK, '');
    }

    public function getShareUrl(string $fileId, string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this
            ->responseFactory
            ->createResponse(501);
    }

    public function lock(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        RequestInterface $request,
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this->makeDocumentNotFoundResponse($fileId);
        }

        if (!$this->authorizationManager->isTokenValid($accessToken, $document, $request)) {
            $this->logger->info(self::LOG_PREFIX.'invalid access token', ['fileId' => $fileId]);

            return $this->responseFactory->createResponse(401)->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => 'invalid access token',
            ])));
        }

        $version = $this->documentManager->getVersion($document);

        if ($this->documentManager->hasLock($document)) {
            if ($xWopiLock !== $currentLock = $this->documentManager->getLock($document)) {
                return $this
                    ->responseFactory
                    ->createResponse(409)
                    ->withHeader(WopiInterface::HEADER_LOCK, $currentLock)
                    ->withHeader(
                        WopiInterface::HEADER_ITEM_VERSION,
                        sprintf('v%s', $version)
                    );
            }
        }

        $this->documentManager->lock($document, $xWopiLock);

        return $this
            ->responseFactory
            ->createResponse()
            ->withHeader(
                WopiInterface::HEADER_ITEM_VERSION,
                sprintf('v%s', $version)
            );
    }

    public function putFile(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        string $xWopiEditors,
        RequestInterface $request,
    ): ResponseInterface {
        return ($this->putFileExecutor)($fileId, $accessToken, $xWopiLock, $xWopiEditors, $request);
    }

    public function putRelativeFile(
        string $fileId,
        string $accessToken,
        ?string $suggestedTarget,
        ?string $relativeTarget,
        bool $overwriteRelativeTarget,
        int $size,
        RequestInterface $request,
    ): ResponseInterface {
        if ((null === $suggestedTarget) && (null === $relativeTarget)) {
            return $this
                ->responseFactory
                ->createResponse(400)
                ->withBody($this->streamFactory->createStream((string) json_encode([
                    'message' => 'target is null',
                ])));
        }

        if (null !== $suggestedTarget) {
            // If it starts with a dot...
            if (str_starts_with($suggestedTarget, '.')) {
                $document = $this->documentManager->findByDocumentId($fileId);

                if (null === $document) {
                    return $this->makeDocumentNotFoundResponse($fileId);
                }
                $filename = pathinfo($this->documentManager->getBasename($document), \PATHINFO_EXTENSION | \PATHINFO_FILENAME);

                $suggestedTarget = sprintf('%s%s', $filename, $suggestedTarget);
            }

            $target = $suggestedTarget;
        } else {
            $document = $this->documentManager->findByDocumentFilename($relativeTarget);

            /*
             * If a file with the specified name already exists,
             * the host must respond with a 409 Conflict,
             * unless the X-WOPI-OverwriteRelativeTarget request header is set to true.
             *
             * When responding with a 409 Conflict for this reason,
             * the host may include an X-WOPI-ValidRelativeTarget specifying a file name that is valid.
             *
             * If the X-WOPI-OverwriteRelativeTarget request header is set to true
             * and a file with the specified name already exists and is locked,
             * the host must respond with a 409 Conflict and include an
             * X-WOPI-Lock response header containing the value of the current lock on the file.
             */
            if (null !== $document) {
                if (false === $overwriteRelativeTarget) {
                    $extension = pathinfo($this->documentManager->getBasename($document), \PATHINFO_EXTENSION);

                    return $this
                        ->responseFactory
                        ->createResponse(409)
                        ->withHeader('Content-Type', 'application/json')
                        ->withHeader(
                            WopiInterface::HEADER_VALID_RELATIVE_TARGET,
                            sprintf('%s.%s', uniqid(), $extension)
                        );
                }

                if ($this->enableLock && $this->documentManager->hasLock($document)) {
                    return $this
                        ->responseFactory
                        ->createResponse(409)
                        ->withHeader(WopiInterface::HEADER_LOCK, $this->documentManager->getLock($document));
                }
            }

            $target = $relativeTarget;
        }

        /** @var array{filename: string, extension: string} $pathInfo */
        $pathInfo = pathinfo($target, \PATHINFO_EXTENSION | \PATHINFO_FILENAME);

        $new = $this->documentManager->create([
            'basename' => $target,
            'name' => $pathInfo['filename'],
            'extension' => $pathInfo['extension'],
            'content' => (string) $request->getBody(),
            'size' => $request->getHeaderLine(WopiInterface::HEADER_SIZE),
        ]);

        $this->documentManager->write($new);

        $uri = $this
            ->uriFactory
            ->createUri(
                $this
                    ->router
                    ->generate(
                        'checkFileInfo',
                        [
                            'fileId' => $this->documentManager->getDocumentId($new),
                        ],
                        RouterInterface::ABSOLUTE_URL
                    )
            )
            ->withQuery(http_build_query([
                'access_token' => $accessToken,
            ]));

        $properties = [
            'Name' => $this->documentManager->getBasename($new),
            'Url' => (string) $uri,
            'HostEditUrl' => $this->documentManager->getDocumentId($new),
            'HostViewUrl' => $this->documentManager->getDocumentId($new),
        ];

        return $this
            ->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream((string) json_encode($properties)));
    }

    public function putUserInfo(string $fileId, string $accessToken, RequestInterface $request): ResponseInterface
    {
        $this->logger->warning(self::LOG_PREFIX.'user info called, but not implemented');

        return $this->responseFactory->createResponse(501)
            ->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => 'User info not implemented',
            ])));
    }

    public function refreshLock(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        RequestInterface $request,
    ): ResponseInterface {
        // note: the validation of access token is done inside unlock and lock methods
        $this->unlock($fileId, $accessToken, $xWopiLock, $request);

        return $this->lock($fileId, $accessToken, $xWopiLock, $request);
    }

    public function renameFile(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        string $xWopiRequestedName,
        RequestInterface $request,
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this->makeDocumentNotFoundResponse($fileId);
        }

        if (!$this->authorizationManager->userCanRename($accessToken, $document, $request)) {
            $userIdentifier = $this->userManager->getUserId($accessToken, $fileId, $request);
            $this->logger->info(self::LOG_PREFIX.'user is not allowed to rename', ['fileId' => $fileId, 'userIdentifier' => $userIdentifier]);

            return $this->responseFactory->createResponse(401)->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => 'user is not allowed to rename',
            ])));
        }

        if ($this->enableLock && $this->documentManager->hasLock($document)) {
            if ($xWopiLock !== $currentLock = $this->documentManager->getLock($document)) {
                return $this
                    ->responseFactory
                    ->createResponse(409)
                    ->withHeader(WopiInterface::HEADER_LOCK, $currentLock);
            }
        }

        $this->documentManager->rename($document, $xWopiRequestedName);

        $data = [
            'Name' => $xWopiRequestedName,
        ];

        return $this
            ->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                $this->streamFactory->createStream((string) json_encode($data))
            );
    }

    public function unlock(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        RequestInterface $request,
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this->makeDocumentNotFoundResponse($fileId);
        }

        if (!$this->authorizationManager->isTokenValid($accessToken, $document, $request)) {
            $this->logger->info(self::LOG_PREFIX.'invalid access token', ['fileId' => $fileId]);

            return $this->responseFactory->createResponse(401)->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => 'invalid access token',
            ])));
        }

        $version = $this->documentManager->getVersion($document);

        if (!$this->documentManager->hasLock($document)) {
            return $this
                ->responseFactory
                ->createResponse(409)
                ->withHeader(WopiInterface::HEADER_LOCK, '');
        }

        $currentLock = $this->documentManager->getLock($document);

        if ($this->enableLock && $currentLock !== $xWopiLock) {
            return $this
                ->responseFactory
                ->createResponse(409)
                ->withHeader(WopiInterface::HEADER_LOCK, $currentLock);
        }

        $this->documentManager->deleteLock($document);

        return $this
            ->responseFactory
            ->createResponse()
            ->withHeader(WopiInterface::HEADER_LOCK, '')
            ->withHeader(
                WopiInterface::HEADER_ITEM_VERSION,
                sprintf('v%s', $version)
            );
    }

    public function unlockAndRelock(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        string $xWopiOldLock,
        RequestInterface $request,
    ): ResponseInterface {
        $this->unlock($fileId, $accessToken, $xWopiOldLock, $request);

        return $this->lock($fileId, $accessToken, $xWopiLock, $request);
    }

    private function makeDocumentNotFoundResponse(string $fileId): ResponseInterface
    {
        $this->logger->error(self::LOG_PREFIX.'Document not found', ['fileId' => $fileId]);

        return $this->responseFactory->createResponse(404)
            ->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => "Document with id {$fileId} not found",
            ])));
    }
}

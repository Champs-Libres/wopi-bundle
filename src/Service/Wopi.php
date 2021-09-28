<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Service;

use ChampsLibres\WopiLib\Contract\Service\DocumentManagerInterface;
use ChampsLibres\WopiLib\Contract\Service\WopiInterface;
use loophp\psr17\Psr17Interface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function strlen;

final class Wopi implements WopiInterface
{
    private CacheItemPoolInterface $cache;

    private DocumentManagerInterface $documentManager;

    private Psr17Interface $psr17;

    private RouterInterface $router;

    private TokenStorageInterface $tokenStorage;

    public function __construct(
        CacheItemPoolInterface $cache,
        DocumentManagerInterface $documentManager,
        Psr17Interface $psr17,
        RouterInterface $router,
        TokenStorageInterface $tokenStorage
    ) {
        $this->cache = $cache;
        $this->documentManager = $documentManager;
        $this->psr17 = $psr17;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public function checkFileInfo(string $fileId, string $accessToken, RequestInterface $request): ResponseInterface
    {
        $document = $this->documentManager->findByDocumentId($fileId);
        $userIdentifier = $this->tokenStorage->getToken()->getUser()->getUserIdentifier();
        $userCacheKey = sprintf('wopi_putUserInfo_%s', $this->tokenStorage->getToken()->getUser()->getUserIdentifier());

        return $this
            ->psr17
            ->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->psr17->createStream((string) json_encode(
                [
                    'BaseFileName' => $this->documentManager->getBasename($document),
                    'OwnerId' => 'Symfony',
                    'Size' => $this->documentManager->getSize($document),
                    'UserId' => $userIdentifier,
                    'ReadOnly' => false,
                    'UserCanAttend' => true,
                    'UserCanPresent' => true,
                    'UserCanRename' => true,
                    'UserCanWrite' => true,
                    'UserCanNotWriteRelative' => false,
                    'SupportsUserInfo' => true,
                    'SupportsDeleteFile' => true,
                    'SupportsLocks' => true,
                    'SupportsGetLock' => true,
                    'SupportsExtendedLockLength' => true,
                    'UserFriendlyName' => $userIdentifier,
                    'SupportsUpdate' => true,
                    'SupportsRename' => true,
                    'DisablePrint' => false,
                    'AllowExternalMarketplace' => true,
                    'SupportedShareUrlTypes' => [
                        'ReadOnly',
                    ],
                    'SHA256' => $this->documentManager->getSha256($document),
                    'UserInfo' => (string) $this->cache->getItem($userCacheKey)->get(),
                ]
            )));
    }

    public function deleteFile(string $fileId, string $accessToken, RequestInterface $request): ResponseInterface
    {
        $this->documentManager->remove($this->documentManager->findByDocumentId($fileId));

        // @TODO Check if the file is properly deleted.

        return $this
            ->psr17
            ->createResponse(200);
    }

    public function enumerateAncestors(
        string $fileId,
        string $accessToken,
        RequestInterface $request
    ): ResponseInterface {
        return $this
            ->psr17
            ->createResponse(501);
    }

    public function getFile(
        string $fileId,
        string $accessToken,
        RequestInterface $request
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);
        $revision = $this->documentManager->getVersion($document);
        $content = $this->documentManager->read($document);

        return $this
            ->psr17
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
                $this->documentManager->getSize($document)
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

        if ($this->documentManager->hasLock($document)) {
            return $this
                ->psr17
                ->createResponse()
                ->withHeader(WopiInterface::HEADER_LOCK, $this->documentManager->getLock($document));
        }

        return $this
            ->psr17
            ->createResponse(404)
            ->withHeader(WopiInterface::HEADER_LOCK, '');
    }

    public function getShareUrl(string $fileId, string $accessToken, RequestInterface $request): ResponseInterface
    {
        return $this
            ->psr17
            ->createResponse(501);
    }

    public function lock(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        RequestInterface $request
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);
        $version = $this->documentManager->getVersion($document);

        if ($this->documentManager->hasLock($document)) {
            if ($xWopiLock === $currentLock = $this->documentManager->getLock($document)) {
                return $this->refreshLock($fileId, $accessToken, $xWopiLock, $request);
            }

            return $this
                ->psr17
                ->createResponse(409)
                ->withHeader(WopiInterface::HEADER_LOCK, $currentLock)
                ->withHeader(
                    WopiInterface::HEADER_ITEM_VERSION,
                    sprintf('v%s', $version)
                );
        }

        $this->documentManager->lock($document, $xWopiLock);

        return $this
            ->psr17
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
        RequestInterface $request
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);
        $version = $this->documentManager->getVersion($document);

        // File is unlocked
        if (false === $this->documentManager->hasLock($document)) {
            if (0 !== $this->documentManager->getSize($document)) {
                return $this
                    ->psr17
                    ->createResponse(409)
                    ->withHeader(
                        WopiInterface::HEADER_ITEM_VERSION,
                        sprintf('v%s', $version)
                    );
            }
        }

        // File is locked
        if ($this->documentManager->hasLock($document)) {
            if ($xWopiLock !== $currentLock = $this->documentManager->getLock($document)) {
                return $this
                    ->psr17
                    ->createResponse(409)
                    ->withHeader(
                        WopiInterface::HEADER_LOCK,
                        $currentLock
                    )
                    ->withHeader(
                        WopiInterface::HEADER_ITEM_VERSION,
                        sprintf('v%s', $version)
                    );
            }
        }

        $body = (string) $request->getBody();
        $this->documentManager->write(
            $document,
            [
                'content' => $body,
                'size' => (string) strlen($body),
            ]
        );
        $version = $this->documentManager->getVersion($document);

        return $this
            ->psr17
            ->createResponse()
            ->withHeader(
                WopiInterface::HEADER_LOCK,
                $xWopiLock
            )
            ->withHeader(
                WopiInterface::HEADER_ITEM_VERSION,
                sprintf('v%s', $version)
            );
    }

    public function putRelativeFile(
        string $fileId,
        string $accessToken,
        ?string $suggestedTarget,
        ?string $relativeTarget,
        bool $overwriteRelativeTarget,
        int $size,
        RequestInterface $request
    ): ResponseInterface {
        if ((null !== $suggestedTarget) && (null !== $relativeTarget)) {
            return $this
                ->psr17
                ->createResponse(400);
        }

        if (null !== $suggestedTarget) {
            // If it starts with a dot...
            if (0 === strpos($suggestedTarget, '.', 0)) {
                $document = $this->documentManager->findByDocumentId($fileId);
                $filename = pathinfo($this->documentManager->getBasename($document), PATHINFO_FILENAME);

                $suggestedTarget = sprintf('%s%s', $filename, $suggestedTarget);
            }

            $target = $suggestedTarget;
        }

        if (null !== $relativeTarget) {
            $document = $this->documentManager->findByDocumentFilename($relativeTarget);

            /**
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
                    $extension = pathinfo($this->documentManager->getBasename($document), PATHINFO_EXTENSION);

                    return $this
                        ->psr17
                        ->createResponse(409)
                        ->withHeader('Content-Type', 'application/json')
                        ->withHeader(
                            WopiInterface::HEADER_VALID_RELATIVE_TARGET,
                            sprintf('%s.%s', uniqid(), $extension)
                        );
                }

                if ($this->documentManager->hasLock($document)) {
                    return $this
                        ->psr17
                        ->createResponse(409)
                        ->withHeader(WopiInterface::HEADER_LOCK, $this->documentManager->getLock($document));
                }
            }

            $target = $relativeTarget;
        }

        $pathInfo = pathinfo($target);

        $new = $this->documentManager->create([
            'basename' => $target,
            'name' => $pathInfo['filename'],
            'extension' => $pathInfo['extension'],
            'content' => (string) $request->getBody(),
            'size' => $request->getHeaderLine(WopiInterface::HEADER_SIZE),
        ]);

        $this->documentManager->write($new);

        $uri = $this
            ->psr17
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
            ->psr17
            ->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->psr17->createStream((string) json_encode($properties)));
    }

    public function putUserInfo(string $fileId, string $accessToken, RequestInterface $request): ResponseInterface
    {
        $userCacheKey = sprintf('wopi_putUserInfo_%s', $this->tokenStorage->getToken()->getUser()->getUserIdentifier());

        $cacheItem = $this->cache->getItem($userCacheKey);
        $cacheItem->set((string) $request->getBody());
        $this->cache->save($cacheItem);

        return $this
            ->psr17
            ->createResponse();
    }

    public function refreshLock(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        RequestInterface $request
    ): ResponseInterface {
        $this->unlock($fileId, $accessToken, $xWopiLock, $request);

        return $this->lock($fileId, $accessToken, $xWopiLock, $request);
    }

    public function renameFile(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        string $xWopiRequestedName,
        RequestInterface $request
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this
                ->psr17
                ->createResponse(404);
        }

        if ($this->documentManager->hasLock($document)) {
            if ($xWopiLock !== $currentLock = $this->documentManager->getLock($document)) {
                return $this
                    ->psr17
                    ->createResponse(409)
                    ->withHeader(WopiInterface::HEADER_LOCK, $currentLock);
            }
        }

        $this->documentManager->write($document, ['filename' => $xWopiRequestedName]);

        $data = [
            'Name' => $xWopiRequestedName,
        ];

        return $this
            ->psr17
            ->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                $this->psr17->createStream((string) json_encode($data))
            );
    }

    public function unlock(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        RequestInterface $request
    ): ResponseInterface {
        $document = $this->documentManager->findByDocumentId($fileId);
        $version = $this->documentManager->getVersion($document);

        if (!$this->documentManager->hasLock($document)) {
            return $this
                ->psr17
                ->createResponse(409)
                ->withHeader(WopiInterface::HEADER_LOCK, '');
        }

        $currentLock = $this->documentManager->getLock($document);

        if ($currentLock !== $xWopiLock) {
            return $this
                ->psr17
                ->createResponse(409)
                ->withHeader(WopiInterface::HEADER_LOCK, $currentLock);
        }

        $this->documentManager->deleteLock($document);

        return $this
            ->psr17
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
        RequestInterface $request
    ): ResponseInterface {
        $this->unlock($fileId, $accessToken, $xWopiOldLock, $request);

        return $this->lock($fileId, $accessToken, $xWopiLock, $request);
    }
}

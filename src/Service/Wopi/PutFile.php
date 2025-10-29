<?php

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Service\Wopi;

use ChampsLibres\WopiBundle\Contracts\AuthorizationManagerInterface;
use ChampsLibres\WopiBundle\Contracts\UserManagerInterface;
use ChampsLibres\WopiLib\Contract\Service\DocumentManagerInterface;
use ChampsLibres\WopiLib\Contract\Service\WopiInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PutFile
{
    private const string LOG_PREFIX = '[wopi][wopi/PutFile] ';

    private readonly bool $enableLock;

    /**
     * @var 'version'|'timestamp'
     */
    private readonly string $versionManagement;

    public function __construct(
        private readonly AuthorizationManagerInterface $authorizationManager,
        private readonly DocumentManagerInterface $documentManager,
        private readonly LoggerInterface $logger,
        ParameterBagInterface $parameterBag,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UserManagerInterface $userManager,
    ) {
        $config = $parameterBag->get('wopi');
        if (is_array($config) && isset($config['version_management']) && isset($config['enable_lock'])) {
            $this->versionManagement = $config['version_management'];
            $this->enableLock = $config['enable_lock'];
        } else {
            throw new \UnexpectedValueException("Invalid configuration for 'wopi' section: missing 'version_management' or 'enable_lock' keys");
        }
    }

    public function __invoke(
        string $fileId,
        string $accessToken,
        string $xWopiLock,
        string $xWopiEditors,
        RequestInterface $request,
    ): ResponseInterface {
        $this->logger->debug(self::LOG_PREFIX.'put file', ['fileId' => $fileId]);
        $document = $this->documentManager->findByDocumentId($fileId);

        if (null === $document) {
            return $this->responseFactory->createResponse(404)
                ->withBody($this->streamFactory->createStream((string) json_encode([
                    'message' => "Document with id {$fileId} not found",
                ])));
        }

        if (!$this->authorizationManager->userCanWrite($accessToken, $document, $request)) {
            $userIdentifier = $this->userManager->getUserId($accessToken, $fileId, $request);
            $this->logger->info(self::LOG_PREFIX.'user is not allowed to write document', ['fileId' => $fileId, 'userIdentifier' => $userIdentifier]);

            return $this->responseFactory->createResponse(401)->withBody($this->streamFactory->createStream((string) json_encode([
                'message' => 'user is not allowed to write this document',
            ])));
        }

        $version = $this->documentManager->getVersion($document);

        // File is unlocked
        if ($this->enableLock && false === $this->documentManager->hasLock($document)) {
            if (0 !== $this->documentManager->getSize($document)) {
                $this->logger->error(self::LOG_PREFIX.'file unlocked', ['fileId' => $fileId]);

                return $this
                    ->responseFactory
                    ->createResponse(409)
                    ->withHeader(
                        WopiInterface::HEADER_ITEM_VERSION,
                        sprintf('v%s', $version)
                    );
            }
        }

        // File is locked
        if (
            $this->enableLock
            && $this->documentManager->hasLock($document)
            && $xWopiLock !== $currentLock = $this->documentManager->getLock($document)
        ) {
            $this->logger->error(self::LOG_PREFIX.'file locked and lock does not match', ['fileId' => $fileId]);

            return $this
                ->responseFactory
                ->createResponse(409)
                ->withHeader(
                    WopiInterface::HEADER_LOCK,
                    $currentLock
                )
                ->withHeader(
                    WopiInterface::HEADER_ITEM_VERSION,
                    sprintf('v%s', $version)
                )
                ->withBody(
                    $this->streamFactory->createStream((string) json_encode([
                        'message' => 'File locked',
                    ]))
                );
        }

        // for collabora online editor, check timestamp if present
        if ($request->hasHeader('x-lool-wopi-timestamp')) {
            $date = \DateTimeImmutable::createFromFormat(
                \DateTimeImmutable::ATOM,
                $request->getHeader('x-lool-wopi-timestamp')[0]
            );

            if (false === $date) {
                $errors = \DateTimeImmutable::getLastErrors();

                if (false === $errors) {
                    throw new \RuntimeException('Could not find error on DateTimeImmutable parsing');
                }

                $e = array_merge($errors['warnings'], $errors['errors']);

                $this->logger->error(self::LOG_PREFIX.'Error parsing date', ['fileId' => $fileId,
                    'date' => $request->getHeader('x-lool-wopi-timestamp')[0], 'errors' => $e]);

                throw new \RuntimeException('Error parsing date: '.implode(', ', $e));
            }

            if ($this->documentManager->getLastModifiedDate($document) > $date) {
                $this->logger->error(self::LOG_PREFIX.'File has more recent modified date', ['fileId' => $fileId]);

                return $this
                    ->responseFactory
                    ->createResponse(409)
                    ->withHeader(
                        WopiInterface::HEADER_LOCK,
                        $currentLock ?? ''
                    )
                    ->withHeader(
                        WopiInterface::HEADER_ITEM_VERSION,
                        sprintf('v%s', $version)
                    )
                    ->withBody(
                        $this->streamFactory->createStream(
                            (string) json_encode(
                                [
                                    'LOOLStatusCode' => 1010,
                                    'COOLStatusCode' => 1010,
                                ]
                            )
                        )
                    );
            }
        }

        $body = (string) $request->getBody();
        $this->documentManager->write(
            $document,
            [
                'content' => $body,
                'size' => \strlen($body),
            ]
        );
        $version = $this->documentManager->getVersion($document);

        $response = $this
            ->responseFactory
            ->createResponse()
            ->withHeader(
                WopiInterface::HEADER_LOCK,
                $xWopiLock
            )
            ->withHeader(
                WopiInterface::HEADER_ITEM_VERSION,
                sprintf('v%s', $version)
            );

        if ('timestamp' === $this->versionManagement) {
            return $response
                ->withBody(
                    $this->streamFactory->createStream(
                        (string) json_encode([
                            'LastModifiedTime' => $this->documentManager->getLastModifiedDate($document)->format(\DateTimeInterface::ATOM),
                        ])
                    )
                );
        }

        $this->logger->info(self::LOG_PREFIX.'file saved', ['fileId' => $fileId]);

        return $response;
    }
}

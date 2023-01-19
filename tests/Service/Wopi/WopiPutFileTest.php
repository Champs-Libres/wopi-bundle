<?php

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Tests\Service\Wopi;

use ChampsLibres\WopiBundle\Contracts\AuthorizationManagerInterface;
use ChampsLibres\WopiBundle\Contracts\UserManagerInterface;
use ChampsLibres\WopiBundle\Service\Wopi;
use ChampsLibres\WopiLib\Contract\Entity\Document;
use ChampsLibres\WopiLib\Contract\Service\DocumentManagerInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use const DATE_ATOM;

/**
 * @internal
 *
 * @coversNothing
 */
final class WopiPutFileTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @covers \PutFile::class
     */
    public function testPutFileNoLock()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $documentManager->findByDocumentId(Argument::type('string'))->willReturn(new DummyDocument());
        $documentManager->getVersion(Argument::type(Document::class))->will(static function ($args) {
            return $args[0]->version;
        });
        $documentManager->hasLock(Argument::type(Document::class))
            ->willReturn(true);
        $documentManager->getLock(Argument::type(Document::class))->willReturn('should-not-be-used');
        $documentManager->write(Argument::type(Document::class), Argument::type('array'))
            ->will(static function ($args) {
                /** @var DummyDocument $doc */
                $doc = $args[0];
                ++$doc->version;
                $doc->lastModified = new DateTimeImmutable('now');
            });
        $documentManager->getLastModifiedDate(Argument::type(Document::class))
            ->will(static function ($args) {
                return $args[0]->lastModified;
            });

        $authorizationManager = $this->prophesize(AuthorizationManagerInterface::class);
        $authorizationManager->userCanWrite(Argument::type('string'), Argument::type(Document::class), Argument::type(RequestInterface::class))
            ->willReturn(true);

        $wopi = new Wopi\PutFile(
            $authorizationManager->reveal(),
            $documentManager->reveal(),
            new NullLogger(),
            new ParameterBag(['wopi' => ['version_management' => 'timestamp', 'enable_lock' => false]]),
            new Psr17Factory(),
            new Psr17Factory(),
            $this->prophesize(UserManagerInterface::class)->reveal()
        );

        $request = (new Psr17Factory())->createRequest('POST', '/wopi/files/1234/contents')
            ->withHeader('x-lool-wopi-timestamp', (new DateTimeImmutable('2022-12-10T00:00:00'))->format(DATE_ATOM))
            ->withBody((new Psr17Factory())->createStream('dummy content'));

        $response = $wopi('1234', 'token', 'x-lock-false', '', $request);
        $body = json_decode((string) $response->getBody(), true);

        self::assertEquals(200, $response->getStatusCode());
        self::assertIsArray($body);
        self::arrayHasKey('LastModifiedTime', $body);
        $this->assertinstanceOf(DateTimeInterface::class, $lastModified = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $body['LastModifiedTime']));
        self::greaterThan(new DateTimeImmutable('1 second ago'), $lastModified);
    }

    /**
     * @covers \PutFile::class
     */
    public function testPutFileTimestampVersioningNoConflict()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $documentManager->findByDocumentId(Argument::type('string'))->willReturn(new DummyDocument());
        $documentManager->getVersion(Argument::type(Document::class))->will(static function ($args) {
            return $args[0]->version;
        });
        $documentManager->hasLock(Argument::type(Document::class))
            ->willReturn(true);
        $documentManager->getLock(Argument::type(Document::class))->willReturn('x-lock-1234');
        $documentManager->write(Argument::type(Document::class), Argument::type('array'))
            ->will(static function ($args) {
                /** @var DummyDocument $doc */
                $doc = $args[0];
                ++$doc->version;
                $doc->lastModified = new DateTimeImmutable('now');
            });
        $documentManager->getLastModifiedDate(Argument::type(Document::class))
            ->will(static function ($args) {
                return $args[0]->lastModified;
            });

        $authorizationManager = $this->prophesize(AuthorizationManagerInterface::class);
        $authorizationManager->userCanWrite(Argument::type('string'), Argument::type(Document::class), Argument::type(RequestInterface::class))
            ->willReturn(true);

        $wopi = new Wopi\PutFile(
            $authorizationManager->reveal(),
            $documentManager->reveal(),
            new NullLogger(),
            new ParameterBag(['wopi' => ['version_management' => 'timestamp', 'enable_lock' => true]]),
            new Psr17Factory(),
            new Psr17Factory(),
            $this->prophesize(UserManagerInterface::class)->reveal()
        );

        $request = (new Psr17Factory())->createRequest('POST', '/wopi/files/1234/contents')
            ->withHeader('x-lool-wopi-timestamp', (new DateTimeImmutable('2022-12-10T00:00:00'))->format(DATE_ATOM))
            ->withBody((new Psr17Factory())->createStream('dummy content'));

        $response = $wopi('1234', 'token', 'x-lock-1234', '', $request);
        $body = json_decode((string) $response->getBody(), true);

        self::assertEquals(200, $response->getStatusCode());
        self::assertIsArray($body);
        self::arrayHasKey('LastModifiedTime', $body);
        $this->assertinstanceOf(DateTimeInterface::class, $lastModified = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $body['LastModifiedTime']));
        self::greaterThan(new DateTimeImmutable('1 second ago'), $lastModified);
    }

    /**
     * @covers \PutFile::class
     */
    public function testPutFileTimestampVersioningWithConflict()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $documentManager->findByDocumentId(Argument::type('string'))
            ->willReturn(new DummyDocument());
        $documentManager->getVersion(Argument::type(Document::class))->will(static function ($args) {
            return $args[0]->version;
        });
        $documentManager->hasLock(Argument::type(Document::class))
            ->willReturn(true);
        $documentManager->getLock(Argument::type(Document::class))->willReturn('x-lock-1234');
        $documentManager->write(Argument::type(Document::class), Argument::type('array'))
            ->will(static function ($args) {
                /** @var DummyDocument $doc */
                $doc = $args[0];
                ++$doc->version;
                $doc->lastModified = new DateTimeImmutable('now');
            });
        $documentManager->getLastModifiedDate(Argument::type(Document::class))
            ->will(static function ($args) {
                return $args[0]->lastModified;
            });

        $authorizationManager = $this->prophesize(AuthorizationManagerInterface::class);
        $authorizationManager->userCanWrite(Argument::type('string'), Argument::type(Document::class), Argument::type(RequestInterface::class))
            ->willReturn(true);

        $wopi = new Wopi\PutFile(
            $authorizationManager->reveal(),
            $documentManager->reveal(),
            new NullLogger(),
            new ParameterBag(['wopi' => ['version_management' => 'timestamp', 'enable_lock' => true]]),
            new Psr17Factory(),
            new Psr17Factory(),
            $this->prophesize(UserManagerInterface::class)->reveal()
        );

        $request = (new Psr17Factory())->createRequest('POST', '/wopi/files/1234/contents')
            ->withHeader('x-lool-wopi-timestamp', (new DateTimeImmutable('2022-01-01T00:00:00'))->format(DATE_ATOM))
            ->withBody((new Psr17Factory())->createStream('dummy content'));

        $response = $wopi('1234', 'token', 'x-lock-1234', '', $request);
        $body = json_decode((string) $response->getBody(), true);

        self::assertEquals(409, $response->getStatusCode());
        self::assertIsArray($body);
        // match the implementation done by richdocuments: https://github.com/nextcloud/richdocuments/blob/1087aa7fc1b91c5eb8e189c9bbd47d0080f53d1a/lib/Controller/WopiController.php#L493
        self::assertArrayHasKey('LOOLStatusCode', $body);
        self::assertEquals($body['LOOLStatusCode'], 1010);
        // match the description of https://sdk.collaboraonline.com/docs/advanced_integration.html#detecting-external-document-change
        self::assertArrayHasKey('COOLStatusCode', $body);
        self::assertEquals($body['COOLStatusCode'], 1010);
    }

    /**
     * @covers \PutFile::class
     */
    public function testPutFileTimestampVersioningWithoutTimestampHeader()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $documentManager->findByDocumentId(Argument::type('string'))
            ->willReturn(new DummyDocument());
        $documentManager->getVersion(Argument::type(Document::class))->will(static function ($args) {
            return $args[0]->version;
        });
        $documentManager->hasLock(Argument::type(Document::class))
            ->willReturn(true);
        $documentManager->getLock(Argument::type(Document::class))->willReturn('x-lock-1234');
        $documentManager->write(Argument::type(Document::class), Argument::type('array'))
            ->will(static function ($args) {
                /** @var DummyDocument $doc */
                $doc = $args[0];
                ++$doc->version;
                $doc->lastModified = new DateTimeImmutable('now');
            });
        $documentManager->getLastModifiedDate(Argument::type(Document::class))
            ->will(static function ($args) {
                return $args[0]->lastModified;
            });

        $authorizationManager = $this->prophesize(AuthorizationManagerInterface::class);
        $authorizationManager->userCanWrite(Argument::type('string'), Argument::type(Document::class), Argument::type(RequestInterface::class))
            ->willReturn(true);

        $wopi = new Wopi\PutFile(
            $authorizationManager->reveal(),
            $documentManager->reveal(),
            new NullLogger(),
            new ParameterBag(['wopi' => ['version_management' => 'timestamp', 'enable_lock' => true]]),
            new Psr17Factory(),
            new Psr17Factory(),
            $this->prophesize(UserManagerInterface::class)->reveal()
        );

        $request = (new Psr17Factory())->createRequest('POST', '/wopi/files/1234/contents')
            ->withBody((new Psr17Factory())->createStream('dummy content'));

        $response = $wopi('1234', 'token', 'x-lock-1234', '', $request);
        $body = json_decode((string) $response->getBody(), true);

        self::assertEquals(200, $response->getStatusCode());
        self::assertIsArray($body);
        self::arrayHasKey('LastModifiedTime', $body);
        $this->assertinstanceOf(DateTimeInterface::class, $lastModified = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $body['LastModifiedTime']));
        self::greaterThan(new DateTimeImmutable('1 second ago'), $lastModified);
    }

    /**
     * @covers \PutFile::class
     */
    public function testPutFileWhenUserCannotWrite()
    {
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $documentManager->findByDocumentId(Argument::type('string'))
            ->willReturn(new DummyDocument());
        $documentManager->getVersion(Argument::type(Document::class))->will(static function ($args) {
            return $args[0]->version;
        });
        $documentManager->hasLock(Argument::type(Document::class))
            ->willReturn(true);
        $documentManager->getLock(Argument::type(Document::class))->willReturn('x-lock-1234');
        $documentManager->write(Argument::type(Document::class), Argument::type('array'))
            ->will(static function ($args) {
                /** @var DummyDocument $doc */
                $doc = $args[0];
                ++$doc->version;
                $doc->lastModified = new DateTimeImmutable('now');
            });
        $documentManager->getLastModifiedDate(Argument::type(Document::class))
            ->will(static function ($args) {
                return $args[0]->lastModified;
            });

        $authorizationManager = $this->prophesize(AuthorizationManagerInterface::class);
        $authorizationManager->userCanWrite(Argument::type('string'), Argument::type(Document::class), Argument::type(RequestInterface::class))
            ->willReturn(false);

        $userManager = $this->prophesize(UserManagerInterface::class);
        $userManager->getUserId(Argument::type('string'), Argument::type('string'), Argument::type(RequestInterface::class))
            ->willReturn(1);

        $wopi = new Wopi\PutFile(
            $authorizationManager->reveal(),
            $documentManager->reveal(),
            new NullLogger(),
            new ParameterBag(['wopi' => ['version_management' => 'timestamp', 'enable_lock' => true]]),
            new Psr17Factory(),
            new Psr17Factory(),
            $userManager->reveal()
        );

        $request = (new Psr17Factory())->createRequest('POST', '/wopi/files/1234/contents')
            ->withHeader('x-lool-wopi-timestamp', (new DateTimeImmutable('2022-01-01T00:00:00'))->format(DATE_ATOM))
            ->withBody((new Psr17Factory())->createStream('dummy content'));

        $response = $wopi('1234', 'token', 'x-lock-1234', '', $request);
        $body = json_decode((string) $response->getBody(), true);

        self::assertEquals(401, $response->getStatusCode());
    }
}

class DummyDocument implements \ChampsLibres\WopiLib\Contract\Entity\Document
{
    public DateTimeImmutable $lastModified;

    public int $version = 1;

    public function __construct()
    {
        $this->lastModified = new DateTimeImmutable('2022-12-10T00:00:00');
    }

    public function getWopiDocId(): string
    {
        return '1234';
    }
}

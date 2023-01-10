[![Latest Stable Version][latest stable version]][1]
 [![GitHub stars][github stars]][1]
 [![Total Downloads][total downloads]][1]
 [![GitHub Workflow Status][github workflow status]][2]
 [![Scrutinizer code quality][code quality]][3]
 [![Type Coverage][type coverage]][4]
 [![Code Coverage][code coverage]][3]
 [![License][license]][1]

# WOPI Bundle

A Symfony bundle to facilitate the implementation of the WOPI endpoints and protocol.

## Description

The **W**eb Application **O**pen **P**latform **I**nterface (WOPI) protocol let you
integrate Office for the web with your application, but also other software like [Collabora Online][52]

This bundle targets the integration with Collabora Online, for now.

In the future, this bundle may achieve [a validation][55] for an usage with Office For The Web.

### Integration of Collabora Online

* [Collabora Online][53]

### Overview for WOPI protocol

* [wopi][54]

### Office for the web platforms:

* [Collabora Office][46]
* [Office 365][47]

## Installation

```composer require champs-libres/wopi-bundle```

## Usage

This bundle provides the basic implementation of the protocol into Symfony. But there are many ways
to:

* store documents in an application;
* secure the protocol
* and manage permission, according to your own business logic.

Therefore, this bundle does not provide a specific implementation of the WOPI protocol described
through [a basic interface][49] from the [champs-libres/wopi-lib][48] bundle.

So, this bundle provides:

* The [routes][50] that the WOPI protocol needs, which starts with `/wopi` path (required by the WOPI protocol);
* A [controller][51] to for the WOPI routes;
* And an implementation for the Wopi logic, which will re-use some of **your** logic to manager permission, document, etc.

Some vocabulary:

* Wopi host: the app which implements this bundle;
* Wopi client: Collabora Online (or Office 365), which will use the endpoint provided by your app (the host)
* Editor: Collabora Online (or office 365). A synonym for Wopi client.

These are steps to integrate the wopi bundle in your application:

### Start an editor / your wopi client for development

You will find a free collabora online with the CODE project: [CODE][CODE].

:warning: the editor must have access to your app, **with the same domain name as the browser will open your app**.

If you use docker and docker-compose, you can achieve this by manipulating your `/etc/hosts` file:

```yaml
# docker-compose.yaml

services:
    app:
        # your php / symfony application
        # we assume that your app listen **inside the container** on the port 8001 (no port mapping required between inside and
        # outside of the container)
        # ...
    collabora:
        image: collabora/code:latest
        environment:
            - SLEEPFORDEBUGGER=0
            - DONT_GEN_SSL_CERT="True"
            - extra_params=--o:ssl.enable=false --o:ssl.termination=false
            - username=admin
            - password=admin
            - dictionaries=en_US
            - aliasgroup1=http://nginx:8001
        ports:
            - "127.0.0.1:9980:9980"
        cap_add:
            - MKNOD
        links:
            - app
```

```
# /etc/hosts

127.0.0.1 app collabora

```

With this config, you should be able to reach collabora using http://collabora:9980, and your app through http://app:8001.
You must use the latter to access your app during debugging collabora features.

### Configure this bundle

```yaml
# app/config/package/wopi.yaml

wopi:
    # this is the path to your server.
    # note: the wopi client (Collabora) must be able to your app **using the same domains as your browser**
    server: http://collabora:9980
```

### Create your document entity

Each document edited should be an entity which implements [`Document`][57].

### Create your document manager

Your manager will implements [`DocumentManagerInterface`][58].

This DocumentManager will handle the document logic into your application. It provides methods for writing the
document, and extract some information from it.

You can read [an implementation here][56].

### Create your logic for access token

**`access_token`** are created by your app, when it will open the editor page (spoiler: the editor page will be an iframe).
The wopi host (your application) will receive this access token on every request made by the client. Each token
should have a duration of 10 hours.

**You can choose your own logic**. But JWT can ease your life.

#### Some working configuration using LexikJWT

An easy way to authenticate your request is to use [JWT (Json Web Token)][59]. This can be achieved easily with
[LexikJWTAuthenticationBundle][60].

Create a firewall and configure access control for url starting by `/wopi`:

```yaml
# config/package/security.yaml
security:
    firewalls:
        wopi:
            pattern: ^/wopi
            stateless: true
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator
    access_control:
        # ...
        - { path: ^/wopi, roles: IS_AUTHENTICATED_FULLY }
        # ...
```

Configure lexik:

```yaml
# config/package/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    # required for wopi - recommended duration for token ttl
    token_ttl: 36000

    # required for wopi: the token is in query, with `?access_token=<your_token>`
    token_extractors:
        query_parameter:
            enabled: true
            name: access_token
```

See a working implementation: https://gitea.champs-libres.be/Chill-project/chill-skeleton-basic

### Provide information about your user

Implements [`UserManagerInterface`][61] to provide information about your users.

This information should be extracted through access token.

[Some working implementation][63]

### Provide information about the permissions / authorization

Implements [`AuthorizationManagerInterface`][62] to provide information about the permissions on the given Document.

[Some working implementation][64]

### Bind all the services

This bundle will require the implementation to be name according to the interface.

Some example:

```php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ChampsLibres\WopiBundle\Contracts\AuthorizationManagerInterface;
use ChampsLibres\WopiBundle\Contracts\UserManagerInterface;
use ChampsLibres\WopiLib\Contract\Service\DocumentManagerInterface;
use Chill\WopiBundle\Service\Wopi\AuthorizationManager;
use Chill\WopiBundle\Service\Wopi\ChillDocumentManager;
use Chill\WopiBundle\Service\Wopi\UserManager;

return static function (ContainerConfigurator $container) {
    $services = $container
        ->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->set(ChillDocumentManager::class);

    $services
        ->alias(DocumentManagerInterface::class, ChillDocumentManager::class);

    $services
        ->set(AuthorizationManager::class);

    $services->alias(AuthorizationManagerInterface::class, AuthorizationManager::class);

    $services
        ->set(UserManager::class);

    $services->alias(UserManagerInterface::class, UserManager::class);
};
```

### Create an editor page

The editor page will be the page which will load the editor, through an iframe.

Here is a controller:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use ChampsLibres\WopiLib\Contract\Service\Configuration\ConfigurationInterface;
use ChampsLibres\WopiLib\Contract\Service\Discovery\DiscoveryInterface;
use ChampsLibres\WopiLib\Contract\Service\DocumentManagerInterface;
use Chill\DocStoreBundle\Entity\StoredObject;
use Chill\MainBundle\Entity\User;
use Chill\WopiBundle\Service\Controller\ResponderInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use loophp\psr17\Psr17Interface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

final class Editor
{
    private DocumentManagerInterface $documentManager;

    private JWTTokenManagerInterface $JWTTokenManager;

    private Psr17Interface $psr17;

    private ResponderInterface $responder;

    private RouterInterface $router;

    private Security $security;

    private ConfigurationInterface $wopiConfiguration;

    private DiscoveryInterface $wopiDiscovery;

    public function __construct(
        ConfigurationInterface $wopiConfiguration,
        DiscoveryInterface $wopiDiscovery,
        DocumentManagerInterface $documentManager,
        JWTTokenManagerInterface $JWTTokenManager,
        ResponderInterface $responder,
        Security $security,
        Psr17Interface $psr17,
        RouterInterface $router
    ) {
        $this->documentManager = $documentManager;
        $this->JWTTokenManager = $JWTTokenManager;
        $this->wopiConfiguration = $wopiConfiguration;
        $this->wopiDiscovery = $wopiDiscovery;
        $this->responder = $responder;
        $this->security = $security;
        $this->psr17 = $psr17;
        $this->router = $router;
    }

    public function __invoke(string $fileId): Response
    {
        if (null === $user = $this->security->getUser()) {
            throw new AccessDeniedHttpException('Please authenticate to access this feature');
        }

        $configuration = $this->wopiConfiguration->jsonSerialize();
        $storedObject = $this->documentManager->findByDocumentId($fileId);

        if (null === $storedObject) {
            throw new NotFoundHttpException(sprintf('Unable to find object %s', $fileId));
        }

        if ([] === $discoverExtension = $this->wopiDiscovery->discoverMimeType($storedObject->getType())) {
            throw new Exception(sprintf('Unable to find mime type %s', $storedObject->getType()));
        }

        $configuration['favIconUrl'] = '';
        $configuration['access_token'] = $this->JWTTokenManager->createFromPayload($user, [
            'UserCanWrite' => true,
            'UserCanAttend' => true,
            'UserCanPresent' => true,
            'fileId' => $fileId,
        ]);

        // we parse the jwt to get the access_token_ttl
        // reminder: access_token_ttl is a javascript epoch, not a number of seconds; it is the
        // time when the token will expire, not the time to live:
        // https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/rest/concepts#the-access_token_ttl-property
        $jwt = $this->JWTTokenManager->parse($configuration['access_token']);
        $configuration['access_token_ttl'] = $jwt['exp'] * 1000;

        $configuration['server'] = $this
            ->psr17
            ->createUri($discoverExtension[0]['urlsrc'])
            ->withQuery(
                http_build_query(
                    [
                        'WOPISrc' => $this
                            ->router
                            ->generate(
                                'checkFileInfo',
                                [
                                    'fileId' => $this->documentManager->getDocumentId($storedObject),
                                ],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                        'closebutton' => 1,
                    ]
                )
            );

        return $this
            ->responder
            ->render(
                '@Wopi/Editor/page.html.twig',
                $configuration
            );
    }
}
```

## Troubleshooting

* check your collabora / CODE 's logs. They provide information about error from within WOPI calls;
* use the profiler to debug the call to WOPI endpoint made behind the scene by the wopi client.

## Documentation

* [https://wopi.readthedocs.io/en/latest/](https://wopi.readthedocs.io/en/latest/)

## Code quality, tests, benchmarks

Every time changes are introduced into the library, [Github][2] runs the
tests.

The library has tests written with PHPUNIT.

Before each commit, some inspections are executed with [GrumPHP][36]; run
`composer grumphp` to check manually.

The quality of the tests is tested with [Infection][37] a PHP Mutation testing
framework, run `composer infection` to try it.

Static analyzers are also controlling the code. [PHPStan][38] and
[PSalm][39] are enabled to their maximum level.

## Contributing

Feel free to contribute to this project by submitting pull requests on Github.

## Changelog

See [CHANGELOG.md][43] for a changelog based on [git commits][44].

For more detailed changelogs, please check [the release changelogs][45].

[1]: https://packagist.org/packages/champs-libres/wopi-bundle
[latest stable version]: https://img.shields.io/packagist/v/champs-libres/wopi-bundle.svg?style=flat-square
[github stars]: https://img.shields.io/github/stars/champs-libres/wopi-bundle.svg?style=flat-square
[total downloads]: https://img.shields.io/packagist/dt/champs-libres/wopi-bundle.svg?style=flat-square
[github workflow status]: https://img.shields.io/github/workflow/status/champs-libres/wopi-bundle/Unit%20tests?style=flat-square
[code quality]: https://img.shields.io/scrutinizer/quality/g/champs-libres/wopi-bundle/master.svg?style=flat-square
[3]: https://scrutinizer-ci.com/g/champs-libres/wopi-bundle/?branch=master
[type coverage]: https://img.shields.io/badge/dynamic/json?style=flat-square&color=color&label=Type%20coverage&query=message&url=https%3A%2F%2Fshepherd.dev%2Fgithub%2Fchamps-libres%2Fwopi-bundle%2Fcoverage
[4]: https://shepherd.dev/github/champs-libres/wopi-bundle
[code coverage]: https://img.shields.io/scrutinizer/coverage/g/champs-libres/wopi-bundle/master.svg?style=flat-square
[license]: https://img.shields.io/packagist/l/champs-libres/wopi-bundle.svg?style=flat-square
[CODE]: https://www.collaboraoffice.com/code/
[34]: https://github.com/champs-libres/wopi-bundle/issues
[2]: https://github.com/champs-libres/wopi-bundle/actions
[35]: http://www.phpspec.net/
[36]: https://github.com/phpro/grumphp
[37]: https://github.com/infection/infection
[38]: https://github.com/phpstan/phpstan
[39]: https://github.com/vimeo/psalm
[43]: https://github.com/champs-libres/wopi-bundle/blob/master/CHANGELOG.md
[44]: https://github.com/champs-libres/wopi-bundle/commits/master
[45]: https://github.com/champs-libres/wopi-bundle/releases
[46]: https://www.collaboraoffice.com/
[47]: https://www.office.com/
[48]: https://github.com/champs-libres/wopi-lib
[49]: https://github.com/Champs-Libres/wopi-lib/blob/master/src/WopiInterface.php
[50]: https://github.com/Champs-Libres/wopi-bundle/blob/master/src/Resources/config/routes/routes.php
[51]: https://github.com/Champs-Libres/wopi-bundle/blob/master/src/Controller/Files.php
[52]: https://www.collaboraoffice.com/collabora-online/
[53]: https://sdk.collaboraonline.com
[54]: https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/online/
[55]: https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/online/build-test-ship/testing
[56]: https://gitlab.com/Chill-Projet/chill-bundles/-/blob/master/src/Bundle/ChillWopiBundle/src/Service/Wopi/ChillDocumentManager.php
[57]: https://github.com/Champs-Libres/wopi-lib/blob/master/src/Contract/Entity/Document.php
[58]: https://github.com/Champs-Libres/wopi-lib/blob/master/src/Contract/Service/DocumentManagerInterface.php
[59]: https://jwt.io/
[60]: https://symfony.com/bundles/LexikJWTAuthenticationBundle/current/index.html
[61]: ./src/Contracts/UserManagerInterface.php
[62]: ./src/Contracts/AuthorizationManagerInterface.php
[63]: https://gitlab.com/Chill-Projet/chill-bundles/-/blob/master/src/Bundle/ChillWopiBundle/src/Service/Wopi/UserManager.php
[64]: https://gitlab.com/Chill-Projet/chill-bundles/-/blob/master/src/Bundle/ChillWopiBundle/src/Service/Wopi/AuthorizationManager.php

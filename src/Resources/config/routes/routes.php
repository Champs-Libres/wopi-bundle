<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use ChampsLibres\WopiBundle\Controller\Files;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Wopi routes callback.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
return static function (RoutingConfigurator $routes) {
    $fileIdRegex = '^[\w,\s-]+\.[\w,\s-]+$';

    /** Conditions shortcuts */
    $hasAccessTokenQueryParam = 'request.query.has("access_token")';
    $hasAccessTokenTTLQueryParam = 'request.query.has("access_token_ttl")';
    $hasXWOPILockHeader = 'request.headers.has("X-WOPI-Lock")';

    $hasXWopiOverrideHeaderSetTo = static function (string $value): string {
        return sprintf('request.headers.get("X-WOPI-Override") === "%s"', $value);
    };

    $hasHeader = static function (string $header): string {
        return sprintf('request.headers.has("%s")', $header);
    };

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/CheckFileInfo.html
     */
    $routes
        ->add('checkFileInfo', '/files/{fileId}')
        ->controller([Files::class, 'checkFileInfo'])
        ->methods([Request::METHOD_GET])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/GetFile.html
     */
    $routes
        ->add('getFile', '/files/{fileId}/contents')
        ->controller([Files::class, 'getFile'])
        ->methods([Request::METHOD_GET])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/Lock.html
     */
    $routes
        ->add('lock', '/files/{fileId}')
        ->controller([Files::class, 'lock'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWOPILockHeader,
                    $hasXWopiOverrideHeaderSetTo('LOCK'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/GetLock.html
     */
    $routes
        ->add('getLock', '/files/{fileId}')
        ->controller([Files::class, 'getLock'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition('request.headers.get("X-WOPI-Override") === "GET_LOCK"')
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWOPILockHeader,
                    $hasXWopiOverrideHeaderSetTo('GET_LOCK'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/RefreshLock.html
     */
    $routes
        ->add('refreshLock', '/files/{fileId}')
        ->controller([Files::class, 'refreshLock'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWOPILockHeader,
                    $hasXWopiOverrideHeaderSetTo('REFRESH_LOCK'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/Unlock.html
     */
    $routes
        ->add('unlock', '/files/{fileId}')
        ->controller([Files::class, 'unlock'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWOPILockHeader,
                    $hasXWopiOverrideHeaderSetTo('UNLOCK'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/UnlockAndRelock.html
     */
    $routes
        ->add('unlockAndRelock', '/files/{fileId}')
        ->controller([Files::class, 'unlockAndRelock'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWOPILockHeader,
                    $hasXWopiOverrideHeaderSetTo('LOCK'),
                    $hasHeader('X-WOPI-OldLock'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/PutFile.html
     */
    $routes
        ->add('putFile', '/files/{fileId}/contents')
        ->controller([Files::class, 'putFile'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWopiOverrideHeaderSetTo('PUT'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/PutRelativeFile.html
     */
    $routes
        ->add('putRelativeFile', '/files/{fileId}')
        ->controller([Files::class, 'putRelativeFile'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWopiOverrideHeaderSetTo('PUT_RELATIVE'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/RenameFile.html
     */
    $routes
        ->add('renameFile', '/files/{fileId}')
        ->controller([Files::class, 'renameFile'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWOPILockHeader,
                    $hasXWopiOverrideHeaderSetTo('RENAME_FILE'),
                    $hasHeader('X-WOPI-RequestedName'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/DeleteFile.html
     */
    $routes
        ->add('deleteFile', '/files/{fileId}')
        ->controller([Files::class, 'deleteFile'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWopiOverrideHeaderSetTo('DELETE'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/EnumerateAncestors.html
     */
    $routes
        ->add('enumerateAncestors', '/files/{fileId}/ancestry')
        ->controller([Files::class, 'enumerateAncestors'])
        ->methods([Request::METHOD_GET])
        ->requirements(['fileId' => $fileIdRegex]);

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/GetShareUrl.html
     */
    $routes
        ->add('getShareUrl', '/files/{fileId}')
        ->controller([Files::class, 'getShareUrl'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWopiOverrideHeaderSetTo('GET_SHARE_URL'),
                    $hasHeader('X-WOPI-UrlType'),
                ]
            )
        );

    /**
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/PutUserInfo.html
     */
    $routes
        ->add('putUserInfo', '/files/{fileId}')
        ->controller([Files::class, 'putUserInfo'])
        ->methods([Request::METHOD_POST])
        ->requirements(['fileId' => $fileIdRegex])
        ->condition(
            implode(
                ' and ',
                [
                    $hasAccessTokenQueryParam,
                    $hasXWopiOverrideHeaderSetTo('PUT_USER_INFO'),
                ]
            )
        );
};

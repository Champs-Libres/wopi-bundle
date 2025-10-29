<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use ChampsLibres\WopiBundle\Controller\Files;
use ChampsLibres\WopiLib\Contract\Service\WopiInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * Wopi routes callback.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
return static function (RoutingConfigurator $routes) {
    /** Conditions shortcuts */
    $isHeaderSetTo = static function (string $header, string $value): string {
        return sprintf('request.headers.get("%s") === "%s"', $header, $value);
    };

    $hasQueryParam = static function (string $header): string {
        return sprintf('request.query.has("%s")', $header);
    };

    $hasHeader = static function (string $header): string {
        return sprintf('request.headers.has("%s")', $header);
    };

    /* Routes definitions */

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/CheckFileInfo.html
     */
    $routes
        ->add('checkFileInfo', '/files/{fileId}')
        ->controller([Files::class, 'checkFileInfo'])
        ->methods([Request::METHOD_GET])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/GetFile.html
     */
    $routes
        ->add('getFile', '/files/{fileId}/contents')
        ->controller([Files::class, 'getFile'])
        ->methods([Request::METHOD_GET])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/UnlockAndRelock.html
     */
    $routes
        ->add('unlockAndRelock', '/files/{fileId}')
        ->controller([Files::class, 'unlockAndRelock'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $hasHeader(WopiInterface::HEADER_LOCK),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'LOCK'),
                    $hasHeader(WopiInterface::HEADER_OLD_LOCK),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/Lock.html
     */
    $routes
        ->add('lock', '/files/{fileId}')
        ->controller([Files::class, 'lock'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $hasHeader(WopiInterface::HEADER_LOCK),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'LOCK'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/GetLock.html
     */
    $routes
        ->add('getLock', '/files/{fileId}')
        ->controller([Files::class, 'getLock'])
        ->methods([Request::METHOD_POST])
        ->condition('request.headers.get("X-WOPI-Override") === "GET_LOCK"')
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'GET_LOCK'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/RefreshLock.html
     */
    $routes
        ->add('refreshLock', '/files/{fileId}')
        ->controller([Files::class, 'refreshLock'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $hasHeader(WopiInterface::HEADER_LOCK),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'REFRESH_LOCK'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/Unlock.html
     */
    $routes
        ->add('unlock', '/files/{fileId}')
        ->controller([Files::class, 'unlock'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $hasHeader(WopiInterface::HEADER_LOCK),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'UNLOCK'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/PutFile.html
     */
    $routes
        ->add('putFile', '/files/{fileId}/contents')
        ->controller([Files::class, 'putFile'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    // @TODO: No lock header?
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'PUT'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/PutRelativeFile.html
     */
    $routes
        ->add('putRelativeFile', '/files/{fileId}')
        ->controller([Files::class, 'putRelativeFile'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'PUT_RELATIVE'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/RenameFile.html
     */
    $routes
        ->add('renameFile', '/files/{fileId}')
        ->controller([Files::class, 'renameFile'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $hasHeader(WopiInterface::HEADER_LOCK),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'RENAME_FILE'),
                    $hasHeader(WopiInterface::HEADER_REQUESTED_NAME),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/DeleteFile.html
     */
    $routes
        ->add('deleteFile', '/files/{fileId}')
        ->controller([Files::class, 'deleteFile'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'DELETE'),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/EnumerateAncestors.html
     */
    $routes
        ->add('enumerateAncestors', '/files/{fileId}/ancestry')
        ->controller([Files::class, 'enumerateAncestors'])
        ->methods([Request::METHOD_GET]);

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/GetShareUrl.html
     */
    $routes
        ->add('getShareUrl', '/files/{fileId}')
        ->controller([Files::class, 'getShareUrl'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'GET_SHARE_URL'),
                    $hasHeader(WopiInterface::HEADER_URL_TYPE),
                ]
            )
        );

    /*
     * @see https://wopi.readthedocs.io/projects/wopirest/en/latest/files/PutUserInfo.html
     */
    $routes
        ->add('putUserInfo', '/files/{fileId}')
        ->controller([Files::class, 'putUserInfo'])
        ->methods([Request::METHOD_POST])
        ->condition(
            implode(
                ' and ',
                [
                    $hasQueryParam('access_token'),
                    $isHeaderSetTo(WopiInterface::HEADER_OVERRIDE, 'PUT_USER_INFO'),
                ]
            )
        );
};

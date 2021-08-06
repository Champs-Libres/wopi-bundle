<?php

/**
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use ChampsLibres\WopiBundle\Controller\Files;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Wopi routes callback.
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 */
return static function (RoutingConfigurator $routes) {
    $routes
        ->add('checkFileInfo', '/files/{fileId}')
        ->controller([Files::class, 'checkFileInfo'])
        ->methods(['GET'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$']);

    $routes
        ->add('getFile', '/files/{fileId}/contents')
        ->controller([Files::class, 'getFile'])
        ->methods(['GET'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$']);

    $routes
        ->add('lock', '/files/{fileId}')
        ->controller([Files::class, 'lock'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "LOCK" and request.headers.get("X-WOPI-Lock") !== null');

    $routes
        ->add('getLock', '/files/{fileId}')
        ->controller([Files::class, 'getLock'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "GET_LOCK"');

    $routes
        ->add('refreshLock', '/files/{fileId}')
        ->controller([Files::class, 'refreshLock'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "REFRESH_LOCK" and request.headers.get("X-WOPI-Lock") !== null');

    $routes
        ->add('unlock', '/files/{fileId}')
        ->controller([Files::class, 'unlock'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "UNLOCK" and request.headers.get("X-WOPI-Lock") !== null');

    $routes
        ->add('unlockAndRelock', '/files/{fileId}')
        ->controller([Files::class, 'unlockAndRelock'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "LOCK" and request.headers.get("X-WOPI-Lock") !== null and request.headers.get("X-WOPI-OldLock") !== null');

    $routes
        ->add('putFile', '/files/{fileId}/contents')
        ->controller([Files::class, 'putFile'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$']);

    // TODO: Incomplete
    $routes
        ->add('putRelativeFile', '/files/{fileId}')
        ->controller([Files::class, 'putRelativeFile'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "PUT_RELATIVE"');

    $routes
        ->add('renameFile', '/files/{fileId}')
        ->controller([Files::class, 'renameFile'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "RENAME_FILE" and request.headers.get("X-WOPI-Lock") !== null and request.headers.get("X-WOPI-RequestedName") !== null');

    $routes
        ->add('deleteFile', '/files/{fileId}')
        ->controller([Files::class, 'deleteFile'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "DELETE"');

    $routes
        ->add('enumerateAncestors', '/files/{fileId}/ancestry')
        ->controller([Files::class, 'enumerateAncestors'])
        ->methods(['GET'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$']);

    $routes
        ->add('getShareUrl', '/files/{fileId}')
        ->controller([Files::class, 'getShareUrl'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "GET_SHARE_URL" and request.headers.get("X-WOPI-UrlType") !== null');

    $routes
        ->add('putUserInfo', '/files/{fileId}')
        ->controller([Files::class, 'putUserInfo'])
        ->methods(['POST'])
        ->requirements(['fileId' => '^[\w,\s-]+\.[\w,\s-]+$'])
        ->condition('request.headers.get("X-WOPI-Override") === "PUT_USER_INFO"');
};

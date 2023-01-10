<?php

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Contracts;

use ChampsLibres\WopiLib\Contract\Entity\Document;
use Psr\Http\Message\RequestInterface;

/**
 * Gives information to build the CheckFileInfo, and allow or deny access to some WOPI endpoint on
 * the host.
 *
 * See https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/rest/files/checkfileinfo/checkfileinfo-response#user-permissions-properties
 */
interface AuthorizationManagerInterface
{
    public function isRestrictedWebViewOnly(string $accessToken, Document $document, RequestInterface $request): bool;

    /**
     * Return true if the token is valid.
     *
     * This expect to check only for the validity of token for this document. This is called in operation
     * when the user is not expected to be important: i.e. for Lock, Unlock and Relock operation.
     *
     * Note that the controller will validate the client, using ProofValidation.
     */
    public function isTokenValid(string $accessToken, Document $document, RequestInterface $request): bool;

    public function userCanAttend(string $accessToken, Document $document, RequestInterface $request): bool;

    /**
     * if false, the method `deleteFile` will fails.
     */
    public function userCanDelete(string $accessToken, Document $document, RequestInterface $request): bool;

    /**
     * if the user can write this file relative.
     *
     * If this method return false, the api endpoint PutRelativeFile will fails.
     */
    public function userCannotWriteRelative(string $accessToken, Document $document, RequestInterface $request): bool;

    public function userCanPresent(string $accessToken, Document $document, RequestInterface $request): bool;

    /**
     * Return true if the user is allowed to see that file.
     *
     * This check is executed for CheckFileInfo, and in the method GetFile.
     */
    public function userCanRead(string $accessToken, Document $document, RequestInterface $request): bool;

    /**
     * If the user can rename the document.
     *
     * If this method return false, the api endpoint Rename will fails.
     */
    public function userCanRename(string $accessToken, Document $document, RequestInterface $request): bool;

    /**
     * If the user can write the document.
     *
     * If false, the property `ReadOnly` will be set to true.
     *
     * If false, the method `putFile` will fails.
     */
    public function userCanWrite(string $accessToken, Document $document, RequestInterface $request): bool;
}

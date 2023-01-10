<?php

declare(strict_types=1);

namespace ChampsLibres\WopiBundle\Contracts;

use Psr\Http\Message\RequestInterface;

interface UserManagerInterface
{
    /**
     * Return the user friendly name.
     *
     * If null, the operation will not be stopped (contrarily to the method @see{UserManagerInterface::getUserId}).
     */
    public function getUserFriendlyName(string $accessToken, string $fileId, RequestInterface $request): ?string;

    /**
     * Return the userId of the user accessing the file.
     *
     * If null is returned, and the user is **not** anonymous, the userId will be considered as not existing,
     * and further operation in WOPI Rest Endpoint will return an "Error 404" response.
     */
    public function getUserId(string $accessToken, string $fileId, RequestInterface $request): ?string;

    /**
     * Is the user anonymous.
     *
     * See https://learn.microsoft.com/en-us/microsoft-365/cloud-storage-partner-program/rest/files/checkfileinfo/checkfileinfo-response#isanonymoususer
     * for more details.
     */
    public function isAnonymousUser(string $accessToken, string $fileId, RequestInterface $request): bool;
}

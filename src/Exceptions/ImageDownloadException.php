<?php

namespace Ngfw\WebpConverter\Exceptions;

use Exception;

/**
 * Exception thrown when downloading an external image fails.
 */
class ImageDownloadException extends Exception
{
    /**
     * Create a new ImageDownloadException for HTTP errors.
     *
     * @param  string  $url
     * @param  int  $statusCode
     * @return static
     */
    public static function httpError(string $url, int $statusCode): self
    {
        return new self("Failed to download image from {$url}. HTTP Status: {$statusCode}");
    }

    /**
     * Create a new ImageDownloadException for invalid content type.
     *
     * @param  string  $contentType
     * @return static
     */
    public static function invalidContentType(string $contentType): self
    {
        return new self("Invalid content type: {$contentType}. Only images are allowed.");
    }

    /**
     * Create a new ImageDownloadException for file size exceeding limit.
     *
     * @param  int  $maxSize
     * @return static
     */
    public static function fileTooLarge(int $maxSize): self
    {
        $maxSizeMB = $maxSize / 1048576;
        return new self("File size exceeds maximum allowed size of {$maxSizeMB}MB");
    }

    /**
     * Create a new ImageDownloadException for general download failures.
     *
     * @param  string  $url
     * @param  string  $reason
     * @return static
     */
    public static function downloadFailed(string $url, string $reason): self
    {
        return new self("Failed to download image from {$url}. Error: {$reason}");
    }
}

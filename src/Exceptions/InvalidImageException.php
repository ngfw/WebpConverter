<?php

namespace Ngfw\WebpConverter\Exceptions;

use Exception;

/**
 * Exception thrown when an image is invalid or cannot be processed.
 */
class InvalidImageException extends Exception
{
    /**
     * Create a new InvalidImageException for unsupported image types.
     *
     * @param  string  $type
     * @return static
     */
    public static function unsupportedType(string $type): self
    {
        return new self("Unsupported image type: {$type}");
    }

    /**
     * Create a new InvalidImageException for failed image loading.
     *
     * @param  string  $file
     * @param  string  $reason
     * @return static
     */
    public static function cannotLoad(string $file, string $reason = ''): self
    {
        $message = "Failed to load image: {$file}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        return new self($message);
    }

    /**
     * Create a new InvalidImageException for failed conversion.
     *
     * @param  string  $reason
     * @return static
     */
    public static function conversionFailed(string $reason = ''): self
    {
        $message = "Failed to convert image to WebP";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new self($message);
    }
}

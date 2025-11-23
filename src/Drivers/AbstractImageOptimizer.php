<?php

namespace Ngfw\WebpConverter\Drivers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ngfw\WebpConverter\Contracts\ImageOptimizerInterface;
use Ngfw\WebpConverter\Exceptions\ImageDownloadException;

abstract class AbstractImageOptimizer implements ImageOptimizerInterface
{
    /**
     * The path to the image file.
     *
     * @var string
     */
    protected string $file;

    /**
     * The quality of the output WebP image.
     *
     * @var int
     */
    protected int $quality = 80;

    /**
     * Indicates if the file was downloaded from an external source.
     *
     * @var bool
     */
    protected bool $isExternalFile = false;

    /**
     * Maximum file size for images in bytes (10MB).
     *
     * @var int
     */
    protected const MAX_FILE_SIZE = 10485760;

    /**
     * HTTP request timeout in seconds.
     *
     * @var int
     */
    protected const REQUEST_TIMEOUT = 30;

    /**
     * Size threshold for non-photographic images in pixels.
     *
     * @var int
     */
    protected const NON_PHOTOGRAPHIC_THRESHOLD = 500;

    /**
     * Allowed MIME types for image downloads.
     *
     * @var array<string>
     */
    protected const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
    ];

    /**
     * Set the quality for the WebP conversion.
     *
     * @param  int  $quality  Quality value between 0 and 100
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setQuality(int $quality): self
    {
        if ($quality < 0 || $quality > 100) {
            throw new \InvalidArgumentException("Quality must be between 0 and 100, got: {$quality}");
        }

        $this->quality = $quality;
        return $this;
    }

    /**
     * Determine if the given URL is an external URL.
     *
     * @param  string  $url
     * @return bool
     */
    public function isExternalUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Download an external image to a temporary directory with security checks.
     *
     * @param  string  $url
     * @return string
     *
     * @throws \Ngfw\WebpConverter\Exceptions\ImageDownloadException
     */
    public function downloadExternalImage(string $url): string
    {
        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
        $tempDir = sys_get_temp_dir();
        $filename = basename(parse_url($url, PHP_URL_PATH));

        // Add unique identifier to prevent collisions
        $tempPath = sprintf('%s/webpconv_%s_%s', $tempDir, uniqid(), $filename);

        $this->ensureDirectoryExists($tempDir);

        try {
            $response = $client->get($url, [
                'sink' => $tempPath,
                'on_headers' => function ($response) use ($url) {
                    $statusCode = $response->getStatusCode();
                    if ($statusCode !== 200) {
                        throw ImageDownloadException::httpError($url, $statusCode);
                    }

                    // Validate content type
                    $contentType = $response->getHeaderLine('Content-Type');
                    if (!in_array($contentType, self::ALLOWED_MIME_TYPES, true)) {
                        throw ImageDownloadException::invalidContentType($contentType);
                    }

                    // Validate content length
                    $contentLength = $response->getHeaderLine('Content-Length');
                    if ($contentLength && (int) $contentLength > self::MAX_FILE_SIZE) {
                        throw ImageDownloadException::fileTooLarge(self::MAX_FILE_SIZE);
                    }
                }
            ]);

            // Verify the downloaded file
            if (!file_exists($tempPath)) {
                throw ImageDownloadException::downloadFailed($url, "Failed to save downloaded file");
            }

            // Additional file size check
            if (filesize($tempPath) > self::MAX_FILE_SIZE) {
                unlink($tempPath);
                throw ImageDownloadException::fileTooLarge(self::MAX_FILE_SIZE);
            }

            return $tempPath;
        } catch (RequestException $e) {
            // Clean up partial download if it exists
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            throw ImageDownloadException::downloadFailed($url, $e->getMessage());
        }
    }

    /**
     * Ensure the directory exists before saving the image.
     *
     * @param  string  $path
     * @return void
     *
     * @throws \Exception
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                throw new Exception("Failed to create directory: {$path}");
            }
        }
    }

    /**
     * Clean up any temporary external files on destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->isExternalFile && isset($this->file) && file_exists($this->file)) {
            @unlink($this->file);
        }
    }
}

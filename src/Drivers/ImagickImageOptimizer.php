<?php

namespace Ngfw\WebpConverter\Drivers;

use Imagick;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ngfw\WebpConverter\Contracts\ImageOptimizerInterface;

class ImagickImageOptimizer implements ImageOptimizerInterface
{
    /**
     * The path to the image file.
     *
     * @var string
     */
    protected string $file;

    /**
     * The Imagick instance for handling image operations.
     *
     * @var \Imagick
     */
    protected Imagick $image;

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
     * Load the image file for processing.
     *
     * @param  string  $file
     * @return $this
     *
     * @throws \Exception
     */
    public function load(string $file): self
    {
        if ($this->isExternalUrl($file)) {
            $file = $this->downloadExternalImage($file);
            $this->isExternalFile = true;
        }

        $this->file = $file;

        try {
            $this->image = new Imagick($this->file);
        } catch (Exception $e) {
            throw new Exception("Failed to load image: {$e->getMessage()}");
        }

        return $this;
    }

    /**
     * Set the quality for the WebP conversion.
     *
     * @param  int  $quality
     * @return $this
     */
    public function setQuality(int $quality): self
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * Resize the image while maintaining the aspect ratio.
     *
     * @param  int|null  $width
     * @param  int|null  $height
     * @return $this
     */
    public function resize(?int $width, ?int $height): self
    {
        if ($width || $height) {
            $originalWidth = $this->image->getImageWidth();
            $originalHeight = $this->image->getImageHeight();
            $aspectRatio = $originalWidth / $originalHeight;

            if ($width && $height) {
                if ($width / $height > $aspectRatio) {
                    $newWidth = $height * $aspectRatio;
                    $newHeight = $height;
                } else {
                    $newWidth = $width;
                    $newHeight = $width / $aspectRatio;
                }
            } elseif ($width) {
                $newWidth = $width;
                $newHeight = $width / $aspectRatio;
            } elseif ($height) {
                $newWidth = $height * $aspectRatio;
                $newHeight = $height;
            }

            $this->image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1, true);
        }

        return $this;
    }

    /**
     * Optimize the image by stripping metadata and reducing the color palette.
     *
     * @return $this
     */
    public function optimize(): self
    {
        // Strip metadata
        $this->image->stripImage();

        // Optional: Reduce color palette for non-photographic images
        if ($this->isNonPhotographicImage()) {
            $this->image->quantizeImage(256, Imagick::COLORSPACE_RGB, 0, false, false);
        }

        return $this;
    }

    /**
     * Convert the loaded image to WebP format.
     *
     * @param  string|null  $outputFile
     * @return string
     *
     * @throws \Exception
     */
    public function convert(?string $outputFile = null): string
    {
        $this->image->setImageFormat('webp');
        $this->image->setImageCompressionQuality($this->quality);

        $outputData = $this->image->getImageBlob();

        $this->ensureDirectoryExists(dirname($outputFile));

        // Save the image
        if ($outputFile) {
            file_put_contents($outputFile, $outputData);
            $this->image->destroy();
            return $outputFile;
        }

        $this->image->destroy();
        return $outputData;
    }


    /**
     * Determine if the image is a non-photographic image (e.g., logo, icon).
     *
     * @return bool
     */
    protected function isNonPhotographicImage(): bool
    {
        return $this->image->getImageWidth() < 500 && $this->image->getImageHeight() < 500;
    }

    /**
     * Ensure the directory exists before saving the image.
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
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
     * Download an external image to a temporary directory.
     *
     * @param  string  $url
     * @return string
     *
     * @throws \Exception
     */
    public function downloadExternalImage(string $url): string
    {
        $client = new Client();
        $tempDir = sys_get_temp_dir();
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $tempPath = "{$tempDir}/webpconv_{$filename}";

        try {
            $response = $client->get($url, ['sink' => $tempPath]);
            if ($response->getStatusCode() !== 200) {
                throw new Exception("Failed to download image from {$url}. HTTP Status: " . $response->getStatusCode());
            }
            return $tempPath;
        } catch (RequestException $e) {
            throw new Exception("Failed to download image from {$url}. Error: " . $e->getMessage());
        }
    }

    /**
     * Clean up any temporary external files on destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->isExternalFile && file_exists($this->file)) {
            unlink($this->file);  // Clean up the temporary file
        }
    }
}

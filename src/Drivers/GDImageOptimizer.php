<?php

namespace Ngfw\WebpConverter\Drivers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ngfw\WebpConverter\Contracts\ImageOptimizerInterface;

class GDImageOptimizer implements ImageOptimizerInterface
{
    /**
     * The path to the image file.
     *
     * @var string
     */
    protected string $file;

    /**
     * The desired width for the resized image.
     *
     * @var int|null
     */
    protected ?int $width = null;

    /**
     * The desired height for the resized image.
     *
     * @var int|null
     */
    protected ?int $height = null;

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
     */
    public function load(string $file): self
    {
        $this->file = $file;
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
     * Set the desired width and height for the image resizing.
     *
     * @param  int|null  $width
     * @param  int|null  $height
     * @return $this
     */
    public function resize(?int $width, ?int $height): self
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * Optimize the image by resizing and reducing the color palette.
     *
     * @return $this
     */
    public function optimize(): self
    {
        $image = $this->createGDImage($this->file);

        // Resize the image if width and height are set
        if ($this->width && $this->height) {
            $image = $this->resizeGDImageWithAspectRatio($image);
        }

        // Reduce color palette for non-photographic images
        if ($this->isNonPhotographicImage($image)) {
            imagetruecolortopalette($image, false, 256);
        }

        // Save the optimized image
        $optimizedData = $this->captureImageOutput(fn() => imagewebp($image, null, $this->quality));
        imagedestroy($image);

        $this->saveImage($optimizedData);

        return $this;
    }

    /**
     * Create a GD image resource from the given file.
     *
     * @param  string  $path
     * @return resource
     *
     * @throws \Exception
     */
    protected function createGDImage(string $path)
    {
        if ($this->isExternalUrl($path)) {
            $path = $this->downloadExternalImage($path);
            $this->isExternalFile = true;
        }

        $info = getimagesize($path);
        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            IMAGETYPE_BMP => imagecreatefrombmp($path),
            default => throw new Exception("Unsupported image type: {$info[2]}"),
        };
    }

    /**
     * Resize the GD image resource while maintaining the aspect ratio.
     *
     * @param  resource  $image
     * @return resource
     */
    protected function resizeGDImageWithAspectRatio($image)
    {
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        $aspectRatio = $originalWidth / $originalHeight;

        if ($this->width / $this->height > $aspectRatio) {
            $newWidth = $this->height * $aspectRatio;
            $newHeight = $this->height;
        } else {
            $newWidth = $this->width;
            $newHeight = $this->width / $aspectRatio;
        }

        return imagescale($image, $newWidth, $newHeight);
    }

    /**
     * Capture the output of a GD image function.
     *
     * @param  callable  $callback
     * @return string
     *
     * @throws \Exception
     */
    protected function captureImageOutput(callable $callback): string
    {
        ob_start();
        if (!$callback()) {
            throw new Exception("Failed to convert image to WebP with GD");
        }
        return ob_get_clean();
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
     * Determine if the image is a non-photographic image (e.g., logo, icon).
     *
     * @param  resource  $image
     * @return bool
     */
    protected function isNonPhotographicImage($image): bool
    {
        return imagesx($image) < 500 && imagesy($image) < 500;
    }

    /**
     * Save the optimized image data to the file system.
     *
     * @param  string  $data
     * @param  string|null  $outputFile
     * @return void
     */
    protected function saveImage(string $data, ?string $outputFile = null): void
    {
        if ($outputFile) {
            $this->ensureDirectoryExists(dirname($outputFile));
            file_put_contents($outputFile, $data);
        } else {
            file_put_contents($this->file, $data);
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
     * Convert the loaded image to WebP format.
     *
     * @param  string|null  $outputFile
     * @return string
     *
     * @throws \Exception
     */
    public function convert(?string $outputFile = null): string
    {
        $image = $this->createGDImage($this->file);

        if ($this->width && $this->height) {
            $image = $this->resizeGDImageWithAspectRatio($image);
        }

        $outputData = $this->captureImageOutput(fn() => imagewebp($image, null, $this->quality));
        imagedestroy($image);

        // Ensure the directory exists before saving the image
        $this->ensureDirectoryExists(dirname($outputFile));

        // Save the image
        if ($outputFile) {
            file_put_contents($outputFile, $outputData);
            return $outputFile;
        }

        return $outputData;
    }

    /**
     * Clean up any temporary external files on destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->isExternalFile && file_exists($this->file)) {
            unlink($this->file);
        }
    }
}

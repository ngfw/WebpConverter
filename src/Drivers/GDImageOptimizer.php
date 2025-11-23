<?php

namespace Ngfw\WebpConverter\Drivers;

use Exception;
use Ngfw\WebpConverter\Exceptions\InvalidImageException;

class GDImageOptimizer extends AbstractImageOptimizer
{
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
     * Load the image file for processing.
     *
     * @param  string  $file
     * @return $this
     */
    public function load(string $file): self
    {
        if ($this->isExternalUrl($file)) {
            $file = $this->downloadExternalImage($file);
            $this->isExternalFile = true;
        }

        $this->file = $file;
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
     * @return resource|\GdImage
     *
     * @throws \Ngfw\WebpConverter\Exceptions\InvalidImageException
     */
    protected function createGDImage(string $path)
    {
        if ($this->isExternalUrl($path)) {
            $path = $this->downloadExternalImage($path);
            $this->isExternalFile = true;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            throw InvalidImageException::cannotLoad($path, "Unable to read image file or invalid image format");
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_BMP => @imagecreatefrombmp($path),
            default => throw InvalidImageException::unsupportedType($info['mime'] ?? "type code {$info[2]}"),
        };

        if ($image === false) {
            throw InvalidImageException::cannotLoad($path, "Failed to create image resource");
        }

        return $image;
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

        if ($this->width && $this->height) {
            // Resize based on both width and height provided
            if ($this->width / $this->height > $aspectRatio) {
                $newWidth = $this->height * $aspectRatio;
                $newHeight = $this->height;
            } else {
                $newWidth = $this->width;
                $newHeight = $this->width / $aspectRatio;
            }
        } elseif ($this->width) {
            // Only width is provided, calculate height
            $newWidth = $this->width;
            $newHeight = $this->width / $aspectRatio;
        } elseif ($this->height) {
            // Only height is provided, calculate width
            $newWidth = $this->height * $aspectRatio;
            $newHeight = $this->height;
        } else {
            // No resizing if neither width nor height is provided
            return $image;
        }

        return imagescale($image, $newWidth, $newHeight);
    }


    /**
     * Capture the output of a GD image function.
     *
     * @param  callable  $callback
     * @return string
     *
     * @throws \Ngfw\WebpConverter\Exceptions\InvalidImageException
     */
    protected function captureImageOutput(callable $callback): string
    {
        ob_start();
        if (!$callback()) {
            ob_end_clean();
            throw InvalidImageException::conversionFailed("GD library failed to convert image");
        }
        return ob_get_clean();
    }

    /**
     * Determine if the image is a non-photographic image (e.g., logo, icon).
     *
     * @param  resource  $image
     * @return bool
     */
    protected function isNonPhotographicImage($image): bool
    {
        return imagesx($image) < self::NON_PHOTOGRAPHIC_THRESHOLD && imagesy($image) < self::NON_PHOTOGRAPHIC_THRESHOLD;
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

        // Check if resizing is necessary
        if ($this->width || $this->height) {
            $image = $this->resizeGDImageWithAspectRatio($image);
        }

        // Convert the image to WebP format
        $outputData = $this->captureImageOutput(fn() => imagewebp($image, null, $this->quality));
        imagedestroy($image);

        $this->ensureDirectoryExists(dirname($outputFile));

        // Save the image
        if ($outputFile) {
            file_put_contents($outputFile, $outputData);
            return $outputFile;
        }

        return $outputData;
    }
}

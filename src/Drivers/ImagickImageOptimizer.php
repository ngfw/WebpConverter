<?php

namespace Ngfw\WebpConverter\Drivers;

use Imagick;
use Exception;
use Ngfw\WebpConverter\Exceptions\InvalidImageException;

class ImagickImageOptimizer extends AbstractImageOptimizer
{
    /**
     * The Imagick instance for handling image operations.
     *
     * @var \Imagick
     */
    protected Imagick $image;

    /**
     * Load the image file for processing.
     *
     * @param  string  $file
     * @return $this
     *
     * @throws \Ngfw\WebpConverter\Exceptions\InvalidImageException
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
            throw InvalidImageException::cannotLoad($file, $e->getMessage());
        }

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
        return $this->image->getImageWidth() < self::NON_PHOTOGRAPHIC_THRESHOLD
            && $this->image->getImageHeight() < self::NON_PHOTOGRAPHIC_THRESHOLD;
    }
}

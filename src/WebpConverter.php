<?php

namespace Ngfw\WebpConverter;

use Ngfw\WebpConverter\Contracts\ImageOptimizerInterface;
use Ngfw\WebpConverter\Drivers\GDImageOptimizer;
use Ngfw\WebpConverter\Drivers\ImagickImageOptimizer;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Exception;

class WebpConverter
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * The image optimizer instance.
     *
     * @var \Ngfw\WebpConverter\Contracts\ImageOptimizerInterface
     */
    protected ImageOptimizerInterface $optimizer;

    /**
     * The storage path for WebP images.
     *
     * @var string
     */
    protected string $storagePath;
    protected string $file;

    /**
     * The subdirectory for storing WebP images.
     *
     * @var string
     */
    protected string $subDirectory = '';

    /**
     * The output file path.
     *
     * @var string|null
     */
    protected ?string $outputFile = null;

    /**
     * Create a new WebpConverter instance.
     *
     * @param  \Illuminate\Contracts\Filesystem\Filesystem  $filesystem
     * @return void
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->storagePath(Config::get('webp_converter.storage_path', 'public/storage/webp_images'));
        $this->setDriver(Config::get('webp_converter.driver', 'gd'));
    }

    /**
     * Set the image optimizer driver.
     *
     * @param  string  $driver  The driver to use ('gd' or 'imagick')
     * @return $this
     *
     * @throws \InvalidArgumentException  When an unsupported driver is specified
     */
    public function setDriver(string $driver): self
    {
        $this->optimizer = match ($driver) {
            'gd' => new GDImageOptimizer(),
            'imagick' => new ImagickImageOptimizer(),
            default => throw new \InvalidArgumentException("Unsupported driver: {$driver}. Supported drivers: gd, imagick"),
        };
        return $this;
    }

    /**
     * Set the storage path for WebP images.
     *
     * @param  string  $path
     * @return $this
     */
    public function storagePath(string $path): self
    {
        $this->storagePath = $path;
        return $this;
    }

    /**
     * Load the image file for conversion.
     *
     * Accepts both local file paths and remote URLs. If the output file already exists,
     * the optimizer will not be loaded to avoid unnecessary processing.
     *
     * @param  string  $file  Path to local file or remote URL
     * @return $this
     *
     * @throws \Ngfw\WebpConverter\Exceptions\InvalidImageException  When image cannot be loaded
     * @throws \Ngfw\WebpConverter\Exceptions\ImageDownloadException  When remote image download fails
     */
    public function load(string $file): self
    {
        $this->file = $file;
        $this->outputFile = $this->getWebpPath($this->file);

        if (!$this->isOutputFileAlreadyCreated()) {
            $this->optimizer->load($this->file);
        }
        return $this;
    }

    /**
     * Set the quality for the WebP conversion.
     *
     * @param  int  $quality  Quality value between 0 (worst) and 100 (best)
     * @return $this
     *
     * @throws \InvalidArgumentException  When quality is not between 0 and 100
     */
    public function quality(int $quality): self
    {
        $this->optimizer->setQuality($quality);
        return $this;
    }

    /**
     * Set the width for the WebP conversion.
     *
     * Height will be automatically calculated to maintain aspect ratio.
     *
     * @param  int  $width  Desired width in pixels
     * @return $this
     */
    public function width(int $width): self
    {
        $this->optimizer->resize($width, null);
        return $this;
    }

    /**
     * Set the height for the WebP conversion.
     *
     * Width will be automatically calculated to maintain aspect ratio.
     *
     * @param  int  $height  Desired height in pixels
     * @return $this
     */
    public function height(int $height): self
    {
        $this->optimizer->resize(null, $height);
        return $this;
    }

    /**
     * Set the filename for the output WebP image.
     *
     * @param  string  $filename
     * @return $this
     */
    public function saveAs(string $filename): self
    {
        $this->outputFile = $this->buildOutputFilePath($filename);
        return $this;
    }

    /**
     * Set the subdirectory for storing WebP images.
     *
     * @param  string  $subDirectory
     * @return $this
     */
    public function subDirectory(string $subDirectory): self
    {
        $this->subDirectory = '/' . trim($subDirectory, '/');
        return $this;
    }

    /**
     * Optimize the image using the selected driver.
     *
     * @return $this
     */
    public function optimize(): self
    {
        if (!$this->isOutputFileAlreadyCreated()) {
            $this->optimizer->optimize();
        }
        return $this;
    }

    /**
     * Refresh the image by forcing re-processing.
     *
     * Deletes the existing output file if it exists and reloads the source image.
     * Useful when the source image has been updated or when you want to regenerate
     * the WebP file with different settings.
     *
     * @return $this
     *
     * @throws \Ngfw\WebpConverter\Exceptions\InvalidImageException  When image cannot be reloaded
     * @throws \Ngfw\WebpConverter\Exceptions\ImageDownloadException  When remote image download fails
     */
    public function refresh(): self
    {
        if ($this->isOutputFileAlreadyCreated()) {
            $this->filesystem->delete($this->outputFile);
        }
        $this->optimizer->load($this->file);
        return $this;
    }

    /**
     * Convert the loaded image to WebP format and return the URL.
     *
     * If the output file already exists, returns the URL immediately without re-processing.
     * Otherwise, performs the conversion and saves the file before returning the URL.
     *
     * @return string  The URL to the converted WebP image
     *
     * @throws \Ngfw\WebpConverter\Exceptions\InvalidImageException  When conversion fails
     */
    public function convert(): string
    {
        $fullOutputPath = $this->filesystem->path($this->outputFile);
        if (file_exists($fullOutputPath)) {
            return $this->serve();
        }
        $this->ensureDirectoryExists(dirname($fullOutputPath));
        $this->optimizer->convert($fullOutputPath);

        return $this->serve();
    }

    /**
     * Convert the filename to have a .webp extension.
     *
     * @param  string  $filePath
     * @return string
     */
    public function convertToWebpFilename(string $filePath): string
    {
        $filenameWithoutExtension = pathinfo($filePath, PATHINFO_FILENAME);
        return $filenameWithoutExtension . '.webp';
    }

    /**
     * Ensure the directory for the output file exists.
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
     * Build the full output file path.
     *
     * @param  string  $filename
     * @return string
     */
    protected function buildOutputFilePath(string $filename): string
    {
        return "{$this->storagePath}{$this->subDirectory}/{$filename}.webp";
    }

    /**
     * Get the WebP path for a given file.
     *
     * @param  string  $path
     * @return string
     */
    protected function getWebpPath(string $path): string
    {
        $filename = pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_FILENAME);
        return "{$this->storagePath}{$this->subDirectory}/{$filename}.webp";
    }

    /**
     * Check if the output WebP file already exists in the filesystem.
     *
     * @return bool
     */
    protected function isOutputFileAlreadyCreated(): bool
    {
        return $this->filesystem->exists($this->outputFile);
    }

    /**
     * Serve the WebP image, optionally as a response array.
     *
     * @param  bool  $asResponse  If true, returns array with headers and content; if false, returns URL
     * @return string|array<string, mixed>  URL string or array with 'headers' and 'content' keys
     */
    public function serve(bool $asResponse = false): string|array
    {
        if ($asResponse) {
            $content = $this->filesystem->get($this->outputFile);
            return [
                'headers' => ['Content-Type' => 'image/webp'],
                'content' => $content,
            ];
        }

        return $this->filesystem->url($this->outputFile);
    }

}

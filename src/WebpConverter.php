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
     * @param  string  $driver
     * @return $this
     *
     * @throws \Exception
     */
    public function setDriver(string $driver): self
    {
        $this->optimizer = match ($driver) {
            'gd' => new GDImageOptimizer(),
            'imagick' => new ImagickImageOptimizer(),
            default => throw new Exception("Unsupported driver: {$driver}"),
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
     * @param  string  $file
     * @return $this
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
     * @param  int  $quality
     * @return $this
     */
    public function quality(int $quality): self
    {
        $this->optimizer->setQuality($quality);
        return $this;
    }

    /**
     * Set the width for the WebP conversion.
     *
     * @param  int  $width
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
     * @param  int  $height
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
     * Refresh the image by re-downloading it if it has been downloaded already.
     * 
     * Calls the `refresh` method on the optimizer, which deletes and re-downloads 
     * the image if it was previously downloaded. 
     * 
     * @return $this
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
     * Convert the loaded image to WebP format.
     *
     * @return string
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
     * @param  bool  $asResponse
     * @return string|array
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

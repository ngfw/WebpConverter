<?php

namespace Ngfw\WebpConverter\Tests\Unit;

use Ngfw\WebpConverter\WebpConverter;
use Ngfw\WebpConverter\Tests\TestCase;

class WebpConverterTest extends TestCase
{
    public function test_if_converts_png_to_webp_with_default_settings()
    {
        $converter = new WebpConverter($this->filesystem);

        $pngFilePath = __DIR__ . '/../fixtures/test_image_png.png';
        $converter->storagePath("tests/storage/public")
            ->load($pngFilePath)
            ->convert();

        $webpFilename = pathinfo($pngFilePath, PATHINFO_FILENAME) . '.webp';
        $this->assertTrue($this->filesystem->exists($webpFilename), "Failed asserting that WebP file was created for PNG.");
    }

    public function test_if_converts_with_custom_quality()
    {
        $converter = new WebpConverter($this->filesystem);

        $jpgFilePath = __DIR__ . '/../fixtures/test_image_jpg.jpg';
        $converter->storagePath("tests/storage/public")
            ->load($jpgFilePath)
            ->quality(50)
            ->convert();

        $webpFilename = pathinfo($jpgFilePath, PATHINFO_FILENAME) . '.webp';
        $this->assertTrue($this->filesystem->exists($webpFilename), "Failed asserting that WebP file was created with custom quality.");
    }

    public function test_if_converts_with_custom_filename()
    {
        $converter = new WebpConverter($this->filesystem);

        $pngFilePath = __DIR__ . '/../fixtures/test_image_png.png';
        $converter->storagePath("tests/storage/public")
            ->load($pngFilePath)
            ->saveAs('custom_filename')
            ->convert();

        $webpFilename = 'custom_filename.webp';
        $this->assertTrue($this->filesystem->exists($webpFilename), "Failed asserting that WebP file was created with custom filename.");
    }

    public function test_if_does_not_reconvert_if_webp_exists()
    {
        $converter = new WebpConverter($this->filesystem);

        $jpgFilePath = __DIR__ . '/../fixtures/test_image_jpg.jpg';
        $webpFilename = 'override_test.webp';

        // Simulate an existing WebP file
        $this->filesystem->put($webpFilename, 'existing content');

        // Attempt to convert again
        $converter->storagePath("tests/storage/public")
            ->load($jpgFilePath)
            ->saveAs('override_test')
            ->convert();

        // Assert that the WebP file was not overwritten
        $this->assertEquals('existing content', $this->filesystem->get($webpFilename), "Failed asserting that existing WebP file was not overwritten.");
    }

    public function test_if_converts_with_custom_dimensions()
    {
        $converter = new WebpConverter($this->filesystem);

        $jpgFilePath = __DIR__ . '/../fixtures/test_image_jpg.jpg';
        $converter->storagePath("tests/storage/public")
            ->load($jpgFilePath)
            ->width(100)
            ->height(100)
            ->convert();

        $webpFilename = pathinfo($jpgFilePath, PATHINFO_FILENAME) . '.webp';
        $this->assertTrue($this->filesystem->exists($webpFilename), "Failed asserting that WebP file was created with custom dimensions.");
    }

    public function test_if_optimizes_and_converts()
    {
        $converter = new WebpConverter($this->filesystem);

        $jpgFilePath = __DIR__ . '/../fixtures/test_image_jpg.jpg';
        $converter->storagePath("tests/storage/public")
            ->load($jpgFilePath)
            ->optimize()
            ->convert();

        $webpFilename = pathinfo($jpgFilePath, PATHINFO_FILENAME) . '.webp';
        $this->assertTrue($this->filesystem->exists($webpFilename), "Failed asserting that WebP file was optimized and converted.");
    }

    public function test_if_serves_without_saving()
    {
        $converter = new WebpConverter($this->filesystem);

        $jpgFilePath = __DIR__ . '/../fixtures/test_image_jpg.jpg';
        $response = $converter->storagePath("tests/storage/public")
            ->load($jpgFilePath)
            ->serve(true);

        $this->assertIsArray($response, "Failed asserting that serve method returns an array.");
        $this->assertArrayHasKey('headers', $response, "Failed asserting that response contains headers.");
        $this->assertArrayHasKey('content', $response, "Failed asserting that response contains content.");
    }

}

<?php 
namespace Ngfw\WebpConverter\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter as FlysystemLocalAdapter;

class TestCase extends BaseTestCase
{
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $adapter = new FlysystemLocalAdapter(__DIR__ . '/storage/public/');
        $flysystem = new Filesystem($adapter);
        $this->filesystem = new FilesystemAdapter($flysystem, $adapter);
        if (!file_exists(__DIR__ . '/storage/public')) {
            mkdir(__DIR__ . '/storage/public', 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory(__DIR__ . '/storage/webp_images');
        parent::tearDown();
    }

    protected function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }
}

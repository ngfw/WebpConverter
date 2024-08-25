# WebP Converter Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ngfw/WebpConverter.svg?style=flat-square)](https://packagist.org/packages/ngfw/WebpConverter)
[![Total Downloads](https://img.shields.io/packagist/dt/ngfw/WebpConverter.svg?style=flat-square)](https://packagist.org/packages/ngfw/WebpConverter)

The WebP Converter package is your go-to tool for converting images to the WebP format. It’s built to make your images smaller, faster, and more efficient without sacrificing quality. Whether you're dealing with JPEGs, PNGs, or even BMPs, this package handles them all with ease. It supports both GD and Imagick drivers, giving you flexibility depending on your server environment. And yes, it’s PSR-4 compliant, so it fits seamlessly into your modern PHP projects.

## Installation

You can install the package via Composer:

```bash
composer require ngfw/WebpConverter
```

After installing the package, you may want to publish the configuration file to customize the settings according to your project’s needs. To publish the configuration file, run the following command:

```bash
php artisan vendor:publish --tag="webp_converter"
```

This will create a webp_converter.php file in your config directory. Inside this file, you can configure the following options:

*driver*: Specifies the image processing library to use (gd or imagick).

*quality*: Sets the default quality for WebP conversion.

*storage_path*: Defines the default storage path for the converted WebP images.


Example configuration file (config/webp_converter.php):

```php

return [

    /*
    * Default Image Processing Driver
    */
    'driver' => env('WEBP_CONVERTER_DRIVER', 'gd'),

    /*
    * Default WebP Quality
    */
    'quality' => env('WEBP_CONVERTER_QUALITY', 80),

    /*
    * Storage Path
    */

    'storage_path' => env('WEBP_CONVERTER_STORAGE_PATH', 'public/storage/webp_images'),
];
```
You can then customize these settings as needed to better fit your application’s requirements.
## Usage

Using the WebP Converter is super simple. Here’s how you can integrate it into your project:

**Loading an Image:**

You can load an image from a local path or even a remote URL. The package is smart enough to handle both.

```php
use Ngfw\WebpConverter\WebpConverter;

$converter = new WebpConverter($filesystem);

// Load a local image
$converter->load('/path/to/image.jpg');

// Load a remote image
$converter->load('https://example.com/image.png');
```

**Setting Quality:**

Want to control the quality of the output WebP image? No problem. Adjust the quality easily.

```php
$converter->quality(80); // Set quality to 80%
```

**Resizing the Image:**

Need to resize the image? Just specify the width and height.

```php
$converter->width(300)->height(200); // Resize to 300x200
```

**Optimizing the Image:**

Optimize your image to reduce file size even further. The package applies smart optimizations to deliver the best results.

```php
$converter->optimize();
```

**Converting to WebP:**

Finally, convert your image to WebP. You can even specify a custom filename for the output.

```php

$webpUrl = $converter->saveAs('optimized_image')->convert();
echo $webpUrl; // Outputs the URL to the converted WebP image
```

**Chaining Multiple Methods:**

You can chain multiple methods for a concise, single-line conversion:

```php

$webpUrl = $converter->load('/path/to/image.jpg')
                     ->quality(90)
                     ->width(500)
                     ->height(300)
                     ->optimize()
                     ->saveAs('final_image')
                     ->convert();
echo $webpUrl; // Outputs the URL to the optimized WebP image
```

**Serving the Image:**

Serve the WebP image directly, or get the raw data if you need to do something custom.

```php
$content = $converter->serve(true); // Serve the image as a response array
```

### Blade Examples

Here are a couple of examples on how to use the WebP Converter in a Blade template:

**Local Image Conversion:**
Convert a local image using Laravel's asset helper:

```blade

<img src="@webpConverter(asset('/images/png1.png'))" />
```

This will convert the image located at public/images/png1.png into WebP format and serve it.

**Remote Image Conversion:**

Convert an image from a remote URL:

```blade

<img src="@webpConverter('https://images.unsplash.com/photo-1724217552369-22b256e395d9?q=80&w=3270&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D')" />
```

This will download the image from the specified URL, convert it to WebP format, and then serve it.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email the author instead of using the issue tracker.

## Credits

- [Nick G](https://github.com/ngfw)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

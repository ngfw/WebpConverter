<?php


return [
/*
|--------------------------------------------------------------------------
| Default Image Processing Driver
|--------------------------------------------------------------------------
|
| This option controls the default image processing driver that will be used
| by the package to perform image conversions. You can set it to "gd" or 
| "imagick" based on the capabilities of your server environment.
|
*/

'driver' => env('WEBP_CONVERTER_DRIVER', 'gd'),

/*
|--------------------------------------------------------------------------
| Default WebP Quality
|--------------------------------------------------------------------------
|
| Here you may specify the quality of the WebP images that are generated
| by the package. The value should be between 0 and 100, with 100 being
| the highest quality.
|
*/

'quality' => env('WEBP_CONVERTER_QUALITY', 80),

/*
|--------------------------------------------------------------------------
| Storage Path
|--------------------------------------------------------------------------
|
| This option defines where the converted WebP images will be stored.
| You can change this to suit your application's structure.
|
*/

'storage_path' => env('WEBP_CONVERTER_STORAGE_PATH', 'public/storage/webp_images'),

];
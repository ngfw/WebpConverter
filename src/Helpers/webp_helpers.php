<?php

if (!function_exists('webpConverter')) {
    function webpConverter($filePath, $options = [])
    {
        $converter = app(\Ngfw\WebpConverter\WebpConverter::class);

        if (isset($options['driver'])) {
            $converter->driver($options['driver']);
        }
        if (isset($options['quality'])) {
            $converter->quality($options['quality']);
        }
        if (isset($options['width'])) {
            $converter->width($options['width']);
        }
        if (isset($options['height'])) {
            $converter->height($options['height']);
        }
        if (isset($options['filename'])) {
            $converter->saveAs($options['filename']);
        }

        return $converter->load($filePath)->convert();
    }
}
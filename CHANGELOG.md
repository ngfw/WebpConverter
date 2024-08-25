# Changelog

All notable changes to `webpconverter` will be documented in this file.

## 1.0.1 - 2024-08-25

### Added
- Implemented the `refresh` method for both `GDImageOptimizer` and `ImagickImageOptimizer` drivers, allowing re-downloading and reprocessing of images.
- Added logic to handle external URLs by downloading images before processing them.
- Improved the image resizing logic in both drivers to handle cases where either width, height, or both are provided.
- Introduced the `isOutputFileAlreadyCreated` method in `WebpConverter` to check if the WebP file already exists, preventing unnecessary processing.
- Enhanced the `optimize` method to skip processing if the output file already exists.

### Fixed
- Resolved issues with resizing when only one dimension (width or height) is provided.
- Ensured that temporary directories are created if they do not exist before saving files.



## 1.0.0 - 2024-08-24

### Added
- Initial release of `webpconverter` with basic WebP conversion functionality.
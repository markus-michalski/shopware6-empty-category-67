# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-01-14

### Added
- Initial release for Shopware 6.7
- Custom HTML messages per category for empty states
- Image upload support with Media Manager integration
- Image size options: Small (150px), Medium (250px), Large (400px)
- Image alignment: Left, Center, Right
- Text alignment: Left, Center, Right
- Custom CSS class support for individual styling
- Parent category inheritance with configurable depth (max 10 levels)
- XSS protection through CSS class sanitization
- Twig extension `mmd_empty_category_message()` for template access
- Multi-language support (German, English)
- PHPUnit tests with 43 test cases
- PHPStan Level 8 static analysis

[Unreleased]: https://github.com/markus-michalski/shopware6-empty-category-67/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/markus-michalski/shopware6-empty-category-67/releases/tag/v1.0.0

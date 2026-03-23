# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Nothing yet

### Changed
- Nothing yet

### Deprecated
- Nothing yet

### Removed
- Nothing yet

### Fixed
- Nothing yet

### Security
- Nothing yet

## [1.0.2] - 2026-03-23

### Fixed
- update license check from GPL-3.0 to proprietary
- use eager loading for LCP image instead of lazy
- add explicit image dimensions to prevent CLS layout shift
- update license from GPL-3.0-or-later to proprietary

## [1.0.1] - 2026-03-13

### Changed
- improve: security audit hardening - PHPStan level 8, CSP compatibility, subscriber tests

## [1.0.0] - 2026-01-18

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
- PHPStan Level 6 static analysis

[Unreleased]: https://github.com/markus-michalski/shopware6-empty-category-67/compare/v1.0.2...HEAD
[1.0.0]: https://github.com/markus-michalski/shopware6-empty-category-67/releases/tag/v1.0.0
[1.0.1]: https://github.com/markus-michalski/shopware6-empty-category-67/releases/tag/v1.0.1
[1.0.2]: https://github.com/markus-michalski/shopware6-empty-category-67/releases/tag/v1.0.2

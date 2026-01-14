# Shopware 6 Empty Category Messages

Custom "No Products" messages and images per category for Shopware 6.7 with optional parent category inheritance.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![Shopware](https://img.shields.io/badge/Shopware-6.7-blue.svg)](https://www.shopware.com/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%206-brightgreen.svg)](https://phpstan.org/)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue.svg)](LICENSE)

## Features

- **Custom Empty Messages**: Define individual HTML messages per category when no products are available
- **Category Images**: Add optional images (e.g., sad smiley) to empty category states
- **Image Size Control**: Small (150px), Medium (250px), or Large (400px)
- **Flexible Alignment**: Separate image and text alignment (left, center, right)
- **CSS Classes**: Add custom CSS classes for individual styling
- **Parent Inheritance**: Optionally inherit empty messages from parent categories
- **Multi-Language**: German and English translations included

## Requirements

- Shopware 6.7.x
- PHP 8.2+

## Installation

### Via Shopware Store (recommended)

Installation via the Shopware Store is recommended. After download, you can install the plugin directly from the backend.

### Via Composer

```bash
composer require mmd/sw67-empty-category
bin/console plugin:install --activate MmdEmptyCategory
bin/console cache:clear
```

### Manual Installation

1. Download the plugin ZIP
2. Extract to `custom/plugins/MmdEmptyCategory`
3. Run:
```bash
bin/console plugin:refresh
bin/console plugin:install --activate MmdEmptyCategory
bin/console cache:clear
```

## Configuration

### Setting Up Empty Category Messages

1. Navigate to **Catalogues > Categories** in the Admin
2. Select a category
3. Scroll to the **Custom Fields** section
4. Find the **Empty Category** field group:

| Field | Description |
|-------|-------------|
| **Message** | HTML text shown when category has no products |
| **Image** | Optional image to display |
| **Image Size** | Small (150px), Medium (250px), Large (400px) |
| **Image Alignment** | Left, Center, Right |
| **Text Alignment** | Left, Center, Right |
| **CSS Classes** | Additional CSS classes for custom styling |
| **Inherit from Parent** | Use parent category message if no own message defined |

### Inheritance

When "Inherit from Parent" is enabled and no custom message is defined:
- The plugin traverses up the category tree
- Uses the first parent with a defined message
- Maximum inheritance depth: 10 levels

## Screenshots

### Admin Configuration
Custom fields in category management for configuring empty state messages.

### Frontend Display
Custom message with image displayed when a category has no products.

## Development

### Running Tests

```bash
composer test
```

### Static Analysis

```bash
composer analyse
```

## License

GPL-3.0-or-later - see [LICENSE](LICENSE) file for details.

## Support

Markus Michalski - [support@markus-michalski.net](mailto:support@markus-michalski.net)

Website: [https://markus-michalski.de](https://markus-michalski.de)

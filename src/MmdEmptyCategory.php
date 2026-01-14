<?php

/**
 * Shopware 6 Empty Category Messages Plugin
 *
 * @package   MmdEmptyCategory
 * @author    Markus Michalski <info@markus-michalski.net>
 * @copyright 2025 Markus Michalski
 * @license   GPL-3.0-or-later - see LICENSE file for details
 */

declare(strict_types=1);

namespace Mmd\EmptyCategory;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

/**
 * Plugin for custom "No Products" messages per category
 *
 * Allows shop owners to define individual empty-state messages,
 * background images, and CSS classes for each category.
 * Supports inheritance from parent categories.
 */
class MmdEmptyCategory extends Plugin
{
    public const CUSTOM_FIELD_SET_NAME = 'mmd_empty_category';

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->upsertCustomFields($installContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $this->addMissingCustomFields($updateContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeCustomFields();
    }

    /**
     * Add only missing custom fields during plugin update
     *
     * This prevents duplicate entry errors when updating the plugin.
     */
    private function addMissingCustomFields(Context $context): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        // Get custom field set ID (binary)
        $setIdBinary = $connection->fetchOne(
            'SELECT id FROM custom_field_set WHERE name = :name',
            ['name' => self::CUSTOM_FIELD_SET_NAME]
        );

        if ($setIdBinary === false) {
            // Set doesn't exist, create everything
            $this->upsertCustomFields($context);

            return;
        }

        // Convert binary ID to hex string (UUID format for Shopware DAL)
        $setId = bin2hex($setIdBinary);

        // Get existing custom field names
        $existingFields = $connection->fetchFirstColumn(
            'SELECT name FROM custom_field WHERE set_id = :setId',
            ['setId' => $setIdBinary]
        );

        $customFieldRepository = $this->container->get('custom_field.repository');

        // Define all fields that should exist
        $allFields = $this->getCustomFieldDefinitions();

        // Add only missing fields
        $fieldsToAdd = [];
        foreach ($allFields as $field) {
            if (!in_array($field['name'], $existingFields, true)) {
                $field['customFieldSetId'] = $setId;
                $fieldsToAdd[] = $field;
            }
        }

        if (!empty($fieldsToAdd)) {
            $customFieldRepository->create($fieldsToAdd, $context);
        }
    }

    /**
     * Get all custom field definitions
     *
     * @return array<array<string, mixed>>
     */
    private function getCustomFieldDefinitions(): array
    {
        return [
            [
                'name' => 'mmd_empty_category_message',
                'type' => 'html',
                'config' => [
                    'label' => [
                        'de-DE' => 'Nachricht bei leerer Kategorie',
                        'en-GB' => 'Empty category message',
                    ],
                    'helpText' => [
                        'de-DE' => 'HTML-Text, der angezeigt wird, wenn keine Produkte in dieser Kategorie vorhanden sind.',
                        'en-GB' => 'HTML text shown when no products exist in this category.',
                    ],
                    'componentName' => 'sw-text-editor',
                    'customFieldPosition' => 10,
                ],
            ],
            [
                'name' => 'mmd_empty_category_image',
                'type' => 'text',
                'config' => [
                    'label' => [
                        'de-DE' => 'Bild',
                        'en-GB' => 'Image',
                    ],
                    'helpText' => [
                        'de-DE' => 'Optionales Bild (z.B. ein trauriges Smiley), das bei leerer Kategorie angezeigt wird.',
                        'en-GB' => 'Optional image (e.g., a sad smiley) shown when category is empty.',
                    ],
                    'componentName' => 'sw-media-field',
                    'customFieldPosition' => 20,
                ],
            ],
            [
                'name' => 'mmd_empty_category_image_size',
                'type' => 'select',
                'config' => [
                    'label' => [
                        'de-DE' => 'Bildgröße',
                        'en-GB' => 'Image size',
                    ],
                    'helpText' => [
                        'de-DE' => 'Maximale Breite des Bildes.',
                        'en-GB' => 'Maximum width of the image.',
                    ],
                    'componentName' => 'sw-single-select',
                    'options' => [
                        [
                            'value' => 'small',
                            'label' => [
                                'de-DE' => 'Klein (150px)',
                                'en-GB' => 'Small (150px)',
                            ],
                        ],
                        [
                            'value' => 'medium',
                            'label' => [
                                'de-DE' => 'Mittel (250px)',
                                'en-GB' => 'Medium (250px)',
                            ],
                        ],
                        [
                            'value' => 'large',
                            'label' => [
                                'de-DE' => 'Groß (400px)',
                                'en-GB' => 'Large (400px)',
                            ],
                        ],
                    ],
                    'customFieldPosition' => 25,
                ],
            ],
            [
                'name' => 'mmd_empty_category_image_align',
                'type' => 'select',
                'config' => [
                    'label' => [
                        'de-DE' => 'Bild-Ausrichtung',
                        'en-GB' => 'Image alignment',
                    ],
                    'componentName' => 'sw-single-select',
                    'options' => [
                        [
                            'value' => 'left',
                            'label' => [
                                'de-DE' => 'Links',
                                'en-GB' => 'Left',
                            ],
                        ],
                        [
                            'value' => 'center',
                            'label' => [
                                'de-DE' => 'Mitte',
                                'en-GB' => 'Center',
                            ],
                        ],
                        [
                            'value' => 'right',
                            'label' => [
                                'de-DE' => 'Rechts',
                                'en-GB' => 'Right',
                            ],
                        ],
                    ],
                    'customFieldPosition' => 26,
                ],
            ],
            [
                'name' => 'mmd_empty_category_text_align',
                'type' => 'select',
                'config' => [
                    'label' => [
                        'de-DE' => 'Text-Ausrichtung',
                        'en-GB' => 'Text alignment',
                    ],
                    'componentName' => 'sw-single-select',
                    'options' => [
                        [
                            'value' => 'left',
                            'label' => [
                                'de-DE' => 'Links',
                                'en-GB' => 'Left',
                            ],
                        ],
                        [
                            'value' => 'center',
                            'label' => [
                                'de-DE' => 'Mitte',
                                'en-GB' => 'Center',
                            ],
                        ],
                        [
                            'value' => 'right',
                            'label' => [
                                'de-DE' => 'Rechts',
                                'en-GB' => 'Right',
                            ],
                        ],
                    ],
                    'customFieldPosition' => 27,
                ],
            ],
            [
                'name' => 'mmd_empty_category_css_class',
                'type' => 'text',
                'config' => [
                    'label' => [
                        'de-DE' => 'CSS-Klassen',
                        'en-GB' => 'CSS classes',
                    ],
                    'helpText' => [
                        'de-DE' => 'Zusätzliche CSS-Klassen für individuelles Styling (z.B. "my-class another-class").',
                        'en-GB' => 'Additional CSS classes for custom styling (e.g., "my-class another-class").',
                    ],
                    'placeholder' => [
                        'de-DE' => 'z.B. sad-category highlight',
                        'en-GB' => 'e.g., sad-category highlight',
                    ],
                    'customFieldPosition' => 30,
                ],
            ],
            [
                'name' => 'mmd_empty_category_inherit',
                'type' => 'bool',
                'config' => [
                    'label' => [
                        'de-DE' => 'Von übergeordneter Kategorie erben',
                        'en-GB' => 'Inherit from parent category',
                    ],
                    'helpText' => [
                        'de-DE' => 'Wenn aktiviert und kein eigener Text definiert ist, wird der Text der übergeordneten Kategorie verwendet.',
                        'en-GB' => 'If enabled and no own text is defined, the parent category text will be used.',
                    ],
                    'customFieldPosition' => 40,
                ],
            ],
        ];
    }

    private function upsertCustomFields(Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetRepository->upsert([
            [
                'name' => self::CUSTOM_FIELD_SET_NAME,
                'config' => [
                    'label' => [
                        'de-DE' => 'Leere Kategorie',
                        'en-GB' => 'Empty Category',
                    ],
                ],
                'customFields' => $this->getCustomFieldDefinitions(),
                'relations' => [
                    [
                        'entityName' => 'category',
                    ],
                ],
            ],
        ], $context);
    }

    private function removeCustomFields(): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        // Remove custom field set and its fields
        $connection->executeStatement(
            'DELETE FROM custom_field_set WHERE name = :name',
            ['name' => self::CUSTOM_FIELD_SET_NAME]
        );
    }
}

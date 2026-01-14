<?php

declare(strict_types=1);

namespace Mmd\EmptyCategory;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

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
        $this->createCustomFields($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeCustomFields();
    }

    private function createCustomFields(InstallContext $context): void
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
                'customFields' => [
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
                                'de-DE' => 'Hintergrundbild',
                                'en-GB' => 'Background image',
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
                ],
                'relations' => [
                    [
                        'entityName' => 'category',
                    ],
                ],
            ],
        ], $context->getContext());
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

<?php

declare(strict_types=1);

namespace Mmd\EmptyCategory\Tests\Unit\Service;

use Mmd\EmptyCategory\Service\EmptyCategoryMessage;
use Mmd\EmptyCategory\Service\EmptyCategoryMessageService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;

#[CoversClass(EmptyCategoryMessageService::class)]
class EmptyCategoryMessageServiceTest extends TestCase
{
    private EmptyCategoryMessageService $service;

    protected function setUp(): void
    {
        $this->service = new EmptyCategoryMessageService();
    }

    #[Test]
    public function itReturnsEmptyMessageWhenNoCustomFields(): void
    {
        $category = $this->createCategory();

        $result = $this->service->getMessage($category);

        self::assertInstanceOf(EmptyCategoryMessage::class, $result);
        self::assertFalse($result->hasContent());
    }

    #[Test]
    public function itReturnsEmptyMessageWhenCustomFieldsAreNull(): void
    {
        $category = $this->createCategory(customFields: null);

        $result = $this->service->getMessage($category);

        self::assertFalse($result->hasContent());
    }

    #[Test]
    public function itReturnsMessageFromCustomFields(): void
    {
        $category = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>No products here!</p>',
        ]);

        $result = $this->service->getMessage($category);

        self::assertTrue($result->hasMessage());
        self::assertSame('<p>No products here!</p>', $result->message);
    }

    #[Test]
    public function itReturnsCssClassFromCustomFields(): void
    {
        $category = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Text</p>',
            'mmd_empty_category_css_class' => 'sad-category custom-style',
        ]);

        $result = $this->service->getMessage($category);

        self::assertSame('sad-category custom-style', $result->cssClass);
    }

    #[Test]
    public function itReturnsImageUrlFromMediaEntity(): void
    {
        $media = $this->createMock(MediaEntity::class);
        $media->method('getUrl')->willReturn('https://shop.example/media/sad-face.png');

        $category = $this->createCategory(
            customFields: [
                'mmd_empty_category_message' => '<p>Text</p>',
                'mmd_empty_category_image' => 'media-uuid-123',
            ],
            media: $media,
        );

        $result = $this->service->getMessage($category);

        self::assertTrue($result->hasImage());
        self::assertSame('https://shop.example/media/sad-face.png', $result->imageUrl);
    }

    #[Test]
    public function itReturnsEmptyImageWhenMediaNotLoaded(): void
    {
        $category = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Text</p>',
            'mmd_empty_category_image' => 'media-uuid-123',
        ]);

        $result = $this->service->getMessage($category);

        self::assertFalse($result->hasImage());
        self::assertSame('', $result->imageUrl);
    }

    #[Test]
    public function itInheritsFromParentWhenEnabled(): void
    {
        $parent = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Parent message</p>',
            'mmd_empty_category_css_class' => 'parent-class',
        ]);

        $child = $this->createCategory(
            customFields: [
                'mmd_empty_category_inherit' => true,
            ],
            parent: $parent,
        );

        $result = $this->service->getMessage($child);

        self::assertTrue($result->hasMessage());
        self::assertSame('<p>Parent message</p>', $result->message);
        self::assertSame('parent-class', $result->cssClass);
    }

    #[Test]
    public function itDoesNotInheritWhenDisabled(): void
    {
        $parent = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Parent message</p>',
        ]);

        $child = $this->createCategory(
            customFields: [
                'mmd_empty_category_inherit' => false,
            ],
            parent: $parent,
        );

        $result = $this->service->getMessage($child);

        self::assertFalse($result->hasContent());
    }

    #[Test]
    public function itPrefersOwnMessageOverInheritance(): void
    {
        $parent = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Parent message</p>',
        ]);

        $child = $this->createCategory(
            customFields: [
                'mmd_empty_category_message' => '<p>Own message</p>',
                'mmd_empty_category_inherit' => true,
            ],
            parent: $parent,
        );

        $result = $this->service->getMessage($child);

        self::assertSame('<p>Own message</p>', $result->message);
    }

    #[Test]
    public function itInheritsRecursivelyFromGrandparent(): void
    {
        $grandparent = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Grandparent message</p>',
        ]);

        $parent = $this->createCategory(
            customFields: [
                'mmd_empty_category_inherit' => true,
            ],
            parent: $grandparent,
        );

        $child = $this->createCategory(
            customFields: [
                'mmd_empty_category_inherit' => true,
            ],
            parent: $parent,
        );

        $result = $this->service->getMessage($child);

        self::assertSame('<p>Grandparent message</p>', $result->message);
    }

    #[Test]
    public function itStopsInheritanceAtMaxDepth(): void
    {
        // Build a chain of 15 categories, all with inherit=true
        $rootWithMessage = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Root message</p>',
        ]);

        $current = $rootWithMessage;
        for ($i = 0; $i < 15; $i++) {
            $newCategory = $this->createCategory(
                customFields: ['mmd_empty_category_inherit' => true],
                parent: $current,
            );
            $current = $newCategory;
        }

        $result = $this->service->getMessage($current);

        // Should stop at max depth (10) and return empty, not the root message
        self::assertFalse($result->hasContent());
    }

    #[Test]
    public function itReturnsEmptyWhenParentIsNull(): void
    {
        $category = $this->createCategory(customFields: [
            'mmd_empty_category_inherit' => true,
        ]);

        $result = $this->service->getMessage($category);

        self::assertFalse($result->hasContent());
    }

    #[Test]
    public function itHandlesEmptyStringMessage(): void
    {
        $category = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '',
        ]);

        $result = $this->service->getMessage($category);

        self::assertFalse($result->hasMessage());
    }

    #[Test]
    public function itTrimsAndNormalizesWhitespaceFromCssClass(): void
    {
        $category = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Text</p>',
            'mmd_empty_category_css_class' => '  my-class   another  ',
        ]);

        $result = $this->service->getMessage($category);

        // Whitespace is normalized to single spaces
        self::assertSame('my-class another', $result->cssClass);
    }

    #[Test]
    public function itSanitizesCssClassToPreventXss(): void
    {
        $category = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Text</p>',
            'mmd_empty_category_css_class' => '" onclick="alert(\'XSS\')" data-foo="',
        ]);

        $result = $this->service->getMessage($category);

        // All dangerous characters should be removed
        self::assertSame('onclickalertXSS data-foo', $result->cssClass);
    }

    #[Test]
    public function itAllowsValidCssClassCharacters(): void
    {
        $category = $this->createCategory(customFields: [
            'mmd_empty_category_message' => '<p>Text</p>',
            'mmd_empty_category_css_class' => 'my-class_name test123 BEM__modifier--variant',
        ]);

        $result = $this->service->getMessage($category);

        self::assertSame('my-class_name test123 BEM__modifier--variant', $result->cssClass);
    }

    /**
     * @param array<string, mixed>|null $customFields
     */
    private function createCategory(
        ?array $customFields = [],
        ?CategoryEntity $parent = null,
        ?MediaEntity $media = null,
    ): CategoryEntity {
        $category = new CategoryEntity();
        $category->setId('cat-' . bin2hex(random_bytes(8)));
        $category->setCustomFields($customFields);

        if ($parent !== null) {
            $category->setParent($parent);
            $category->setParentId($parent->getId());
        }

        // Simulate media loaded via extension/custom field
        if ($media !== null) {
            $category->addExtension('mmdEmptyCategoryMedia', $media);
        }

        return $category;
    }
}

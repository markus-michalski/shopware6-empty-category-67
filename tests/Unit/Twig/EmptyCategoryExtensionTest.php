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

namespace Mmd\EmptyCategory\Tests\Unit\Twig;

use Mmd\EmptyCategory\Service\EmptyCategoryMessage;
use Mmd\EmptyCategory\Service\EmptyCategoryMessageService;
use Mmd\EmptyCategory\Twig\EmptyCategoryExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryEntity;
use Twig\TwigFunction;

#[CoversClass(EmptyCategoryExtension::class)]
class EmptyCategoryExtensionTest extends TestCase
{
    private EmptyCategoryExtension $extension;

    protected function setUp(): void
    {
        // Use real service since it's final and has no external dependencies
        $this->extension = new EmptyCategoryExtension(new EmptyCategoryMessageService());
    }

    #[Test]
    public function itRegistersTwigFunction(): void
    {
        $functions = $this->extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('mmd_empty_category_message', $functions[0]->getName());
    }

    #[Test]
    public function itReturnsEmptyMessageWhenCategoryIsNull(): void
    {
        $result = $this->extension->getMessage(null);

        self::assertInstanceOf(EmptyCategoryMessage::class, $result);
        self::assertFalse($result->hasContent());
    }

    #[Test]
    public function itReturnsMessageFromCategory(): void
    {
        $category = new CategoryEntity();
        $category->setId('cat-123');
        $category->setCustomFields([
            'mmd_empty_category_message' => '<p>Custom text</p>',
            'mmd_empty_category_css_class' => 'custom-class',
        ]);

        $result = $this->extension->getMessage($category);

        self::assertInstanceOf(EmptyCategoryMessage::class, $result);
        self::assertTrue($result->hasMessage());
        self::assertSame('<p>Custom text</p>', $result->message);
        self::assertSame('custom-class', $result->cssClass);
    }

    #[Test]
    public function itReturnsEmptyMessageWhenCategoryHasNoCustomFields(): void
    {
        $category = new CategoryEntity();
        $category->setId('cat-456');

        $result = $this->extension->getMessage($category);

        self::assertFalse($result->hasContent());
    }
}

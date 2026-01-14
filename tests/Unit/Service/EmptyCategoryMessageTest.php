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

namespace Mmd\EmptyCategory\Tests\Unit\Service;

use Mmd\EmptyCategory\Service\EmptyCategoryMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmptyCategoryMessage::class)]
class EmptyCategoryMessageTest extends TestCase
{
    #[Test]
    public function itCreatesEmptyMessageByDefault(): void
    {
        $message = new EmptyCategoryMessage();

        self::assertSame('', $message->message);
        self::assertSame('', $message->imageUrl);
        self::assertSame('', $message->cssClass);
    }

    #[Test]
    public function itCreatesMessageWithAllValues(): void
    {
        $message = new EmptyCategoryMessage(
            message: '<p>Custom text</p>',
            imageUrl: 'https://example.com/image.jpg',
            cssClass: 'my-class another-class',
        );

        self::assertSame('<p>Custom text</p>', $message->message);
        self::assertSame('https://example.com/image.jpg', $message->imageUrl);
        self::assertSame('my-class another-class', $message->cssClass);
    }

    #[Test]
    #[DataProvider('contentCheckProvider')]
    public function itChecksHasContent(string $message, string $imageUrl, bool $expected): void
    {
        $dto = new EmptyCategoryMessage(message: $message, imageUrl: $imageUrl);

        self::assertSame($expected, $dto->hasContent());
    }

    /**
     * @return iterable<string, array{message: string, imageUrl: string, expected: bool}>
     */
    public static function contentCheckProvider(): iterable
    {
        yield 'empty message and image' => [
            'message' => '',
            'imageUrl' => '',
            'expected' => false,
        ];

        yield 'message only' => [
            'message' => '<p>Text</p>',
            'imageUrl' => '',
            'expected' => true,
        ];

        yield 'image only' => [
            'message' => '',
            'imageUrl' => 'https://example.com/img.jpg',
            'expected' => true,
        ];

        yield 'both message and image' => [
            'message' => '<p>Text</p>',
            'imageUrl' => 'https://example.com/img.jpg',
            'expected' => true,
        ];
    }

    #[Test]
    public function itChecksHasMessage(): void
    {
        $empty = new EmptyCategoryMessage();
        $withMessage = new EmptyCategoryMessage(message: '<p>Hello</p>');

        self::assertFalse($empty->hasMessage());
        self::assertTrue($withMessage->hasMessage());
    }

    #[Test]
    public function itChecksHasImage(): void
    {
        $empty = new EmptyCategoryMessage();
        $withImage = new EmptyCategoryMessage(imageUrl: 'https://example.com/img.jpg');

        self::assertFalse($empty->hasImage());
        self::assertTrue($withImage->hasImage());
    }

    #[Test]
    #[DataProvider('imageSizeProvider')]
    public function itReturnsCorrectImageMaxWidth(string $size, string $expectedWidth): void
    {
        $message = new EmptyCategoryMessage(imageSize: $size);

        self::assertSame($expectedWidth, $message->getImageMaxWidth());
    }

    /**
     * @return iterable<string, array{size: string, expectedWidth: string}>
     */
    public static function imageSizeProvider(): iterable
    {
        yield 'small size' => [
            'size' => EmptyCategoryMessage::SIZE_SMALL,
            'expectedWidth' => '150px',
        ];

        yield 'medium size' => [
            'size' => EmptyCategoryMessage::SIZE_MEDIUM,
            'expectedWidth' => '250px',
        ];

        yield 'large size' => [
            'size' => EmptyCategoryMessage::SIZE_LARGE,
            'expectedWidth' => '400px',
        ];

        yield 'invalid size falls back to medium' => [
            'size' => 'invalid',
            'expectedWidth' => '250px',
        ];

        yield 'empty size falls back to medium' => [
            'size' => '',
            'expectedWidth' => '250px',
        ];
    }

    #[Test]
    #[DataProvider('imageAlignProvider')]
    public function itReturnsCorrectImageJustify(string $align, string $expectedJustify): void
    {
        $message = new EmptyCategoryMessage(imageAlign: $align);

        self::assertSame($expectedJustify, $message->getImageJustify());
    }

    /**
     * @return iterable<string, array{align: string, expectedJustify: string}>
     */
    public static function imageAlignProvider(): iterable
    {
        yield 'left alignment' => [
            'align' => EmptyCategoryMessage::ALIGN_LEFT,
            'expectedJustify' => 'flex-start',
        ];

        yield 'center alignment' => [
            'align' => EmptyCategoryMessage::ALIGN_CENTER,
            'expectedJustify' => 'center',
        ];

        yield 'right alignment' => [
            'align' => EmptyCategoryMessage::ALIGN_RIGHT,
            'expectedJustify' => 'flex-end',
        ];

        yield 'invalid alignment falls back to center' => [
            'align' => 'invalid',
            'expectedJustify' => 'center',
        ];
    }

    #[Test]
    #[DataProvider('textAlignProvider')]
    public function itReturnsCorrectTextAlignCss(string $align, string $expectedCss): void
    {
        $message = new EmptyCategoryMessage(textAlign: $align);

        self::assertSame($expectedCss, $message->getTextAlignCss());
    }

    /**
     * @return iterable<string, array{align: string, expectedCss: string}>
     */
    public static function textAlignProvider(): iterable
    {
        yield 'left alignment' => [
            'align' => EmptyCategoryMessage::ALIGN_LEFT,
            'expectedCss' => 'left',
        ];

        yield 'center alignment' => [
            'align' => EmptyCategoryMessage::ALIGN_CENTER,
            'expectedCss' => 'center',
        ];

        yield 'right alignment' => [
            'align' => EmptyCategoryMessage::ALIGN_RIGHT,
            'expectedCss' => 'right',
        ];

        yield 'empty alignment falls back to center' => [
            'align' => '',
            'expectedCss' => 'center',
        ];
    }

    #[Test]
    public function itCreatesMessageWithAllNewProperties(): void
    {
        $message = new EmptyCategoryMessage(
            message: '<p>Test</p>',
            imageUrl: 'https://example.com/img.jpg',
            cssClass: 'my-class',
            imageSize: EmptyCategoryMessage::SIZE_LARGE,
            imageAlign: EmptyCategoryMessage::ALIGN_LEFT,
            textAlign: EmptyCategoryMessage::ALIGN_RIGHT,
        );

        self::assertSame(EmptyCategoryMessage::SIZE_LARGE, $message->imageSize);
        self::assertSame(EmptyCategoryMessage::ALIGN_LEFT, $message->imageAlign);
        self::assertSame(EmptyCategoryMessage::ALIGN_RIGHT, $message->textAlign);
        self::assertSame('400px', $message->getImageMaxWidth());
        self::assertSame('flex-start', $message->getImageJustify());
        self::assertSame('right', $message->getTextAlignCss());
    }

    #[Test]
    public function itHasCorrectDefaultValues(): void
    {
        $message = new EmptyCategoryMessage();

        self::assertSame(EmptyCategoryMessage::SIZE_MEDIUM, $message->imageSize);
        self::assertSame(EmptyCategoryMessage::ALIGN_CENTER, $message->imageAlign);
        self::assertSame(EmptyCategoryMessage::ALIGN_CENTER, $message->textAlign);
    }
}

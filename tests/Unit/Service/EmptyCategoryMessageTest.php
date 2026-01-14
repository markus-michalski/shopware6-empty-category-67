<?php

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
}

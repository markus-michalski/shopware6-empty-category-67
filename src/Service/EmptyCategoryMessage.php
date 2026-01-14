<?php

declare(strict_types=1);

namespace Mmd\EmptyCategory\Service;

/**
 * Value object representing an empty category message configuration
 *
 * Immutable data transfer object containing all display data
 * for an empty category state.
 */
final readonly class EmptyCategoryMessage
{
    public const SIZE_SMALL = 'small';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_LARGE = 'large';

    public const ALIGN_LEFT = 'left';
    public const ALIGN_CENTER = 'center';
    public const ALIGN_RIGHT = 'right';

    public function __construct(
        public string $message = '',
        public string $imageUrl = '',
        public string $cssClass = '',
        public string $imageSize = self::SIZE_MEDIUM,
        public string $imageAlign = self::ALIGN_CENTER,
        public string $textAlign = self::ALIGN_CENTER,
    ) {
    }

    /**
     * Check if any custom content is defined
     */
    public function hasContent(): bool
    {
        return $this->message !== '' || $this->imageUrl !== '';
    }

    /**
     * Check if a custom message is defined
     */
    public function hasMessage(): bool
    {
        return $this->message !== '';
    }

    /**
     * Check if a custom image is defined
     */
    public function hasImage(): bool
    {
        return $this->imageUrl !== '';
    }

    /**
     * Get max-width value for image size
     */
    public function getImageMaxWidth(): string
    {
        return match ($this->imageSize) {
            self::SIZE_SMALL => '150px',
            self::SIZE_LARGE => '400px',
            default => '250px',
        };
    }

    /**
     * Get CSS justify-content value for image alignment
     */
    public function getImageJustify(): string
    {
        return match ($this->imageAlign) {
            self::ALIGN_LEFT => 'flex-start',
            self::ALIGN_RIGHT => 'flex-end',
            default => 'center',
        };
    }

    /**
     * Get CSS text-align value
     */
    public function getTextAlignCss(): string
    {
        return $this->textAlign ?: self::ALIGN_CENTER;
    }
}

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
    public function __construct(
        public string $message = '',
        public string $imageUrl = '',
        public string $cssClass = '',
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
}

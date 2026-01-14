<?php

declare(strict_types=1);

namespace Mmd\EmptyCategory\Service;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;

/**
 * Service for resolving empty category messages with inheritance support
 *
 * Handles the lookup of custom "no products" messages for categories,
 * including recursive parent category inheritance when enabled.
 */
final class EmptyCategoryMessageService
{
    private const MAX_INHERITANCE_DEPTH = 10;

    /**
     * Get the empty category message for a given category
     *
     * Resolves custom fields and optionally inherits from parent categories.
     */
    public function getMessage(CategoryEntity $category): EmptyCategoryMessage
    {
        return $this->resolveMessage($category, 0);
    }

    private function resolveMessage(CategoryEntity $category, int $depth): EmptyCategoryMessage
    {
        // Circuit breaker: prevent infinite recursion
        if ($depth >= self::MAX_INHERITANCE_DEPTH) {
            return new EmptyCategoryMessage();
        }

        $customFields = $category->getCustomFields() ?? [];

        // If category has its own message, use it
        if ($this->hasOwnMessage($customFields)) {
            return $this->buildMessage($category, $customFields);
        }

        // If inheritance is enabled and parent exists, recurse
        if ($this->shouldInherit($customFields) && $category->getParent() !== null) {
            return $this->resolveMessage($category->getParent(), $depth + 1);
        }

        // Fallback: empty message (template will use default)
        return new EmptyCategoryMessage();
    }

    /**
     * @param array<string, mixed> $customFields
     */
    private function hasOwnMessage(array $customFields): bool
    {
        $message = $customFields['mmd_empty_category_message'] ?? '';

        return is_string($message) && $message !== '';
    }

    /**
     * @param array<string, mixed> $customFields
     */
    private function shouldInherit(array $customFields): bool
    {
        return ($customFields['mmd_empty_category_inherit'] ?? false) === true;
    }

    /**
     * @param array<string, mixed> $customFields
     */
    private function buildMessage(CategoryEntity $category, array $customFields): EmptyCategoryMessage
    {
        $message = (string) ($customFields['mmd_empty_category_message'] ?? '');
        $cssClass = $this->sanitizeCssClass((string) ($customFields['mmd_empty_category_css_class'] ?? ''));
        $imageUrl = $this->resolveImageUrl($category, $customFields);

        return new EmptyCategoryMessage(
            message: $message,
            imageUrl: $imageUrl,
            cssClass: $cssClass,
        );
    }

    /**
     * Sanitize CSS class names to prevent XSS attacks
     *
     * Only allows alphanumeric characters, hyphens, underscores, and spaces.
     */
    private function sanitizeCssClass(string $cssClass): string
    {
        // Remove any character that is not allowed in CSS class names
        $sanitized = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $cssClass);

        // Normalize whitespace
        return trim((string) preg_replace('/\s+/', ' ', $sanitized ?? ''));
    }

    /**
     * @param array<string, mixed> $customFields
     */
    private function resolveImageUrl(CategoryEntity $category, array $customFields): string
    {
        $imageId = $customFields['mmd_empty_category_image'] ?? null;

        if (empty($imageId)) {
            return '';
        }

        // Try to get media from extension (loaded by subscriber)
        $media = $category->getExtension('mmdEmptyCategoryMedia');

        if ($media instanceof MediaEntity) {
            return $media->getUrl();
        }

        return '';
    }
}

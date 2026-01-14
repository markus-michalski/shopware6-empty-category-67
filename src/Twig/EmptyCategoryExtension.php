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

namespace Mmd\EmptyCategory\Twig;

use Mmd\EmptyCategory\Service\EmptyCategoryMessage;
use Mmd\EmptyCategory\Service\EmptyCategoryMessageService;
use Shopware\Core\Content\Category\CategoryEntity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension providing empty category message functionality
 *
 * Exposes the EmptyCategoryMessageService to Twig templates
 * via the mmd_empty_category_message() function.
 */
class EmptyCategoryExtension extends AbstractExtension
{
    public function __construct(
        private readonly EmptyCategoryMessageService $messageService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('mmd_empty_category_message', $this->getMessage(...)),
        ];
    }

    /**
     * Get empty category message for a category
     *
     * @param CategoryEntity|null $category The category to get the message for
     */
    public function getMessage(?CategoryEntity $category): EmptyCategoryMessage
    {
        if ($category === null) {
            return new EmptyCategoryMessage();
        }

        return $this->messageService->getMessage($category);
    }
}

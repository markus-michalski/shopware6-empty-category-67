<?php

declare(strict_types=1);

namespace Mmd\EmptyCategory\Subscriber;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to load parent categories and media for empty category messages
 *
 * Ensures that parent category chain and custom field media are available
 * when resolving inherited empty category messages.
 */
class CategoryPageSubscriber implements EventSubscriberInterface
{
    private const MAX_PARENT_DEPTH = 10;

    public function __construct(
        private readonly EntityRepository $categoryRepository,
        private readonly EntityRepository $mediaRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NavigationPageLoadedEvent::class => 'onNavigationPageLoaded',
        ];
    }

    public function onNavigationPageLoaded(NavigationPageLoadedEvent $event): void
    {
        $category = $event->getPage()->getHeader()->getNavigation()->getActive();

        if ($category === null) {
            return;
        }

        // Early exit: Skip if no relevant custom fields
        if (!$this->hasRelevantCustomFields($category)) {
            return;
        }

        $context = $event->getContext();

        // Load parent chain if inheritance is used
        $this->loadParentChain($category, $context);

        // Load media for all categories in the chain
        $this->loadMediaForChain($category, $context);
    }

    /**
     * Check if category has any relevant custom fields set
     */
    private function hasRelevantCustomFields(CategoryEntity $category): bool
    {
        $customFields = $category->getCustomFields() ?? [];

        // Has own message or image
        $hasMessage = !empty($customFields['mmd_empty_category_message']);
        $hasImage = !empty($customFields['mmd_empty_category_image']);
        $hasInherit = ($customFields['mmd_empty_category_inherit'] ?? false) === true;

        return $hasMessage || $hasImage || $hasInherit;
    }

    /**
     * Load parent categories using the category path (efficient single query)
     */
    private function loadParentChain(CategoryEntity $category, Context $context): void
    {
        $customFields = $category->getCustomFields() ?? [];
        $shouldInherit = ($customFields['mmd_empty_category_inherit'] ?? false) === true;

        if (!$shouldInherit) {
            return;
        }

        // Get all parent IDs from the category path
        $parentIds = $this->getParentIdsFromPath($category);

        if (empty($parentIds)) {
            return;
        }

        $criteria = new Criteria($parentIds);
        /** @var CategoryCollection $parents */
        $parents = $this->categoryRepository->search($criteria, $context)->getEntities();

        // Build parent chain
        $this->buildParentChain($category, $parents);
    }

    /**
     * Extract parent IDs from category path
     *
     * @return array<string>
     */
    private function getParentIdsFromPath(CategoryEntity $category): array
    {
        $path = $category->getPath();

        if ($path === null || $path === '') {
            return $category->getParentId() !== null ? [$category->getParentId()] : [];
        }

        // Path format: "|uuid1|uuid2|uuid3|"
        $ids = array_filter(explode('|', $path));

        // Limit to MAX_PARENT_DEPTH
        return array_slice($ids, -self::MAX_PARENT_DEPTH);
    }

    /**
     * Build the parent chain by linking categories
     */
    private function buildParentChain(CategoryEntity $category, CategoryCollection $parents): void
    {
        $current = $category;
        $depth = 0;

        while ($current->getParentId() !== null && $depth < self::MAX_PARENT_DEPTH) {
            $parent = $parents->get($current->getParentId());

            if ($parent === null) {
                break;
            }

            $current->setParent($parent);
            $current = $parent;
            $depth++;

            // Stop if this parent doesn't have inherit enabled
            $customFields = $parent->getCustomFields() ?? [];
            if (($customFields['mmd_empty_category_inherit'] ?? false) !== true) {
                break;
            }
        }
    }

    /**
     * Load media entities for all categories in the inheritance chain
     */
    private function loadMediaForChain(CategoryEntity $category, Context $context): void
    {
        $mediaIds = $this->collectMediaIds($category);

        if (empty($mediaIds)) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $mediaIds));

        /** @var MediaCollection $mediaEntities */
        $mediaEntities = $this->mediaRepository->search($criteria, $context)->getEntities();

        $this->assignMediaRecursively($category, $mediaEntities);
    }

    /**
     * Collect all media IDs from the category chain
     *
     * @return array<string>
     */
    private function collectMediaIds(CategoryEntity $category, int $depth = 0): array
    {
        if ($depth >= self::MAX_PARENT_DEPTH) {
            return [];
        }

        $ids = [];
        $customFields = $category->getCustomFields() ?? [];

        $mediaId = $customFields['mmd_empty_category_image'] ?? null;
        if (is_string($mediaId) && $mediaId !== '') {
            $ids[] = $mediaId;
        }

        if ($category->getParent() !== null) {
            $ids = array_merge($ids, $this->collectMediaIds($category->getParent(), $depth + 1));
        }

        return array_unique($ids);
    }

    /**
     * Assign media entities to their categories
     */
    private function assignMediaRecursively(CategoryEntity $category, MediaCollection $mediaEntities, int $depth = 0): void
    {
        if ($depth >= self::MAX_PARENT_DEPTH) {
            return;
        }

        $customFields = $category->getCustomFields() ?? [];
        $mediaId = $customFields['mmd_empty_category_image'] ?? null;

        if (is_string($mediaId) && $mediaId !== '') {
            $media = $mediaEntities->get($mediaId);
            if ($media !== null) {
                $category->addExtension('mmdEmptyCategoryMedia', $media);
            }
        }

        if ($category->getParent() !== null) {
            $this->assignMediaRecursively($category->getParent(), $mediaEntities, $depth + 1);
        }
    }
}

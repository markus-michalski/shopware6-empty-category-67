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

namespace Mmd\EmptyCategory\Tests\Unit\Subscriber;

use Mmd\EmptyCategory\Subscriber\CategoryPageSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Storefront\Page\Navigation\NavigationPage;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(CategoryPageSubscriber::class)]
class CategoryPageSubscriberTest extends TestCase
{
    /** @var EntityRepository<CategoryCollection>&\PHPUnit\Framework\MockObject\MockObject */
    private EntityRepository $categoryRepository;

    /** @var EntityRepository<MediaCollection>&\PHPUnit\Framework\MockObject\MockObject */
    private EntityRepository $mediaRepository;

    private CategoryPageSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(EntityRepository::class);
        $this->mediaRepository = $this->createMock(EntityRepository::class);

        $this->subscriber = new CategoryPageSubscriber(
            $this->categoryRepository,
            $this->mediaRepository,
        );
    }

    #[Test]
    public function itSubscribesToNavigationPageLoadedEvent(): void
    {
        $events = CategoryPageSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(NavigationPageLoadedEvent::class, $events);
        self::assertSame('onNavigationPageLoaded', $events[NavigationPageLoadedEvent::class]);
    }

    #[Test]
    public function itDoesNothingWhenCategoryIsNull(): void
    {
        $page = $this->createMock(NavigationPage::class);
        $page->method('getCategory')->willReturn(null);

        $event = $this->createNavigationEvent($page);

        $this->categoryRepository->expects(self::never())->method('search');
        $this->mediaRepository->expects(self::never())->method('search');

        $this->subscriber->onNavigationPageLoaded($event);
    }

    #[Test]
    public function itDoesNothingWhenNoRelevantCustomFields(): void
    {
        $category = $this->createCategory('cat-1', []);

        $page = $this->createMock(NavigationPage::class);
        $page->method('getCategory')->willReturn($category);

        $event = $this->createNavigationEvent($page);

        $this->categoryRepository->expects(self::never())->method('search');
        $this->mediaRepository->expects(self::never())->method('search');

        $this->subscriber->onNavigationPageLoaded($event);
    }

    #[Test]
    public function itDoesNothingWhenCustomFieldsAreNull(): void
    {
        $category = $this->createCategory('cat-1', null);

        $page = $this->createMock(NavigationPage::class);
        $page->method('getCategory')->willReturn($category);

        $event = $this->createNavigationEvent($page);

        $this->categoryRepository->expects(self::never())->method('search');
        $this->mediaRepository->expects(self::never())->method('search');

        $this->subscriber->onNavigationPageLoaded($event);
    }

    #[Test]
    public function itLoadsMediaWhenCategoryHasImage(): void
    {
        $mediaId = 'media-uuid-123';

        $media = new MediaEntity();
        $media->setId($mediaId);
        $media->assign(['url' => 'https://shop.example/media/image.png']);

        $category = $this->createCategory('cat-1', [
            'mmd_empty_category_message' => '<p>Text</p>',
            'mmd_empty_category_image' => $mediaId,
        ]);

        $page = $this->createMock(NavigationPage::class);
        $page->method('getCategory')->willReturn($category);

        $event = $this->createNavigationEvent($page);

        // No inheritance, so category repo should not be called
        $this->categoryRepository->expects(self::never())->method('search');

        // Media repo should be called to load the image
        $mediaCollection = new MediaCollection([$media]);
        $mediaSearchResult = $this->createSearchResult($mediaCollection);
        $this->mediaRepository->expects(self::once())
            ->method('search')
            ->willReturn($mediaSearchResult);

        $this->subscriber->onNavigationPageLoaded($event);

        // Media should be assigned as extension
        self::assertSame($media, $category->getExtension('mmdEmptyCategoryMedia'));
    }

    #[Test]
    public function itLoadsParentChainWhenInheritanceEnabled(): void
    {
        $parentId = 'parent-uuid-456';

        $parent = $this->createCategory($parentId, [
            'mmd_empty_category_message' => '<p>Parent message</p>',
        ]);

        $category = $this->createCategory('cat-1', [
            'mmd_empty_category_inherit' => true,
        ]);
        $category->setParentId($parentId);
        $category->setPath('|' . $parentId . '|');

        $page = $this->createMock(NavigationPage::class);
        $page->method('getCategory')->willReturn($category);

        $event = $this->createNavigationEvent($page);

        // Category repo should be called to load parents
        $parentCollection = new CategoryCollection([$parent]);
        $categorySearchResult = $this->createSearchResult($parentCollection);
        $this->categoryRepository->expects(self::once())
            ->method('search')
            ->willReturn($categorySearchResult);

        // No media to load
        $this->mediaRepository->expects(self::never())->method('search');

        $this->subscriber->onNavigationPageLoaded($event);

        // Parent should be linked
        self::assertSame($parent, $category->getParent());
    }

    #[Test]
    public function itDoesNotLoadParentsWhenInheritanceDisabled(): void
    {
        $category = $this->createCategory('cat-1', [
            'mmd_empty_category_message' => '<p>Own message</p>',
            'mmd_empty_category_inherit' => false,
        ]);
        $category->setParentId('parent-uuid');

        $page = $this->createMock(NavigationPage::class);
        $page->method('getCategory')->willReturn($category);

        $event = $this->createNavigationEvent($page);

        // Should not load parents
        $this->categoryRepository->expects(self::never())->method('search');

        // No image → no media loading
        $this->mediaRepository->expects(self::never())->method('search');

        $this->subscriber->onNavigationPageLoaded($event);
    }

    #[Test]
    public function itLoadsMediaForParentChain(): void
    {
        $parentId = 'parent-uuid-456';
        $parentMediaId = 'parent-media-789';

        $parentMedia = new MediaEntity();
        $parentMedia->setId($parentMediaId);
        $parentMedia->assign(['url' => 'https://shop.example/media/parent.png']);

        $parent = $this->createCategory($parentId, [
            'mmd_empty_category_message' => '<p>Parent</p>',
            'mmd_empty_category_image' => $parentMediaId,
        ]);

        $category = $this->createCategory('cat-1', [
            'mmd_empty_category_inherit' => true,
        ]);
        $category->setParentId($parentId);
        $category->setPath('|' . $parentId . '|');

        $page = $this->createMock(NavigationPage::class);
        $page->method('getCategory')->willReturn($category);

        $event = $this->createNavigationEvent($page);

        // Load parents
        $parentCollection = new CategoryCollection([$parent]);
        $this->categoryRepository->method('search')
            ->willReturn($this->createSearchResult($parentCollection));

        // Load media for parent
        $mediaCollection = new MediaCollection([$parentMedia]);
        $this->mediaRepository->method('search')
            ->willReturn($this->createSearchResult($mediaCollection));

        $this->subscriber->onNavigationPageLoaded($event);

        // Parent media should be assigned
        self::assertSame($parentMedia, $parent->getExtension('mmdEmptyCategoryMedia'));
    }

    /**
     * @param array<string, mixed>|null $customFields
     */
    private function createCategory(string $id, ?array $customFields): CategoryEntity
    {
        $category = new CategoryEntity();
        $category->setId($id);
        $category->setCustomFields($customFields);

        return $category;
    }

    private function createNavigationEvent(NavigationPage $page): NavigationPageLoadedEvent
    {
        $request = new Request();
        $context = $this->createMock(\Shopware\Core\System\SalesChannel\SalesChannelContext::class);
        $context->method('getContext')->willReturn(Context::createDefaultContext());

        $event = $this->createMock(NavigationPageLoadedEvent::class);
        $event->method('getPage')->willReturn($page);
        $event->method('getContext')->willReturn(Context::createDefaultContext());

        return $event;
    }

    /**
     * @template T of \Shopware\Core\Framework\DataAbstractionLayer\EntityCollection
     * @param T $collection
     * @return EntitySearchResult<T>
     */
    private function createSearchResult($collection): EntitySearchResult
    {
        /** @var EntitySearchResult<T> $result */
        $result = $this->createMock(EntitySearchResult::class);
        $result->method('getEntities')->willReturn($collection);
        $result->method('get')->willReturnCallback(
            fn (string $id) => $collection->get($id)
        );

        return $result;
    }
}

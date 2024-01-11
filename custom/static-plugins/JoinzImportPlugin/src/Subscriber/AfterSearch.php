<?php declare(strict_types=1);

namespace JoinzImportPlugin\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Suggest\SuggestPageLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class AfterSearch implements EventSubscriberInterface
{
    /** @var EntityRepositoryInterface */
    private $categoryRepository;

    /** @var EntityRepositoryInterface */
    private $salesChannelRepository;

    public function __construct(
        $categoryRepository,
        $salesChannelRepository
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->salesChannelRepository = $salesChannelRepository;
    }


    public static function getSubscribedEvents(): array
    {
        return[
            SuggestPageLoadedEvent::class => 'onAfterSearch'
        ];
    }

    public function onAfterSearch(SuggestPageLoadedEvent $event)
    {
        $suggestPage = $event->getPage();

        $request = $event->getRequest();

        $term = $request->get('search');

        $salesChannelId = $event->getContext()->getSource()->getSalesChannelId();

        // Getting salesChannelId to fetch site's entry points' ids
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $salesChannelId));
        $salesChannel = $this->salesChannelRepository->search($criteria, $event->getContext())->first();

        $footerCategoryId = $salesChannel->getFooterCategoryId();
        $navigationCategoryId = $salesChannel->getNavigationCategoryId();
        $serviceCategoryId = $salesChannel->getServiceCategoryId();

        $categoryCriteria = new Criteria();
        $categoryCriteria->addFilter(new ContainsFilter('name', $term));
        $categoryCriteria->addFilter(new EqualsFilter('active', 1));
        $results = $this->categoryRepository->search($categoryCriteria, $event->getContext());

        $categories = array();
        if($results != null){
            $cats = $results->getEntities();

            foreach($cats as $cat){
                // If category path contains an entry point id (or category is the entry point), add the category to the search results
                if(($footerCategoryId &&
                        (str_contains($cat->getPath(), $footerCategoryId) ||
                            $cat->getId() === $footerCategoryId)) ||
                    ($navigationCategoryId &&
                        (str_contains($cat->getPath(), $navigationCategoryId) ||
                            $cat->getId() === $navigationCategoryId)) ||
                    ($serviceCategoryId &&
                        (str_contains($cat->getPath(), $serviceCategoryId) ||
                            $cat->getId() === $serviceCategoryId))
                ){
                    $categories[] = array(
                        'id' => $cat->getId(),
                        'name' => $cat->getTranslated()['name'],
                        'entity' => $cat
                    );
                }

            }
        }

        $extensions = $suggestPage->getExtensions();
        $extensions['categories'] = $categories;
        $suggestPage->setExtensions($extensions);

    }
}

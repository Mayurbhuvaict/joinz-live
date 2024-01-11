<?php declare(strict_types=1);

namespace JoinzImportPlugin\Service;


use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddCategoriesToHome implements EventSubscriberInterface
{
    protected $categoryRepository;

    public function __construct(EntityRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NavigationPageLoadedEvent::class => 'addCategories'
        ];
    }

    public function addCategories(NavigationPageLoadedEvent $event): void
    {
        $topCategories = $this->getCategory( ['wijn_met_eigen_etiket', 'BORRELPLANK_GRAVEN', 'ELECTRONICS/USB-POWER_BANK/USB_STICKS','Duurzame_relatiegeschenken',
            'gadgets_met_logo', 'tassen', 'FOOD/CANDY-SWEETS'], $event);
        $besteCadeaus = $this->getCategory( ['Bluetooth-Speakers', 'Koptelefoons','Awards-Trofeeen',
            'Draadloze-Opladers', 'ELECTRONICS/USB-POWER_BANK/POWERBANKS', 'Woonaccessoires'], $event);
        $voorOpKantoor = $this->getCategory( ['Pennen', 'Notitieboekjes', 'Mappen', 'OFFICE/AGENDAS', 'Muismatten'], $event);

        $event->getPage()->addExtension('topCategories', $topCategories);
        $event->getPage()->addExtension('besteCadeaus', $besteCadeaus);
        $event->getPage()->addExtension('voorOpKantoor', $voorOpKantoor);
    }

    private function getCategory($criteriaFilter, $event) {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('tags.name', $criteriaFilter));
        $criteria->addAssociation('tags');
        $criteria->addAssociation('media');
        return $this->categoryRepository->search($criteria, $event->getContext());
    }
}


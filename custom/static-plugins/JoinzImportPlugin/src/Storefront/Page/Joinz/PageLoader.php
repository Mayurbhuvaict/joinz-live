<?php declare(strict_types=1);

namespace JoinzImportPlugin\Storefront\Page\Joinz;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class PageLoader
{
    private GenericPageLoaderInterface $genericPageLoader;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(GenericPageLoaderInterface $genericPageLoader, EventDispatcherInterface $eventDispatcher)
    {
        $this->genericPageLoader = $genericPageLoader;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function load(Request $request, SalesChannelContext $context): DefaultPage
    {
        $page = $this->genericPageLoader->load($request, $context);
        $page = DefaultPage::createFrom($page);

        $this->eventDispatcher->dispatch(
            new DefaultPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}

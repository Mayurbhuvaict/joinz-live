<?php declare(strict_types=1);

namespace JoinzImportPlugin\Storefront\Page\Joinz;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class DefaultPageLoadedEvent extends PageLoadedEvent
{
    protected DefaultPage $page;

    public function __construct(DefaultPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): DefaultPage
    {
        return $this->page;
    }
}

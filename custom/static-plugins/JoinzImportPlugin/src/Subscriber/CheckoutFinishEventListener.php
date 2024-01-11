<?php

declare(strict_types=1);

namespace JoinzImportPlugin\Subscriber;

use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutFinishEventListener implements EventSubscriberInterface
{


    public function __construct() {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinish',
        ];
    }

    public function onCheckoutFinish(CheckoutFinishPageLoadedEvent $event): void
    {
        $event->getRequest()->getSession()->remove('cart');

    }

}

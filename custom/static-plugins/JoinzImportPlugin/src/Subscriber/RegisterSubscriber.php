<?php

namespace JoinzImportPlugin\Subscriber;

use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegisterSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::MAPPING_REGISTER_CUSTOMER => 'addCustomField'
        ];
    }

    public function addCustomField(DataMappingEvent $event): bool
    {
        $inputData = $event->getInput();
        $outputData = $event->getOutput();

        $custom_field = $inputData->get('custom_company_name_text', false);
        //dd($inputData->get('custom_company_name_text', false));
        $outputData['customFields'] = array('custom_company_name_text' => $custom_field);

        $event->setOutput($outputData);

        return true;
    }
}

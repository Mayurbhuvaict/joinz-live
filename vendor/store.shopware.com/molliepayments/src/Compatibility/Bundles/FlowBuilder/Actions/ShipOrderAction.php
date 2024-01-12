<?php

namespace Kiener\MolliePayments\Compatibility\Bundles\FlowBuilder\Actions;


use Kiener\MolliePayments\Facade\MollieShipment;
use Kiener\MolliePayments\Facade\MollieShipmentInterface;
use Kiener\MolliePayments\Service\OrderService;
use Kiener\MolliePayments\Service\OrderServiceInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Flow\Dispatching\Action\FlowAction;
use Shopware\Core\Framework\Event\FlowEvent;
use Shopware\Core\Framework\Event\OrderAware;


class ShipOrderAction extends FlowAction
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderServiceInterface
     */
    private $orderService;

    /**
     * @var MollieShipmentInterface
     */
    private $shipmentFacade;


    /**
     * @param OrderServiceInterface $orderService
     * @param MollieShipmentInterface $shipment
     * @param LoggerInterface $logger
     */
    public function __construct(OrderServiceInterface $orderService, MollieShipmentInterface $shipment, LoggerInterface $logger)
    {
        $this->orderService = $orderService;
        $this->shipmentFacade = $shipment;
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'action.mollie.order.ship';
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            self::getName() => 'handle',
        ];
    }

    /**
     * @return string[]
     */
    public function requirements(): array
    {
        return [OrderAware::class];
    }

    /**
     * @param FlowEvent $event
     * @throws \Exception
     */
    public function handle(FlowEvent $event): void
    {
        $config = $event->getConfig();

        if (empty($config)) {
            return;
        }

        $baseEvent = $event->getEvent();

        if (!$baseEvent instanceof OrderAware) {
            return;
        }

        $this->shipOrder($baseEvent, $config);
    }

    /**
     * @param OrderAware $baseEvent
     * @param array<mixed> $config
     * @throws \Exception
     */
    private function shipOrder(OrderAware $baseEvent, array $config): void
    {
        $orderNumber = '';

        try {

            $orderId = $baseEvent->getOrderId();

            $order = $this->orderService->getOrder($orderId, $baseEvent->getContext());

            $orderNumber = $order->getOrderNumber();

            $this->logger->info('Starting Shipment through Flow Builder Action for order: ' . $orderNumber);

            $this->shipmentFacade->shipOrder(
                $order,
                '',
                '',
                '',
                $baseEvent->getContext()
            );

        } catch (\Exception $ex) {

            $this->logger->error('Error when shipping order with Flow Builder Action',
                [
                    'error' => $ex->getMessage(),
                    'order' => $orderNumber,
                ]);

            throw $ex;
        }
    }

}
<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Elegento\BoxNow\Observer\Sales;
use Elegento\BoxNow\Helper\RequestHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;



class OrderShipmentSaveAfter implements ObserverInterface
{

    protected $logger;
    /**
     * @var TrackFactory
     */
    protected $trackFactory;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * Execute observer
     *
     * @param LoggerInterface $logger
     * @param TrackFactory $trackFactory
     * @param RequestHelper $requestHelper
     */


    public function __construct(
        LoggerInterface $logger ,
        TrackFactory $trackFactory,
        RequestHelper $requestHelper
    )
    {
        $this->logger = $logger;
        $this->trackFactory = $trackFactory;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(
        Observer $observer
    ) {
        $shipment = $observer->getEvent()->getShipment();
        $shippingMethod = $shipment->getOrder()->getShippingMethod();

        if($shippingMethod == 'boxnow_boxnow') {

            //API request
            $trackingInfo = $this->requestHelper->generateTrackingAndVoucher($shipment);

            

            $this->logger->info(json_encode($trackingInfo));

            $data = array(
                'carrier_code' => 'boxnow_boxnow',
                'title' => 'BoxNow',
                'number' => $trackingInfo['parcels'][0]['id'],
            );
            $track = $this->trackFactory->create()->addData($data);
            $shipment->addTrack($track)->save();
        }
    }
}

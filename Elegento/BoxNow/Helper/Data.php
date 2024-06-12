<?php

declare(strict_types=1);

namespace Elegento\BoxNow\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\DataObject\Copy\Config;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    /**
     * @var Config
     */
    protected $fieldsetConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $convertOrder;

    protected $shipmentNotifier;

    protected $orderInterface;

    /**
     * Data constructor.
     *
     * @param Config $fieldsetConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $fieldsetConfig,
        LoggerInterface $logger,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Sales\Model\OrderFactory $orderInterface
    ) {
        $this->fieldsetConfig = $fieldsetConfig;
        $this->logger = $logger;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->orderInterface = $orderInterface;
    }

    /**
     * @param string $fieldset
     * @param string $root
     * @return array
     */
    public function getExtraCheckoutAddressFields(string $fieldset='extra_checkout_billing_address_fields', string $root='global'): array
    {
        $fields = $this->fieldsetConfig->getFieldset($fieldset, $root);

        $extraCheckoutFields = [];

        if (is_array($fields)) {
            foreach ($fields as $field => $fieldInfo) {
                $extraCheckoutFields[] = $field;
            }
        }

        return $extraCheckoutFields;
    }

    /**
     * @param $fromObject
     * @param $toObject
     * @param string $fieldset
     * @return mixed
     */
    public function transportFieldsFromExtensionAttributesToObject(
        $fromObject,
        $toObject,
        string $fieldset='extra_checkout_billing_address_fields'
    ) {
        foreach ($this->getExtraCheckoutAddressFields($fieldset) as $extraField) {
            $set = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $extraField)));
            $get = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $extraField)));

            $value = $fromObject->$get();
            try {
                $toObject->$set($value);
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }

        return $toObject;
    }

    public function getExistingBoxNowTracking($orderIncrementId){
        $order = $this->orderInterface->create()->loadByIncrementId($orderIncrementId);
        if($order->getShippingMethod() != "boxnow_boxnow" || !$order){
            return false;
        }
        $tracksCollection = $order->getTracksCollection();
        $trackNumbers = [];
        foreach ($tracksCollection->getItems() as $track) {

            $trackNumbers[] = $track->getTrackNumber();

        }
        return $trackNumbers;
    }

    public function createShipment($orderIncrementId){
        $order = $this->orderInterface->create()->loadByIncrementId($orderIncrementId);
        if($order->canShip()){
            $shipment = $this->convertOrder->toShipment($order);

            // Loop through order items
            foreach ($order->getAllItems() AS $orderItem) {
                // Check if order item has qty to ship or is virtual
                if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }

                $qtyShipped = $orderItem->getQtyToShip();

                // Create shipment item with qty
                $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

                // Add shipment item to shipment
                $shipment->addItem($shipmentItem);
            }

            // Register shipment
            $shipment->register();

            $shipment->getOrder()->setIsInProcess(true);

            try {
                // Save created shipment and order
                $shipment->save();
                $shipment->getOrder()->save();

                $shipment->save();

                // Send email
                //TODO reload shipment through factory in order to include tracking number from observer
                $this->shipmentNotifier->notify($shipment);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }
        }
    }

    public function formatParcelInfo($parcelInfo){

        $weight = explode(" ", $parcelInfo['data']['parcel']['weight'])[0];
        $weight = floatval($weight);

        $arrayToReturn = [
            'voucher_number' => $parcelInfo['data']['parcel']['displayName'],
            'weight' => $weight,
            'depot' => $parcelInfo['data']['depotId'],
            'lane' => $parcelInfo['data']['laneId'],
            'position' => $parcelInfo['data']['delivery']['destinationPublicId'],
            'created_at' => $parcelInfo['data']['createTime'],
        ];

        return $arrayToReturn;

    }
}

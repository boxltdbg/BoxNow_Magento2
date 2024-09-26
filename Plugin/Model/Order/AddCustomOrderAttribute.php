<?php

namespace Elegento\BoxNow\Plugin\Model\Order;

use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;

class AddCustomOrderAttribute
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * @param OrderExtensionFactory $extensionFactory
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        OrderExtensionFactory $extensionFactory,
        OrderFactory $orderFactory
    ) {
        $this->orderExtensionFactory = $extensionFactory;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Set "my_custom_order_attribute" to order data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     *
     * @return OrderSearchResultInterface
     */
    public function setMyCustomOrderAttributeData(OrderInterface $order)
    {
        if ($order instanceof \Magento\Sales\Model\Order) {
            $boxNowData = $order->getShippingAddress()->getBoxnowId();
        } else {
            $orderModel = $this->orderFactory->create();
            $orderModel->load($order->getId());
            $boxNowData = $orderModel->getShippingAddress()->getBoxnowId();
        }

        $extensionAttributes = $order->getExtensionAttributes();
        $orderExtensionAttributes = $extensionAttributes ? $extensionAttributes
            : $this->orderExtensionFactory->create();

        $orderExtensionAttributes->setBoxnowId($boxNowData);

        $order->setExtensionAttributes($orderExtensionAttributes);
    }

    /**
     * Add "my_custom_order_attribute" extension attribute to order data object
     * to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     *
     * @return OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $orderSearchResult
    ) {
        foreach ($orderSearchResult->getItems() as $order) {
            $this->setMyCustomOrderAttributeData($order);
        }
        return $orderSearchResult;
    }

    /**
     * Add "my_custom_order_attribute" extension attribute to order data object
     * to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $resultOrder
    ) {
        $this->setMyCustomOrderAttributeData($resultOrder);
        return $resultOrder;
    }
}
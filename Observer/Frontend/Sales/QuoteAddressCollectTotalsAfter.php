<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Elegento\BoxNow\Observer\Frontend\Sales;

use Magento\Framework\Event\Observer;
use \Psr\Log\LoggerInterface;

class QuoteAddressCollectTotalsAfter implements \Magento\Framework\Event\ObserverInterface
{

    protected $logger;

    /**
     * Execute observer
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(
        Observer $observer
    ) {
        $quote = $observer->getEvent()->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $boxNowJSON = $shippingAddress->getBoxnow_id();
        $shippingMethod = $shippingAddress->getShippingMethod();

        if(!$boxNowJSON   === 'boxnow_boxnow'){
            $boxNowJSON = $quote->getBillingAddress()->getBoxnow_id();
            if($boxNowJSON){
                $shippingAddress->setBoxnowId($quote->getBillingAddress()->getBoxnow_id());
            }
        }

        if ($boxNowJSON && $shippingMethod === 'boxnow_boxnow') {
            $boxNowData = json_decode($boxNowJSON);
            if ($boxNowData !== null) {
                $shippingAddress->setShippingDescription($boxNowData->boxnowLockerId . ':BoxNow' . ' - ' . $boxNowData->boxnowLockerAddressLine1 . ', ' . 'TK ' . $boxNowData->boxnowLockerPostalCode);
            }
        }
        $quote->setShippingAddress($shippingAddress);
        $quote->save();

    }
}

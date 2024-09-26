<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Elegento\BoxNow\Model\Payment;

class Boxnow extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "boxnow";
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    )
    {
        if ($quote->getShippingAddress()->getShippingMethod() === 'boxnow_boxnow') {
            return parent::isAvailable($quote);
        }

        return false;
    }
}

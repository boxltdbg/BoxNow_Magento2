<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Elegento\BoxNow\Plugin\Magento\Quote\Model;

use Magento\Framework\Exception\NoSuchEntityException;

class CouponManagement
{

    protected $quoteRepository;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    )
    {
        $this->quoteRepository = $quoteRepository;
    }

    public function aroundSet(
        \Magento\Quote\Model\CouponManagement $subject,
        \Closure $proceed,
                                              $cartId,
                                              $couponCode
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        $shippingAddress = $quote->getShippingAddress();
        $boxNowJSON = $shippingAddress->getBoxnow_id();
        if(!$boxNowJSON){
            $boxNowJSON = $quote->getBillingAddress()->getBoxnow_id();
        }

        try{
            $result = $proceed($cartId, $couponCode);
            if($boxNowJSON){
                $shippingAddress->setBoxnowId($boxNowJSON);
                $boxNowData = json_decode($boxNowJSON);
                if ($boxNowData !== null) {
                    $shippingAddress->setShippingDescription($boxNowData->boxnowLockerId . ':BoxNow' . ' - ' . $boxNowData->boxnowLockerAddressLine1 . ', ' . 'TK ' . $boxNowData->boxnowLockerPostalCode);
                }
            }

            $quote->setShippingAddress($shippingAddress);
            $quote->save();
            return $result;
        }
        catch(\Exception $e){
            if($boxNowJSON){
                $shippingAddress->setBoxnowId($boxNowJSON);
                $boxNowData = json_decode($boxNowJSON);
                if ($boxNowData !== null) {
                    $shippingAddress->setShippingDescription($boxNowData->boxnowLockerId . ':BoxNow' . ' - ' . $boxNowData->boxnowLockerAddressLine1 . ', ' . 'TK ' . $boxNowData->boxnowLockerPostalCode);
                }
            }

            $quote->setShippingAddress($shippingAddress);
            $quote->save();

            throw new NoSuchEntityException(__("The coupon code couldn't be applied. Verify the coupon code and try again"));
        }


    }
}

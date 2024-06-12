<?php

namespace Elegento\BoxNow\Plugin;


/**
 * Class MSPCashondeliveryPlugin
 * @package Elegento\StorePickup\Plugin
 */
class MSPCashondeliveryPlugin
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    public function __construct
    (
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param \Magento\Payment\Model\Method\AbstractMethod $subject
     * @param $result
     * @return bool
     */
    public function afterIsAvailable(\Magento\Payment\Model\Method\AbstractMethod $subject, $result)
    {
        $quoteID =  $this->_checkoutSession->getQuoteId();
        if($quoteID){
            $quote = $this->cartRepository->get($quoteID);
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        }
       else{
           $shippingMethod = $this->_checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();
       }
        if(stripos($shippingMethod, 'boxnow_boxnow') !== false) {
            return false;
        }

        return $result;
    }
}

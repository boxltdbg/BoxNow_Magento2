<?php

namespace Elegento\BoxNow\Plugin;


use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\CartRepositoryInterface;

class CashondeliveryPlugin
{

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Constructor
     *
     * @param Session $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct
    (
        Session $checkoutSession,
        CartRepositoryInterface $cartRepository
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param AbstractMethod $subject
     * @param $result
     * @return false|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterIsAvailable(AbstractMethod $subject, $result)
    {
        $quoteID =  $this->_checkoutSession->getQuoteId();
        if($quoteID){
            $quote = $this->cartRepository->get($quoteID);
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
        }
        else{
            $shippingMethod = $this->_checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();
        }
        if ($shippingMethod == 'boxnow_boxnow') {
            return false;
        }


        return $result;
    }
}

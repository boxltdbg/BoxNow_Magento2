<?php

namespace Elegento\BoxNow\Model\Payment;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class InstructionsConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    protected $paymentHelper;

    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->escaper = $escaper;
        $this->paymentHelper = $paymentHelper;
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig()
    {
        $config['payment']['instructions']['boxnow'] = $this
            ->scopeConfig
            ->getValue('payment/boxnow/instructions', ScopeInterface::SCOPE_STORE);;
        return $config;
    }

}

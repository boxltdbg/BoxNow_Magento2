<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Elegento\BoxNow\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;


class BoxNowUrl extends \Magento\Framework\View\Element\Template
{

    protected $scopeConfig;


    /**
     * Constructor
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context              $context,
        ScopeConfigInterface $scopeConfig,
        array                $data = []
    )
    {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getPartnerBoxNowUrl(): string
    {
        $partnerIdParam = $this->getPartnerIdParam();
        // version 3 URL
        $url = "https://widget-v4.boxnow.bg/iframe.html";
        return $url . "?$partnerIdParam" . "&gps=yes";
    }

    /**
     * @return string
     */
    protected function getPartnerIdParam(): string
    {
        $partnerId = $this->scopeConfig->getValue('carriers/boxnow/customer_details/partner_id');
        return "partnerId=$partnerId";

    }

    /**
     * @return bool
     */
    public function getAmastyConfigStatus(): bool
    {
        $amasty = $this->scopeConfig->getValue('amasty_checkout/general/enabled');
        if ($amasty) {
            return true;
        } else {
            return false;
        }
    }
}

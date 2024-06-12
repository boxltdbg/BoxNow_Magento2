<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Elegento\BoxNow\Model;

use Psr\Log\LoggerInterface;

class GenerateBoxnowVoucherManagement implements \Elegento\BoxNow\Api\GenerateBoxnowVoucherManagementInterface
{

    protected $helper;

    protected $requestHelper;

    public function __construct(
        \Elegento\BoxNow\Helper\Data $helper,
        \Elegento\BoxNow\Helper\RequestHelper $requestHelper,
        LoggerInterface $logger
    )
    {
        $this->helper = $helper;
        $this->requestHelper = $requestHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function postGenerateBoxnowVoucher($orderIncrementId)
    {

        $existingBoxNowTracking = $this->helper->getExistingBoxNowTracking($orderIncrementId);
        if($existingBoxNowTracking === false){
            $returnValue = ["error" => "order does not have box now shipping method"];
            return [$returnValue];
        }
        elseif(!empty($existingBoxNowTracking)){
            $parcelInfo = $this->requestHelper->getParcelInfo($existingBoxNowTracking[0]);
            $decoded = [(($this->helper->formatParcelInfo($parcelInfo)))];
            return $decoded;
        }
        else{
            try{
                $this->helper->createShipment($orderIncrementId);
                $existingBoxNowTracking = $this->helper->getExistingBoxNowTracking($orderIncrementId);
                $parcelInfo = $this->requestHelper->getParcelInfo($existingBoxNowTracking[0]);
                $decoded = [(($this->helper->formatParcelInfo($parcelInfo)))];
                return $decoded;
            }
            catch(\Exception $e){
                $returnValue = ["error" => $e->getMessage()];
                return [$returnValue];
            }
        }
    }
}

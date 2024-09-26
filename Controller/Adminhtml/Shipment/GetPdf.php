<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Elegento\BoxNow\Controller\Adminhtml\Shipment;

use Elegento\BoxNow\Helper\RequestHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class GetPdf implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Json
     */
    protected $serializer;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Http
     */
    protected $http;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;


    protected ScopeConfigInterface $scopeConfig;
    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Http $http
     * @param RequestHelper $requestHelper
     * @param \Magento\Framework\App\Request\Http $request
     * @param ScopeConfigInterface $scopeConfig
     */

    public function __construct(
        PageFactory $resultPageFactory,
        Json $json,
        LoggerInterface $logger,
        Http $http,
        RequestHelper $requestHelper,
        \Magento\Framework\App\Request\Http $request,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $json;
        $this->logger = $logger;
        $this->http = $http;
        $this->requestHelper = $requestHelper;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;

    }

    public function execute()
    {

        try {
            $trackingNumber = $this->request->getParam('trackingNumber');
            $getAuthToken = $this->requestHelper->obtainAccessToken();
            $base_url = $this->buildUrl('/parcel/');
            $file_url = $base_url . $trackingNumber . "/label.pdf";

            $headers = array(
                'Accept: application/pdf',
                "Authorization: Bearer $getAuthToken",
                'X-Requested-With: XMLHttpRequest'
            );

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $file_url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($curl);

            header( 'Content-Type: application/pdf' );
            header( "Content-Disposition:attachment;filename=BoxNow-$trackingNumber.pdf" );
            echo ( $result );

        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }

    }


    /**
     * @param string $response
     * @return Http|HttpInterface
     */
    public function jsonResponse(string $response = '')
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'application/json');
        return $this->http->setBody(
            $this->serializer->serialize($response)
        );
    }

    protected function buildUrl($endpoint): string
    {
        $base_url = $this
            ->scopeConfig
            ->getValue('carriers/boxnow/api_details/api_url', ScopeInterface::SCOPE_STORE);
        $suffix = '/ui/v1';

        return $base_url . $suffix . $endpoint;
    }
}

<?php
/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - generateTrackingAndVoucher()
 * - deliveryRequest()
 * - prepareOriginDetails()
 * - prepareDestinationArray()
 * - prepareParcel()
 * - prepareParcelUniqueId()
 * - computeShippingPackageWeight()
 * - computeTotalParcelValue()
 * - authenticate()
 * - getAuthenticationDetails()
 * - getData()
 * - postData()
 * - initializeCurl()
 * Classes list:
 * - RequestHelper extends AbstractHelper
 */

namespace Elegento\BoxNow\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use function YoastSEO_Vendor\GuzzleHttp\Promise\exception_for;

class RequestHelper extends AbstractHelper
{

    protected $trackFactory;
    protected $dir;
    protected $driverFile;
    protected $logger;
    protected $authToken;
    protected $scopeConfig;
    protected $orderRepository;

    /**
     * @param TrackFactory $trackFactory
     * @param DirectoryList $dir
     * @param File $driverFile
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(TrackFactory $trackFactory, DirectoryList $dir, File $driverFile, LoggerInterface $logger, ScopeConfigInterface $scopeConfig, \Magento\Sales\Api\OrderRepositoryInterface $orderRepository)
    {
        $this->trackFactory = $trackFactory;
        $this->dir = $dir;
        $this->driverFile = $driverFile;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
    }

    public function generateTrackingAndVoucher($shipment)
    {
        //Authenticate
        $authenticated = $this->authenticate();

        $delivery_request = $this->deliveryRequest($shipment);

        if (!$delivery_request) {
            return false;
        }

        return $delivery_request;
    }

    public function getParcelInfo($parcelId){
        //Authenticate
        $authenticated = $this->authenticate();

        $parcelInfo = $this->getData("/parcels/$parcelId/label", []);

        return $parcelInfo;
    }

    public function obtainAccessToken()
    {
        $authenticated = $this->authenticate();
        return $this->authToken;
    }

    /**
     * @param $shipment
     * @return false|mixed
     * @throws InputException
     */
    protected function deliveryRequest($shipment)
    {
        $order = $shipment->getOrder();
        $orderShipmentUniqueId = $this->prepareOrderShipmentUniqueId($shipment);
        $shippingAddress = $order->getShippingAddress();
        $destinationArray = $this->prepareDestinationArray($shippingAddress, $order);
        $originArray = $this->prepareOriginDetails();
//        $orderTotal = $this->computeTotalParcelValue($shipment);
        $orderGrandTotal = $this->formatWithDecimals($order->getGrandTotal(), 2);
        $parcel[] = $this->prepareParcel($shipment);
        $paymentMode = $this->checkPaymentMethod($order);
//        $fixedAmountPerShipment = $this
//            ->scopeConfig
//            ->getValue('carriers/boxnow/price', ScopeInterface::SCOPE_STORE);


        $body = array(
            'orderNumber' => $orderShipmentUniqueId,
            'invoiceValue' => $orderGrandTotal,
            'paymentMode' => $paymentMode,
            'amountToBeCollected' => $orderGrandTotal,
            'allowReturn' => true,
            'origin' => $originArray,
            'destination' => $destinationArray,
            'items' => $parcel,
        );

        $parcel = $this->postData('/delivery-requests', $body);

        if (!$parcel) {
            return false;
        }

        return $parcel;
    }

    /**
     * @return array
     */
    protected function prepareOriginDetails(): array
    {
        $contactNumber = $this
            ->scopeConfig
            ->getValue('carriers/boxnow/customer_details/contact_number', ScopeInterface::SCOPE_STORE);
        $contactEmail = $this
            ->scopeConfig
            ->getValue('carriers/boxnow/customer_details/contact_email', ScopeInterface::SCOPE_STORE);
        $contactName = $this
            ->scopeConfig
            ->getValue('carriers/boxnow/customer_details/contact_name', ScopeInterface::SCOPE_STORE);
        $locationId = $this
            ->scopeConfig
            ->getValue('carriers/boxnow/customer_details/locationId', ScopeInterface::SCOPE_STORE);

        return array(
            'contactNumber' => $contactNumber,
            'contactEmail' => $contactEmail,
            'contactName' => $contactName,
            'locationId' => $locationId,
        );

    }


    /**
     * @param $shippingAddress
     * @param $order
     * @return array
     * Customer Details
     */
    protected function prepareDestinationArray($shippingAddress, $order):
    array
    {
        $customerEmail = $shippingAddress->getEmail();

        $orderFromRepository = $this->orderRepository->get($order->getEntityId());

//        if($orderFromRepository->getExtensionAttributes() && $orderFromRepository->getExtensionAttributes()->getAmastyOrderAttributes()){
//            $amastyOrderAttributes = $orderFromRepository->getExtensionAttributes()->getAmastyOrderAttributes();
//            foreach($amastyOrderAttributes as $customAttribute){
//                $attrCode = $customAttribute->getAttributeCode();
//                if($attrCode == 'order_shipping_email'){ // case of telephone order
//                    $customerEmail = trim($customAttribute->getValue());
//                    break;
//                }
//            }
//        }

        $destContactNumber = $shippingAddress->getTelephone();
//        $destContactNumber = $this->checkIfMobile($destContactNumber);
        $destContactEmail = $customerEmail;
        $destContactName = $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname();
        $boxnowObject = json_decode($shippingAddress->getBoxnowId());
        $boxnowLockerId = $boxnowObject->boxnowLockerId;
//        $boxnowLockerId = '4';

        return array(
            'contactNumber' => $destContactNumber,
            'contactEmail' => $destContactEmail,
            'contactName' => $destContactName,
            'locationId' => $boxnowLockerId,
        );

    }

    protected function prepareParcel($shipment):
    array
    {
        $id = $shipment->getIncrementId();;
        $weight = $this->computeShippingPackageWeight($shipment);
        $totalValue = $this->computeTotalParcelValue($shipment);
        $name = $shipment->getIncrementId();


        return array(
            'id' => $id, //to id tha einai to shipping increment + order increment
            'weight' => $weight,
            'value' => $this->formatWithDecimals($totalValue, 2),
            'name' => $name
        );

    }

    /**
     * @param $shipment
     * @return string
     */
    protected function prepareOrderShipmentUniqueId($shipment):
    string
    {
        $shipmentIncrId = $shipment->getIncrementId();
        $orderIncrId = $shipment->getOrder()
            ->getIncrementId();

        return $orderIncrId . '-' . $shipmentIncrId;
    }

    /**
     * @param $shipment
     * @return float
     */
    protected function computeShippingPackageWeight($shipment):
    float
    {
        $packageWeight = 0;
        $itemsToShip = $shipment->getAllItems();

        foreach ($itemsToShip as $item) {
            $packageWeight += $item->getQty() * $item->getWeight();
        }

        return floatval($packageWeight);
    }

    /**
     * @param $shipment
     * @return float
     */
    protected function computeTotalParcelValue($shipment):
    float
    {
        $totalParcelValueInclTax = 0;
        $itemsToShip = $shipment->getAllItems();

        foreach ($itemsToShip as $item) {
            $totalParcelValueInclTax += $item->getQty() * $item->getPriceInclTax();
        }

        return (float)$totalParcelValueInclTax;
    }

    /**
     * @param $value
     * @param $decimals
     * @return string
     */
    protected function formatWithDecimals($value, $decimals): string
    {
        return number_format((float)$value, $decimals, '.', '');
    }

    /**
     * @param $quote
     * @return string
     */
    protected function checkPaymentMethod($quote): string
    {
        $paymentCode = $quote->getPayment()->getMethod();

        if ($paymentCode === 'boxnow') {
            return 'cod';
        }

        return 'prepaid';
    }

    /**
     * @param $tel
     *
     * @return false|string
     */
    protected function checkIfMobile($tel)
    {

        if (preg_match('/69[0-9]{8}$/', $tel)) {

            return str_pad($tel, 13, '+30', STR_PAD_LEFT);
        }

        return false;

    }

    /**
     * @return bool
     * @throws InputException
     */
    protected function authenticate():
    bool
    {

        $authDetails = $this->getAuthenticationDetails();

        $authenticated = $this->postData('/auth-sessions', $authDetails); //authenticate
        if (!$authenticated) {
            throw new InputException(__('Could not Authenticate'));
        }

        $this->authToken = $authenticated['access_token'];
        return true;
    }

    /**
     * @return array
     * @throws InputException
     */
    protected function getAuthenticationDetails(): array
    {
        $client_id = $this
            ->scopeConfig
            ->getValue('carriers/boxnow/api_details/client_id', ScopeInterface::SCOPE_STORE);
        $client_secret = $this
            ->scopeConfig
            ->getValue('carriers/boxnow/api_details/client_secret', ScopeInterface::SCOPE_STORE);

        if ($client_id == null || $client_secret == null) {
            throw new InputException(__('Client ID / Client Secret is incorrect - Please check your the details'));
        }

        return array(
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        );
    }

    /**
     * @param $endpoint
     * @return string
     */
    protected function buildUrl($endpoint): string
    {
        $base_url = $this
            ->scopeConfig
            ->getValue('carriers/boxnow/api_details/api_url', ScopeInterface::SCOPE_STORE);

        $suffix = '/api/v1';

        return $base_url . $suffix . $endpoint;
    }

    /**
     * @param string $endpoint
     * @param $data
     * @return false|mixed
     * @throws InputException
     */
    protected function getData(string $endpoint, $data)
    {
        //url build
        $url = $this->buildUrl($endpoint);

        //headers
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json'
        );

        //check if token exists
        if ($this->authToken) {
            $headers[] = "Authorization: Bearer $this->authToken";
        }

        if (is_array($data)) {
            $getParams = http_build_query($data);
            $url = $url . '?' . $getParams;
        }

        //initialize curl
        return $this->initializeCurl($url, $headers, 'get');
    }

    /**
     * @param string $endpoint
     * @param $data
     * @return false|mixed
     * @throws InputException
     */
    protected function postData(string $endpoint, $data)
    {
        //url build
        $url = $this->buildUrl($endpoint);

        //headers
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json'
        );

        //add token to header if it exists
        if ($this->authToken) {
            $headers[] = "Authorization: Bearer $this->authToken";
        }
        $payload = json_encode($data);

        //initialize curl
        return $this->initializeCurl($url, $headers, 'post', $payload);
    }

    /**
     * @param string $url
     * @param array $headers
     * @param $type
     * @param null $payload
     * @return false|mixed
     * @throws InputException
     */
    protected function initializeCurl(string $url, array $headers, $type, $payload = null)
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if ($type == 'get') curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        if ($type == 'post') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        $decoded_result = json_decode($result, true);

        if (isset($decoded_result['status'])) {
            $this->handleError($decoded_result);
        }

        //end curl
        curl_close($curl);
        return $decoded_result;
    }


    /**
     * @throws InputException
     */
    public function handleError($result)
    {
        $message = '';
        if (isset($result['code'])) {
            switch ($result['code']) {
                case 'P400':
                    $message = 'Invalid request data';
                    break;
                case 'P401':
                    $message = 'Invalid request origin location reference';
                    break;
                case 'P402':
                    $message = 'Invalid request destination location reference';
                    break;
                case 'P403':
                    $message = 'You are not allowed to use AnyAPM-SameAPM delivery';
                    break;
                case 'P404':
                    $message = 'Invalid import CSV.';
                    break;
                case 'P405':
                    $message = 'Invalid phone number';
                    break;
                case 'P406':
                    $message = 'Invalid compartment/parcel size.';
                    break;
                case 'P407':
                    $message = 'Invalid country code';
                    break;
                case 'P410':
                    $message = 'Order number conflict';
                    break;
                case 'P411':
                    $message = 'You are not eligible to use Cash-on-delivery payment type';
                    break;
                case 'P420':
                    $message = 'Parcel not ready for cancel';
                    break;
                case 'P430':
                    $message = 'Parcel not ready for AnyAPM confirmation';
                    break;
                default:
                    $message = 'An error has occurred - please contact admin for more information';
            }

            throw new InputException(__($message));
        }

    }
}

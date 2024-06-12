<?php

declare(strict_types=1);

namespace Elegento\BoxNow\Block\Checkout;

use Elegento\BoxNow\Helper\Data;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * Form element mapping
     *
     * @var array
     */
    private $formElementMap = [
        'text' => 'input',
        'hidden' => 'input',
        'boolean' => 'checkbox',
    ];
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * LayoutProcessor constructor.
     *
     * @param Data $helper
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        Data $helper,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->helper = $helper;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param array $jsLayout
     * @return array
     * @throws NoSuchEntityException
     */
    public function process($jsLayout): array
    {
        $jsLayout = $this->getShippingFormFields($jsLayout);
        return $this->getBillingFormFields($jsLayout);
    }

    /**
     * @param string $addressType
     * @return array
     */
    public function getAdditionalFields(string $addressType='shipping'): array
    {
        if ($addressType=='shipping') {
            return $this->helper->getExtraCheckoutAddressFields('extra_checkout_shipping_address_fields');
        }
        return  $this->helper->getExtraCheckoutAddressFields('extra_checkout_billing_address_fields');
    }

    /**
     * @param $result
     * @return array
     * @throws NoSuchEntityException
     */
    public function getShippingFormFields($result): array
    {
        if (isset($result['components']['checkout']['children']['steps']['children']
                ['shipping-step']['children']['shippingAddress']['children']
                ['shipping-address-fieldset'])
        ) {
            $shippingPostcodeFields = $this->getFields('shippingAddress.custom_attributes', 'shipping');

            $shippingFields = $result['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children'];

            if (isset($shippingFields['street'])) {
                unset($shippingFields['street']['children'][1]['validation']);
                unset($shippingFields['street']['children'][2]['validation']);
            }

            $shippingFields = array_replace_recursive($shippingFields, $shippingPostcodeFields);

            $result['components']['checkout']['children']['steps']['children']
            ['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children'] = $shippingFields;
        }

        return $result;
    }

    /**
     * @param $result
     * @return array
     * @throws NoSuchEntityException
     */
    public function getBillingFormFields($result): array
    {
        if (isset($result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']
            ['payments-list'])) {
            $paymentForms = $result['components']['checkout']['children']['steps']['children']
            ['billing-step']['children']['payment']['children']
            ['payments-list']['children'];

            foreach ($paymentForms as $paymentMethodForm => $paymentMethodValue) {
                $paymentMethodCode = str_replace('-form', '', $paymentMethodForm);

                if (!isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['payments-list']['children'][$paymentMethodCode . '-form'])) {
                    continue;
                }

                $billingFields = $result['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['payments-list']['children'][$paymentMethodCode . '-form']['children']['form-fields']['children'];

                $billingPostcodeFields = $this->getFields('billingAddress' . $paymentMethodCode . '.custom_attributes', 'billing');

                $billingFields = array_replace_recursive($billingFields, $billingPostcodeFields);

                $result['components']['checkout']['children']['steps']['children']
                ['billing-step']['children']['payment']['children']
                ['payments-list']['children'][$paymentMethodCode . '-form']['children']['form-fields']['children'] = $billingFields;
            }
        }

        return $result;
    }

    /**
     * @param $scope
     * @param $addressType
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFields($scope, $addressType): array
    {
        $fields = [];
        foreach ($this->getAdditionalFields($addressType) as $field) {
            $fields[$field] = $this->getField($field, $scope);
        }
        return $fields;
    }

    /**
     * @param $attributeCode
     * @param $scope
     * @return array
     * @throws NoSuchEntityException
     */
    public function getField($attributeCode, $scope): array
    {
        $attribute = $this->attributeRepository->get('customer_address', $attributeCode);

        $inputType = $attribute->getFrontendInput();
        if (isset($this->formElementMap[$inputType])) {
            $inputType = $this->formElementMap[$inputType];
        }

        return [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => $scope,
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/' . $inputType
            ],
            'dataScope' => $scope . '.' . $attributeCode,
            'sortOrder' => $attribute->getSortOrder(),
            'visible' => true,
            'provider' => 'checkoutProvider',
            'validation' => $attribute->getValidationRules(),
            'options' => $attribute->getOptions(),
            'label' => __($attribute->getStoreLabel())
        ];
    }
}

<?php
/**
 * Copyright © 2018 Codealist. All rights reserved.
 *
 * @category Class
 * @package  Codealist_OrderViewButton
 * @author   Codealist <info@codealist.com>
 * @license  See LICENSE.txt for license details.
 * @link     https://www.codealist.com/
 */

namespace Elegento\BoxNow\Plugin\Adminhtml;


use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Toolbar\Interceptor;
use Magento\Framework\View\Element\AbstractBlock;

class AddDownloadVoucherButton
{
    /**
     * @param Interceptor $subject
     * @param AbstractBlock $context
     * @param ButtonList $buttonList
     */
    public function beforePushButtons(
        Interceptor $subject,
        AbstractBlock $context,
        ButtonList $buttonList
    )
    {
        $fullActionName = $context->getRequest()->getFullActionName();

        //Generate a button for a specific shipment at shipment view page

        if ($fullActionName == 'adminhtml_order_shipment_view') {
            $shipment = $context->getShipment();
            if($shipment) {
                $shippingMethod = $shipment->getOrder()->getShippingMethod();

                if ( $shippingMethod !== 'boxnow_boxnow') return; //check if the shipment is a boxnow

                $tracksCollection = $shipment->getTracksCollection();
                foreach ($tracksCollection->getItems() as $track) {
                    $trackingNumber = $track->getTrackNumber();
                    $url = $context->getUrl( "boxnow/shipment/getpdf" ,['trackingNumber'=> $trackingNumber]);
                    $buttonList->add(
                        'downloadVoucherButton',
                        [ 'label'   => __( 'Download BoxNow Voucher'),
                          'onclick' => 'setLocation("' . $url . '")',
                          'class'   => 'reset'
                        ],
                        1,
                        0
                    );
                }
            }
        }

        //Generate Buttons for every shipment in order page



        if( $fullActionName == 'sales_order_view'){
             $order = $context->getOrder();
            if ($order) {
                $shippingMethod = $order->getShippingMethod();

                if($shippingMethod !== 'boxnow_boxnow') return;  //check if the shipment is a boxnow

                $tracksCollection = $order->getTracksCollection();
                $counter = 0;
                foreach ($tracksCollection->getItems() as $track) {
                    $counter++;
                    $trackingNumber = $track->getTrackNumber();
                    $url = $context->getUrl('boxnow/shipment/getpdf', ['trackingNumber' => $trackingNumber]);
                    $buttonList->add(
                        'DownloadVoucherButton' . $counter,
                        ['label' => __('Download BoxNow Voucher %1', $counter),
                            'onclick' => 'setLocation("' . $url . '")',
                            'class' => 'reset'
                        ],
                        1,
                        0
                    );
                }
            }
        }
    }
}

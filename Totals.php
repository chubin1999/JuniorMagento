<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Margifox\MyOrder\Block\Adminhtml;

use Magento\Framework\DataObject;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;
use Magento\Sales\Model\Order;

/**
 * Adminhtml sales totals block
 */
class Totals extends \Magento\Sales\Block\Order\Totals
{
    /**
     * Admin helper
     *
     * @var \Magento\Sales\Helper\Admin
     */
    protected $_adminHelper;

    /**
     * @var RuleCollection
     */
    protected $_ruleCollection;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = [],
        RuleCollection $ruleCollection
    ) {
        $this->_adminHelper = $adminHelper;
        parent::__construct($context, $registry, $data);
        $this->_ruleCollection = $ruleCollection;
    }

    /**
     * Format total value based on order currency
     *
     * @param \Magento\Framework\DataObject $total
     * @return string
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->_adminHelper->displayPrices($this->getOrder(), $total->getBaseValue(), $total->getValue());
        }
        return $total->getValue();
    }

    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        $rules = $this->_ruleCollection->create();
         /*echo "<pre>";*/
            /*print_r(*/$getDataRules = $rules->getData()/*)*/;
            /*foreach($getDataRules as $key => $dataR){
                if ($dataR['is_active']==1) {
                     echo "<pre>";
                     print_r($dataR);
                     print_r($dataR['name']);
                     print_r($dataR['discount_amount']);
                }
               
            }*/
            /*die($rules);*/
       
        $this->_totals = [];
        $order = $this->getSource();

        $this->_totals['subtotal'] = new DataObject(
            [
                'code' => 'subtotal',
                'value' => $order->getSubtotal(),
                'base_value' => $order->getBaseSubtotal(),
                'label' => __('Subtotal'),
            ]
        );

        /**
         * Add discount
         */
        if ((double)$order->getDiscountAmount() != 0) {
            if ($order->getDiscountDescription()) {
                $discountLabel = __('Discount (%1)', $order->getDiscountDescription());
            } else {
                $discountLabel = __('Discount');
            }
            $this->_totals['discount'] = new DataObject(
                [
                    'code' => 'discount',
                    'value' => $order->getDiscountAmount(),
                    'base_value' => $order->getBaseDiscountAmount(),
                    'label' => $discountLabel,
                ]
            );
        }

         /*Detail all*/
        $getDetailsDiscount =  $order->getDiscountDescription();
       /* print_r($getDetailsDiscount);*/
        if (strpos($getDetailsDiscount,',') && $getDetailsDiscount != null) {
            $pieces = explode(",", $getDetailsDiscount);
            /*unset($pieces[0]);*/
            $discountDetailLabel = null;
            $getSubtotalAfter = $order->getSubtotalInclTax();
            foreach ($pieces as $key => $value) {
                foreach($getDataRules as $key2 => $dataR){
                    if ($dataR['is_active'] == 1 && $dataR['name'] == $value or $dataR['name'] == $order->getCouponRuleName() ){
                        $discountDetailLabel.$value = __($value);
                        
                        $getPriceDetail = $dataR['discount_amount'];
                        if ($dataR['simple_action']== 'by_percent' ) {
                            $getPriceDetail = $getPriceDetail * $getSubtotalAfter / 100;
                        }
                        if ($value != $pieces[0] && $dataR['simple_action'] != 'ampromo_items') {
                            $getSubtotalAfter = $getSubtotalAfter - $getPriceDetail;
                        } 

                        $this->_totals['discount'.$value] = new \Magento\Framework\DataObject(
                            [
                                'code' => 'detail',
                                'field' => 'discount_detail_amount',
                                'value' => '-'.$getPriceDetail,
                                'label' => $dataR['name'],
                            ]
                        );
                    }     
                }
            }
        }

        /**
         * Add shipping
         */
        if (!$order->getIsVirtual()
            && ((double)$order->getShippingAmount()
            || $order->getShippingDescription())
        ) {
            $shippingLabel = __('Shipping & Handling');

            if ($order->getCouponCode() && !isset($this->_totals['discount'])) {
                $shippingLabel .= " ({$order->getCouponCode()})";
            }

            $this->_totals['shipping'] = new DataObject(
                [
                    'code' => 'shipping',
                    'value' => $order->getShippingAmount(),
                    'base_value' => $order->getBaseShippingAmount(),
                    'label' => $shippingLabel,
                ]
            );
        }

        $this->_totals['grand_total'] = new DataObject(
            [
                'code' => 'grand_total',
                'strong' => true,
                'value' => $order->getGrandTotal(),
                'base_value' => $order->getBaseGrandTotal(),
                'label' => __('Grand Total'),
                'area' => 'footer',
            ]
        );

        return $this;
    }
}


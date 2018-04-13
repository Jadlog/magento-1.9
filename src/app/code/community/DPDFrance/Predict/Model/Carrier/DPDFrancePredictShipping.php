<?php
/**
 * DPD France shipping module for Magento
 *
 * @category   DPDFrance
 * @package    DPDFrance_Shipping
 * @author     Antoine Lemoine, DPD France S.A.S. <ensavoirplus.ecommerce@dpd.fr>
 * @copyright  Copyright (c) 2008-10 Owebia (http://www.owebia.com/) , 2016 DPD France S.A.S., société par actions simplifiée, au capital de 18.500.000 euros, dont le siège social est situé 9 Rue Maurice Mallet - 92130 ISSY LES MOULINEAUX, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 444 420 830
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class DPDFrance_Predict_Model_Carrier_DPDFrancePredictShipping extends Mage_Shipping_Model_Carrier_Abstract {

    protected $_code = "dpdfrpredict";
    protected $_expenseConfig;
    /**
     * Owebia
     *
     * @var DPDFrance_Predict_Model_Owebia
     */
    protected $_owebiaCore;
    protected $_messages;

    protected function _addMessages($messages) {
        if (!is_array($messages)) $messages = array($messages);
        if (!is_array($this->_messages)) $this->_messages = $messages;
        else $this->_messages = array_merge($this->_messages, $messages);
    }

    public function getAllowedMethods() {
        $process = array();
        $config = $this->_getConfig();
        $allowed_methods = array();
        if (count($config)>0) {
            foreach ($config as $row){
                $allowed_methods[$row['*code']] = isset($row['label']['value']) ? $row['label']['value'] : 'No label';
            }
        }
       return $allowed_methods;
    }

    protected function _appendMethod($process, $row, $fees) {
        $method = Mage::getModel('shipping/rate_result_method')
            ->setCarrier($this->_code)
            ->setCarrierTitle($this->getConfigData('title'))
            ->setMethod($row['*code'])
            // ->setMethod('dpdfrpredict')
            ->setMethodTitle($this->getConfigData('methodname') . ' ' . $this->_getMethodText($process,$row,'label'))
            //->setMethodDescription($this->_getMethodText($process,$row,'description')) // can be enabled if necessary
            ->setPrice($fees)
            ->setCost($fees)
        ;

        $process['result']->append($method);
    }

    protected function _formatPrice($price) {
        return Mage::helper('core')->currency($price);
    }

    protected function _getCartTaxAmount($process) {
    $quote = Mage::getSingleton('checkout/session')->getQuote();
    $items_in_cart = $quote->getAllVisibleItems();
    // var_dump($quote, $items_in_cart);exit;
        // $items_in_cart = $process->getData('all_items');
        $tax_amount = 0;
        if (count($items_in_cart)>0)
        {
            foreach ($items_in_cart as $item)
            {
                $calc = Mage::getSingleton('tax/calculation');
                $rates = $calc->getRatesForAllProductTaxClasses($calc->getRateRequest());
                $vat_rate = isset($rates[$item->getProduct()->getTaxClassId()]) ? $rates[$item->getProduct()->getTaxClassId()] : 0;
                // var_dump($calc, $rates, $vat_rate);exit;
                if ($vat_rate > 0){
                    $vat_to_add = $item->getData('row_total')*$vat_rate/100;
                    // var_dump($vat_to_add);exit;
                } else {
                    $vat_to_add = $item->getData('tax_amount');
                }
                $tax_amount += $vat_to_add;
            }
        }
        // var_dump($tax_amount);exit;
        return $tax_amount;

    }

    /**
     * Get Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getConfig() {
        if (!isset($this->_expenseConfig)) {
            $this->_owebiaCore = Mage::getModel('dpdfrpredict/owebia', $this->getConfigData('expense'));
            $this->_expenseConfig = $this->_owebiaCore->getConfig();
            $this->_addMessages($this->_owebiaCore->getMessages());
        }
        return $this->_expenseConfig;
    }

    protected function _getCountryName($country_code) {
        return Mage::getModel('directory/country')->load($country_code)->getName();
    }

    protected function _getMethodText($process, $row, $property) {
        if (!isset($row[$property])) return '';

        return $this->_owebiaCore->evalInput($process,$row,$property,str_replace(
            array('{cart.weight}','{cart.price_including_tax}','{cart.price_excluding_tax}'),
            array(
                $process['data']['cart.weight'].$process['data']['cart.weight.unit'],
                $this->_formatPrice($process['data']['cart.price_including_tax']),
                $this->_formatPrice($process['data']['cart.price_excluding_tax'])
            ),
            $this->_owebiaCore->getRowProperty($row,$property)
        ));
    }

    protected function _process(&$process) {
        $store = Mage::app()->getStore($process['data']['store.id']);
        $timestamp = time();
        $customer_group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
        // Pour les commandes depuis Adminhtml
        if ($customer_group_id==0) {
            $customer_group_id2 = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getCustomerGroupId();
            if (isset($customer_group_id2)) $customer_group_id = $customer_group_id2;
        }

        $customer_group_code = Mage::getSingleton('customer/group')->load($customer_group_id)->getData('customer_group_code');
        $process['data'] = array_merge($process['data'],array(
            'customer.group.id' => $customer_group_id,
            'customer.group.code' => $customer_group_code,
            'shipto.country.name' => $this->_getCountryName($process['data']['shipto.country.code']),
            'shipto.postal.code' => Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getPostcode(),
            'origin.country.name' => $this->_getCountryName($process['data']['origin.country.code']),
            'cart.weight.unit' => 'kg',
            'store.code' => $store->getCode(),
            'store.name' => $store->getConfig('general/store_information/name'),
            'store.address' => $store->getConfig('general/store_information/address'),
            'store.phone' => $store->getConfig('general/store_information/phone'),
            'date.timestamp' => $timestamp,
            'date.year' => (int)date('Y',$timestamp),
            'date.month' => (int)date('m',$timestamp),
            'date.day' => (int)date('d',$timestamp),
            'date.hour' => (int)date('H',$timestamp),
            'date.minute' => (int)date('i',$timestamp),
            'date.second' => (int)date('s',$timestamp),
        ));

        // We don't need process certain products. If necessary, enable this block.
        foreach ($process['cart.items'] as $id => $item) {
            if ($item->getProduct()->getTypeId()!='configurable') {
                $parent_item_id = $item->getParentItemId();
                $process['products'][] = new DPDFrance_Predict_Magento_Product($item, isset($process['cart.items'][$parent_item_id]) ? $process['cart.items'][$parent_item_id] : null);
            }
        }


        if (!$process['data']['free_shipping']) {
            foreach ($process['cart.items'] as $item) {
                if ($item->getProduct() instanceof Mage_Catalog_Model_Product) {
                    if ($item->getFreeShipping()) $process['data']['free_shipping'] = true;
                    else {
                        $process['data']['free_shipping'] = false;
                        break;
                    }
                }
            }
        }

        $process['data']['cart.price_including_tax'] = $this->_getCartTaxAmount($process)+$process['data']['cart.price_excluding_tax'];
        // var_dump($process['data']['cart.price_excluding_tax'], $process['data']['cart.price_including_tax']);exit;

        $value_found = false;
        foreach ($this->_getConfig() as $row) {
            $result = $this->_owebiaCore->processRow($process, $row);
            $this->_addMessages($this->_owebiaCore->getMessages());
            if ($result->success) {
                if ($process['stop_to_first_match'] && $value_found) {
                    // Mage::log('DPD Predict shipping Method : WARNING, shipto is duplicated in BO configuration. Using first one, skipping others', Zend_Log::WARN);
                    break;
                }
                $value_found = true;
                $this->_appendMethod($process, $row, $result->result);
            }
        }

        if (!$value_found && $this->getConfigData('showerror')) $this->_setError($process, $this->getConfigData('specificerrmsg'));
    }

    protected function _setError(&$process, $message) {
        if (is_array($this->_messages))
            foreach ($this->_messages as $errMessage)
                if ($errMessage->type == 'over_weight') {
                    $message = 'Your shopping cart is too heavy for being shipped by DPD Predict';
                    break;
                }
        $error = Mage::getModel('shipping/rate_result_error')
            ->setCarrier($this->_code)
            ->setCarrierTitle($this->getConfigData('title'))
            ->setErrorMessage(Mage::helper('shipping')->__($message))
        ;
        $process['result'] = $error;
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request){
        try {
            $process = array(
                'result' => Mage::getModel('shipping/rate_result'),
                'cart.items' => array(),
                'products' => array(),
                'data' => array(
                    'cart.price_excluding_tax' => $request->_data['package_value_with_discount'],
                    'cart.price_including_tax' => $request->_data['package_value_with_discount'],
                    'cart.weight' => $request->_data['package_weight'],
                    'cart.weight.unit' => null,
                    'cart.quantity' => $request->_data['package_qty'],
                    'cart.coupon' => Mage::getSingleton('checkout/cart')->getQuote()->getCouponCode(),
                    'shipto.country.code' => $request->_data['dest_country_id'],
                    'shipto.country.name' => null,
                    'shipto.region.code' => $request->_data['dest_region_code'],
                    'shipto.postal.code' => $request->_data['dest_postcode'],
                    'origin.country.code' => $request->_data['country_id'],
                    'origin.country.name' => null,
                    'origin.region.code' => $request->_data['region_id'],
                    'origin.postal.code' => $request->_data['postcode'],
                    'customer.group.id' => null,
                    'customer.group.code' => null,
                    'free_shipping' => $request->getFreeShipping(),
                    'store.id' => $request->_data['store_id'],
                    'store.code' => null,
                    'store.name' => null,
                    'store.address' => null,
                    'store.phone' => null,
                    'date.timestamp' => null,
                    'date.year' => null,
                    'date.month' => null,
                    'date.day' => null,
                    'date.hour' => null,
                    'date.minute' => null,
                    'date.second' => null,
                ),
                'stop_to_first_match' => TRUE,
                'config' => null,
            );

            // We don't need process certain products. If necessary, enable this block.
            $items = $request->getAllItems();
            for ($i=0, $n=count($items); $i<$n; $i++) {
                $item = $items[$i];
                if ($item->getProduct() instanceof Mage_Catalog_Model_Product) $process['cart.items'][$item->getId()] = $item;
            }


            $this->_process($process);
            return $process['result'];
        }
        catch (Exception $e){
            Mage::logException($e);
        }

    }

    public function isTrackingAvailable() {
        return true;
    }

    public function getTrackingInfo($trackingNumber)
    {
        $trackingUrlByRef = 'http://www.dpd.fr/tracer_'.$trackingNumber;
        $trackingUrlByNb = 'http://www.dpd.fr/traces_'.$trackingNumber;
        $cargo = Mage::getStoreConfig('carriers/dpdfrpredict/cargo');
        $longueurcargo = strlen($cargo);
        $cargoutilise = substr($trackingNumber, -(int) $longueurcargo);

        if($cargoutilise == $cargo){
            $trackingStatus = Mage::getModel('shipping/tracking_result_status')
                ->setCarrier('dpdfrpredict')
                ->setCarrierTitle('DPD Predict')
                ->setTracking($trackingNumber)
                ->addData(array('status'=>'<script type="text/javascript">window.resizeTo(1280,800);</script><iframe src="'.$trackingUrlByRef.'" style="border: none;" width="1024" height="800"/>'));

            $trackingResult = Mage::getModel('shipping/tracking_result')
                ->append($trackingStatus);
            if($trackings = $trackingResult->getAllTrackings())
                return $trackings[0];
            return false;
        }else{
            $trackingStatus = Mage::getModel('shipping/tracking_result_status')
                ->setCarrier('dpdfrpredict')
                ->setCarrierTitle('DPD Predict')
                ->setTracking($trackingNumber)
                ->addData(array('status'=>'<script type="text/javascript">window.resizeTo(1280,800);</script><iframe src="'.$trackingUrlByNb.'" style="border: none;" width="1024" height="800"/>'));

            $trackingResult = Mage::getModel('shipping/tracking_result')
                ->append($trackingStatus);
            if($trackings = $trackingResult->getAllTrackings())
                return $trackings[0];
            return false;
        }
    }
}

class DPDFrance_Predict_Magento_Product implements DPDFrance_Predict_Os_Product {
    private $parent_cart_item;
    private $cart_item;
    private $cart_product;
    private $loaded_product;
    private $quantity;

    public function __construct($cart_item, $parent_cart_item) {
        $this->cart_item = $cart_item;
        $this->cart_product = $cart_item->getProduct();
        $this->parent_cart_item = $parent_cart_item;
        $this->quantity = isset($parent_cart_item) ? $parent_cart_item->getQty() : $cart_item->getQty();
    }

    public function getOption($option_name, $get_by_id=false) {
        $value = null;
        $product = $this->cart_product;
        foreach ($product->getOptions() as $option) {
            if ($option->getTitle()==$option_name) {
                $custom_option = $product->getCustomOption('option_'.$option->getId());
                if ($custom_option) {
                    $value = $custom_option->getValue();
                    if ($option->getType()=='drop_down' && !$get_by_id) {
                        $option_value = $option->getValueById($value);
                        if ($option_value) $value = $option_value->getTitle();
                    }
                }
                break;
            }
        }
        return $value;
    }

    public function getAttribute($attribute_name, $get_by_id=false) {
        $value = null;
        $product = $this->_getLoadedProduct();
        $attribute = $product->getResource()->getAttribute($attribute_name);
        if ($attribute) {
            $input_type = $attribute->getFrontend()->getInputType();
            switch ($input_type) {
                case 'select' :
                    $value = $get_by_id ? $product->getData($attribute_name) : $product->getAttributeText($attribute_name);
                    break;
                default :
                    $value = $product->getData($attribute_name);
                    break;
            }
        }
        return $value;
    }

    private function _getLoadedProduct() {
        if (!isset($this->loaded_product)) $this->loaded_product = Mage::getModel('catalog/product')->load($this->cart_product->getId());
        return $this->loaded_product;
    }

    public function getQuantity() {
        return $this->quantity;
    }

    public function getName() {
        return $this->cart_product->getName();
    }

    public function getSku() {
        return $this->cart_product->getSku();
    }

    public function getStockData($key) {
        $stock = $this->cart_product->getStockItem();
        switch ($key) {
            case 'is_in_stock':
                return (bool)$stock->getIsInStock();
            case 'quantity':
                $quantity = $stock->getQty();
                return $stock->getIsQtyDecimal() ? (float)$quantity : (int)$quantity;
        }
        return null;
    }
}

interface DPDFrance_Predict_Os_Product {
    public function getOption($option);
    public function getAttribute($attribute);
    public function getName();
    public function getSku();
    public function getQuantity();
    public function getStockData($key);
}

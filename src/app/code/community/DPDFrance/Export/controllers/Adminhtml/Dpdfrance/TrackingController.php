<?php
/**
 * DPD France shipping module for Magento
 *
 * @category   DPDFrance
 * @package    DPDFrance_Shipping
 * @author     Smile, Jibé, DPD France S.A.S. <ensavoirplus.ecommerce@dpd.fr>
 * @copyright  2016 DPD France S.A.S., société par actions simplifiée, au capital de 18.500.000 euros, dont le siège social est situé 9 Rue Maurice Mallet - 92130 ISSY LES MOULINEAUX, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 444 420 830
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class DPDFrance_Export_Adminhtml_DPDFrance_TrackingController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->setUsedModuleName('DPDFrance_Export');
    }

    /**
     * Check whether the admin user has access to this controller.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/dpdfrexport');
    }

    /**
     * Main action : show orders list
     */
    public function indexAction() {
        $this->loadLayout()
            ->_setActiveMenu('sales/dpdfrexport/tracking')
            ->_addContent($this->getLayout()->createBlock('dpdfrexport/tracking_orders'))
            ->renderLayout();
    }

    /* Method to go to previous page */
    function _goback()
    {
        header("Location: {$_SERVER['HTTP_REFERER']}");
        exit;
    }

    /**
     * Importation logic
     * @param string $fileName
     * @param string $trackingTitle
     */
    public function trackingAction() {

        /* get the orders */
        $orderIds = $this->getRequest()->getPost('order_ids');
        // var_dump($orderIds);exit;
        if (!empty($orderIds)) {

        foreach ($orderIds as $orderId) {

            /* get the order */
            $order = Mage::getModel('sales/order')->load($orderId);
            $incrementId = $order->getIncrementID();
            // var_dump($order, $incrementId);exit;

            /**
             * Try to load the order
             */
            $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
            $order2 = Mage::getModel('sales/order')->load($orderId);
            $orderuniqueid = $order->getId();
            // var_dump($order);exit;
            if (!$orderuniqueid) {
                // var_dump($order->getId());exit;
                $this->_getSession()->addError($this->__('La commande %s n\'existe pas', $orderId));
                continue;
                }

            /* type of delivery */
            $type = stristr($order->getShippingMethod(),'_', true);

            /* depot code and shipper code determination */
            switch ($type) {
                case 'dpdfrrelais' :
                    $depot_code = Mage::getStoreConfig('carriers/dpdfrrelais/depot', $order->store_id);
                    $shipper_code = Mage::getStoreConfig('carriers/dpdfrrelais/cargo', $order->store_id);
                    break;
                case 'dpdfrpredict' :
                    $depot_code = Mage::getStoreConfig('carriers/dpdfrpredict/depot', $order->store_id);
                    $shipper_code = Mage::getStoreConfig('carriers/dpdfrpredict/cargo', $order->store_id);
                    break;
                case 'dpdfrclassic' :
                    $depot_code = Mage::getStoreConfig('carriers/dpdfrclassic/depot', $order->store_id);
                    $shipper_code = Mage::getStoreConfig('carriers/dpdfrclassic/cargo', $order->store_id);
                    break;
            }

            /**
             * Try to create a shipment
             */

            $trackingNumber = $order->getIncrementID().'_'.$depot_code.$shipper_code;
            $trackingTitle = 'DPD France';
            $sendEmail = 1;
            $comment = 'Cher client, vous pouvez suivre l\'acheminement de votre colis par DPD en cliquant sur le lien ci-contre : '.'<a target="_blank" href="http://www.dpd.fr/tracer_'.$trackingNumber.'">Suivre ce colis DPD France</a>';
            $includeComment = 1;

            $shipmentId = $this->_createTracking($order, $trackingNumber, $trackingTitle, $sendEmail, $comment, $includeComment);
            if ($shipmentId != 0) {
                $this->_getSession()->addSuccess($this->__('Livraison %s créée pour la commande %s, statut mis à jour', $shipmentId, $incrementId, $trackingNumber));
            }

        }//foreach
     $this->_goback();
    }
}



    /**
     * Create new shipment for order
     * Inspired by Mage_Sales_Model_Order_Shipment_Api methods
     *
     * @param Mage_Sales_Model_Order $order (it should exist, no control is done into the method)
     * @param string $trackingNumber
     * @param string $trackingTitle
     * @param booleam $email
     * @param string $comment
     * @param boolean $includeComment
     * @return int : shipment real id if creation was ok, else 0
     */
    public function _createTracking($order, $trackingNumber, $trackingTitle, $email, $comment, $includeComment)
    {
        /**
         * Check shipment creation availability
         */
        if (!$order->canShip()) {
            $this->_getSession()->addError($this->__('La commande %s ne peut pas être expédiée, ou a déjà été expédiée.', $order->getRealOrderId()));
            return 0;
        }

        /**
         * Initialize the Mage_Sales_Model_Order_Shipment object
         */
        $convertor = Mage::getModel('sales/convert_order');
        $shipment = $convertor->toShipment($order);

        /**
         * Add the items to send
         */
        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->getQtyToShip()) {
                continue;
            }
            if ($orderItem->getIsVirtual()) {
                continue;
            }

            $item = $convertor->itemToShipmentItem($orderItem);
            $qty = $orderItem->getQtyToShip();
            $item->setQty($qty);

            $shipment->addItem($item);
        }//foreach

        $shipment->register();



        /**
         * Tracking number instanciation
         */
        $carrierCode = stristr($order->getShippingMethod(),'_', true);
        if(!$carrierCode) $carrierCode = 'custom';

        /* depot code and shipper code determination */
        switch ($carrierCode) {
            case 'dpdfrrelais' :
                $depot_code = Mage::getStoreConfig('carriers/dpdfrrelais/depot', $order->store_id);
                $shipper_code = Mage::getStoreConfig('carriers/dpdfrrelais/cargo', $order->store_id);
                break;
            case 'dpdfrpredict' :
                $depot_code = Mage::getStoreConfig('carriers/dpdfrpredict/depot', $order->store_id);
                $shipper_code = Mage::getStoreConfig('carriers/dpdfrpredict/cargo', $order->store_id);
                break;
            case 'dpdfrclassic' :
                $depot_code = Mage::getStoreConfig('carriers/dpdfrclassic/depot', $order->store_id);
                $shipper_code = Mage::getStoreConfig('carriers/dpdfrclassic/cargo', $order->store_id);
                break;
        }
        // Le trackingNumber est composé du n° de commande + le code agence + code cargo, intégré en un bloc dans l'URL
        $trackingNumber = $order->getIncrementID().'_'.$depot_code.$shipper_code;
        $trackingUrl = 'http://www.dpd.fr/tracer_'.$trackingNumber;

        $track = Mage::getModel('sales/order_shipment_track')
            ->setNumber($trackingNumber)
            ->setCarrierCode($carrierCode)
            ->setTitle($trackingTitle)
            ->setUrl($trackingUrl)
            ->setStatus( '<a target="_blank" href="'.$trackingUrl.'">'.__('Suivre ce colis DPD France').'</a>' );

        $shipment->addTrack($track);

        /**
         * Comment handling
         */
        $shipment->addComment($comment, $email && $includeComment);

        /**
         * Change order status to Processing
         */
        $shipment->getOrder()->setIsInProcess(true);

        /**
         * If e-mail, set as sent (must be done before shipment object saving)
         */
        if ($email) {
            $shipment->setEmailSent(true);
        }

        try {
            /**
             * Save the created shipment and the updated order
             */
            $shipment->save();
            $shipment->getOrder()->save();

            /**
             * Email sending
             */
            $shipment->sendEmail($email, ($includeComment ? $comment : ''));
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($this->__('Erreur pendant la création de l\'expédition %s : %s', $orderId, $e->getMessage()));
            return 0;
        }

        /**
         * Everything was ok : return Shipment real id
         */
        return $shipment->getIncrementId();

    }
}
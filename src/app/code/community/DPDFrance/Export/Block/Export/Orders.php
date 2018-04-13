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

class DPDFrance_Export_Block_Export_Orders extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'dpdfrexport';
        $this->_controller = 'export_orders';
        $this->_headerText = '<img src="'.Mage::getBaseUrl('media').'dpdfrance/admin/logo.png"/> <span style="color:#424143;">'.Mage::helper('dpdfrexport')->__('Orders management').'</span>';
        parent::__construct();
        $this->_removeButton('add');
    }
}
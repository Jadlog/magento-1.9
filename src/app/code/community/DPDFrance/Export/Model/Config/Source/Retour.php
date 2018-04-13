<?php
/**
 * DPD France shipping module for Magento
 *
 * @category   DPDFrance
 * @package    DPDFrance_Shipping
 * @author     DPD France S.A.S. <ensavoirplus.ecommerce@dpd.fr>
 * @copyright  2016 DPD France S.A.S., société par actions simplifiée, au capital de 18.500.000 euros, dont le siège social est situé 9 Rue Maurice Mallet - 92130 ISSY LES MOULINEAUX, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 444 420 830
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class DPDFrance_Export_Model_Config_Source_Retour
{
    public function toOptionArray()
    {
        return array(
            array('value'=>0, 'label'=>Mage::helper('dpdfrexport')->__('No returns')),
            array('value'=>3, 'label'=>Mage::helper('dpdfrexport')->__('On Demand')),
            array('value'=>4, 'label'=>Mage::helper('dpdfrexport')->__('Prepared'))
        );
    }
}

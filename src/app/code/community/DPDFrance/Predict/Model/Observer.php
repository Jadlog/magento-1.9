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

class DPDFrance_Predict_Model_Observer extends Mage_Core_Model_Abstract {

    protected function _construct() {}

    public function dpdfrpredictgsmAction() {

    $current = Mage::getSingleton('checkout/session')->getQuote(); // Récupération de la session checkout
    $modeliv = $current->getShippingAddress()->getShippingMethod(); // Récupération de la methode de livraison choisie par le client

        if(substr($modeliv,0,12) === 'dpdfrpredict') { // Modifier l'adresse uniquement si la methode de livraison sélectionnée est predict
            $input_tel = Mage::app()->getRequest()->getParam('gsm_dest'); // Saisie GSM utilisateur

            if($input_tel !='') { // Si un GSM est bien renseigné, l'enregistrer dans l'adresse client

                $gsm = str_replace(array(' ', '.', '-', ',', ';', '/', '\\', '(', ')'),'',$input_tel); // Nettoyage des symboles et espaces - donne 10 chiffres collés
                $gsm = str_replace('+33','0',$gsm); // Suppression d'un éventuel préfixe +33 en 0

                if (!(bool)preg_match('/^((\+33|0)[67])(?:[ _.-]?(\d{2})){4}$/', $gsm, $res)){ // Test sur la présence du 06 ou 07, de 10 chiffres
                // Si GSM incorrect, rien car géré par predict.js
                }else{
                    $address = $current->getShippingAddress(); // Recupération adresse de livraison
                    $billing = $current->getBillingAddress(); // Récupération adresse de facturation
                    $address->setTelephone($gsm);
                    $address->save();
                }
            }
        }
    }
}
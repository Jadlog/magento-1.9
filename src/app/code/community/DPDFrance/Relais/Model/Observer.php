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

class DPDFrance_Relais_Model_Observer extends Mage_Core_Model_Abstract {

    protected function _construct() {}

    public function changeshippingaddressAction() {

    $current = Mage::getSingleton('checkout/session')->getQuote(); // Récupération de la session checkout
    $modeliv = $current->getShippingAddress()->getShippingMethod(); // Récupération de la methode de livraison choisie par le client

    if(substr($modeliv,0,11) === 'dpdfrrelais') { // Modifier l'adresse uniquement si la methode de livraison sélectionnée est DPD Relais

        $relais_list = Mage::app()->getRequest()->getParam('relay-point'); // Récupération des données du PR choisi par le bouton radio, séparées par §§§, depuis RelaisController.php

        if($relais_list !='') { // Si un point relais est bien choisi, écraser l'adresse

            $shippingArray = explode("|||", $relais_list); // Séparation des données du PR dans des variables en majuscules (adresse, nom, code postal, ville)
                $adressePR = strtoupper($shippingArray[0]);
                $nomPR = strtoupper($shippingArray[1]);
                $codepostalPR = $shippingArray[2];
                $villePR = strtoupper($shippingArray[3]);

            $address = $current->getShippingAddress(); // Recupération adresse de livraison
            $billing = $current->getBillingAddress(); // Récupération adresse de facturation

            if (substr($codepostalPR,0,2) == 20) // Récupération de la région de destination : Tri spécial sur le code postal pour la Corse, permettant de séparer 2A et 2B
                {
                    $regioncode = substr($codepostalPR,0,3);
                    switch ($regioncode) {
                        case 200 :
                        case 201 :
                            $regioncode = '2A';
                            break;
                        case 202 :
                        case 206 :
                            $regioncode = '2B';
                            break;
                    }

                } else { // Si pas en Corse, récupérer les 2 premiers chiffres du CP pour trouver la région.
                    $regioncode = substr($codepostalPR,0,2);
                }

                Mage::app()->getLocale()->setLocaleCode('en_US');

                if (substr($regioncode,0,1) == 0) {
                    $region = Mage::getModel ('directory/region')->loadByCode($regioncode,$address->getCountryId());
                    if ($region->getDefaultName() == '') {
                        $region = Mage::getModel ('directory/region')->loadByCode(substr($regioncode,1,1),$address->getCountryId());
                    }
                }else{
                    $region = Mage::getModel ('directory/region')->loadByCode($regioncode,$address->getCountryId());
                }
                
                $regionname = $region->getDefaultName();
                $regionid = $region->getRegionId();
                Mage::getModel('core/log_adapter')->log("region name: $regionname");
                Mage::getModel('core/log_adapter')->log("region id: $regionid");

                $address->setRegion($regionname);
                $address->setPostcode($codepostalPR);
                $address->setStreet($adressePR);
                $address->setCity($villePR);
                $address->setCompany($nomPR);
                $address->save();
                $current->setShippingAddress($address);
                $current->save(); // Enregistrement nouvelle adresse

        } // Si pas de PR choisi : rien

    } // Cette partie de code permet de remplacer l'adresse de livraison par celle de facturation si la methode de livraison était DPD Relais et qu'elle a ensuite changé.

        if(substr($modeliv,0,11) !== 'dpdfrrelais') { // Si la methode de livraison sélectionnée n'est pas relaypoint

            $relais_list = Mage::app()->getRequest()->getParam('relay-point'); // Récupération des données du PR choisi par le bouton radio

            if($relais_list !='') { // Si un point relais était choisi, écraser l'adresse par celle de facturation

                $shippingAddress = $current->getShippingAddress(); // Recupération adresse de livraison
                $billingAddress = $current->getBillingAddress(); // Récupération adresse de facturation

                $shippingAddress->setData('customer_id', $billingAddress->getData('customer_id'));
                $shippingAddress->setData('customer_address_id', $billingAddress->getData('customer_address_id'));
                $shippingAddress->setData('email', $billingAddress->getData('email'));
                $shippingAddress->setData('prefix', $billingAddress->getData('prefix'));
                $shippingAddress->setData('firstname', $billingAddress->getData('firstname'));
                $shippingAddress->setData('middlename', $billingAddress->getData('middlename'));
                $shippingAddress->setData('lastname', $billingAddress->getData('lastname'));
                $shippingAddress->setData('suffix', $billingAddress->getData('suffix'));
                $shippingAddress->setData('company', $billingAddress->getData('company'));
                $shippingAddress->setData('street', $billingAddress->getData('street'));
                $shippingAddress->setData('city', $billingAddress->getData('city'));
                $shippingAddress->setData('region', $billingAddress->getData('region'));
                $shippingAddress->setData('region_id', $billingAddress->getData('region_id'));
                $shippingAddress->setData('postcode', $billingAddress->getData('postcode'));
                $shippingAddress->setData('country_id', $billingAddress->getData('country_id'));
                $shippingAddress->save();
                $current->setShippingAddress($shippingAddress);
                $current->save(); // Enregistrement nouvelle adresse

            } // Si pas de PR choisi et la méthode de livraison n'est pas relaypoint : rien
        }
    }
}
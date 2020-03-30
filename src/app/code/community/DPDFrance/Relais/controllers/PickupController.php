<?php

/**
 * DPD France shipping module for Magento
 *
 * @category DPDFrance
 * @package DPDFrance_Shipping
 * @author DPD France S.A.S. <ensavoirplus.ecommerce@dpd.fr>
 * @copyright 2016 DPD France S.A.S., société par actions simplifiée, au capital de 18.500.000 euros, dont le siège social est situé 9 Rue Maurice Mallet - 92130 ISSY LES MOULINEAUX, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 444 420 830
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class DPDFrance_Relais_PickupController extends Mage_Core_Controller_Front_Action
{

    public static function stripAccents($str)
    {
        $str = preg_replace('/[\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}]/u', 'A', $str);
        $str = preg_replace('/[\x{0105}\x{0104}\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}]/u', 'a', $str);
        $str = preg_replace('/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}]/u', 'C', $str);
        $str = preg_replace('/[\x{00E7}\x{0107}\x{0109}\x{010B}\x{010D}}]/u', 'c', $str);
        $str = preg_replace('/[\x{010E}\x{0110}]/u', 'D', $str);
        $str = preg_replace('/[\x{010F}\x{0111}]/u', 'd', $str);
        $str = preg_replace('/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}]/u', 'E', $str);
        $str = preg_replace('/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}]/u', 'e', $str);
        $str = preg_replace('/[\x{00CC}\x{00CD}\x{00CE}\x{00CF}\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}]/u', 'I', $str);
        $str = preg_replace('/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}]/u', 'i', $str);
        $str = preg_replace('/[\x{0142}\x{0141}\x{013E}\x{013A}]/u', 'l', $str);
        $str = preg_replace('/[\x{00F1}\x{0148}]/u', 'n', $str);
        $str = preg_replace('/[\x{00D2}\x{00D3}\x{00D4}\x{00D5}\x{00D6}\x{00D8}]/u', 'O', $str);
        $str = preg_replace('/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}]/u', 'o', $str);
        $str = preg_replace('/[\x{0159}\x{0155}]/u', 'r', $str);
        $str = preg_replace('/[\x{015B}\x{015A}\x{0161}]/u', 's', $str);
        $str = preg_replace('/[\x{00DF}]/u', 'ss', $str);
        $str = preg_replace('/[\x{0165}]/u', 't', $str);
        $str = preg_replace('/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{016E}\x{0170}\x{0172}]/u', 'U', $str);
        $str = preg_replace('/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{016F}\x{0171}\x{0173}]/u', 'u', $str);
        $str = preg_replace('/[\x{00FD}\x{00FF}]/u', 'y', $str);
        $str = preg_replace('/[\x{017C}\x{017A}\x{017B}\x{0179}\x{017E}]/u', 'z', $str);
        $str = preg_replace('/[\x{00C6}]/u', 'AE', $str);
        $str = preg_replace('/[\x{00E6}]/u', 'ae', $str);
        $str = preg_replace('/[\x{0152}]/u', 'OE', $str);
        $str = preg_replace('/[\x{0153}]/u', 'oe', $str);
        $str = preg_replace('/[\x{0022}\x{0025}\x{0026}\x{0027}\x{00A1}\x{00A2}\x{00A3}\x{00A4}\x{00A5}\x{00A6}\x{00A7}\x{00A8}\x{00AA}\x{00AB}\x{00AC}\x{00AD}\x{00AE}\x{00AF}\x{00B0}\x{00B1}\x{00B2}\x{00B3}\x{00B4}\x{00B5}\x{00B6}\x{00B7}\x{00B8}\x{00BA}\x{00BB}\x{00BC}\x{00BD}\x{00BE}\x{00BF}]/u', ' ', $str);
        return $str;
    }

    public function indexAction()
    {
        $address = $this->getRequest()->getParam('address');
        $address = mb_convert_encoding(urldecode($address), 'UTF-8');
        $address = self::stripAccents($address);

        $zipcode = $this->getRequest()->getParam('zipcode');
        $zipcode = trim(urldecode($zipcode));
        $zipcode = mb_convert_encoding($zipcode, 'UTF-8');

        $city = $this->getRequest()->getParam('city');
        $city = mb_convert_encoding(urldecode($city), 'UTF-8');
        $city = self::stripAccents($city);

        // esses campos sao adicionados no js
        $vlDec = $this->getRequest()->getParam('vlDec');
        $peso = $this->getRequest()->getParam('pesoCubadoTotal');
        $cpfOrCnpj = $this->getRequest()->getParam('cpfOrCnpj');

        Mage::getModel('core/log_adapter')->log("vlDec: $vlDec");
        Mage::getModel('core/log_adapter')->log("peso: $peso");
        Mage::getModel('core/log_adapter')->log("cpfOrCnpj: $cpfOrCnpj");

        if (empty($zipcode))
            echo '<ul class="messages"><li class="warnmsg"><ul><li>' . Mage::helper('dpdfrrelais')->__('The field Postal Code is mandatory!') . '</li></ul></li></ul>';
        else {
            if (empty($city))
                echo '<ul class="messages"><li class="warnmsg"><ul><li>' . Mage::helper('dpdfrrelais')->__('The field City is mandatory!') . '</li></ul></li></ul>';
            else {

                $serviceurl = Mage::getStoreConfig('dpdfrexport/embarcador/mypudoserviceurl');
                $firmid = Mage::getStoreConfig('dpdfrexport/embarcador/mypudofirmid');
                $key = Mage::getStoreConfig('dpdfrexport/embarcador/mypudokey');
                $zipcodeStore = Mage::getStoreConfig('dpdfrexport/embarcador/cep');
                $authorization = Mage::getStoreConfig('dpdfrexport/embarcador/authorization');

                // Mage::getModel('core/log_adapter')->log("serviceurl: $serviceurl");
                // Mage::getModel('core/log_adapter')->log("firmid: $firmid");
                // Mage::getModel('core/log_adapter')->log("key: $key");
                // Mage::getModel('core/log_adapter')->log("zipcodeStore: $zipcodeStore");

                // Paramètres d'appel au WS MyPudo
                $requestId = rand(1000, 10000000);
                $variables = array(
                    'serviceurl' => $serviceurl,
                    'carrier' => $firmid,
                    'key' => $key,
                    'address' => '', // a consulta eh feita pelos ceps,
                    'zipCode' => $zipcode,
                    'city' => $city,
                    'countrycode' => 'BRA',
                    'requestID' => $requestId,
                    'request_id' => $requestId,
                    'date_from' => '', // date('d/m/Y'),
                    'max_pudo_number' => '',
                    'max_distance_search' => '',
                    'weight' => '',
                    'category' => '',
                    'holiday_tolerant' => '',
                    'authorization' => $authorization
                );

                try {
                    ini_set("default_socket_timeout", 3);
                    $GetPudoList = Mage::helper('dpdfrexport')->getMYPUDOList($variables);
                    if ($GetPudoList == false){
                        echo '<ul class="messages"><li class="warnmsg"><ul><li>Resposta inválida do servidor de pontos de entrega</li></ul></li></ul>';
                        return;
                    }
                    // Mage::getModel('core/log_adapter')->log($GetPudoList);
                } catch (Exception $e) {
                    echo '<ul class="messages"><li class="warnmsg"><ul><li>' . Mage::helper('dpdfrrelais')->__('An error ocurred while fetching the DPD Pickup points. Please try again') . '</li></ul></li></ul>';
                    exit();
                }
                try {
                    $doc_xml = new SimpleXMLElement($GetPudoList);
                    // Mage::getModel('core/log_adapter')->log("doc_xml: $doc_xml");
                } catch (Exception $ex) {
                    // error_log($ex);
                }

                $quality = (int) $doc_xml->attributes()->quality;

                if ($doc_xml->xpath('ERROR')) {
                    echo '<ul class="messages"><li class="warnmsg"><ul><li>' . Mage::helper('dpdfrrelais')->__('An error ocurred while fetching the DPD Pickup points. Please try again') . '</li></ul></li></ul>';
                } else {
                    if ((int) $quality == 0) {
                        echo '<ul class="messages"><li class="warnmsg"><ul><li>' . Mage::helper('dpdfrrelais')->__('There are no DPD Pickup points for the selected adress. Please modify it.') . '</li></ul></li></ul>';
                    } else {
                        $allpudoitems = $doc_xml->xpath('PUDO_ITEMS');

                        foreach ($allpudoitems as $singlepudoitem) {
                            $result = $singlepudoitem->xpath('PUDO_ITEM');
                            $i = 0;
                            foreach ($result as $result2) {
                                $offset = $i;

                                $LATITUDE = (float) str_replace(",", ".", (string) $result2->LATITUDE);
                                $LONGITUDE = (float) str_replace(",", ".", (string) $result2->LONGITUDE);

                                $precoFrete = Mage::helper('dpdfrexport')->getPrecoFrete($zipcodeStore, $result2->ZIPCODE, $vlDec, $peso, $cpfOrCnpj, '40');

                                if ($precoFrete == false) {
                                    $precoFrete = 'ERRO';
                                }

                                // Mage::getModel('core/log_adapter')->log("result2: ".print_r($result2, true));

                                $html = '
                                <div>
                                    <span class="dpdfrrelais_logo"><img src="' . Mage::getBaseUrl('media') . 'dpdfrance/front/relais/pointrelais.png" alt="-"/></span>
                                    <span class="s1"><strong>' . self::stripAccents($result2->NAME) . '</strong><br/>' . self::stripAccents($result2->ADDRESS1) . ' <br/> ' . $result2->ZIPCODE . ' ' . self::stripAccents($result2->CITY) . '</span>
                                    <span class="s2">' . sprintf("%01.2f", (int) $result2->DISTANCE / 1000) . ' km  </span>
                                    <span class="s3">R$' . (string) $precoFrete . '</span>
                                    <span class="s3"><a href="#!" onClick="openDialog(\'relaydetail' . $offset . '\',\'map_canvas' . $offset . '\',\'' . $LATITUDE . '\',\'' . $LONGITUDE . '\',\'' . Mage::getBaseUrl('media') . '\')">' . Mage::helper('dpdfrrelais')->__('More details') . '</a></span>
                                    <input type="radio" id="relay-point' . $offset . '" name="relay-point" class="dpdfrrelais_radio" value="' . self::stripAccents($result2->ADDRESS1) . '  ' . self::stripAccents($result2->ADDRESS2) . '|||' . self::stripAccents($result2->NAME) . '  ' . (string) $result2->PUDO_ID . '|||' . $result2->ZIPCODE . '|||' . self::stripAccents($result2->CITY) . '">
                                    <label class="dpdfrrelais_button_ici" for="relay-point' . $offset . '"><span><span></span></span><b>ICI</b></label>
                                </div>
                                ';

                                $days = array(
                                    1 => 'monday',
                                    2 => 'tuesday',
                                    3 => 'wednesday',
                                    4 => 'thursday',
                                    5 => 'friday',
                                    6 => 'saturday',
                                    7 => 'sunday'
                                );
                                $point = array();
                                $item = (array) $result2;

                                if (count($item['OPENING_HOURS_ITEMS']->OPENING_HOURS_ITEM) > 0)
                                    foreach ($item['OPENING_HOURS_ITEMS']->OPENING_HOURS_ITEM as $k => $oh_item) {
                                        $oh_item = (array) $oh_item;
                                        $point[$days[$oh_item['DAY_ID']]][] = $oh_item['START_TM'] . ' - ' . $oh_item['END_TM'];
                                    }

                                if (empty($point['monday'])) {
                                    $h1 = Mage::helper('dpdfrrelais')->__('Closed');
                                } else {
                                    if (empty($point['monday'][1])) {
                                        $h1 = $point['monday'][0];
                                    } else {
                                        $h1 = $point['monday'][0] . ' & ' . $point['monday'][1];
                                    }
                                }

                                if (empty($point['tuesday'])) {
                                    $h2 = Mage::helper('dpdfrrelais')->__('Closed');
                                } else {
                                    if (empty($point['tuesday'][1])) {
                                        $h2 = $point['tuesday'][0];
                                    } else {
                                        $h2 = $point['tuesday'][0] . ' & ' . $point['tuesday'][1];
                                    }
                                }

                                if (empty($point['wednesday'])) {
                                    $h3 = Mage::helper('dpdfrrelais')->__('Closed');
                                } else {
                                    if (empty($point['wednesday'][1])) {
                                        $h3 = $point['wednesday'][0];
                                    } else {
                                        $h3 = $point['wednesday'][0] . ' & ' . $point['wednesday'][1];
                                    }
                                }

                                if (empty($point['thursday'])) {
                                    $h4 = Mage::helper('dpdfrrelais')->__('Closed');
                                } else {
                                    if (empty($point['thursday'][1])) {
                                        $h4 = $point['thursday'][0];
                                    } else {
                                        $h4 = $point['thursday'][0] . ' & ' . $point['thursday'][1];
                                    }
                                }

                                if (empty($point['friday'])) {
                                    $h5 = Mage::helper('dpdfrrelais')->__('Closed');
                                } else {
                                    if (empty($point['friday'][1])) {
                                        $h5 = $point['friday'][0];
                                    } else {
                                        $h5 = $point['friday'][0] . ' & ' . $point['friday'][1];
                                    }
                                }

                                if (empty($point['saturday'])) {
                                    $h6 = Mage::helper('dpdfrrelais')->__('Closed');
                                } else {
                                    if (empty($point['saturday'][1])) {
                                        $h6 = $point['saturday'][0];
                                    } else {
                                        $h6 = $point['saturday'][0] . ' & ' . $point['saturday'][1];
                                    }
                                }

                                if (empty($point['sunday'])) {
                                    $h7 = Mage::helper('dpdfrrelais')->__('Closed');
                                } else {
                                    if (empty($point['sunday'][1])) {
                                        $h7 = $point['sunday'][0];
                                    } else {
                                        $h7 = $point['sunday'][0] . ' & ' . $point['sunday'][1];
                                    }
                                }

                                $html .= '<div id="relaydetail' . $offset . '" style="display:none;">
                                            <div class="dpdfrrelaisboxcarto" id="map_canvas' . $offset . '" style="width:100%;"></div>
                                            <div id="dpdfrrelaisboxbottom" class="dpdfrrelaisboxbottom">
                                                <div id="dpdfrrelaisboxadresse" class="dpdfrrelaisboxadresse">
                                                    <div class="dpdfrrelaisboxadresseheader"><img src="' . Mage::getBaseUrl('media') . 'dpdfrance/front/relais/pointrelais.png" alt="-" width="32" height="32"/><br/>' . Mage::helper('dpdfrrelais')->__('Your DPD Pickup point') . '</div>
                                                    <strong>' . $result2->NAME . '</strong></br>
                                                    ' . $result2->ADDRESS1 . '</br>';
                                if (! empty($result2->ADDRESS2))
                                    $html .= $result2->ADDRESS2 . '</br>';
                                $html .= $result2->ZIPCODE . '  ' . $result2->CITY . '<br/>';
                                if (! empty($result2->LOCAL_HINT))
                                    $html .= '<p>' . Mage::helper('dpdfrrelais')->__('info') . '  :  ' . $result2->LOCAL_HINT . '</p>';
                                $html .= '</div>';

                                $html .= '<div class="dpdfrrelaisboxhoraires">
                                            <div class="dpdfrrelaisboxhorairesheader"><img src="' . Mage::getBaseUrl('media') . 'dpdfrance/front/relais/horaires.png" alt="-" width="32" height="32"/><br/>' . Mage::helper('dpdfrrelais')->__('Opening hours') . '</div>
                                            <p><span>' . Mage::helper('dpdfrrelais')->__('Monday') . ' : </span>' . $h1 . '</p>
                                            <p><span>' . Mage::helper('dpdfrrelais')->__('Tuesday') . ' : </span>' . $h2 . '</p>
                                            <p><span>' . Mage::helper('dpdfrrelais')->__('Wednesday') . ' : </span>' . $h3 . '</p>
                                            <p><span>' . Mage::helper('dpdfrrelais')->__('Thursday') . ' : </span>' . $h4 . '</p>
                                            <p><span>' . Mage::helper('dpdfrrelais')->__('Friday') . ' : </span>' . $h5 . '</p>
                                            <p><span>' . Mage::helper('dpdfrrelais')->__('Saturday') . ' : </span>' . $h6 . '</p>
                                            <p><span>' . Mage::helper('dpdfrrelais')->__('Sunday') . ' : </span>' . $h7 . '</p>
                                        </div>';

                                $html .= '<div class="dpdfrrelaisboxinfos">
                                            <div class="dpdfrrelaisboxinfosheader"><img src="' . Mage::getBaseUrl('media') . 'dpdfrance/front/relais/info.png" alt="-" width="32" height="32"/><br/>' . Mage::helper('dpdfrrelais')->__('More info') . '</div>
                                            <div><h5>' . Mage::helper('dpdfrrelais')->__('Distance in KM') . '  :  </h5><strong>' . sprintf("%01.2f", $result2->DISTANCE / 1000) . ' km </strong></div>
                                            <div><h5>' . Mage::helper('dpdfrrelais')->__('DPD Pickup ID#') . '  :  </h5><strong>' . (string) $result2->PUDO_ID . '</strong></div>';
                                if (count($result2->HOLIDAY_ITEMS->HOLIDAY_ITEM) > 0) {
                                    foreach ($result2->HOLIDAY_ITEMS->HOLIDAY_ITEM as $holiday_item) {
                                        $holiday_item = (array) $holiday_item;
                                        $html .= '<div><img id="dpdfrrelaisboxinfoswarning" src="' . Mage::getBaseUrl('media') . 'dpdfrance/front/relais/warning.png" alt="-" width="16" height="16"/> <h4>' . Mage::helper('dpdfrrelais')->__('Closing period') . '  : </h4> ' . $holiday_item['START_DTM'] . ' - ' . $holiday_item['END_DTM'] . '</div>';
                                    }
                                }
                                $html .= '</div>';

                                $html .= '</div></div>'; // dpdfrrelaisboxbottom et relaydetail
                                echo $html;

                                $i ++;
                                $hd1 = $hd2 = $hd3 = $hd4 = $hd5 = $hd6 = $hd7 = $h1 = $h2 = $h3 = $h4 = $h5 = $h6 = $h7 = null;
                                if ($i == 5) { // Nombre de points relais à afficher - max 10
                                    exit();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

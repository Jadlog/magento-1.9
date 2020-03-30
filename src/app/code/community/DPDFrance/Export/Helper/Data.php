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
class DPDFrance_Export_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function isTeste() {
        // TAREFA - TESTE mudar para false depois dos testes
        return false;
    }

    public function getValueStrOrNullNoEmpty($entity, $variable_name)
    {
        $value = $entity->getData($variable_name);
        if ($value === null) {
            return null;
        }
        if (strlen(trim($value)) == 0) {
            return null;
        }
        return trim($value);
    }

    public function isEmpty($var)
    {
        if ($var === null) {
            return true;
        }
        if (strlen(trim($var)) == 0) {
            return true;
        }
        return false;
    }

    public function getValueIntOrNullNoEmpty($product, $variable_name)
    {
        $value = $product->getData($variable_name);
        if ($value === null) {
            return null;
        }
        $intValue = intval($value);
        if ($intValue <= 0) {
            return null;
        }
        return $intValue;
    }

    public function getPrecoFrete($zipcodeFrom, $zipcodeTo, $vlDec, $peso, $cpfOrCnpj, $modalidade)
    {
        $zipcodeFrom = str_replace('-', '', $zipcodeFrom);
        $zipcodeTo = str_replace('-', '', $zipcodeTo);
        Mage::getModel('core/log_adapter')->log("getPrecoFrete($zipcodeFrom, $zipcodeTo, $vlDec, $peso, $cpfOrCnpj, $modalidade)");
        $serviceurlfrete = Mage::getStoreConfig('dpdfrexport/embarcador/serviceurlfrete');
        $passwordfrete = Mage::getStoreConfig('dpdfrexport/embarcador/passwordfrete');
        $usuariofrete = Mage::getStoreConfig('dpdfrexport/embarcador/usuariofrete');
        $url = $serviceurlfrete . '&vModalidade='.$modalidade.'&Password=' . $passwordfrete . '&vSeguro=N&vVlDec=' . $vlDec . '&vVlColeta=&vCepOrig=' . str_replace('-', '', $zipcodeFrom) . '&vCepDest=' . str_replace('-', '', $zipcodeTo) . '&vPeso=' . $peso . '&vFrap=N&vEntrega=D&vCnpj=' . $usuariofrete;
        Mage::getModel('core/log_adapter')->log("frete url: " . $url);

        $str = '';


        if ($this->isTeste()) {
            $str = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><soapenv:Body><valorarResponse xmlns=""><ns1:valorarReturn xmlns:ns1="http://jadlogEdiws">&lt;?xml version=&quot;1.0&quot; encoding=&quot;utf-8&quot; ?&gt;
&lt;string xmlns=&quot;http://www.jadlog.com.br/JadlogEdiWs/services&quot;&gt;
   &lt;Jadlog_Valor_Frete&gt;
       &lt;versao&gt;1.0&lt;/versao&gt;
       &lt;Retorno&gt;16,72&lt;/Retorno&gt;
       &lt;Mensagem&gt;Valor do Frete&lt;/Mensagem&gt;
   &lt;/Jadlog_Valor_Frete&gt;
&lt;/string&gt;</ns1:valorarReturn></valorarResponse></soapenv:Body></soapenv:Envelope>';
        } else {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            $str = curl_exec($curl);
            curl_close($curl);
        }
        Mage::getModel('core/log_adapter')->log("frete resposta: " . $str);
        $sl1 = '&lt;Retorno&gt;';
        $sl1l = strlen($sl1);
        $pos1 = strrpos($str, $sl1);
        $pos2 = strrpos($str, '&lt;/Retorno&gt;');
        if ($pos1 == false || $pos2 == false) {
            return false;
        }
        $preco = substr($str, $pos1 + $sl1l, $pos2 - $pos1 - $sl1l);
        return $preco;
    }

    public function getMYPUDOList($params)
    {
        $queryparams = 'carrier=' . $params['carrier'] . '&key=' . $params['key'] . '&zipcode=' . $params['zipCode'] . '&city=' . str_replace(' ', '', strtoupper($params['city'])) . '&countrycode=' . $params['countrycode'] . '&requestID=' . $params['requestID'] . '&address=' . $params['address'] . '&date_from=' . $params['date_from'] . '&max_pudo_number=' . $params['max_pudo_number'] . '&max_distance_search=' . $params['max_distance_search'] . '&weight=' . $params['weight'] . '&category=' . $params['category'] . '&holiday_tolerant=' . $params['holiday_toleran'];

        //$service_url = $params['serviceurl'] . '?' . $queryparams;
        $service_url = $params['serviceurl'] . '/' . preg_replace("/\D/", "", $params['zipCode']);
        Mage::getModel('core/log_adapter')->log("getMYPUDOList url: $service_url");
//         Mage::getModel('core/log_adapter')->log("getMYPUDOList body: $body");

        if ($this->isTeste()) {
            $result = '<RESPONSE quality="1">
  <REQUEST_ID>1234</REQUEST_ID>
  <PUDO_ITEMS>
    <PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10355</PUDO_ID>
      <ORDER>1</ORDER>
      <DISTANCE>997</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>POWER GAMES</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>2021</STREETNUM>
      <ADDRESS1>AV SILVA BUENO</ADDRESS1>
      <ADDRESS2>
      </ADDRESS2>
      <ADDRESS3>IPIRANGA</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>04208-052</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.60075320</LONGITUDE>
      <LATITUDE>-23.59634680</LATITUDE>
      <HANDICAPES>False</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=2021+AV+SILVA+BUENO&amp;codePostal=04208-052&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=POWER+GAMES&amp;horairesOuvertureLundi=09%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureMardi=09%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureMercredi=09%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureJeudi=09%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureVendredi=09%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureSamedi=09%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=113307&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.59634680&amp;lng=-46.60075320&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
<PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10404</PUDO_ID>
      <ORDER>2</ORDER>
      <DISTANCE>4208</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>OFICINA DE COSTURA</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>1023</STREETNUM>
      <ADDRESS1>RUA NATAL </ADDRESS1>
      <ADDRESS2>LOJA 16</ADDRESS2>
      <ADDRESS3>VILA BERTIOGA</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>03186-030</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.58239420</LONGITUDE>
      <LATITUDE>-23.56822860</LATITUDE>
      <HANDICAPES>False</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=1023+RUA+NATAL&amp;codePostal=03186-030&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=OFICINA+DE+COSTURA+&amp;horairesOuvertureLundi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureMardi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureMercredi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureJeudi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureVendredi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureSamedi=08%3a00-12%3a0012%3a00-15%3a00&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=113557&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.56822860&amp;lng=-46.58239420&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>15:00</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
      </PUDO_ITEMS>
</RESPONSE>';
    $unn='<PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10400</PUDO_ID>
      <ORDER>3</ORDER>
      <DISTANCE>4967</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>ITAFRAN SOUND</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>539</STREETNUM>
      <ADDRESS1>AV, DOUTOR HUGO BEOLCHI</ADDRESS1>
      <ADDRESS2>
      </ADDRESS2>
      <ADDRESS3>VILA GUARANI</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>04310-030</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.64116370</LONGITUDE>
      <LATITUDE>-23.63068430</LATITUDE>
      <HANDICAPES>True</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=539+AV%2c+DOUTOR+HUGO+BEOLCHI&amp;codePostal=04310-030&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=ITAFRAN+SOUND&amp;horairesOuvertureLundi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureMardi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureMercredi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureJeudi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureVendredi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureSamedi=08%3a00-12%3a00&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=113553&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.63068430&amp;lng=-46.64116370&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
    <PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10200</PUDO_ID>
      <ORDER>4</ORDER>
      <DISTANCE>9937</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>SANCOMP</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>223</STREETNUM>
      <ADDRESS1>AV.  GENERAL PEDRO LEON SCHNEIDER</ADDRESS1>
      <ADDRESS2>
      </ADDRESS2>
      <ADDRESS3>SANTANA</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>02012-100</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.62860720</LONGITUDE>
      <LATITUDE>-23.50811010</LATITUDE>
      <HANDICAPES>False</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=223+AV.++GENERAL+PEDRO+LEON+SCHNEIDER&amp;codePostal=02012-100&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=SANCOMP&amp;horairesOuvertureLundi=08%3a30-12%3a0012%3a00-17%3a30&amp;horairesOuvertureMardi=08%3a30-12%3a0012%3a00-17%3a30&amp;horairesOuvertureMercredi=08%3a30-12%3a0012%3a00-17%3a30&amp;horairesOuvertureJeudi=08%3a30-12%3a0012%3a00-17%3a30&amp;horairesOuvertureVendredi=08%3a30-12%3a0012%3a00-17%3a30&amp;horairesOuvertureSamedi=08%3a30-12%3a30&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=112153&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.50811010&amp;lng=-46.62860720&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:30</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
    <PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10263</PUDO_ID>
      <ORDER>5</ORDER>
      <DISTANCE>10767</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>ALLPE</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>102</STREETNUM>
      <ADDRESS1>RUA ALFREDO PUJOL</ADDRESS1>
      <ADDRESS2>
      </ADDRESS2>
      <ADDRESS3>SANTANA</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>02017-000</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.62680320</LONGITUDE>
      <LATITUDE>-23.500259</LATITUDE>
      <HANDICAPES>False</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=102+RUA+ALFREDO+PUJOL&amp;codePostal=02017-000&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=ALLPE&amp;horairesOuvertureLundi=10%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureMardi=10%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureMercredi=10%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureJeudi=10%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureVendredi=10%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureSamedi=10%3a00-13%3a00&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=112701&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.500259&amp;lng=-46.62680320&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>13:00</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
    <PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10199</PUDO_ID>
      <ORDER>6</ORDER>
      <DISTANCE>11282</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>NOSSA CARA KIDS</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>259</STREETNUM>
      <ADDRESS1>AVENIDA BARUEL</ADDRESS1>
      <ADDRESS2>LETRA A</ADDRESS2>
      <ADDRESS3>VILA BARUEL</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>02522-000</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.65697280</LONGITUDE>
      <LATITUDE>-23.50382830</LATITUDE>
      <HANDICAPES>True</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=259+AVENIDA+BARUEL&amp;codePostal=02522-000&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=NOSSA+CARA+KIDS&amp;horairesOuvertureLundi=09%3a30-12%3a0012%3a00-19%3a00&amp;horairesOuvertureMardi=09%3a30-12%3a0012%3a00-19%3a00&amp;horairesOuvertureMercredi=09%3a30-12%3a0012%3a00-19%3a00&amp;horairesOuvertureJeudi=09%3a30-12%3a0012%3a00-19%3a00&amp;horairesOuvertureVendredi=09%3a30-12%3a0012%3a00-19%3a00&amp;horairesOuvertureSamedi=09%3a30-12%3a0012%3a00-19%3a00&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=112152&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.50382830&amp;lng=-46.65697280&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>09:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
    <PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10124</PUDO_ID>
      <ORDER>7</ORDER>
      <DISTANCE>11370</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>Ciclo Vila Isa</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>1645</STREETNUM>
      <ADDRESS1>Avenida Nossa Senhora do Sabara</ADDRESS1>
      <ADDRESS2>
      </ADDRESS2>
      <ADDRESS3>Vila Isa</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>04685-004</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.68973050</LONGITUDE>
      <LATITUDE>-23.66797440</LATITUDE>
      <HANDICAPES>True</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=1645+Avenida+Nossa+Senhora+do+Sabara&amp;codePostal=04685-004&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=Ciclo+Vila+Isa&amp;horairesOuvertureLundi=10%3a00-12%3a0012%3a00-17%3a00&amp;horairesOuvertureMardi=10%3a00-12%3a0012%3a00-17%3a00&amp;horairesOuvertureMercredi=10%3a00-12%3a0012%3a00-17%3a00&amp;horairesOuvertureJeudi=10%3a00-12%3a0012%3a00-17%3a00&amp;horairesOuvertureVendredi=10%3a00-12%3a0012%3a00-17%3a00&amp;horairesOuvertureSamedi=&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=111645&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.66797440&amp;lng=-46.68973050&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>10:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>17:00</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
    <PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10045</PUDO_ID>
      <ORDER>8</ORDER>
      <DISTANCE>11383</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>PLANET ÁGUAS</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>373</STREETNUM>
      <ADDRESS1>RUA MARCELINA</ADDRESS1>
      <ADDRESS2>
      </ADDRESS2>
      <ADDRESS3>VILA ROMANA</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>05044-010</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.69633880</LONGITUDE>
      <LATITUDE>-23.53040420</LATITUDE>
      <HANDICAPES>False</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=373+RUA+MARCELINA&amp;codePostal=05044-010&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=PLANET+%c3%81GUAS&amp;horairesOuvertureLundi=09%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureMardi=09%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureMercredi=09%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureJeudi=09%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureVendredi=09%3a00-12%3a0012%3a00-18%3a00&amp;horairesOuvertureSamedi=09%3a00-12%3a0012%3a00-15%3a00&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=111500&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.53040420&amp;lng=-46.69633880&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>09:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>09:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>09:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>09:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>09:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>09:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>15:00</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
    <PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10197</PUDO_ID>
      <ORDER>9</ORDER>
      <DISTANCE>12009</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>E LINK</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>1947</STREETNUM>
      <ADDRESS1>RUA DOUTOR ZUQUIM</ADDRESS1>
      <ADDRESS2>
      </ADDRESS2>
      <ADDRESS3>SANTANA</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>02035-012</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.62647760</LONGITUDE>
      <LATITUDE>-23.48892330</LATITUDE>
      <HANDICAPES>True</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=1947+RUA+DOUTOR+ZUQUIM&amp;codePostal=02035-012&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=E+LINK&amp;horairesOuvertureLundi=08%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureMardi=08%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureMercredi=08%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureJeudi=08%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureVendredi=08%3a30-12%3a0012%3a00-18%3a30&amp;horairesOuvertureSamedi=09%3a00-13%3a00&amp;horairesOuvertureDimanche=&amp;identifiantChronopostPointA2PAS=112149&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.48892330&amp;lng=-46.62647760&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>08:30</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>18:30</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>09:00</START_TM>
          <END_TM>13:00</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
    <PUDO_ITEM active="true" overloaded="false">
      <PUDO_ID>BR10077</PUDO_ID>
      <ORDER>10</ORDER>
      <DISTANCE>14931</DISTANCE>
      <PUDO_TYPE>100</PUDO_TYPE>
      <PUDO_TYPE_INFOS />
      <NAME>OESTE FARMA</NAME>
      <LANGUAGE>PT</LANGUAGE>
      <STREETNUM>528</STREETNUM>
      <ADDRESS1>AVENIDA PRESIDENTE ALTINO</ADDRESS1>
      <ADDRESS2>
      </ADDRESS2>
      <ADDRESS3>JAGUARE</ADDRESS3>
      <LOCATION_HINT>
      </LOCATION_HINT>
      <ZIPCODE>05323-001</ZIPCODE>
      <CITY>SÃO PAULO</CITY>
      <COUNTRY>BRA</COUNTRY>
      <LONGITUDE>-46.74858530</LONGITUDE>
      <LATITUDE>-23.55100110</LATITUDE>
      <HANDICAPES>True</HANDICAPES>
      <PARKING>False</PARKING>
      <MAP_URL>http://www.chronopost.fr/transport-express/webdav/site/chronov4/groups/administrators/public/Chronomaps/print-result.html?request=print&amp;adresse1=528+AVENIDA+PRESIDENTE+ALTINO&amp;codePostal=05323-001&amp;localite=S%c3%83O+PAULO&amp;nomEnseigne=OESTE+FARMA&amp;horairesOuvertureLundi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureMardi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureMercredi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureJeudi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureVendredi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureSamedi=08%3a00-12%3a0012%3a00-19%3a00&amp;horairesOuvertureDimanche=08%3a00-13%3a00&amp;identifiantChronopostPointA2PAS=111552&amp;rtype=chronorelais&amp;icnname=ac&amp;lat=-23.55100110&amp;lng=-46.74858530&amp;sw-form-type-point=opt_chrlas&amp;is_print_direction=false&amp;from_addr=&amp;to_addr</MAP_URL>
      <AVAILABLE>full</AVAILABLE>
      <OPENING_HOURS_ITEMS>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>1</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>2</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>3</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>4</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>5</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>12:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>6</DAY_ID>
          <START_TM>12:00</START_TM>
          <END_TM>19:00</END_TM>
        </OPENING_HOURS_ITEM>
        <OPENING_HOURS_ITEM>
          <DAY_ID>7</DAY_ID>
          <START_TM>08:00</START_TM>
          <END_TM>13:00</END_TM>
        </OPENING_HOURS_ITEM>
      </OPENING_HOURS_ITEMS>
      <HOLIDAY_ITEMS />
    </PUDO_ITEM>
  </PUDO_ITEMS>
</RESPONSE>';
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $service_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
//             curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//                 "cache-control: no-cache",
//                 "content-type: application/x-www-form-urlencoded"
//             ));
//             curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/xml',
                'Authorization: ' . $params['authorization']
              )
            );
            $result = curl_exec($ch);
            curl_close($ch);
        }
        Mage::getModel('core/log_adapter')->log("getMYPUDOList resposta: $result");
        $pos = strpos($result, 'PUDO_ITEMS');
        if ($pos == false) {
            return false;
        }

        return $result;
    }
}

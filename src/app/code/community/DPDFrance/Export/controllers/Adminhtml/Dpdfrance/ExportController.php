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
class DPDFrance_Export_Adminhtml_DPDFrance_ExportController extends Mage_Adminhtml_Controller_Action
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
    public function indexAction()
    {
        // Mage::getModel('core/log_adapter')->log("passei");
        $this->loadLayout()
            ->_setActiveMenu('sales/dpdfrexport/export')
            ->_addContent($this->getLayout()
            ->createBlock('dpdfrexport/export_orders'))
            ->renderLayout();
    }

    public static function getIsoCodebyIdCountry($idcountry) // Récupere le code ISO du pays concerné et le convertit au format attendu par la Station DPD
    {
        $isops = array(
            "DE",
            "AD",
            "AT",
            "BE",
            "BA",
            "BG",
            "HR",
            "DK",
            "ES",
            "EE",
            "FI",
            "FR",
            "GB",
            "GR",
            "GG",
            "HU",
            "IM",
            "IE",
            "IT",
            "JE",
            "LV",
            "LI",
            "LT",
            "LU",
            "NO",
            "NL",
            "PL",
            "PT",
            "CZ",
            "RO",
            "RS",
            "SK",
            "SI",
            "SE",
            "CH"
        );
        $isoep = array(
            "D",
            "AND",
            "A",
            "B",
            "BA",
            "BG",
            "CRO",
            "DK",
            "E",
            "EST",
            "SF",
            "F",
            "GB",
            "GR",
            "GG",
            "H",
            "IM",
            "IRL",
            "I",
            "JE",
            "LET",
            "LIE",
            "LIT",
            "L",
            "N",
            "NL",
            "PL",
            "P",
            "CZ",
            "RO",
            "RS",
            "SK",
            "SLO",
            "S",
            "CH"
        );

        if (in_array($idcountry, $isops)) { // Si le code ISO est européen, on le convertit au format Station DPD
            $code_iso = str_replace($isops, $isoep, $idcountry);
        } else {
            $code_iso = str_replace($idcountry, "INT", $idcountry); // Si le code ISO n'est pas européen, on le passe en "INT" (intercontinental)
        }
        return $code_iso;
    }

    private static function _getLastWord($val)
    {
        $pos = strrchr($val, ' ');
        if ($pos === false) {
            return $val;
        }
        return substr($val, $pos + 1, strlen($val));
    }

    private static function _onlyNumbers($val)
    {
        return preg_replace('/\D/', '', $val);
    }

    /**
     * Export Action
     * Generates a CSV file to download
     */
    public function exportAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids');

        $attribute_name_cpf_or_cnpj = Mage::getStoreConfig('dpdfrexport/embarcador/attribute_name_cpf_or_cnpj');

        if (Mage::helper('dpdfrexport')->isEmpty($attribute_name_cpf_or_cnpj)) {
            $this->_getSession()->addError($this->__('Attributo de configuração com o nome da variável está ausente: attribute_name_cpf_or_cnpj'));
            return;
        }

        $attribute_name_width = Mage::getStoreConfig('dpdfrexport/embarcador/attribute_name_width');
        $attribute_name_height = Mage::getStoreConfig('dpdfrexport/embarcador/attribute_name_height');
        $attribute_name_length = Mage::getStoreConfig('dpdfrexport/embarcador/attribute_name_length');

        // Mage::getModel('core/log_adapter')->log("attribute_name_width: $attribute_name_width");
        // Mage::getModel('core/log_adapter')->log("attribute_name_height: $attribute_name_height");
        // Mage::getModel('core/log_adapter')->log("attribute_name_length: $attribute_name_length");

        if (Mage::helper('dpdfrexport')->isEmpty($attribute_name_width) || Mage::helper('dpdfrexport')->isEmpty($attribute_name_height) || Mage::helper('dpdfrexport')->isEmpty($attribute_name_length)) {
            $this->_getSession()->addError($this->__("Attributos de configuração com os nomes das variáveis está ausente: attribute_name_width, attribute_name_height ou attribute_name_length."));
            return;
        }

        if (! empty($orderIds)) {

            $embarcadorServiceUrl = Mage::getStoreConfig('dpdfrexport/embarcador/serviceurl');
            $embarcadorServiceAuthorization = Mage::getStoreConfig('dpdfrexport/embarcador/authorization');
            $embarcadorclientid = Mage::getStoreConfig('dpdfrexport/embarcador/clientid');
            $embarcadorContaCorrente = Mage::getStoreConfig('dpdfrexport/embarcador/contacorrente');
            $embarcadorNumeroContrato = Mage::getStoreConfig('dpdfrexport/embarcador/numerocontrato');

            $remnome = Mage::getStoreConfig('dpdfrexport/embarcador/nome');
            $remcnpjCpf = Mage::getStoreConfig('dpdfrexport/embarcador/cnpjCpf');
            $reminscricaoestadual = Mage::getStoreConfig('dpdfrexport/embarcador/inscricaoestadual');
            $remendereco = Mage::getStoreConfig('dpdfrexport/embarcador/endereco');
            $remnumero = Mage::getStoreConfig('dpdfrexport/embarcador/numero');
            $remcompl = Mage::getStoreConfig('dpdfrexport/embarcador/compl');
            $rembairro = Mage::getStoreConfig('dpdfrexport/embarcador/bairro');
            $remcidade = Mage::getStoreConfig('dpdfrexport/embarcador/cidade');
            $remuf = Mage::getStoreConfig('dpdfrexport/embarcador/uf');
            $remcep = Mage::getStoreConfig('dpdfrexport/embarcador/cep');
            $remfone = Mage::getStoreConfig('dpdfrexport/embarcador/fone');
            $remcel = Mage::getStoreConfig('dpdfrexport/embarcador/cel');
            $rememail = Mage::getStoreConfig('dpdfrexport/embarcador/email');
            $remcontato = Mage::getStoreConfig('dpdfrexport/embarcador/contato');

            $dfeMsgErroDefault = 'O capo DFE é inválido. Separar campos por , e DFEs por |. Exemplo para 2 DFEs: "cfop,danfeCte,nrDoc,serie,tpDocumento,valor|cfop,danfeCte,nrDoc,serie,tpDocumento,valor", exemplo: "6909,null,DECLARACAO,null,2,20.2|6909,null,DECLARACAO,null,2,20.2"';
            $dfes = array();
            foreach ($orderIds as $orderId) {
                Mage::getModel('core/log_adapter')->log("YYY->" . $this->getRequest()
                    ->getPost('order_details_' . $orderId));
                $order_details = explode("-", $this->getRequest()->getPost('order_details_' . $orderId));
                $order_weight = floor($order_details[1]);
                $dfe_fields = $order_details[2];


                Mage::getModel('core/log_adapter')->log("dfe_fields from html: " . $dfe_fields);
                $order = Mage::getModel('sales/order')->load($orderId);
                $this->saveEmbarcadorDfe($order, $dfe_fields);
                // $checkbox_advalorem = $order_details[4];
                // $checkbox_retour = $order_details[4];

                // ////////////////////////////
                if (strlen($dfe_fields) == 0) {
                    $msgError = $dfeMsgErroDefault;
                } else {
                    $dfesToParser = explode('|', $dfe_fields);
                    $msgError = '';
                    foreach ($dfesToParser as $dfe) {
                        if (strlen(trim($dfe)) == 0) {
                            $msgError = $dfeMsgErroDefault;
                            break;
                        }
                        $fs = explode(',', $dfe);
                        if (count($fs) != 6) {
                            $msgError = $dfeMsgErroDefault;
                            break;
                        }
                        foreach ($fs as $f) {
                            if (strlen($f) == 0) {
                                $msgError = $dfeMsgErroDefault;
                                break;
                            }
                        }
                        if (strlen($msgError) != 0) {
                            break;
                        }
                        array_push($dfes, array(
                            'cfop' => $fs[0],
                            'danfeCte' => $fs[1],
                            'nrDoc' => $fs[2],
                            'serie' => $fs[3],
                            'tpDocumento' => $fs[4],
                            'valor' => $fs[5]
                        ));
                    }
                }
                if (strlen($msgError) != 0) {
                    $this->saveMsgEmbarcador($order, $msgError);
                    continue;
                }
                Mage::getModel('core/log_adapter')->log("dfes: " . print_r($dfes, true));
                // ////////////////////////////

                $items = $order->getAllItems();
                $productIds = array();

                $hasError = false;
                $volumes = array();
                $pesoCubadoTotal = 0;
                foreach ($items as $item) {
                    // Mage::getModel('core/log_adapter')->log("item data: ".print_r($item->getData(), true));
//                     if (! ($item->getProduct() instanceof Mage_Catalog_Model_Product)) {
//                         continue;
//                     }
                    if ($item->getParentItemId() == null) {
                        continue;
                    }
                    // Mage::getModel('core/log_adapter')->log("item data: ".print_r($item->getData(), true));
                    // Mage::getModel('core/log_adapter')->log("item_id: ".$item->getProductId());

                    $_product = Mage::getModel('catalog/product')->load($item->getProduct()
                        ->getId());
                    // Mage::getModel('core/log_adapter')->log("product data: ".print_r($_product->getData(), true));
                    Mage::getModel('core/log_adapter')->log("SKU: " . $_product->getSku());
                    Mage::getModel('core/log_adapter')->log("ID: " . $_product->getId());

                    $height = Mage::helper('dpdfrexport')->getValueIntOrNullNoEmpty($_product, $attribute_name_height);
                    $length = Mage::helper('dpdfrexport')->getValueIntOrNullNoEmpty($_product, $attribute_name_length);
                    $width = Mage::helper('dpdfrexport')->getValueIntOrNullNoEmpty($_product, $attribute_name_width);

                    Mage::getModel('core/log_adapter')->log("$attribute_name_height: ".$height);
                    Mage::getModel('core/log_adapter')->log("$attribute_name_length: ".$length);
                    Mage::getModel('core/log_adapter')->log("$attribute_name_width: ".$width);

                    if ($height == null || $height == 0 || $length == null ||$length == 0 || $width == null || $width == 0) {
                        $hasError = true;
                        $this->saveMsgEmbarcador($order, "Atributos " . $attribute_name_width . ", " . $attribute_name_length . " ou " . $attribute_name_height . " inválidos para o produto de id \"" . $_product->getId() . "\"");
                        break;
                    }
                    $pesoCubado = ($height * $length * $width / 6000);
                    // Mage::getModel('core/log_adapter')->log("peso cubado: $pesoCubado");
                    if ($pesoCubado >= 36) {
                        $hasError = true;
                        $this->saveMsgEmbarcador($order, 'As dimensões do produto de id "'.$_product->getId().'" excedem o limite.');
                        break;
                    }
                    $pesoCubadoTotal += $pesoCubado;

                    array_push($volumes, array(
                        'sku' => $item->getSku(),
                        'altura' => $height,
                        'comprimento' => $length,
                        'identificador' => '',
                        'lacre' => 'null',
                        'largura' => $width,
                        'peso' => $order_weight, // $_product->getWeight(),
                        'parentId' => $item->getParentItemId()
                    ));
                }
                if ($hasError == true) {
                    // se tiver alguem item com problema nao chama o embarcador
                    continue;
                }
                // /////////

                $customer_id = $order->getCustomerId();
                $customerData = Mage::getModel('customer/customer')->load($customer_id);

                /* get the billing address */
//                 $to_address = $order->getShippingAddress();
                $to_address = $customerData->getPrimaryShippingAddress();
//                 Mage::getModel('core/log_adapter')->log("customer data: ".print_r($to_address, true));

                /* type of delivery */
                $type = stristr($order->getShippingMethod(), '_', true);
                $cd_pickup_des = 'null';
                $modalidade = '9';

                if ($type === 'dpdfrrelais') {
                    $cd_pickup_des = "\"".trim(strrchr($order->getShippingAddress()->getCompany(), ' '))."\"";
                    $modalidade = '40';
                }

                $dest_nome = $to_address->getFirstname() . ' ' . $to_address->getLastname();

                $cpfOrCnpj = Mage::helper('dpdfrexport')->getValueStrOrNullNoEmpty($customerData, $attribute_name_cpf_or_cnpj);
                if ($cpfOrCnpj == null || strlen($cpfOrCnpj) == 0) {
                    $msg = "Attributo $attribute_name_cpf_or_cnpj está ausente nos dados do consumidor de id '$customer_id'.";
                    $this->saveMsgEmbarcador($order, $msg);
                    continue;
                }
                // Mage::getModel('core/log_adapter')->log("cpfOrCnpj: $cpfOrCnpj");
                $dest_cnpjCpf = $cpfOrCnpj;

                $dest_endereco = $to_address->getStreet(1);
                $dest_numero = '';
                $dest_compl = '';
                $dest_bairro = $to_address->getStreet(2);
                $dest_cidade = $to_address->getCity();

                $dest_uf = $to_address->getRegion();

                $dest_cep = self::_onlyNumbers($to_address->getPostcode());
                $dest_fone = $to_address->getTelephone();
                $dest_cel = $to_address->getFax();
                $dest_email = $customerData->getEmail();// $to_address->getEmail();
                $dest_contato = $to_address->getFirstname();
                $order_tot_valor = $order->getGrandTotal();

                $variables = array(
                    'serviceurl' => $embarcadorServiceUrl,
                    'serviceauthorization' => $embarcadorServiceAuthorization,
                    'client_id' => $embarcadorclientid,
                    'conta_corrente' => $embarcadorContaCorrente,
                    'numero_contrato' => $embarcadorNumeroContrato,
                    'order_id' => $order->getRealOrderId(),
                    'order_tot_valor' => $order_tot_valor,
                    'conteudo' => 'PICKUP POINT',

                    'order_weight' => $order_weight > $pesoCubadoTotal ? $order_weight : $pesoCubadoTotal,
                    'cd_pickup_des' => $cd_pickup_des,
                    'modalidade' => $modalidade,

                    'rem_nome' => $remnome,
                    'rem_cnpjCpf' => $remcnpjCpf,
                    'rem_inscricaoestadual' => $reminscricaoestadual,
                    'rem_endereco' => $remendereco,
                    'rem_numero' => $remnumero,
                    'rem_compl' => $remcompl,
                    'rem_bairro' => $rembairro,
                    'rem_cidade' => $remcidade,
                    'rem_uf' => $remuf,
                    'rem_cep' => str_replace('-', '', $remcep),
                    'rem_fone' => $remfone,
                    'rem_cel' => $remcel,
                    'rem_email' => $rememail,
                    'rem_contato' => $remcontato,

                    'dest_nome' => $dest_nome,
                    'dest_cnpjCpf' => $dest_cnpjCpf,
                    'dest_ie' => '',
                    'dest_endereco' => $dest_endereco,
                    'dest_numero' => $dest_numero,
                    'dest_compl' => $dest_compl,
                    'dest_bairro' => $dest_bairro,
                    'dest_cidade' => $dest_cidade,
                    'dest_uf' => $dest_uf,
                    'dest_cep' => str_replace('-', '', $dest_cep),
                    'dest_fone' => $dest_fone,
                    'dest_cel' => $dest_cel,
                    'dest_email' => $dest_email,
                    'dest_contato' => $dest_contato,
                    'volumes' => $volumes,
                    'dfes' => $dfes
                );
                $response = self::_embarcadorIncluir($variables);
                $this->saveMsgEmbarcador($order, $response);
                if (strpos($response, 'sucesso') !== false) {
                    // resposta com sucesso, salva tracking
                    Mage::getModel('core/log_adapter')->log("server succes response: ".$response);
                    $trackingNumber = self::_getShippingIdByResponse($response);
                    $shipment = $order->prepareShipment();
                    if ($shipment && $order->canShip()) {
                        $shipment->register();
                        $track = Mage::getModel('sales/order_shipment_track')->setShipment($shipment)
                            ->setData('title', 'dpdfrrelais')
                            ->setData('number', $trackingNumber)
                            ->setData('carrier_code', 'dpdfrrelais')
                            ->setData('order_id', $shipment->getData('order_id'));

                        $shipment->addTrack($track);
                        $shipment->save();
                        $track->save();
                    }
                }
            }
        } else {
            $this->_getSession()->addError($this->__('No Order has been selected'));
        }
        self::indexAction();
    }
    private static function _getShippingIdByResponse($response) {
        // {"codigo":"76287645","shipmentId":"06601100000137","status":"Solicitação inserida com sucesso."}
        $sl1 = '"shipmentId":"';
        $pos = strpos($response, $sl1)+strlen($sl1);
        $pos2 = strpos($response, '"', $pos);
        return substr($response, $pos, $pos2-$pos);
    }

    private function saveMsgEmbarcador($order, $pmsg)
    {
        $msg = htmlspecialchars($pmsg, ENT_COMPAT, 'UTF-8', false);
        // Mage::getModel('core/log_adapter')->log("msg: $msg");
        $order->setJadLogEmbarcadorResponseOrStatus($msg);
        $order->save();
    }
    private function saveEmbarcadorDfe($order, $dfe)
    {
        $order->setJadLogEmbarcadorDfe($dfe);
        $order->save();
    }

    /**
     * Add a new field to the csv file
     *
     * @param
     *            csvContent : the current csv content
     * @param
     *            fieldDelimiter : the delimiter character
     * @param
     *            fieldContent : the content to add
     * @return : the concatenation of current content and content to add
     */
    private function _addFieldToCsv($csvContent, $fieldDelimiter, $fieldContent, $size = 0, $isNum = false)
    {
        if (! $size) {
            return $csvContent . $fieldDelimiter . $fieldContent . $fieldDelimiter;
        } else {
            $newFieldContent = $fieldContent;
            if ($isNum) {
                for ($i = strlen($fieldContent); $i < $size; $i ++) {
                    $newFieldContent = '0' . $newFieldContent;
                }
            } else {
                for ($i = strlen($fieldContent); $i < $size; $i ++) {
                    $newFieldContent .= ' ';
                }
            }
            /*
             * if( strlen( $newFieldContent ) != $size ) {
             * var_dump('!! FAIL !! '. $newFieldContent);
             * }
             */
            $newFieldContent = substr($newFieldContent, 0, $size);
            return $csvContent . $fieldDelimiter . $newFieldContent . $fieldDelimiter;
        }
    }

    // Fonction pour enlever les accents afin d'eliminer les erreurs d'encodage
    private function _stripAccents($str)
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
        $str = preg_replace('/[\x{0022}\x{0025}\x{0026}\x{0027}\x{00B0}]/u', ' ', $str);
        return $str;
    }

    private static function _embarcadorIncluir($params)
    {
        Mage::getModel('core/log_adapter')->log("embarcador url: ".$params['serviceurl']);

        $dfesOut = '    "dfe": [';
        foreach ($params['dfes'] as $dfe) {
            $dfesOut .= '
        {
            "cfop": "' . $dfe['cfop'] . '",
            "danfeCte": ' . $dfe['danfeCte'] . ',
            "nrDoc": "' . $dfe['nrDoc'] . '",
            "serie": ' . $dfe['serie'] . ',
            "tpDocumento": ' . $dfe['tpDocumento'] . ',
            "valor": ' . $dfe['valor'] . '
        },';
        }
        if (strlen($dfesOut) > 0) {
            $dfesOut = substr($dfesOut, 0, strlen($dfesOut) - 1);
        }
        $dfesOut .= '
    ],';

        $volumeOut = '    "volume": [';
        foreach ($params['volumes'] as $vol) {
            $volumeOut .= '
        {
            "altura": ' . $vol['altura'] . ',
            "comprimento": ' . $vol['comprimento'] . ',
            "identificador": "' . $vol['identificador'] . '",
            "lacre": ' . $vol['lacre'] . ',
            "largura": ' . $vol['largura'] . ',
            "peso": ' . $vol['peso'] . '
        },';
        }
        if (strlen($volumeOut) > 0) {
            $volumeOut = substr($volumeOut, 0, strlen($volumeOut) - 1);
        }
        $volumeOut .= '
    ]';

//"codCliente": "' . $params['client_id'] . '",
        $body = '{
    "conteudo": "' . $params['conteudo'] . '",
    "pedido": ["' . $params['order_id'] . '"],
    "totPeso": ' . $params['order_weight'] . ',
    "totValor": ' . $params['order_tot_valor'] . ',
    "obs": "",
    "modalidade": ' . $params['modalidade'] . ',
    "contaCorrente": "' . $params['conta_corrente'] . '",
    "centroCusto": null,
    "tpColeta": "K",
    "cdPickupOri": null,
    "cdPickupDes": ' . $params['cd_pickup_des'] . ',
    "tipoFrete": 0,
    "cdUnidadeOri": "1",
    "cdUnidadeDes": null,
    "vlColeta" : null,
    "nrContrato": ' . $params['numero_contrato'] . ',
    "servico": 1,
    "shipmentId": null,
    "rem": {
        "nome": "' . $params['rem_nome'] . '",
        "cnpjCpf": "' . $params['rem_cnpjCpf'] . '",
        "ie": "' . $params['rem_inscricaoestadual'] . '",
        "endereco": "' . $params['rem_endereco'] . '",
        "numero": "' . $params['rem_numero'] . '",
        "compl": "' . $params['rem_compl'] . '",
        "bairro": "' . $params['rem_bairro'] . '",
        "cidade": "' . $params['rem_cidade'] . '",
        "uf": "' . $params['rem_uf'] . '",
        "cep": "' . $params['rem_cep'] . '",
        "fone": "' . $params['rem_fone'] . '",
        "cel": "' . $params['rem_cel'] . '",
        "email": "' . $params['rem_email'] . '",
        "contato": "' . $params['rem_contato'] . '"
    },
    "des": {
        "nome": "' . $params['dest_nome'] . '",
        "cnpjCpf": "' . $params['dest_cnpjCpf'] . '",
        "ie": "' . $params['dest_ie'] . '",
        "endereco": "' . $params['dest_endereco'] . '",
        "numero": "' . $params['dest_numero'] . '",
        "compl": "' . $params['dest_compl'] . '",
        "bairro": "' . $params['dest_bairro'] . '",
        "cidade": "' . $params['dest_cidade'] . '",
        "uf": "' . $params['dest_uf'] . '",
        "cep": "' . $params['dest_cep'] . '",
        "fone": "' . $params['dest_fone'] . '",
        "cel": "' . $params['dest_cel'] . '",
        "email": "' . $params['dest_email'] . '",
        "contato": "' . $params['dest_contato'] . '"
    },
' . $dfesOut . '
' . $volumeOut . '
}';

        Mage::getModel('core/log_adapter')->log("embarcador body: ".$body);

        $result = '';

        if (Mage::helper('dpdfrexport')->isTeste()) {
            $result = '{"codigo":"76551969","shipmentId":"06601100000153","status":"Solicitação inserida com sucesso."}';

        } else {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $params['serviceurl']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "authorization: $params[serviceauthorization]",
                "cache-control: no-cache",
                "content-type: application/json"
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $result = curl_exec($ch);
            curl_close($ch);
        }
        Mage::getModel('core/log_adapter')->log("embarcador resposta: ".$result);
        return $result;
    }
}
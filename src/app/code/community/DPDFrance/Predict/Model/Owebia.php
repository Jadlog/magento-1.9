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

class DPDFrance_Predict_Model_Owebia
{
    public static $FLOAT_REGEX = '[-]?\d+(?:[.]\d+)?';
    public static $POSITIVE_FLOAT_REGEX = '\d+(?:[.]\d+)?';
    //public static $COUPLE_REGEX = '(?:[0-9.]+|\*)(?:\[|\])?\:[0-9.]+(?:\:[0-9.]+%?)*';
    public static $COUPLE_REGEX = '(?:[0-9.]+|\*) *(?:\[|\])? *\: *[0-9.]+';
    public static $UNCOMPRESSED_STRINGS = array(
        ' product.attribute.',
        ' product.option.',
        ' product.stock.',
        '{product.attribute.',
        '{product.option.',
        '{product.stock.',
        '{product.weight}',
        '{product.quantity}',
        '{cart.weight}',
        '{cart.quantity}',
        '{cart.coupon}',
        '{cart.price_including_tax}',
        '{cart.price_excluding_tax}',
        '{cart.',
        '{customvar.',
        '{selection.weight}',
        '{selection.quantity}',
        '{selection.',
        '{shipto.country.',
        '{foreach ',
        '{/foreach}',
    );
    public static $COMPRESSED_STRINGS = array(
        ' p.a.',
        ' p.o.',
        ' p.s.',
        '{p.a.',
        '{p.o.',
        '{p.s.',
        '{p.w}',
        '{p.qty}',
        '{c.w}',
        '{c.qty}',
        '{c.cpn}',
        '{c.pit}',
        '{c.pet}',
        '{c.',
        '{v.',
        '{s.w}',
        '{s.qty}',
        '{s.',
        '{dest.ctry.',
        '{each ',
        '{/each}',
    );

    protected $_input;
    protected $_config;
    protected $_messages;
    protected $_formula_cache;
    protected $_expression_cache;
    protected $_overWeightMessage = 'Over weight';
    public $debug = 0;
    public $debug_output = '';
    public $debug_header = null;

    public function __construct($input) {
        $this->_formula_cache = array();
        $this->_messages = array();
        $this->_input = $input;
        $this->_config = array();
        $this->_parseInput();
    }

    private function debug($text, $level=10) {
        if ($this->debug>=$level) $this->debug_output .= "<p>".$text."</p>";
    }

    public function printDebug() {
        if ($this->debug>0) echo "<style rel=\"stylesheet\" type=\"text/css\">"
            .".osh-formula{color:#f90;} .osh-key{color:#0099f7;}"
            .".osh-error{color:#f00;} .osh-warning{color:#ff0;} .osh-info{color:#7bf700;}"
            .".osh-debug{background:#000;color:#bbb;position:absolute;top:0;left:0;width:100%;z-index:100;-moz-opacity:0.9;opacity:0.9;text-align:left;white-space:pre-wrap;}"
            .".osh-debug-content{padding:10px;}"
            .".osh-replacement{color:#ff3000;}"
            ."</style>"
            ."<div id=\"osh-debug\" class=\"osh-debug\"><pre class=\"osh-debug-content\"><span style=\"float:right;cursor:pointer;\" onclick=\"document.getElementById('osh-debug').style.display = 'none';\">[<span style=\"padding:0 5px;color:#f00;\">X</span>]</span>"
            ."<p>".$this->debug_header."</p>".$this->debug_output."</pre></div>";
    }

    public function setDebugHeader($process) {
        $header = 'DEBUG ' . __FILE__;//'DEBUG app/code/community/Owebia/Shipping/2/Model/Carrier/OwebiaShippingHelper.php<br/>';
        foreach ($process['data'] as $key => $data) {
            $header .= '   <span class="osh-key">'.$key.'</span> = <span class="osh-formula">'.$this->_toString($data).'</span><br/>';
        }
        $this->debug_header = $header;
    }

    public function getConfig() {
        return $this->_config;
    }

    public function getMessages() {
        $messages = $this->_messages;
        $this->_messages = array();
        return $messages;
    }

    public function formatConfig($compress) {
        $output = '';
        foreach ($this->_config as $code => $row) {
            if (!isset($row['lines'])) {
                if (isset($row['*comment']['value'])) {
                    $output .= trim($row['*comment']['value'])."\n";
                }
                $output .= '{'.($compress ? '' : "\n");
                foreach ($row as $key => $property) {
                    if (substr($key,0,1)!='*') {
                        $value = $property['value'];
                        if (isset($property['comment'])) $output .= ($compress ? '' : "\t").'/* '.$property['comment'].' */'.($compress ? '' : "\n");
                        $output .= ($compress ? '' : "\t").$key.':'.($compress ? '' : ' ');
                        if (is_bool($value)) $output .= $value ? 'true' : 'false';
                        else if (is_int($value)) $output .= $value;
                        else if ((string)((float)$value)==$value) $output .= $value;
                        else $output .= '"'.str_replace('"','\\"',$value).'"';
                        $output .= ','.($compress ? '' : "\n");
                    }
                }
                if ($compress) $output = preg_replace('/,$/','',$output);
                $output .= "}\n".($compress ? '' : "\n");
            } else {
                $output .= $row['lines']."\n";
            }
        }
        return $compress ? $this->compress($output) : $this->uncompress($output);
    }

    public function checkConfig() {
        $process = array(
            'result' => null,
            'data' => array(
                'cart.price_excluding_tax' => 0,
                'cart.price_including_tax' => 0,
                'cart.coupon' => '',
                'shipto.country.code' => '',
                'shipto.country.name' => '',
                'shipto.region.code' => '',
                'shipto.postal.code' => '',
                'origin.country.code' => '',
                'origin.country.name' => '',
                'origin.region.code' => '',
                'origin.postal.code' => '',
                'free_shipping' => false,
                'customer.group.id' => '',
                'customer.group.code' => '',
                'cart.weight' => 0,
                'cart.weight.unit' => 'kg',
                'cart.quantity' => 0,
            ),
            'cart.items' => array(),
            'products' => array(),
            'config' => $this->_config,
        );
        foreach ($this->_config as $code => &$row) {
            $this->processRow($process,$row,$check_all_conditions=true);
            foreach ($row as $property_key => $property_value) {
                if (substr($property_key,0,1)!='*') $this->getRowProperty($row,$property_key);
            }
        }
    }

    public function processRow($process, &$row, $is_checking=false) {
        if (!isset($row['*code'])) return;

        self::debug('process row <span class="osh-key">'.$row['*code'].'</span>',1);
        if (!isset($row['label']['value'])) $row['label']['value'] = '';

        $enabled = $this->getRowProperty($row,'enabled');
        if (isset($enabled)) {
            if (!$is_checking && !$enabled) {
                $this->addMessage('info',$row,'enabled','Configuration disabled');
                return new DPDFrancePredict_Os_Result(false);
            }
        }

        $conditions = $this->getRowProperty($row,'conditions');
        if (isset($conditions)) {
            $result = $this->_processFormula($process,$row,'conditions',$conditions,$is_checking);
            if (!$is_checking) {
                if (!$result->success) return $result;
                if (!$result->result) {
                    $this->addMessage('info',$row,'conditions',"The cart doesn't match conditions");
                    return new DPDFrancePredict_Os_Result(false);
                }
            }
        }

        $shipto = $this->getRowProperty($row,'shipto');
        if (isset($shipto)) {
            $shipto_match = $this->_addressMatch($shipto,array(
                'country_code' => $process['data']['shipto.country.code'],
                'region_code' => $process['data']['shipto.region.code'],
                'postcode' => $process['data']['shipto.postal.code']
            ));
            if (!$is_checking && !$shipto_match) {
                $this->addMessage('info',$row,'shipto',"The shipping method doesn't cover the zone");
                return new DPDFrancePredict_Os_Result(false);
            }
        }

        $origin = $this->getRowProperty($row,'origin');
        if (isset($origin)) {
            $origin_match = $this->_addressMatch($origin,array(
                'country_code' => $process['data']['origin.country.code'],
                'region_code' => $process['data']['origin.region.code'],
                'postcode' => $process['data']['origin.postal.code']
            ));
            if (!$is_checking && !$origin_match) {
                $this->addMessage('info',$row,'origin',"The shipping method doesn't match to shipping origin");
                return new DPDFrancePredict_Os_Result(false);
            }
        }

        $customer_groups = $this->getRowProperty($row,'customer_groups');
        if (isset($customer_groups)) {
            $groups = explode(',',$customer_groups);
            $group_match = false;
            //self::debug('code:'.$process['data']['customer.group.code'].', id:'.$process['data']['customer.group.id']);
            foreach ($groups as $group) {
                $group = trim($group);
                if ($group==$process['data']['customer.group.code'] || is_int($group) && $group==$process['data']['customer.group.id'] || $group=='*') {
                    $group_match = true;
                    break;
                }
            }
            if (!$is_checking && !$group_match) {
                $this->addMessage('info',$row,'customer_groups',"The shipping method doesn't match to customer group (%s)",$process['data']['customer.group.code']);
                return new DPDFrancePredict_Os_Result(false);
            }
        }

        $fees = $this->getRowProperty($row,'fees');
        if (isset($fees)) {
            $result = $this->_processFormula($process,$row,'fees',$fees,$is_checking);
            if (!$result->success) return $result;
            self::debug('   => <span class="osh-info">result = <span class="osh-formula">'.$this->_toString($result->result).'</span>',1);
            return new DPDFrancePredict_Os_Result(true,(float)$result->result);
        }
        return new DPDFrancePredict_Os_Result(false);
    }

    public function getRowProperty($row, $key, $original_row=null, $original_key=null) {
        $property = null;
        $output = null;
        if (isset($original_row) && isset($original_key) && $original_row['*code']==$row['*code'] && $original_key==$key) {
            $this->addMessage('error',$row,$key,'Infinite loop %s',"<span class=\"code\">{".$row['*code'].'.'.$key."}</span>");
            return array('error' => 'Infinite loop');
        }
        if (isset($row[$key]['value'])) {
            $property = $row[$key]['value'];
            $output = $property;
            $code = array_key_exists('*code', $row) ? $row['*code'] : NULL; self::debug('   get <span class="osh-key">'.$code.'</span>.<span class="osh-key">'.$key.'</span> = <span class="osh-formula">'.$this->_toString($property).'</span>',5);
            preg_match_all('/{([a-z0-9_]+)\.([a-z0-9_]+)}/i',$output,$result_set,PREG_SET_ORDER);
            foreach ($result_set as $result) {
                list($original,$ref_code,$ref_key) = $result;
                if (!in_array($ref_code,array('module','date','store','cart','product','selection','customvar'))) {
                    if ($ref_code==$row['code']['value'] && $ref_key==$key) {
                        $this->addMessage('error',$row,$key,'Infinite loop %s',"<span class=\"code\">".$original."</span>");
                        return null;
                    }
                    if (isset($this->_config[$ref_code][$ref_key]['value'])) {
                        $replacement = $this->getRowProperty($this->_config[$ref_code],$ref_key,
                            isset($original_row) ? $original_row : $row,isset($original_key) ? $original_key : $key);
                        if (is_array($replacement) && isset($replacement['error'])) {
                            return isset($original_row) ? $replacement : 'false';
                        }
                    } else {
                        $this->addMessage('error',$row,$key,'Non-existent property %s',"<span class=\"code\">".$original."</span>");
                        $replacement = 'null';
                    }
                    $output = $this->replace($original,$replacement,$output);
                }
            }
        } else {
            $code = array_key_exists('*code', $row) ? $row['*code'] : NULL; self::debug('   get <span class="osh-key">'.$code.'</span>.<span class="osh-key">'.$key.'</span> = <span class="osh-formula">null</span>',5);
        }
        return $output;
    }

    protected function _toString($value) {
        if (!isset($value)) return 'null';
        else if (is_bool($value)) return $value ? 'true' : 'false';
        else return $value;
    }

    protected function replace($from, $to, $input) {
        if ($from===$to) return $input;
        if (strpos($input,$from)===false) return $input;
        $to = $this->_toString($to);
        self::debug('      replace <span class="osh-replacement">'.$this->_toString($from).'</span> by <span class="osh-replacement">'.$to.'</span> =&gt; <span class="osh-formula">'.str_replace($from,'<span class="osh-replacement">'.$to.'</span>',$input).'</span>',5);
        return str_replace($from,$to,$input);
    }

    protected function _min() {
        $args = func_get_args();
        $min = null;
        foreach ($args as $arg) {
            if (isset($arg) && (!isset($min) || $min>$arg)) $min = $arg;
        }
        return $min;
    }

    protected function _max() {
        $args = func_get_args();
        $max = null;
        foreach ($args as $arg) {
            if (isset($arg) && (!isset($max) || $max<$arg)) $max = $arg;
        }
        return $max;
    }

    protected function _processFormula($process, &$row, $property_key, $formula_string, $is_checking, $use_cache=true)
    {
        $result = $this->_prepareFormula($process,$row,$property_key,$formula_string,$is_checking,$use_cache);
        if (!$result->success) return $result;

        $eval_result = $this->_evalFormula($result->result);
        if ($result->result == 'null') {
            $this->addMessage('over_weight',$row,$property_key, $this->_overWeightMessage);
            $result = new DPDFrancePredict_Os_Result(false);
            if ($use_cache) $this->setCache($formula_string,$result);
            return $result;
        }
        if (!isset($eval_result)) {
            $this->addMessage('error',$row,$property_key,'Invalid formula');
            $result = new DPDFrancePredict_Os_Result(false);
            if ($use_cache) $this->setCache($formula_string,$result);
            return $result;
        }
        self::debug('      formula evaluation = <span class="osh-formula">'.$this->_toString($eval_result).'</span>',10);
        $result = new DPDFrancePredict_Os_Result(true,$eval_result);
        if ($use_cache) $this->setCache($formula_string,$result);
        return $result;
    }

    public function evalInput($process, $row, $property_key, $input) {
        $result = $this->_prepareFormula($process,$row,$property_key,$input,$is_checking=false,$use_cache=true);
        return $result->success ? $result->result : $input;
    }

    protected function setCache($expression, $value) {
        if ($value instanceof DPDFrancePredict_Os_Result) {
            $this->_formula_cache[$expression] = $value;
            self::debug('      cache <span class="osh-replacement">'.$expression.'</span> = <span class="osh-formula">'.$this->_toString($this->_formula_cache[$expression]).'</span>',10);
        } else {
            $value = $this->_toString($value);
            $this->_expression_cache[$expression] = $value;
            self::debug('      cache <span class="osh-replacement">'.$expression.'</span> = <span class="osh-formula">'.$value.'</span>',10);
        }
    }

    protected function _prepareFormula($process, $row, $property_key, $formula_string, $is_checking, $use_cache=true)
    {
        if ($use_cache && isset($this->_formula_cache[$formula_string])) {
            $result = $this->_formula_cache[$formula_string];
            self::debug('      get cached formula <span class="osh-replacement">'.$formula_string.'</span> = <span class="osh-formula">'.$this->_toString($result->result).'</span>',10);
            return $result;
        }

        $formula = $formula_string;
        //self::debug('      formula = <span class="osh-formula">'.$formula.'</span>',10);

        while (preg_match("#{foreach product\.((?:attribute|option)\.(?:[a-z0-9_]+))}(.*){/foreach}#i",$formula,$result)) {
            $original = $result[0];
            if ($use_cache && isset($this->_expression_cache[$original])) {
                $replacement = $this->_expression_cache[$original];
                self::debug('      get cached expression <span class="osh-replacement">'.$original.'</span> = <span class="osh-formula">'.$replacement.'</span>',10);
            }
            else {
                $replacement = 0;
                list($filter_property_type,$filter_property_name) = explode('.',$result[1]);
                $selections = array();
                self::debug('      :: foreach <span class="osh-key">'.$filter_property_type.'</span>.<span class="osh-key">'.$filter_property_name.'</span>',10);
                foreach ($process['products'] as $product) {
                    $tmp_value = $this->_getProductProperty($product,$filter_property_type,$filter_property_name,$get_by_id=false);
                    self::debug('         products[<span class="osh-formula">'.$product->getName().'</span>].<span class="osh-key">'.$filter_property_type.'</span>.<span class="osh-key">'.$filter_property_name.'</span> = <span class="osh-formula">'.$this->_toString($tmp_value).'</span>',10);
                    $key = 'val_'.$tmp_value;
                    $sel = isset($selections[$key]) ? $selections[$key] : null;
                    $selections[$key]['products'][] = $product;
                    $selections[$key]['weight'] = (isset($sel['weight']) ? $sel['weight'] : 0)+$product->getAttribute('weight')*$product->getQuantity();
                    $selections[$key]['quantity'] = (isset($sel['quantity']) ? $sel['quantity'] : 0)+$product->getQuantity();
                }
                self::debug('      :: start foreach',10);
                foreach ($selections as $selection) {
                    $process2 = $process;
                    $process2['products'] = $selection['products'];
                    $process2['data']['selection.quantity'] = $selection['quantity'];
                    $process2['data']['selection.weight'] = $selection['weight'];
                    $process_result = $this->_processFormula($process2,$row,$property_key,$result[2],$is_checking,$tmp_use_cache=false);
                    $replacement += $process_result->result;
                }
                self::debug('      :: end foreach <span class="osh-key">'.$filter_property_type.'</span>.<span class="osh-key">'.$filter_property_name.'</span>',10);
                if ($use_cache) $this->setCache($original,$replacement);
            }
            $formula = $this->replace($original,$replacement,$formula);
        }

        $formula = str_replace(array("\n","\t"),array('',''),$formula);

        while (preg_match("#{customvar\.([a-z0-9_]+)}#i",$formula,$result)) {
            $original = $result[0];
            $replacement = Mage::getModel('core/variable')->loadByCode($result[1])->getValue('plain');
            $formula = $this->replace($original,$replacement,$formula);
        }

        $first_product = isset($process['products'][0]) ? $process['products'][0] : null;
        if (!isset($process['data']['selection.weight'])) $process['data']['selection.weight'] = $process['data']['cart.weight'];
        if (!isset($process['data']['selection.quantity'])) $process['data']['selection.quantity'] = $process['data']['cart.quantity'];
        $process['data']['product.weight'] = isset($first_product) ? $first_product->getAttribute('weight') : 0;
        $process['data']['product.quantity'] = isset($first_product) ? $first_product->getQuantity() : 0;

        foreach ($process['data'] as $original => $replacement) {
            $formula = $this->replace('{'.$original.'}',$replacement,$formula);
        }

        if (isset($first_product)) {
            while (preg_match("#{product\.(attribute|option|stock)\.([a-z0-9_]+)}#i",$formula,$result)) {
                $original = $result[0];
                switch ($result[1]) {
                    case 'attribute': $replacement = $first_product->getAttribute($result[2]); break;
                    case 'option': $replacement = $first_product->getOption($result[2]); break;
                    case 'stock': $replacement = $first_product->getStockData($result[2]); break;
                }
                $formula = $this->replace($original,$replacement,$formula);
            }
        }

        //while (preg_match("/{(count|all|any) (attribute|option) '([^'\)]+)' ?(==|<=|>=|<|>|!=) ?(".self::$FLOAT_REGEX."|true|false|'[^'\)]*')}/",$formula,$result)
        //            || preg_match("/{(sum|count distinct) (attribute|option) '([^'\)]+)'}/",$formula,$result))
        while (preg_match("/{(count) products(?: where ([^}]+))?}/i",$formula,$result)
                    || preg_match("/{(sum|count distinct) product\.(attribute|option)\.([a-z0-9_]+)(?: where ([^}]+))?}/i",$formula,$result)) {
            $original = $result[0];
            if ($use_cache && isset($this->_expression_cache[$original])) {
                $replacement = $this->_expression_cache[$original];
                self::debug('      get cached expression <span class="osh-replacement">'.$original.'</span> = <span class="osh-formula">'.$replacement.'</span>',10);
            }
            else {
                $replacement = $this->_processProductProperty($process['products'],$result);
                if ($use_cache) $this->setCache($result[0],$replacement);
            }
            $formula = $this->replace($original,$replacement,$formula);
        }

        //while (preg_match("/{table '([^']+)' ([^}]+)}/",$formula,$result))
        while (preg_match("/{table ([^}]+) in ([0-9\.:,\*\[\] ]+)}/i",$formula,$result)) {
            $original = $result[0];
            if ($use_cache && isset($this->_expression_cache[$original])) {
                $replacement = $this->_expression_cache[$original];
                self::debug('      get cached expression <span class="osh-replacement">'.$original.'</span> = <span class="osh-formula">'.$replacement.'</span>',10);
            } else {
                $reference_value = $this->_evalFormula($result[1]);
                if (isset($reference_value)) {
                    $fees_table_string = $result[2];

                    if (!preg_match('#^'.self::$COUPLE_REGEX.'(?:, *'.self::$COUPLE_REGEX.')*$#',$fees_table_string)) {
                        $this->addMessage('error',$row,$property_key,'Error in table %s','<span class="osh-formula">'.htmlentities($result[0]).'</span>');
                        $result = new DPDFrancePredict_Os_Result(false);
                        if ($use_cache) $this->setCache($formula_string,$result);
                        return $result;
                    }
                    $fees_table = explode(',',$fees_table_string);

                    $replacement = null;
                    foreach ($fees_table as $item) {
                        $fee_data = explode(':',$item);

                        $fee = trim($fee_data[1]);
                        $max_value = trim($fee_data[0]);

                        $last_char = $max_value{strlen($max_value)-1};
                        if ($last_char=='[') $including_max_value = false;
                        else if ($last_char==']') $including_max_value = true;
                        else $including_max_value = true;

                        $max_value = str_replace(array('[',']'),'',$max_value);

                        if ($max_value=='*' || $including_max_value && $reference_value<=$max_value || !$including_max_value && $reference_value<$max_value) {
                            $replacement = $fee;//$this->_calculateFee($process,$fee,$var);
                            break;
                        }
                    }
                }
                $replacement = $this->_toString($replacement);
                if ($use_cache) $this->setCache($original,$replacement);
            }
            $formula = $this->replace($original,$replacement,$formula);
        }
        $result = new DPDFrancePredict_Os_Result(true,$formula);
        return $result;
    }

    protected function _evalFormula($formula) {
        if (is_bool($formula)) return $formula;
        if (!preg_match('/^(?:floor|ceil|round|max|min|rand|pow|pi|sqrt|log|exp|abs|int|float|true|false|null|and|or|in|substr|strtolower'
                .'|in_array\(\'(?:[^\']*)\', *array\( *(?:\'(?:[^\']+)\') *(?: *, *\'(?:[^\']+)\')* *\) *\)'
                .'|\'[^\']*\'|[0-9,\'\.\-\(\)\*\/\?\:\+\<\>\=\&\|%! ])*$/',$formula)) {
            self::debug('      doesn\'t match',10);
            return null;
        }
        $formula = str_replace(
            array('min','max'),
            array('$this->_min','$this->_max'),
            $formula
        );
        $eval_result = null;
        @eval('$eval_result = ('.$formula.');');
        return $eval_result;
    }

    protected function _getOptionsAndData($string) {
        if (preg_match('/^(\\s*\(\\s*([^\] ]*)\\s*\)\\s*)/',$string,$result)) {
            $options = $result[2];
            $data = str_replace($result[1],'',$string);
        } else {
            $options = '';
            $data = $string;
        }
        return array(
            'options' => $options,
            'data' => $data,
        );
    }

    public function compress($input) {
        if (preg_match_all("/{table (.*) in (".self::$COUPLE_REGEX."(?:, *".self::$COUPLE_REGEX.")*)}/imsU",$input,$result,PREG_SET_ORDER)) {
            foreach ($result as $result_i) {
                $fees_table = explode(',',$result_i[2]);
                $value = null;
                foreach ($fees_table as $index => $item) {
                    list($max_value,$fee) = explode(':',$item);
                    $last_char = $max_value{strlen($max_value)-1};
                    if (in_array($last_char,array('[',']'))) {
                        $including_char = $last_char;
                        $max_value = str_replace(array('[',']'),'',$max_value);
                    } else $including_char = '';
                    $fees_table[$index] = ((float)$max_value).$including_char.':'.((float)$fee);
                }
                $input = str_replace($result_i[2],implode(',',$fees_table),$input);
                $input = str_replace($result_i[1],trim($result_i[1]),$input);
            }
        }
        if (preg_match_all("#{foreach ([^}]*)}(.*){/foreach}#imsU",$input,$result,PREG_SET_ORDER)) {
            foreach ($result as $result_i) {
                $input = str_replace($result_i[1],trim($result_i[1]),$input);
                $input = str_replace($result_i[2],trim($result_i[2]),$input);
            }
        }
        return '$$'.str_replace(
            self::$UNCOMPRESSED_STRINGS,
            self::$COMPRESSED_STRINGS,
            $input
        );
    }

    public function uncompress($input) {
        if (preg_match_all("/{table (.*) in (".self::$COUPLE_REGEX."(?:, *".self::$COUPLE_REGEX.")*)}/iU",$input,$result,PREG_SET_ORDER)) {
            foreach ($result as $result_i) {
                $fees_table = explode(',',$result_i[2]);
                $value = null;
                foreach ($fees_table as $index => $item) {
                    list($max_value,$fee) = explode(':',$item);
                    $last_char = $max_value{strlen($max_value)-1};
                    if (in_array($last_char,array('[',']'))) {
                        $including_char = $last_char;
                        $max_value = str_replace(array('[',']'),'',$max_value);
                    } else $including_char = '';
                    $max_value = (float)$max_value;
                    $fee = (float)$fee;
                    $new_max_value = number_format($max_value,2,'.','');
                    $new_fee = number_format($fee,2,'.','');
                    $fees_table[$index] = (((float)$new_max_value)==$max_value ? $new_max_value : $max_value).$including_char.':'
                        .(((float)$new_fee)==$fee ? $new_fee : $fee);
                }
                $input = str_replace($result_i[2],implode(', ',$fees_table),$input);
                $input = str_replace($result_i[1],trim($result_i[1]),$input);
            }
        }
        if (preg_match_all("#{foreach ([^}]*)}(.*){/foreach}#iU",$input,$result,PREG_SET_ORDER)) {
            foreach ($result as $result_i) {
                $input = str_replace($result_i[1],trim($result_i[1]),$input);
                $input = str_replace($result_i[2],trim($result_i[2]),$input);
            }
        }
        return str_replace(
            self::$COMPRESSED_STRINGS,
            self::$UNCOMPRESSED_STRINGS,
            $input
        );
    }

    public function parseProperty($input) {
        $value = $input==='false' || $input==='true' ? $input=='true' : str_replace('\"','"',preg_replace('/^(?:"|\')(.*)(?:"|\')$/s','$1',$input));
        return $value==='' ? null : $value;
    }

    public function cleanProperty(&$row, $key) {
        $input = $row[$key]['value'];
        if (is_string($input)) {
            $input = str_replace(array("\n"),array(''),$input);
            while (preg_match('/({TABLE |{SUM |{COUNT | DISTINCT | IN )/',$input,$resi)) {
                $input = str_replace($resi[0],strtolower($resi[0]),$input);
            }

            while (preg_match('/{{customVar code=([a-zA-Z0-9_-]+)}}/',$input,$resi)) {
                $input = str_replace($resi[0],'{customvar.'.$resi[1].'}',$input);
            }

            $regex = "{(weight|products_quantity|price_including_tax|price_excluding_tax|country)}";
            if (preg_match('/'.$regex.'/',$input,$resi)) {
                $this->addMessage('warning',$row,$key,'Usage of deprecated syntax %s','<span class="osh-formula">'.$resi[0].'</span>');
                while (preg_match('/'.$regex.'/',$input,$resi)) {
                    switch ($resi[1]) {
                        case 'price_including_tax':
                        case 'price_excluding_tax':
                        case 'weight':
                            $input = str_replace($resi[0],"{cart.".$resi[1]."}",$input);
                            break;
                        case 'products_quantity': $input = str_replace($resi[0],"{cart.quantity}",$input); break;
                        case 'country': $input = str_replace($resi[0],"{shipto.country.name}",$input); break;
                    }
                }
            }

            $regex1 = "{copy '([a-zA-Z0-9_]+)'\.'([a-zA-Z0-9_]+)'}";
            if (preg_match('/'.$regex1.'/',$input,$resi)) {
                $this->addMessage('warning',$row,$key,'Usage of deprecated syntax %s','<span class="osh-formula">'.$resi[0].'</span>');
                while (preg_match('/'.$regex1.'/',$input,$resi)) $input = str_replace($resi[0],'{'.$resi[1].'.'.$resi[2].'}',$input);
            }

            $regex1 = "{(count|all|any) (attribute|option) '([^'\)]+)' ?((?:==|<=|>=|<|>|!=) ?(?:".self::$FLOAT_REGEX."|true|false|'[^'\)]*'))}";
            $regex2 = "{(sum) (attribute|option) '([^'\)]+)'}";
            if (preg_match('/'.$regex1.'/',$input,$resi) || preg_match('/'.$regex2.'/',$input,$resi)) {
                $this->addMessage('warning',$row,$key,'Usage of deprecated syntax %s','<span class="osh-formula">'.$resi[0].'</span>');
                while (preg_match('/'.$regex1.'/',$input,$resi) || preg_match('/'.$regex2.'/',$input,$resi)) {
                    switch ($resi[1]) {
                        case 'count':    $input = str_replace($resi[0],"{count products where product.".$resi[2]."s.".$resi[3].$resi[4]."}",$input); break;
                        case 'all':        $input = str_replace($resi[0],"{count products where product.".$resi[2]."s.".$resi[3].$resi[4]."}=={products_quantity}",$input); break;
                        case 'any':        $input = str_replace($resi[0],"{count products where product.".$resi[2]."s.".$resi[3].$resi[4]."}>0",$input); break;
                        case 'sum':        $input = str_replace($resi[0],"{sum product.".$resi[2].".".$resi[3]."}",$input); break;
                    }
                }
            }

            $regex = "((?:{| )product.(?:attribute|option))s.";
            if (preg_match('/'.$regex.'/',$input,$resi)) {
                $this->addMessage('warning',$row,$key,'Usage of deprecated syntax %s','<span class="osh-formula">'.$resi[0].'</span>');
                while (preg_match('/'.$regex.'/',$input,$resi)) {
                    $input = str_replace($resi[0],$resi[1].'.',$input);
                }
            }

            $regex = "{table '([^']+)' (".self::$COUPLE_REGEX."(?:, *".self::$COUPLE_REGEX.")*)}";
            if (preg_match('/'.$regex.'/',$input,$resi)) {
                $this->addMessage('warning',$row,$key,'Usage of deprecated syntax %s','<span class="osh-formula">'.$resi[0].'</span>');
                while (preg_match('/'.$regex.'/',$input,$resi)) {
                    switch ($resi[1]) {
                        case 'products_quantity':
                            $input = str_replace($resi[0],"{table {cart.weight} in ".$resi[2]."}*{cart.quantity}",$input);
                            break;
                        default:
                            $input = str_replace($resi[0],"{table {cart.".$resi[1]."} in ".$resi[2]."}",$input);
                            break;
                    }
                }
            }
        }
        $row[$key]['value'] = $input;
    }

    protected static function json_decode($input)
    {
        if (function_exists('json_decode')) { // PHP >= 5.2.0
            $output = json_decode($input);
            if (function_exists('json_last_error')) { // PHP >= 5.3.0
                $error = json_last_error();
                if ($error!=JSON_ERROR_NONE) throw new Exception($error);
            }
            return $output;
        } else {
            return Zend_Json::decode($input);
        }
    }

    protected function _parseInput() {
        $config_string = str_replace(
            array('&gt;','&lt;','“','”',utf8_encode(chr(147)),utf8_encode(chr(148)),'&laquo;','&raquo;',"\r\n"),
            array('>','<','"','"','"','"','"','"',"\n"),
            $this->_input
        );

        if (substr($config_string,0,2)=='$$') $config_string = $this->uncompress(substr($config_string,2,strlen($config_string)));

        $config = self::json_decode($config_string);
        $config = (array)$config;

        $this->_config = array();
        $available_keys = array('type', 'label', 'enabled', 'fees', 'conditions', 'shipto', 'origin', 'customer_groups');
        $reserved_keys = array('*code');

        $deprecated_properties = array();
        $unknown_properties = array();

        foreach ($config as $code => $object) {
            $object = (array)$object;
            $row = array();
            $i = 1;
            foreach ($object as $property_name => $property_value)
            {
                if (in_array($property_name, $reserved_keys))
                    continue;
                if (in_array($property_name, $available_keys)
                    || substr($property_name, 0, 1)=='_'
                    || in_array($object['type'], array('data', 'meta')))
                {
                    if (isset($property_value))
                        $row[$property_name] = array('value' => $property_value, 'original_value' => $property_value);
                }
                else
                    if (!in_array($property_name, $unknown_properties)) $unknown_properties[] = $property_name;
                $i++;
            }
            $this->_addRow($row);
        }
        $row = null;
        if (count($unknown_properties)>0)
            $this->addMessage('error', $row, null, 'Usage of unknown properties %s', ': <span class=osh-key>'.implode('</span>, <span class=osh-key>', $unknown_properties).'</span>');
        if (count($deprecated_properties)>0)
            $this->addMessage('warning', $row, null, 'Usage of deprecated properties %s', ': <span class=osh-key>'.implode('</span>, <span class=osh-key>', $deprecated_properties).'</span>');
    }

    public function addMessage($type, &$row, $property) {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);
        $message = new DPDFrancePredict_Os_Message($type,$args);
        if (isset($row)) {
            if (isset($property)) {
                $row[$property]['messages'][] = $message;
            } else {
                $row['*messages'][] = $message;
            }
        }
        $this->_messages[] = $message;
        self::debug('   => <span class="osh-'.$message->type.'">'.$message->toString().'</span>',1);
    }

    protected function _addRow(&$row) {
        if (isset($row['code'])) {
            $key = $row['code']['value'];
            if (isset($this->_config[$key])) $this->addMessage('error',$row,'code','The property `code` must be unique, `%s` has been found twice',$key);
            while (isset($this->_config[$key])) $key .= rand(0,9);
            //$row['code'] = $key;
        } else {
            $i = 1;
            do {
                $key = 'code_auto'.sprintf('%03d',$i);
                $i++;
            } while (isset($this->_config[$key]));
        }
        $row['*code'] = $key;
        $this->_config[$key] = $row;
    }

    protected function _addIgnoredLines($lines) {
        $this->_config[] = array('lines' => $lines);
    }

    protected function _addressMatch($address_filter, $address) {
        $excluding = false;
        if (preg_match('# *\* *- *\((.*)\) *#s',$address_filter,$result)) {
            $address_filter = $result[1];
            $excluding = true;
        }

        $tmp_address_filter_array = explode(',',trim($address_filter));

        $concat = false;
        $concatened = '';
        $address_filter_array = array();
        $i = 0;

        foreach ($tmp_address_filter_array as $address_filter) {
            if ($concat) $concatened .= ','.$address_filter;
            else {
                if ($i<count($tmp_address_filter_array)-1 && preg_match('#\(#',$address_filter)) {
                    $concat = true;
                    $concatened .= $address_filter;
                } else $address_filter_array[] = $address_filter;
            }
            if (preg_match('#\)#',$address_filter)) {
                $address_filter_array[] = $concatened;
                $concatened = '';
                $concat = false;
            }
            $i++;
        }

        foreach ($address_filter_array as $address_filter) {
            if (preg_match('# *([A-Z]{2}) *(-)? *(?:\( *(-)? *(.*)\))? *#s',$address_filter,$result)) {
                $country_code = $result[1];
                if ($address['country_code']==$country_code) {
                    self::debug('      country code <span class="osh-replacement">'.$address['country_code'].'</span> matches',5);
                    if (!isset($result[4]) || $result[4]=='') return !$excluding;
                    else {
                        $region_codes = explode(',',$result[4]);
                        $in_array = false;
                        for ($i=count($region_codes); --$i>=0;) {
                            $code = trim($region_codes[$i]);
                            $region_codes[$i] = $code;
                            if ($address['region_code']===$code) {
                                self::debug('      region code <span class="osh-replacement">'.$address['region_code'].'</span> matches',5);
                                $in_array = true;
                            } else if ($address['postcode']===$code) {
                                self::debug('      postcode <span class="osh-replacement">'.$address['postcode'].'</span> matches',5);
                                $in_array = true;
                            } else if (strpos($code,'*')!==false && preg_match('/^'.str_replace('*','(?:.*)',$code).'$/',$address['postcode'])) {
                                self::debug('      postcode <span class="osh-replacement">'.$address['postcode'].'</span> matches <span class="osh-formula">'.$code.'</span>',5);
                                $in_array = true;
                            }
                            if ($in_array) break;
                        }
                        if (!$in_array) {
                            self::debug('      region code <span class="osh-replacement">'.$address['region_code'].'</span> and postcode <span class="osh-replacement">'.$address['postcode'].'</span> don\'t match',5);
                        }
                        // Vérification stricte
                        // $in_array = in_array($address['region_code'],$region_codes,true) || in_array($address['postcode'],$region_codes,true);
                        $excluding_region = $result[2]=='-' || $result[3]=='-';
                        if ($excluding_region && !$in_array || !$excluding_region && $in_array) return !$excluding;
                    }
                }
            }
        }
        return $excluding;
    }

    protected function _getProductProperty($product, $property_type, $property_name, $get_by_id=false) {
        switch ($property_type) {
            case 'attribute':
            case 'attributes': return $product->getAttribute($property_name,$get_by_id);
            case 'option':
            case 'options': return $product->getOption($property_name,$get_by_id);
            case 'stock': return $product->getStockData($property_name);
        }
        return null;
    }

    protected function _processProductProperty($products, $regex_result) {
        // COUNT, SUM or COUNT DISTINCT
        $operation = strtolower($regex_result[1]);
        switch ($operation) {
            case 'sum':
            case 'count distinct':
                $property_type = $regex_result[2];
                $property_name = $regex_result[3];
                $conditions = isset($regex_result[4]) ? $regex_result[4] : null;
                break;
            case 'count':
                $conditions = isset($regex_result[2]) ? $regex_result[2] : null;
                break;
        }

        self::debug('      :: start <span class="osh-replacement">'.$regex_result[0].'</span>',10);

        $return_value = 0;

        preg_match_all('/product\.(attribute(?:s)?|option(?:s)?|stock)\.([a-z0-9_]+)(?:\.(id))?/i',$conditions,$properties_regex_result,PREG_SET_ORDER);
        $properties = array();
        foreach ($properties_regex_result as $property_regex_result) {
            $key = $property_regex_result[0];
            if (!isset($properties[$key])) $properties[$key] = $property_regex_result;
        }

        foreach ($products as $product) {
            if (isset($conditions) && $conditions!='') {
                $formula = $conditions;
                foreach ($properties as $property) {
                    $value = $this->_getProductProperty(
                        $product,
                        $tmp_property_type = $property[1],
                        $tmp_property_name = $property[2],
                        $get_by_id = isset($property[3]) && $property[3]=='id'
                    );
                    //$formula = $this->replace($property[0],$value,$formula);
                    $from = $property[0];
                    $to = is_string($value) || empty($value) ? "'".$value."'" : $value;
                    $formula = str_replace($from,$to,$formula);
                    self::debug('         replace <span class="osh-replacement">'.$from.'</span> by <span class="osh-replacement">'.$to.'</span> =&gt; <span class="osh-formula">'.str_replace($from,'<span class="osh-replacement">'.$to.'</span>',$formula).'</span>',5);
                }
                $eval_result = $this->_evalFormula($formula);
                if (!isset($eval_result)) return 'null';
            }
            else $eval_result = true;

            if ($eval_result==true) {
                switch ($operation) {
                    case 'sum':
                        $value = $this->_getProductProperty($product,$property_type,$property_name);
                        //self::debug($product->getSku().'.'.$property_type.'.'.$property_name.' = "'.$value.'" x '.$product->getQuantity(),10);
                        $return_value += $value*$product->getQuantity();
                        break;
                    case 'count distinct':
                        if (!isset($distinct_values)) $distinct_values = array();
                        $value = $this->_getProductProperty($product,$property_type,$property_name);
                        if (!in_array($value,$distinct_values)) {
                            $distinct_values[] = $value;
                            $return_value++;
                        }
                        break;
                    case 'count':
                        $return_value += $product->getQuantity();
                        break;
                }
            }
        }

        self::debug('      :: end <span class="osh-replacement">'.$regex_result[0].'</span>',10);

        return $return_value;
    }
}

class DPDFrancePredict_Os_Message {
    public $type;
    public $message;
    public $args;

    public function __construct($type, $args) {
        $this->type = $type;
        $this->message = array_shift($args);
        $this->args = $args;
    }

    public function toString() {
        return vsprintf($this->message,$this->args);
    }
}

class DPDFrancePredict_Os_Result {
    public $success;
    public $result;

    public function __construct($success, $result=null) {
        $this->success = $success;
        $this->result = $result;
    }

    public function __toString() {
        return is_bool($this->result) ? ($this->result ? 'true' : 'false') : (string)$this->result;
    }
}

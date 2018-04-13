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

class DPDFrance_Relais_Model_Outputs extends Mage_Core_Model_Abstract
{
    private $_status = false;
    private $_result = '';
    private $_periodStatus = array();


    public function getPeriod($point)
    {
        $str = "";
        $date_day = "d ";
        $date_month = "M";

        if(Mage::helper ( 'dpdfrrelais' )->isBetweenClosingPeriod ( $point, 1 )) {
            $start = strtotime(Mage::helper ('dpdfrrelais')->convertdateformat($point['closing_period_start_date_1']));
            $end = strtotime(Mage::helper ('dpdfrrelais')->convertdateformat($point['closing_period_end_date_1']));
            $str .= date($date_day,$start).__(date($date_month,$start))." au ". date($date_day,$end).__(date($date_month,$end));
        }
        if(Mage::helper ( 'dpdfrrelais' )->isBetweenClosingPeriod ( $point, 2 )) {
            $start = strtotime(Mage::helper ('dpdfrrelais')->convertdateformat($point['closing_period_start_date_2']));
            $end = strtotime(Mage::helper ('dpdfrrelais')->convertdateformat($point['closing_period_end_date_2']));
            if($str) {
                $str .= " and ";
            }
            $str .= date($date_day,$start).__(date($date_month,$start))." au ". date($date_day,$end).__(date($date_month,$end));
        }
        if(Mage::helper ( 'dpdfrrelais' )->isBetweenClosingPeriod ( $point, 3 )) {
            $start = strtotime(Mage::helper ('dpdfrrelais')->convertdateformat($point['closing_period_start_date_3']));
            $end = strtotime(Mage::helper ('dpdfrrelais')->convertdateformat($point['closing_period_end_date_3']));
            if($str) {
                $str .= " and ";
            }
            $str .= date($date_day,$start).__(date($date_month,$start))." au ". date($date_day,$end).__(date($date_month,$end));
        }
        if($str) {
            $img = Mage::getDesign()->getSkinUrl('images/i_notice.gif');
            $words = __('Attention, this Pickup point will not be available from')." ".$str;
            $this->_result = <<<EOT
<div class="noticeICI"><img src="$img" />$words</div>
EOT;
        }
        return $this;
    }


    public function getStatus()
    {
        return  $this->_status;
    }

    public function getResult()
    {
        return $this->_result;
    }

    public function checkPeriodStatus($point, $num,$flag=false)
    {
        $period = 4*24*60*60;
        $start = strtotime(Mage::helper ('dpdfrrelais')->convertdateformat($point['closing_period_start_date_'.$num]));
        $end = strtotime(Mage::helper ('dpdfrrelais')->convertdateformat($point['closing_period_end_date_'.$num]));
        $closing = $end - $start;
        if($closing <= $period) {
            if($flag && $closing > 0) { Mage::log('period');
                if($this->_result) {
                    $this->_result .= " and ";
                }
                $this->_result .= date($date_day,$start).__(date($date_month,$start))." to ". date($date_day,$end).__(date($date_month,$end));

            }
            return true;
        }
        return false;
    }
}
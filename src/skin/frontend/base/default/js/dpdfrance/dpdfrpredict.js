/**
 * DPD France shipping module for Magento
 *
 * @category   DPDFrance
 * @package    DPDFrance_Shipping
 * @author     DPD France S.A.S. <ensavoirplus.ecommerce@dpd.fr>
 * @copyright  2016 DPD France S.A.S., société par actions simplifiée, au capital de 18.500.000 euros, dont le siège social est situé 9 Rue Maurice Mallet - 92130 ISSY LES MOULINEAUX, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 444 420 830
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

function radioCheck() {
    if (($$("input[id*='s_method_dpdfrpredict_']").length!=0) && ($("dpdfrpredict"))) {
        if ($$("input[id*='s_method_dpdfrpredict_']")[0].checked) {
            $("dpdfrpredict").show();
        } else {
            $("dpdfrpredict").hide();
        }
    }
    if ($$("input[id*='s_method_dpdfrrelais_']").length!=0) {
        if ($$("input[id*='s_method_dpdfrrelais_']")[0].checked) {
            $("dpdfrrelais").show();
        } else {
            $("dpdfrrelais").hide();
        }
    }
}

function in_array(search, array) {
    for (i = 0; i < array.length; i++) {
        if (array[i] == search ) {
            return false;
        }
    }
    return true;
}

function updateshipping(url) {
    if ($$("input[id*='s_method_dpdfrrelais_']").length!=0) {
        if ($$("input[id*='s_method_dpdfrrelais_']")[0].checked) {
            var radioGrp = document['forms']['co-shipping-method-form']['relay-point'];
            if (radioGrp) {
                for (i=0; i < radioGrp.length; i++) {
                    if (radioGrp[i].checked == true) {
                        var radioValue = radioGrp[i].value;
                    }
                }
            } else {
                if (radioValue==null) {
                    alert ("Por favor, selecione um ponto.");
                    return false;
                }
            }
            var shippingstring = new Array();
            if (radioValue) {
                shippingstring=radioValue.split("|||");
            } else {
                alert ("Por favor, selecione um ponto.");
                return false;
            }
        }
    }
    if (($$("input[id*='s_method_dpdfrpredict_']").length!=0) && ($("dpdfrpredict"))) {
        if ($$("input[id*='s_method_dpdfrpredict_']")[0].checked) {
            var regex = new RegExp(/^((\+33|0)[67])(?:[ _.-]?(\d{2})) {4}$/);
            var gsmDest = document.getElementById('gsm_dest');
            var numbers = gsmDest.value.substr(-8);
            var pattern = new Array('00000000','11111111','22222222','33333333','44444444','55555555','66666666','77777777','88888888','99999999','12345678','23456789','98765432');

            if (regex.test(gsmDest.value) && in_array(numbers, pattern)) {
                document.getElementById('dpdfrance_predict_error').style.display = 'none';
            } else {
                document.getElementById('dpdfrance_predict_error').style.display = 'block';
                return false;
            }
        }
    }
    shippingMethod.save();
}
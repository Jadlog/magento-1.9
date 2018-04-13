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
    if ($$("input[id*='s_method_dpdfrrelais_']").length!=0) {
        if ($$("input[id*='s_method_dpdfrrelais_']")[0].checked) {
            $("dpdfrrelais").show();
        } else {
            $("dpdfrrelais").hide();
        }
    }
    if (($$("input[id*='s_method_dpdfrpredict_']").length!=0) && ($("#dpdfrpredict"))) {
        if ($$("input[id*='s_method_dpdfrpredict_']")[0].checked) {
            $("#dpdfrpredict").show();
        } else {
            $("#dpdfrpredict").hide();
        }
    }
}


function fetchPoint(url,area) {
    var address = escape($("address").value);
    var zipcode = escape($("zipcode").value);
    var city = escape($("city").value);
    var vlDec = escape($("vlDec").value);
    var pesoCubadoTotal = escape($("pesoCubadoTotal").value);
    var cpfOrCnpj = escape($("cpfOrCnpj").value);
    
    new Ajax.Request(url,{
        method      :   'post',
        parameters  :   {address:address,zipcode:zipcode,city:city,vlDec:vlDec,pesoCubadoTotal:pesoCubadoTotal,cpfOrCnpj:cpfOrCnpj},
        onLoading   :   function() {
            $("loadingpointswait").show();
        },
        onComplete  :   function() {
            $("loadingpointswait").hide();
        },
        onSuccess   :   function(transport) {
            $(area).update(transport.responseText);
        }
    });
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
    if ($$("input[id*='s_method_dpdfrrelais_']").length!=0){
        if ($$("input[id*='s_method_dpdfrrelais_']")[0].checked) {
            var radioGrp = document['forms']['co-shipping-method-form']['relay-point'];
            if (radioGrp) {
            		if (radioGrp.length == null) {
            			var check = document.getElementById('relay-point0');
            			if(check.checked) {
            				var radioValue = check.value;
            			}
            		}
                for (i=0; i < radioGrp.length; i++) {
                    if (radioGrp[i].checked == true) {
                        var radioValue = radioGrp[i].value;
                    }
                }
            } else {
                if (radioValue==null) {
                    alert ("Por favor, selecione um ponto de entrega");
                    return false;
                }
            }
            var shippingstring = new Array();
            if(radioValue) {
                shippingstring=radioValue.split("|||");
            } else {
                alert ("Por favor, selecione um ponto de entrega");
                return false;
            }
        }
    }
    if (($$("input[id*='s_method_dpdfrpredict_']").length!=0) && ($("dpdfrpredict"))) {
        if ($$("input[id*='s_method_dpdfrpredict_']")[0].checked) {
            var regex = new RegExp(/^((\+33|0)[67])(?:[ _.-]?(\d{2})){4}$/);
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

function openDialog(id,mapid,lat,longti,baseurl) {
    Dialog.alert(document.getElementById(id).innerHTML, {className: "alphacube",  width:"60%", height:"90%", okLabel:"X"});
     //alert('breakpoint');
    initialize.delay(0.5,mapid,lat,longti,baseurl);
}

function initialize(mapid,lat,longti,baseurl) {
    var latlng = new google.maps.LatLng(lat, longti);
    var myOptions = {
        zoom: 15,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        styles:[{"featureType":"landscape","stylers":[{"visibility":"on"},{"color":"#ebebeb"}]},{"featureType":"poi.sports_complex","stylers":[{"visibility":"on"}]},{"featureType":"poi.attraction","stylers":[{"visibility":"off"}]},{"featureType":"poi.government","stylers":[{"visibility":"on"}]},{"featureType":"poi.medical","stylers":[{"visibility":"on"}]},{"featureType":"poi.place_of_worship","stylers":[{"visibility":"on"}]},{"featureType":"poi.school","stylers":[{"visibility":"on"}]},{"featureType":"water","elementType":"geometry","stylers":[{"visibility":"on"},{"color":"#d2e4f3"}]},{"featureType":"water","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"transit","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#ffffff"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"color":"#ebebeb"}]},{"elementType":"labels.text.fill","stylers":[{"visibility":"on"},{"color":"#666666"}]},{"featureType":"poi.business","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#dbdbdb"}]},{"featureType":"administrative.locality","elementType":"labels.text.fill","stylers":[{"visibility":"on"},{"color":"#999999"}]},{"featureType":"transit.station","stylers":[{"visibility":"on"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"visibility":"on"},{"color":"#dbdbdb"}]},{"elementType":"labels.icon","stylers":[{"visibility":"on"},{"saturation":-100}]},{"featureType":"road","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"transit.line","elementType":"labels.text","stylers":[{"visibility":"off"}]}]
    };
    var map = new google.maps.Map(document.getElementById(mapid), myOptions);
    var markericon = baseurl+'dpdfrance/front/relais/logo-max-png.png';
    var marker = new google.maps.Marker({
        position     : latlng,
        map          : map,
        animation    : google.maps.Animation.DROP,
        icon         : markericon
    });
}


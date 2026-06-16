/**
 * Prestaworks AB
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://license.prestaworks.se/license.html
 *
 * @author    Prestaworks AB <info@prestaworks.se>
 * @copyright Copyright Prestaworks AB (https://www.prestaworks.se/)
 * @license   http://license.prestaworks.se/license.html
 */

 $(document).ready(function() {
    
    window.q1Ready = function(q1) {
        q1.onPaymentDeclined(paymentDeclinedListener);
      
        q1.onShippingMethodChanged(function(shippingObject) {
            handleShippingMethodChanges(shippingObject);
        });
      
        q1.onCustomerInfoChanged(function(customerObject) {
            handleCustomerInfoChanges(customerObject);
        });
      
        q1.onCheckoutLoaded(qliroCheckoutHasLoaded);
      
        q1.onPaymentProcess(function onStart() {
            hideDynamicContent();
            $('#waiting-for-redirect').show();
            
            // $('html, body').animate({scrollTop: $(document).height() }, 'slow');
        });
      
        q1.onSessionExpired(function updateToken() {
            updateCustomerSession();
        });
    }
    
    $(document).on('click', '.btn-touchspin', function() { loading(1); });
    
    $(document).on('change', 'input:text.js-cart-line-product-quantity', function() { loading(1); });
    
    prestashop.on('updateCart', function (event) {
        if (event.reason == 'updateShipping') {
            return;
        }
        lockQliroOne();
        updateQliroAndDelivery();
    });
    
    $("#message").click(function() {
        $("#message_container").slideToggle();
        $("#message h1").toggleClass("svea-trigger--inactive");
    });

    $("#giftwrapping").click(function() {
        $("#giftwrapping_container").slideToggle();
        $("#giftwrapping h1").toggleClass("svea-trigger--inactive");
    });
    
    $("#savemessagebutton").on('click', function() {
        var order_message = $("#order_message").val();
        setOrderMessage(order_message);
    });

    $("#savegiftbutton").on('click', function() {
        var gift_message = $("#gift_message").val();
        setGiftMessage(gift_message);
    });
});

function loading(state) {
    if (state == 1) {
        $('#pwc-full-loader').show();
    } else {
        $('#pwc-full-loader').hide(); 
        
    }
}

function hideDynamicContent() {
    $("#dynamic_changes").hide();
    $(".card-block").hide();
    $(".card-block").empty();
    $("#dynamic_changes").empty();
    $("#dynamic_cart_row").hide();
    $("#dynamic_cart_row").empty(); 
}

function updateQliroAndDelivery() {
    updateDeliveryOptions();
    updateQliro();
    checkIfAllProductsAreInStock();
}

function updateDeliveryOptions() {
    // console.log('Called FAKE function');
}

function setOrderMessage(order_message) {
    
    lockQliroOne();
    
    $.ajax({
        type: 'GET',
        url: qlirocheckout_url,
        async: true,
        cache: false,
        dataType: 'json',
        data: 'ajax=1'
            +'&save_order_message'
            +'&message=' + encodeURI(order_message),
        success: function(jsonData) {
            $("#order_message").val(jsonData.message);
            if ($("#order_message").val() == '') {
                 $("#message_container").slideToggle();
                 $("#message h1").addClass("svea-trigger--inactive");
            }
            unlockQliroOne();
        }
    });
}

function checkIfAllProductsAreInStock()
{
    $.ajax({
        type: 'GET',
        url: qlirocheckout_url,
        async: true,
        cache: false,
        data: '&ajax=1'
            +'&checkIfAllProductsAreInStock',
        success: function(data) {
            if (data == 'NOK') {
                $('#changes_out_of_stock_qliro').hide();
                $('#pwc-availableproduct-div').show();
            } else {
                $('#changes_out_of_stock_qliro').show();
                $('#pwc-availableproduct-div').hide();
            }
        }
    });
}

function changeGift() {
    
    lockQliroOne();
    
    var gift = 0;
    var message = '';
    
    if ($('#gift').is(":checked")) {
        gift = 1;
        message = $("#gift_message").val();  
    }
    
    $.ajax({
        type: 'GET',
        url: qlirocheckout_url,
        async: true,
        cache: false,
        dataType: 'json',
        data: 'ajax=1'
            +'&save_gift'
            +'&change_gift'
            +'&gift=' + gift
            +'&gift_message=' + encodeURI(message),
        success: function(jsonData) {
            $("#gift_message").val(jsonData.message);
            updatePrestaCart();
            
            if (jsonData.gift == 0) {
                 $("#giftwrapping_container").slideToggle();
                 $("#giftwrapping h1").addClass("svea-trigger--inactive");
            }
        }
    });
}

function setGiftMessage(gift_message) {
    
    lockQliroOne();
    
    gift = 0;
    
    if ($('#gift').is(":checked")) {
        gift = 1; 
    }
    
    $.ajax({
        type: 'GET',
        url: qlirocheckout_url,
        async: true,
        cache: false,
        dataType: 'json',
        data: 'ajax=1'
            +'&save_gift'
            +'&change_message'
            +'&gift=' + gift
            +'&gift_message=' + encodeURI(gift_message),
        success: function(jsonData) {
            $("#gift_message").val(jsonData.message);
            updatePrestaCart();
            if (jsonData.gift == 1) {
                $("#uniform-gift span").addClass('checked');
                $('#gift').attr('checked', 'checked');
            }
        }
    });
}

function updatePrestaCart(reason) {
    reason = reason ? reason : 'orderChange';
    prestashop.emit('updateCart', {reason: reason, resp: reason});
}

function updateQliro() {
    
    lockQliroOne();
    
    $.ajax({
        type: 'GET',
		url: qlirocheckout_url,
		async: true,
		cache: false,
		data: {
            'ajax' : 1,
            'qliro_update' : 1,
            'ps_id_cart' : pw_ps_id_cart,
            'qliro_order_id' : pw_qliro_order_id
        },
		success: function(orderTotal) {
			if (orderTotal == 'stock') {
                location.reload();
            }
            if (orderTotal != 'FAILED') {
                window.q1.onOrderUpdated(function(order) {
                    
                    unlockQliroOne();
                    loading(0);
                    // if (order.totalPrice == orderTotal) {
                        // unlockQliroOne();
                    // }
                });
            } else {
                location.reload();
            }
        }
    });
}

function lockQliroOne() {
    window.q1.lock();
}

function unlockQliroOne() {
    window.q1.unlock();
}

function paymentDeclinedListener()
{
    return true;
}

function handleShippingMethodChanges(shippingObject)
{
    var method = shippingObject.method;
    
    $.ajax({
        type: 'POST',
        url: qlirocheckout_shipping_updates_url,
        async: true,
        cache: false,
        data: {
            'method' : method,
            'pw_id_cart' : pw_ps_id_cart
        },
        success: function() {
            updatePrestaCart('updateShipping');
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            console.log('An error occurred while handling shipping cost updates');
        }
    });
}

function handleCustomerInfoChanges(customerObject)
{
    // SOME STUFF
}

function qliroCheckoutHasLoaded()
{
    console.log('Qliro Checkout has loaded');
}

function updateCustomerSession()
{
    $.ajax({
        type: 'POST',
		url: qlirocheckout_url,
		async: true,
		cache: false,
		data: {
            'updateStatusOfCheckoutExpired' : '1',
            'qliro_order_id' : pw_qliro_order_id
        },
		success: function(result) {
            if (result == 'OK') {
                location.reload();
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
			if (textStatus !== 'abort') {
				alert("QLIRO CHECKOUT EXPIRED ERROR: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
            }
		}
    });
    
}

function updateHookShoppingCart(html)
{
	$('#HOOK_SHOPPING_CART').html(html);
}

function updateHookShoppingCartExtra(html)
{
	$('#HOOK_SHOPPING_CART_EXTRA').html(html);
}
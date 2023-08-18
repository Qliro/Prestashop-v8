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
    $('#close_cart').on('click', function() {
        $('.closeable').slideToggle();
        $('#close_cart_sign').toggleClass('icon-angle-down');
        $('#close_cart_sign').toggleClass('icon-angle-up');
    });
    $(".qliro-upsell-button").on("click", function() {
        let id_product = $(this).attr("data-id-product");
        let id_product_attribute = $(this).attr("data-id-product-attribute");
        qco.lock();
        $.ajax({
            type: "post",
            url: qliro_upsell_url,
            data: {
                id_product: id_product,
                id_product_attribute: id_product_attribute,
                ajax: true,
            },
            dataType: "json",
            success: function (response) {
                $([document.documentElement, document.body]).animate({
                    scrollTop: $("#qliro_thankyou_page").offset().top
                }, 1000);
                if (response.success) {
                    if (typeof qco != 'undefined') {
                        qco.getOrderUpdates();
                    }
                }
                window.setTimeout(function(){
                    qco.unlock();
                    $(".qliro-upsell").html(response.upsell_html);
                }, 2000);
                
            },
            error: function(a,b,c){
                window.setTimeout(function(){
                    qco.unlock();
                }, 2000);
            }
        });
    });
});
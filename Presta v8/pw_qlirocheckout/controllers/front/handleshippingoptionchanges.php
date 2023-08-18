<?php
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

class Pw_qlirocheckouthandleshippingoptionchangesModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = true;
    
    public function init()
    {
        $selected_method = Tools::getValue('method');
        $id_cart = Tools::getValue('pw_id_cart');
        
        $this->qliroChangeCartShippingInformation($selected_method, $id_cart);
    }
    
    public function qliroChangeCartShippingInformation($selected_method, $id_cart)
    {
        // GET AVAILABLE CARRRIERS
        $all_carriers = Carrier::getCarriers($this->context->language->id, true);
        $selected_id_carrier = 0;
        
        // GET THE RIGHT CARRIER ID
        foreach ($all_carriers as $key => $carrier) {
            if ($carrier['name'] == $selected_method) {
                $selected_id_carrier = $carrier['id_carrier'];
            }
        }
        
        // CHANGE DELIVERY OPTION FOR CARRIER
        if ($selected_id_carrier > 0 AND $id_cart > 0) {
            
            $cart = new Cart($id_cart);
            $cart->id_carrier = $selected_id_carrier;
            $cart->update();
            
            $new_delivery_options = array();
            $new_delivery_options[(int)$cart->id_address_delivery] = $cart->id_carrier.',';
            
            $new_delivery_options_serialized = json_encode($new_delivery_options);
            
            // $cart->setDeliveryOption($new_delivery_options_serialized);
            
            $update_sql = 'UPDATE '._DB_PREFIX_.'cart '.
                'SET delivery_option=\''.
                pSQL($new_delivery_options_serialized).
                '\' WHERE id_cart='.
                (int) $cart->id;
            Db::getInstance()->execute($update_sql);
        }
        
        die(json_encode($cart));
    }
}

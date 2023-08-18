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

class Pw_qlirocheckoutOrderavailableshippingmethodsModuleFrontController extends ModuleFrontController
{
    
    /* 
     * Qliro One handles the shipping interaction with the customer
     *
     * Calculate available shipping options after customer interaction
     *
     * Return shipping methods available after customer interactionl if any, or an error message if there are no shipping options available
     *
     */
     
    public function postProcess()
    {
        PrestaShopLogger::addLog('Qliro One: Update Shipping Methods called (in postProcess)', 1, null, null, null, true);
        
        $page = 'orderavailableshippingmethods';
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $body = json_decode(file_get_contents('php://input'), true);
            
            if (Tools::getIsset('id_cart') && Tools::getIsset('id_shop') && Tools::getIsset('token')) {
                $id_cart = (int)Tools::getValue('id_cart');
                $id_shop = (int)Tools::getValue('id_shop');
            
                // Retrieve token and make sure it is valid
                $token              = Tools::getValue('token');
                if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
                    $confirmation_token = $this->module->createConfirmationKey($id_cart, $id_shop, Configuration::get('QLIRO_API_PASSWORD_LIVE'));
                } else {
                    $confirmation_token = $this->module->createConfirmationKey($id_cart, $id_shop, Configuration::get('QLIRO_API_PASSWORD_TEST'));
                }
                
                if ($token != $confirmation_token) {
                    sleep(10);
                }
            
                $cart = new Cart($id_cart);
                
                $merchant_reference = $body['MerchantReference'];
                $qliro_order_id     = $body['OrderId'];
                
                $id_country = Db::getInstance()->getValue("SELECT ps_country_iso
                                                        FROM "._DB_PREFIX_."qlirocheckout
                                                        WHERE qliro_merchant_reference = '".$merchant_reference."'
                                                            AND qliro_order_id = ".$qliro_order_id."
                                                            AND ps_id_shop = ".$id_shop."
                                                            AND ps_id_cart = ".$id_cart."");
                
                if ($id_country != '') {
                    $iso_code = $id_country;
                } else {
					$iso_code = 'SE';
				}
                
				$available_shipping_methods = $this->module->getQliroAvailableShippingMethods($cart, $iso_code);
                
                $error_message = 'PostalCodeIsNotSupported';
                $declineReason = array(
                    'DeclineReason' => $error_message
                );
                
                if (is_array($available_shipping_methods) AND !empty($available_shipping_methods)) {
                    $this->module->response(200, $page, array('AvailableShippingMethods' => $available_shipping_methods));
                } else {
                    $this->module->response(400, $page, $declineReason);
                }
            }
        }
    }
}
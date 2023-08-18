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

class Pw_qlirocheckoutOrdervalidationModuleFrontController extends ModuleFrontController
{
    
    /* 
     * Qliro One validate order before submit
     *
     * Return 200 or 400 and error reason (outOfStock)
     *
     */
     
    public function postProcess()
    {
        PrestaShopLogger::addLog('Qliro One: Order Validation URL called (in postProcess)', 1, null, null, null, true);
        
        $page = 'ordervalidation';
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $body = json_decode(file_get_contents('php://input'), true);
            PrestaShopLogger::addLog('Qliro One: Order Validation body: ' . json_encode($body));
            
            if (Tools::getIsset('id_cart') && Tools::getIsset('id_shop') && Tools::getIsset('token')) {
                $id_cart = (int)Tools::getValue('id_cart');
                $id_shop = (int)Tools::getValue('id_shop');
            
                // Retrieve token and make sure it is valid
                $token              = Tools::getValue('token');
                $token = Tools::getValue('token');
                if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
                    $confirm_token = $this->module->createConfirmationKey($id_cart, $id_shop, Configuration::get('QLIRO_API_PASSWORD_LIVE'));
                } else {
                    $confirm_token = $this->module->createConfirmationKey($id_cart, $id_shop, Configuration::get('QLIRO_API_PASSWORD_TEST'));
                }
                
                if ($token != $confirm_token) {
                    PrestaShopLogger::addLog('Order Validation for cartID: ' . $id_cart . '. Declined, token does not match');
                    $this->module->resonse(400, $page, [
                        'DeclineReason' => 'Other',
                    ]);
                }
            
                $cart = new Cart($id_cart);
                
                $all_in_stock = true;
                $check_cart = $cart->checkQuantities(false);
                
                if (!$check_cart) {
                    $all_in_stock = false;
                }
                
                $error_message = 'OutOfStock';
                $declineReason = array(
                    'DeclineReason' => $error_message
                );
                
                PrestaShopLogger::addLog('Qliro One: Order Validation URL called (in postProcess) '.$all_in_stock, 1, null, null, null, true);
                
                if ($all_in_stock) {
                    $this->module->response(200, $page);
                } else {
                    $this->module->response(400, $page, $declineReason);
                }
            }
        }
    }
}
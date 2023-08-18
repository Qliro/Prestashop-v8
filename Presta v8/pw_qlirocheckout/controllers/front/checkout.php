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

use PrestaShop\PrestaShop\Adapter\Cart\CartPresenter;

class Pw_qlirocheckoutCheckoutModuleFrontController extends ModuleFrontController
{
    public function setMedia()
    {
        parent::setMedia();
        
        $this->context->controller->addCSS(_MODULE_DIR_.'pw_qlirocheckout/views/css/qlirocheckout_style.css', 'all');
        $this->context->controller->addJS(_MODULE_DIR_.'pw_qlirocheckout/views/js/qlirocheckout.js');
        
        Media::addJsDef(array('qlirocheckout_url' => $this->context->link->getModuleLink($this->module->name, 'checkout', array(), Tools::usingSecureMode())));
        Media::addJsDef(array('pw_qliro_checkout_back_to_shop_url' => $this->context->shop->getBaseURL()));
        Media::addJsDef(array('pw_ps_id_cart' => $this->context->cart->id));
    }
    
    public function postProcess()
    {
        // EXTERNAL .PHP FILE HANDLES ALL POST PROCESS
        require_once dirname(__FILE__).'/../../libraries/generalpostprocess.php';
    }
    
    public function initContent()
    {
        parent::initContent();
        
        $cart = $this->context->cart;
        
        if (!isset($cart->id)) {
            Tools::redirect($this->context->shop->getBaseURL());
        }
        
        $this->context->smarty->assign(array(
            'pw_ps_id_cart' => $this->context->cart->id
        ));
        
        $currency  = new Currency($cart->id_currency);
        
        $id_address_delivery = $cart->id_address_delivery;
        
        // IF NO ADDRESS DELIVERY IS SET, DEFAULT TO DEFAULT COUNTRY
        if ($id_address_delivery > 0) {
            $qliro_address = new Address($id_address_delivery);
        
            $id_country_delivery = $qliro_address->id_country;
            $qliro_country = new Country($id_country_delivery, $cart->id_lang);
        } else {
            $qliro_country = new Country((int)Configuration::get('PS_COUNTRY_DEFAULT'), $cart->id_lang);
        }
        
        // RETURNS CURRENCY ISO CODE AND COUNTRY ISO CODE
        $qliro_settings = $this->module->getQliroCountryInformation($currency->iso_code, $qliro_country->iso_code);
        
        // THIS FILE WILL DOUBLE CHECK ALL SETTINGS
        require_once dirname(__FILE__).'/../../libraries/qliroredirectcheck.php';
        
        $cart = $this->context->cart;
        
        $currency  = new Currency($cart->id_currency);
        $language  = new Language($cart->id_lang);
        
        $id_address_delivery = $cart->id_address_delivery;
        $qliro_address       = new Address($id_address_delivery);
        
        $id_country_delivery = $qliro_address->id_country;
        $qliro_country       = new Country($id_country_delivery, $cart->id_lang);
        
        // AJAX
        if (Tools::getIsset('ajax') && (int)Tools::getValue('ajax') == 1) {
            $this->ajax = true;
        } else {
            $this->ajax = false; 
        }
        
        // QLIRO ACTIVE
        if ((int)(Configuration::get('QLIRO_ACTIVE')) == 0) {
            if ($this->ajax) {
                die('0');
            }
            
            Tools::redirect('index.php?controller=order');
        }
        
        // CART EXISTS
        if (!isset($this->context->cart->id)) {
            if ($this->ajax) {
                die('0');
            }
            
            Tools::redirect($this->context->shop->getBaseURL());
        }
        
        // ORDER ALREADY EXISTS
        if ($this->context->cart->orderExists()) {
            if ($this->ajax) {
                die('0');
            }
            
            Tools::redirect($this->context->shop->getBaseURL());
        }
        
        // ITEMS IN CART
        if ($this->context->cart->nbProducts() < 1) {
            if ($this->ajax) {
                die('stock');
				// die('0');
            }
            
            Tools::redirect($this->context->shop->getBaseURL());
        }
        
        if (!$this->context->cart->checkQuantities()) {
            $this->context->smarty->assign(array(
                'pwc_available_product' => 'no'
            ));
        } else {
            $this->context->smarty->assign(array(
                'pwc_available_product' => 'yes'
            ));
        }
        
        $two_columns_layout = (int)Configuration::get('QLIRO_TWO_COLUMNS');
        $this->context->smarty->assign(array(
            'two_columns_layout' => $two_columns_layout
        ));

        // CHECK CURRENCY
        if ($currency->iso_code != 'SEK' AND $currency->iso_code != 'NOK' AND $currency->iso_code != 'DKK' AND $currency->iso_code != 'EUR' AND $currency->iso_code != 'GBP') {
            if ($this->ajax) {
                die('0');
            }
            
            Tools::redirect('index.php?controller=order');
        }
        
        // IF AJAX
        if (isset($this->ajax) && $this->ajax) {
            
            if (Tools::isSubmit('qliro_update')) {
                $qliro_order_id = Tools::getValue('qliro_order_id');
                $ps_id_cart     = Tools::getValue('ps_id_cart');
                
                // GET QLIRO CHECKOUT INFORMATION
                $checkout_information = $this->module->getBasicCheckouApiInformation($cart, $qliro_settings['purchase_country'], $currency->iso_code, $language->iso_code, true);
                
                // UPDATE QLIRO ONE CHECKOUT
                $updated_successfully = $this->module->updateQliroOneCheckout($cart->id, $checkout_information, $qliro_order_id);
                
                if ($updated_successfully == 200 OR $updated_successfully == 201) {
                    die($cart->getOrderTotal(true, Cart::ONLY_PRODUCTS) - $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS) + $cart->getOrderTotal(true, Cart::ONLY_WRAPPING));
                    die($cart->getOrderTotal());
                } else {
                    die('FAILED');
                }
            }
            
            if (Tools::isSubmit('updateStatusOfCheckoutExpired')) {
                $qliro_order_id = Tools::getValue('qliro_order_id');
                
                $response = $this->module->updateQliroCheckoutStatusInDatabase($qliro_order_id);
                
                die($response);
            }
            
            // AJAX Save message
            if (Tools::isSubmit('save_order_message')) {
                $messageContent = urldecode(Tools::getValue('message'));
                $this->updateMessage($messageContent, $cart);
                die(json_encode(Message::getMessageByCartId($cart->id)));
            }
            
            // AJAX Save gift
            if (Tools::isSubmit('save_gift')) {
                $message = urldecode(Tools::getValue('gift_message'));
                $gift    = (int)Tools::getValue('gift');
                
                if (Tools::isSubmit('change_gift')) {
                    $cart->gift = $gift;
                    if ($gift == 0) {
                        $message = '';
                    }
                } else if (Tools::isSubmit('change_message')) {
                    $cart->gift = 1;
                }  
                if (Validate::isMessage($message)) {
                    $cart->gift_message = strip_tags($message);
                } else {
                    $cart->gift_message = '';
                }
                $cart->update();
                $result = array(
                    'gift'    => $cart->gift,
                    'message' => $cart->gift_message,
                );
                die(json_encode($result));
            }
            
            if (Tools::isSubmit('checkIfAllProductsAreInStock')) {
                if (!$cart->checkQuantities()) {
                    die('NOK');
                } else {
                    die('OK');
                }
            }
            
            exit;
        }
        
        // SHOW COUNTRIES IN QLIRO ONE CHECKOUT (CURRENCY IS DETERMINED BY COUNTRY)
        $countries = Country::getCountries($this->context->language->id, true);
        $number_of_countries = 0;
        $number_of_countries = @count($countries);
        
        $qliro_selected_country = 0;
        if ((int)Tools::getValue('qliro_country') > 0) {
            $qliro_selected_country = (int)Tools::getValue('qliro_country');
        } else {
            $qliro_selected_country = $qliro_country->iso_code;
        }
        
        $this->context->smarty->assign(array(
            'qliro_countries'        => $countries,
            'qliro_selected_country' => $qliro_selected_country,
            'number_of_countries'    => $number_of_countries
        ));
                    
        // GET PS CART
        $presenter = new CartPresenter();
        $presented_cart = $presenter->present($cart, true);
        
        $this->context->smarty->assign([
            'pw_cart' => $presented_cart,
            'static_token' => Tools::getToken(false),
        ]);
        
        $wrapping_fees_tax_inc = $cart->getGiftWrappingPrice(true);
        
        $this->context->smarty->assign(array(
            'message'                  => Message::getMessageByCartId($cart->id),
            'giftAllowed'              => (int)(Configuration::get('PS_GIFT_WRAPPING')),
            'gift_message'             => $cart->gift_message,
            'discounts'                => $cart->getCartRules(),
            'gift'                     => $cart->gift,
            'gift_wrapping_price'      => Tools::convertPrice($wrapping_fees_tax_inc, new Currency($currency->iso_code)),
            'qliro_show_order_message' => (int)Configuration::get('QLIRO_SHOW_ORDER_MESSAGE')
        ));
        
        
        // QLIRO CHECKOUT CREATION ETC. STARTS HERE
        
        // CHECK IF THERE IS A QLIRO ONE CHECKOUT CREATED (DO NOT USE STAFF ABOVE BECAUSE MERCHANT REFERENCE WILL NOT BE THE SAME)
        $qliro_checkout = $this->module->getQliroCheckout($cart->id, null, $qliro_settings['purchase_country'], $qliro_settings['purchase_currency']);
        
        if ($qliro_checkout) {
            $checkout_information = $this->module->getBasicCheckouApiInformation($cart, $qliro_settings['purchase_country'], $currency->iso_code, $language->iso_code, true);
            
            // FIX
            $response_qliro = json_decode($qliro_checkout['response'], JSON_PRETTY_PRINT);
            $order_id = $response_qliro['OrderId'];
            // FIX
            
            // $updated_successfully_at_reload = $this->module->updateQliroOneCheckout($cart->id, $checkout_information, null, $qliro_country_information['purchase_country'], $qliro_country_information['purchase_currency']);
            $updated_successfully_at_reload = $this->module->updateQliroOneCheckout($cart->id, $checkout_information, $order_id, $qliro_settings['purchase_country'], $qliro_settings['purchase_currency']);
            
            if ($updated_successfully_at_reload == 400 OR $updated_successfully_at_reload == 500) {
                PrestaShopLogger::addLog('Qliro One: Unable to update QLIRO One Checkout', 1, null, null, null, true);
                $this->context->smarty->assign(array(
                    'pw_qliro_one_checkout_has_error'     => true,
                    'pw_qliro_one_checkout_error_message' => $this->module->l('Unable to update Qliro Checkout')
                ));
            }
        } else {
            // CREATE QLIRO ONE CHECKOUT
            $checkout_information = $this->module->getBasicCheckouApiInformation($cart, $qliro_settings['purchase_country'], $qliro_settings['purchase_currency'], $language->iso_code, false);
            
            $qliro_checkout = $this->module->createQliroCheckout($checkout_information);
            
            // IF CREATION SUCCESSFULL
            if (is_array($qliro_checkout) AND ($qliro_checkout['response_code'] == 200 OR $qliro_checkout['response_code'] == 201)) {
                
                $qliro_order_id           = $qliro_checkout['response']->OrderId;
                $qliro_merchant_reference = $checkout_information['MerchantReference'];
                
                $this->module->saveQliroCheckoutInformationInDbAtCreation($cart->id, $qliro_order_id, $qliro_merchant_reference, $qliro_settings['purchase_country'], $qliro_settings['purchase_currency']);
            } else {
                $this->context->smarty->assign(array(
                    'pw_qliro_one_checkout_has_error' => true,
                    'pw_qliro_one_checkout_error_message' => $qliro_checkout['response']
                ));
                
                return $this->setTemplate('module:pw_qlirocheckout/views/templates/front/qlirocheckout.tpl');
            }
        }
        
        // GET CHECKOUT FROM QLIRO
        $qliro_checkout = $this->module->getQliroCheckout($cart->id, null, $qliro_settings['purchase_country'], $qliro_settings['purchase_currency']);
       
        if (is_array($qliro_checkout) AND ($qliro_checkout['response_code'] == 200 OR $qliro_checkout['response_code'] == 201)) {
            
            $qliro_response = $qliro_checkout['response'];
            $qliro_response = json_decode($qliro_response);
            
            if ($qliro_response->CustomerCheckoutStatus != 'InProcess') {
                unset($this->context->cookie->id_cart, $cart, $this->context->cart);
                $this->context->cart = new Cart();
                $this->context->smarty->assign(array(
                    'cart_qties' => 0,
                    'cart' => $this->context->cart
                ));
                Tools::redirect($this->context->shop->getBaseURL());
            }
            
            $this->module->updateQliroCheckoutInDb($cart->id, $qliro_response->OrderId, $qliro_response->MerchantReference, $qliro_settings['purchase_country'], $qliro_settings['purchase_currency']);
            
            $this->context->smarty->assign(array(
                'pw_qliro_one_checkout_has_error' => false,
                'pw_qliro_one_checkout_snippet' => $qliro_response->OrderHtmlSnippet,
                'pw_qliro_order_id' => $qliro_response->OrderId
            ));
        } else {
            $this->context->smarty->assign(array(
                'pw_qliro_one_checkout_has_error' => true,
                'pw_qliro_one_checkout_error_message' => $this->l('An error occurred while creating the checkout')
            ));
        }
        
        return $this->setTemplate('module:pw_qlirocheckout/views/templates/front/qlirocheckout.tpl');
    }
    
    public function updateMessage($messageContent, $cart)
    {
        if ($messageContent) {
            if (!Validate::isMessage($messageContent)) {
                return false;
            } elseif ($oldMessage = Message::getMessageByCartId((int) ($cart->id))) {
                $message = new Message((int) ($oldMessage['id_message']));
                $message->message = $messageContent;
                $message->update();
            } else {
                $message = new Message();
                $message->message = $messageContent;
                $message->id_cart = (int) ($cart->id);
                $message->id_customer = (int) ($cart->id_customer);
                $message->add();
            }
        } else {
            if ($oldMessage = Message::getMessageByCartId((int) ($cart->id))) {
                $message = new Message((int) ($oldMessage['id_message']));
                $message->delete();
            }
        }

        return true;
    }
}
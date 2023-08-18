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
        
// CHANGE OF COUNTRY
if (Tools::isSubmit('qliro_change_country')) {
    $id_lang = 0;
    $id_currency = 0;
    
    // IF SWEDEN
    if (Tools::getValue('qliro_change_country') == 'SE') {
        $id_lang      = Language::getIdByIso('sv');
        $id_currency  = Currency::getIdByIsoCode('SEK');
        
        $id_address_delivery = Configuration::get('QLIRO_SE_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress('SE', 'QLIRO_SE_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_SE_ADDRESS');
    }
    
    // IF NORWAY
    if (Tools::getValue('qliro_change_country') == 'NO') {
        $id_lang     = Language::getIdByIso('no');
        $id_currency = Currency::getIdByIsoCode('NOK');
        
        $id_address_delivery = Configuration::get('QLIRO_NO_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress('NO', 'QLIRO_NO_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_NO_ADDRESS');
    }
    
    // IF DENMARK
    if (Tools::getValue('qliro_change_country') == 'DK') {
        $id_lang     = Language::getIdByIso('da');
        $id_currency = Currency::getIdByIsoCode('DKK');
        
        $id_address_delivery = Configuration::get('QLIRO_DK_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress('DK', 'QLIRO_DK_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_DK_ADDRESS');
    }
    
    // IF FINLAND
    if (Tools::getValue('qliro_change_country') == 'FI') {
        $id_lang     = Language::getIdByIso('fi');
        $id_currency = Currency::getIdByIsoCode('EUR');
        
        $id_address_delivery = Configuration::get('QLIRO_FI_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress('FI', 'QLIRO_FI_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_FI_ADDRESS');
    }
    
    // OTHER COUNTRIES THAT ARE NOT SWEDEN, NORWAY, DENMARK, FINLAND
    if (Tools::getValue('qliro_change_country') != 'SE' AND Tools::getValue('qliro_change_country') != 'NO' AND Tools::getValue('qliro_change_country') != 'DK' AND Tools::getValue('qliro_change_country') != 'FI') {
        $id_lang = $this->context->language->id;
        $country = new Country(Country::getByIso(Tools::getValue('qliro_change_country')));
        
        $id_currency = $country->id_currency;
        
        $id_address_delivery = Configuration::get('QLIRO_'.Tools::getValue('qliro_change_country').'_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress(Tools::getValue('qliro_change_country'), 'QLIRO_'.Tools::getValue('qliro_change_country').'_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_'.Tools::getValue('qliro_change_country').'_ADDRESS');
    }
	
    if ($id_lang > 0 and $id_currency > 0) {
        $_GET['id_lang'] = $id_lang;
        
        $_POST['id_lang']       = $id_lang;
        $_POST['id_currency']   = $id_currency;
        $_POST['SubmitCurrency'] = $id_currency;
        
        Tools::switchLanguage();
        Tools::setCurrency($this->context->cookie);
        
        $this->context->cart->id_lang     = $id_lang;
        $this->context->cart->id_currency = $id_currency;
        
        $this->context->cart->id_address_delivery = $id_address_delivery;
        $this->context->cart->id_address_invoice  = $id_address_delivery;
        
        $this->context->cart->update();
        
        $update_sql = "UPDATE "._DB_PREFIX_."cart_product
                    SET id_address_delivery=".(int)$id_address_delivery."
                    WHERE id_cart=".(int)$this->context->cart->id;
                    
        Db::getInstance()->execute($update_sql);
        
        $link = $this->context->link->getModuleLink($this->module->name, 'checkout', array(), Tools::usingSecureMode());
        Tools::redirect($link);
    }
}

// GIFT
if (Tools::isSubmit('savegift')) {
    $this->context->cart->gift = (int) (Tools::getValue('gift'));
    $gift_error = '';
    if (!Validate::isMessage($_POST['gift_message'])) {
        $gift_error = Tools::displayError('Invalid gift message');
    } else {
        $this->context->cart->gift_message = strip_tags(Tools::getValue('gift_message'));
    }
    $this->context->cart->update();
    $this->context->smarty->assign('gift_error', $gift_error);
}

// ORDER MESSAGE
if (Tools::isSubmit('savemessagebutton')) {
    $messageContent = Tools::getValue('message');
    $message_result = $this->updateMessage($messageContent, $this->context->cart);
    if (!$message_result) {
        $this->context->smarty->assign('gift_error', Tools::displayError('Invalid message'));
    }
}
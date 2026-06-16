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

// RETURN FALSE IF CURRENCY IS NOT ONE OF THE CURRENCIES THAT QLIRO ONE CHECKOUT SUPPORTS
if ($qliro_settings === false) {
    Tools::redirect('index.php?controller=order&step=1');
}

$tmp_address = new Address((int)$this->context->cart->id_address_delivery);
$country     = new Country($tmp_address->id_country);

// IF THE COUNTRY IS ONE OF THE COUNTRY CONFIGURED BUT THE DELIVERY ADDRESS DOES NOT CORRESPOND, CHANGE THE DELIVERY ADDRESS
if ($qliro_settings['purchase_country'] == 'SE') {
    if ($country->iso_code != 'SE') {
        if ($this->context->cart->id_address_delivery == Configuration::get('QLIRO_SE_ADDRESS')) {
            $this->module->installAddress('SE', 'QLIRO_SE_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_SE_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress('SE', 'QLIRO_SE_ADDRESS');
        }

        $id_address_delivery = Configuration::get('QLIRO_SE_ADDRESS');

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
    
} elseif ($qliro_settings['purchase_country'] == 'NO') {
    if ($country->iso_code != 'NO') {
        if ($this->context->cart->id_address_delivery == Configuration::get('QLIRO_NO_ADDRESS')) {
            $this->module->installAddress('NO', 'QLIRO_NO_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_NO_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress('NO', 'QLIRO_NO_ADDRESS');
        }

        $id_address_delivery = Configuration::get('QLIRO_NO_ADDRESS');

        $this->context->cart->id_address_delivery = $id_address_delivery;
        $this->context->cart->id_address_invoice  = $id_address_delivery;
        $this->context->cart->update();
        
        $update_sql = "UPDATE "._DB_PREFIX_."cart_product
                    SET id_address_delivery=".(int)$id_address_delivery."
                    WHERE id_cart=".(int)$this->context->cart->id;
        
        Db::getInstance()->execute($update_sql);
        
        Tools::redirect('index.php?fc=module&module=pw_qlirocheckout&controller=checkout');
    }
    
} elseif ($qliro_settings['purchase_country'] == 'DK') {
    if ($country->iso_code != 'DK') {
        if ($this->context->cart->id_address_delivery == Configuration::get('QLIRO_NO_ADDRESS')) {
            $this->module->installAddress('DK', 'QLIRO_DK_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_NO_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress('DK', 'QLIRO_DK_ADDRESS');
        }

        $id_address_delivery = Configuration::get('QLIRO_DK_ADDRESS');

        $this->context->cart->id_address_delivery = $id_address_delivery;
        $this->context->cart->id_address_invoice  = $id_address_delivery;
        $this->context->cart->update();
        
        $update_sql = "UPDATE "._DB_PREFIX_."cart_product
                    SET id_address_delivery=".(int)$id_address_delivery."
                    WHERE id_cart=".(int)$this->context->cart->id;
        
        Db::getInstance()->execute($update_sql);
        
        Tools::redirect('index.php?fc=module&module=pw_qlirocheckout&controller=checkout');
    }
    
} elseif ($qliro_settings['purchase_country'] == 'FI') {
    if ($country->iso_code != 'FI') {
        if ($this->context->cart->id_address_delivery == Configuration::get('QLIRO_FI_ADDRESS')) {
            $this->module->installAddress('FI', 'QLIRO_FI_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_NO_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress('FI', 'QLIRO_FI_ADDRESS');
        }

        $id_address_delivery = Configuration::get('QLIRO_FI_ADDRESS');

        $this->context->cart->id_address_delivery = $id_address_delivery;
        $this->context->cart->id_address_invoice  = $id_address_delivery;
        $this->context->cart->update();
        
        $update_sql = "UPDATE "._DB_PREFIX_."cart_product
                    SET id_address_delivery=".(int)$id_address_delivery."
                    WHERE id_cart=".(int)$this->context->cart->id;
        
        Db::getInstance()->execute($update_sql);
        
        Tools::redirect('index.php?fc=module&module=pw_qlirocheckout&controller=checkout');
    }
} else {
    $purchase_country = $qliro_settings['purchase_country'];
    $country_iso_code = $country->iso_code;
    
    if ($purchase_country != $country_iso_code) {
        if ($this->context->cart->id_address_delivery == Configuration::get('QLIRO_'.$purchase_country.'_ADDRESS')) {
            $this->module->installAddress($purchase_country, 'QLIRO_'.$purchase_country.'_ADDRESS');
        }
        
        $id_address_delivery = Configuration::get('QLIRO_'.$purchase_country.'_ADDRESS');
        
        if (!isset($id_address_delivery) OR ($id_address_delivery <= 0)) {
            $this->module->installAddress($purchase_country, 'QLIRO_'.$purchase_country.'_ADDRESS');
        }

        $id_address_delivery = Configuration::get('QLIRO_'.$purchase_country.'_ADDRESS');

        $this->context->cart->id_address_delivery = $id_address_delivery;
        $this->context->cart->id_address_invoice  = $id_address_delivery;
        $this->context->cart->update();
        
        $update_sql = "UPDATE "._DB_PREFIX_."cart_product
                    SET id_address_delivery=".(int)$id_address_delivery."
                    WHERE id_cart=".(int)$this->context->cart->id;
        
        Db::getInstance()->execute($update_sql);
        
        Tools::redirect('index.php?fc=module&module=pw_qlirocheckout&controller=checkout');
    }
}
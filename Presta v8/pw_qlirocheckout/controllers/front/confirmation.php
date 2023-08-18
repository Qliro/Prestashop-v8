<?php

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

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

class Pw_qlirocheckoutConfirmationModuleFrontController extends ModuleFrontController
{
    public $display_column_left  = false;
    public $display_column_right = false;
    
    public function setMedia()
    {
        parent::setMedia();
        
        $this->context->controller->addCSS(_MODULE_DIR_.'pw_qlirocheckout/views/css/confirmation.css', 'all');
        $this->addJS(_MODULE_DIR_.'pw_qlirocheckout/views/js/confirmation.js');
        Media::addJsDef([
            'qliro_upsell_url' => $this->context->link->getModuleLink('pw_qlirocheckout', 'upsell', [
                'id_cart' => Tools::getValue('id_cart'),
                'country_iso'  => Tools::getValue('country_iso'),
                'currency_iso' => Tools::getValue('currency_iso'),
                'token' => Tools::getValue('token'),
            ]),
        ]);
    }

    public function init()
    {
        parent::init();
    }
    
    public function initContent()
    {
        parent::initContent();
        
        $page = 'confirmation';
        
        // IS QLIRO ACTIVE
        if ((int)(Configuration::get('QLIRO_ACTIVE')) == 0) {
            Tools::redirect($this->context->shop->getBaseURL());
        }

        if (isset($_GET) && Tools::getIsset('id_cart') && Tools::getIsset('id_shop') && Tools::getIsset('token') && Tools::getIsset('country_iso') && Tools::getIsset('currency_iso')) {
            
            $id_cart = (int)Tools::getValue('id_cart');
            $id_shop = (int)Tools::getValue('id_shop');
            
            $country_iso  = Tools::getValue('country_iso');
            $currency_iso = Tools::getValue('currency_iso');
            
            $cart = new Cart($id_cart);
            
            if ($cart->OrderExists()) {
                //Tools::redirect($this->context->shop->getBaseURL());
            }
            
            $token = Tools::getValue('token');
            if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
                $confirm_token = $this->module->createConfirmationKey($id_cart, $id_shop, Configuration::get('QLIRO_API_PASSWORD_LIVE'));
            } else {
                $confirm_token = $this->module->createConfirmationKey($id_cart, $id_shop, Configuration::get('QLIRO_API_PASSWORD_TEST'));
            }
            if ($token != $confirm_token) {
                Tools::redirect($this->context->shop->getBaseURL());
            }
            
            $qliro_one_checkout_information = $this->module->getQliroCheckout($id_cart, null, $country_iso, $currency_iso, false);//true);
            
            if (!$qliro_one_checkout_information) {
                $this->errors = array('Something went wrong');
                $this->context->smarty->assign(array(
                    'HOOK_ORDER_CONFIRMATION' => null
                ));
            
                return $this->setTemplate('module:pw_qlirocheckout/views/templates/front/confirmation_error.tpl');
            }
            
            $qliro_one_checkout_information = json_decode($qliro_one_checkout_information['response']);
            
            $qliro_customer_checkout_status = $qliro_one_checkout_information->CustomerCheckoutStatus;
            $qliro_one_checkout_order_id    = $qliro_one_checkout_information->OrderId;
            $qliro_one_merchant_reference   = $qliro_one_checkout_information->MerchantReference;
            
            $customer_signup_for_newsletter = isset($qliro_one_checkout_information->SignupForNewsletter) ? $qliro_one_checkout_information->SignupForNewsletter : false;
            
            $country_iso_code = $qliro_one_checkout_information->Country;
            
            $qliro_country_id = Country::getByIso($country_iso_code);
            
            // IF THE CART HAS NOT BEEN CONVERTED TO AN ORDER YET
            if (!$cart->OrderExists()) {
                if ($qliro_customer_checkout_status == 'OnHold' OR $qliro_customer_checkout_status == 'Completed') {
                    // CUSTOMER INFORMATION; BILLING ADDRESS, SHIPPING ADDRESS
                    $qliro_one_customer         = $qliro_one_checkout_information->Customer;
                    $qliro_one_billing_address  = $qliro_one_checkout_information->BillingAddress;
                    $qliro_one_shipping_address = $qliro_one_checkout_information->ShippingAddress;
                    $qliro_one_order_items      = $qliro_one_checkout_information->OrderItems;
                    $total_payed                = $qliro_one_checkout_information->TotalPrice;
                    $payment_name               = $qliro_one_checkout_information->PaymentMethod->PaymentMethodName;
                    $country                    = $qliro_one_checkout_information->Country;
                    $qliro_upsell               = $qliro_one_checkout_information->Upsell;
                    
                    $ps_id_order = $this->module->createOrder($qliro_one_checkout_order_id, $qliro_one_merchant_reference, $qliro_customer_checkout_status, $qliro_one_customer, $qliro_one_billing_address, $qliro_one_shipping_address, $qliro_one_order_items, $total_payed, $country_iso_code, $qliro_country_id, $payment_name, $id_cart, $id_shop, $customer_signup_for_newsletter);
                    
                    $this->context->smarty->assign(array(
                        'pw_qliro_confirmation_snippet' => $qliro_one_checkout_information->OrderHtmlSnippet,
                        'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation($ps_id_order),
                    ));
                    $upsell_cart = $cart;
                    unset($this->context->cookie->id_cart, $cart, $this->context->cart);
                    $this->context->cart = new Cart();
                    $upsell_max_price = -1;
                    $sql = "SELECT payment FROM " . _DB_PREFIX_."qlirocheckout WHERE qliro_order_id = " . (int)$qliro_one_checkout_order_id;
                    if (Db::getInstance()->getValue($sql) == 'CREDITCARDS') {
                        $id_currency = Currency::getIdByIsoCode('SEK');
                        $obj = new Currency($id_currency);
                        $upsell_max_price = 300/$obj->conversion_rate;
                    }
                    $this->context->smarty->assign(array(
                        'cart_qties' => 0,
                        'cart' => $this->context->cart,
                        'qliro_upsell' => $this->getUpsellItems($upsell_cart, $qliro_one_checkout_information, $upsell_max_price),
                        'qliro_upsell_info' => $qliro_upsell,
                    ));
                    
                    return $this->setTemplate('module:pw_qlirocheckout/views/templates/front/confirmation.tpl');
                    
                } else {
                    PrestaShopLogger::addLog('Qliro One: Confirmation URL not valid status', 1, null, null, null, true);
                    // If the status of the checkout is Refused or InProcess, do something else
                }
            } else {
                // CUSTOMER INFORMATION; BILLING ADDRESS, SHIPPING ADDRESS
                $qliro_one_customer         = $qliro_one_checkout_information->Customer;
                $qliro_one_billing_address  = $qliro_one_checkout_information->BillingAddress;
                $qliro_one_shipping_address = $qliro_one_checkout_information->ShippingAddress;
                $qliro_one_order_items      = $qliro_one_checkout_information->OrderItems;
                $total_payed                = $qliro_one_checkout_information->TotalPrice;
                $payment_name               = $qliro_one_checkout_information->PaymentMethod->PaymentMethodName;
                $country                    = $qliro_one_checkout_information->Country;
                $qliro_upsell               = $qliro_one_checkout_information->Upsell;
                
                //$ps_id_order = $this->module->createOrder($qliro_one_checkout_order_id, $qliro_one_merchant_reference, $qliro_customer_checkout_status, $qliro_one_customer, $qliro_one_billing_address, $qliro_one_shipping_address, $qliro_one_order_items, $total_payed, $country_iso_code, $qliro_country_id, $payment_name, $id_cart, $id_shop, $customer_signup_for_newsletter);
                
                $this->context->smarty->assign(array(
                    'pw_qliro_confirmation_snippet' => $qliro_one_checkout_information->OrderHtmlSnippet,
                    'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(Order::getByCartId($id_cart)->id),
                ));
                $upsell_cart = $cart;
                unset($this->context->cookie->id_cart, $cart, $this->context->cart);
                $this->context->cart = new Cart();
                $upsell_max_price = -1;
                $sql = "SELECT payment FROM " . _DB_PREFIX_."qlirocheckout WHERE qliro_order_id = " . (int)$qliro_one_checkout_order_id;
                if (Db::getInstance()->getValue($sql) == 'CREDITCARDS') {
                    $id_currency = Currency::getIdByIsoCode('SEK');
                    $obj = new Currency($id_currency);
                    $upsell_max_price = 300/$obj->conversion_rate;
                }
                $this->context->smarty->assign(array(
                    'cart_qties' => 0,
                    'cart' => $this->context->cart,
                    'qliro_upsell' => $qliro_upsell->IsUpsellOrder ? false : $this->getUpsellItems($upsell_cart, $qliro_one_checkout_information, $upsell_max_price),
                    'qliro_upsell_info' => $qliro_upsell,
                ));
                
                return $this->setTemplate('module:pw_qlirocheckout/views/templates/front/confirmation.tpl');
            }
        } else {
            Tools::redirect($this->context->shop->getBaseURL());
        }
    }
    
    public function displayOrderConfirmation($id_order)
    {
        if (Validate::isUnsignedId($id_order)) {
            $params = array();
            
            $order = new Order($id_order);
            $currency = new Currency($order->id_currency);

            if (Validate::isLoadedObject($order)) {
                $params['total_to_pay'] = $order->getOrdersTotalPaid();
                $params['currency'] = $currency->sign;
                $params['objOrder'] = $order;
                $params['order'] = $order;
                $params['currencyObj'] = $currency;

                return Hook::exec('displayOrderConfirmation', $params);
            }
        }
        
        return false;
    }

    public function getUpsellItems(\Cart $cart, $checkout_information, $upsell_max_price = -1)
    {
        if (!Configuration::get('QLIRO_USE_UPSELL')) {
            return [];
        }
        if (Configuration::get('QLIRO_UPSELL_PRODUCT_SELECTION') == 'CATEGORY') {
            return $this->getUpsellItemsCategory($cart, $checkout_information, $upsell_max_price);
        }
        return $this->getUpsellItemsAccessory($cart, $checkout_information, $upsell_max_price);
    }

    protected function getUpsellItemsCategory($cart, $checkout_information, $upsell_max_price)
    {
        $id_category = Configuration::get('QLIRO_UPSELL_ID_CATEGORY');
        $id_lang = $this->context->language->id;

        if (!$id_category) {
            return [];
        }
        $category = new Category($id_category, $id_lang, $this->context->shop->id);
        $products = $category->getProducts(
            $id_lang,
            1,
            Configuration::get('QLIRO_UPSELL_NUM_OF_PRODUCTS')
        );
        $products = $this->productsToLazyArray($products, $upsell_max_price);
        return $products;
    }

    protected function getUpsellItemsAccessory(\Cart $cart, $checkout_information, $upsell_max_price = -1)
    {
        $products = $cart->getProducts();
        $related_products = [];
        $related_products_lookup = [];
        $products_lookup = [];
        foreach ($products as $product) {
            $products_lookup[$product['id_product']] = true;
        }
        foreach ($products as $product) {
            $obj = new Product($product['id_product']);
            $accessories = $obj->getAccessories($this->context->language->id);
            foreach ($accessories as $accessory) {
                if (isset($related_products_lookup[$accessory['id_product']]) ||
                    isset($products_lookup[$accessory['id_product']])) {
                    continue;
                }
                if ($accessory['customization_required']) {
                    continue;
                }
                $out_of_stock = true;
                $attr = Product::getProductAttributesIds($accessory['id_product']);
                if (empty($attr)) {
                    $out_of_stock = (Product::getQuantity($accessory['id_product']) < $obj->minimal_quantity);
                }
                foreach ($attr as $value) {
                    if (Product::getQuantity($accessory['id_product'], $value['id_product_attribute'])) {
                        $out_of_stock = false;
                        $attr = $value['id_product_attribute'];
                        break;
                    }
                }
                if ($out_of_stock) {
                    continue;
                }
                $accessory['id_product_attribute'] = $attr;
                if (!$accessory['id_product_attribute']) {
                    $accessory['add_to_cart_url'] = $this->context->link->getAddToCartURL($accessory['id_product'], 0);
                    array_unshift($related_products, $accessory);
                } else {
                    $accessory['add_to_cart_url'] = $this->context->link->getAddToCartURL($accessory['id_product'], $accessory['id_product_attribute']);
                    $related_products[] = $accessory;
                }
                $related_products_lookup[$accessory['id_product']] = true;
            }
        }
        $related_products = array_slice($related_products, 0, Configuration::get('QLIRO_UPSELL_NUM_OF_PRODUCTS'));
        return $this->productsToLazyArray($related_products, $upsell_max_price);
    }

    protected function productsToLazyArray($products, $upsell_max_price = -1)
    {
        foreach ($products as $key => $product) {
            $price = Product::getPriceStatic($product['id_product'], true, $product['id_product_attribute']);
            if($upsell_max_price > 0 && $price > $upsell_max_price) {
                unset($products[$key]);
            }
        }
        $assembler = new ProductAssembler($this->context);

        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $products_for_template = [];

        foreach ($products as $rawProduct) {
            $products_for_template[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($rawProduct),
                $this->context->language
            );
        }
        return $products_for_template;
    }

}


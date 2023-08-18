<?php

use PrestaShop\PrestaShop\Adapter\Order\Invoice;
use PrestaShop\PrestaShop\Adapter\Order\OrderDetailUpdater;

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

class Pw_qlirocheckoutUpsellModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        /**@var Pw_qlirocheckout */
        $module = $this->module;
        $token = Tools::getValue('token');
        $id_cart = Tools::getValue('id_cart');
        $id_product = Tools::getValue('id_product');
        $id_product_attribute = Tools::getValue('id_product_attribute');
        $id_shop = $this->context->shop->id;
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $confirm_token = $this->module->createConfirmationKey($id_cart, $id_shop, Configuration::get('QLIRO_API_PASSWORD_LIVE'));
        } else {
            $confirm_token = $this->module->createConfirmationKey($id_cart, $id_shop, Configuration::get('QLIRO_API_PASSWORD_TEST'));
        }
        if ($token != $confirm_token) {
            die(json_encode([
                'success' => false,
                'upsell_html' => $this->context->smarty->fetch('module:pw_qlirocheckout/views/templates/front/upsell_error.tpl'),
                'message' => $this->l('Invalid token'), 
            ]));
        }
        if (!Configuration::get('QLIRO_USE_UPSELL')) {
            die(json_encode([
                'success' => false,
                'upsell_html' => $this->context->smarty->fetch('module:pw_qlirocheckout/views/templates/front/upsell_error.tpl'),
                'message' => $this->l('Upsell failed'),
            ]));
        }
        if (!Tools::getValue('ajax')) {
            die(json_encode([
                'success' => false,
                'upsell_html' => $this->context->smarty->fetch('module:pw_qlirocheckout/views/templates/front/upsell_error.tpl'),
                'message' => $this->l('Upsell failed')
            ]));
        }
        $country_iso = Tools::getValue('country_iso');
        $currency_iso = Tools::getValue('currency_iso');
        $query = "SELECT qliro_order_id
                FROM "._DB_PREFIX_."qlirocheckout
                WHERE ps_id_cart = ".(int)$id_cart."
                    AND ps_country_iso = '".$country_iso."'
                    AND ps_currency_iso = '".$currency_iso."'
                    AND ps_id_shop = ".$this->context->shop->id."
                ORDER BY update_date DESC";
                
        $qliro_order_id = Db::getInstance()->getValue($query);

        //ORDER ITEM
        $row = array();
        $product = new Product($id_product, true, $this->context->language->id);
        $merchant_reference = $product->id;
        if (strlen($merchant_reference > 200)) {
            $merchant_reference = mb_substr($merchant_reference, 0, 50);
        }
        
        $description = strip_tags($product->name);
        if (strlen($description > 200)) {
            $description = mb_substr($description, 0, 50);
        }
        
        $type               = 'Product';
        $quantity           = (int)1;
        $pricePerItemIncVat = $this->module->qlirocheckoutRound($product->getPrice(true, $id_product_attribute));//Product::getPriceStatic((int) $product->id, true, $id_product_attribute, 6, null, false, true));
        $pricePerItemExVat  = $this->module->qlirocheckoutRound($product->getPrice(false));// getprice['price_with_reduction_without_tax']);
        $sql = "SELECT payment FROM " . _DB_PREFIX_."qlirocheckout WHERE qliro_order_id = " . (int)$qliro_order_id;
        if (Db::getInstance()->getValue($sql) == 'CREDITCARDS') {
            $id_currency = Currency::getIdByIsoCode('SEK');
            $obj = new Currency($id_currency);
            $upsell_max_price = 300/$obj->conversion_rate;
            if ($pricePerItemIncVat > $upsell_max_price) {
                die(json_encode([
                    'success' => false,
                    'upsell_html' => $this->context->smarty->fetch('module:pw_qlirocheckout/views/templates/front/upsell_error.tpl'),
                    'message' => $this->l('Upsell failed')
                ]));
            }
        }

        $row['MerchantReference']  = $merchant_reference;
        $row['Type']               = $type;
        $row['Quantity']           = $quantity;
        $row['PricePerItemIncVat'] = $pricePerItemIncVat;
        $row['PricePerItemExVat']  = $pricePerItemExVat;
        $row['Description']        = $description;

        $order_items = array($row);
        
        // echo(json_encode($row));
        // die;
        $qliro_one_checkout_information = $this->module->getQliroCheckout($id_cart, null, $country_iso, $currency_iso, false);
        $qliro_one_checkout_information = json_decode($qliro_one_checkout_information['response']);
            
        $qliro_customer_checkout_status = $qliro_one_checkout_information->CustomerCheckoutStatus;
        $qliro_one_checkout_order_id    = $qliro_one_checkout_information->OrderId;
        $qliro_one_merchant_reference   = $qliro_one_checkout_information->MerchantReference;
        
        $customer_signup_for_newsletter = isset($qliro_one_checkout_information->SignupForNewsletter) ? $qliro_one_checkout_information->SignupForNewsletter : false;
        
        $country_iso_code = $qliro_one_checkout_information->Country;
        
        $qliro_country_id           = Country::getByIso($country_iso_code);
        $qliro_one_customer         = $qliro_one_checkout_information->Customer;
        $qliro_one_billing_address  = $qliro_one_checkout_information->BillingAddress;
        $qliro_one_shipping_address = $qliro_one_checkout_information->ShippingAddress;
        $qliro_one_order_items      = $qliro_one_checkout_information->OrderItems;
        $total_payed                = $qliro_one_checkout_information->TotalPrice;
        $payment_name               = $qliro_one_checkout_information->PaymentMethod->PaymentMethodName;
        $country                    = $qliro_one_checkout_information->Country;
        $qliro_upsell               = $qliro_one_checkout_information->Upsell;
        if ($qliro_upsell->IsUpsellOrder) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Cannot add more than one item through Upsell'),
            ]));
        }

        $response = $this->module->createQliroUpsell($qliro_order_id, $currency_iso, $order_items);
        
        if ($response['response_code'] == 200 || $response['response_code'] == 201) {
            $cart = new Cart($id_cart);
            $order = Order::getByCartId($cart->id);
            $order = new Order($order->id, $order->id_lang);
            $cart->updateQty(1, $id_product, $id_product_attribute, false, 'up', 0, null, false);
            $this->addOrderDetail($order, $product, $cart, $id_product_attribute, $response['response']->PaymentTransactionId);
            $paymentTransaction = $this->module->getQliroPaymentTransaction($response['response']->PaymentTransactionId);
            if ($paymentTransaction['response_code'] == 200) {
                Db::getInstance()->insert('qlirocheckout_payment_transactions', [
                    'ps_id_order' => (int)$order->id,
                    'PaymentTransactionId' => (int)$paymentTransaction['response']->PaymentTransactionId,
                    'Status' => pSQL($paymentTransaction['response']->Status),
                    'PaymentType' => pSQL($paymentTransaction['response']->Type),
                ]);
            }
            die(json_encode([
                'success' => true,
                'upsell_html' => $this->context->smarty->fetch('module:pw_qlirocheckout/views/templates/front/upsell_confirmation.tpl'),
            ]));
        }
        die(json_encode([
            'success' => false,
            'upsell_html' => $this->context->smarty->fetch('module:pw_qlirocheckout/views/templates/front/upsell_error.tpl'),
            'message' => $response['message'],
        ]));
        
    }

    protected function addOrderDetail(Order $order, Product $product, Cart $cart, $id_product_attribute, $transaction_id)
    {
        $list = $order->getOrderDetailList();
        $currency = new Currency($cart->id_currency, $order->id_lang);
        $computingPrecision = _PS_PRICE_COMPUTE_PRECISION_;
        $carrierId = $order->id_carrier;

        $carrier = new Carrier((int) $carrierId, (int) $order->id_lang);

        $order->total_paid_real = 0;

        $order->total_products = Tools::ps_round(
            $order->total_products + $product->getPrice(false, $id_product_attribute),
            $computingPrecision
        );
        $order->total_products_wt = Tools::ps_round(
            $order->total_products_wt + $product->getPrice(true, $id_product_attribute),
            $computingPrecision
        );


        $order->total_shipping_tax_excl = Tools::ps_round(
            (float) $cart->getPackageShippingCost($carrierId, false, null, @$order->product_list),
            $computingPrecision
        );
        $order->total_shipping_tax_incl = Tools::ps_round(
            (float) $cart->getPackageShippingCost($carrierId, true, null, @$order->product_list),
            $computingPrecision
        );
        $order->total_shipping = $order->total_shipping_tax_incl;

        if (null !== $carrier && Validate::isLoadedObject($carrier)) {
            $order->carrier_tax_rate = $carrier->getTaxesRate(new Address((int) $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
        }

        $order->total_wrapping_tax_excl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING, @$order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_wrapping_tax_incl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(true, Cart::ONLY_WRAPPING, @$order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_wrapping = $order->total_wrapping_tax_incl;

        $order->total_paid_tax_excl = Tools::ps_round(
            $order->total_paid_tax_excl + $product->getPrice(false, $id_product_attribute),
            $computingPrecision
        );
        $order->total_paid_tax_incl = Tools::ps_round(
            $order->total_paid_tax_incl + $product->getPrice(true, $id_product_attribute),
            $computingPrecision
        );
        $order->total_paid = $order->total_paid_tax_incl;
        $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
        $order->round_type = Configuration::get('PS_ROUND_TYPE');

        $order->update();


        $order_detail = new OrderDetail(null, null, $this->context);
        //Get products in cart where id_product = $product->id
        $product_list = $cart->getProducts(true, $product->id);
        $unset_list = [];
        $key_to_product = 0;
        foreach ($product_list as $key => $list_item) {
            if ($list_item['id_product_attribute'] != $id_product_attribute) {
                $unset_list[] = $key;
            } else {
                $key_to_product = $key;
            }
        }
        foreach ($unset_list as $value) {
            unset($product_list[$value]);
        }

        //OrderDetail already exists. Therefore update that one instead of creating a new one
        if ($product_list[$key_to_product]['quantity'] > 1) {
            $sql = "SELECT `id_order_detail` FROM " . _DB_PREFIX_ . "order_detail WHERE `id_order` = " . (int)$order->id . " 
            AND `product_id` = " . (int)$product->id . " 
            AND `product_attribute_id` = " . (int)$product_list[$key_to_product]['id_product_attribute'] . "
            AND `id_shop` = " . (int)$order->id_shop;
            $id_order_detail = Db::getInstance()->getValue($sql);

            $old_order_detail = new OrderDetail($id_order_detail, $order->id_lang);
            $old_order_detail->total_price_tax_excl = $product_list[$key_to_product]['total'];
            $old_order_detail->total_price_tax_incl = $product_list[$key_to_product]['total_wt'];
            $old_order_detail->product_quantity = $product_list[$key_to_product]['quantity'];
            $old_order_detail->update();

            //Update stock
            $sql = "UPDATE " . _DB_PREFIX_ . "stock_available
                SET `quantity` = `quantity` - 1
                WHERE `id_product` = " . (int)$product->id . " AND `id_product_attribute` = " . (int)$product_list[$key_to_product]['id_product_attribute'] . " AND `id_shop` = " . (int)$order->id_shop;
            Db::getInstance()->execute($sql);
        } else {
            $order_detail->createList($order, $cart, $order->getCurrentState(), $product_list, $list[0]['id_order_invoice'], true, 0);
        }
        //Update invoice
        $invoice = new OrderInvoice($list[0]['id_order_invoice'], $order->id_lang, $order->id_shop);
        $invoice->total_paid_tax_excl   += $product_list[$key_to_product]['price'];
        $invoice->total_paid_tax_incl   += $product_list[$key_to_product]['price_wt'];
        $invoice->total_products        += $product_list[$key_to_product]['price'];
        $invoice->total_products_wt     += $product_list[$key_to_product]['price_wt'];
        $invoice->total_paid_tax_excl   = Tools::ps_round($invoice->total_paid_tax_excl, $computingPrecision);
        $invoice->total_paid_tax_incl   = Tools::ps_round($invoice->total_paid_tax_incl, $computingPrecision);
        $invoice->total_products        = Tools::ps_round($invoice->total_products, $computingPrecision);
        $invoice->total_products_wt     = Tools::ps_round($invoice->total_products_wt, $computingPrecision);
        $invoice->update();

        //Update order_payment
        $previous_order_payment = OrderPayment::getByInvoiceId($invoice->id)->getFirst();
        $order_payment = new OrderPayment(null, $order->id_lang, $order->id_shop);
        $order_payment->order_reference = $order->reference;
        $order_payment->payment_method = $previous_order_payment->payment_method;
        $order_payment->transaction_id = $transaction_id;
        $order_payment->amount = $product_list[$key_to_product]['price_wt'];
        $order_payment->conversion_rate = $previous_order_payment->conversion_rate;
        $order_payment->id_currency = $previous_order_payment->id_currency;
        $order_payment->card_brand = $previous_order_payment->card_brand;
        $order_payment->card_expiration = $previous_order_payment->card_expiration;
        $order_payment->card_holder = $previous_order_payment->card_holder;
        $order_payment->card_number = $previous_order_payment->card_number;
        $order_payment->add();

        Db::getInstance()->insert('order_invoice_payment', [
            'id_order_invoice' => $invoice->id,
            'id_order_payment' => $order_payment->id,
            'id_order' => $order->id,
        ]);

        
    }
}
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

class Pw_qlirocheckoutNotificationModuleFrontController extends ModuleFrontController
{
    
    /* 
     * HANDLE NOTIFICATIONS FROM QLIRO ONE
     *
     * CUSTOMER CHECKOUT STATUS UPDATES ARE SENT TO THIS CONTROLLER
     *
     * IF AN ORDER IS onHold, RESPONDE first time AND THEN WHEN THE ORDER STATUS BECOMES Completed or Refused
     *
     * IF AN ORDER IS Completed OR Refused, RESPONSE JUST ONCE
     * 
     */
     
    public function postProcess()
    {
        /**@var Pw_qlirocheckout */
        $module = $this->module;
        $page = 'notification';
        
        // SLEEP FOR SOME SECONDS
        sleep(6);
        
        PrestaShopLogger::addLog('Qliro One: Push URL after sleeping', 1, null, null, null, true);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // QLIRO BODY
            $body = json_decode(file_get_contents('php://input'), true);
            
            $qliro_order_id           = $body['OrderId'];
            $qliro_merchant_reference = $body['MerchantReference'];
            $qliro_order_status       = $body['Status'];
            
            $qliro_order_notification_type = $body['NotificationType'];
            
            $qliro_order_transaction_id = $body['PaymentTransactionId'];
            
            PrestaShopLogger::addLog('Qliro One: Push URL Qliro status for order '.$qliro_order_id.' is '.$qliro_order_status, 1, null, null, null, true);
            PrestaShopLogger::addLog(json_encode($body));
            $id_cart = Tools::getValue('id_cart');
            
            if ($qliro_order_notification_type == 'CustomerCheckoutStatus') {
            // THIS SHOULD NEVER HAPPEN, BUT IGNORE REQUESTS WHERE ORDER STATUS IS InProcess
                if ($qliro_order_status != 'InProcess') {
                    
                    $sql = "SELECT *
                        FROM "._DB_PREFIX_."qlirocheckout
                        WHERE qliro_order_id = ".$qliro_order_id;
                    
                    $qliro = Db::getInstance()->getRow($sql);
                    
                    $id_shop = $qliro['ps_id_shop'];
                    
                    if (isset($qliro) && is_array($qliro) && (int)$qliro['ps_id_order'] > 0) {
                        
                        $payment_failed_status = Configuration::get('PS_OS_ERROR');
                        
                        if ($qliro_order_status == 'Completed') {
                            $new_status = Configuration::get('QLIRO_ACCEPTED_STATUS');
                            $new_status_str = 'Completed';
                        } elseif ($qliro_order_status == 'Refused') {
                            $new_status = Configuration::get('QLIRO_REJECTED_STATUS');
                            $new_status_str = 'Refused';
                        } elseif ($qliro_order_status == 'OnHold') {
                            $new_status = Configuration::get('QLIRO_PENDING_STATUS');
                            $new_status_str = 'OnHold';
                        }
                        
                        // ORDER ID
                        $id_order = (int)$qliro['ps_id_order'];
                        // ORDER STATUS
                        $order = new Order($id_order);
                        
                        // CURRENT ORDER STATUS
                        $current_order_state = $order->getCurrentOrderState();
                        $new_order_state = new OrderState((int)$new_status);
                        
                        if (($current_order_state->id != $new_order_state->id) AND ($current_order_state->id != $payment_failed_status)) {
                            $history = new OrderHistory();
                            $history->id_order = $order->id;
                            $history->id_employee = 0;
                            $use_existings_payment = !$order->hasInvoice();
                            $history->changeIdOrderState((int)$new_order_state->id, $order, $use_existings_payment);
                            $history->add();
                        }
                        
                        PrestaShopLogger::addLog('Qliro One: Response to notification URL when status for Qliro order '.$qliro_order_id.' is '.$qliro_order_status, 1, null, null, null, true);
                        
                        $id_cart = $order->id_cart;
                        $datetime = date("Y-m-d H:i:s");
                        
                        $sql = "UPDATE "._DB_PREFIX_."qlirocheckout
                            SET qliro_status = '".$qliro_order_status."', update_date = '".$datetime."'
                            WHERE ps_id_cart = ".$id_cart." AND ps_id_order = ".$id_order."";
                        Db::getInstance()->execute($sql);
                        $sql = 'SELECT PaymentTransactionId FROM ' . _DB_PREFIX_.'qlirocheckout_payment_transactions
                        WHERE PaymentTransactionId = ' . (int)$qliro_order_transaction_id;
                        if (Db::getInstance()->getValue($sql)) {
                            Db::getInstance()->update('qlirocheckout_payment_transactions', [
                                'Status' => pSQL($qliro_order_status),
                            ], 'PaymentTransactionId = ' . (int)$qliro_order_transaction_id);
                        } else {
                            $paymentTransaction = $this->module->getQliroPaymentTransaction($qliro_order_transaction_id);
                            if ($paymentTransaction['response_code'] == 200) {
                                Db::getInstance()->insert('qlirocheckout_payment_transactions',
                                [
                                    'ps_id_order' => $id_order,
                                    'PaymentTransactionId' => $qliro_order_transaction_id,
                                    'Status' => $paymentTransaction['response']->Status,
                                    'PaymentType' => $paymentTransaction['response']->Type,
                                ]);
                            }
                        }
                        
                        
                        $this->module->addOrderMessage('Order is now '.$new_status_str, $id_order);
                        $this->module->response(200, $page, array('CallbackResponse' => "received"));
                    } else { // Create order
                        PrestaShopLogger::addLog('Qliro One: Push URL Qliro inside create order function with order id '.$qliro_order_id, 1, null, null, null, true);
                        $qliro_one_checkout_information = $this->module->getQliroCheckoutOnNotification($qliro_order_id);
                        $qliro_one_checkout_information = json_decode($qliro_one_checkout_information['response']);
                        // $qliro_customer_checkout_status = $qliro_one_checkout_information->CustomerCheckoutStatus;
                        $qliro_one_checkout_order_id    = $qliro_one_checkout_information->OrderId;
                        $qliro_one_merchant_reference   = $qliro_one_checkout_information->MerchantReference;
                        $customer_signup_for_newsletter = isset($qliro_one_checkout_information->SignupForNewsletter) ? $qliro_one_checkout_information->SignupForNewsletter : false;
                        
                        $country_iso_code = $qliro_one_checkout_information->Country;
                        
                        $qliro_country_id = Country::getByIso($country_iso_code);
                        if ($qliro_order_status == 'OnHold' OR $qliro_order_status == 'Completed') {
                            $qliro_one_customer         = $qliro_one_checkout_information->Customer;
                            $qliro_one_billing_address  = $qliro_one_checkout_information->BillingAddress;
                            $qliro_one_shipping_address = $qliro_one_checkout_information->ShippingAddress;
                            $qliro_one_order_items      = $qliro_one_checkout_information->OrderItems;
                            $total_payed                = $qliro_one_checkout_information->TotalPrice;
                            $payment_name               = $qliro_one_checkout_information->PaymentMethod->PaymentMethodName;
                            $country                    = $qliro_one_checkout_information->Country;
                            $id_order = $this->module->createOrder($qliro_one_checkout_order_id, $qliro_one_merchant_reference, $qliro_order_status, $qliro_one_customer, $qliro_one_billing_address, $qliro_one_shipping_address, $qliro_one_order_items, $total_payed, $country, $qliro_country_id, $payment_name, $id_cart, $id_shop, $customer_signup_for_newsletter);
                        }
                    }
                }
            }
            if ($qliro_order_notification_type == 'UpsellStatus') {
                if ($qliro_order_status == 'Success') {
                    $sql = "SELECT *
                    FROM "._DB_PREFIX_."qlirocheckout
                    WHERE qliro_order_id = ".$qliro_order_id;

                    $qliro = Db::getInstance()->getRow($sql);
                    $id_shop = $qliro['ps_id_shop'];
                    
                    if (!(isset($qliro) && is_array($qliro) && (int)$qliro['ps_id_order'] > 0)) {
                        PrestaShopLogger::addLog('Qliro Notification for UpsellStatus called but no order with id:'.$qliro_order_id.' found');
                        $this->module->response(200, $page, array('CallbackResponse' => "received"));
                    }
                    $order = new Order($qliro['ps_id_order']);
                    if (isset($body['ReplacesOriginalPaymentTransaction']) && $body['ReplacesOriginalPaymentTransaction']) {
                        Db::getInstance()->update('qlirocheckout', [
                            'paymentTransactionId' => (int)$qliro_order_transaction_id,
                        ], 'ps_id_order = ' . (int)$qliro['ps_id_order']);
                        Db::getInstance()->delete('qlirocheckout_payment_transactions', 'PaymentTransactionId = '.(int)$qliro_order_transaction_id);
                        Db::getInstance()->update('qlirocheckout_payment_transactions', [
                            'ps_id_order' => (int)$qliro['ps_id_order'],
                            'PaymentTransactionId' => (int)$qliro_order_transaction_id,
                            'Status' => $body['Status'],
                        ], 'PaymentTransactionId = ' . $body['OriginalPaymentTransactionId']);
                    } else {
                        Db::getInstance()->update('qlirocheckout_payment_transactions', [
                            'ps_id_order' => (int)$qliro['ps_id_order'],
                            'PaymentTransactionId' => (int)$qliro_order_transaction_id,
                            'Status' => pSQL($body['Status']),
                        ], 'PaymentTransactionId = ' . (int)$qliro_order_transaction_id);
                    }
                    $this->module->addOrderMessage('Upsell successfull paymentTransactionId: '. $qliro_order_transaction_id, $order->id);
                } else {
                    $this->module->addOrderMessage('Upsell error', $order->id);
                }
            }
            
            $this->module->response(200, $page, array('CallbackResponse' => "received"));
            
        } else {
            echo '<pre>';
            print_r(json_encode(array('Error' => 'Request has to be POST'), JSON_PRETTY_PRINT));
            echo '</pre>';
            die;
        }
        
        $this->module->response(200, $page, array('CallbackResponse' => "received"));
    }
}
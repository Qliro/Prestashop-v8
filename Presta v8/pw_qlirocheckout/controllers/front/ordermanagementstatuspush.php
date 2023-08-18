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

class Pw_qlirocheckoutOrdermanagementstatuspushModuleFrontController extends ModuleFrontController
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
        $page = 'ordermanagementstatuspush';
        
        sleep(3);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $body = json_decode(file_get_contents('php://input'), true);
            
            $qliro_order_id            = $body['OrderId'];
            $qliro_status              = $body['Status'];
            $payment_reference         = $body['PaymentReference'];
            $PaymentTransactionId      = $body['PaymentTransactionId'];
            $PaymentType               = $body['PaymentType'];
            $ProviderTransactionId     = $body['ProviderTransactionId'];
            $ProviderResultDescription = $body['ProviderResultDescription'];
            $ProviderResultCode        = $body['ProviderResultCode'];
            
            PrestaShopLogger::addLog('Qliro One: Order Management Push, Qliro order ID '.$qliro_order_id.', Qliro status is '.$qliro_status.', Qliro type is '.$PaymentType.', PaymentTransactionId is '.$PaymentTransactionId, 1, null, null, null, true);
            PrestaShopLogger::addLog('Ordermanagementstatuspush body: '. json_encode($body));
           
            $PaymentType = ($PaymentType == 'Debit') ? 'Capture' : $PaymentType;
            $order_id = Db::getInstance()->getValue("SELECT ps_id_order FROM "._DB_PREFIX_."qlirocheckout_payment_transactions WHERE PaymentTransactionId =".$PaymentTransactionId );
            if (!$order_id) {
                $order_id =  $order_id = Db::getInstance()->getValue("SELECT ps_id_order FROM "._DB_PREFIX_."qlirocheckout WHERE qliro_order_id = ".$qliro_order_id);
                if (!$order_id) {
                    PrestaShopLogger::addLog('Qliro One: Order Management Push, Error: Could not find qliro order: '.$qliro_order_id, 3);
                    die;
                }
                Db::getInstance()->insert('qlirocheckout_payment_transactions',
                [
                    'ps_id_order' => $order_id,
                    'PaymentTransactionId' => $PaymentTransactionId,
                    'Status' => $qliro_status,
                    'PaymentType' => $PaymentType,
                ]);
            } else {
                Db::getInstance()->update('qlirocheckout_payment_transactions',
                [
                    'Status' => $qliro_status,
                    'PaymentType' => $PaymentType,
                ], "PaymentTransactionId =".$PaymentTransactionId);
            }
            
       
            $order    = new Order($order_id);
            $current_order_state = $order->getCurrentOrderState();
            
            $qliro_order_management_status = '';
            
            if ($qliro_status == 'Success') {
                
                if ($PaymentType == 'Reversal') {
                    
                    $order_management_state = (int)Configuration::get('PS_OS_CANCELED');
                    $new_order_state        = new OrderState((int)$order_management_state);
                    
                    $qliro_order_management_status = 'Reversal';
                    
                    if ($current_order_state->id != $new_order_state->id) {
                        $this->changeOrderStatus($order, $order_management_state);
                    }
                    
                    $this->module->addOrderMessage('QLIRO '.$PaymentType.' request was successfull', $order_id);
                    
                } elseif ($PaymentType == 'Capture' OR $PaymentType == 'Debit') {
                    
                    $order_management_state = (int)Configuration::get('QLIRO_READY_TO_BE_SHIPPED');
                    $new_order_state        = new OrderState((int)$order_management_state);
                    
                    $qliro_order_management_status = 'Capture';
                    
                    if ($current_order_state->id != $new_order_state->id) {
                        $this->changeOrderStatus($order, $order_management_state);
                    }
                    $this->module->addOrderMessage('QLIRO '.$PaymentType.', provider transaction id is '.$ProviderTransactionId, $order_id);
                    
                } elseif ($PaymentType == 'Refund') {
                    
                    $order_management_state = (int)Configuration::get('PS_OS_REFUND');
                    $new_order_state        = new OrderState((int)$order_management_state);
                    
                    $qliro_order_management_status == 'Refund';
                    
                    if ($current_order_state->id != $new_order_state->id) {
                        $this->changeOrderStatus($order, $order_management_state);
                    }
                    
                    $this->module->addOrderMessage('QLIRO '.$PaymentType.' request was successfull', $order_id);
                    
                }
                
                $datetime = date("Y-m-d H:i:s");
                
                $sql = "UPDATE "._DB_PREFIX_."qlirocheckout
                        SET payment_reference = '".$payment_reference."', qliro_order_management_status = '".$qliro_order_management_status."', update_date = '".$datetime."'
                        WHERE qliro_order_id = ".$qliro_order_id." AND ps_id_order = ".$order_id."";
                
                Db::getInstance()->execute($sql);
                        
            } elseif ($qliro_status == 'Error') {
                
                $this->module->addOrderMessage('QLIRO '.$PaymentType.' request Failed, status of payment transaction is '.$qliro_status, $order_id);
                
                // Capture failed
                if ($PaymentType == 'Capture' OR $PaymentType == 'Debit') {
                    
                    $order_management_state = (int)Configuration::get('PS_OS_ERROR');
                    $new_order_state        = new OrderState((int)$order_management_state);
                
                    if ($current_order_state->id != $new_order_state->id) {
                        $this->changeOrderStatus($order, $order_management_state);
                    }
                    
                    $this->module->addOrderMessage('QLIRO '.$PaymentType.' failed, reason is '.$ProviderResultDescription, $order_id);
                    
                } elseif ($PaymentType == 'Reversal') {
                    
                    $this->module->addOrderMessage('QLIRO '.$PaymentType.' failed', $order_id);
                    
                } elseif ($PaymentType == 'Refund') {
                    
                    $this->module->addOrderMessage('QLIRO '.$PaymentType.' failed', $order_id);
                    
                }              
                
            } else {
                
                $this->module->addOrderMessage('QLIRO '.$PaymentType.', status of payment transaction is '.$qliro_status, $order_id);
                
            }
        }
        
        $this->module->response(200, $page, array('CallbackResponse' => "received"));
    }
    
    public function changeOrderStatus($order, $new_order_state)
    {
        if (!$new_order_state) {
            return;
        }
        $history = new OrderHistory();
        $history->id_order = $order->id;
        $history->id_employee = 0;
        $use_existings_payment = !$order->hasInvoice();
        $history->changeIdOrderState((int)$new_order_state, $order->id, $use_existings_payment);
        $history->add();
    }
}

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

if (!defined('_PS_VERSION_')) {
    exit;
}


class Pw_qlirocheckout extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'pw_qlirocheckout';
        $this->tab = 'payments_gateways';
        $this->version = '8.0.6';
        $this->author = 'Prestaworks AB';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Qliro Checkout');
        $this->description = $this->l('Payment module with Qliro Checkout, integrated by Prestaworks AB');

        $this->ps_versions_compliancy = array('min' => '1.8.0', 'max' => '8.99.99');
        
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Qliro Checkout?');
    }

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $this->createStatuses();
        
        if (!parent::install()
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('displayAdminOrder')
            || !$this->registerHook('actionOrderStatusUpdate')
            || !$this->registerHook('displayPayment')
            || !$this->registerHook('actionCartSave')
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('qliroCheckoutLeftColumn')
            || !$this->registerHook('actionAdminControllerSetMedia')
            //|| !$this->registerHook('actionOrderSlipAdd') //This hook can potentially be used if we want to implement partial returns
            
            // Qliro Checkout Settings
            || !Configuration::updateValue('QLIRO_ACTIVE', null)
            || !Configuration::updateValue('QLIRO_LIVE_MODE', null)
            
            // QLIRO B2B or B2C
            || !Configuration::updateValue('QLIRO_B2B_B2C', null)
            
            // Qliro Checkout API Settings
            || !Configuration::updateValue('QLIRO_API_USERNAME_TEST', '')
            || !Configuration::updateValue('QLIRO_API_PASSWORD_TEST', '')
            
            || !Configuration::updateValue('QLIRO_API_USERNAME_LIVE', '')
            || !Configuration::updateValue('QLIRO_API_PASSWORD_LIVE', '')
            
            // Qliro Terms and Conditions
            || !Configuration::updateValue('QLIRO_TERMS_CMS', '')
            || !Configuration::updateValue('QLIRO_INTEGRITY_CMS', '')
            
            // Qliro Checkout General Option
            || !Configuration::updateValue('QLIRO_USE_MINIMUM_CUSTOMER_AGE', 0)
            || !Configuration::updateValue('QLIRO_MINIMUM_CUSTOMER_AGE', '')
            || !Configuration::updateValue('QLIRO_USE_REQUIRE_IDENTITY_VERIFICATION', 0)
            || !Configuration::updateValue('QLIRO_ASK_FOR_NEWSLETTER', 0)
            || !Configuration::updateValue('QLIRO_SHOW_ORDER_MESSAGE', 0)
            || !Configuration::updateValue('QLIRO_UPDATE_MERCHANT_REFERENCE', 1)
            || !Configuration::updateValue('QLIRO_USE_UPSELL', 0)
            || !Configuration::updateValue('QLIRO_UPSELL_NUM_OF_PRODUCTS', 0)
            || !Configuration::updateValue('QLIRO_UPSELL_PRODUCT_SELECTION', 'ACCESSORY')
            || !Configuration::updateValue('QLIRO_UPSELL_ID_CATEGORY', 0)
            
            || !Configuration::updateValue('QLIRO_INVOICE_FEE', '')
            || !Configuration::updateValue('QLIRO_FEE_REFERENCE', '')
            
            || !Configuration::updateValue('QLIRO_REJECTED_STATUS', Configuration::get('PS_OS_ERROR'))
            || !Configuration::updateValue('QLIRO_ACCEPTED_STATUS', Configuration::get('PS_OS_PAYMENT'))
            
            // Design Settings
            || !Configuration::updateValue('QLIRO_ADAPT_DESIGN', 0)
            || !Configuration::updateValue('QLIRO_ADAPT_BACKGROUND_COLOR', '#5ca375')
            || !Configuration::updateValue('QLIRO_ADAPT_PRIMARY_COLOR', '#5ca375')
            || !Configuration::updateValue('QLIRO_ADAPT_CALL_TO_ACTION_COLOR', '#5ca375')
            || !Configuration::updateValue('QLIRO_ADAPT_CALL_TO_ACTION_HOVER_COLOR', '#5ca375')
            || !Configuration::updateValue('QLIRO_ADAPT_CORNER_RADIUS', '1')
            || !Configuration::updateValue('QLIRO_ADAPT_BUTTON_CORNER_RADIUS', '1')
            || !Configuration::updateValue('QLIRO_TWO_COLUMNS', 0)
        ) {
            return false;
        }
        
        include(dirname(__FILE__).'/sql/install.php');
        
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
        || !Configuration::deleteByName('QLIRO_ACTIVE')
        || !Configuration::deleteByName('QLIRO_LIVE_MODE')
        
        || !Configuration::deleteByName('QLIRO_B2B_B2C')
        
        || !Configuration::deleteByName('QLIRO_API_USERNAME_TEST')
        || !Configuration::deleteByName('QLIRO_API_PASSWORD_TEST')
        
        || !Configuration::deleteByName('QLIRO_API_USERNAME_LIVE')
        || !Configuration::deleteByName('QLIRO_API_PASSWORD_LIVE')
        
        || !Configuration::deleteByName('QLIRO_SEND_REVERSE')
        || !Configuration::deleteByName('QLIRO_SEND_REFUND')
        
        || !Configuration::deleteByName('QLIRO_REJECTED_STATUS')
        || !Configuration::deleteByName('QLIRO_ACCEPTED_STATUS')
        
        || !Configuration::deleteByName('QLIRO_TERMS_CMS')
        || !Configuration::deleteByName('QLIRO_INTEGRITY_CMS')
        
        || !Configuration::deleteByName('QLIRO_USE_MINIMUM_CUSTOMER_AGE')
        || !Configuration::deleteByName('QLIRO_MINIMUM_CUSTOMER_AGE')
        || !Configuration::deleteByName('QLIRO_USE_REQUIRE_IDENTITY_VERIFICATION')
        || !Configuration::deleteByName('QLIRO_ASK_FOR_NEWSLETTER')
        || !Configuration::deleteByName('QLIRO_SHOW_ORDER_MESSAGE')
        || !Configuration::deleteByName('QLIRO_UPDATE_MERCHANT_REFERENCE')
        || !Configuration::deleteByName('QLIRO_USE_UPSELL')
        || !Configuration::deleteByName('QLIRO_UPSELL_NUM_OF_PRODUCTS')
        || !Configuration::deleteByName('QLIRO_UPSELL_PRODUCT_SELECTION')
        || !Configuration::deleteByName('QLIRO_UPSELL_ID_CATEGORY')
        
        || !Configuration::deleteByName('QLIRO_INVOICE_FEE')
        || !Configuration::deleteByName('QLIRO_FEE_REFERENCE')
        
        || !Configuration::deleteByName('QLIRO_ADAPT_DESIGN')
        || !Configuration::deleteByName('QLIRO_ADAPT_BACKGROUND_COLOR')
        || !Configuration::deleteByName('QLIRO_ADAPT_PRIMARY_COLOR')
        || !Configuration::deleteByName('QLIRO_ADAPT_CALL_TO_ACTION_COLOR')
        || !Configuration::deleteByName('QLIRO_ADAPT_CALL_TO_ACTION_HOVER_COLOR')
        || !Configuration::deleteByName('QLIRO_ADAPT_CORNER_RADIUS')
        || !Configuration::deleteByName('QLIRO_ADAPT_BUTTON_CORNER_RADIUS')
        || !Configuration::deleteByName('QLIRO_TWO_COLUMNS')
        ) {
            return false;
        }
        
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        return true;
    }
    
    public function createStatuses()
    {
        $states = OrderState::getOrderStates(Configuration::get('PS_LANG_DEFAULT'));
        
        $exists_qliro_avvaktar = false;
        $exists_qliro_send_capture = false;
        $exists_qliro_ready_to_be_shipped    = false;
        $exists_qliro_send_reverse = false;
        $exists_qliro_send_refund = false;
        
		foreach ($states as $state) {
            if ($state['name'] == 'QliroOne pending' || $state['name'] == 'QliroOne avvaktar') {
                $exists_qliro_avvaktar = true;
				Configuration::updateValue('QLIRO_PENDING_STATUS', $state['id_order_state']);
			}
            
            if ($state['name'] == 'QliroOne capture' || $state['name'] == 'Debitera hos QliroOne') {
                $exists_qliro_send_capture = true;
				Configuration::updateValue('QLIRO_SEND_CAPTURE', $state['id_order_state']);
			}
            
            if ($state['name'] == 'QliroOne ready to be shipped' || $state['name'] == 'QliroOne redo att skickas') {
                $exists_qliro_ready_to_be_shipped = true;
				Configuration::updateValue('QLIRO_READY_TO_BE_SHIPPED', $state['id_order_state']);
			}
            
            if ($state['name'] == 'QliroOne reverse' || $state['name'] == 'QliroOne makulera') {
                $exists_qliro_send_reverse = true;
				Configuration::updateValue('QLIRO_SEND_REVERSE', $state['id_order_state']);
			}
            
            if ($state['name'] == 'QliroOne refund' || $state['name'] == 'QliroOne kreditera') {
                $exists_qliro_send_refund = true;
				Configuration::updateValue('QLIRO_SEND_REFUND', $state['id_order_state']);
			}
		}
        
		if ($exists_qliro_avvaktar == false) {
            $orderstate = New OrderState();
			foreach(Language::getLanguages(false) as $language) {
                if ($language['iso_code'] == 'sv') {
                    $names[$language['id_lang']] = 'QliroOne avvaktar';
                } else {
                    $names[$language['id_lang']] = 'QliroOne pending';
                }
			}
			$orderstate->name = $names;
			$orderstate->send_email = false;
			$orderstate->invoice = true;
			$orderstate->color = '#669900';
			$orderstate->unremovable = false;
			$orderstate->hidden = true;
			$orderstate->logable = true;
			$orderstate->save();
			Configuration::updateValue('QLIRO_PENDING_STATUS', $orderstate->id);
		}
        
		if ($exists_qliro_send_capture == false) {
            $orderstate = New OrderState();
			foreach(Language::getLanguages(false) as $language) {
                if ($language['iso_code'] == 'sv') {
                    $names[$language['id_lang']] = 'Debitera hos QliroOne';
                } else {
                    $names[$language['id_lang']] = 'QliroOne capture';
                }
			}
			$orderstate->name = $names;
			$orderstate->send_email = false;
			$orderstate->invoice = true;
			$orderstate->color = '#66ffcc';
			$orderstate->unremovable = false;
			$orderstate->hidden = true;
			$orderstate->logable = true;
			$orderstate->save();
			Configuration::updateValue('QLIRO_SEND_CAPTURE', $orderstate->id);
		}
		
		if ($exists_qliro_ready_to_be_shipped == false) {
            $orderstate = New OrderState();
			foreach(Language::getLanguages(false) as $language) {
                if ($language['iso_code'] == 'sv') {
                    $names[$language['id_lang']] = 'QliroOne redo att skickas';
                } else {
                    $names[$language['id_lang']] = 'QliroOne ready to be shipped';
                }
			}
			$orderstate->name = $names;
			$orderstate->send_email = false;
			$orderstate->invoice = true;
			$orderstate->color = '#ffff99';
			$orderstate->unremovable = false;
			$orderstate->hidden = true;
			$orderstate->logable = true;
			$orderstate->save();
			Configuration::updateValue('QLIRO_READY_TO_BE_SHIPPED', $orderstate->id);
		}
		
		if ($exists_qliro_send_reverse == false) {
            $orderstate = New OrderState();
			foreach(Language::getLanguages(false) as $language) {
                if ($language['iso_code'] == 'sv') {
                    $names[$language['id_lang']] = 'QliroOne makulera';
                } else {
                    $names[$language['id_lang']] = 'QliroOne reverse';
                }
			}
			$orderstate->name = $names;
			$orderstate->send_email = false;
			$orderstate->invoice = true;
			$orderstate->color = '#cc6600';
			$orderstate->unremovable = false;
			$orderstate->hidden = true;
			$orderstate->logable = true;
			$orderstate->save();
			Configuration::updateValue('QLIRO_SEND_REVERSE', $orderstate->id);
		}
		
		if ($exists_qliro_send_refund == false) {
            $orderstate = New OrderState();
			foreach(Language::getLanguages(false) as $language) {
                if ($language['iso_code'] == 'sv') {
                    $names[$language['id_lang']] = 'QliroOne kreditera';
                } else {
                    $names[$language['id_lang']] = 'QliroOne refund';
                }
			}
			$orderstate->name = $names;
			$orderstate->send_email = false;
			$orderstate->invoice = true;
			$orderstate->color = '#ff9966';
			$orderstate->unremovable = false;
			$orderstate->hidden = true;
			$orderstate->logable = true;
			$orderstate->save();
			Configuration::updateValue('QLIRO_SEND_REFUND', $orderstate->id);
		}
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        if(Tools::getValue('controller') != 'AdminModules' && Tools::getValue('configure') != 'pw_qlirocheckout') {
            return;
        }
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
    }

    public function hookDisplayAdminOrder($params) 
    {
        $orderId = $params['id_order'];
        $order = New Order($orderId);
        
        if ($order->module === $this->name) {
            
            $sql = 'SELECT * FROM '._DB_PREFIX_.'message WHERE id_order='.$orderId.' AND private=1 ORDER BY date_add DESC';
            $private_messages = Db::getInstance()->executeS($sql);
            $private_messages_count = count($private_messages);
            $sql = 'SELECT * FROM '._DB_PREFIX_.'qlirocheckout_payment_transactions WHERE ps_id_order = '.(int)$orderId;
            $qliro_transactions = Db::getInstance()->executeS($sql);
            $qliro_transactions_count = count($qliro_transactions);

            $this->context->smarty->assign(
                array(
                    'private_messages' => $private_messages,
                    'private_messages_count' => $private_messages_count,
                    'qliro_transactions' => $qliro_transactions,
                    'qliro_transactions_count' => $qliro_transactions_count,
                )
            );
            return $this->display(__FILE__, 'orderpage.tpl');
        }
    }
    
    public function hookDisplayPayment($params) {
        return;
    }
    
    public function getContent()
    {
        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
        ));
        
        $output = '';
        
        if (((bool)Tools::isSubmit('submitQlirocheckoutpayModule')) == true) {
            if ($this->postProcess()) {
                $output .= $this->displayConfirmation($this->l('Configurations was updated successfully'));
            } else {
                $output .= $this->displayError($this->l('Could not update configurations'));
            }
        }
        $this->context->controller->addJqueryPlugin('select2');
        $this->context->controller->addJS('module:pw_qlirocheckout/views/js/back.js');
        return $output.$this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitQlirocheckoutpayModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $fields_value = $this->getConfigFormValues();
        
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        $states = OrderState::getOrderStates((int) $this->context->cookie->id_lang);
        $carrier_references = Db::getInstance()->executeS('SELECT id_reference, name FROM `'._DB_PREFIX_.'carrier` WHERE deleted=0 GROUP BY id_reference');
        $arr = array();
        $arr[] = array(
            'id_status' => -1,
            'name'      => '- '.$this->l('Not used').' -'
        );
		foreach ($states as $id => $state) {
			$arr[] = array(
				'id_status' => $state["id_order_state"],
				'name'      => $state["name"]
			);
		}
        
        $arr_forced = array();
		foreach ($states as $id => $state) {
			$arr_forced[] = array(
				'id_status' => $state["id_order_state"],
				'name'      => $state["name"]
			);
		}

        $cms_pages = array();
        $cms_pages = CMS::listCms(null, false, true);
        
        $cms_pages_with_integrity = array();
        $cms_pages_with_integrity = $cms_pages;
        $cms_pages_with_integrity[] = array(
            'id_cms' => -1,
            'meta_title' => 'Do not use'
        );
        $form_array = array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('QliroOne settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'html',
                        'desc' => '<h4>'.$this->l('General settings').'</h4>',
                        'name' => '',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Activated'),
                        'name' => 'QLIRO_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Activate QliroOne Checkout in your store'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live Mode'),
                        'name' => 'QLIRO_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use Qliro One in Live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'html',
                        'desc' => '<h4>'.$this->l('Checkout Settings Test Environment').'</h4>',
                        'name' => '',
                    ),
                    array(
                        'type' => 'text',
                        'col' => 2,
                        'prefix' => '<i class="icon icon-user"></i>',
                        'name' => 'QLIRO_API_USERNAME_TEST',
                        'label' => $this->l('API Username'),
                        'desc' => $this->l('Provided by Qliro')
                    ),
                    array(
                        'type' => 'text',
                        'col' => 3,
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'QLIRO_API_PASSWORD_TEST',
                        'label' => $this->l('API Password'),
                        'desc' => $this->L('Provided by Qliro')
                    ),
                    array(
                        'type' => 'html',
                        'desc' => '<h4>'.$this->l('Checkout Settings Live Environment').'</h4>',
                        'name' => '',
                    ),
                    array(
                        'type' => 'text',
                        'col' => 2,
                        'prefix' => '<i class="icon icon-user"></i>',
                        'name' => 'QLIRO_API_USERNAME_LIVE',
                        'label' => $this->l('API Username'),
                        'desc' => $this->l('Provided by Qliro')
                    ),
                    array(
                        'type' => 'text',
                        'col' => 3,
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'QLIRO_API_PASSWORD_LIVE',
                        'label' => $this->l('API Password'),
                        'desc' => $this->L('Provided by Qliro')
                    ),
                    array(
                        'type' => 'html',
                        'desc' => '<h4>'.$this->l('Other Checkout Settings').'</h4>',
                        'name' => '',
                    ),
                    array(
                        'type'    => 'select',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id'   => 1,
                                    'name' => $this->l('B2B and B2C')
                                ),
                                array(
                                    'id'   => 2,
                                    'name' => $this->l('Only B2C')
                                ),
                                array(
                                    'id'   => 3,
                                    'name' => $this->l('Only B2B')
                                )
                            ),
                            'id'   => 'id',
                            'name' => 'name'
                        ),
                        'name'  => 'QLIRO_B2B_B2C',
                        'label' => $this->l('Type of customer'),
                        'desc'  => $this->l('Choose which type of customer can buy with Qliro One')
                    ),
                    array(
                        'type' => 'select',
                        'options' => array(
                            'query' => $cms_pages,
                            'id'    => 'id_cms',
                            'name'  => 'meta_title'
                        ),
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'QLIRO_TERMS_CMS',
                        'label' => $this->l('CMS page for terms and conditions'),
                        'desc' => $this->l('Choose between CMS pages in your shop'),
                        'class' => 'select2',
                    ),
                    array(
                        'type' => 'select',
                        'class' => 'select2',
                        'options' => array(
                            'query' => $cms_pages_with_integrity,
                            'id'    => 'id_cms',
                            'name'  => 'meta_title'
                        ),
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'QLIRO_INTEGRITY_CMS',
                        'label' => $this->l('CMS page for integrity policy'),
                        'desc' => $this->l('Choose between CMS pages in your shop')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Update merchant reference'),
                        'name' => 'QLIRO_UPDATE_MERCHANT_REFERENCE',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Use Customer Age Restrictions'),
                        'name' => 'QLIRO_USE_MINIMUM_CUSTOMER_AGE',
                        'is_bool' => true,
                        'desc' => $this->l('Use a restriction on the customer age'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'class' => 'col-lg-3',
                        'type' => 'text',
                        'col' => 1,
                        'name' => 'QLIRO_MINIMUM_CUSTOMER_AGE',
                        'label' => $this->l('Mimimum Customer Age'),
                        'desc' => $this->l('The customer will have to be at least this old to complete a purchase with QliroOne')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Customer has to verify with BankID'),
                        'name' => 'QLIRO_USE_REQUIRE_IDENTITY_VERIFICATION',
                        'is_bool' => true,
                        'desc' => $this->l('Verify customer with BankID'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Ask for newsletter signup'),
                        'name' => 'QLIRO_ASK_FOR_NEWSLETTER',
                        'is_bool' => true,
                        'desc' => $this->l('Make it possible for the customer to register to newsletter'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show order message field'),
                        'name' => 'QLIRO_SHOW_ORDER_MESSAGE',
                        'is_bool' => true,
                        'desc' => $this->l('Show a field for order message in Qliro One'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Use Invoice fee'),
                        'name' => 'QLIRO_INVOICE_FEE',
                        'is_bool' => true,
                        'desc' => $this->l('Select Yes if you have an invoice fee in your Qliro One dashboard'),
                        'hint' => $this->l('Remember to create a product that exactly matches the price and VAT with your Qliro One Invoice fee.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'class' => 'fixed-width-xl',
                        'type' => 'text',
                        'name' => 'QLIRO_FEE_REFERENCE',
                        'label' => $this->l('Invoice fee product reference'),
                        'hint' => $this->l('This value should refer to your Prestashop product used for adding a fee to customers paying by invoice.'),
                    ),
                    array(
                        'type' => 'html',
                        'desc' => '<h4>'.$this->l('Upsell settings').'</h4>',
                        'name' => '',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Use upsell'),
                        'name' => 'QLIRO_USE_UPSELL',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'html',
                        'label' => $this->l('Number of products'),
                        'name' => '',
                        'col' => 1,
                        'html_content' => '<input type="number" name="QLIRO_UPSELL_NUM_OF_PRODUCTS" value="'.Configuration::get('QLIRO_UPSELL_NUM_OF_PRODUCTS') .'">',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Upsell product selection'),
                        'name' => 'QLIRO_UPSELL_PRODUCT_SELECTION',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id' => 'CATEGORY',
                                    'name' => $this->l('Category'),
                                ),
                                array(
                                    'id' => 'ACCESSORY',
                                    'name' => $this->l('Accessory'),
                                ),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'QLIRO_UPSELL_ID_CATEGORY',
                        'label' => $this->l('Product selection category'),
                        'description' => $this->l('The category to select products from'),
                        'class' => 'select2',
                        'options' => array(
                            'query' => Category::getAllCategoriesName(null, $this->context->language->id),
                            'id' => 'id_category',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'html',
                        'desc' => '<h4>'.$this->l('Order status mapping').'</h4>',
                        'name' => '',
                    ),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('OnHold status'),
                        'name' => 'QLIRO_PENDING_STATUS',
                        'desc' => $this->l('Order status for pending purchases'),
                        'options' => array(
	                        'query' => $arr_forced,
	                        'id' => 'id_status',
	                        'name' => 'name',
	                    ),
                	),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Completed status'),
                        'name' => 'QLIRO_ACCEPTED_STATUS',
                        'desc' => $this->l('Order status for completed purchases'),
                        'options' => array(
	                        'query' => $arr_forced,
	                        'id' => 'id_status',
	                        'name' => 'name',
	                    ),
                	),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Refused status'),
                        'name' => 'QLIRO_REJECTED_STATUS',
                        'desc' => $this->l('Order status for refused purchases'),
                        'options' => array(
                            'query' => $arr_forced,
                            'id' => 'id_status',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Capture payment'),
                        'name' => 'QLIRO_SEND_CAPTURE',
                        'hint' => $this->l('When you change the status of an order to this status, a request to capture the payment in Qliro will be sent'),
                        'desc' => $this->l('Send capture request to Qliro'),
                        'options' => array(
                            'query' => $arr,
                            'id' => 'id_status',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Order ready to be sent'),
                        'name' => 'QLIRO_READY_TO_BE_SHIPPED',
                        'hint' => $this->l('An order will get this status if a request to capture the payment in Qliro gives successfull result'),
                        'desc' => $this->l('Status of orders ready to be shipped'),
                        'options' => array(
                            'query' => $arr,
                            'id' => 'id_status',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Reverse payment'),
                        'name' => 'QLIRO_SEND_REVERSE',
                        'hint' => $this->l('When you change the status of an order to this status, a request to reverse the payment in Qliro will be sent'),
                        'desc' => $this->l('Send request to reverse a payment'),
                        'options' => array(
                            'query' => $arr,
                            'id' => 'id_status',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'select',
                        'label' => $this->l('Refund payment'),
                        'name' => 'QLIRO_SEND_REFUND',
                        'hint' => $this->l('When you change the status of an order to this status, a request to refund the payment in Qliro will be sent'),
                        'desc' => $this->l('Send request to refund a payment'),
                        'options' => array(
                            'query' => $arr,
                            'id' => 'id_status',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'html',
                        'desc' => '<h4>'.$this->l('Design settings').'</h4>',
                        'name' => '',
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Two columns design'),
                        'name' => 'QLIRO_TWO_COLUMNS',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Peronalize the checkout layout with color'),
                        'name' => 'QLIRO_ADAPT_DESIGN',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'color',
                        'name' => 'QLIRO_ADAPT_BACKGROUND_COLOR',
                        'label' => $this->l('Background Color'),
                        'desc' => $this->l('Hex color code to use as background color in Qliro One'),
                    ),
                    array(
                        'type' => 'color',
                        'name' => 'QLIRO_ADAPT_PRIMARY_COLOR',
                        'label' => $this->l('Primary Color'),
                        'desc' => $this->l('Hex color code to use as primary color in Qliro One'),
                    ),
                    array(
                        'type' => 'color',
                        'name' => 'QLIRO_ADAPT_CALL_TO_ACTION_COLOR',
                        'label' => $this->l('Call to Action Color'),
                        'desc' => $this->l('Hex color code to use as call to action color in Qliro One'),
                    ),
                    array(
                        'type' => 'color',
                        'name' => 'QLIRO_ADAPT_CALL_TO_ACTION_HOVER_COLOR',
                        'label' => $this->l('Call to Action hover Color'),
                        'desc' => $this->l('Hex color code to use as call to action hover color in Qliro One'),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'QLIRO_ADAPT_CORNER_RADIUS',
                        'label' => $this->l('Corner Radius'),
                        'desc' => $this->l('A pixel value to be used on corners throughout Qliro One'),
                        'col' => 1
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'QLIRO_ADAPT_BUTTON_CORNER_RADIUS',
                        'label' => $this->l('Button corner Radius'),
                        'desc' => $this->l('A pixel value to be used on corners of buttons throughout Qliro One'),
                        'col' => 1
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        
        return  $form_array;
    }

    protected function getConfigFormValues()
    {
        $form_values = array(
            // Qliro Checkout Settings
            'QLIRO_ACTIVE'    => Configuration::get('QLIRO_ACTIVE'),
            'QLIRO_LIVE_MODE' => Configuration::get('QLIRO_LIVE_MODE'),
            
            'QLIRO_B2B_B2C' => Configuration::get('QLIRO_B2B_B2C'),
            
            // Qliro Checkout API Settings
            'QLIRO_API_USERNAME_TEST' => Configuration::get('QLIRO_API_USERNAME_TEST'),
            'QLIRO_API_PASSWORD_TEST' => Configuration::get('QLIRO_API_PASSWORD_TEST'),
            
            'QLIRO_API_USERNAME_LIVE' => Configuration::get('QLIRO_API_USERNAME_LIVE'),
            'QLIRO_API_PASSWORD_LIVE' => Configuration::get('QLIRO_API_PASSWORD_LIVE'),

            // Qliro Checkout Order Management
            'QLIRO_PENDING_STATUS'      => Configuration::get('QLIRO_PENDING_STATUS'),
            'QLIRO_SEND_CAPTURE'        => Configuration::get('QLIRO_SEND_CAPTURE'),
            'QLIRO_READY_TO_BE_SHIPPED' => Configuration::get('QLIRO_READY_TO_BE_SHIPPED'),
            'QLIRO_SEND_REVERSE'        => Configuration::get('QLIRO_SEND_REVERSE'),
            'QLIRO_SEND_REFUND'         => Configuration::get('QLIRO_SEND_REFUND'),
            'QLIRO_REJECTED_STATUS'     => Configuration::get('QLIRO_REJECTED_STATUS'),
            'QLIRO_ACCEPTED_STATUS'     => Configuration::get('QLIRO_ACCEPTED_STATUS'),
            
            // Qliro Terms and Conditions
            'QLIRO_TERMS_CMS'     => Configuration::get('QLIRO_TERMS_CMS'),
            'QLIRO_INTEGRITY_CMS' => Configuration::get('QLIRO_INTEGRITY_CMS'),
            
            // Qliro Checkout General Option
            'QLIRO_USE_MINIMUM_CUSTOMER_AGE'            => Configuration::get('QLIRO_USE_MINIMUM_CUSTOMER_AGE'),
            'QLIRO_MINIMUM_CUSTOMER_AGE'                => Configuration::get('QLIRO_MINIMUM_CUSTOMER_AGE'),
            'QLIRO_USE_REQUIRE_IDENTITY_VERIFICATION'   => Configuration::get('QLIRO_USE_REQUIRE_IDENTITY_VERIFICATION'),
            'QLIRO_ASK_FOR_NEWSLETTER'                  => Configuration::get('QLIRO_ASK_FOR_NEWSLETTER'),
            'QLIRO_SHOW_ORDER_MESSAGE'                  => Configuration::get('QLIRO_SHOW_ORDER_MESSAGE'),
            'QLIRO_UPDATE_MERCHANT_REFERENCE'           => Configuration::get('QLIRO_UPDATE_MERCHANT_REFERENCE'),

            //Qliro Upsell Setting
            'QLIRO_USE_UPSELL'                          => Configuration::get('QLIRO_USE_UPSELL'),
            'QLIRO_UPSELL_NUM_OF_PRODUCTS'              => Configuration::get('QLIRO_UPSELL_NUM_OF_PRODUCTS'),
            'QLIRO_UPSELL_PRODUCT_SELECTION'            => Configuration::get('QLIRO_UPSELL_PRODUCT_SELECTION'),
            'QLIRO_UPSELL_ID_CATEGORY'                  => Configuration::get('QLIRO_UPSELL_ID_CATEGORY'),
            
            'QLIRO_INVOICE_FEE'         => Configuration::get('QLIRO_INVOICE_FEE', false),
            'QLIRO_FEE_REFERENCE'       => Configuration::get('QLIRO_FEE_REFERENCE', ''),
            
            // Design Settings
            'QLIRO_ADAPT_DESIGN'                     => Configuration::get('QLIRO_ADAPT_DESIGN'),
            'QLIRO_ADAPT_BACKGROUND_COLOR'           => Configuration::get('QLIRO_ADAPT_BACKGROUND_COLOR'),
            'QLIRO_ADAPT_PRIMARY_COLOR'              => Configuration::get('QLIRO_ADAPT_PRIMARY_COLOR'),
            'QLIRO_ADAPT_CALL_TO_ACTION_COLOR'       => Configuration::get('QLIRO_ADAPT_CALL_TO_ACTION_COLOR'),
            'QLIRO_ADAPT_CALL_TO_ACTION_HOVER_COLOR' => Configuration::get('QLIRO_ADAPT_CALL_TO_ACTION_HOVER_COLOR'),
            'QLIRO_ADAPT_CORNER_RADIUS'              => Configuration::get('QLIRO_ADAPT_CORNER_RADIUS'),
            'QLIRO_ADAPT_BUTTON_CORNER_RADIUS'       => Configuration::get('QLIRO_ADAPT_BUTTON_CORNER_RADIUS'),
            'QLIRO_TWO_COLUMNS'                      => Configuration::get('QLIRO_TWO_COLUMNS')
        );
        
        return $form_values;
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        
        foreach (array_keys($form_values) as $key) {
            if ($key == 'QLIRO_MINIMUM_CUSTOMER_AGE') {
                Configuration::updateValue($key, (int)Tools::getValue($key));
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
        
        return true;
    }

    public function hookDisplayHeader()
    {
		if ((int)Configuration::get('QLIRO_ACTIVE')) {
            $qliro_checkout_url = $this->context->link->getModuleLink($this->name, 'checkout', array(), true);
            $qlirocheckout_shipping_updates_url = $this->context->link->getModuleLink($this->name, 'handleshippingoptionchanges',  array(), true);
            
            $this->context->controller->addJS($this->_path.'/views/js/front.js');
            $this->context->controller->addCSS($this->_path.'/views/css/front.css');
            
            Media::addJsDef(array('qlirocheckout_url' => $qliro_checkout_url));
            Media::addJsDef(array('qlirocheckout_shipping_updates_url' => $qlirocheckout_shipping_updates_url));
        }
    }

    //Use this hook for partial returns
    // public function hookActionOrderSlipAdd($params)
    // {

    // }

    // QLIRO HANDLE ORDERS FROM BO
    public function hookActionOrderStatusUpdate($params)
    {
        $new_order_status = $params['newOrderStatus'];
        $new_status_id = $new_order_status->id;
        
        $qliro_send_capture = (int)Configuration::get('QLIRO_SEND_CAPTURE'); // Debitera
        $qliro_send_reverse = (int)Configuration::get('QLIRO_SEND_REVERSE'); // Makulera
        $qliro_send_refund = (int)Configuration::get('QLIRO_SEND_REFUND'); // Kreditera
        
        $order_id = (int)$params['id_order'];
        
        $order = new Order($order_id);
        
        if ($order->module == $this->name) {
            if ($new_status_id == $qliro_send_capture OR $new_status_id == $qliro_send_reverse OR $new_status_id == $qliro_send_refund) {
                $qliro_order = $this->getQliroOrderBackOffice($order_id);
                
                if (!$qliro_order) {
                    $this->addOrderMessage('Could not find Qliro order ID in the database', $order_id);
                } else {
                    if ($qliro_order['response_code'] == 200 OR $qliro_order['response_code'] == 201) {
                        
                        $qliro_order = json_decode($qliro_order['response'], JSON_PRETTY_PRINT);
                        
                        $request_body = array();
                        
                        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
                            $MerchantApiKey = Configuration::get('QLIRO_API_USERNAME_LIVE');
                        } else {
                            $MerchantApiKey = Configuration::get('QLIRO_API_USERNAME_TEST');
                        }
                        
                        $RequestId  = $this->getGUID();
                        
                        $OrderId    = $qliro_order['OrderId'];
                        
                        $Currency   = $qliro_order['Currency'];
                        
                        $OrderItems = $qliro_order['OrderItemActions'];

                        $PaymentTransactionIds = [];

                        $PaymentTransactions = $qliro_order['PaymentTransactions'];
                        foreach ($PaymentTransactions as $transaction) {
                            if ($transaction['Status'] == 'Success') {
                                $PaymentTransactionIds[$transaction['PaymentTransactionId']] = [];
                            }
                        }
                        
                        foreach ($OrderItems as $key => &$row) {
                            if (!isset($PaymentTransactionIds[$row['PaymentTransactionId']])) {
                                unset($OrderItems[$key]);
                            } else {
                                $PaymentTransactionIds[$row['PaymentTransactionId']][] = $row;
                            }
                        }
                        
                        if ($new_status_id == $qliro_send_capture) { // Debitera
                            $shipments = [];
                            foreach ($PaymentTransactionIds as $PaymentTransactionId => &$PaymentTransactionItems) {
                                foreach ($PaymentTransactionItems as &$item) {
                                    unset($item['PricePerItemExVat']);
                                    unset($item['PaymentTransactionId']);
                                    unset($item['ActionType']);
                                    if (isset($item['MetaData'])) {
                                        unset($item['MetaData']);
                                    }
                                    unset($item['Description']);
                                }
                                $shipments[] = [
                                    'PaymentTransactionId' => $PaymentTransactionId,
                                    'OrderItems' => $PaymentTransactionItems,
                                ];
                            }
                            $request_body['RequestId']                  = $RequestId;
                            $request_body['MerchantApiKey']             = $MerchantApiKey;
                            $request_body['OrderId']                    = $OrderId;
                            $request_body['Currency']                   = $Currency;
                            // $OrderItems                              = $this->multi_unique($OrderItems);
                            $request_body['Shipments']                  = $shipments;
                            // $request_body['Shipments'][]['OrderItems']    = array_values($PaymentTransactionItems);
                            // $request_body['Shipments'][0]['PaymentTransactionId'] = $PaymentTransactionId;
                            // $request_body['OrderItems']                 = array_values($OrderItems);
                            $mark_item_as_shipped_result = $this->markItemsAsShipped($request_body);
                            
                            if (is_array($mark_item_as_shipped_result) AND ($mark_item_as_shipped_result['response_code'] == 200 OR $mark_item_as_shipped_result['response_code'] == 201)) {
                                $PaymentTransactions = @$mark_item_as_shipped_result['response']->PaymentTransactions;
                                foreach ($PaymentTransactions as $key => $PaymentTransaction) {
                                    Db::getInstance()->insert('qlirocheckout_payment_transactions',
                                    [
                                        'ps_id_order' => $order_id,
                                        'PaymentTransactionId' => $PaymentTransaction->PaymentTransactionId,
                                        'Status' => $PaymentTransaction->Status,
                                        'PaymentType' => 'Capture',
                                    ]);
                                    $PaymentTransactionId = $PaymentTransaction->PaymentTransactionId;
                                    $this->addOrderMessage('QLIRO capture request sent, paymentTransactionId is '.$PaymentTransactionId, $order_id);
                                }
                                
                                Db::getInstance()->execute("UPDATE "._DB_PREFIX_."qlirocheckout SET paymentTransactionId = '".$PaymentTransactionId."' WHERE qliro_order_id = ".$OrderId." AND ps_id_order = ".$order_id."");
                                

                                
                            } else {
                                
                                $this->addOrderMessage('QLIRO capture request failed, reason : '.json_encode($mark_item_as_shipped_result),$order_id);//['response'], $order_id);
                            }
                        } elseif ($new_status_id == $qliro_send_reverse) { // Makulera
                            
                            $request_body['MerchantApiKey'] = $MerchantApiKey;
                            $request_body['OrderId']        = $OrderId;
                            $request_body['RequestId']      = $RequestId;
                            
                            $cancel_order_result = $this->cancelOrder($request_body);
                            
                            if (is_array($cancel_order_result) AND ($cancel_order_result['response_code'] == 200 OR $cancel_order_result['response_code'] == 201)) {
                                
                                $PaymentTransactions = @$cancel_order_result['response']->PaymentTransactions;
                                foreach ($PaymentTransactions as $PaymentTransaction) {
                                    Db::getInstance()->insert('qlirocheckout_payment_transactions',
                                    [
                                        'ps_id_order' => $order_id,
                                        'PaymentTransactionId' => $PaymentTransaction->PaymentTransactionId,
                                        'Status' => $PaymentTransaction->Status,
                                        'PaymentType' => 'Reversal',
                                    ]);
                                    $PaymentTransactionId = $PaymentTransaction->PaymentTransactionId;
                                }
                                
                                Db::getInstance()->execute("UPDATE "._DB_PREFIX_."qlirocheckout SET paymentTransactionId = '".$PaymentTransactionId."' WHERE qliro_order_id = ".$OrderId." AND ps_id_order = ".$order_id."");
                                
                                $this->addOrderMessage('QLIRO cancel request Sent, paymentTransactionId is '.$PaymentTransactionId, $order_id);
                                
                            } else {
                                
                                $this->addOrderMessage('QLIRO cancel request Failed, reason : '.$cancel_order_result['response'], $order_id);
                                
                            }
                            
                        } elseif ($new_status_id == $qliro_send_refund) { // Kreditera
                            $returns =[];
                            foreach ($PaymentTransactionIds as $PaymentTransactionId => &$PaymentTransactionItems) {
                                foreach ($PaymentTransactionItems as $key => &$item) {
                                    if ($item['ActionType'] != 'Ship') {
                                        unset($PaymentTransactionItems[$key]);
                                        continue;
                                    }
                                    unset($item['PricePerItemExVat']);
                                    unset($item['PaymentTransactionId']);
                                    unset($item['ActionType']);
                                    if (isset($item['MetaData'])) {
                                        unset($item['MetaData']);
                                    }
                                    unset($item['Description']);
                                }
                                if (empty($PaymentTransactionIds[$PaymentTransactionId])) {
                                    unset($PaymentTransactionIds[$PaymentTransactionId]);
                                } else {
                                    $returns[] = [
                                        'PaymentTransactionId' => $PaymentTransactionId,
                                        'OrderItems' => $PaymentTransactionItems,
                                    ];
                                }
                            }
                            // $request_body['PaymentReference']   = Db::getInstance()->getValue("SELECT payment_reference FROM "._DB_PREFIX_."qlirocheckout WHERE ps_id_order = ".$order_id."");
                            $request_body['RequestId']          = $RequestId;
                            $request_body['MerchantApiKey']     = $MerchantApiKey;
                            $request_body['OrderId']            = $OrderId;
                            $request_body['Currency']           = $Currency;
                            // $returns = [];
                            // $returns['PaymentTransactionId']    = $PaymentTransactionId;//Db::getInstance()->getValue("SELECT PaymentTransactionId FROM "._DB_PREFIX_."qlirocheckout_payment_transactions WHERE ps_id_order = ".(int)$order_id." AND PaymentType = 'Capture' AND Status = 'Success'");
                            // $returns['OrderItems']              = array_values($PaymentTransactionItems);
                            
                            $request_body['Returns']            = $returns;

                            $refund_order_result = $this->refundOrder($request_body);
                            
                            if (is_array($refund_order_result) AND ($refund_order_result['response_code'] == 200 OR $refund_order_result['response_code'] == 201)) {
                                
                                $PaymentTransactions = @$refund_order_result['response']->PaymentTransactions;
                                foreach ($PaymentTransactions as $key => $PaymentTransaction) {
                                    Db::getInstance()->insert('qlirocheckout_payment_transactions',
                                    [
                                        'ps_id_order' => $order_id,
                                        'PaymentTransactionId' => $PaymentTransaction->PaymentTransactionId,
                                        'Status' => $PaymentTransaction->Status,
                                        'PaymentType' => 'Refund',
                                    ]);
                                    $PaymentTransactionId = $PaymentTransaction->PaymentTransactionId;
                                }
                                
                                Db::getInstance()->execute("UPDATE "._DB_PREFIX_."qlirocheckout SET paymentTransactionId = '".$PaymentTransactionId."' WHERE qliro_order_id = ".$OrderId." AND ps_id_order = ".$order_id."");
                                
                                $this->addOrderMessage('QLIRO refund request Sent, paymentTransactionId is '.$PaymentTransactionId, $order_id);
                                
                            } else {
                                
                                $this->addOrderMessage('QLIRO refund request Failed, reason : '.$refund_order_result['response'], $order_id);
                                
                            }
                        }
                        
                    } else {
                        $this->addOrderMessage('Could not fetch Qliro order', $order_id);
                    }
                }
            }
        }
    }
    
    public function multi_unique($src)
    {
        $output = array_map("unserialize", array_unique(array_map("serialize", $src)));
        
        return $output;
    }
    
    // GET QLIRO ORDER FROM PS BACK OFFICE
    public function getQliroOrderBackOffice($order_id)
    {
        $qliro_order_id = Db::getInstance()->getValue("SELECT qliro_order_id FROM "._DB_PREFIX_."qlirocheckout WHERE ps_id_order = ".$order_id."");
        
        if ($qliro_order_id) {
            $path = 'checkout/adminapi/v2/orders/'.$qliro_order_id;
            
            if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
                $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
                $endpoint_url = 'https://payments.qit.nu/';
            } else {
                $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
                $endpoint_url = 'https://pago.qit.nu/';
            }
            
            $qliro_url = $endpoint_url.$path;
            
            // cURL INFORMATION
            $curl = curl_init();
            
            curl_setopt($curl, CURLOPT_URL, $qliro_url);
             curl_setopt($curl, CURLOPT_HTTPHEADER,
                array(
                    "Content-Type: application/json",
                    "Authorization: ".$this->createAuthHeader('', $merchantApiPassword)
                 )
            );
            
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            
            // Get response (header, header size, error codes etc.)
            $response      = curl_exec($curl);
            $header_size   = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header        = substr($response, 0, $header_size);
            $body          = json_decode(substr($response, $header_size));
            $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            curl_close($curl);
            
            // Build return array
            $to_return = array();
            $to_return['response_code'] = $response_code;
            
            $message = '';
            
            if ($response_code == 200 OR $response_code == 201) {
                $response_message = $response;
            } else {
                $response         = json_decode($response);
                $response_message = @$response->ErrorCode;
                $message          = @$response->ErrorMessage;
                $reference        = @$response->ErrorReference;
                
            }
            
            $to_return['response']  = $response_message;
            $to_return['message']   = $message;
            $to_return['reference'] = $reference;
            
            return $to_return;
        } else {
            return false;
        }
    }

    // QLIRO GET PAYMENT TRANSACTION
    public function getQliroPaymentTransaction($paymentTransactionId)
    {
        /*
        * GET
        * /v2/paymentTransactions/{id}
        */
        $path = 'checkout/adminapi/v2/paymentTransactions/'.$paymentTransactionId;
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        $qliro_url = $endpoint_url.$path;
        
        // cURL INFORMATION
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader('', $merchantApiPassword)
             )
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response      = curl_exec($curl);
        $header_size   = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header        = substr($response, 0, $header_size);
        $body          = json_decode(substr($response, $header_size));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        $response = json_decode($response);
        // Build return array
        $to_return = array();
        $to_return['response_code'] = $response_code;
        
        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        
        return $to_return;
    }
    
    // QLIRO MARK ITEMS AS SHIPPED
    public function markItemsAsShipped($request_body)
    {
        $request_body = json_encode($request_body, JSON_PRETTY_PRINT);
        //$path = 'checkout/adminapi/markitemsasshipped/withitems';
        $path = 'checkout/adminapi/v2/markitemsasshipped';    
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        
        $qliro_url = $endpoint_url.$path;
        
        // cURL INFORMATION
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader($request_body, $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request_body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        // $response      = json_decode(curl_exec($curl));
        $response      = curl_exec($curl);
        $header_size   = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header        = substr($response, 0, $header_size);
        $body          = json_decode(substr($response, $header_size));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        // Build return array
        $to_return = array();
        $to_return['response_code'] = $response_code;
        
        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        
        return $to_return;
    }
    
    // QLIRO CANCEL ORDER
    public function cancelOrder($request_body)
    {
        $request_body = json_encode($request_body);
        
        // $path = 'checkout/adminapi/cancelorder';
        $path = 'checkout/adminapi/v2/cancelorder';
            
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        
        $qliro_url = $endpoint_url.$path;
        
        // cURL INFORMATION
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader($request_body, $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request_body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $response      = json_decode(curl_exec($curl));
        $header_size   = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header        = substr($response, 0, $header_size);
        $body          = json_decode(substr($response, $header_size));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        
        // Build return array
        $to_return = array();
        $to_return['response_code'] = $response_code;
        
        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        
        return $to_return;
    }
    
     // QLIRO REFUND ORDER
    public function refundOrder($request_body)
    {
        $request_body = json_encode($request_body, JSON_PRETTY_PRINT);
        //$path = 'checkout/adminapi/returnwithitems';
        $path = 'checkout/adminapi/v2/returnitems';
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        
        $qliro_url = $endpoint_url.$path;
        
        // cURL INFORMATION
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader($request_body, $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request_body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $response      = json_decode(curl_exec($curl));
        $header_size   = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header        = substr($response, 0, $header_size);
        $body          = json_decode(substr($response, $header_size));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
        // Build return array
        $to_return = array();
        $to_return['response_code'] = $response_code;

        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        
        return $to_return;
    }
    
    // CREATE QLIRO FUNCTION
    public function createQliroCheckout($checkout_information)
    {
        $body_of_request = json_encode($checkout_information, JSON_PRETTY_PRINT);
        
        $path = 'checkout/merchantapi/orders';
        
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        
        $qliro_url = $endpoint_url.$path;
        
        // CURL INFORMATION
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader($body_of_request, $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body_of_request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        // cURL RESPONSE
        $response      = json_decode(curl_exec($curl));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error_curl    = curl_error($curl);
        $error_no_curl = curl_errno($curl);
        
        curl_close($curl);
        
        // RETURN ARRAY
        $to_return = array();
        $to_return['response_code'] = $response_code;
        
        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        return $to_return;
    }

    public function updateQliroMerchantReference($qliro_order_id, $new_merchant_reference) {
        
        $path = 'checkout/adminapi/V2/updatemerchantreference';
        
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $merchantApiKey = Configuration::get('QLIRO_API_USERNAME_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $merchantApiKey = Configuration::get('QLIRO_API_USERNAME_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        $body_of_request = json_encode([
            'RequestId' => $this->getGUID(),
            'OrderId' => $qliro_order_id,
            'MerchantApiKey' => $merchantApiKey,
            'NewMerchantReference' => $new_merchant_reference,
        ], JSON_PRETTY_PRINT);
        
        $qliro_url = $endpoint_url.$path;
        
        // CURL INFORMATION
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader($body_of_request, $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body_of_request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        // cURL RESPONSE
        $response      = json_decode(curl_exec($curl));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error_curl    = curl_error($curl);
        $error_no_curl = curl_errno($curl);
        
        curl_close($curl);
        // RETURN ARRAY
        $to_return = array();
        $to_return['response_code'] = $response_code;
        
        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        
        return $to_return;
    }
    
    public function createQliroUpsell($qliro_order_id, $currency_iso, $order_items) {
        
        $path = 'checkout/merchantapi/upsell';
        
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $merchantApiKey = Configuration::get('QLIRO_API_USERNAME_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $merchantApiKey = Configuration::get('QLIRO_API_USERNAME_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        $body_of_request = json_encode([
            'RequestId' => $this->getGUID(),
            'OrderId' => $qliro_order_id,
            'MerchantApiKey' => $merchantApiKey,
            'Currency' => $currency_iso,
            'OrderItems' => $order_items,
        ], JSON_PRETTY_PRINT);
        
        $qliro_url = $endpoint_url.$path;
        // CURL INFORMATION
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader($body_of_request, $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body_of_request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        // cURL RESPONSE
        $response      = json_decode(curl_exec($curl));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error_curl    = curl_error($curl);
        $error_no_curl = curl_errno($curl);
        
        curl_close($curl);
        // RETURN ARRAY
        $to_return = array();
        $to_return['response_code'] = $response_code;
        
        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        
        return $to_return;
    }

    // SAVE QLIRO ONE INFORMATION IN THE DB
    public function saveQliroCheckoutInformationInDbAtCreation($id_cart, $qliro_order_id, $qliro_merchant_reference, $country_iso_code, $currency_iso_code)
    {
        $datetime = date("Y-m-d H:i:s");
        
        $sql_save_query = "INSERT INTO "._DB_PREFIX_."qlirocheckout (ps_id_cart, qliro_order_id, qliro_merchant_reference, ps_id_shop, ps_country_iso, ps_currency_iso, qliro_status, create_date, update_date)
                            VALUES (".$id_cart.", ".$qliro_order_id.", '".$qliro_merchant_reference."', ".$this->context->shop->id.", '".$country_iso_code."', '".$currency_iso_code."', 'inProcess', '".$datetime."', '".$datetime."')";
        
        Db::getInstance()->execute($sql_save_query);
    }
    
    public function updateQliroCheckoutInDb($id_cart, $qliro_order_id, $qliro_merchant_reference, $country_iso_code, $currency_iso_code)
    {
        $datetime = date("Y-m-d H:i:s");
        
        $sql = "UPDATE "._DB_PREFIX_."qlirocheckout
            SET update_date = '".$datetime."'
            WHERE qliro_order_id = ".$qliro_order_id."
                AND qliro_merchant_reference = '".$qliro_merchant_reference."'
                AND ps_id_cart = ".$id_cart."
                AND ps_country_iso = '".$country_iso_code."'
                AND ps_currency_iso = '".$currency_iso_code."'";
        
        $response = Db::getInstance()->execute($sql);
    }

    public function updateQliroOneCheckout($id_cart, $checkout_information, $qliro_order_id = null, $country_iso_code = null, $currency_iso_code = null)
    {
        $body_of_request = json_encode($checkout_information, JSON_PRETTY_PRINT);
        
        if ($qliro_order_id == null) {
            $sql = "SELECT qliro_order_id
                    FROM "._DB_PREFIX_."qlirocheckout
                    WHERE ps_id_cart = ".$id_cart."
                        AND ps_country_iso = '".$country_iso_code."'
                        AND ps_currency_iso = '".$currency_iso_code."'
                        AND ps_id_shop = ".$this->context->shop->id."
                        AND qliro_status = 'inProcess'
                    ORDER BY update_date DESC";
            
            $qliro_order_id = Db::getInstance()->getValue($sql);
        }
        
        $path = 'checkout/merchantapi/orders/'.$qliro_order_id;
        
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        
        $qliro_url = $endpoint_url.$path;
        
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader($body_of_request, $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body_of_request);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $response      = curl_exec($curl);
        $header_size   = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header        = substr($response, 0, $header_size);
        $body          = json_decode(substr($response, $header_size));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error_curl    = curl_error($curl);
        $error_no_curl = curl_errno($curl);
        return json_decode($response_code);
    }
    
    public function getQliroCheckout($id_cart, $qliro_order_id, $country_iso_code, $currency_iso_code, $confirmation = false)
    {
        if ($qliro_order_id == null) {
            $query = "SELECT qliro_order_id, create_date
                    FROM "._DB_PREFIX_."qlirocheckout
                    WHERE ps_id_cart = ".$id_cart."
                        AND ps_country_iso = '".$country_iso_code."'
                        AND ps_currency_iso = '".$currency_iso_code."'
                        AND ps_id_shop = ".$this->context->shop->id."
                        ORDER BY update_date DESC";
                        //AND qliro_status = 'inProcess'
                    
            $qliro_order_id = Db::getInstance()->getRow($query);
        }
        
        if ($qliro_order_id == null OR $qliro_order_id == false) {
            return false;
        }
        
        // QLIRO SESSION EXPIRES AFTER 90 MINUTES, QLIRO ORDER EXPIRES AFTER 24 HOURS
        
        $qliro_time_of_creation = $qliro_order_id['create_date'];
        
        $qliro_time_of_creation = strtotime($qliro_time_of_creation); // THIS SHOULD BE THE SAME AS THE MERCHANT REFERENCE (BUT IT IS A STRING)
        $now  = strtotime("now"); // UNIX TIMESTAMP TO NOW
        
        $oneHourInSeconds = 1 * 60 * 50; // CREATE A NEW ONE AFTER 50 MINUTES
        
        $qliro_session_lifetime = $now - $qliro_time_of_creation;
        
        if (($qliro_session_lifetime > $oneHourInSeconds) AND !$confirmation) {
            return false;
        }
        
        $path = 'checkout/merchantapi/orders/'.$qliro_order_id['qliro_order_id'];
        
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        
        $qliro_url = $endpoint_url.$path;
        
        // Setup curl information
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader('', $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        // Get response (header, header size, error codes etc.)
        $response      = curl_exec($curl);
        $header_size   = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header        = substr($response, 0, $header_size);
        $body          = json_decode(substr($response, $header_size));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error_curl    = curl_error($curl);
        $error_no_curl = curl_errno($curl);
        
        curl_close($curl);
        
        // Build return array
        $to_return = array();
        $to_return['response_code'] = $response_code;
        
        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        
        return $to_return;
    }
    
    public function getQliroCheckoutOnNotification($qliro_order_id)
    {
        $path = 'checkout/merchantapi/orders/'.$qliro_order_id;
        
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_LIVE');
            $endpoint_url = 'https://payments.qit.nu/';
        } else {
            $merchantApiPassword = Configuration::get('QLIRO_API_PASSWORD_TEST');
            $endpoint_url = 'https://pago.qit.nu/';
        }
        
        $qliro_url = $endpoint_url.$path;
        
        // Setup curl information
        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, $qliro_url);
         curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                "Content-Type: application/json",
                "Authorization: ".$this->createAuthHeader('', $merchantApiPassword)
             )
        );
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        
        // Get response (header, header size, error codes etc.)
        $response      = curl_exec($curl);
        $header_size   = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header        = substr($response, 0, $header_size);
        $body          = json_decode(substr($response, $header_size));
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error_curl    = curl_error($curl);
        $error_no_curl = curl_errno($curl);
        
        curl_close($curl);
        
        // Build return array
        $to_return = array();
        $to_return['response_code'] = $response_code;
        
        $message = '';
        
        if ($response_code == 200 OR $response_code == 201) {
            $response = $response;
        } else {
            $response = @$response->ErrorCode;
            $message  = explode('Property', @$response->ErrorMessage);
            unset($message[0]);
        }
        
        $to_return['response'] = $response;
        $to_return['message']  = $message;
        
        return $to_return;
    }
    
    // Build json object depending on if we have to update the existing Qliro Checkout or create a new Qliro Checkout
    public function getBasicCheckouApiInformation(Cart $cart, $country_iso, $currency_iso, $language_iso_code, $has_to_update)
    {
        // Create URL parameters to use for notifications calls from Qliro One
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $token = Configuration::get('QLIRO_API_PASSWORD_LIVE');
        } else {
            $token = Configuration::get('QLIRO_API_PASSWORD_TEST');
        }
        
        $id_cart  = $cart->id;
        $id_shop  = $this->context->shop->id;
        
        $checkout_information = array();
        
        if (!$has_to_update) {
            $checkout_information['MerchantReference'] = date("U");
        }
        
        if ((int)Configuration::get('QLIRO_LIVE_MODE')) {
            $checkout_information['MerchantApiKey'] = Configuration::get('QLIRO_API_USERNAME_LIVE');
        } else {
            $checkout_information['MerchantApiKey'] = Configuration::get('QLIRO_API_USERNAME_TEST');
        }
      
        if (!$has_to_update) {
            $checkout_information['Country']                                   = strtoupper($country_iso);
            $checkout_information['Currency']                                  = strtoupper($currency_iso);
            $checkout_information['Language']                                  = $this->getLanguageCode($language_iso_code);
        
            $checkout_information['MerchantConfirmationUrl']                   = $this->context->link->getModuleLink($this->name, 'confirmation', array(), Tools::usingSecureMode()) . '?id_cart='.(int)$id_cart.'&id_shop='.$id_shop.'&country_iso='.$country_iso.'&currency_iso='.$currency_iso.'&token='.$this->createConfirmationKey($cart->id, $this->context->shop->id, $token);
            $checkout_information['MerchantCheckoutStatusPushUrl']             = $this->context->link->getModuleLink($this->name, 'notification', array(), Tools::usingSecureMode()) . '?id_cart='.(int)$id_cart.'&id_shop='.$id_shop.'&token='.$this->createConfirmationKey($cart->id, $this->context->shop->id, $token);
            $checkout_information['MerchantOrderManagementStatusPushUrl']      = $this->context->link->getModuleLink($this->name, 'ordermanagementstatuspush', array(), Tools::usingSecureMode());
            $checkout_information['MerchantOrderValidationUrl']                = $this->context->link->getModuleLink($this->name, 'ordervalidation', array(), Tools::usingSecureMode()) . '?id_cart='.(int)$id_cart.'&id_shop='.$id_shop.'&token='.$this->createConfirmationKey($cart->id, $this->context->shop->id, $token);
            $checkout_information['MerchantOrderAvailableShippingMethodsUrl']  = $this->context->link->getModuleLink($this->name, 'orderavailableshippingmethods', array(), Tools::usingSecureMode()) . '?id_cart='.(int)$id_cart.'&id_shop='.$id_shop.'&token='.$this->createConfirmationKey($cart->id, $this->context->shop->id, $token);
        
            $checkout_information['MerchantTermsUrl']                          = $this->context->link->getCMSLink(new CMS((int)Configuration::get('QLIRO_TERMS_CMS'), $this->context->language->id));
            $checkout_information['MerchantIntegrityPolicyUrl']                = $this->context->link->getCMSLink(new CMS((int)Configuration::get('QLIRO_INTEGRITY_CMS'), $this->context->language->id));
        }
        
        $checkout_information['OrderItems']  = $this->getOrderItems($cart);

        if (!$has_to_update AND (int)Configuration::get('QLIRO_ADAPT_DESIGN')) {
            $checkout_information['BackgroundColor']                           = Configuration::get('QLIRO_ADAPT_BACKGROUND_COLOR');
            $checkout_information['PrimaryColor']                              = Configuration::get('QLIRO_ADAPT_PRIMARY_COLOR');
            $checkout_information['CallToActionColor']                         = Configuration::get('QLIRO_ADAPT_CALL_TO_ACTION_COLOR');
            $checkout_information['CallToActionHoverColor']                    = Configuration::get('QLIRO_ADAPT_CALL_TO_ACTION_HOVER_COLOR');
            $checkout_information['CornerRadius']                              = (int)Configuration::get('QLIRO_ADAPT_CORNER_RADIUS');
            $checkout_information['ButtonCornerRadius']                        = (int)Configuration::get('QLIRO_ADAPT_BUTTON_CORNER_RADIUS');
        }
        
        // PREFILL CUSTOMER INFORMATION IF POSSIBLE
        if (!$has_to_update) {
            $customer_info = $this->prefilWithCustomerInformation($id_cart);
            if (is_array($customer_info) AND !empty($customer_info)) {
                $checkout_information['CustomerInformation'] = $this->prefilWithCustomerInformation($id_cart);
            }
        }
        
         // B2B or B2C
        if (!$has_to_update) {
            if ((int)Configuration::get('QLIRO_B2B_B2C') == 2) {
                $checkout_information['EnforcedJuridicalType'] = 'Physical';
            } elseif ((int)Configuration::get('QLIRO_B2B_B2C') == 3) {
                $checkout_information['EnforcedJuridicalType'] = 'Company';
            }
        }
        
        // SOME EXTRA SETTINGS
        if ((int)Configuration::get('QLIRO_USE_MINIMUM_CUSTOMER_AGE') AND !$has_to_update) {
            $checkout_information['MinimumCustomerAge'] = (int)Configuration::get('QLIRO_MINIMUM_CUSTOMER_AGE');
        }
        
        if ((int)Configuration::get('QLIRO_USE_REQUIRE_IDENTITY_VERIFICATION') AND $country_iso == 'SE') {
            $checkout_information['RequireIdentityVerification'] = true;
        }
        
        if ((int)Configuration::get('QLIRO_ASK_FOR_NEWSLETTER')) {
            $checkout_information['AskForNewsletterSignup'] = true;
        }
        
        // if (!$has_to_update) {
        $checkout_information['AvailableShippingMethods'] = $this->getQliroAvailableShippingMethods($cart, $country_iso);
        // }
        
        return $checkout_information;

    }
    
    public function prefilWithCustomerInformation($id_cart)
    {
        $customer_proceeding_to_checkout_information = $this->context->customer;
        
        $customer_information = array();
        $customer_address = array();
        
        if ($customer_proceeding_to_checkout_information->logged) {
            $customer_proceeding_to_checkout_address = $this->context->customer->getAddresses($this->context->language->id);
            
            if (is_array($customer_proceeding_to_checkout_address) AND !empty($customer_proceeding_to_checkout_address)) {
                $customer_proceeding_to_checkout_address = $customer_proceeding_to_checkout_address[0];
            }
            
            $Email     = $customer_proceeding_to_checkout_information->email;
            $FirstName = $customer_proceeding_to_checkout_information->firstname;
            $LastName  = $customer_proceeding_to_checkout_information->lastname;
            
            $Street     = $customer_proceeding_to_checkout_address['address1'];
            $PostalCode = $customer_proceeding_to_checkout_address['postcode'];
            $City       = $customer_proceeding_to_checkout_address['city'];
            
            $MobileNumber = $customer_proceeding_to_checkout_address['phone_mobile'];
            
            $customer_information['Email']        = $Email;
            $customer_information['MobileNumber'] = $MobileNumber;
            
            $customer_address['FirstName']  = $FirstName;
            $customer_address['LastName']   = $LastName;
            $customer_address['Street']     = $Street;
            $customer_address['PostalCode'] = $PostalCode;
            $customer_address['City']       = $City;
            
            $customer_information['Address'] = $customer_address;
            
            return $customer_information;
        }
    }
    
    public function updateQliroCheckoutStatusInDatabase($qliro_order_id)
    {
        $sql = "UPDATE "._DB_PREFIX_."qlirocheckout SET qliro_status = 'Expired' WHERE qliro_order_id = ".$qliro_order_id."";
        
        $response = Db::getInstance()->execute($sql);
        
        if ($response) {
            return 'OK';
        } else {
            return 'NOK';
        }
    }
    
    public function getLanguageCode($language_iso_code)
    {
        switch ($language_iso_code) {
            case $language_iso_code == 'sv':
                return 'sv-se';
                break;
           case $language_iso_code == 'fi':
                return 'fi-fi';
                break;
            case $language_iso_code == 'da':
                return 'da-dk';
                break;
            case $language_iso_code == 'no':
                return 'nb-no';
                break;
            default :
                return 'sv-se';   
        }
    }
    
    public function getOrderItems(Cart $cart)
    {
        $products = array();
        $discounts = array();
        
        $rows = array();
        
        // PRODUCTS
        $products = $cart->getProducts();
        foreach ($products as $product) {
            $row = array();
            
            $merchant_reference = $product['id_product'];
            if (strlen($merchant_reference > 200)) {
                $merchant_reference = mb_substr($merchant_reference, 0, 50);
            }
            
            $description = strip_tags($product['name']);
            if (strlen($description > 200)) {
                $description = mb_substr($description, 0, 50);
            }
            
            $type               = 'Product';
            $quantity           = (int)$product['cart_quantity'];
            $pricePerItemIncVat = $this->qlirocheckoutRound($product['price_with_reduction']);
            $pricePerItemExVat  = $this->qlirocheckoutRound($product['price_with_reduction_without_tax']);
            
            $row['MerchantReference']  = $merchant_reference;
            $row['Type']               = $type;
            $row['Quantity']           = $quantity;
            $row['PricePerItemIncVat'] = $pricePerItemIncVat;
            $row['PricePerItemExVat']  = $pricePerItemExVat;
            $row['Description']        = $description;
            
            $rows[] = $row;
        }
        
        // DISCCOUNTS
        $discounts = $cart->getCartRules();
        foreach ($discounts as $discount) {
            // if ($discount['free_shipping'] != 1) {
                $row = array();

                $merchant_reference = $discount['name'];
                // $merchant_reference = preg_replace('~[^\p{L}\p{N}]~', ' ', $merchant_reference);
                $merchant_reference = preg_replace("/[^[:alnum:][:space:]]/u", '', $merchant_reference);
                if (strlen($merchant_reference > 200)) {
                    $merchant_reference = mb_substr($merchant_reference, 0, 50);
                }
                $type = 'Discount';
                $quantity = 1;
                $pricePerItemIncVat = $this->qlirocheckoutRound($discount['value_real']);;
                $pricePerItemExVat  = $this->qlirocheckoutRound($discount['value_tax_exc']);;
                
                $row['MerchantReference']  = $merchant_reference;
                $row['Type']               = $type;
                $row['Quantity']           = $quantity;
                $row['PricePerItemIncVat'] = $pricePerItemIncVat;
                $row['PricePerItemExVat']  = $pricePerItemExVat;
                $row['Description']        = $merchant_reference;
                
                $rows[] = $row;
            // }
        }
        
        // GIFT WRAPPER
        if ($cart->gift == 1) {
            $cart_wrapping = $cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
            
            if ($cart_wrapping > 0) {
                
                $row = array();
                
                $wrapping_cost_excl = $this->context->cart->getOrderTotal(false, Cart::ONLY_WRAPPING);
                $wrapping_cost_incl = $this->context->cart->getOrderTotal(true, Cart::ONLY_WRAPPING);
                
                $merchant_reference = $this->l('Gift Wrapping');
                // $merchant_reference = preg_replace('~[^\p{L}\p{N}]~', ' ', $merchant_reference);
                $merchant_reference = preg_replace("/[^[:alnum:][:space:]]/u", '', $merchant_reference);
                
                $row['MerchantReference']  = $merchant_reference;
                $row['Type']               = 'Product';
                $row['Quantity']           = 1;
                $row['PricePerItemIncVat'] = $wrapping_cost_incl;
                $row['PricePerItemExVat']  = $wrapping_cost_excl;
                $row['Description']        = $this->l('Gift Wrapping');
                
                $rows[] = $row;
            }
        }
        return $rows;
    }
    
    // Function that returns available shipping methods
    public function getQliroAvailableShippingMethods(Cart $cart, $country_iso)
    {
        // Check if free shipping cart rule
        $free_shipping = false;
        foreach ($cart->getCartRules() as $rule) {
            if ($rule['free_shipping']) {
                $free_shipping = true;
                break;
            }
        }
        
        $id_lang = $cart->id_lang;
        
        $country = new Country(Country::getByIso($country_iso));
        
        $delivery_options = array();
        
        $delivery_option_list = $cart->getDeliveryOptionList($country);
        
        $supported_brands = array('AramexBest', 'Bring', 'Budbee', 'DHL', 'Instabox', 'MTD', 'Posti', 'PostNord', 'Postnord', 'Schenker', 'UPS');
        
        foreach ($delivery_option_list as $id_address => $option_list) {
            $delivery_option = array();
            foreach ($option_list as $key => $option) {
                if (isset($option['unique_carrier']) AND $option['unique_carrier'] == 1) {
                    $descriptions = array();
                    foreach ($option['carrier_list'] as $carrier) {
                        $merchant_reference = $carrier['instance']->name;
                        $displayName        = $carrier['instance']->name;
                        
                        $description = array();
                        
                        foreach ($carrier['instance']->delay as $key => $delay) {
                            if ($key == $id_lang) {
                                $description[] = $carrier['instance']->delay[$key];
                            }
                            
                            $descriptions = $description;
                        }
                    }
                }
                
                if (isset($option['total_price_with_tax']) AND !$free_shipping) {
                    $priceIncVat = $option['total_price_with_tax'];
                } else {
                    $priceIncVat = 0;
                }
                
                if (isset($option['total_price_without_tax']) AND !$free_shipping) {
                    $priceExVat = $option['total_price_without_tax'];
                } else {
                    $priceExVat = 0;
                }
                
                $delivery_option['MerchantReference'] = $merchant_reference;
                $delivery_option['DisplayName']       = $displayName;
                $delivery_option['PriceIncVat']       = $priceIncVat;
                $delivery_option['PriceExVat']        = $priceExVat;
                $delivery_option['Descriptions']      = $descriptions;
                
                if (in_array($merchant_reference, $supported_brands)) {
                    $delivery_option['Brand'] = $merchant_reference;
                } else {
                    $delivery_option['Brand'] = '';
                }
                if ($cart->carrierIsSelected($carrier['instance']->id, $id_address)) {
                    array_unshift($delivery_options, $delivery_option);
                } else {
                    $delivery_options[] = $delivery_option;
                }
            }
        }
        
        return $delivery_options;
    }
    
    public function saveCustomerInfoInDatabase($email, $mobileNumber, $personalNumber, $organizationNumber, $firstName, $lastName, $street, $city, $postalCode, $ps_id_cart, $qliro_order_id)
    {
        Db::getInstance()->execute("INSERT INTO "._DB_PREFIX_."qliroprefilcustomer (ps_id_cart, qliro_order_id, email, mobileNumber, personalNumber, organizationNumber, firstName, lastName, street, city, postalCode)
                                    VALUES (".$ps_id_cart.", ".$qliro_order_id.", '".$email."', '".$mobileNumber."', '".$personalNumber."', '".$organizationNumber."', '".$firstName."', '".$lastName."', '".$street."', '".$city."', '".$postalCode."')
                                    ON DUPLICATE KEY UPDATE qliro_order_id = ".$qliro_order_id.", email = '".$email."', mobileNumber = '".$mobileNumber."',
                                                            personalNumber = '".$personalNumber."', organizationNumber = '".$organizationNumber."',
                                                            firstName = '".$firstName."', lastName = '".$lastName."',
                                                            street = '".$street."', city = '".$city."', postalCode = '".$postalCode."'");                   
    }
    
    public function createOrder($qliro_one_checkout_order_id, $qliro_one_merchant_reference, $qliro_customer_checkout_status, $qliro_one_customer, $qliro_one_billing_address, $qliro_one_shipping_address, $qliro_one_order_items, $total_payed, $country_iso_code, $qliro_country_id, $payment_name, $id_cart, $id_shop, $customer_signup_for_newsletter)
    {
        $cart = new Cart($id_cart);
        
        // Create Customer and Address
        $cart = $this->createCustomerAndAddress($cart, $qliro_one_customer, $qliro_one_billing_address, $qliro_one_shipping_address, $qliro_country_id, $customer_signup_for_newsletter);
        
        /* 
         * Handle delivery option
         *
         * Qliro One handles the shipping interaction with the customer. Therefore, nothing is saved in the cart.
         *
         * The system has so retrieve the selected shipping option, and then handle the delivery option for the cart
         *
         */
        
        // ADD INVOICE FEE
        $fee_product_ref = Configuration::get('QLIRO_FEE_REFERENCE');
        if ($payment_name == 'QLIRO_INVOICE' && Configuration::get('QLIRO_INVOICE_FEE') == 1 && strlen($fee_product_ref) > 0) {
            
            $fee_product = $this->getFeeByReference($fee_product_ref, $cart->id_lang, $cart->id_shop);
            if ($fee_product > 0) {
                $insert_sql = 'INSERT INTO '._DB_PREFIX_.'cart_product
                                     (id_address_delivery, id_shop, id_cart, id_product, id_product_attribute, quantity, date_add)
                               VALUES('.(int)$cart->id_address_delivery.','.(int)$cart->id_shop.','.(int)$cart->id.','.(int)$fee_product.', 0 ,1 ,\''.pSQL(date('Y-m-d h:i:s')).'\')';
                Db::getInstance()->Execute($insert_sql);
                $cart->update(true);
            } else {
                PrestaShopLogger::addLog('Qliro One: Invoice fee product not found'.' ('.$fee_product_ref.')', 1, null, null, null, true);                            
            }
        }
        
        $shipping_reference = '';
        $all_carriers = Carrier::getCarriers($this->context->language->id, true);
        $selected_id_carrier = 0;
        
        $new_delivery_options = array();
        foreach ($qliro_one_order_items as $key => $item) {
            if ($item->Type == 'Shipping') {
                $shipping_reference = $qliro_one_order_items[$key]->MerchantReference;
            }
        }
        
        foreach ($all_carriers as $key => $carrier) {
            if ($carrier['name'] == $shipping_reference) {
                $selected_id_carrier = $carrier['id_carrier'];
            }
        }
        
        $new_delivery_options[$cart->id_address_delivery] = $selected_id_carrier.',';
        if (version_compare(_PS_VERSION_, "1.6.1.21", "<")) {
            $new_delivery_options_serialized = serialize($new_delivery_options);
        } else {
            $new_delivery_options_serialized = json_encode($new_delivery_options);
        }
            
        // MANUALLY CORRECT DELIVERY OPTION IN DB
        $update_sql = 'UPDATE '._DB_PREFIX_.'cart '.
            'SET delivery_option=\''.
            pSQL($new_delivery_options_serialized).
            '\' WHERE id_cart='.
            (int)$cart->id;
        
        Db::getInstance()->execute($update_sql);
        
        if ($selected_id_carrier > 0) {
            $cart->delivery_option = $new_delivery_options_serialized;
        } else {
            $cart->delivery_option = '';
        }
        
        // TWO MORE MANUAL UPDATES IN DB
       $update_sql = 'UPDATE '._DB_PREFIX_.'cart_product '.
            'SET id_address_delivery='.(int)$cart->id_address_delivery.
            ' WHERE id_cart='.(int) $cart->id;
            
        Db::getInstance()->execute($update_sql);

        $update_sql = 'UPDATE '._DB_PREFIX_.'customization '.
            'SET id_address_delivery='.(int)$cart->id_address_delivery.
            ' WHERE id_cart='.(int) $cart->id;
            
        Db::getInstance()->execute($update_sql);
        
        // CACHE FIX AND STUFF
        $cart->getPackageList(true);
        $cart->getDeliveryOptionList(null, true);
        $cart->save();
        
        // MANUAL DB FIX
        $update_sql = 'UPDATE '._DB_PREFIX_.'cart '.
            'SET id_customer='.(int) $cart->id_customer.
            ', secure_key=\''.pSQL($cart->secure_key).
            '\' WHERE id_cart='.(int)$cart->id;
        Db::getInstance()->execute($update_sql);

        $cache_id = 'objectmodel_cart_'.$cart->id.'*';
        Cache::clean($cache_id);
        
        // NEW FRESH CART
        $cart = new Cart($cart->id);
        
        if ($qliro_customer_checkout_status == 'Completed') {
            $order_status = Configuration::get('QLIRO_ACCEPTED_STATUS');
        } elseif ($qliro_customer_checkout_status == 'OnHold') {
            $order_status = Configuration::get('QLIRO_PENDING_STATUS');
        } elseif ($qliro_customer_checkout_status == 'Refused') {
            $order_status = Configuration::get('QLIRO_REJECTED_STATUS');
        } else {
            PrestaShopLogger::addLog('Qliro One Checkout: Unknown order and purchase status, Validation Aborted: External ('.$id_cart.'), Internal ('.$qliro_one_checkout_order_id.')', 1, null, null, null, true);
        }
        
        // QLIRO PAYMENT NAME
        $payment_method = $payment_name;
		
		// Check if order is already being created somewhere else.
		// This validation is to prevent duplicate orders
		$dbi = Db::getInstance();
		$dbi->execute('UPDATE '._DB_PREFIX_.'qlirocheckout SET ps_created=1 WHERE ps_id_cart='.(int)$cart->id.' AND ps_created=0');
		if (!($dbi->Affected_Rows() > 0)) {
			PrestaShopLogger::addLog('PS order is already being created somewhere else', 1, null, null, null, true);
			return false;
		}
		
        if ($this->validateOrder((int)$cart->id,  // VALIDATE ORDER IN PAYMENT MODULE CLASS
            $order_status,
            $total_payed,
            'Qliro One '.$payment_method,
            null,
            array('transaction_id' => $qliro_one_checkout_order_id),
            (int)$cart->id_currency,
            false,
            $cart->secure_key)
        ) {
            // SOME STUFF RELATED TO THE ORDER
            $sql = "SELECT id_order, reference FROM "._DB_PREFIX_."orders WHERE id_cart=".$cart->id;
            $order_info = Db::getInstance()->getRow($sql);
            
            $id_order   = (int)$order_info['id_order'];
            $reference  = $order_info['reference'];
            
            $this->updateQliroPaymentStatus((int)$cart->id, (int)$cart->id_shop, $id_order, $payment_method, $qliro_one_checkout_order_id, $qliro_customer_checkout_status, $qliro_one_merchant_reference, $qliro_country_id);

            $transactionId = "";
            $status = "";
            if (Configuration::get('QLIRO_UPDATE_MERCHANT_REFERENCE')) {
                $update_columns = [];
                $response = $this->updateQliroMerchantReference($qliro_one_checkout_order_id, $id_order);
                PrestaShopLogger::addLog('UpdateMerchantReference: '.json_encode($response));
                if ($response['response_code'] == 200) {
                    $update_columns['qliro_merchant_reference'] = $id_order;
                    if ($response['response']->Type == 'UpdateMerchantReferenceWithTransactionResponse') {
                        $update_columns['paymentTransactionId'] = $response['response']->PaymentTransactionId;
                        $update_columns['qliro_status'] = $response['response']->Status;

                        Db::getInstance()->insert('qlirocheckout_payment_transactions', [
                            'ps_id_order' => (int)$id_order,
                            'PaymentTransactionId' => $response['response']->PaymentTransactionId,
                            'Status' => $response['response']->Status,
                            'PaymentType' => '',
                        ]);
                    }

                    Db::getInstance()->update('qlirocheckout', $update_columns, "ps_id_cart = '".(int)$cart->id."'
                        AND qliro_merchant_reference ='".$qliro_one_merchant_reference."'
                        AND ps_id_shop = '".(int)$cart->id_shop."'
                        AND qliro_order_id = '".$qliro_one_checkout_order_id."'
                        AND ps_country_iso = '".$country_iso_code."'");
                    
                    $qliro_one_merchant_reference = $id_order;
                }
            }

            $this->addOrderMessage('Qliro Order ID: '.$qliro_one_checkout_order_id.' | Qliro Reference ' .$qliro_one_merchant_reference.' | Status '.$qliro_customer_checkout_status, $id_order);
            
            return $id_order;
        } else {
            return false;
        }
    }
    
    public function getFeeByReference($invoiceref, $id_lang, $id_shop)
	{
		$result = (int)Db::getInstance()->getValue("SELECT id_product FROM `"._DB_PREFIX_."product` WHERE reference='".pSQL($invoiceref)."'");
		if (isset($result) && $result > 0) {
			return $result;
		} else {
			return 0;   
        }
	}
    
    public function createCustomerAndAddress($cart, $qliro_one_customer, $qliro_one_billing_address, $qliro_one_shipping_address, $qliro_country_id, $customer_signup_for_newsletter)
    {
        // B2C or B2B
        $b2c   = true;
        $vat_number   = '';
        $company_name = '';
        if ($qliro_one_customer->JuridicalType == 'Company') {
            $b2c          = false;
            $vat_number   = $qliro_one_customer->PersonalNumber;
            $company_name = $qliro_one_billing_address->CompanyName;
        }
        
        $id_country = $qliro_country_id;
        
        // Clean up customer e-mail and mobile_phone numbers
        $customer_email        = $this->cleanUpValue('EMAIL', date('YmdHis').'@qliroonemissing.se', $qliro_one_customer->Email);
        $customer_mobile_phone = $this->cleanUpValue('PHONE', '123-0000000', $qliro_one_customer->MobileNumber);
        
        $customer_firstname = $this->cleanUpValue('NAME', 'Nofirstname', $qliro_one_customer->FirstName);
        $customer_lastname  = $this->cleanUpValue('NAME', 'Nolastname', $qliro_one_customer->LastName);
        
        // Clean up billing address
        $billing_address_firstname  = $this->cleanUpValue('NAME', 'Nofirstname', $qliro_one_billing_address->FirstName);
        $billing_address_lastname   = $this->cleanUpValue('NAME', 'Nolastname', $qliro_one_billing_address->LastName);
        $billing_address_address    = $this->cleanUpValue('ADDRESS', 'Missing Address 1', $qliro_one_billing_address->Street);
        $billing_address_postalcode = $this->cleanUpValue('ZIP', '12345', $qliro_one_billing_address->PostalCode);
        $billing_address_city       = $this->cleanUpValue('CITY', 'CityMissing', $qliro_one_billing_address->City);
        
         // Clean up shipping address
        $shipping_address_firstname  = $this->cleanUpValue('NAME', 'Nofirstname', $qliro_one_shipping_address->FirstName);
        $shipping_address_lastname   = $this->cleanUpValue('NAME', 'Nolastname', $qliro_one_shipping_address->LastName);
        $shippingg_address_address   = $this->cleanUpValue('ADDRESS', 'Missing Address 1', $qliro_one_shipping_address->Street);
        $shipping_address_postalcode = $this->cleanUpValue('ZIP', '12345', $qliro_one_shipping_address->PostalCode);
        $shipping_address_city       = $this->cleanUpValue('CITY', 'CityMissing', $qliro_one_shipping_address->City);
        
        // Continue with customer and address creation
        $existing_customer = true;
        
        if (!isset($cart->id_customer) || $cart->id_customer <= 0) {
            $id_customer = (int)Customer::customerExists($customer_email, true, true);
            
            // If the customer already exists, retrieve it. Otherwise, create a new customer
            if ($id_customer > 0) {
                $customer = new Customer($id_customer);
                if (0 == $customer->newsletter && $customer_signup_for_newsletter == true) {
                    $customer->newsletter = 1;
                    $customer->update();
                }
            } else {
                $password = Tools::passwdGen(8);
                
                $customer = new Customer();
                $customer->firstname = $customer_firstname;
                $customer->lastname  = $customer_lastname;
                $customer->email     = $customer_email;
                $customer->passwd    = Tools::encrypt($password);
                $customer->is_guest = 0;
                $customer->id_default_group = (int)Configuration::get('PS_CUSTOMER_GROUP', null, $cart->id_shop);
                if ($customer_signup_for_newsletter == true) {
                    $customer->newsletter = 1;
                } else {
                    $customer->newsletter = 0;
                }
                $customer->optin = 0;
                $customer->active = 1;
                
                try {
                    $customer->add();
                    if (!$this->sendConfirmationMail($customer, $cart->id_lang, $password)) {
                        PrestaShopLogger::addLog('Qliro One Checkout: Failed sending welcome mail to: '.$customer->email, 1, null, null, null, true);
                    }
                } catch(Excpetion $e) {
                     PrestaShopLogger::addLog('Qliro One Checkout: Could not create customer: '.$e->getMessage().' ', 1, null, null, null, true);
                }
                
                $existing_customer = false;
            }
        } else {
            $customer = new Customer($cart->id_customer);
            if (0 == $customer->newsletter && $customer_signup_for_newsletter == true) {
                $customer->newsletter = 1;
                $customer->update();
            }
        }
        
        // Customer has been created, now procedee with addresses
        $cart->id_customer = $customer->id;
        $cart->secure_key  = $customer->secure_key;
        
        $id_address_invoice  = 0;
        $id_address_delivery = 0;
        
        // If the customer already exists, try to retrieve the already saved address
        if ($existing_customer) {
            $existing_addresses = $customer->getAddresses($cart->id_lang);
            if (isset($existing_addresses) && is_array($existing_addresses) && count($existing_addresses) > 0) {
                foreach ($existing_addresses as $address) {
                    if ($address['firstname']       == $billing_address_firstname
                        && $address['lastname']     == $billing_address_lastname
                        && $address['city']         == $billing_address_city
                        && $address['address1']     == $billing_address_address
                        && $address['postcode']     == $billing_address_postalcode
                        && $address['phone_mobile'] == $customer_mobile_phone
                        && $address['id_country']   == $id_country) {
                            $cart->id_address_invoice = $address['id_address'];
                            $id_address_invoice = $address['id_address'];
                        }
                    
                    if ($address['firstname']       == $shipping_address_firstname
                        && $address['lastname']     == $shipping_address_lastname
                        && $address['city']         == $shipping_address_city
                        && $address['address1']     == $shippingg_address_address
                        && $address['postcode']     == $shipping_address_postalcode
                        && $address['phone_mobile'] == $customer_mobile_phone
                        && $address['id_country']   == $id_country) {
                            $cart->id_address_delivery = $address['id_address'];
                            $id_address_delivery = $address['id_address'];
                        }
                    
                    if ($id_address_invoice > 0 && $id_address_delivery > 0) {
                        break;
                    }
                }
            }
        }
        
        // If there is not a matching address, create a new billing and delivery address
        if ($id_address_invoice == 0) {
            $id_address_invoice = $this->createQliroAddress($cart->id_customer, $id_country, $this->l('Qliro One Billing'), $qliro_one_billing_address, $customer_mobile_phone, $b2c, $vat_number, $company_name);
            
            $cart->id_address_invoice = $id_address_invoice;
        }
        
        if ($id_address_delivery == 0) {
            $id_address_delivery = $this->createQliroAddress($cart->id_customer, $id_country, $this->l('Qliro One Delivery'), $qliro_one_shipping_address, $customer_mobile_phone, $b2c, $vat_number, $company_name);
            
            $cart->id_address_delivery = $id_address_delivery;
        }
        
        return $cart;
    }
    
    // Clean up garbage characters
    private function cleanUpValue($type, $default, $value)
    {
        // If empty, return default value
        if (!isset($value) || trim($value) == '') {
            return $default;
        }
        switch ($type) {
            case "NAME": // Firstname and lastname
                return mb_substr(str_replace(str_split('0123456789!<>,;?=+(){}[]@#:'), '', trim($value)), 0, 32);
                break;
            case "EMAIL": // Email address
                $value = filter_var(trim($value), FILTER_SANITIZE_EMAIL);
                if ($value === false) { // If sanitize failed
                    return $default;
                }
                return $value;
                break;
            case "PHONE": // Phone number
                return preg_replace("/[^0-9\+\-]/", '', trim($value));
                break;
            case "ADDRESS": // Street address
                return mb_substr(str_replace(str_split('!<>,;?=+(){}[]@#:'), '', trim($value)), 0, 32);
                break;
            case "ZIP": // Postal code
                return preg_replace("/[^0-9]/", '', $value);
                break;
            case "CITY": // City name
                return mb_substr(str_replace(str_split('0123456789!<>,;?=+(){}[]@#:.'), '', trim($value)), 0, 64);
                break;
            case "COMPANY": // Company name
                return mb_substr(str_replace(str_split('!<>,;?=+(){}[]@#:'), '', trim($value)), 0, 60);
                break;  
            case 'OTHER': // Reference text
                return mb_substr(str_replace(str_split('!<>;?=+(){}[]@#:'), '', trim($value)), 0, 100);
            default:
                return $value;
        }
    }
    
    //check if address is correct country
    public function checkQliroAddress($id_address, $iso_code)
    {
        $address = new Address((int) $id_address);
        if ($address->id <= 0) {
            return false;
        }
        if ($address->id_customer > 0) {
            return false;
        }
        $country = new Country($address->id_country);
        if (strtolower($country->iso_code) != strtolower($iso_code)) {
            return false;
        }
        return true;
    }
    
    // Create a new address for the customer
    private function createQliroAddress($id_customer, $id_country, $name, $qliro_address, $phone_number, $b2c, $vat_number, $company_name)
    {
        $address = new Address();

        $address->firstname    = $qliro_address->FirstName;
        $address->lastname     = $qliro_address->LastName;
        $address->address1     = $qliro_address->Street;
        $address->postcode     = $qliro_address->PostalCode;
        $address->phone_mobile = $phone_number;
        $address->city         = $qliro_address->City;
        $address->id_country   = $id_country;
        $address->id_customer  = $id_customer;
        $address->alias        = $name;
        
        if (!$b2c AND $vat_number != '') {
            $address->vat_number = $vat_number;
            $address->company    = $company_name;
        }
            
        try {
            $address->add();
            return $address->id;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Qliro One Checkout: Address creation failed: '.$e->getMessage().' ', 1, null, null, null, true);
            return 0;
        }
    }
    
    public function getOrderMerchantReferenceFromCartId($id_cart, $country_iso_code)
    {
        return "QLIRO_PSCARTID_".$id_cart."_".$country_iso_code;
    }
    
    public function createAuthHeader($body_of_request, $merchantApiPassword)
    {
        return "Qliro ".base64_encode(hex2bin((hash('sha256', $body_of_request.$merchantApiPassword))));
    }
    
    public function createConfirmationKey($id_cart, $id_shop, $token) {
        return mb_substr(base64_encode('Confirmation:'.hash('sha256', $id_cart.$id_shop.$token)), 0, 20);
    }
    
    public function qlirocheckoutRound($total)
    {
        return Tools::ps_round($total, 2);
    }
    
    // Handles reponse for Qliro One Checkout API request
    public function response($resp, $page, $body = null)
    {
        if ($page == 'orderavailableshippingmethods') {
            header("Content-Type: application/json; charset=utf-8");
            if ($resp == 200) {
                http_response_code(200);
                echo json_encode($body, JSON_PRETTY_PRINT);
                die;
            } else if ($resp == 400) {
                http_response_code(400);
                echo json_encode($body, JSON_PRETTY_PRINT);
                die;
            }
        }
        
        if ($page == 'notification') {
            header("Content-Type: application/json; charset=utf-8");
            if ($resp == 200) {
                http_response_code(200);
                echo json_encode($body, JSON_PRETTY_PRINT);
                die;
            }
        }
        
        if ($page == 'ordermanagementstatuspush') {
            header("Content-Type: application/json; charset=utf-8");
            if ($resp == 200) {
                http_response_code(200);
                echo json_encode($body, JSON_PRETTY_PRINT);
                die;
            }
        }
        
        if ($page == 'ordervalidation') {
            header("Content-Type: application/json; charset=utf-8");
            if ($resp == 200) {
                http_response_code(200);
                echo json_encode($body, JSON_PRETTY_PRINT);
                die;
            } else if ($resp == 400) {
                http_response_code(400);
                echo json_encode($body, JSON_PRETTY_PRINT);
                die;
            }
        }
    }
    
    // UPDATE INFO IN DB AFTER PAYMENT
    public function updateQliroPaymentStatus($id_cart, $id_shop, $id_order, $paymentMethod, $qliro_one_order_id, $status, $qliro_one_merchant_reference, $qliro_country_id)
    {
        $datetime = date("Y-m-d H:i:s");
        
        $country_iso_code = Country::getIsoById($qliro_country_id);
        $sql = "UPDATE "._DB_PREFIX_."qlirocheckout
            SET ps_id_order=".(int)$id_order.", payment='".pSQL($paymentMethod)."', qliro_status ='".pSQL($status)."', update_date = '".$datetime."'
            WHERE ps_id_cart=".(int)$id_cart."
                AND ps_id_shop= ".(int)$id_shop."
                AND qliro_merchant_reference = '".$qliro_one_merchant_reference."'
                AND qliro_order_id = '".$qliro_one_order_id."'
                AND ps_country_iso = '".$country_iso_code."'";
        return Db::getInstance()->execute($sql);
    }
    
    // This function adds a message to the order
    public function addOrderMessage($message, $id_order)
    {
        $msg = new Message();
        $msg->message = $message;
        $msg->id_order = (int)$id_order;
        $msg->private = 1;
        $msg->add();
    }
    
    // This function creates fake addresses
    public function installAddress($countryCode, $countryAddressConfig)
    {
        $idCountry = Country::getByIso($countryCode);
        $country = new Country($idCountry);

        if (is_array($country->name)) {
            $countryName = reset($country->name);
        } else {
            $countryName = $country->name;
        }

        $address = new Address();
        $address->id_country = $country->id;
        $address->alias = sprintf('Qliro %s', $countryName);
        $address->address1 = 'Street 1';
        $address->address2 = '';
        $address->postcode = '00000';
        $address->city = 'City';
        $address->firstname = 'Qliro';
        $address->lastname = 'Checkout';
        $address->phone_mobile = '000000000';
        $address->id_customer = 0;
        $address->deleted = 0;
        
        if (!$address->save()) {
            PrestaShopLogger::addLog('Qliro One Checkout, coul not create address with country code '.$countryCode, 1, null, null, null, true);
        }

        Configuration::updateValue($countryAddressConfig, $address->id);

        return true;
    }
    
    public function sendConfirmationMail($customer, $id_lang, $psw)
    {
        if (!Configuration::get('PS_CUSTOMER_CREATION_EMAIL')) {
            return true;
        }
        try {
            return Mail::Send(
                $id_lang,
                'account',
                Mail::l('Welcome!', $id_lang),
                array(
                    '{firstname}' => $customer->firstname,
                    '{lastname}' => $customer->lastname,
                    '{email}' => $customer->email,
                    '{passwd}' => $psw, ),
                $customer->email,
                $customer->firstname.' '.$customer->lastname
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Qliro One Checkout: '.htmlspecialchars($e->getMessage()), 1, null, null, null, true);

            return false;
        }
    }
    
    // ALL COUNTRIES AND SEK, NOK, DKK, EUR
    public function getQliroCountryInformation($currency_iso_code, $country_iso_code)
    {
        // CREATE VARIABLES DEPENDING ON CURRENCY
        if ($currency_iso_code == 'SEK') {
            return array('purchase_country' => $country_iso_code, 'purchase_currency' => 'SEK');
        } elseif ($currency_iso_code == 'EUR') {
            return array('purchase_country' => $country_iso_code, 'purchase_currency' => 'EUR');
        } elseif ($currency_iso_code == 'NOK') {
            return array('purchase_country' => $country_iso_code, 'purchase_currency' => 'NOK');
        } elseif ($currency_iso_code == 'DKK') {
            return array('purchase_country' => $country_iso_code, 'purchase_currency' => 'DKK');
        } elseif ($currency_iso_code == 'GBP') {
            return array('purchase_country' => $country_iso_code, 'purchase_currency' => 'GBP');
        } else {
            return false;
        }
    }
    
    public function getGUID()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime() * 10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
                
            return $uuid;
        }
    }
}

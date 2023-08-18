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

function upgrade_module_1_0_4($module)
{
    try {
		$sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'qlirocheckout_payment_transactions` (
            `ps_id_order` int(11) NOT NULL,
            `PaymentTransactionId` text NOT NULL,
            `Status` text NOT NULL,
            `PaymentType` text NOT NULL
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';        
        Db::getInstance()->execute($sql);
    } catch (Exception $e) {
        // Nothing here
    }
    
    return true;
}

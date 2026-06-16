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

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'qlirocheckout` (
    `ps_id_cart` int(11) NOT NULL,
    `qliro_order_id` int(11) NOT NULL,
    `qliro_merchant_reference` text NOT NULL,
    `paymentTransactionId` text NOT NULL,
    `payment_reference` text NOT NULL,
    `ps_id_shop` int(11) NOT NULL,
    `ps_country_iso` text NOT NULL,
    `ps_currency_iso` text NOT NULL,
    `ps_id_order` int(11) NOT NULL DEFAULT 0,
    `payment` text NOT NULL,
    `qliro_status` text NOT NULL,
    `qliro_order_management_status` text NOT NULL,
    `activated` TINYINT(4) NOT NULL DEFAULT 0,
    `canceled` TINYINT(4) NOT NULL DEFAULT 0,
    `ps_created` TINYINT(4) NOT NULL DEFAULT 0,
    `credited` TINYINT(4) NOT NULL DEFAULT 0,
    `create_date` DATETIME NOT NULL DEFAULT \'0000-00-00\',
    `update_date` DATETIME NOT NULL DEFAULT \'0000-00-00\'
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'qlirocheckout_payment_transactions` (
    `ps_id_order` int(11) NOT NULL,
    `PaymentTransactionId` text NOT NULL,
    `Status` text NOT NULL,
    `PaymentType` text NOT NULL
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}

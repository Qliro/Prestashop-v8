{**
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
*}

<div class="card">
    <div class="card-heading">
        <i class="icon-envelope"></i>&nbsp;{l s='Qliro Checkout History' mod='pw_qlirocheckout'}<span class="badge">{$private_messages_count}</span>
        <button type="button" id="show-Qliro-messages" class="btn btn-primary" style="margin: 3px;">{l s='Show messages' mod='pw_qlirocheckout'}</button>
    </div>
    <div class="card card-highlighted" id="Qliro-messages" style="padding: 0; margin: 0; display: none;">
        <div class="message-item">
            {foreach $private_messages as $message}
                <div class="message-body" style="margin: 0;">
                    <span class="message-date">&nbsp;<i class="icon-calendar"></i>&nbsp;{$message.date_add}</span>
                    <p class="message-item-text">{$message.message}</p>
                </div>
            {/foreach}
        </div>
    </div>
</div>
<div class="card">
    <div class="card-heading">
        <i class="icon-money"></i>&nbsp;{l s='Qliro Checkout Payment Transactions' mod='pw_qlirocheckout'}<span class="badge">{$qliro_transactions_count}</span>
        <button type="button" id="show-Qliro-transactions" class="btn btn-primary" style="margin: 3px;">{l s='Show transactions' mod='pw_qlirocheckout'}</button>
    </div>
    <div class="card card-highlighted" id="Qliro-transactions" style="padding: 0; margin: 0; display: none;">
        <div class="message-item">
            {foreach $qliro_transactions as $transaction}
                <div class="message-body" style="margin: 0;">
                    <p class="message-item-text">
                    PaymentTransactionId: {$transaction.PaymentTransactionId}
                    Type: {$transaction.PaymentType}
                    Status: {$transaction.Status}
                    </p>
                </div>
            {/foreach}
        </div>
    </div>
</div>

<script>
    Qliro_show_text = "{l s='Show messages' mod='pw_qlirocheckout'}";
    Qliro_hide_text = "{l s='Hide messages' mod='pw_qlirocheckout'}";
    Qliro_show_transactions_text = "{l s='Show transactions' mod='pw_qlirocheckout'}";
    Qliro_hide_transactions_text = "{l s='Hide transactions' mod='pw_qlirocheckout'}";
    
    $(document).ready( function() {
        $('#show-Qliro-messages').on('click', function () {
             if ($('#Qliro-messages').is(":visible")) {
                $('#show-Qliro-messages').html(Qliro_show_text);
             } else {
                $('#show-Qliro-messages').html(Qliro_hide_text);
             }
             $('#Qliro-messages').slideToggle();
             
        });
    });
    $(document).ready( function() {
        $('#show-Qliro-transactions').on('click', function () {
             if ($('#Qliro-transactions').is(":visible")) {
                $('#show-Qliro-transactions').html(Qliro_show_transactions_text);
             } else {
                $('#show-Qliro-transactions').html(Qliro_hide_transactions_text);
             }
             $('#Qliro-transactions').slideToggle();
             
        });
    });
</script>
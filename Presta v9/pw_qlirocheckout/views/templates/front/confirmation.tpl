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

{extends $layout}
{block name='content'}
    {if !empty($qliro_upsell) && $qliro_upsell_info->EligibleForUpsell}
        <div class="row">
            <div class="col-xs-12 col-12 col-md-10 offset-md-1 col-xl-8 offset-xl-2">
                <div class="qliro-upsell">
                    <h1 class="h3 text-xs-center text-center">{l s='Do you want to add one of these items to your order?' mod='pw_qlirocheckout'}</h1>
                    <p id="qliro_upsell_timer" class="qliro_upsell_timer alert alert-info"></p>
                    <div class="row">
                        {foreach from=$qliro_upsell item=product}
                            {include file="module:pw_qlirocheckout/views/templates/catalog/_partials/upsell-miniature.tpl" product=$product}
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    {/if}
    <div id="qliro_thankyou_page" class="thankyou_page">
        <div class="row">
            {if isset($pw_qliro_confirmation_snippet)}
                <div class="col-xs-12 col-12 col-md-10 offset-md-1 col-xl-8 offset-xl-2">
                    {$pw_qliro_confirmation_snippet nofilter}
                </div>
            {/if}
        </div>
    </div>

    {if isset($HOOK_ORDER_CONFIRMATION)}
        <div class="row">
            <div class="col-md-12">
                {$HOOK_ORDER_CONFIRMATION nofilter}
            </div>
        </div>
    {/if}
{/block}
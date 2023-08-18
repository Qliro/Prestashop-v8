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

{capture name='qliroCountries'}
  {if $number_of_countries > 1}
    <div class="card">
      <div class="card-block">
        <h1 class="h1">{l s='Choose country' mod='pw_qlirocheckout'}</h1>
      </div>
      <div class="card-block">
        <form action="{$link->getModuleLink('pw_qlirocheckout', 'checkout', [], true)|escape:'html':'UTF-8'}" method="post" id="qliro_change_country">
          <select name="qliro_change_country" class="form-control" id="qliro_country_select" onchange="$('#qliro_change_country').submit();">
            {foreach $qliro_countries as $country}
              <option value="{$country['iso_code']}" {if $qliro_selected_country==$country['iso_code']}selected="selected"{/if}>{$country['country']}</option>
            {/foreach}
          </select>
        </form>
      </div>
    </div>
  {/if}
{/capture}

{capture name='qliroMessage'}
  {if $qliro_show_order_message == 1}
    <div class="card">
      <div class="card-block qliro-trigger" id="message">
        <h1 class="h1 qliro-trigger {if !$message.message}qliro-trigger--inactive{/if}" >
          {l s='Message' mod='pw_qlirocheckout'}<span class="material-icons"></span>
        </h1>
      </div>
      <div class="qliro-target" {if !$message.message}style="display: none;"{/if} id="message_container">
        <hr class="separator">
        <p class="card-block" id="messagearea" style="margin-bottom: 0px;">
          <textarea id="order_message" class="qliro-input qliro-input--area qliro-input--full" placeholder="{l s='Add additional information to your order (optional)' mod='pw_qlirocheckout'}">{$message.message|escape:'htmlall':'UTF-8'}</textarea>
          <button class="btn btn-primary" id="savemessagebutton" value="save" />{l s='Save' mod='pw_qlirocheckout'}</button>
        </p>
      </div>
    </div>
  {/if}
{/capture}

{capture name='qliroGift'}
  {if $giftAllowed == 1}
    <div class="card">
      <div class="card-block qliro-trigger" id="giftwrapping">
        <h1 class="h1 qliro-trigger {if $gift_message == '' && (!isset($gift) || $gift==0)}qliro-trigger--inactive{/if}">
          {l s='Giftwrapping' mod='pw_qlirocheckout'}<span class="material-icons"></span>
        </h1>
      </div>
      <div  class="qliro-target" {if $gift_message == '' && (!isset($gift) || $gift==0)}style="display: none;"{/if} id="giftwrapping_container">
        <hr class="separator">
        <p class="card-block" id="giftmessagearea_long" style="margin-bottom: 0px;">
          <textarea id="gift_message" class="qliro-input qliro-input--area qliro-input--full" placeholder="{l s='Gift message (optional)' mod='pw_qlirocheckout'}">{$gift_message|escape:'htmlall':'UTF-8'}</textarea>
          <button class="btn btn-primary" id="savegiftbutton" value="save" />{l s='Save' mod='pw_qlirocheckout'}</button>
          <span class="qliro-check-group">
            <input type="checkbox" style="margin: 4px;" onchange="changeGift()" class="giftwrapping_radio" id="gift" value="1"{if isset($gift) AND $gift==1} checked="checked"{/if} />
            <span id="giftwrappingextracost">{l s='Additional cost:' mod='pw_qlirocheckout'}&nbsp;<strong>{$gift_wrapping_price}</strong></span>
          </span>
        </p>
      </div>
    </div>
  {/if}
{/capture}

{capture name='qliroCheckout'}
  <div class="cart-grid-body">
    <div class="card">
      <div class="card-block">
        <h1 class="h1">
          {l s='Pay' mod='pw_qlirocheckout'}
        </h1>
      </div>
      {if isset($pw_qliro_one_checkout_has_error) && $pw_qliro_one_checkout_has_error==false}
        <div id="waiting-for-redirect" style="text-align: center; display: none; margin-bottom: 12px;">
          <div style="text-align: center; margin-top: 12px;">
            <h4 class="h4">
              {l s='You will be redirected to the confirmation page shortly' mod='pw_qlirocheckout'}
            </h4>
            <div class="qliro-spinner"><i></i><i></i><i></i><i></i></div>
          </div>
        </div>
        <div id="qlirocheckout_container">
          {$pw_qliro_one_checkout_snippet nofilter}
        </div>
      {else}
        {if isset($pw_qliro_one_checkout_error_message)}
          <div class="alert alert-warning">
            {$pw_qliro_one_checkout_error_message}
          </div>
        {/if}
      {/if}
    </div>
  </div>
{/capture}

{block name='content'}

  <div id="pwc" class="pwc">

    {if isset($pw_qliro_order_id)}
      <script type="text/javascript">
        var pw_qliro_order_id = "{$pw_qliro_order_id}";
      </script>
    {/if}

    {if isset($pw_qliro_one_checkout_has_error) && $pw_qliro_one_checkout_has_error}
      <div class="alert alert-warning">
        {l s='Something went wrong.' mod='pw_qlirocheckout'}<br />
        {l s='Please try again.' mod='pw_qlirocheckout'}
      </div>
    {/if}

    {if isset($vouchererrors) && $vouchererrors!=''}
      <div class="alert alert-warning">
        {$vouchererrors|escape:'html':'UTF-8'}
      </div>
    {/if}

    <div class="row mb-3" id="pwc-availableproduct-div" style="display:{if isset($pwc_available_product) AND $pwc_available_product=='no'}block{else}none{/if}">
      <div class="col-12">
        <div class="card my-0">
          <div class="card-block">
            <h4 class="h3 my-0">
              {l s='Product/s out of stock' mod='pw_qlirocheckout'}
            </h4>
          </div>
          <hr class="separator">
          <div class="card-block">
            <div class="alert alert-danger">
              {l s='Could not proceed to checkout, one or more product/s in your cart is not available' mod='pw_qlirocheckout'}<br>
            </div>
            <button class="btn btn-primary" onclick="location.reload();">{l s='Refresh page' mod='pw_qlirocheckout'}</button>
          </div>
        </div>
      </div>
    </div>

      <!-- SHOPPING CART -->
      <div class="row dynamic-content">
        <div class="col-xs-12 col-md-12">
          <div class="row" id="dynamic_cart_row">
            <div class="cart-grid-body col-xs-12 col-lg-8">
              <div class="card cart-container">
                <div class="card-block">
                  <h1 class="h1">{l s='Shopping cart' mod='pw_qlirocheckout'}</h1>
                </div>
                <hr class="separator">
                {include file='checkout/_partials/cart-detailed.tpl' cart=$pw_cart}
              </div>
              
              {block name='continue_shopping'}
                <a class="label" href="{$urls.pages.index}">
                  <i class="material-icons">chevron_left</i>{l s='Continue shopping' d='Shop.Theme.Actions'}
                </a>
              {/block}

              <!-- shipping informations -->
              {block name='hook_shopping_cart_footer'}
                {hook h='displayShoppingCartFooter'}
              {/block}
            </div>
            <div class="cart-grid-right col-xs-12 col-lg-4">
              {block name='cart_summary'}
                <div class="card cart-summary">
                  {block name='hook_shopping_cart'}
                    {hook h='displayShoppingCart'}
                  {/block}

                  {block name='cart_totals'}
                    {include file='checkout/_partials/cart-detailed-totals.tpl' cart=$pw_cart}
                  {/block}
                </div>
              {/block}
                
              {block name='hook_reassurance'}
                {hook h='displayReassurance'}
              {/block}
            </div>
          </div>
        </div>
      </div>

      <!-- CHECKOUT AND OPTIONS -->
    <div class="row">
      <div class="hx-co-loader" id="summary_loader" style="display: none;">
        <div class="loader loader-sm loader__checkout-start"></div>
      </div> 
      <div id="changes_out_of_stock_qliro" style="display:{if isset($pwc_available_product) AND $pwc_available_product=='no'}none{else}block{/if}">
        {if isset($two_columns_layout) && $two_columns_layout}
          <div id="dynamic_changes" class="col-12 col-xs-12 col-md-5">
            {$smarty.capture.qliroCountries nofilter}
            {$smarty.capture.qliroMessage nofilter}
            {$smarty.capture.qliroGift nofilter}
            {hook h='qliroCheckoutLeftColumn'}
          </div>
          <div class="col-12 col-xs-12 col-md-7">
            {$smarty.capture.qliroCheckout nofilter}
          </div>
        {else}
          <div id="dynamic_changes" class="dynamic-content">
            <div class="col-xs-12">
              <div class="row">
                <div class="cart-grid-body col-xs-12 col-md-4" style="margin: 0;">
                  {$smarty.capture.qliroCountries nofilter}
                </div>
                <div class="col-xs-12 col-md-{if $giftAllowed==1}4{else}8{/if}">
                  {$smarty.capture.qliroMessage nofilter}
                </div>
                <div class="col-xs-12 col-md-4">
                {$smarty.capture.qliroGift nofilter}
                </div>
              </div>
            </div>
          </div>
          <div class="col-xs-12">
            {$smarty.capture.qliroCheckout nofilter}
          </div>
        {/if}
      </div>
    </div>
    <div id="pwc-full-loader" class="pwc-full-loader pwc-full-loader--light" style="display: none;">
      <div class="pwc-loader pwc-loader--dark"></div>
    </div>

  </div>

{/block}
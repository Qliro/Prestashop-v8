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
    <div class="row card">
        <div class="card-block" style="padding: 0px;">
            <div class="alert alert-warning">
                {l s='We are sorry, but we could not show the order confirmation pag. Please contact the shop for more information about your order' mod='pw_qlirocheckout'}
            </div>
        </div>
    </div>
{/block}
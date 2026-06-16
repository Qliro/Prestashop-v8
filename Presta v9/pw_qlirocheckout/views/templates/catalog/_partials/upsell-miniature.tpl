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

<article class="qliro-upsell-item col-xs-12 col-12 col-sm-6 col-md-4 col-lg-3">
  <div class="thumbnail-container">
    <a href="{$product.url}" class="thumbnail product-thumbnail" target="_blank">
      <img
        class="img-responsive img-fluid"
        {if $product.cover}
          src="{$product.cover.bySize.home_default.url}"
          alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name}{/if}"
          data-full-size-image-url="{$product.cover.large.url}"
        {else}
          src="{$urls.no_picture_image.bySize.home_default.url}"
          alt="{$product.name}"
        {/if}
        loading="lazy"
        width="250"
        height="250"
      />
    </a>
    <div class="qliro-upsell-item-description">
      <h3 class="h3 product-title">
        <a href="{$product.url}" target="_blank">
          Hummingbird printed t-shirt
        </a>
      </h3>
      {if !empty($product.attributes)}
        <p class="upsell-attribute-text">
          {foreach from=$product.attributes item=attribute}
            {$attribute.group}: {$attribute.name}
          {/foreach}
        </p>
      {/if}
      <button class="qliro-upsell-button btn btn-primary" data-id-product="{$product.id_product}" data-id-product-attribute="{$product.id_product_attribute}">
        {l s='Add to order' d='Shop.Theme.Actions'}
      </button>
    </div>
  </div>
</article>
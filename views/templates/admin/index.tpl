<div class="panel b2b-main-panel">
    <h3>
        <i class="icon-cogs"></i>
        {l s='B2B Price Import' mod='b2bpriceimport'}
    </h3>

    <ul class="nav nav-tabs" id="b2b-priceimport-menu">
        {foreach from=$menuItems item=menuItem}
            <li class="{if $activeSection == $menuItem.key || ($activeSection == 'import_detail' && $menuItem.key == 'import')}active{/if}">
                <a href="{$menuItem.url|escape:'html':'UTF-8'}">
                    <i class="{$menuItem.icon|escape:'html':'UTF-8'}"></i>
                    {$menuItem.label|escape:'html':'UTF-8'}
                </a>
            </li>
        {/foreach}
    </ul>

    <div class="b2b-section-content" style="margin-top: 20px;">
        {if $activeSection == 'discount_matrix'}
            {include file="./tabs/discount_matrix.tpl"}
        {elseif $activeSection == 'config'}
            {include file="./tabs/config_panel.tpl"}
        {elseif $activeSection == 'import'}
            {include file="./tabs/import.tpl"}
        {elseif $activeSection == 'import_detail'}
            {include file="./tabs/import_detail.tpl"}
        {elseif $activeSection == 'logs'}
            {include file="./tabs/logs.tpl"}
        {else}
            <div class="alert alert-warning">
                {l s='Unknown section.' mod='b2bpriceimport'}
            </div>
        {/if}
    </div>
</div>
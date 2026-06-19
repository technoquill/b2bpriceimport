<div class="b2b-accordion-panel b2b-category-level-{$category.level|intval}">
    <div
        class="b2b-accordion-heading"
        data-toggle="collapse"
        data-target="#b2b-category-{$category.id_category|intval}"
    >

        <i class="icon-folder-close b2b-toggle-icon"></i>
        <span class="b2b-category-title">
        {$category.name|escape:'html':'UTF-8'}
    </span>

        <span class="label label-danger b2b-missing-label" style="display: none;">

        {l s='Not filled' mod='b2bpriceimport'}

    </span>

    </div>

    <div id="b2b-category-{$category.id_category|intval}" class="collapse{if $category.level == 0} in{/if}">
        <div class="b2b-accordion-body">

            {if !empty($category.brands)}
                <table class="table b2b-brand-table">
                    <thead>
                    <tr>
                        <th style="width: 240px;">
                            {l s='Brand' mod='b2bpriceimport'}
                        </th>

                        {foreach from=$groups item=group}
                            <th class="text-center">
                                {$group.name|escape:'html':'UTF-8'}
                            </th>
                        {/foreach}
                    </tr>
                    </thead>

                    <tbody>
                    {foreach from=$category.brands item=brand}
                        <tr>
                            <td>
                                <strong>{$brand.name|escape:'html':'UTF-8'}</strong>
                            </td>

                            {foreach from=$groups item=group}
                                {assign var=idGroup value=$group.id_group}
                                {assign var=value value=''}

                                {if isset($brand.discounts[$idGroup])}
                                    {assign var=value value=$brand.discounts[$idGroup]}
                                {/if}

                                <td>
                                    <div class="input-group">
                                        <input
                                                type="text"
                                                class="form-control text-right b2b-discount-input"
                                                data-id-category="{$category.id_category|intval}"
                                                data-id-manufacturer="{$brand.id_manufacturer|intval}"
                                                data-id-group="{$group.id_group|intval}"
                                                data-original-value="{$value|escape:'html':'UTF-8'}"
                                                value="{$value|escape:'html':'UTF-8'}"
                                                placeholder="0.00"
                                        />
                                        <span class="input-group-addon">%</span>
                                    </div>
                                    <span class="b2b-save-status"></span>
                                </td>
                            {/foreach}
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            {/if}

            {if !empty($category.children)}
                {foreach from=$category.children item=childCategory}
                    {include file="./discount_matrix_category.tpl" category=$childCategory groups=$groups}
                {/foreach}
            {/if}

            {if empty($category.brands) && empty($category.children)}
                <div class="alert alert-info b2b-empty-category">
                    {l s='No brands in this category.' mod='b2bpriceimport'}
                </div>
            {/if}

        </div>
    </div>
</div>
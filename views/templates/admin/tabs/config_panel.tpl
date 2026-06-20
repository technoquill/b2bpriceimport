<div class="panel b2b-config-panel">
    <h3>
        <i class="icon-cogs"></i>
        {l s='Module configuration' mod='b2bpriceimport'}
    </h3>

    <style>
        .b2b-config-row {
            margin-bottom: 18px;
        }

        .b2b-config-status {
            display: block;
            min-height: 16px;
            margin-top: 5px;
            font-size: 11px;
        }

        .b2b-checkbox-list {
            padding: 10px 12px;
            border: 1px solid #d3d8db;
            background: #fff;
           /*max-width: 720px;*/
        }

        .b2b-checkbox-item {
            display: block;
            padding: 4px 12px;
            margin-bottom: 8px;
            font-weight: 400;
            cursor: pointer;
        }

        .b2b-checkbox-item:last-child {
            margin-bottom: 0;
        }

        .b2b-checkbox-item input {
            margin-right: 7px;
        }
    </style>

    {if empty($configDefinitions)}
        <div class="alert alert-info">
            {l s='No configuration options available.' mod='b2bpriceimport'}
        </div>
    {else}
        {foreach from=$configDefinitions item=config}
            <div class="form-group b2b-config-row">
                <label class="control-label col-lg-3">
                    {$config.label|escape:'html':'UTF-8'}
                </label>

                <div class="col-lg-6">
                    {if $config.type == 'group_multiselect'}
                        <div
                                class="b2b-checkbox-list b2b-config-checkbox-list"
                                data-config-key="{$config.key|escape:'html':'UTF-8'}"
                                data-config-type="{$config.type|escape:'html':'UTF-8'}"
                        >
                            {foreach from=$allGroups item=group}
                                <label class="b2b-checkbox-item">
                                    <input
                                            type="checkbox"
                                            class="b2b-config-checkbox"
                                            value="{$group.id_group|intval}"
                                            {if in_array($group.id_group, $config.value)}checked="checked"{/if}
                                    />
                                    {$group.name|escape:'html':'UTF-8'}
                                </label>
                            {/foreach}
                        </div>
                    {elseif $config.type == 'text'}
                        <input
                                type="text"
                                class="form-control b2b-config-field"
                                data-config-key="{$config.key|escape:'html':'UTF-8'}"
                                data-config-type="{$config.type|escape:'html':'UTF-8'}"
                                value="{$config.value|escape:'html':'UTF-8'}"
                        />
                    {elseif $config.type == 'integer'}
                        <input
                                type="number"
                                class="form-control b2b-config-field"
                                data-config-key="{$config.key|escape:'html':'UTF-8'}"
                                data-config-type="{$config.type|escape:'html':'UTF-8'}"
                                value="{$config.value|intval}"
                                {if isset($config.min)}min="{$config.min|intval}"{/if}
                                {if isset($config.max)}max="{$config.max|intval}"{/if}
                        />
                    {elseif $config.type == 'select'}
                        <select
                                class="form-control b2b-config-field"
                                data-config-key="{$config.key|escape:'html':'UTF-8'}"
                                data-config-type="{$config.type|escape:'html':'UTF-8'}"
                        >
                            {foreach from=$config.options item=option}
                                <option
                                        value="{$option.value|escape:'html':'UTF-8'}"
                                        {if $option.value == $config.value}selected="selected"{/if}
                                >
                                    {$option.label|escape:'html':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    {else}
                        <div class="alert alert-warning">
                            {l s='Unsupported configuration field type:' mod='b2bpriceimport'}
                            {$config.type|escape:'html':'UTF-8'}
                        </div>
                    {/if}

                    {if isset($config.description) && $config.description}
                        <p class="help-block">
                            {$config.description|escape:'html':'UTF-8'}
                        </p>
                    {/if}

                    <span class="b2b-config-status"></span>
                </div>

                <div class="clearfix"></div>
            </div>
        {/foreach}
    {/if}
</div>

<script>
    var b2bPriceImportAjaxUrl = '{$ajaxUrl|escape:'javascript':'UTF-8'}';

    $(document).ready(function () {
        function getCheckedConfigValues($container) {
            var values = [];

            $container.find('.b2b-config-checkbox:checked').each(function () {
                values.push($(this).val());
            });

            return values;
        }

        function saveConfig($container, value) {
            var key = $container.data('config-key');
            var $status = $container.closest('.form-group').find('.b2b-config-status');

            $status
                .removeClass('text-success text-danger text-warning')
                .addClass('text-warning')
                .text('Saving...');

            $.ajax({
                url: b2bPriceImportAjaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: 1,
                    action: 'SaveConfig',
                    key: key,
                    value: value
                },
                success: function (response) {
                    if (response && response.success) {
                        $status
                            .removeClass('text-warning text-danger')
                            .addClass('text-success')
                            .text(response.message || 'Saved');
                    } else {
                        $status
                            .removeClass('text-warning text-success')
                            .addClass('text-danger')
                            .text(response && response.message ? response.message : 'Save error');
                    }
                },
                error: function () {
                    $status
                        .removeClass('text-warning text-success')
                        .addClass('text-danger')
                        .text('Server error');
                }
            });
        }

        $('.b2b-config-checkbox').on('change', function () {
            var $container = $(this).closest('.b2b-config-checkbox-list');
            saveConfig($container, getCheckedConfigValues($container));
        });

        $('.b2b-config-field').on('change', function () {
            var $field = $(this);
            saveConfig($field, $field.val());
        });
    });
</script>

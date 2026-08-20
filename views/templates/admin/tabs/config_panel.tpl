<div class="b2b-config-page">
    <style>
        .b2b-config-page-title {
            margin: 0 0 16px;
            font-size: 18px;
        }

        .b2b-config-group {
            margin-bottom: 15px;
        }

        .b2b-config-group-heading {
            padding: 0;
        }

        .b2b-config-group-toggle {
            display: block;
            width: 100%;
            padding: 13px 15px;
            border: 0;
            background: transparent;
            color: #363a41;
            text-align: left;
        }

        .b2b-config-group-toggle:hover,
        .b2b-config-group-toggle:focus {
            color: #25b9d7;
            text-decoration: none;
            outline: none;
        }

        .b2b-config-group-toggle .b2b-config-group-chevron {
            float: right;
            margin-top: 9px;
        }

        .b2b-config-group-toggle[aria-expanded="true"] .b2b-config-group-chevron:before {
            content: "\f077";
        }

        .b2b-config-group-title {
            display: block;
            font-size: 15px;
            font-weight: 600;
        }

        .b2b-config-group-description {
            display: block;
            margin: 3px 24px 0;
            color: #6c868e;
            font-size: 12px;
            font-weight: 400;
        }

        .b2b-config-group .panel-body {
            padding-top: 18px;
        }

        .b2b-config-row {
            margin-bottom: 18px;
        }

        .b2b-config-status {
            display: block;
            min-height: 16px;
            margin-top: 5px;
            font-size: 11px;
        }

        .b2b-secret-control .btn {
            white-space: nowrap;
        }

        .b2b-key-rotation-confirmation {
            margin: 8px 0 0;
            color: #a94442;
        }
    </style>

    <h2 class="b2b-config-page-title">
        <i class="icon-cogs"></i>
        {l s='Module configuration' mod='b2bpriceimport'}
    </h2>

    {if empty($configGroups)}
        <div class="alert alert-info">
            {l s='No configuration options available.' mod='b2bpriceimport'}
        </div>
    {else}
        {foreach from=$configGroups item=configGroup}
            <div class="panel panel-default b2b-config-group">
                <div class="panel-heading b2b-config-group-heading">
                    <button
                            type="button"
                            class="b2b-config-group-toggle{if !empty($configGroup.collapsed)} collapsed{/if}"
                            data-toggle="collapse"
                            data-target="#b2b-config-group-{$configGroup.key|escape:'html':'UTF-8'}"
                            aria-expanded="{if empty($configGroup.collapsed)}true{else}false{/if}"
                            aria-controls="b2b-config-group-{$configGroup.key|escape:'html':'UTF-8'}"
                    >
                        <span class="b2b-config-group-title">
                            <i class="{$configGroup.icon|escape:'html':'UTF-8'}"></i>
                            {$configGroup.label|escape:'html':'UTF-8'}
                            <i class="icon-chevron-down b2b-config-group-chevron"></i>
                        </span>
                        {if !empty($configGroup.description)}
                            <span class="b2b-config-group-description">
                                {$configGroup.description|escape:'html':'UTF-8'}
                            </span>
                        {/if}
                    </button>
                </div>

                <div
                        id="b2b-config-group-{$configGroup.key|escape:'html':'UTF-8'}"
                        class="panel-collapse collapse{if empty($configGroup.collapsed)} in{/if}"
                >
                    <div class="panel-body">
                        {foreach from=$configGroup.definitions item=config}
                            <div class="form-group b2b-config-row">
                                <label class="control-label col-lg-3">
                                    {$config.label|escape:'html':'UTF-8'}
                                </label>

                                <div class="col-lg-7">
                                    {if $config.type == 'text'}
                                        {if !empty($config.readonly)}
                                            <div class="input-group b2b-readonly-config-control">
                                                <input
                                                        type="text"
                                                        class="form-control b2b-readonly-config-field"
                                                        value="{$config.value|escape:'html':'UTF-8'}"
                                                        readonly="readonly"
                                                        spellcheck="false"
                                                />
                                                {if !empty($config.copyable)}
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-default b2b-copy-config-value">
                                                            <i class="icon-copy"></i>
                                                            {l s='Copy' mod='b2bpriceimport'}
                                                        </button>
                                                    </span>
                                                {/if}
                                            </div>
                                        {else}
                                            <input
                                                    type="text"
                                                    class="form-control b2b-config-field"
                                                    data-config-key="{$config.key|escape:'html':'UTF-8'}"
                                                    data-config-type="{$config.type|escape:'html':'UTF-8'}"
                                                    value="{$config.value|escape:'html':'UTF-8'}"
                                            />
                                        {/if}
                                    {elseif $config.type == 'secret'}
                                        <div class="input-group b2b-secret-control">
                                            <input
                                                    type="password"
                                                    class="form-control b2b-config-field b2b-secret-field"
                                                    data-config-key="{$config.key|escape:'html':'UTF-8'}"
                                                    data-config-type="{$config.type|escape:'html':'UTF-8'}"
                                                    value="{$config.value|escape:'html':'UTF-8'}"
                                                    autocomplete="new-password"
                                                    spellcheck="false"
                                            />
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default b2b-toggle-secret" title="{l s='Show or hide key' mod='b2bpriceimport'}">
                                                    <i class="icon-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-default b2b-copy-secret" title="{l s='Copy key' mod='b2bpriceimport'}">
                                                    <i class="icon-copy"></i>
                                                </button>
                                                <button
                                                        type="button"
                                                        class="btn btn-primary b2b-generate-import-key"
                                                        {if !empty($config.value)}disabled="disabled"{/if}
                                                >
                                                    <i class="icon-refresh"></i>
                                                    {l s='Generate key' mod='b2bpriceimport'}
                                                </button>
                                            </span>
                                        </div>
                                        <div
                                                class="checkbox b2b-key-rotation-confirmation"
                                                {if empty($config.value)}style="display: none;"{/if}
                                        >
                                            <label>
                                                <input type="checkbox" class="b2b-confirm-key-rotation" />
                                                {l s='I understand that generating a new key will stop the 1C exchange until the key is updated in 1C.' mod='b2bpriceimport'}
                                            </label>
                                        </div>
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

                        {if !empty($configGroup.show_import_trigger_url) && !empty($importTriggerBaseUrl)}
                            <div class="form-group b2b-config-row">
                                <label class="control-label col-lg-3">
                                    {l s='Import trigger URL' mod='b2bpriceimport'}
                                </label>

                                <div class="col-lg-7">
                                    <div class="input-group b2b-import-trigger-url-control">
                                        <input
                                                type="text"
                                                class="form-control b2b-import-trigger-url-field"
                                                value="{$importTriggerUrl|escape:'html':'UTF-8'}"
                                                data-base-url="{$importTriggerBaseUrl|escape:'html':'UTF-8'}"
                                                readonly="readonly"
                                                spellcheck="false"
                                        />
                                        <span class="input-group-btn">
                                            <button
                                                    type="button"
                                                    class="btn btn-default b2b-copy-import-trigger-url"
                                                    {if empty($importTriggerHasKey)}disabled="disabled"{/if}
                                            >
                                                <i class="icon-copy"></i>
                                                {l s='Copy URL' mod='b2bpriceimport'}
                                            </button>
                                        </span>
                                    </div>
                                    <p class="help-block">
                                        {l s='A GET or POST request to this URL scans the import directory and starts processing. The URL contains the secret key; keep it private.' mod='b2bpriceimport'}
                                    </p>
                                    <span class="b2b-config-status"></span>
                                </div>

                                <div class="clearfix"></div>
                            </div>
                        {/if}

                        {if !empty($configGroup.show_cli_command) && !empty($importCliCommand)}
                            <div class="form-group b2b-config-row">
                                <label class="control-label col-lg-3">
                                    {l s='Terminal import command' mod='b2bpriceimport'}
                                </label>

                                <div class="col-lg-7">
                                    <div class="input-group b2b-cli-command-control">
                                        <input
                                                type="text"
                                                class="form-control b2b-cli-command-field"
                                                value="{$importCliCommand|escape:'html':'UTF-8'}"
                                                readonly="readonly"
                                                spellcheck="false"
                                        />
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default b2b-copy-cli-command">
                                                <i class="icon-copy"></i>
                                                {l s='Copy command' mod='b2bpriceimport'}
                                            </button>
                                        </span>
                                    </div>
                                    <p class="help-block">
                                        {l s='The command reads all import parameters from the module settings above.' mod='b2bpriceimport'}
                                    </p>
                                    <span class="b2b-config-status"></span>
                                </div>

                                <div class="clearfix"></div>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        {/foreach}
    {/if}
</div>

<script>
    var b2bPriceImportAjaxUrl = '{$ajaxUrl|escape:'javascript':'UTF-8'}';

    $(document).ready(function () {
        function updateImportTriggerUrl(accessKey) {
            var $field = $('.b2b-import-trigger-url-field');

            if (!$field.length) {
                return;
            }

            var baseUrl = String($field.data('base-url') || '');
            var hasKey = Boolean(accessKey);
            var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';

            $field.val(hasKey ? baseUrl + separator + 'key=' + encodeURIComponent(accessKey) : baseUrl);
            $('.b2b-copy-import-trigger-url').prop('disabled', !hasKey);
        }

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
                        if ($container.hasClass('b2b-secret-field')) {
                            updateImportTriggerUrl(response.value || value);
                        }

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

        function setConfigStatus($container, type, message) {
            var $status = $container.closest('.form-group').find('.b2b-config-status');

            $status
                .removeClass('text-success text-danger text-warning')
                .addClass(type)
                .text(message);
        }

        function copyText(value, onSuccess, onError) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(onSuccess).catch(onError);
                return;
            }

            var $temporary = $('<textarea>')
                .css({ position: 'fixed', opacity: 0 })
                .val(value)
                .appendTo('body');

            $temporary[0].select();

            try {
                document.execCommand('copy') ? onSuccess() : onError();
            } catch (error) {
                onError();
            }

            $temporary.remove();
        }

        $('.b2b-config-checkbox').on('change', function () {
            var $container = $(this).closest('.b2b-config-checkbox-list');
            saveConfig($container, getCheckedConfigValues($container));
        });

        $('.b2b-config-field').on('change', function () {
            var $field = $(this);
            saveConfig($field, $field.val());
        });

        $('.b2b-secret-field').on('change', function () {
            var $field = $(this);
            var $control = $field.closest('.b2b-secret-control');
            var $confirmation = $control.siblings('.b2b-key-rotation-confirmation');
            var hasKey = Boolean($field.val());

            $confirmation.toggle(hasKey);
            $confirmation.find('.b2b-confirm-key-rotation').prop('checked', false);
            $control.find('.b2b-generate-import-key').prop('disabled', hasKey);
        });

        $('.b2b-toggle-secret').on('click', function () {
            var $field = $(this).closest('.b2b-secret-control').find('.b2b-secret-field');
            var showKey = $field.attr('type') === 'password';

            $field.attr('type', showKey ? 'text' : 'password');
            $(this).find('i').toggleClass('icon-eye', !showKey).toggleClass('icon-eye-close', showKey);
        });

        $('.b2b-copy-secret').on('click', function () {
            var $control = $(this).closest('.b2b-secret-control');
            var value = $control.find('.b2b-secret-field').val();

            if (!value) {
                setConfigStatus($control, 'text-danger', 'There is no key to copy.');
                return;
            }

            copyText(
                value,
                function () { setConfigStatus($control, 'text-success', 'Key copied.'); },
                function () { setConfigStatus($control, 'text-danger', 'Cannot copy the key.'); }
            );
        });

        $('.b2b-copy-cli-command').on('click', function () {
            var $control = $(this).closest('.b2b-cli-command-control');
            var command = $control.find('.b2b-cli-command-field').val();

            copyText(
                command,
                function () { setConfigStatus($control, 'text-success', 'Command copied.'); },
                function () { setConfigStatus($control, 'text-danger', 'Cannot copy the command.'); }
            );
        });

        $('.b2b-copy-import-trigger-url').on('click', function () {
            var $control = $(this).closest('.b2b-import-trigger-url-control');
            var url = $control.find('.b2b-import-trigger-url-field').val();

            copyText(
                url,
                function () { setConfigStatus($control, 'text-success', 'Import URL copied.'); },
                function () { setConfigStatus($control, 'text-danger', 'Cannot copy the import URL.'); }
            );
        });

        $('.b2b-copy-config-value').on('click', function () {
            var $control = $(this).closest('.b2b-readonly-config-control');
            var value = $control.find('.b2b-readonly-config-field').val();

            copyText(
                value,
                function () { setConfigStatus($control, 'text-success', 'Value copied.'); },
                function () { setConfigStatus($control, 'text-danger', 'Cannot copy the value.'); }
            );
        });

        $('.b2b-confirm-key-rotation').on('change', function () {
            var $confirmation = $(this).closest('.b2b-key-rotation-confirmation');
            var $control = $confirmation.siblings('.b2b-secret-control');

            $control.find('.b2b-generate-import-key').prop('disabled', !$(this).prop('checked'));
        });

        $('.b2b-generate-import-key').on('click', function () {
            var $button = $(this);
            var $control = $button.closest('.b2b-secret-control');
            var $field = $control.find('.b2b-secret-field');
            var $confirmation = $control.siblings('.b2b-key-rotation-confirmation');
            var $confirmationCheckbox = $confirmation.find('.b2b-confirm-key-rotation');

            if ($field.val() && !$confirmationCheckbox.prop('checked')) {
                setConfigStatus(
                    $control,
                    'text-danger',
                    'Confirm that the 1C exchange will stop before generating a new key.'
                );
                return;
            }

            $button.prop('disabled', true);
            setConfigStatus($control, 'text-warning', 'Generating...');

            $.ajax({
                url: b2bPriceImportAjaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: 1,
                    action: 'GenerateImportApiKey'
                },
                success: function (response) {
                    if (response && response.success && response.value) {
                        $field.val(response.value);
                        updateImportTriggerUrl(response.value);
                        $confirmation.show();
                        $confirmationCheckbox.prop('checked', false);
                        setConfigStatus($control, 'text-success', response.message || 'Key generated.');
                    } else {
                        setConfigStatus(
                            $control,
                            'text-danger',
                            response && response.message ? response.message : 'Cannot generate the key.'
                        );
                    }
                },
                error: function () {
                    setConfigStatus($control, 'text-danger', 'Server error.');
                },
                complete: function () {
                    $button.prop(
                        'disabled',
                        Boolean($field.val()) && !$confirmationCheckbox.prop('checked')
                    );
                }
            });
        });
    });
</script>

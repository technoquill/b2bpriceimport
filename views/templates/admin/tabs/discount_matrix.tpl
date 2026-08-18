<div class="panel">
    <h3>
        <i class="icon-percent"></i>
        {l s='B2B Discount Matrix' mod='b2bpriceimport'}
    </h3>

    <style>
        .b2b-accordion-panel {
            border: 1px solid #d3d8db;
            margin-bottom: 8px;
            background: #fff;
        }

        .b2b-accordion-heading {
            padding: 10px 14px;
            cursor: pointer;
            background: #f8f8f8;
            border-bottom: 1px solid #d3d8db;
            font-weight: 600;
        }

        .b2b-accordion-heading:hover {
            background: #f0f0f0;
        }

        .b2b-accordion-body {
            padding: 12px 14px;
        }

        .b2b-category-level-0 > .b2b-accordion-heading {
            font-size: 16px;
            background: #f3f3f3;
        }

        .b2b-category-level-1 {
            margin-left: 18px;
        }

        .b2b-category-level-2 {
            margin-left: 36px;
        }

        .b2b-category-level-3 {
            margin-left: 54px;
        }

        .b2b-category-level-4 {
            margin-left: 72px;
        }

        .b2b-brand-table {
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .b2b-toggle-icon {
            margin-right: 7px;
        }

        .b2b-empty-category {
            margin-bottom: 10px;
        }
        .b2b-save-status {
            display: block;
            min-height: 16px;
            margin-top: 3px;
            font-size: 11px;
        }

        .b2b-discount-input.b2b-saving {
            background-color: #fff8e1;
        }

        .b2b-missing-label {
            margin-left: 8px;
            font-size: 11px;
            vertical-align: middle;
        }

        .b2b-accordion-panel.b2b-has-missing > .b2b-accordion-heading {
            border-left: 4px solid #d9534f;
        }

        .b2b-accordion-panel.b2b-has-missing > .b2b-accordion-heading .b2b-category-title {
            color: #a94442;
        }

        .b2b-matrix-config-panel {
            margin-bottom: 18px;
        }

        .b2b-matrix-config-panel .panel-heading {
            font-weight: 600;
        }

        .b2b-matrix-group-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 20px;
            padding: 10px 12px;
            border: 1px solid #d3d8db;
            background: #fff;
        }

        .b2b-matrix-group-item {
            min-width: 150px;
            margin: 0;
            font-weight: 400;
            cursor: pointer;
        }

        .b2b-matrix-group-item input {
            margin-right: 7px;
        }

        .b2b-matrix-config-status {
            display: block;
            min-height: 16px;
            margin-top: 5px;
            font-size: 11px;
        }
    </style>

    {foreach from=$discountMatrixConfigGroups item=configGroup}
        <div class="panel panel-default b2b-matrix-config-panel">
            <div class="panel-heading">
                <i class="{$configGroup.icon|escape:'html':'UTF-8'}"></i>
                {$configGroup.label|escape:'html':'UTF-8'}
            </div>
            <div class="panel-body">
                {if !empty($configGroup.description)}
                    <p>{$configGroup.description|escape:'html':'UTF-8'}</p>
                {/if}

                {foreach from=$configGroup.definitions item=config}
                    {if $config.type == 'group_multiselect'}
                        <div class="form-group">
                            <label class="control-label">
                                {$config.label|escape:'html':'UTF-8'}
                            </label>
                            <div
                                    class="b2b-matrix-group-list"
                                    data-config-key="{$config.key|escape:'html':'UTF-8'}"
                            >
                                {foreach from=$allGroups item=group}
                                    <label class="b2b-matrix-group-item">
                                        <input
                                                type="checkbox"
                                                class="b2b-matrix-group-checkbox"
                                                value="{$group.id_group|intval}"
                                                {if in_array($group.id_group, $config.value)}checked="checked"{/if}
                                        />
                                        {$group.name|escape:'html':'UTF-8'}
                                    </label>
                                {/foreach}
                            </div>
                            {if !empty($config.description)}
                                <p class="help-block">
                                    {$config.description|escape:'html':'UTF-8'}
                                </p>
                            {/if}
                            <span class="b2b-matrix-config-status"></span>
                        </div>
                    {/if}
                {/foreach}
            </div>
        </div>
    {/foreach}

    <form method="post" action="{$currentIndex|escape:'html':'UTF-8'}&token={$token|escape:'html':'UTF-8'}">
        <div class="table-responsive">

            {if empty($matrix)}
                <div class="alert alert-warning">
                    {l s='No categories with brands found.' mod='b2bpriceimport'}
                </div>
            {else}
                {foreach from=$matrix item=category}
                    {include file="./discount_matrix_category.tpl" category=$category groups=$groups}
                {/foreach}
            {/if}

        </div>

        <div class="panel-footer">
            <div class="clearfix"></div>
        </div>
    </form>



    <script>
    var b2bDiscountMatrixAjaxUrl = '{$ajaxUrl|escape:'javascript':'UTF-8'}';

    $(document).ready(function () {
        var saveTimers = {};
        var groupConfigTimer = null;

        function getExcludedGroupValues($container) {
            var values = [];

            $container.find('.b2b-matrix-group-checkbox:checked').each(function () {
                values.push($(this).val());
            });

            return values;
        }

        function saveExcludedGroups($container) {
            var $status = $container.closest('.form-group').find('.b2b-matrix-config-status');

            $status
                .removeClass('text-success text-danger text-warning')
                .addClass('text-warning')
                .text('Saving...');

            $container.find('.b2b-matrix-group-checkbox').prop('disabled', true);

            $.ajax({
                url: b2bDiscountMatrixAjaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: 1,
                    action: 'SaveConfig',
                    key: $container.data('config-key'),
                    value: getExcludedGroupValues($container)
                },
                success: function (response) {
                    if (response && response.success) {
                        $status
                            .removeClass('text-warning text-danger')
                            .addClass('text-success')
                            .text('Saved. Refreshing the matrix...');

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 350);
                    } else {
                        $container.find('.b2b-matrix-group-checkbox').prop('disabled', false);
                        $status
                            .removeClass('text-warning text-success')
                            .addClass('text-danger')
                            .text(response && response.message ? response.message : 'Save error');
                    }
                },
                error: function () {
                    $container.find('.b2b-matrix-group-checkbox').prop('disabled', false);
                    $status
                        .removeClass('text-warning text-success')
                        .addClass('text-danger')
                        .text('Server error');
                }
            });
        }

        function normalizeValue(value) {
            value = $.trim(String(value || ''));
            value = value.replace(',', '.');

            if (value === '') {
                return '';
            }

            var number = parseFloat(value);

            if (isNaN(number)) {
                return value;
            }

            return number.toFixed(2);
        }

        function getInputKey($input) {
            return [
                $input.data('id-category'),
                $input.data('id-manufacturer'),
                $input.data('id-group')
            ].join('-');
        }

        function hasRealChange($input) {
            var currentValue = normalizeValue($input.val());
            var originalValue = normalizeValue($input.data('original-value'));

            return currentValue !== originalValue;
        }

        function setStatus($input, message, type) {
            var $status = $input.closest('td').find('.b2b-save-status');

            $status
                .removeClass('text-success text-danger text-warning text-muted')
                .addClass(type)
                .text(message);
        }

        function clearStatus($input) {
            var $status = $input.closest('td').find('.b2b-save-status');

            $status
                .removeClass('text-success text-danger text-warning text-muted')
                .text('');
        }

        function updateMissingLabels() {
            $('.b2b-accordion-panel').each(function () {
                var $panel = $(this);
                var hasMissing = false;

                $panel.find('.b2b-discount-input').each(function () {
                    var value = $.trim($(this).val());

                    if (value === '') {
                        hasMissing = true;
                        return false;
                    }
                });

                if (hasMissing) {
                    $panel.addClass('b2b-has-missing');
                    $panel.children('.b2b-accordion-heading').find('.b2b-missing-label').show();
                } else {
                    $panel.removeClass('b2b-has-missing');
                    $panel.children('.b2b-accordion-heading').find('.b2b-missing-label').hide();
                }
            });
        }

        function saveDiscount($input) {
            if (!hasRealChange($input)) {
                clearStatus($input);
                updateMissingLabels();
                return;
            }

            var idCategory = $input.data('id-category');
            var idManufacturer = $input.data('id-manufacturer');
            var idGroup = $input.data('id-group');
            var value = $.trim($input.val());

            setStatus($input, 'Saving...', 'text-warning');

            $.ajax({
                url: b2bDiscountMatrixAjaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax: 1,
                    action: 'SaveDiscountRule',
                    id_category: idCategory,
                    id_manufacturer: idManufacturer,
                    id_group: idGroup,
                    discount_percent: value
                },
                success: function (response) {
                    if (response && response.success) {
                        var savedValue = '';

                        if (typeof response.value !== 'undefined') {
                            savedValue = response.value;
                            $input.val(savedValue);
                        } else {
                            savedValue = $input.val();
                        }

                        $input.data('original-value', savedValue);
                        $input.attr('data-original-value', savedValue);

                        updateMissingLabels();

                        setStatus($input, response.message || 'Saved', 'text-success');

                        setTimeout(function () {
                            if (!hasRealChange($input)) {
                                clearStatus($input);
                            }
                        }, 1500);
                    } else {
                        setStatus(
                            $input,
                            response && response.message ? response.message : 'Save error',
                            'text-danger'
                        );

                        updateMissingLabels();
                    }
                },
                error: function () {
                    setStatus($input, 'Server error', 'text-danger');
                    updateMissingLabels();
                }
            });
        }

        $('.b2b-discount-input').each(function () {
            var $input = $(this);
            var normalized = normalizeValue($input.val());

            if (normalized !== '') {
                $input.val(normalized);
            }

            $input.data('original-value', normalized);
            $input.attr('data-original-value', normalized);
        });

        updateMissingLabels();

        $('.b2b-matrix-group-checkbox').on('change', function () {
            var $container = $(this).closest('.b2b-matrix-group-list');
            var $status = $container.closest('.form-group').find('.b2b-matrix-config-status');

            window.clearTimeout(groupConfigTimer);
            $status
                .removeClass('text-success text-danger')
                .addClass('text-warning')
                .text('Changed');

            groupConfigTimer = window.setTimeout(function () {
                saveExcludedGroups($container);
            }, 500);
        });

        $('.b2b-discount-input').on('input', function () {
            var $input = $(this);
            var key = getInputKey($input);

            clearTimeout(saveTimers[key]);

            updateMissingLabels();

            if (!hasRealChange($input)) {
                clearStatus($input);
                return;
            }

            setStatus($input, 'Changed', 'text-warning');

            saveTimers[key] = setTimeout(function () {
                saveDiscount($input);
            }, 700);
        });

        $('.b2b-discount-input').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();

                var $input = $(this);
                var key = getInputKey($input);

                clearTimeout(saveTimers[key]);

                saveDiscount($input);
            }
        });

        $('.b2b-discount-input').on('blur', function () {
            var $input = $(this);
            var key = getInputKey($input);

            clearTimeout(saveTimers[key]);

            if (!hasRealChange($input)) {
                clearStatus($input);
                updateMissingLabels();
                return;
            }

            saveDiscount($input);
        });
    });
</script>



</div>

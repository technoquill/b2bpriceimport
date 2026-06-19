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
    </style>

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
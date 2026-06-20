<div class="panel">
    <h3>
        <i class="icon-upload"></i>
        {l s='Import' mod='b2bpriceimport'}
    </h3>

    <div class="alert alert-info">
        <p>{l s='Required CSV columns: reference, price, currency, currency_rate, active.' mod='b2bpriceimport'}</p>
        <p><strong>{l s='Example:' mod='b2bpriceimport'}</strong> <code>ER45398;68.15;EUR;52.15;1</code></p>
        <p>{l s='Validation: reference is required, price must be numeric and not negative, currency must be a 3-letter code, currency_rate must be greater than zero, active must be 0 or 1.' mod='b2bpriceimport'}</p>
    </div>

    <form id="b2b-import-form" class="form-horizontal" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='CSV file' mod='b2bpriceimport'}
            </label>
            <div class="col-lg-6">
                <input type="file" name="import_file" accept=".csv,text/csv" required>
                <p class="help-block">
                    {l s='Delimiter is detected automatically: semicolon or comma.' mod='b2bpriceimport'}
                </p>
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-primary">
                <i class="process-icon-upload"></i>
                {l s='Upload import' mod='b2bpriceimport'}
            </button>
        </div>
    </form>
</div>

<div class="panel">
    <h3>
        <i class="icon-list"></i>
        {l s='Recent imports' mod='b2bpriceimport'}
    </h3>

    <div id="b2b-import-message"></div>

    {if empty($imports)}
        <div class="alert alert-warning">
            {l s='No imports yet.' mod='b2bpriceimport'}
        </div>
    {else}
        <table class="table">
            <thead>
            <tr>
                <th>{l s='ID' mod='b2bpriceimport'}</th>
                <th>{l s='File' mod='b2bpriceimport'}</th>
                <th>{l s='Status' mod='b2bpriceimport'}</th>
                <th>{l s='Rows' mod='b2bpriceimport'}</th>
                <th>{l s='Success' mod='b2bpriceimport'}</th>
                <th>{l s='Failed' mod='b2bpriceimport'}</th>
                <th>{l s='Created' mod='b2bpriceimport'}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$imports item=import}
                <tr>
                    <td>{$import.id_b2b_import|intval}</td>
                    <td>{$import.original_filename|escape:'html':'UTF-8'}</td>
                    <td>{$import.status|escape:'html':'UTF-8'}</td>
                    <td>{$import.total_rows|intval}</td>
                    <td>{$import.success_rows|intval}</td>
                    <td>{$import.failed_rows|intval}</td>
                    <td>{$import.date_add|escape:'html':'UTF-8'}</td>
                    <td class="text-right">
                        <button type="button"
                                class="btn btn-default b2b-run-import"
                                data-id-import="{$import.id_b2b_import|intval}">
                            <i class="icon-play"></i>
                            {l s='Run' mod='b2bpriceimport'}
                        </button>
                        <button type="button"
                                class="btn btn-danger b2b-delete-import"
                                data-id-import="{$import.id_b2b_import|intval}">
                            <i class="icon-trash"></i>
                            {l s='Delete' mod='b2bpriceimport'}
                        </button>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {/if}
</div>

<script>
    (function () {
        var ajaxUrl = '{$ajaxUrl|escape:'javascript':'UTF-8'}';
        var messageBox = document.getElementById('b2b-import-message');

        function showMessage(success, message) {
            messageBox.innerHTML = '<div class="alert alert-' + (success ? 'success' : 'danger') + '">' + message + '</div>';
        }

        function handleJsonResponse(response) {
            return response.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    throw new Error(text.substring(0, 1000));
                }
            });
        }

        document.getElementById('b2b-import-form').addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(this);

            fetch(ajaxUrl + '&ajax=1&action=CreateImport', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(handleJsonResponse)
                .then(function (json) {
                    showMessage(json.success, json.message);
                    if (json.success) {
                        window.location.reload();
                    }
                })
                .catch(function (error) {
                    showMessage(false, error.message);
                });
        });

        Array.prototype.forEach.call(document.querySelectorAll('.b2b-run-import'), function (button) {
            button.addEventListener('click', function () {
                var idImport = this.getAttribute('data-id-import');
                var formData = new FormData();
                formData.append('id_import', idImport);

                this.disabled = true;

                fetch(ajaxUrl + '&ajax=1&action=RunImport', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                    .then(handleJsonResponse)
                    .then(function (json) {
                        showMessage(json.success, json.message);
                        window.location.reload();
                    })
                    .catch(function (error) {
                        showMessage(false, error.message);
                    });
            });
        });

        Array.prototype.forEach.call(document.querySelectorAll('.b2b-delete-import'), function (button) {
            button.addEventListener('click', function () {
                var idImport = this.getAttribute('data-id-import');

                if (!confirm('{l s='Delete this import, its stored CSV file, jobs and import rows?' mod='b2bpriceimport' js=1}')) {
                    return;
                }

                var formData = new FormData();
                formData.append('id_import', idImport);

                this.disabled = true;

                fetch(ajaxUrl + '&ajax=1&action=DeleteImport', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                    .then(handleJsonResponse)
                    .then(function (json) {
                        showMessage(json.success, json.message);
                        window.location.reload();
                    })
                    .catch(function (error) {
                        showMessage(false, error.message);
                    });
            });
        });
    })();
</script>

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
                {l s='File source' mod='b2bpriceimport'}
            </label>
            <div class="col-lg-6">
                <div class="radio">
                    <label>
                        <input type="radio" name="import_source" value="upload" checked>
                        {l s='Upload a new CSV file' mod='b2bpriceimport'}
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio"
                               name="import_source"
                               value="existing"
                               {if empty($existingImportFiles)}disabled{/if}>
                        {l s='Choose an existing CSV file' mod='b2bpriceimport'}
                    </label>
                </div>
            </div>
        </div>

        <div id="b2b-upload-file-group" class="form-group">
            <label class="control-label col-lg-3">
                {l s='New CSV file' mod='b2bpriceimport'}
            </label>
            <div class="col-lg-6">
                <input id="b2b-upload-file" type="file" name="import_file" accept=".csv,text/csv" required>
                <p class="help-block">
                    {l s='Delimiter is detected automatically: semicolon or comma.' mod='b2bpriceimport'}
                </p>
            </div>
        </div>

        <div id="b2b-existing-file-group" class="form-group hidden">
            <label class="control-label col-lg-3">
                {l s='Existing CSV file' mod='b2bpriceimport'}
            </label>
            <div class="col-lg-6">
                <select id="b2b-existing-import" name="stored_filename" class="form-control" disabled>
                    <option value="">{l s='Select a file' mod='b2bpriceimport'}</option>
                    {foreach from=$existingImportFiles item=existingFile}
                        <option value="{$existingFile.stored_filename|escape:'html':'UTF-8'}">
                            {$existingFile.display_filename|escape:'html':'UTF-8'}
                            {if !empty($existingFile.id_b2b_import)}
                                (#{$existingFile.id_b2b_import|intval}, {$existingFile.status|escape:'html':'UTF-8'}, {$existingFile.date_add|escape:'html':'UTF-8'})
                            {else}
                                ({l s='not imported yet' mod='b2bpriceimport'})
                            {/if}
                        </option>
                    {/foreach}
                </select>
                <p class="help-block">
                    {if empty($existingImportFiles)}
                        {l s='No stored CSV files are available.' mod='b2bpriceimport'}
                    {else}
                        {l s='The selected stored CSV file will be registered if necessary and then processed.' mod='b2bpriceimport'}
                    {/if}
                </p>
            </div>
        </div>

        <div class="panel-footer">
            <button id="b2b-import-submit" type="submit" class="btn btn-primary">
                <i class="process-icon-upload"></i>
                <span id="b2b-import-submit-label">{l s='Upload import' mod='b2bpriceimport'}</span>
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
                    <td>
                        <a href="{$ajaxUrl|escape:'html':'UTF-8'}&section=import_detail&id_import={$import.id_b2b_import|intval}">
                            {$import.original_filename|escape:'html':'UTF-8'}
                        </a>
                    </td>
                    <td>{$import.status|escape:'html':'UTF-8'}</td>
                    <td>{$import.total_rows|intval}</td>
                    <td>{$import.success_rows|intval}</td>
                    <td>{$import.failed_rows|intval}</td>
                    <td>{$import.date_add|escape:'html':'UTF-8'}</td>
                    <td class="text-right">
                        <a class="btn btn-default" href="{$ajaxUrl|escape:'html':'UTF-8'}&section=import_detail&id_import={$import.id_b2b_import|intval}">
                            <i class="icon-search"></i>
                            {l s='Details' mod='b2bpriceimport'}
                        </a>
                        <button type="button"
                                class="btn btn-default b2b-run-import"
                                data-id-import="{$import.id_b2b_import|intval}">
                            <i class="icon-play"></i>
                            {l s='Run' mod='b2bpriceimport'}
                        </button>
                        <button type="button"
                                class="btn btn-danger b2b-delete-import"
                                data-id-import="{$import.id_b2b_import|intval}"
                                data-import-file="{$import.original_filename|escape:'html':'UTF-8'}">
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

<div id="b2b-delete-import-modal"
     class="modal fade"
     tabindex="-1"
     role="dialog"
     aria-labelledby="b2b-delete-import-modal-title"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='b2bpriceimport'}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 id="b2b-delete-import-modal-title" class="modal-title">
                    {l s='Delete import' mod='b2bpriceimport'}
                </h4>
            </div>
            <div class="modal-body">
                <p>
                    {l s='Are you sure you want to delete this import?' mod='b2bpriceimport'}
                    <strong id="b2b-delete-import-file"></strong>
                </p>
                <div class="checkbox">
                    <label>
                        <input id="b2b-delete-import-file-checkbox" type="checkbox" value="1">
                        {l s='Also delete the stored import file' mod='b2bpriceimport'}
                    </label>
                </div>
                <p class="help-block">
                    {l s='If unchecked, the import data will be deleted but the CSV file will remain on the server.' mod='b2bpriceimport'}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {l s='Cancel' mod='b2bpriceimport'}
                </button>
                <button id="b2b-confirm-delete-import" type="button" class="btn btn-danger">
                    <i class="icon-trash"></i>
                    {l s='Delete import' mod='b2bpriceimport'}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var ajaxUrl = '{$ajaxUrl|escape:'javascript':'UTF-8'}';
        var messageBox = document.getElementById('b2b-import-message');
        var importForm = document.getElementById('b2b-import-form');
        var uploadFileGroup = document.getElementById('b2b-upload-file-group');
        var existingFileGroup = document.getElementById('b2b-existing-file-group');
        var uploadFile = document.getElementById('b2b-upload-file');
        var existingImport = document.getElementById('b2b-existing-import');
        var submitButton = document.getElementById('b2b-import-submit');
        var submitLabel = document.getElementById('b2b-import-submit-label');
        var uploadLabel = '{l s='Upload import' mod='b2bpriceimport' js=1}';
        var runExistingLabel = '{l s='Run selected import' mod='b2bpriceimport' js=1}';
        var deleteModal = document.getElementById('b2b-delete-import-modal');
        var deleteModalFile = document.getElementById('b2b-delete-import-file');
        var deleteFileCheckbox = document.getElementById('b2b-delete-import-file-checkbox');
        var confirmDeleteButton = document.getElementById('b2b-confirm-delete-import');
        var pendingDeleteButton = null;

        function showMessage(success, message) {
            var alert = document.createElement('div');
            alert.className = 'alert alert-' + (success ? 'success' : 'danger');
            alert.textContent = message;
            messageBox.innerHTML = '';
            messageBox.appendChild(alert);
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

        function getSelectedSource() {
            var selectedSource = importForm.querySelector('input[name="import_source"]:checked');

            return selectedSource ? selectedSource.value : 'upload';
        }

        function updateFileSource() {
            var useExistingFile = getSelectedSource() === 'existing';

            uploadFileGroup.classList.toggle('hidden', useExistingFile);
            existingFileGroup.classList.toggle('hidden', !useExistingFile);
            uploadFile.disabled = useExistingFile;
            uploadFile.required = !useExistingFile;
            existingImport.disabled = !useExistingFile;
            existingImport.required = useExistingFile;
            submitLabel.textContent = useExistingFile ? runExistingLabel : uploadLabel;
        }

        Array.prototype.forEach.call(importForm.querySelectorAll('input[name="import_source"]'), function (input) {
            input.addEventListener('change', updateFileSource);
        });

        updateFileSource();

        importForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var formData = new FormData(this);
            var useExistingFile = getSelectedSource() === 'existing';
            var action = useExistingFile ? 'RunStoredImport' : 'CreateImport';

            submitButton.disabled = true;

            fetch(ajaxUrl + '&ajax=1&action=' + action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(handleJsonResponse)
                .then(function (json) {
                    showMessage(json.success, json.message);
                    if (json.success) {
                        window.location.reload();
                        return;
                    }

                    submitButton.disabled = false;
                })
                .catch(function (error) {
                    showMessage(false, error.message);
                    submitButton.disabled = false;
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
                var importFile = this.getAttribute('data-import-file');

                pendingDeleteButton = this;
                deleteFileCheckbox.checked = false;
                deleteModalFile.textContent = importFile ? importFile + ' (#' + idImport + ')' : '#' + idImport;
                window.jQuery(deleteModal).modal('show');
            });
        });

        confirmDeleteButton.addEventListener('click', function () {
            if (pendingDeleteButton === null) {
                return;
            }

            var deleteButton = pendingDeleteButton;
            var formData = new FormData();
            formData.append('id_import', deleteButton.getAttribute('data-id-import'));
            formData.append('delete_file', deleteFileCheckbox.checked ? '1' : '0');

            confirmDeleteButton.disabled = true;
            deleteButton.disabled = true;

            fetch(ajaxUrl + '&ajax=1&action=DeleteImport', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(handleJsonResponse)
                .then(function (json) {
                    showMessage(json.success, json.message);

                    if (json.success) {
                        window.location.reload();
                        return;
                    }

                    confirmDeleteButton.disabled = false;
                    deleteButton.disabled = false;
                    window.jQuery(deleteModal).modal('hide');
                })
                .catch(function (error) {
                    showMessage(false, error.message);
                    confirmDeleteButton.disabled = false;
                    deleteButton.disabled = false;
                    window.jQuery(deleteModal).modal('hide');
                });
        });

        window.jQuery(deleteModal).on('hidden.bs.modal', function () {
            deleteFileCheckbox.checked = false;
            pendingDeleteButton = null;
        });
    })();
</script>

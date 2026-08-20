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
                        <input type="radio" id="b2b-source-upload" name="import_source" value="upload" checked>
                        {l s='Upload a new CSV file' mod='b2bpriceimport'}
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio"
                               id="b2b-source-existing"
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
                <p id="b2b-existing-file-help" class="help-block">
                    {if empty($existingImportFiles)}
                        {l s='No stored CSV files are available.' mod='b2bpriceimport'}
                    {else}
                        {l s='The selected stored CSV file will be registered if necessary and then processed.' mod='b2bpriceimport'}
                    {/if}
                </p>
            </div>
        </div>

        <div id="b2b-stored-files-section" class="form-group">
            <label class="control-label col-lg-3">
                {l s='Stored CSV files' mod='b2bpriceimport'}
            </label>
            <div class="col-lg-9">
                <div id="b2b-stored-files-message"></div>

                <div id="b2b-stored-files-empty"
                     class="alert alert-warning{if !empty($existingImportFiles)} hidden{/if}">
                    {l s='No stored CSV files are available.' mod='b2bpriceimport'}
                </div>

                <div id="b2b-stored-files-table-wrapper"
                     class="table-responsive{if empty($existingImportFiles)} hidden{/if}">
                    <table id="b2b-stored-files-table" class="table">
                        <thead>
                        <tr>
                            <th>{l s='File' mod='b2bpriceimport'}</th>
                            <th>{l s='Size' mod='b2bpriceimport'}</th>
                            <th>{l s='Modified' mod='b2bpriceimport'}</th>
                            <th>{l s='Linked import' mod='b2bpriceimport'}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody id="b2b-stored-files-body">
                        {foreach from=$existingImportFiles item=existingFile}
                            <tr data-stored-filename="{$existingFile.stored_filename|escape:'html':'UTF-8'}">
                                <td>
                                    <strong>{$existingFile.display_filename|escape:'html':'UTF-8'}</strong>
                                    {if $existingFile.display_filename != $existingFile.stored_filename}
                                        <br>
                                        <small class="text-muted">{$existingFile.stored_filename|escape:'html':'UTF-8'}</small>
                                    {/if}
                                </td>
                                <td>{$existingFile.file_size_display|escape:'html':'UTF-8'}</td>
                                <td>{$existingFile.modified_at_display|escape:'html':'UTF-8'}</td>
                                <td>
                                    {if !empty($existingFile.id_b2b_import)}
                                        <a href="{$ajaxUrl|escape:'html':'UTF-8'}&section=import_detail&id_import={$existingFile.id_b2b_import|intval}">
                                            #{$existingFile.id_b2b_import|intval}
                                        </a>
                                        ({$existingFile.status|escape:'html':'UTF-8'})
                                    {else}
                                        <span class="text-muted">{l s='Not imported' mod='b2bpriceimport'}</span>
                                    {/if}
                                </td>
                                <td class="text-right">
                                    <button type="button"
                                            class="btn btn-default b2b-select-stored-file"
                                            data-stored-filename="{$existingFile.stored_filename|escape:'html':'UTF-8'}">
                                        <i class="icon-check"></i>
                                        {l s='Select' mod='b2bpriceimport'}
                                    </button>
                                    <button type="button"
                                            class="btn btn-danger b2b-delete-stored-file"
                                            data-stored-filename="{$existingFile.stored_filename|escape:'html':'UTF-8'}"
                                            data-display-filename="{$existingFile.display_filename|escape:'html':'UTF-8'}"
                                            data-id-import="{if !empty($existingFile.id_b2b_import)}{$existingFile.id_b2b_import|intval}{/if}"
                                            {if empty($existingFile.can_delete)}disabled="disabled" title="{l s='The file cannot be deleted while its import is active.' mod='b2bpriceimport'}"{/if}>
                                        <i class="icon-trash"></i>
                                        {l s='Delete' mod='b2bpriceimport'}
                                    </button>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel-footer">
            <button id="b2b-import-submit" type="submit" class="btn btn-primary">
                <i class="process-icon-upload"></i>
                <span id="b2b-import-submit-label">{l s='Upload file' mod='b2bpriceimport'}</span>
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
                                data-id-import="{$import.id_b2b_import|intval}"
                                {if empty($import.file_available)}disabled="disabled" title="{l s='The stored CSV file is not available.' mod='b2bpriceimport'}"{/if}>
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

<div id="b2b-import-processing-overlay"
     class="b2b-import-processing-overlay hidden"
     aria-hidden="true">
    <div class="b2b-import-processing-dialog" role="status" aria-live="assertive">
        <div class="b2b-import-processing-spinner" aria-hidden="true"></div>
        <h3 id="b2b-import-processing-title">{l s='Operation in progress' mod='b2bpriceimport'}</h3>
        <p id="b2b-import-processing-message">
            {l s='Please wait for the current operation to finish.' mod='b2bpriceimport'}
        </p>
        <div class="progress progress-striped active b2b-import-processing-progress" aria-hidden="true">
            <div class="progress-bar progress-bar-info" style="width: 100%"></div>
        </div>
        <p class="help-block">
            {l s='Please do not close, reload, or leave this page until the current operation is complete.' mod='b2bpriceimport'}
        </p>
    </div>
</div>

<div id="b2b-upload-complete-modal"
     class="modal fade"
     tabindex="-1"
     role="dialog"
     aria-labelledby="b2b-upload-complete-modal-title"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="b2b-upload-complete-modal-title" class="modal-title">
                    <i class="icon-check-circle text-success"></i>
                    {l s='CSV file uploaded' mod='b2bpriceimport'}
                </h4>
            </div>
            <div class="modal-body">
                <p>
                    {l s='The file was stored successfully. The import has not been started.' mod='b2bpriceimport'}
                </p>
                <p>
                    <strong id="b2b-upload-complete-filename"></strong>
                </p>
                <p>{l s='What would you like to do next?' mod='b2bpriceimport'}</p>
            </div>
            <div class="modal-footer">
                <button id="b2b-continue-without-uploaded-file" type="button" class="btn btn-default">
                    {l s='Continue without selecting' mod='b2bpriceimport'}
                </button>
                <button id="b2b-select-uploaded-file" type="button" class="btn btn-primary">
                    <i class="icon-check"></i>
                    {l s='Select uploaded file' mod='b2bpriceimport'}
                </button>
            </div>
        </div>
    </div>
</div>

<div id="b2b-delete-stored-file-modal"
     class="modal fade"
     tabindex="-1"
     role="dialog"
     aria-labelledby="b2b-delete-stored-file-modal-title"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='b2bpriceimport'}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 id="b2b-delete-stored-file-modal-title" class="modal-title">
                    {l s='Delete stored CSV file' mod='b2bpriceimport'}
                </h4>
            </div>
            <div class="modal-body">
                <p>
                    {l s='Are you sure you want to delete this stored CSV file?' mod='b2bpriceimport'}
                    <strong id="b2b-delete-stored-filename"></strong>
                </p>
                <p id="b2b-delete-stored-file-warning" class="help-block"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {l s='Cancel' mod='b2bpriceimport'}
                </button>
                <button id="b2b-confirm-delete-stored-file" type="button" class="btn btn-danger">
                    <i class="icon-trash"></i>
                    {l s='Delete file' mod='b2bpriceimport'}
                </button>
            </div>
        </div>
    </div>
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
                <p class="help-block">
                    {l s='The import history will be deleted. The stored CSV file will remain on the server and can be managed in Stored CSV files.' mod='b2bpriceimport'}
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
        var uploadSourceRadio = document.getElementById('b2b-source-upload');
        var existingSourceRadio = document.getElementById('b2b-source-existing');
        var existingFileHelp = document.getElementById('b2b-existing-file-help');
        var storedFilesMessage = document.getElementById('b2b-stored-files-message');
        var storedFilesEmpty = document.getElementById('b2b-stored-files-empty');
        var storedFilesTableWrapper = document.getElementById('b2b-stored-files-table-wrapper');
        var storedFilesBody = document.getElementById('b2b-stored-files-body');
        var submitButton = document.getElementById('b2b-import-submit');
        var submitLabel = document.getElementById('b2b-import-submit-label');
        var uploadLabel = '{l s='Upload file' mod='b2bpriceimport' js=1}';
        var runExistingLabel = '{l s='Run selected import' mod='b2bpriceimport' js=1}';
        var processingOverlay = document.getElementById('b2b-import-processing-overlay');
        var processingTitle = document.getElementById('b2b-import-processing-title');
        var processingMessage = document.getElementById('b2b-import-processing-message');
        var uploadProcessingTitle = '{l s='File upload in progress' mod='b2bpriceimport' js=1}';
        var importProcessingTitle = '{l s='Import in progress' mod='b2bpriceimport' js=1}';
        var uploadProcessingMessage = '{l s='Uploading the CSV file. The import will not be started.' mod='b2bpriceimport' js=1}';
        var existingProcessingMessage = '{l s='Processing the selected CSV file. This may take several minutes.' mod='b2bpriceimport' js=1}';
        var notImportedLabel = '{l s='not imported yet' mod='b2bpriceimport' js=1}';
        var notImportedTableLabel = '{l s='Not imported' mod='b2bpriceimport' js=1}';
        var selectFileLabel = '{l s='Select' mod='b2bpriceimport' js=1}';
        var deleteFileLabel = '{l s='Delete' mod='b2bpriceimport' js=1}';
        var noStoredFilesHelpText = '{l s='No stored CSV files are available.' mod='b2bpriceimport' js=1}';
        var existingFileHelpText = '{l s='The selected stored CSV file will be registered if necessary and then processed.' mod='b2bpriceimport' js=1}';
        var uploadCompleteModal = document.getElementById('b2b-upload-complete-modal');
        var uploadCompleteFilename = document.getElementById('b2b-upload-complete-filename');
        var selectUploadedFileButton = document.getElementById('b2b-select-uploaded-file');
        var continueWithoutUploadedFileButton = document.getElementById('b2b-continue-without-uploaded-file');
        var deleteStoredFileModal = document.getElementById('b2b-delete-stored-file-modal');
        var deleteStoredFilename = document.getElementById('b2b-delete-stored-filename');
        var deleteStoredFileWarning = document.getElementById('b2b-delete-stored-file-warning');
        var confirmDeleteStoredFileButton = document.getElementById('b2b-confirm-delete-stored-file');
        var deleteModal = document.getElementById('b2b-delete-import-modal');
        var deleteModalFile = document.getElementById('b2b-delete-import-file');
        var confirmDeleteButton = document.getElementById('b2b-confirm-delete-import');
        var pendingDeleteButton = null;
        var pendingStoredFileButton = null;
        var pendingUploadedFile = null;
        var isOperationProcessing = false;

        function showMessage(success, message) {
            var alert = document.createElement('div');
            alert.className = 'alert alert-' + (success ? 'success' : 'danger');
            alert.textContent = message;
            messageBox.innerHTML = '';
            messageBox.appendChild(alert);
        }

        function showStoredFilesMessage(success, message) {
            var alert = document.createElement('div');
            alert.className = 'alert alert-' + (success ? 'success' : 'danger');
            alert.textContent = message;
            storedFilesMessage.innerHTML = '';
            storedFilesMessage.appendChild(alert);
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

        function startProcessing(title, message) {
            isOperationProcessing = true;
            processingTitle.textContent = title;
            processingMessage.textContent = message;
            processingOverlay.classList.remove('hidden');
            processingOverlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('b2b-import-processing');
        }

        function stopProcessing() {
            isOperationProcessing = false;
            processingOverlay.classList.add('hidden');
            processingOverlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('b2b-import-processing');
        }

        function reloadAfterImport() {
            isOperationProcessing = false;
            window.location.reload();
        }

        function findStoredFileOption(storedFilename) {
            var index;

            for (index = 0; index < existingImport.options.length; index++) {
                if (existingImport.options[index].value === storedFilename) {
                    return existingImport.options[index];
                }
            }

            return null;
        }

        function findStoredFileRow(storedFilename) {
            var rows = storedFilesBody.querySelectorAll('tr');
            var index;

            for (index = 0; index < rows.length; index++) {
                if (rows[index].getAttribute('data-stored-filename') === storedFilename) {
                    return rows[index];
                }
            }

            return null;
        }

        function createStoredFileActionButton(className, iconClass, label) {
            var button = document.createElement('button');
            var icon = document.createElement('i');

            button.type = 'button';
            button.className = className;
            icon.className = iconClass;
            button.appendChild(icon);
            button.appendChild(document.createTextNode(' ' + label));

            return button;
        }

        function addUploadedFileToStoredFiles(file) {
            if (findStoredFileRow(file.stored_filename) !== null) {
                return;
            }

            var row = document.createElement('tr');
            var fileCell = document.createElement('td');
            var displayFilename = document.createElement('strong');
            var sizeCell = document.createElement('td');
            var modifiedCell = document.createElement('td');
            var linkedImportCell = document.createElement('td');
            var notImported = document.createElement('span');
            var actionsCell = document.createElement('td');
            var selectButton = createStoredFileActionButton(
                'btn btn-default b2b-select-stored-file',
                'icon-check',
                selectFileLabel
            );
            var deleteButton = createStoredFileActionButton(
                'btn btn-danger b2b-delete-stored-file',
                'icon-trash',
                deleteFileLabel
            );

            row.setAttribute('data-stored-filename', file.stored_filename);
            displayFilename.textContent = file.display_filename;
            fileCell.appendChild(displayFilename);

            if (file.display_filename !== file.stored_filename) {
                var storedFilename = document.createElement('small');
                storedFilename.className = 'text-muted';
                storedFilename.textContent = file.stored_filename;
                fileCell.appendChild(document.createElement('br'));
                fileCell.appendChild(storedFilename);
            }

            sizeCell.textContent = file.file_size_display || String(file.file_size || 0) + ' B';
            modifiedCell.textContent = file.modified_at_display || '';
            notImported.className = 'text-muted';
            notImported.textContent = notImportedTableLabel;
            linkedImportCell.appendChild(notImported);
            actionsCell.className = 'text-right';
            selectButton.setAttribute('data-stored-filename', file.stored_filename);
            deleteButton.setAttribute('data-stored-filename', file.stored_filename);
            deleteButton.setAttribute('data-display-filename', file.display_filename);
            deleteButton.setAttribute('data-id-import', '');
            actionsCell.appendChild(selectButton);
            actionsCell.appendChild(document.createTextNode(' '));
            actionsCell.appendChild(deleteButton);
            row.appendChild(fileCell);
            row.appendChild(sizeCell);
            row.appendChild(modifiedCell);
            row.appendChild(linkedImportCell);
            row.appendChild(actionsCell);

            storedFilesBody.insertBefore(row, storedFilesBody.firstChild);
            storedFilesEmpty.classList.add('hidden');
            storedFilesTableWrapper.classList.remove('hidden');
        }

        function addUploadedFileToSelect(file) {
            var option = findStoredFileOption(file.stored_filename);

            if (option === null) {
                option = document.createElement('option');
                option.value = file.stored_filename;
                option.textContent = file.display_filename + ' (' + notImportedLabel + ')';

                if (existingImport.options.length > 1) {
                    existingImport.insertBefore(option, existingImport.options[1]);
                } else {
                    existingImport.appendChild(option);
                }
            }

            existingSourceRadio.disabled = false;
            existingFileHelp.textContent = existingFileHelpText;

            return option;
        }

        function selectStoredFile(storedFilename) {
            var option = findStoredFileOption(storedFilename);

            if (option === null) {
                return;
            }

            existingImport.value = storedFilename;
            existingSourceRadio.disabled = false;
            existingSourceRadio.checked = true;
            updateFileSource();
            existingImport.focus();
        }

        function removeStoredFileFromInterface(storedFilename, idImport) {
            var row = findStoredFileRow(storedFilename);
            var option = findStoredFileOption(storedFilename);
            var selectedFileWasDeleted = existingImport.value === storedFilename;

            if (row !== null) {
                row.parentNode.removeChild(row);
            }

            if (option !== null) {
                option.parentNode.removeChild(option);
            }

            if (idImport) {
                var runButton = document.querySelector('.b2b-run-import[data-id-import="' + idImport + '"]');

                if (runButton !== null) {
                    runButton.disabled = true;
                    runButton.title = '{l s='The stored CSV file is not available.' mod='b2bpriceimport' js=1}';
                }
            }

            if (existingImport.options.length <= 1) {
                existingSourceRadio.disabled = true;
                existingFileHelp.textContent = noStoredFilesHelpText;
                storedFilesEmpty.classList.remove('hidden');
                storedFilesTableWrapper.classList.add('hidden');
            }

            if (selectedFileWasDeleted) {
                existingImport.value = '';
                uploadSourceRadio.checked = true;
                updateFileSource();
            }
        }

        function showUploadCompleteChoice(file) {
            pendingUploadedFile = file;
            uploadCompleteFilename.textContent = file.display_filename;
            window.jQuery(uploadCompleteModal).modal({
                backdrop: 'static',
                keyboard: false,
                show: true
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
            var action = useExistingFile ? 'RunStoredImport' : 'UploadImportFile';

            submitButton.disabled = true;
            startProcessing(
                useExistingFile ? importProcessingTitle : uploadProcessingTitle,
                useExistingFile ? existingProcessingMessage : uploadProcessingMessage
            );

            fetch(ajaxUrl + '&ajax=1&action=' + action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(handleJsonResponse)
                .then(function (json) {
                    if (!json.success) {
                        showMessage(false, json.message);
                        stopProcessing();
                        submitButton.disabled = false;
                        return;
                    }

                    if (useExistingFile) {
                        showMessage(true, json.message);
                        reloadAfterImport();
                        return;
                    }

                    if (!json.file || !json.file.stored_filename || !json.file.display_filename) {
                        throw new Error('The server did not return the uploaded file details.');
                    }

                    addUploadedFileToSelect(json.file);
                    addUploadedFileToStoredFiles(json.file);
                    stopProcessing();
                    submitButton.disabled = false;
                    uploadFile.value = '';
                    showMessage(true, json.message);
                    showUploadCompleteChoice(json.file);
                })
                .catch(function (error) {
                    showMessage(false, error.message);
                    stopProcessing();
                    submitButton.disabled = false;
                });
        });

        selectUploadedFileButton.addEventListener('click', function () {
            if (pendingUploadedFile === null) {
                return;
            }

            selectStoredFile(pendingUploadedFile.stored_filename);
            window.jQuery(uploadCompleteModal).modal('hide');
        });

        continueWithoutUploadedFileButton.addEventListener('click', function () {
            window.jQuery(uploadCompleteModal).modal('hide');
        });

        window.jQuery(uploadCompleteModal).on('hidden.bs.modal', function () {
            uploadCompleteFilename.textContent = '';
            pendingUploadedFile = null;
        });

        storedFilesBody.addEventListener('click', function (event) {
            var selectButton = event.target.closest('.b2b-select-stored-file');
            var deleteButton = event.target.closest('.b2b-delete-stored-file');

            if (selectButton !== null) {
                selectStoredFile(selectButton.getAttribute('data-stored-filename'));
                return;
            }

            if (deleteButton === null || deleteButton.disabled) {
                return;
            }

            var idImport = deleteButton.getAttribute('data-id-import');

            pendingStoredFileButton = deleteButton;
            deleteStoredFilename.textContent = deleteButton.getAttribute('data-display-filename');
            deleteStoredFileWarning.textContent = idImport
                ? '{l s='The linked import history will remain, but this import cannot be run again after the file is deleted.' mod='b2bpriceimport' js=1}'
                : '{l s='Only the physical CSV file will be deleted.' mod='b2bpriceimport' js=1}';
            window.jQuery(deleteStoredFileModal).modal('show');
        });

        confirmDeleteStoredFileButton.addEventListener('click', function () {
            if (pendingStoredFileButton === null) {
                return;
            }

            var deleteButton = pendingStoredFileButton;
            var storedFilename = deleteButton.getAttribute('data-stored-filename');
            var idImport = deleteButton.getAttribute('data-id-import');
            var formData = new FormData();
            formData.append('stored_filename', storedFilename);

            confirmDeleteStoredFileButton.disabled = true;
            deleteButton.disabled = true;

            fetch(ajaxUrl + '&ajax=1&action=DeleteStoredImportFile', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(handleJsonResponse)
                .then(function (json) {
                    if (!json.success) {
                        showStoredFilesMessage(false, json.message);
                        confirmDeleteStoredFileButton.disabled = false;
                        deleteButton.disabled = false;
                        window.jQuery(deleteStoredFileModal).modal('hide');
                        return;
                    }

                    removeStoredFileFromInterface(storedFilename, idImport || json.id_import);
                    showStoredFilesMessage(true, json.message);
                    window.jQuery(deleteStoredFileModal).modal('hide');
                })
                .catch(function (error) {
                    showStoredFilesMessage(false, error.message);
                    confirmDeleteStoredFileButton.disabled = false;
                    deleteButton.disabled = false;
                    window.jQuery(deleteStoredFileModal).modal('hide');
                });
        });

        window.jQuery(deleteStoredFileModal).on('hidden.bs.modal', function () {
            deleteStoredFilename.textContent = '';
            deleteStoredFileWarning.textContent = '';
            confirmDeleteStoredFileButton.disabled = false;
            pendingStoredFileButton = null;
        });

        Array.prototype.forEach.call(document.querySelectorAll('.b2b-run-import'), function (button) {
            button.addEventListener('click', function () {
                var idImport = this.getAttribute('data-id-import');
                var formData = new FormData();
                formData.append('id_import', idImport);

                this.disabled = true;
                startProcessing(importProcessingTitle, existingProcessingMessage);

                var runButton = this;

                fetch(ajaxUrl + '&ajax=1&action=RunImport', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                    .then(handleJsonResponse)
                    .then(function (json) {
                        showMessage(json.success, json.message);

                        if (json.success) {
                            reloadAfterImport();
                            return;
                        }

                        stopProcessing();
                        runButton.disabled = false;
                    })
                    .catch(function (error) {
                        showMessage(false, error.message);
                        stopProcessing();
                        runButton.disabled = false;
                    });
            });
        });

        Array.prototype.forEach.call(document.querySelectorAll('.b2b-delete-import'), function (button) {
            button.addEventListener('click', function () {
                var idImport = this.getAttribute('data-id-import');
                var importFile = this.getAttribute('data-import-file');

                pendingDeleteButton = this;
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
            pendingDeleteButton = null;
        });

        window.addEventListener('beforeunload', function (event) {
            if (!isOperationProcessing) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });
    })();
</script>

{if empty($import)}
    <div class="alert alert-danger">
        {l s='Import not found.' mod='b2bpriceimport'}
    </div>
    <a class="btn btn-default" href="{$importListUrl|escape:'html':'UTF-8'}">
        <i class="icon-arrow-left"></i>
        {l s='Back to imports' mod='b2bpriceimport'}
    </a>
{else}
    <div class="panel">
        <h3>
            <i class="icon-file-text"></i>
            {l s='Import details' mod='b2bpriceimport'} #{$import.id_b2b_import|intval}
        </h3>

        <p>
            <a class="btn btn-default" href="{$importListUrl|escape:'html':'UTF-8'}">
                <i class="icon-arrow-left"></i>
                {l s='Back to imports' mod='b2bpriceimport'}
            </a>
        </p>

        <table class="table">
            <tbody>
                <tr>
                    <th>{l s='File' mod='b2bpriceimport'}</th>
                    <td>{$import.original_filename|escape:'html':'UTF-8'}</td>
                    <th>{l s='Status' mod='b2bpriceimport'}</th>
                    <td>{$import.status|escape:'html':'UTF-8'}</td>
                </tr>
                <tr>
                    <th>{l s='Stored file' mod='b2bpriceimport'}</th>
                    <td>{$import.stored_filename|escape:'html':'UTF-8'}</td>
                    <th>{l s='Source' mod='b2bpriceimport'}</th>
                    <td>{$import.source|escape:'html':'UTF-8'}</td>
                </tr>
                <tr>
                    <th>{l s='Rows' mod='b2bpriceimport'}</th>
                    <td>{$import.total_rows|intval}</td>
                    <th>{l s='Processed' mod='b2bpriceimport'}</th>
                    <td>{$import.processed_rows|intval}</td>
                </tr>
                <tr>
                    <th>{l s='Success' mod='b2bpriceimport'}</th>
                    <td>{$import.success_rows|intval}</td>
                    <th>{l s='Failed' mod='b2bpriceimport'}</th>
                    <td>{$import.failed_rows|intval}</td>
                </tr>
                <tr>
                    <th>{l s='Created' mod='b2bpriceimport'}</th>
                    <td>{$import.date_add|escape:'html':'UTF-8'}</td>
                    <th>{l s='Updated' mod='b2bpriceimport'}</th>
                    <td>{$import.date_upd|escape:'html':'UTF-8'}</td>
                </tr>
                {if !empty($import.last_error)}
                    <tr>
                        <th>{l s='Last error' mod='b2bpriceimport'}</th>
                        <td colspan="3">{$import.last_error|escape:'html':'UTF-8'}</td>
                    </tr>
                {/if}
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h3>
            <i class="icon-tasks"></i>
            {l s='Import jobs' mod='b2bpriceimport'}
        </h3>

        {if empty($importJobs)}
            <div class="alert alert-warning">
                {l s='No jobs found for this import.' mod='b2bpriceimport'}
            </div>
        {else}
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='ID' mod='b2bpriceimport'}</th>
                        <th>{l s='Type' mod='b2bpriceimport'}</th>
                        <th>{l s='Status' mod='b2bpriceimport'}</th>
                        <th>{l s='Attempts' mod='b2bpriceimport'}</th>
                        <th>{l s='Started' mod='b2bpriceimport'}</th>
                        <th>{l s='Finished' mod='b2bpriceimport'}</th>
                        <th>{l s='Error' mod='b2bpriceimport'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$importJobs item=job}
                        <tr>
                            <td>{$job.id_b2b_import_job|intval}</td>
                            <td>{$job.type|escape:'html':'UTF-8'}</td>
                            <td>{$job.status|escape:'html':'UTF-8'}</td>
                            <td>{$job.attempts|intval} / {$job.max_attempts|intval}</td>
                            <td>{$job.started_at|escape:'html':'UTF-8'}</td>
                            <td>{$job.finished_at|escape:'html':'UTF-8'}</td>
                            <td>{$job.last_error|escape:'html':'UTF-8'}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {/if}
    </div>

    <div class="panel">
        <h3>
            <i class="icon-list"></i>
            {l s='Imported positions' mod='b2bpriceimport'}
        </h3>

        {if empty($importItemsHasRows)}
            <div class="alert alert-warning">
                {l s='No imported rows found yet. Run the import parser first.' mod='b2bpriceimport'}
            </div>
        {else}
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='Row' mod='b2bpriceimport'}</th>
                        <th>
                            <div>{l s='Reference' mod='b2bpriceimport'}</div>
                            <div class="input-group" style="min-width: 150px;">
                                <input id="import-items-reference-search"
                                       type="search"
                                       class="form-control input-sm js-import-items-search"
                                       value="{$importItemsSearches.reference.value|escape:'html':'UTF-8'}"
                                       data-base-url="{$importItemsSearches.reference.base_url|escape:'html':'UTF-8'}"
                                       data-parameter="{$importItemsSearches.reference.parameter|escape:'html':'UTF-8'}"
                                       placeholder="{l s='Search...' mod='b2bpriceimport'}"
                                       aria-label="{l s='Search by reference' mod='b2bpriceimport'}">
                                <span class="input-group-btn">
                                    <button type="button"
                                            class="btn btn-default btn-sm js-import-items-search-submit"
                                            data-search-input="import-items-reference-search"
                                            title="{l s='Search by reference' mod='b2bpriceimport'}">
                                        <i class="icon-search"></i>
                                    </button>
                                </span>
                            </div>
                        </th>
                        <th>
                            <div>{l s='Product name' mod='b2bpriceimport'}</div>
                            <div class="input-group" style="min-width: 200px;">
                                <input id="import-items-product-name-search"
                                       type="search"
                                       class="form-control input-sm js-import-items-search"
                                       value="{$importItemsSearches.product_name.value|escape:'html':'UTF-8'}"
                                       data-base-url="{$importItemsSearches.product_name.base_url|escape:'html':'UTF-8'}"
                                       data-parameter="{$importItemsSearches.product_name.parameter|escape:'html':'UTF-8'}"
                                       placeholder="{l s='Search...' mod='b2bpriceimport'}"
                                       aria-label="{l s='Search by product name' mod='b2bpriceimport'}">
                                <span class="input-group-btn">
                                    <button type="button"
                                            class="btn btn-default btn-sm js-import-items-search-submit"
                                            data-search-input="import-items-product-name-search"
                                            title="{l s='Search by product name' mod='b2bpriceimport'}">
                                        <i class="icon-search"></i>
                                    </button>
                                </span>
                            </div>
                        </th>
                        <th>{l s='Product ID' mod='b2bpriceimport'}</th>
                        <th>{l s='Source price' mod='b2bpriceimport'}</th>
                        <th>{l s='Currency' mod='b2bpriceimport'}</th>
                        <th>{l s='Rate' mod='b2bpriceimport'}</th>
                        <th>{l s='UAH price' mod='b2bpriceimport'}</th>
                        <th>
                            <div>{l s='Active' mod='b2bpriceimport'}</div>
                            <select class="form-control input-sm"
                                    aria-label="{l s='Filter by active status' mod='b2bpriceimport'}"
                                    onchange="window.location.href = this.value;">
                                <option value="{$importItemsFilters.active.all_url|escape:'html':'UTF-8'}"{if $importItemsFilters.active.is_all} selected="selected"{/if}>
                                    {l s='All' mod='b2bpriceimport'}
                                </option>
                                {foreach from=$importItemsFilters.active.options item=filterOption}
                                    <option value="{$filterOption.url|escape:'html':'UTF-8'}"{if $filterOption.is_current} selected="selected"{/if}>
                                        {$filterOption.label|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </th>
                        <th>
                            <div>{l s='Validation' mod='b2bpriceimport'}</div>
                            <select class="form-control input-sm"
                                    aria-label="{l s='Filter by validation status' mod='b2bpriceimport'}"
                                    onchange="window.location.href = this.value;">
                                <option value="{$importItemsFilters.validation_status.all_url|escape:'html':'UTF-8'}"{if $importItemsFilters.validation_status.is_all} selected="selected"{/if}>
                                    {l s='All' mod='b2bpriceimport'}
                                </option>
                                {foreach from=$importItemsFilters.validation_status.options item=filterOption}
                                    <option value="{$filterOption.url|escape:'html':'UTF-8'}"{if $filterOption.is_current} selected="selected"{/if}>
                                        {$filterOption.label|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </th>
                        <th>
                            <div>{l s='Processing' mod='b2bpriceimport'}</div>
                            <select class="form-control input-sm"
                                    aria-label="{l s='Filter by processing status' mod='b2bpriceimport'}"
                                    onchange="window.location.href = this.value;">
                                <option value="{$importItemsFilters.processing_status.all_url|escape:'html':'UTF-8'}"{if $importItemsFilters.processing_status.is_all} selected="selected"{/if}>
                                    {l s='All' mod='b2bpriceimport'}
                                </option>
                                {foreach from=$importItemsFilters.processing_status.options item=filterOption}
                                    <option value="{$filterOption.url|escape:'html':'UTF-8'}"{if $filterOption.is_current} selected="selected"{/if}>
                                        {$filterOption.label|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </th>
                        <th>
                            <div>{l s='Item status' mod='b2bpriceimport'}</div>
                            <select class="form-control input-sm"
                                    aria-label="{l s='Filter by item status' mod='b2bpriceimport'}"
                                    onchange="window.location.href = this.value;">
                                <option value="{$importItemsFilters.item_status.all_url|escape:'html':'UTF-8'}"{if $importItemsFilters.item_status.is_all} selected="selected"{/if}>
                                    {l s='All' mod='b2bpriceimport'}
                                </option>
                                {foreach from=$importItemsFilters.item_status.options item=filterOption}
                                    <option value="{$filterOption.url|escape:'html':'UTF-8'}"{if $filterOption.is_current} selected="selected"{/if}>
                                        {$filterOption.label|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </th>
                        <th>
                            <div>{l s='Error' mod='b2bpriceimport'}</div>
                            <select class="form-control input-sm"
                                    style="min-width: 160px; max-width: 260px;"
                                    aria-label="{l s='Filter by error' mod='b2bpriceimport'}"
                                    onchange="window.location.href = this.value;">
                                <option value="{$importItemsFilters.error.all_url|escape:'html':'UTF-8'}"{if $importItemsFilters.error.is_all} selected="selected"{/if}>
                                    {l s='All' mod='b2bpriceimport'}
                                </option>
                                {foreach from=$importItemsFilters.error.options item=filterOption}
                                    <option value="{$filterOption.url|escape:'html':'UTF-8'}"{if $filterOption.is_current} selected="selected"{/if}>
                                        {$filterOption.label|escape:'html':'UTF-8'}
                                    </option>
                                {/foreach}
                            </select>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {if empty($importItems)}
                        <tr>
                            <td colspan="13" class="text-center">
                                {l s='No rows match the selected filters.' mod='b2bpriceimport'}
                            </td>
                        </tr>
                    {else}
                        {foreach from=$importItems item=item}
                            <tr class="{if $item.status == 'failed' || $item.validation_status == 'failed' || $item.processing_status == 'failed'}danger{elseif $item.status == 'processed' || $item.processing_status == 'processed'}success{/if}">
                                <td>{$item.row_number|intval}</td>
                                <td>{$item.reference|escape:'html':'UTF-8'}</td>
                                <td>{$item.product_name|escape:'html':'UTF-8'}</td>
                                <td>{$item.id_product|intval}</td>
                                <td>{$item.source_price|escape:'html':'UTF-8'}</td>
                                <td>{$item.currency_code|escape:'html':'UTF-8'}</td>
                                <td>{$item.currency_rate|escape:'html':'UTF-8'}</td>
                                <td>{$item.price_uah|escape:'html':'UTF-8'}</td>
                                <td>{$item.active|escape:'html':'UTF-8'}</td>
                                <td>{$item.validation_status|escape:'html':'UTF-8'}</td>
                                <td>{$item.processing_status|escape:'html':'UTF-8'}</td>
                                <td>{$item.status|escape:'html':'UTF-8'}</td>
                                <td>
                                    {if !empty($item.error_message)}
                                        {$item.error_message|escape:'html':'UTF-8'}
                                    {elseif !empty($item.staging_error_message)}
                                        {$item.staging_error_message|escape:'html':'UTF-8'}
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    {/if}
                </tbody>
            </table>

            <div class="row" style="margin-top: 15px;">
                <div class="col-sm-6">
                    <span>
                        {l s='Showing' mod='b2bpriceimport'}
                        {$importItemsPagination.first_item|intval}-{$importItemsPagination.last_item|intval}
                        {l s='of' mod='b2bpriceimport'}
                        {$importItemsPagination.total_items|intval}
                    </span>

                    <label for="import-items-page-size" style="margin-left: 15px;">
                        {l s='Rows per page' mod='b2bpriceimport'}
                    </label>
                    <select id="import-items-page-size"
                            class="form-control input-sm"
                            style="display: inline-block; width: auto; margin-left: 5px;"
                            onchange="window.location.href = this.value;">
                        {foreach from=$importItemsPagination.page_size_options item=pageSizeOption}
                            <option value="{$pageSizeOption.url|escape:'html':'UTF-8'}"{if $pageSizeOption.is_current} selected="selected"{/if}>
                                {$pageSizeOption.value|intval}
                            </option>
                        {/foreach}
                    </select>
                </div>

                {if $importItemsPagination.total_pages > 1}
                    <div class="col-sm-6">
                        <nav aria-label="{l s='Imported positions pagination' mod='b2bpriceimport'}">
                            <ul class="pagination pagination-sm pull-right" style="margin: 0;">
                                <li{if empty($importItemsPagination.previous_url)} class="disabled"{/if}>
                                    {if empty($importItemsPagination.previous_url)}
                                        <span aria-hidden="true">&laquo;</span>
                                    {else}
                                        <a href="{$importItemsPagination.previous_url|escape:'html':'UTF-8'}"
                                           aria-label="{l s='Previous page' mod='b2bpriceimport'}">&laquo;</a>
                                    {/if}
                                </li>

                                {foreach from=$importItemsPagination.pages item=paginationPage}
                                    {if $paginationPage.ellipsis}
                                        <li class="disabled"><span>&hellip;</span></li>
                                    {elseif $paginationPage.is_current}
                                        <li class="active">
                                            <span>
                                                {$paginationPage.number|intval}
                                                <span class="sr-only">({l s='current page' mod='b2bpriceimport'})</span>
                                            </span>
                                        </li>
                                    {else}
                                        <li>
                                            <a href="{$paginationPage.url|escape:'html':'UTF-8'}">
                                                {$paginationPage.number|intval}
                                            </a>
                                        </li>
                                    {/if}
                                {/foreach}

                                <li{if empty($importItemsPagination.next_url)} class="disabled"{/if}>
                                    {if empty($importItemsPagination.next_url)}
                                        <span aria-hidden="true">&raquo;</span>
                                    {else}
                                        <a href="{$importItemsPagination.next_url|escape:'html':'UTF-8'}"
                                           aria-label="{l s='Next page' mod='b2bpriceimport'}">&raquo;</a>
                                    {/if}
                                </li>
                            </ul>
                        </nav>
                    </div>
                {/if}
            </div>
        {/if}
    </div>

    <script>
        (function () {
            function applyImportItemsSearch(input) {
                var url = input.getAttribute('data-base-url');
                var parameter = input.getAttribute('data-parameter');
                var value = input.value.trim();

                if (value !== '') {
                    url += '&' + encodeURIComponent(parameter) + '=' + encodeURIComponent(value);
                }

                window.location.href = url;
            }

            Array.prototype.forEach.call(
                document.querySelectorAll('.js-import-items-search'),
                function (input) {
                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            applyImportItemsSearch(this);
                        }
                    });
                }
            );

            Array.prototype.forEach.call(
                document.querySelectorAll('.js-import-items-search-submit'),
                function (button) {
                    button.addEventListener('click', function () {
                        var input = document.getElementById(this.getAttribute('data-search-input'));

                        if (input) {
                            applyImportItemsSearch(input);
                        }
                    });
                }
            );
        })();
    </script>
{/if}

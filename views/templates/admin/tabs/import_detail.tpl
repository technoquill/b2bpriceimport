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
                    <th>{l s='Warnings' mod='b2bpriceimport'}</th>
                    <td>{$import.warning_rows|intval}</td>
                    <th></th>
                    <td></td>
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

    <div class="panel b2b-import-items-panel">
        <h3>
            <i class="icon-list"></i>
            {l s='Imported positions' mod='b2bpriceimport'}
        </h3>

        {if empty($importItemsHasRows)}
            <div class="alert alert-warning">
                {l s='No imported rows found yet. Run the import parser first.' mod='b2bpriceimport'}
            </div>
        {else}
            <div class="b2b-import-items-toolbar clearfix">
                <a class="btn btn-default pull-right{if empty($importItemsHasActiveCriteria)} disabled{/if}"
                   href="{$importItemsResetUrl|escape:'html':'UTF-8'}"
                   {if empty($importItemsHasActiveCriteria)}aria-disabled="true" tabindex="-1"{/if}>
                    <i class="icon-eraser"></i>
                    {l s='Reset all filters' mod='b2bpriceimport'}
                </a>
            </div>

            <div class="table-responsive b2b-import-items-table-wrapper">
                <table class="table table-striped table-hover b2b-import-items-table">
                <thead>
                    <tr>
                        <th>{l s='Row' mod='b2bpriceimport'}</th>
                        <th>
                            <span class="b2b-import-items-filter-label">{l s='Reference' mod='b2bpriceimport'}</span>
                            <div class="input-group input-group-sm b2b-import-items-search b2b-import-items-search-reference">
                                <input id="import-items-reference-search"
                                       type="search"
                                       class="form-control js-import-items-search"
                                       value="{$importItemsSearches.reference.value|escape:'html':'UTF-8'}"
                                       data-base-url="{$importItemsSearches.reference.base_url|escape:'html':'UTF-8'}"
                                       data-parameter="{$importItemsSearches.reference.parameter|escape:'html':'UTF-8'}"
                                       placeholder="{l s='Search...' mod='b2bpriceimport'}"
                                       aria-label="{l s='Search by reference' mod='b2bpriceimport'}">
                                <span class="input-group-btn">
                                    <button type="button"
                                            class="btn btn-default js-import-items-search-submit"
                                            data-search-input="import-items-reference-search"
                                            title="{l s='Search by reference' mod='b2bpriceimport'}">
                                        <i class="icon-search"></i>
                                    </button>
                                </span>
                            </div>
                        </th>
                        <th>
                            <span class="b2b-import-items-filter-label">{l s='Product name' mod='b2bpriceimport'}</span>
                            <div class="input-group input-group-sm b2b-import-items-search b2b-import-items-search-product">
                                <input id="import-items-product-name-search"
                                       type="search"
                                       class="form-control js-import-items-search"
                                       value="{$importItemsSearches.product_name.value|escape:'html':'UTF-8'}"
                                       data-base-url="{$importItemsSearches.product_name.base_url|escape:'html':'UTF-8'}"
                                       data-parameter="{$importItemsSearches.product_name.parameter|escape:'html':'UTF-8'}"
                                       placeholder="{l s='Search...' mod='b2bpriceimport'}"
                                       aria-label="{l s='Search by product name' mod='b2bpriceimport'}">
                                <span class="input-group-btn">
                                    <button type="button"
                                            class="btn btn-default js-import-items-search-submit"
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
                            <span class="b2b-import-items-filter-label">{l s='Active' mod='b2bpriceimport'}</span>
                            <select class="form-control input-sm b2b-import-items-filter-control"
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
                            <span class="b2b-import-items-filter-label">{l s='Validation' mod='b2bpriceimport'}</span>
                            <select class="form-control input-sm b2b-import-items-filter-control"
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
                            <span class="b2b-import-items-filter-label">{l s='Processing' mod='b2bpriceimport'}</span>
                            <select class="form-control input-sm b2b-import-items-filter-control"
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
                            <span class="b2b-import-items-filter-label">{l s='Item status' mod='b2bpriceimport'}</span>
                            <select class="form-control input-sm b2b-import-items-filter-control"
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
                            <span class="b2b-import-items-filter-label">{l s='Error' mod='b2bpriceimport'}</span>
                            <select class="form-control input-sm b2b-import-items-filter-control b2b-import-items-filter-error"
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
                        <th>{l s='Actions' mod='b2bpriceimport'}</th>
                    </tr>
                </thead>
                <tbody>
                    {if empty($importItems)}
                        <tr>
                            <td colspan="14" class="text-center">
                                {l s='No rows match the selected filters.' mod='b2bpriceimport'}
                            </td>
                        </tr>
                    {else}
                        {foreach from=$importItems item=item}
                            <tr class="{if $item.status == 'failed' || $item.validation_status == 'failed' || $item.processing_status == 'failed'}danger{elseif $item.status == 'unmatched' || $item.processing_status == 'waiting_product'}warning{elseif $item.status == 'processed' || $item.status == 'created' || $item.processing_status == 'processed' || $item.processing_status == 'created'}success{/if}">
                                <td>{$item.row_number|intval}</td>
                                <td>{$item.reference|escape:'html':'UTF-8'}</td>
                                <td>{$item.product_name|escape:'html':'UTF-8'}</td>
                                <td>
                                    {if !empty($item.product_url)}
                                        <a href="{$item.product_url|escape:'html':'UTF-8'}"
                                           target="_blank"
                                           rel="noopener noreferrer">
                                            {$item.id_product|intval}
                                        </a>
                                    {else}
                                        &mdash;
                                    {/if}
                                </td>
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
                                <td class="text-nowrap b2b-import-item-actions">
                                    {if !empty($item.can_resolve_product)}
                                        <button type="button"
                                                class="btn btn-primary btn-sm b2b-import-item-action js-resolve-import-item"
                                                data-id-import-item="{$item.id_b2b_import_item|intval}"
                                                data-reference="{$item.reference|escape:'html':'UTF-8'}"
                                                data-product-name="{$item.product_name|escape:'html':'UTF-8'}"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                title="{l s='Add product' mod='b2bpriceimport'}"
                                                aria-label="{l s='Add product' mod='b2bpriceimport'}">
                                            <i class="icon-plus" aria-hidden="true"></i>
                                            <span class="sr-only">{l s='Add product' mod='b2bpriceimport'}</span>
                                        </button>
                                    {elseif !empty($item.product_url)}
                                        <a class="btn btn-default btn-sm b2b-import-item-action"
                                           href="{$item.product_url|escape:'html':'UTF-8'}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           data-toggle="tooltip"
                                           data-placement="top"
                                           title="{l s='Open product' mod='b2bpriceimport'}"
                                           aria-label="{l s='Open product' mod='b2bpriceimport'}">
                                            <i class="icon-external-link" aria-hidden="true"></i>
                                            <span class="sr-only">{l s='Open product' mod='b2bpriceimport'}</span>
                                        </a>
                                    {else}
                                        &mdash;
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    {/if}
                </tbody>
                </table>
            </div>

            <div class="panel-footer b2b-import-items-footer">
                <div class="b2b-import-items-summary">
                    <span class="b2b-import-items-range">
                        {l s='Showing' mod='b2bpriceimport'}
                        {$importItemsPagination.first_item|intval}-{$importItemsPagination.last_item|intval}
                        {l s='of' mod='b2bpriceimport'}
                        {$importItemsPagination.total_items|intval}
                    </span>

                    <label for="import-items-page-size" class="b2b-import-items-page-size-label">
                        {l s='Rows per page' mod='b2bpriceimport'}
                    </label>
                    <select id="import-items-page-size"
                            class="form-control input-sm b2b-import-items-page-size"
                            onchange="window.location.href = this.value;">
                        {foreach from=$importItemsPagination.page_size_options item=pageSizeOption}
                            <option value="{$pageSizeOption.url|escape:'html':'UTF-8'}"{if $pageSizeOption.is_current} selected="selected"{/if}>
                                {$pageSizeOption.value|intval}
                            </option>
                        {/foreach}
                    </select>
                </div>

                {if $importItemsPagination.total_pages > 1}
                    <nav class="b2b-import-items-pagination"
                         aria-label="{l s='Imported positions pagination' mod='b2bpriceimport'}">
                            <ul class="pagination pagination-sm">
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
                {/if}
            </div>
        {/if}
    </div>

    <div id="b2b-import-item-modal"
         class="modal fade"
         tabindex="-1"
         role="dialog"
         aria-labelledby="b2b-import-item-modal-title"
         data-ajax-url="{$ajaxUrl|escape:'html':'UTF-8'}"
         data-id-import="{$import.id_b2b_import|intval}"
         data-create-label="{l s='Create disabled product' mod='b2bpriceimport'}"
         data-link-label="{l s='Link and process' mod='b2bpriceimport'}">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='b2bpriceimport'}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 id="b2b-import-item-modal-title" class="modal-title">
                        {l s='Add product' mod='b2bpriceimport'}
                    </h4>
                </div>

                <form id="b2b-import-item-resolution-form">
                    <div class="modal-body">
                        <p>
                            {l s='This position was not found in the product catalog. Choose how it should be handled.' mod='b2bpriceimport'}
                        </p>

                        <dl class="dl-horizontal b2b-import-item-summary">
                            <dt>{l s='Reference' mod='b2bpriceimport'}</dt>
                            <dd id="b2b-resolution-reference">&mdash;</dd>
                            <dt>{l s='Product name' mod='b2bpriceimport'}</dt>
                            <dd id="b2b-resolution-product-name">&mdash;</dd>
                        </dl>

                        <input id="b2b-resolution-item-id" type="hidden" value="">
                        <input id="b2b-resolution-product-id" type="hidden" value="">

                        <div class="b2b-import-item-resolution-options">
                            <label class="b2b-import-item-resolution-option">
                                <input type="radio" name="resolution_mode" value="create" checked="checked">
                                <span>
                                    <strong>{l s='Create a new product' mod='b2bpriceimport'}</strong>
                                    <small>
                                        {l s='The reference, name and imported price will be copied. The product will be disabled until you review and enable it.' mod='b2bpriceimport'}
                                    </small>
                                </span>
                            </label>

                            <label class="b2b-import-item-resolution-option">
                                <input type="radio" name="resolution_mode" value="link">
                                <span>
                                    <strong>{l s='Link an existing product' mod='b2bpriceimport'}</strong>
                                    <small>
                                        {l s='This reference will use the selected product during future imports.' mod='b2bpriceimport'}
                                    </small>
                                </span>
                            </label>
                        </div>

                        <div id="b2b-resolution-product-search-wrap" class="form-group hidden">
                            <label for="b2b-resolution-product-search">
                                {l s='Find a product by name, reference or ID' mod='b2bpriceimport'}
                            </label>
                            <input id="b2b-resolution-product-search"
                                   type="search"
                                   class="form-control"
                                   autocomplete="off"
                                   placeholder="{l s='Enter at least 2 characters' mod='b2bpriceimport'}">
                            <div id="b2b-resolution-product-search-help" class="help-block">
                                {l s='Select exactly one product from the search results.' mod='b2bpriceimport'}
                            </div>
                            <div id="b2b-resolution-product-results" class="list-group b2b-resolution-product-results"></div>
                            <div id="b2b-resolution-selected-product" class="alert alert-info hidden"></div>
                        </div>

                        <div id="b2b-resolution-feedback" class="alert hidden" role="alert"></div>
                    </div>

                    <div class="modal-footer b2b-import-item-modal-footer">
                        <button id="b2b-resolution-skip"
                                type="button"
                                class="btn btn-link text-danger pull-left">
                            {l s='Do not import this position' mod='b2bpriceimport'}
                        </button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            {l s='Cancel' mod='b2bpriceimport'}
                        </button>
                        <button id="b2b-resolution-submit" type="submit" class="btn btn-primary">
                            {l s='Create disabled product' mod='b2bpriceimport'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('b2b-import-item-modal');
            var form = document.getElementById('b2b-import-item-resolution-form');
            var itemIdInput = document.getElementById('b2b-resolution-item-id');
            var productIdInput = document.getElementById('b2b-resolution-product-id');
            var referenceText = document.getElementById('b2b-resolution-reference');
            var productNameText = document.getElementById('b2b-resolution-product-name');
            var productSearchWrap = document.getElementById('b2b-resolution-product-search-wrap');
            var productSearch = document.getElementById('b2b-resolution-product-search');
            var productResults = document.getElementById('b2b-resolution-product-results');
            var selectedProduct = document.getElementById('b2b-resolution-selected-product');
            var feedback = document.getElementById('b2b-resolution-feedback');
            var submitButton = document.getElementById('b2b-resolution-submit');
            var skipButton = document.getElementById('b2b-resolution-skip');
            var searchTimer = null;
            var searchRequest = 0;

            if (window.jQuery && window.jQuery.fn.tooltip) {
                window.jQuery('.b2b-import-item-action[data-toggle="tooltip"]').each(function () {
                    var action = window.jQuery(this);
                    var bootstrapContainer = action.closest('.bootstrap');
                    var tooltipOptions = {};

                    if (bootstrapContainer.length) {
                        tooltipOptions.container = bootstrapContainer.get(0);
                    }

                    action.tooltip(tooltipOptions);
                });
            }

            function applyImportItemsSearch(input) {
                var url = input.getAttribute('data-base-url');
                var parameter = input.getAttribute('data-parameter');
                var value = input.value.trim();

                if (value !== '') {
                    url += '&' + encodeURIComponent(parameter) + '=' + encodeURIComponent(value);
                }

                window.location.href = url;
            }

            function getResolutionMode() {
                var checked = form.querySelector('input[name="resolution_mode"]:checked');

                return checked ? checked.value : 'create';
            }

            function setFeedback(message, type) {
                feedback.className = 'alert';
                feedback.classList.add(type === 'warning' ? 'alert-warning' : 'alert-danger');
                feedback.textContent = message;
            }

            function clearFeedback() {
                feedback.className = 'alert hidden';
                feedback.textContent = '';
            }

            function clearSelectedProduct() {
                productIdInput.value = '';
                selectedProduct.className = 'alert alert-info hidden';
                selectedProduct.textContent = '';
            }

            function updateResolutionMode() {
                var isLink = getResolutionMode() === 'link';
                productSearchWrap.classList.toggle('hidden', !isLink);
                submitButton.textContent = isLink
                    ? modal.getAttribute('data-link-label')
                    : modal.getAttribute('data-create-label');
                clearFeedback();

                if (isLink) {
                    window.setTimeout(function () {
                        productSearch.focus();
                    }, 100);
                }
            }

            function openResolutionModal(button) {
                form.reset();
                clearFeedback();
                clearSelectedProduct();
                productSearch.value = '';
                productResults.innerHTML = '';
                itemIdInput.value = button.getAttribute('data-id-import-item');
                referenceText.textContent = button.getAttribute('data-reference') || '—';
                productNameText.textContent = button.getAttribute('data-product-name') || '—';
                updateResolutionMode();

                if (window.jQuery && window.jQuery.fn.modal) {
                    window.jQuery(modal).modal('show');
                }
            }

            function appendProductResult(product) {
                var button = document.createElement('button');
                var title = document.createElement('strong');
                var details = document.createElement('small');
                var reference = product.reference || '—';

                button.type = 'button';
                button.className = 'list-group-item';
                title.textContent = product.name || reference;
                details.textContent = 'ID ' + product.id_product + ' · ' + reference
                    + (product.active ? ' · active' : ' · disabled');
                button.appendChild(title);
                button.appendChild(details);
                button.addEventListener('click', function () {
                    productIdInput.value = String(product.id_product);
                    selectedProduct.textContent = (product.name || reference)
                        + ' (ID ' + product.id_product + ', ' + reference + ')';
                    selectedProduct.className = 'alert alert-info';
                    productResults.innerHTML = '';
                    clearFeedback();
                });
                productResults.appendChild(button);
            }

            function searchProducts(query) {
                var requestId = ++searchRequest;
                var url = modal.getAttribute('data-ajax-url')
                    + '&ajax=1&action=SearchImportProducts&query=' + encodeURIComponent(query);

                productResults.innerHTML = '<div class="list-group-item">Searching…</div>';

                fetch(url, { credentials: 'same-origin' })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (response) {
                        if (requestId !== searchRequest) {
                            return;
                        }

                        productResults.innerHTML = '';

                        if (!response.success) {
                            setFeedback(response.message || 'Product search failed.', 'error');
                            return;
                        }

                        if (!response.products.length) {
                            productResults.innerHTML = '<div class="list-group-item">No products found.</div>';
                            return;
                        }

                        response.products.forEach(appendProductResult);
                    })
                    .catch(function () {
                        if (requestId === searchRequest) {
                            productResults.innerHTML = '';
                            setFeedback('Product search failed.', 'error');
                        }
                    });
            }

            function resolveImportItem(action) {
                if (action === 'link' && !productIdInput.value) {
                    setFeedback('Select a product from the search results.', 'error');
                    return;
                }

                clearFeedback();
                submitButton.disabled = true;
                skipButton.disabled = true;

                var data = new FormData();
                data.append('id_import', modal.getAttribute('data-id-import'));
                data.append('id_import_item', itemIdInput.value);
                data.append('resolution_action', action);
                data.append('id_product', productIdInput.value);

                fetch(modal.getAttribute('data-ajax-url') + '&ajax=1&action=ResolveImportItem', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (response) {
                        if (!response.success) {
                            throw new Error(response.message || 'The import position could not be processed.');
                        }

                        if (response.processing_warning) {
                            setFeedback(response.message + ' ' + response.processing_warning, 'warning');
                        }

                        window.setTimeout(function () {
                            window.location.reload();
                        }, response.processing_warning ? 1400 : 250);
                    })
                    .catch(function (error) {
                        setFeedback(error.message || 'The import position could not be processed.', 'error');
                        submitButton.disabled = false;
                        skipButton.disabled = false;
                    });
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

            Array.prototype.forEach.call(
                document.querySelectorAll('.js-resolve-import-item'),
                function (button) {
                    button.addEventListener('click', function () {
                        openResolutionModal(this);
                    });
                }
            );

            Array.prototype.forEach.call(
                form.querySelectorAll('input[name="resolution_mode"]'),
                function (radio) {
                    radio.addEventListener('change', updateResolutionMode);
                }
            );

            productSearch.addEventListener('input', function () {
                var query = this.value.trim();
                window.clearTimeout(searchTimer);
                clearSelectedProduct();
                productResults.innerHTML = '';

                if (query.length < 2) {
                    return;
                }

                searchTimer = window.setTimeout(function () {
                    searchProducts(query);
                }, 250);
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                resolveImportItem(getResolutionMode());
            });

            skipButton.addEventListener('click', function () {
                resolveImportItem('skip');
            });
        })();
    </script>
{/if}

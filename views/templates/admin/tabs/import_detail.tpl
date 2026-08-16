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

        {if empty($importItems)}
            <div class="alert alert-warning">
                {l s='No imported rows found yet. Run the import parser first.' mod='b2bpriceimport'}
            </div>
        {else}
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='Row' mod='b2bpriceimport'}</th>
                        <th>{l s='Reference' mod='b2bpriceimport'}</th>
                        <th>{l s='Product name' mod='b2bpriceimport'}</th>
                        <th>{l s='Product ID' mod='b2bpriceimport'}</th>
                        <th>{l s='Source price' mod='b2bpriceimport'}</th>
                        <th>{l s='Currency' mod='b2bpriceimport'}</th>
                        <th>{l s='Rate' mod='b2bpriceimport'}</th>
                        <th>{l s='UAH price' mod='b2bpriceimport'}</th>
                        <th>{l s='Active' mod='b2bpriceimport'}</th>
                        <th>{l s='Validation' mod='b2bpriceimport'}</th>
                        <th>{l s='Processing' mod='b2bpriceimport'}</th>
                        <th aria-sort="{if $importItemsPagination.status_order == 'asc'}ascending{else}descending{/if}">
                            <a href="{$importItemsPagination.status_sort_url|escape:'html':'UTF-8'}"
                               title="{if $importItemsPagination.status_order == 'asc'}{l s='Sort status descending' mod='b2bpriceimport'}{else}{l s='Sort status ascending' mod='b2bpriceimport'}{/if}">
                                {l s='Item status' mod='b2bpriceimport'}
                                <i class="{if $importItemsPagination.status_order == 'asc'}icon-sort-up{else}icon-sort-down{/if}"></i>
                            </a>
                        </th>
                        <th>{l s='Error' mod='b2bpriceimport'}</th>
                    </tr>
                </thead>
                <tbody>
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
{/if}

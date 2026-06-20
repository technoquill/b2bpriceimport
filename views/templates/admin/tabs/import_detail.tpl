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
                        <th>{l s='Product ID' mod='b2bpriceimport'}</th>
                        <th>{l s='Source price' mod='b2bpriceimport'}</th>
                        <th>{l s='Currency' mod='b2bpriceimport'}</th>
                        <th>{l s='Rate' mod='b2bpriceimport'}</th>
                        <th>{l s='UAH price' mod='b2bpriceimport'}</th>
                        <th>{l s='Active' mod='b2bpriceimport'}</th>
                        <th>{l s='Validation' mod='b2bpriceimport'}</th>
                        <th>{l s='Processing' mod='b2bpriceimport'}</th>
                        <th>{l s='Item status' mod='b2bpriceimport'}</th>
                        <th>{l s='Error' mod='b2bpriceimport'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$importItems item=item}
                        <tr class="{if $item.status == 'failed' || $item.validation_status == 'failed' || $item.processing_status == 'failed'}danger{elseif $item.status == 'processed' || $item.processing_status == 'processed'}success{/if}">
                            <td>{$item.row_number|intval}</td>
                            <td>{$item.reference|escape:'html':'UTF-8'}</td>
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
        {/if}
    </div>
{/if}

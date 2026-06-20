<div class="panel">
    <h3>
        <i class="icon-upload"></i>
        {l s='Import CSV' mod='b2bpriceimport'}
    </h3>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label class="control-label">
                {l s='CSV file' mod='b2bpriceimport'}
            </label>

            <input type="file" name="b2b_import_file" accept=".csv" class="form-control">
        </div>

        <button type="submit" name="submit_b2b_import_upload" class="btn btn-primary">
            <i class="icon-upload"></i>
            {l s='Create import' mod='b2bpriceimport'}
        </button>
    </form>
</div>

{if isset($imports) && $imports|count}
    <div class="panel">
        <h3>
            <i class="icon-list"></i>
            {l s='Recent imports' mod='b2bpriceimport'}
        </h3>

        <table class="table">
            <thead>
            <tr>
                <th>{l s='ID' mod='b2bpriceimport'}</th>
                <th>{l s='File' mod='b2bpriceimport'}</th>
                <th>{l s='Status' mod='b2bpriceimport'}</th>
                <th>{l s='Rows' mod='b2bpriceimport'}</th>
                <th>{l s='Created' mod='b2bpriceimport'}</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$imports item=import}
                <tr>
                    <td>{$import.id_b2b_import|intval}</td>
                    <td>{$import.original_filename|escape:'html':'UTF-8'}</td>
                    <td>{$import.status|escape:'html':'UTF-8'}</td>
                    <td>
                        {$import.processed_rows|intval} / {$import.total_rows|intval}
                    </td>
                    <td>{$import.date_add|escape:'html':'UTF-8'}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
{/if}
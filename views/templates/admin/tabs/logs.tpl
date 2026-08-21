<div class="panel b2b-logs-panel">
    <h3>
        <i class="icon-list"></i>
        {l s='Logs' mod='b2bpriceimport'}
    </h3>

    {if !empty($logsError)}
        <div class="alert alert-danger">
            {$logsError|escape:'html':'UTF-8'}
        </div>
    {/if}

    <form method="get"
          action="{$logsFormAction|escape:'html':'UTF-8'}"
          class="b2b-logs-filters">
        {foreach from=$logsFormHiddenFields item=hiddenField}
            <input type="hidden"
                   name="{$hiddenField.name|escape:'html':'UTF-8'}"
                   value="{$hiddenField.value|escape:'html':'UTF-8'}">
        {/foreach}
        <input type="hidden" name="section" value="logs">
        <input type="hidden" name="logs_per_page" value="{$logsPagination.page_size|intval}">

        <div class="row">
            <div class="form-group col-lg-2 col-md-3 col-sm-6">
                <label for="logs-date-from">{l s='From' mod='b2bpriceimport'}</label>
                <input id="logs-date-from"
                       type="date"
                       name="logs_date_from"
                       class="form-control"
                       value="{$logFilters.date_from|escape:'html':'UTF-8'}">
            </div>
            <div class="form-group col-lg-2 col-md-3 col-sm-6">
                <label for="logs-date-to">{l s='To' mod='b2bpriceimport'}</label>
                <input id="logs-date-to"
                       type="date"
                       name="logs_date_to"
                       class="form-control"
                       value="{$logFilters.date_to|escape:'html':'UTF-8'}">
            </div>
            <div class="form-group col-lg-2 col-md-3 col-sm-6">
                <label for="logs-entity-type">{l s='Entity' mod='b2bpriceimport'}</label>
                <select id="logs-entity-type" name="logs_entity_type" class="form-control">
                    <option value="">{l s='All' mod='b2bpriceimport'}</option>
                    {foreach from=$logEntityTypes item=entityType}
                        <option value="{$entityType|escape:'html':'UTF-8'}"
                                {if $entityType == $logFilters.entity_type}selected="selected"{/if}>
                            {$entityType|escape:'html':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
            <div class="form-group col-lg-2 col-md-3 col-sm-6">
                <label for="logs-result">{l s='Result' mod='b2bpriceimport'}</label>
                <select id="logs-result" name="logs_result" class="form-control">
                    <option value="">{l s='All' mod='b2bpriceimport'}</option>
                    {foreach from=$logResults item=logResult}
                        <option value="{$logResult|escape:'html':'UTF-8'}"
                                {if $logResult == $logFilters.result}selected="selected"{/if}>
                            {$logResult|escape:'html':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
            <div class="form-group col-lg-2 col-md-3 col-sm-6">
                <label for="logs-channel">{l s='Channel' mod='b2bpriceimport'}</label>
                <select id="logs-channel" name="logs_channel" class="form-control">
                    <option value="">{l s='All' mod='b2bpriceimport'}</option>
                    {foreach from=$logChannels item=logChannel}
                        <option value="{$logChannel|escape:'html':'UTF-8'}"
                                {if $logChannel == $logFilters.channel}selected="selected"{/if}>
                            {$logChannel|escape:'html':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
            <div class="form-group col-lg-2 col-md-3 col-sm-6">
                <label for="logs-action">{l s='Action' mod='b2bpriceimport'}</label>
                <select id="logs-action" name="logs_action" class="form-control">
                    <option value="">{l s='All' mod='b2bpriceimport'}</option>
                    {foreach from=$logActions item=logAction}
                        <option value="{$logAction|escape:'html':'UTF-8'}"
                                {if $logAction == $logFilters.action}selected="selected"{/if}>
                            {$logAction|escape:'html':'UTF-8'}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-lg-3 col-md-4">
                <label for="logs-actor">{l s='Actor' mod='b2bpriceimport'}</label>
                <input id="logs-actor"
                       type="search"
                       name="logs_actor"
                       class="form-control"
                       value="{$logFilters.actor|escape:'html':'UTF-8'}"
                       placeholder="{l s='Employee, API, CLI...' mod='b2bpriceimport'}">
            </div>
            <div class="form-group col-lg-5 col-md-5">
                <label for="logs-search">{l s='Search' mod='b2bpriceimport'}</label>
                <input id="logs-search"
                       type="search"
                       name="logs_search"
                       class="form-control"
                       value="{$logFilters.search|escape:'html':'UTF-8'}"
                       placeholder="{l s='Action, entity ID, message or context' mod='b2bpriceimport'}">
            </div>
            <div class="form-group col-lg-4 col-md-3 b2b-logs-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="icon-filter"></i>
                    {l s='Apply filters' mod='b2bpriceimport'}
                </button>
                <a href="{$logsResetUrl|escape:'html':'UTF-8'}" class="btn btn-default">
                    <i class="icon-eraser"></i>
                    {l s='Reset' mod='b2bpriceimport'}
                </a>
            </div>
        </div>
    </form>

    {if empty($logs) && empty($logsError)}
        <div class="alert alert-info">
            {l s='No log entries match the selected filters.' mod='b2bpriceimport'}
        </div>
    {elseif !empty($logs)}
        <div class="table-responsive b2b-logs-table-wrapper">
            <table class="table table-striped table-hover b2b-logs-table">
                <thead>
                <tr>
                    <th>{l s='Date' mod='b2bpriceimport'}</th>
                    <th>{l s='Actor' mod='b2bpriceimport'}</th>
                    <th>{l s='Channel' mod='b2bpriceimport'}</th>
                    <th>{l s='Action' mod='b2bpriceimport'}</th>
                    <th>{l s='Entity' mod='b2bpriceimport'}</th>
                    <th>{l s='Result' mod='b2bpriceimport'}</th>
                    <th>{l s='Message' mod='b2bpriceimport'}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$logs item=log}
                    <tr>
                        <td class="text-nowrap">{$log.date_add|escape:'html':'UTF-8'}</td>
                        <td>{$log.actor_display|escape:'html':'UTF-8'}</td>
                        <td><code>{$log.channel|escape:'html':'UTF-8'}</code></td>
                        <td><code>{$log.action|escape:'html':'UTF-8'}</code></td>
                        <td>
                            {$log.entity_type|escape:'html':'UTF-8'}
                            {if !empty($log.entity_id)}
                                <br><small class="text-muted">{$log.entity_id|escape:'html':'UTF-8'}</small>
                            {/if}
                        </td>
                        <td>
                            <span class="label {$log.result_class|escape:'html':'UTF-8'}">
                                {$log.result|escape:'html':'UTF-8'}
                            </span>
                        </td>
                        <td class="b2b-logs-message">{$log.message|escape:'html':'UTF-8'}</td>
                        <td class="text-right">
                            {if !empty($log.has_details)}
                                <button type="button"
                                        class="btn btn-default btn-sm js-b2b-log-details"
                                        data-target="#b2b-log-details-{$log.id_b2b_audit_log|intval}"
                                        aria-expanded="false">
                                    <i class="icon-search"></i>
                                    {l s='Details' mod='b2bpriceimport'}
                                </button>
                            {/if}
                        </td>
                    </tr>
                    {if !empty($log.has_details)}
                        <tr id="b2b-log-details-{$log.id_b2b_audit_log|intval}"
                            class="hidden b2b-log-details-row">
                            <td colspan="8">
                                <div class="row">
                                    {if !empty($log.before_display)}
                                        <div class="col-lg-4 col-md-6">
                                            <h4>{l s='Before' mod='b2bpriceimport'}</h4>
                                            <pre>{$log.before_display|escape:'html':'UTF-8'}</pre>
                                        </div>
                                    {/if}
                                    {if !empty($log.after_display)}
                                        <div class="col-lg-4 col-md-6">
                                            <h4>{l s='After' mod='b2bpriceimport'}</h4>
                                            <pre>{$log.after_display|escape:'html':'UTF-8'}</pre>
                                        </div>
                                    {/if}
                                    {if !empty($log.context_display)}
                                        <div class="col-lg-4 col-md-12">
                                            <h4>{l s='Context' mod='b2bpriceimport'}</h4>
                                            <pre>{$log.context_display|escape:'html':'UTF-8'}</pre>
                                        </div>
                                    {/if}
                                </div>
                            </td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>
        </div>

        <div class="panel-footer b2b-logs-footer">
            <div class="b2b-logs-summary">
                <span>
                    {l s='Showing' mod='b2bpriceimport'}
                    {$logsPagination.first_item|intval}-{$logsPagination.last_item|intval}
                    {l s='of' mod='b2bpriceimport'}
                    {$logsPagination.total_items|intval}
                </span>
                <label for="logs-page-size">{l s='Rows per page' mod='b2bpriceimport'}</label>
                <select id="logs-page-size"
                        class="form-control input-sm b2b-logs-page-size"
                        onchange="window.location.href = this.value;">
                    {foreach from=$logsPagination.page_size_options item=pageSizeOption}
                        <option value="{$pageSizeOption.url|escape:'html':'UTF-8'}"
                                {if $pageSizeOption.is_current}selected="selected"{/if}>
                            {$pageSizeOption.value|intval}
                        </option>
                    {/foreach}
                </select>
            </div>

            {if $logsPagination.total_pages > 1}
                <nav aria-label="{l s='Logs pagination' mod='b2bpriceimport'}">
                    <ul class="pagination pagination-sm">
                        <li{if empty($logsPagination.previous_url)} class="disabled"{/if}>
                            {if empty($logsPagination.previous_url)}
                                <span>&laquo;</span>
                            {else}
                                <a href="{$logsPagination.previous_url|escape:'html':'UTF-8'}">&laquo;</a>
                            {/if}
                        </li>
                        {foreach from=$logsPagination.pages item=page}
                            <li{if $page.is_current} class="active"{/if}>
                                {if $page.is_current}
                                    <span>{$page.number|intval}</span>
                                {else}
                                    <a href="{$page.url|escape:'html':'UTF-8'}">{$page.number|intval}</a>
                                {/if}
                            </li>
                        {/foreach}
                        <li{if empty($logsPagination.next_url)} class="disabled"{/if}>
                            {if empty($logsPagination.next_url)}
                                <span>&raquo;</span>
                            {else}
                                <a href="{$logsPagination.next_url|escape:'html':'UTF-8'}">&raquo;</a>
                            {/if}
                        </li>
                    </ul>
                </nav>
            {/if}
        </div>
    {/if}
</div>

<script>
    $(document).ready(function () {
        $('.js-b2b-log-details').on('click', function () {
            var $button = $(this);
            var $details = $($button.data('target'));
            var isExpanded = !$details.hasClass('hidden');

            $details.toggleClass('hidden', isExpanded);
            $button.attr('aria-expanded', isExpanded ? 'false' : 'true');
        });
    });
</script>

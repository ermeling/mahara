{include file="header.tpl"}

{include file="view/editviewtabs.tpl" issiteview=$issiteview}
<div class="view-instructions">
    <form action="{$formurl}" method="post" class="row">
        <input type="submit" name="{$action_name}" id="action-dummy" class="d-none">
        <input type="hidden" id="viewid" name="id" value="{$view}">
        <input type="hidden" name="change" value="1">
        <input type="hidden" id="category" name="c" value="{$category}">
        <input type="hidden" name="sesskey" value="{$SESSKEY}">

        <div class="col-with-collapse">
        {if $instructions}
            <div id="viewinstructions" class="last form-group collapsible-group small-group">
            <fieldset  class="pieform-fieldset collapsible collapsible-small">
                <legend>
                    <h4>
                        <a href="#viewinstructions-dropdown" data-toggle="collapse" aria-expanded="false" aria-controls="viewinstructions-dropdown" class="{if $instructionscollapsed}collapsed{/if}">
                            {str tag='instructions' section='view'}
                            <span class="icon icon-chevron-down collapse-indicator right text-inline"></span>
                        </a>
                    </h4>
                </legend>
                <div class="fieldset-body collapse viewinstructions {if !$instructionscollapsed} in {/if}" id="viewinstructions-dropdown">
                    {$instructions|clean_html|safe}
                </div>
            </fieldset>
            </div>
        {else}
            <div id="blocksinstruction" class="lead view-description with-addblock">
                {str tag='blocksintructionnoajax' section='view'}
            </div>
        {/if}
        </div>
        {include file="view/editviewpageactions.tpl" selected='content' ineditor=true}
    </form>
</div>
<div class="view-container" selected='content'>
    <form action="{$formurl}" method="post" class="row">
        <input type="submit" name="{$action_name}" id="action-dummy" class="d-none">
        <input type="hidden" id="viewid" name="id" value="{$view}">
        <input type="hidden" name="change" value="1">
        <input type="hidden" id="category" name="c" value="{$category}">
        <input type="hidden" name="sesskey" value="{$SESSKEY}">

        <div class="col">
            <div id="bottom-pane" data-role="workspace">
                <div id="column-container" class="user-page-content">
                    {$columns|safe}
                </div>
            </div>
        </div>
    </form>

    <div id="view-return-controls">
        {if !$issitetemplate}
        <a class="btn btn-secondary" href="{$displaylink}">
            {str tag=displayview section=view}
            <span class="icon icon-arrow-circle-right right" role="presentation" aria-hidden="true"></span>
        </a>
        {/if}
        {if !$groupid && !$institution}
        <a class="btn btn-secondary" href="{$WWWROOT}view/index.php">
            {str tag=returntoviews section=view}
            <span class="icon icon-arrow-circle-right right" role="presentation" aria-hidden="true"></span>
        </a>
        {/if}
        {if $groupid}
        <a class="btn btn-secondary" href="{$WWWROOT}view/groupviews.php?group={$groupid}">
            {str tag=returntogroupportfolios section=group}
            <span class="icon icon-arrow-circle-right right" role="presentation" aria-hidden="true"></span>
        </a>
        {/if}
        {if $institution}
            {if $institution == 'mahara'}
                <a class="btn btn-secondary" href="{$WWWROOT}admin/site/views.php">
                {str tag=returntositeportfolios section=view}
            {else}
                <a class="btn btn-secondary" href="{$WWWROOT}view/institutionviews.php?institution={$institution}">
                {str tag=returntoinstitutionportfolios section=view}
            {/if}
            <span class="icon icon-arrow-circle-right right" role="presentation" aria-hidden="true"></span>
        </a>
        {/if}
    </div>
</div>

<div class="modal" id="addblock" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content" data-height=".modal-body">
            <div class="modal-header">
                <button class="deletebutton close" name="action_removeblockinstance_id_{$id}">
                    <span class="times">&times;</span>
                    <span class="sr-only">{str tag=Close}</span>
                </button>
                <h4 class="modal-title blockinstance-header text-inline" id="addblock-heading"></h4>
            </div>
            <div class="modal-body blockinstance-content">
                <div class="block-inner">{$addform|safe}</div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-shown modal-docked-right modal-docked closed blockinstance configure" id="configureblock" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" data-height=".modal-body">
            <div class="modal-header">
                <button class="deletebutton close" name="close_configuration">
                    <span class="times">&times;</span>
                    <span class="sr-only">{str tag=closeconfiguration section=view}</span>
                </button>
                <h4 class="modal-title blockinstance-header text-inline"></h4>
                <span class="icon icon-cogs icon-2x float-right" role="presentation" aria-hidden="true"></span>
            </div>
            <div class="modal-body blockinstance-content">
            </div>
        </div>
    </div>
</div>

{include file="footer.tpl"}

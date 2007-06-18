<form name="packages" action={concat("/harmonia/list/",$selected_repository|urlencode)|ezurl} method="post">

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h1 class="context-title">{'Remote package repositories'|i18n('design')}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<div class="context-attributes">

<div class="block">
<label>Repository:</label>
<select name="RepositoryID">
{foreach $repositories as $identifier => $url}
<option value="{$identifier|wash}" {if $selected_repository|eq($identifier)}selected="selected"{/if}>{$identifier} [{$url|wash}]</option>
{/foreach}
</select>
</div>

</div>
{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
  <input class="button" type="submit" name="SwitchButton" value="{'Switch repository'|i18n('design')}" />
</div>

{* DESIGN: Control bar END *}</div></div></div></div></div></div>

</div>{* class="controlbar" *}

</div>{* class="context-block" *}


{if is_array( $list )}

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h2 class="context-title">{'Packages in %name [%package_count]'|i18n( 'extension/harmonia', '', hash('%name', $selected_repository, '%package_count', $list|count))}</h2>

{* DESIGN: Subline *}<div class="header-subline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

{* View mode selector. *}
<div class="context-toolbar">
<div class="block">

<div class="right">
        <p>
        {switch match=ezpreference( 'harmonia_list_viewmode' )}
        {case match='thumbnail'}
        <a href={'/user/preferences/set/harmonia_list_viewmode/list'|ezurl} title="{'Display packages using a simple list.'|i18n( 'design/admin/node/view/full' )}">{'List'|i18n( 'design/admin/node/view/full' )}</a>
        <span class="current">{'Thumbnail'|i18n( 'design/admin/node/view/full' )}</span>
        {*<a href={'/user/preferences/set/harmonia_list_viewmode/detailed'|ezurl} title="{'Display packages using a detailed list.'|i18n( 'design/admin/node/view/full' )}">{'Detailed'|i18n( 'design/admin/node/view/full' )}</a>*}
        {/case}

        {*
        {case match='detailed'}
        <a href={'/user/preferences/set/harmonia_list_viewmode/list'|ezurl} title="{'Display packages using a simple list.'|i18n( 'design/admin/node/view/full' )}">{'List'|i18n( 'design/admin/node/view/full' )}</a>
        <a href={'/user/preferences/set/harmonia_list_viewmode/thumbnail'|ezurl} title="{'Display packages as thumbnails.'|i18n( 'design/admin/node/view/full' )}">{'Thumbnail'|i18n( 'design/admin/node/view/full' )}</a>
        <span class="current">{'Detailed'|i18n( 'design/admin/node/view/full' )}</span>
        {/case}
        *}

        {case}
        <span class="current">{'List'|i18n( 'design/admin/node/view/full' )}</span>
        <a href={'/user/preferences/set/harmonia_list_viewmode/thumbnail'|ezurl} title="{'Display packages as thumbnails.'|i18n( 'design/admin/node/view/full' )}">{'Thumbnail'|i18n( 'design/admin/node/view/full' )}</a>
        {*<a href={'/user/preferences/set/harmonia_list_viewmode/detailed'|ezurl} title="{'Display packages using a detailed list.'|i18n( 'design/admin/node/view/full' )}">{'Detailed'|i18n( 'design/admin/node/view/full' )}</a>*}
        {/case}
        {/switch}
        </p>
</div>

<div class="break"></div>

</div>
</div>

{switch match=ezpreference( 'harmonia_list_viewmode' )}

{case match="thumbnail"}

<table class="list-thumbnails remote_packages" cellspacing="0">
    <tr>
    {foreach $list as $package sequence array('bglight','bgdark') as $sequence}
    <td width="25%" class="{switch match=$package.status}{case match=1}version-lower{/case}{case match=2}version-current{/case}{case match=3}version-higher{/case}{/switch}">
        <div class="content-view-thumbnail">
        <img src="{$package.thumbnail_url}" />
        </div>
        <p><input type="checkbox" name="SelectPackage" value="{$package.name|wash}" /> {$package.name|wash} ( {$package.version|wash} )</p>
    </td>
    {delimiter modulo=4}
    </tr><tr>
    {/delimiter}
    {/foreach}
</tr>
</table>
{/case}

{case}

<table class="list remote_packages" cellspacing="0">
<tr>
    <th class="tight"><img src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/admin/section/list' )}" title="{'Invert selection.'|i18n( 'design/admin/section/list' )}" onclick="ezjs_toggleCheckboxes( document.packages, 'PackageNameArray[]' ); return false;" /></th>
    <th>Name</th>
    <th>Version</th>
    <th>Local version</th>
    <th>Type</th>
    <th>Summary</th>
</tr>
{foreach $list as $package sequence array('bglight','bgdark') as $sequence}
<tr class="{$sequence}">
    <td><input type="checkbox" name="PackageNameArray[]" value="{$package.name|wash}" /></td>
    <td>{$package.name|wash}</td>
    <td class="{switch match=$package.status}{case match=1}version-lower{/case}{case match=2}version-current{/case}{case match=3}version-higher{/case}{/switch}">{*{$package.status}&nbsp;&nbsp;&nbsp;*}{$package.version|wash}</td>
    <td>{if $package.local}<a href={concat("/package/view/full/",$package.name|wash)|ezurl}>{$package.local.version-number}-{$package.local.release-number}</a>{/if}</td>
    <td>{$package.type|wash}</td>
    <td>{$package.summary|wash}</td>
</tr>
{/foreach}
</table>

{/case}

{/switch}

{* DESIGN: Content END *}</div></div></div>

<div class="controlbar">

{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">

<div class="block">
<div class="button-left">
<input class="button" type="submit" value="Import selected packages" name="ImportButton" />
</div>

<div class="break"></div>
</div>

{* DESIGN: Control bar END *}</div></div></div></div></div></div>

</div>{* class="controlbar" *}

</div>

{/if}

</form>
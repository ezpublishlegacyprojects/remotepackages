<form name="sections" method="post" action={'/section/list/'|ezurl}>

<div class="context-block">
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h1 class="context-title">{'Remote package repositories [%repository_count]'|i18n( 'design/admin/section/list',, hash( '%repository_count', $repositories|count ) )}</h1>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<table class="list" cellspacing="0">
<tr>
    <th>{'Identifier'|i18n('design/admin/section/list')}</th>
    <th>{'URL'|i18n('design/admin/section/list')}</th>
</tr>

{foreach $repositories as $identifier => $url sequence array( 'bglight', 'bgdark' ) as $sequence}
<tr class="{$sequence}">
    <td><a href={concat("/remotepackages/list/", $identifier|urlencode)|ezurl}>{$identifier|wash}</a></td>
    <td>{$url|wash}</td>
</tr>
{/foreach}

</table>

{* DESIGN: Content END *}</div></div></div>

{* Buttons. *}
<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
{*<input class="button" type="submit" name="RemoveSectionButton" value="{'Remove selected'|i18n( 'design/admin/section/list' )}" title="{'Remove selected sections.'|i18n( 'design/admin/section/list' )}" />
<input class="button" type="submit" name="CreateSectionButton" value="{'New section'|i18n( 'design/admin/section/list' )}" title="{'Create a new section.'|i18n( 'design/admin/section/list' )}" />
*}
</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

</form>
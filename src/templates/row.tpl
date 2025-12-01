<tr{if $row.empty == 1} class="empty"{/if}>
{if $row.empty == 0}
  <th>
    {$row.prefix}
    <emph></emph>
  </th>
{foreach from=$row.cells item=cell}  {include file='cell.tpl'}
{/foreach}{/if}{if $row.empty == 1}
  <th colspan="17">&middot; &middot; &middot;</th>
{/if}</tr>

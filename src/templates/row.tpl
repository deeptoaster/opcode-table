<tr>
  <th>{$row.prefix}</th>
{foreach from=$row.cells item=cell}  {include file='cell.tpl'}
{/foreach}</tr>

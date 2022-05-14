<tr>
  <th>{$row.index}</th>
{foreach from=$row.elements item=cell}  {include file='cell.tpl'}
{/foreach}</tr>

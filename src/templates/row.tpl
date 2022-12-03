<tr>
  <th>
    {$row.prefix}
    <emph></emph>
  </th>
{foreach from=$row.cells item=cell}  {include file='cell.tpl'}
{/foreach}</tr>

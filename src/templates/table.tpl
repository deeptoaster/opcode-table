<h1>{$table.prefix} Instructions</h1>
<table>
  <tr>
    <td>
    <th>0</th>
    <th>1</th>
    <th>2</th>
    <th>3</th>
    <th>4</th>
    <th>5</th>
    <th>6</th>
    <th>7</th>
    <th>8</th>
    <th>9</th>
    <th>A</th>
    <th>B</th>
    <th>C</th>
    <th>D</th>
    <th>E</th>
    <th>F</th>
  </tr>
{foreach from=$table.rows item=row}  {include file='row.tpl'}
{/foreach}</table>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Z80 Opcode Table</title>
    <link href="opcode_table.css" type="text/css" rel="stylesheet" />
  </head>
  <body>
    <table>
      <tr>
        <td>
        <th>0</th>
      </tr>
{foreach from=$rows item=row}      {include file='row.tpl'}
{/foreach}    </table>
  </body>
</html>

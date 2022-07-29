<td{if $cell}{if $cell.class} class="{$cell.class}"{/if}{/if}>{if $cell}
  {$cell.mnemonic}
  <dl>
    <dt>Opcode</dt>
    <dd>{$cell.bytes}</dd>
    <dt>Bytes</dt>
    <dd>{$cell.space}</dd>
    <dt>Cycles</dt>
    <dd>{$cell.cycles}</dd>
    <dd>{$cell.description}</dd>
  </dl>
{/if}</td>

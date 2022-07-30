<td{if $cell}{if $cell.class} class="{$cell.class}"{/if}{/if}>{if $cell}
  {$cell.mnemonic}
  <dl>
    <dt>Opcode</dt>
    <dd>{foreach from=$cell.bytes item=byte}{$byte}{/foreach}</dd>
    <dt>Bytes</dt>
    <dd>{$cell.space}</dd>
    <dt>Cycles</dt>
    <dd>{$cell.cycles}</dd>
    <dt>C</dt>
    <dd>{$cell.flags.c}</dd>
    <dt>N</dt>
    <dd>{$cell.flags.n}</dd>
    <dt>P/V</dt>
    <dd>{$cell.flags.pv}</dd>
    <dt>H</dt>
    <dd>{$cell.flags.h}</dd>
    <dt>Z</dt>
    <dd>{$cell.flags.z}</dd>
    <dt>S</dt>
    <dd>{$cell.flags.s}</dd>
    <dd>{$cell.description}</dd>
  </dl>
{/if}</td>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Z80 Opcode Table</title>
    <link href="opcode_table.css" type="text/css" rel="stylesheet" />
  </head>
  <body>
    <table>
      <tr>
        <td></td>
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
      <tr>
        <th>0</th>
        <td>nop</td>
        <td>ld bc,**</td>
        <td>ld (bc),a</td>
        <td>inc bc</td>
        <td>inc b</td>
        <td>dec b</td>
        <td>ld b,*</td>
        <td>rlca</td>
        <td>ex af,af'</td>
        <td>add hl,bc</td>
        <td>ld a,(bc)</td>
        <td>dec bc</td>
        <td>inc c</td>
        <td>dec c</td>
        <td>ld c,*</td>
        <td>rrca</td>
      </tr>
      <tr>
        <th>1</th>
        <td>djnz *</td>
        <td>ld de,**</td>
        <td>ld (de),a</td>
        <td>inc de</td>
        <td>inc d</td>
        <td>dec d</td>
        <td>ld d,*</td>
        <td>rla</td>
        <td>
          jr *
          <span>
            The signed value * is added to pc. The jump is measured from the
            start of the instruction opcode.
          </span>
        </td>
        <td>add hl,de</td>
        <td>ld a,(de)</td>
        <td>dec de</td>
        <td>inc e</td>
        <td>dec e</td>
        <td>ld e,*</td>
        <td>rra</td>
      </tr>
      <tr>
        <th>2</th>
        <td>jr nz,*</td>
        <td>ld hl,**</td>
        <td>ld (**),hl</td>
        <td>inc hl</td>
        <td>inc h</td>
        <td>dec h</td>
        <td>ld h,*</td>
        <td>daa</td>
        <td>jr z,*</td>
        <td>add hl,hl</td>
        <td>ld hl,(**)</td>
        <td>dec hl</td>
        <td>inc l</td>
        <td>dec l</td>
        <td>ld l,*</td>
        <td>cpl</td>
      </tr>
      <tr>
        <th>3</th>
        <td>jr nc,*</td>
        <td>ld sp,**</td>
        <td>ld (**),a</td>
        <td>inc sp</td>
        <td>inc (hl)</td>
        <td>dec (hl)</td>
        <td>ld (hl),*</td>
        <td>scf</td>
        <td>jr c,*</td>
        <td>add hl,sp</td>
        <td>ld a,(**)</td>
        <td>dec sp</td>
        <td>inc a</td>
        <td>dec a</td>
        <td>ld a,*</td>
        <td>ccf</td>
      </tr>
    </table>
  </body>
</html>

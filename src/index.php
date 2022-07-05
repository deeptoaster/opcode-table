<?php
namespace ClrHome;

include(__DIR__ . '/../lib/tools/SimpleNumber.class.php');
include(__DIR__ . '/../lib/cleverly/Cleverly.class.php');

class Opcode {
  public string $bytes = '';
  public string $description = '';
  public string $mnemonic = '';
}

class OpcodeRow {
  public array $cells;
  public string $prefix;
}

class OpcodeTable {
  public string $prefix;
  public array $rows;

  final public static function fromFile(string $file_name): array {
    $tables = [];
    $json_opcodes = json_decode(file_get_contents($file_name));

    foreach ($json_opcodes as $json_opcode) {
      $opcodes = self::listOpcodesFromShorthand(
        $json_opcode->bytes,
        0,
        '',
        $json_opcode->mnemonic
      );

      foreach ($opcodes as $opcode) {
        $bytes = preg_grep('/^[\dA-F][\dA-F]$/', explode(' ', $opcode->bytes));
        $final_byte = array_pop($bytes);
        $table_prefix = implode('', $bytes);
        list($row_index, $column_index) = str_split($final_byte);

        if (!array_key_exists($table_prefix, $tables)) {
          $table = new OpcodeTable();
          $table->prefix = $table_prefix;
          $table->rows = [];
          $tables[$table_prefix] = &$table;
        } else {
          $table = &$tables[$table_prefix];
        }

        if (!array_key_exists($row_index, $table->rows)) {
          $row = new OpcodeRow();
          $row->cells = [];
          $row->prefix = $row_index;
          $table->rows[$row_index] = &$row;

          for ($index = 0; $index < 16; $index++) {
            $row->cells[strtoupper(dechex($index))] = new Opcode();
          }
        } else {
          $row = &$table->rows[$row_index];
        }

        $row->cells[$column_index] = $opcode;

        unset($table);
        unset($row);
      }
    }

    foreach ($tables as &$table) {
      foreach ($table->rows as &$row) {
        ksort($row->cells, SORT_STRING);
      }

      ksort($table->rows, SORT_STRING);
    }

    ksort($tables, SORT_STRING);
    return $tables;
  }

  private static function listOpcodesFromShorthand(
    array $bytes,
    int $byte_index,
    string $description,
    string $mnemonic
  ): array {
    if ($byte_index === count($bytes)) {
      $opcode = new Opcode();
      $opcode->bytes = implode(' ', $bytes);
      $opcode->description = $description;
      $opcode->mnemonic =
          strtolower(preg_replace('/[a-z]/', '<var>$0</var>', $mnemonic));
      return [$opcode];
    } else if (strpos($bytes[$byte_index], 'b') !== false) {
      return array_merge(...array_map(function(int $bit) use (
        $byte_index,
        $bytes,
        $description,
        $mnemonic
      ): array {
        $bytes[$byte_index] = str_pad(strtoupper(dechex(SimpleNumber::from(
          $bytes[$byte_index],
          ['b' => $bit]
        )->real)), 2, '0', STR_PAD_LEFT);

        return self::listOpcodesFromShorthand(
          $bytes,
          $byte_index + 1,
          $description,
          str_replace('b', $bit, $mnemonic)
        );
      }, range(0, 7)));
    } else if (strpos($bytes[$byte_index], 'p') !== false) {
      return array_merge(...array_map(function(int $byte) use (
        $byte_index,
        $bytes,
        $description,
        $mnemonic
      ): array {
        $bytes[$byte_index] = str_pad(strtoupper(dechex(SimpleNumber::from(
          $bytes[$byte_index],
          ['p' => $byte]
        )->real)), 2, '0', STR_PAD_LEFT);

        return self::listOpcodesFromShorthand(
          $bytes,
          $byte_index + 1,
          $description,
          str_replace('p', $byte, $mnemonic)
        );
      }, range(0x00, 0x38, 0x08)));
    } else {
      return self::listOpcodesFromShorthand(
        $bytes,
        $byte_index + 1,
        $description,
        $mnemonic
      );
    }
  }
}

$cleverly = new \Cleverly();
$cleverly->preserveIndent = true;
$cleverly->setTemplateDir(__DIR__ . '/templates');

$cleverly->display('index.tpl', [
  'tables' => OpcodeTable::fromFile(__DIR__ . '/opcode_table.json')
]);
?>

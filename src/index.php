<?php
namespace ClrHome;

include(__DIR__ . '/../lib/cleverly/Cleverly.class.php');

class Opcode {
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
    $tables = array();
    $json_opcodes = json_decode(file_get_contents($file_name));

    foreach ($json_opcodes as $json_opcode) {
      $tokens = array();
      $token = strtok(strtoupper($json_opcode->opcode), ' ');

      while (preg_match('/^[\dA-F][\dA-F]$/', $token)) {
        $tokens[] = $token;
        $token = strtok(' ');
      }

      switch (count($tokens)) {
        case 1:
          $table_prefix = '';
          list($row_index, $column_index) = str_split($tokens[0]);
          break;
        case 2:
          $table_prefix = $tokens[0];
          list($row_index, $column_index) = str_split($tokens[1]);
          break;
        default:
          continue 2;
      }

      if (!array_key_exists($table_prefix, $tables)) {
        $table = new OpcodeTable();
        $table->prefix = $table_prefix;
        $table->rows = array();
        $tables[$table_prefix] = &$table;
      } else {
        $table = &$tables[$table_prefix];
      }

      if (!array_key_exists($row_index, $table->rows)) {
        $row = new OpcodeRow();
        $row->cells = array();
        $row->prefix = $row_index;
        $table->rows[$row_index] = &$row;

        for ($index = 0; $index < 16; $index++) {
          $row->cells[strtoupper(dechex($index))] = new Opcode();
        }
      } else {
        $row = &$table->rows[$row_index];
      }

      $opcode = &$row->cells[$column_index];
      $opcode->description = $json_opcode->opcode;

      $opcode->mnemonic = strtolower(
        preg_replace('/[a-z]/', '<var>$0</var>', $json_opcode->mnemonic)
      );

      unset($table);
      unset($row);
      unset($opcode);
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
}

$cleverly = new \Cleverly();
$cleverly->preserveIndent = true;
$cleverly->setTemplateDir(__DIR__ . '/templates');

$cleverly->display('index.tpl', array(
  'tables' => OpcodeTable::fromFile(__DIR__ . '/opcode_table.json')
));
?>

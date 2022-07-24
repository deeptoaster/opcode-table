<?php
namespace ClrHome;

define('ClrHome\REGISTER_NAMES', ['B', 'C', 'D', 'E', 'H', 'L', null, 'A']);
define('ClrHome\ITERATED_PARAMETERS', ['b', 'p', 'r', 'r1', 'r2']);

include(__DIR__ . '/../lib/tools/SimpleNumber.class.php');
include(__DIR__ . '/../lib/cleverly/Cleverly.class.php');

class Opcode {
  public string $bytes = '';
  public string $cycles = '';
  public string $description = '';
  public string $mnemonic = '';
  public int $space = 0;
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
        property_exists($json_opcode, 'cycles')
          ? $json_opcode->cycles
          : 'unknown',
        property_exists($json_opcode, 'description')
          ? $json_opcode->description
          : 'unknown',
        $json_opcode->mnemonic,
        []
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

  private static function applyArgumentStyling(string $content) {
    return preg_replace('/\b[a-z]+\b/', '<var>$0</var>', $content);
  }

  private static function formatHex(int $number) {
    return str_pad(strtoupper(dechex($number)), 2, '0', STR_PAD_LEFT);
  }

  private static function listOpcodesFromShorthand(
    array $bytes,
    int $byte_index,
    string $cycles,
    string $description,
    string $mnemonic,
    array $substitutions
  ): array {
    if ($byte_index === count($bytes)) {
      $opcode = new Opcode();
      $opcode->bytes = self::applyArgumentStyling(implode(' ', $bytes));
      $opcode->cycles = $cycles;

      $opcode->description = preg_replace_callback(
        '/\$(\w+)/',
        function($matches) use ($mnemonic, $substitutions) {
          return in_array($matches[1], ITERATED_PARAMETERS)
            ? $matches[1][0] === 'r'
              ? REGISTER_NAMES[$substitutions[$matches[1]]]
              : $substitutions[$matches[1]]
            : "<var>$matches[1]</var>";
        },
        $description
      );

      $opcode->mnemonic = strtolower(self::applyArgumentStyling($mnemonic));
      $opcode->space =
          $byte_index + count(preg_grep('/^[a-z][a-z]$/', $bytes));
      return [$opcode];
    } else if (
      !array_key_exists('b', $substitutions) &&
          strpos($bytes[$byte_index], 'b') !== false
    ) {
      return array_merge(...array_map(function(int $bit) use (
        $byte_index,
        $bytes,
        $cycles,
        $description,
        $mnemonic,
        $substitutions
      ): array {
        return self::listOpcodesFromShorthand(
          $bytes,
          $byte_index,
          $cycles,
          $description,
          str_replace('b', $bit, $mnemonic),
          array_merge($substitutions, ['b' => $bit])
        );
      }, range(0, 7)));
    } else if (
      !array_key_exists('p', $substitutions) &&
          strpos($bytes[$byte_index], 'p') !== false
    ) {
      return array_merge(...array_map(function(int $byte) use (
        $byte_index,
        $bytes,
        $cycles,
        $description,
        $mnemonic,
        $substitutions
      ): array {
        return self::listOpcodesFromShorthand(
          $bytes,
          $byte_index,
          $cycles,
          $description,
          str_replace('p', self::formatHex($byte) . 'H', $mnemonic),
          array_merge($substitutions, ['p' => $byte])
        );
      }, range(0x00, 0x38, 0x08)));
    } else if (
      !array_key_exists('r1', $substitutions) &&
          strpos($bytes[$byte_index], 'r1') !== false
    ) {
      return array_merge(...array_map(function(int $register) use (
        $byte_index,
        $bytes,
        $cycles,
        $description,
        $mnemonic,
        $substitutions
      ): array {
        return self::listOpcodesFromShorthand(
          $bytes,
          $byte_index,
          $cycles,
          $description,
          str_replace('r1', REGISTER_NAMES[$register], $mnemonic),
          array_merge($substitutions, ['r1' => $register])
        );
      }, array_diff(range(0, 7), [6])));
    } else if (
      !array_key_exists('r2', $substitutions) &&
          strpos($bytes[$byte_index], 'r2') !== false
    ) {
      return array_merge(...array_map(function(int $register) use (
        $byte_index,
        $bytes,
        $cycles,
        $description,
        $mnemonic,
        $substitutions
      ): array {
        return self::listOpcodesFromShorthand(
          $bytes,
          $byte_index,
          $cycles,
          $description,
          str_replace('r2', REGISTER_NAMES[$register], $mnemonic),
          array_merge($substitutions, ['r2' => $register])
        );
      }, array_diff(range(0, 7), [6])));
    } else if (
      !array_key_exists('r', $substitutions) &&
          strpos($bytes[$byte_index], 'r') !== false
    ) {
      return array_merge(...array_map(function(int $register) use (
        $byte_index,
        $bytes,
        $cycles,
        $description,
        $mnemonic,
        $substitutions
      ): array {
        return self::listOpcodesFromShorthand(
          $bytes,
          $byte_index,
          $cycles,
          $description,
          str_replace('r', REGISTER_NAMES[$register], $mnemonic),
          array_merge($substitutions, ['r' => $register])
        );
      }, array_diff(range(0, 7), [6])));
    } else {
      if (preg_match(
        '/' . implode('|', ITERATED_PARAMETERS) . '/',
        $bytes[$byte_index]
      )) {
        $bytes[$byte_index] = self::formatHex(SimpleNumber::from(
          $bytes[$byte_index],
          $substitutions
        )->real);
      }

      return self::listOpcodesFromShorthand(
        $bytes,
        $byte_index + 1,
        $cycles,
        $description,
        $mnemonic,
        $substitutions
      );
    }
  }
}

$cleverly = new \Cleverly();
$cleverly->preserveIndent = true;
$cleverly->setTemplateDir(__DIR__ . '/templates');

$cleverly->display('index.tpl', [
  'tables' => OpcodeTable::fromFile(__DIR__ . '/../opcode_table.json')
]);
?>

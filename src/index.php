<?php
namespace ClrHome;

define('ClrHome\REGISTER_NAMES', ['B', 'C', 'D', 'E', 'H', 'L', null, 'A']);
define('ClrHome\ITERATED_PARAMETERS', ['b', 'p', 'r', 'r1', 'r2']);

include(__DIR__ . '/../lib/tools/SimpleNumber.class.php');
include(__DIR__ . '/../lib/cleverly/Cleverly.class.php');

class OpcodeFlags {
  public string $c;
  public string $h;
  public string $n;
  public string $pv;
  public string $s;
  public string $z;

  public function __construct(object $json_flags) {
    foreach ($json_flags as $name => $behavior) {
      $property = str_replace('/', '', $name);

      if (!property_exists(self::class, $property)) {
        throw new \DomainException("Unrecognized flag name $name");
      }

      $this->$property = self::formatFlag($behavior);
    }
  }

  private static function formatFlag(string $behavior) {
    switch ($behavior) {
      case ' ':
        return 'undefined';
      case '*':
        return 'exceptional';
      case '+':
        return 'as defined';
      case '-':
        return 'unaffected';
      case '0':
        return 'reset';
      case '1':
        return 'set';
      case 'p':
        return 'detects parity';
      case 'v':
        return 'detects overflow';
      default:
        throw new \RangeException("Unrecognized flag behavior $behavior");
    }
  }
}

class Opcode {
  public array $bytes;
  public string $class;
  public string $cycles;
  public string $description;
  public OpcodeFlags $flags;
  public string $mnemonic;
  public int $space;

  public function __clone() {
    $this->bytes = $this->bytes;
  }

  public function __construct(object $json_opcode) {
    $this->bytes = $json_opcode->bytes;

    $this->class = implode(' ', array_filter([
      property_exists($json_opcode, 'undefined')
        ? $json_opcode->undefined ? 'undefined' : ''
        : '',
      property_exists($json_opcode, 'z180')
        ? $json_opcode->z180 ? 'z180' : ''
        : ''
    ]));

    $this->cycles = $json_opcode->cycles;
    $this->description = $json_opcode->description;
    $this->flags = new OpcodeFlags($json_opcode->flags);
    $this->mnemonic = $json_opcode->mnemonic;
    $this->space = count($json_opcode->bytes) +
        count(preg_grep('/^[a-z][a-z]$/', $json_opcode->bytes));
  }
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
        new Opcode($json_opcode),
        0,
        []
      );

      foreach ($opcodes as $opcode) {
        $bytes = preg_grep('/^[\dA-F][\dA-F]$/', $opcode->bytes);
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
            $row->cells[strtoupper(dechex($index))] = null;
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
    Opcode $opcode,
    int $byte_index,
    array $substitutions
  ): array {
    if ($byte_index === count($opcode->bytes)) {
      $opcode_copy = clone $opcode;
      $opcode_copy->bytes =
          array_map([self::class, 'applyArgumentStyling'], $opcode->bytes);

      $opcode_copy->description = preg_replace_callback(
        '/\$(\w+)/',
        function($matches) use ($substitutions) {
          return in_array($matches[1], ITERATED_PARAMETERS)
            ? $matches[1][0] === 'r'
              ? REGISTER_NAMES[$substitutions[$matches[1]]]
              : $substitutions[$matches[1]]
            : "<var>$matches[1]</var>";
        },
        $opcode->description
      );

      $opcode_copy->mnemonic =
          strtolower(self::applyArgumentStyling($opcode->mnemonic));
      return [$opcode_copy];
    } else if (
      !array_key_exists('b', $substitutions) &&
          strpos($opcode->bytes[$byte_index], 'b') !== false
    ) {
      return array_merge(...array_map(function(int $bit) use (
        $byte_index,
        $opcode,
        $substitutions
      ): array {
        $opcode_copy = clone $opcode;
        $opcode_copy->mnemonic = str_replace('b', $bit, $opcode->mnemonic);

        return self::listOpcodesFromShorthand(
          $opcode_copy,
          $byte_index,
          array_merge($substitutions, ['b' => $bit])
        );
      }, range(0, 7)));
    } else if (
      !array_key_exists('p', $substitutions) &&
          strpos($opcode->bytes[$byte_index], 'p') !== false
    ) {
      return array_merge(...array_map(function(int $byte) use (
        $byte_index,
        $opcode,
        $substitutions
      ): array {
        $opcode_copy = clone $opcode;
        $opcode_copy->mnemonic =
            str_replace('p', self::formatHex($byte) . 'H', $opcode->mnemonic);

        return self::listOpcodesFromShorthand(
          $opcode_copy,
          $byte_index,
          array_merge($substitutions, ['p' => $byte])
        );
      }, range(0x00, 0x38, 0x08)));
    } else if (
      !array_key_exists('r1', $substitutions) &&
          strpos($opcode->bytes[$byte_index], 'r1') !== false
    ) {
      return array_merge(...array_map(function(int $register) use (
        $byte_index,
        $opcode,
        $substitutions
      ): array {
        $opcode_copy = clone $opcode;
        $opcode_copy->mnemonic =
            str_replace('r1', REGISTER_NAMES[$register], $opcode->mnemonic);

        return self::listOpcodesFromShorthand(
          $opcode_copy,
          $byte_index,
          array_merge($substitutions, ['r1' => $register])
        );
      }, array_diff(range(0, 7), [6])));
    } else if (
      !array_key_exists('r2', $substitutions) &&
          strpos($opcode->bytes[$byte_index], 'r2') !== false
    ) {
      return array_merge(...array_map(function(int $register) use (
        $byte_index,
        $opcode,
        $substitutions
      ): array {
        $opcode_copy = clone $opcode;
        $opcode_copy->mnemonic =
            str_replace('r2', REGISTER_NAMES[$register], $opcode->mnemonic);

        return self::listOpcodesFromShorthand(
          $opcode_copy,
          $byte_index,
          array_merge($substitutions, ['r2' => $register])
        );
      }, array_diff(range(0, 7), [6])));
    } else if (
      !array_key_exists('r', $substitutions) &&
          strpos($opcode->bytes[$byte_index], 'r') !== false
    ) {
      return array_merge(...array_map(function(int $register) use (
        $byte_index,
        $opcode,
        $substitutions
      ): array {
        $opcode_copy = clone $opcode;
        $opcode_copy->mnemonic =
            str_replace('r', REGISTER_NAMES[$register], $opcode->mnemonic);

        return self::listOpcodesFromShorthand(
          $opcode_copy,
          $byte_index,
          array_merge($substitutions, ['r' => $register])
        );
      }, array_diff(range(0, 7), [6])));
    } else {
      $opcode_copy = clone $opcode;

      if (preg_match(
        '/' . implode('|', ITERATED_PARAMETERS) . '/',
        $opcode->bytes[$byte_index]
      )) {
        $opcode_copy->bytes[$byte_index] = self::formatHex(SimpleNumber::from(
          $opcode->bytes[$byte_index],
          $substitutions
        )->real);
      }

      return self::listOpcodesFromShorthand(
        $opcode_copy,
        $byte_index + 1,
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

<?
namespace ClrHome;

include(__DIR__ . '/Opcode.class.php');

final class OpcodeLink extends Immutable {
  public function __construct(
    protected string $tableId,
    protected string $tableName
  ) {}
}

final class OpcodeRow {
  public array $cells;
  public string $prefix;
}

final class OpcodeTable {
  const ITERATED_PARAMETERS = ['b', 'p', 'r', 'r1', 'r2'];

  const REGISTER_NAMES = [
    '' => ['B', 'C', 'D', 'E', 'H', 'L', null, 'A'],
    'ix' => ['B', 'C', 'D', 'E', 'IXH', 'IXL', null, 'A'],
    'iy' => ['B', 'C', 'D', 'E', 'IYH', 'IYL', null, 'A']
  ];

  const TABLE_NAMES = [
    '' => 'Main',
    'CB' => 'Bit',
    'DD' => 'IX',
    'DDCB' => 'IX Bit',
    'ED' => 'Misc.',
    'FD' => 'IY',
    'FDCB' => 'IY Bit'
  ];

  public string $id;
  public string $name;
  public string $prefix;
  public array $rows;

  public static function fromFile(string $file_name): array {
    $tables = [];
    $opcode_jsons = json_decode(file_get_contents($file_name));

    foreach ($opcode_jsons as $opcode_json) {
      $opcodes = self::listOpcodesFromShorthand(
        new Opcode($opcode_json),
        0,
        []
      );

      foreach ($opcodes as $opcode) {
        self::setElement(
          $tables,
          preg_grep('/^[\dA-F][\dA-F]$/', $opcode->bytes),
          $opcode
        );
      }

      foreach (self::TABLE_NAMES as $prefix => $name) {
        if ($prefix !== '') {
          self::setElement(
            $tables,
            str_split($prefix, 2),
            new OpcodeLink(strtolower($prefix), $name)
          );
        }
      }
    }

    foreach ($tables as $table) {
      foreach ($table->rows as $row) {
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

  private static function setElement(
    array &$tables,
    array $bytes,
    object $element
  ) {
    list($row_index, $column_index) = str_split(array_pop($bytes));
    $table_prefix = implode('', $bytes);

    if (!array_key_exists($table_prefix, $tables)) {
      $table = new OpcodeTable();
      $table->id = strtolower($table_prefix);
      $table->name = self::TABLE_NAMES[$table_prefix];
      $table->prefix = $table_prefix;
      $table->rows = [];
      $tables[$table_prefix] = $table;
    } else {
      $table = $tables[$table_prefix];
    }

    if (!array_key_exists($row_index, $table->rows)) {
      $row = new OpcodeRow();
      $row->cells = [];
      $row->prefix = $row_index;
      $table->rows[$row_index] = $row;

      for ($index = 0; $index < 16; $index++) {
        $row->cells[strtoupper(dechex($index))] = null;
      }
    } else {
      $row = $table->rows[$row_index];
    }

    $row->cells[$column_index] = $element;
  }

  private static function listOpcodesFromShorthand(
    Opcode $opcode,
    int $byte_index,
    array $substitutions
  ): array {
    $register_names = self::REGISTER_NAMES[$opcode->category];

    if ($byte_index === count($opcode->bytes)) {
      $opcode_copy = clone $opcode;
      $opcode_copy->bytes =
          array_map([self::class, 'applyArgumentStyling'], $opcode->bytes);

      $opcode_copy->description = preg_replace_callback(
        '/\$(\w+)/',
        function($matches) use ($register_names, $substitutions) {
          return in_array($matches[1], self::ITERATED_PARAMETERS)
            ? $matches[1][0] === 'r'
              ? $register_names[$substitutions[$matches[1]]]
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
        $register_names,
        $substitutions
      ): array {
        $opcode_copy = clone $opcode;
        $opcode_copy->mnemonic =
            str_replace('r1', $register_names[$register], $opcode->mnemonic);

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
        $register_names,
        $substitutions
      ): array {
        $opcode_copy = clone $opcode;
        $opcode_copy->mnemonic =
            str_replace('r2', $register_names[$register], $opcode->mnemonic);

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
        $register_names,
        $substitutions
      ): array {
        $opcode_copy = clone $opcode;
        $opcode_copy->mnemonic =
            str_replace('r', $register_names[$register], $opcode->mnemonic);

        return self::listOpcodesFromShorthand(
          $opcode_copy,
          $byte_index,
          array_merge($substitutions, ['r' => $register])
        );
      }, array_diff(range(0, 7), [6])));
    } else {
      $opcode_copy = clone $opcode;

      if (preg_match(
        '/' . implode('|', self::ITERATED_PARAMETERS) . '/',
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
?>

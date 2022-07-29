<?php
namespace ClrHome;

// set_error_handler(function($number, $string) {
//   global $opcode;
//   echo "$string on $opcode\n";
// });

define('ClrHome\PREFIXES', ['', 'ED', 'CB', 'DD', 'DDCB', 'FD', 'FDCB']);

include(__DIR__ . '/../lib/tools/SimpleNumber.class.php');

function formatHex(int $number) {
  return str_pad(strtoupper(dechex($number)), 2, '0', STR_PAD_LEFT);
}

function implodeBytes(array $bytes) {
  foreach ($bytes as &$byte) {
    if (strpos($byte, 'b') !== false || strpos($byte, 'r') !== false) {
      try {
        $byte = formatHex(SimpleNumber::from($byte, [
          'b' => 0,
          'r' => 0,
          'r1' => 0,
          'r2' => 0
        ])->real);
      } catch (UnexpectedValueException $exception) {}
    }
  }

  return implode('', preg_grep('/^[\dA-F][\dA-F]$/', $bytes));
}

$document = new \DOMDocument();
$document->load('https://clrhome.org/table/');
$tables = $document->getElementsByTagName('table');
$table_json =
    json_decode(file_get_contents(__DIR__ . '/../opcode_table.json'));

for ($table_index = 0; $table_index < $tables->length; $table_index++) {
  $rows = $tables[$table_index]->getElementsByTagName('tr');

  for ($row_index = 1; $row_index < $rows->length; $row_index++) {
    $cells = $rows[$row_index]->getElementsByTagName('td');

    for ($cell_index = 0; $cell_index < $cells->length; $cell_index++) {
      $opcode =
          PREFIXES[$table_index] .
          formatHex($row_index * 16 + $cell_index - 16);

      if ($cells[$cell_index]->hasAttribute('axis')) {
        foreach ($table_json as &$opcode_json) {
          if (implodeBytes($opcode_json->bytes) === $opcode) {
            $axis = explode('|', $cells[$cell_index]->getAttribute('axis'));

            if (!property_exists($opcode_json, 'cycles')) {
              $opcode_json->cycles = $axis[2];
            }

            if (!property_exists($opcode_json, 'description')) {
              $opcode_json->description = preg_replace(
                array('/bit 0/', '/\bb\b/'),
                array('bit <var>b</var>', '<var>r</var>'),
                $axis[3]
              );
            }

            if (!property_exists($opcode_json, 'flags')) {
              $opcode_json->flags = [
                'c' => $axis[0][0],
                'h' => $axis[0][3],
                'n' => $axis[0][1],
                'p/v' => strtolower($axis[0][2]),
                's' => $axis[0][5],
                'z' => $axis[0][4]
              ];
            }

            if ($cells[$cell_index]->getAttribute('class') === 'un') {
              $opcode_json->undefined = true;
            }

            $opcode_array = (array)$opcode_json;
            ksort($opcode_array);
            $opcode_json = (object)$opcode_array;
            break;
          }
        }
      }
    }
  }
}

usort($table_json, function($opcode_a, $opcode_b) {
  return strcmp($opcode_a->mnemonic, $opcode_b->mnemonic);
});

file_put_contents(
  __DIR__ . '/../opcode_table_2.json',
  json_encode($table_json)
);
?>

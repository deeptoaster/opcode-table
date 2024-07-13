<?php
namespace ClrHome;

include(__DIR__ . '/../../lib/tools/SimpleNumber.class.php');

abstract class OpcodeCategory extends Enum {
  const DEFAULT = '';
  const IX = 'ix';
  const IY = 'iy';
}

final class OpcodeFlags {
  public string $c;
  public string $h;
  public string $n;
  public string $pv;
  public string $s;
  public string $z;

  public function __construct(object $flags_json) {
    foreach ($flags_json as $name => $behavior) {
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
  public string $category;
  public string $class;
  public string $cycles;
  public string $description;
  public OpcodeFlags $flags;
  public string $mnemonic;
  public string $reference;
  public int $space;

  public function __clone() {
    $this->bytes = $this->bytes;
  }

  public function __construct(object $opcode_json) {
    $this->bytes = $opcode_json->bytes;
    $this->category = property_exists($opcode_json, 'category')
      ? OpcodeCategory::validate($opcode_json->category)
      : '';

    $this->class = implode(' ', array_filter([
      property_exists($opcode_json, 'undocumented')
        ? $opcode_json->undocumented ? 'undocumented' : ''
        : '',
      property_exists($opcode_json, 'z180')
        ? $opcode_json->z180 ? 'z180' : ''
        : ''
    ]));

    $this->cycles = $opcode_json->cycles;
    $this->description = $opcode_json->description;
    $this->flags = new OpcodeFlags($opcode_json->flags);
    $this->mnemonic = $opcode_json->mnemonic;
    $this->reference = property_exists($opcode_json, 'reference')
      ? $opcode_json->reference
      : '';
    $this->space = count($opcode_json->bytes) +
        count(preg_grep('/^[a-z][a-z]$/', $opcode_json->bytes));
  }
}
?>

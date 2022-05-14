<?
include(__DIR__ . '/../lib/cleverly/Cleverly.class.php');

$cleverly = new Cleverly();
$cleverly->preserveIndent = true;
$cleverly->setTemplateDir(__DIR__ . '/templates');

$cleverly->display('index.tpl', array(
  'rows' => array(
    array(
      'elements' => array(
        array(
          'description' =>
              'The signed value * is added to pc. The jump is measured from the start of the instruction opcode.',
          'mnemonic' => 'jr *'
        )
      ),
      'index' => '0'
    )
  )
));
?>

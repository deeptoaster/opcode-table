<?php
namespace ClrHome;

include(__DIR__ . '/classes/OpcodeTable.class.php');
include(__DIR__ . '/../lib/cleverly/Cleverly.class.php');

$cleverly = new \Cleverly();
$cleverly->preserveIndent = true;
$cleverly->setTemplateDir(__DIR__ . '/templates');

$cleverly->display('index.tpl', [
  'tables' => OpcodeTable::fromFile(__DIR__ . '/../opcode-table.json')
]);
?>

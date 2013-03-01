<?php
require_once 'XML/Serializer.php';

$base_dir = trim($argv[1]);
$dest_dir = trim($argv[2]);

$dir = scandir ($base_dir);

if (!is_dir($dest_dir)) {
  mkdir ($dest_dir);
}

// Iterate through the retrieved records
$z = 0;
foreach ($dir AS $file) {
  if (strpos($file, '.xml') === FALSE) continue;

  $xml = simplexml_load_file ($base_dir . "/". $file);
  $pdf = str_replace ('.xml', '.pdf', $file);

  if (!is_file($base_dir . "/".  $pdf)) {
    $pdf = str_replace ('.pdf', '.PDF', $pdf);
  }

  if (!is_file($base_dir . "/".  $pdf)) {
    print "Could not find $pdf \n";
    continue;
  }

  $arr = array();
  if (!$xml || !$xml->title) continue;

  $arr[] = array('_content' => (string) $xml->title, '_attributes' => array('element' => 'title'));
  if ($xml->type) $arr[] = array('_content' => (string) $xml->type, '_attributes' => array('element' => 'type'));
  if ($xml->rights) $arr[] = array('_content' => (string) $xml->rights, '_attributes' => array('element' => 'rights'));
  if ($xml->identifier) $arr[] = array('_content' => (string) $xml->identifier, '_attributes' => array('element' => 'identifier'));
  if ($xml->subject) $arr[] = array('_content' => (string) $xml->subject, '_attributes' => array('element' => 'subject'));
  if ($xml->source) $arr[] = array('_content' => (string) $xml->source, '_attributes' => array('element' => 'publisher'));
  if ($xml->aw_keywords) $arr[] = array('_content' => (string) $xml->aw_keywords->aw_field[0], '_attributes' => array('element' => 'relation', 'qualifier' => 'ispartofseries'));
  if ($xml->aw_keywords) $arr[] = array('_content' => (string) $xml->aw_keywords->aw_field[1], '_attributes' => array('element' => 'identifier', 'qualifier' => 'citation'));
  if ($xml->aw_keywords) $arr[] = array('_content' => (string) $xml->aw_keywords->aw_field[2], '_attributes' => array('element' => 'identifier', 'qualifier' => 'other'));

  $time = null;
  try {
    $time = new DateTime((string) $xml->date->created);
  }
  catch (Exception $e) {
    print "Bad date: $xml->date \n";
    continue;
  }
  if ($time) {
    $arr[] = array('_content' => $time->format('Y-m-d'), '_attributes' => array('element' => 'date', 'qualifier' => 'issued'));
  }

  if (count($arr) < 3) continue;

  $dc_xml = serializeRow($arr);

  if (empty($dc_xml)) continue;

  mkdir ($dest_dir . '/' . $z);
  file_put_contents ($dest_dir . '/' . $z . '/dublin_core.xml', $dc_xml);
  file_put_contents ($dest_dir . '/' . $z . '/contents', $pdf);
  copy ($base_dir . '/' . $pdf, $dest_dir . '/' . $z . '/' . $pdf);
  $z++;
}



function serializeRow($arr, $schema="dc") {
	$serializer_options = array (
   'addDecl' => TRUE,
   'encoding' => 'utf-8',
   'indent' => "\t",
   'rootName' => 'dublin_core',
	 'rootAttributes' => array('schema' => $schema),
   'defaultTagName' => 'dcvalue',
   'scalarAsAttributes' => FALSE,
   'attributesArray' => '_attributes',
   'contentName' => '_content',
  );

  if (empty($arr) || count($arr) < 1) {
      return null;
  }

  $serializer = &new XML_Serializer($serializer_options);
  $serializer->setOption(XML_SERIALIZER_OPTION_CDATA_SECTIONS, true);

  $status = $serializer->serialize($arr);

  // Check whether serialization worked
  if (PEAR::isError($status)) {
    die($status->getMessage());
  }

  return $serializer->getSerializedData();
}

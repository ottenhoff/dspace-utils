<?php
require 'File/MARC.php';
require_once 'XML/Serializer.php';

$marc_file = trim($argv[1]);
$dest_dir = trim($argv[2]);

// Retrieve a set of MARC records from a file
$journals = new File_MARC($marc_file);

// Iterate through the retrieved records
$z = 0;
while ($record = $journals->next()) {
  $arr = array();
  $foundTitle = false;

  foreach ($record->getFields() as $tag=>$value) {
    // initialize the dc array
    $dc = array();
    $val = null;

    switch ($tag) {
    case 110:
      $dc['element'] = 'contributor';
      break;
    case 900:
      $dc['element'] = 'publisher';
      break;
    case 902:
      $dc['element'] = 'date';
      $dc['qualifier'] = 'created';
      break;
    case 903:
    case 130:
    case 210:
    case 240:
    case 242:
    case 246:
    case 730:
    case 740:
      $dc['element'] = 'title';
      $foundTitle = true;
      break;
    case 907:
      $dc['element'] = 'source';
      break;
    case 904:
      $dc['element'] = 'subject';
      break;
    case 905:
    case 520:
      $dc['element'] = 'description';
      $dc['qualifier'] = 'abstract';
      break;
    case 521:
      $dc['element'] = 'audience';
      break;
    case 650:
      $dc['element'] = 'subject';
      break;
    }

    if ($value instanceof File_MARC_Control_Field) {
      $val = $value;
    }
    else {
      // Iterate through the subfields in this data field
      foreach ($value->getSubfields() as $code=>$subdata) {
        // var_dump($code . "::" . $subdata);die();
        $val = $subdata->getData();
      }
    }

    // strip invalid characters
    if (!empty($val)) {
      $val = preg_replace('/[[:^print:]]/', '', $val);
    }

    if (!empty($val) && count($dc) > 0) {
      if ($dc['element'] == 'date') {
        $val = str_replace (' pg', ', pg', $val);
        $val = str_replace ('?', '', $val);
        $val = str_replace ('Unknown,', '', $val);
        $val = str_replace ('Unknown', '', $val);
        $val = str_replace ('Supplement', '', $val);
        $val = str_replace ('(marked as December)', '', $val);
        $val = str_replace (',,', ',', $val);
        $val = trim($val);

        if (strpos($val, ",") !== FALSE && substr_count($val, ",") > 1) {
          $pos = strrpos ($val, ",");
          $page = trim(substr($val, $pos+1));
          $arr[] = array('_content' => $page, '_attributes' => array('element' => 'source'));

          $val = substr($val, 0, $pos);
        }
        else if (strpos($val, ",") === FALSE) {
          $val = str_replace (" ", "-", $val);
        }

        $time = null;
        try {
          $time = new DateTime($val);
        }
        catch (Exception $e) {
          print "Bad date: $val \n";
          continue;
        }
        if ($time) {
          $val = $time->format('Y-m-d');
        }
      }

      if (!$val) continue;

      $arr[] = array('_content' => $val, '_attributes' => $dc);
    }
  }

  if (!$foundTitle || count($arr) < 3) continue;

  $dc_xml = serializeRow($arr);

  if (empty($dc_xml)) continue;

  mkdir ($dest_dir . '/' . $z);
  file_put_contents ($dest_dir . '/' . $z . '/dublin_core.xml', $dc_xml);
  file_put_contents ($dest_dir . '/' . $z . '/contents', "");
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

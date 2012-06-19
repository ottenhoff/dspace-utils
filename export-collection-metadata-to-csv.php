#!/usr/bin/php
<?php

if (count($argv) !== 4) {
  exit("Bad number of arguments. Please supply database string, collection id, destination CSV\n\n");
}


$connString = trim($argv[1]);
$coll_id = (int) $argv[2];
$dest_csv = trim($argv[3]);

$conn = pg_connect ($connString);

if (!$conn) {
  exit ("Could not make connection to database server\n");
}

$fp = fopen($dest_csv, 'w');

$collections = "706";

$sql = "select mv.item_id, mf.element, mf.qualifier, mv.text_value, c.name AS collection_name
from metadatavalue mv
inner join metadatafieldregistry mf ON mv.metadata_field_id=mf.metadata_field_id
inner join item i ON mv.item_id=i.item_id
inner join collection2item c2i ON i.item_id=c2i.item_id
inner join collection c ON c2i.collection_id=c.collection_id
WHERE c2i.collection_id IN ($collections) AND mv.text_value != ''
ORDER BY mv.item_id, mv.place";

$res = pg_query ($conn, $sql);

$metadata = array('collection' => 'collection');
$out = array();

while ($row = pg_fetch_object ($res)) {
  $dc = $row->element;
  if (!empty ($row->qualifier)) {
    $dc .= "." . $row->qualifier;
  }

  $metadata[$dc] = $dc;

  // add the collection
  $out[(int) $row->item_id]['collection'] = $row->collection_name;

  // concatenate multiple items
  if (isset ($out[(int) $row->item_id][$dc])) {
    $out[(int) $row->item_id][$dc] .= "|" . $row->text_value;
  }
  else {
    $out[(int) $row->item_id][$dc] = $row->text_value;
  }
}

fputcsv ($fp, $metadata);

// loop through all items
foreach ($out AS $item_id => $arr) {
  $ret = array();

  foreach ($metadata AS $dc) {
    $ret[] = @$arr[$dc];
  }

  fputcsv ($fp, $ret);
}

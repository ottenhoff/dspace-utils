#!/usr/bin/php
<?php

if (count($argv) !== 5) {
  exit("Bad number of arguments. Please supply database string, collection id, community id, destination CSV\n\n");
}

$connString = trim($argv[1]);
$coll_id = (int) $argv[2];
$comm_id = (int) $argv[3];
$dest_csv = trim($argv[4]);

$conn = pg_connect ($connString);

if (!$conn) {
  exit ("Could not make connection to database server\n");
}

$fp = fopen($dest_csv, 'w');

$sql = "select mv.item_id, mf.element, mf.qualifier, mv.text_value, c.name AS collection_name
from metadatavalue mv
inner join metadatafieldregistry mf ON mv.metadata_field_id=mf.metadata_field_id
inner join item i ON mv.item_id=i.item_id
inner join collection2item c2i ON i.item_id=c2i.item_id
inner join collection c ON c2i.collection_id=c.collection_id
WHERE (c2i.collection_id=$coll_id OR c2i.collection_id IN (SELECT collection_id FROM community2collection WHERE community_id=$comm_id)) AND mv.text_value != ''
ORDER BY mv.item_id, mv.place";

$res = pg_query ($conn, $sql);

// set the first rows to files and collections
$metadata = array('files' => 'files', 'collection' => 'collection');
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

  // find all files for item
  $sql = "select b.name from bitstream b
          inner join bundle2bitstream b2b ON b.bitstream_id=b2b.bitstream_id
          inner join item2bundle i2b ON b2b.bundle_id=i2b.bundle_id
          inner join item i ON i2b.item_id=i.item_id
          WHERE i.item_id=$item_id AND b.source NOT LIKE 'Written by %' AND b.source NOT LIKE 'org.dspace.license.%'";

  $res = pg_query ($conn, $sql);

  $files = array();
  while ($row = pg_fetch_object ($res)) {
    $files[] = $row->name;
  }

  foreach ($metadata AS $dc) {
    if ($dc == "files") {
      $ret[] = implode ("|", $files);
    }
    else {
      $ret[] = @$arr[$dc];
    }
  }

  fputcsv ($fp, $ret);
}

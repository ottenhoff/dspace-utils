#!/usr/bin/php
<?php

if (count($argv) !== 5) {
  exit("Bad number of arguments. Please supply database string, collection id, DSpace asset path, destination path\n\n");
}


$connString = trim($argv[1]);
$coll_id = (int) $argv[2];
$base_path = trim($argv[3]);
$dest_path = trim($argv[4]);

$conn = pg_connect ($connString);

if (!$conn) {
  exit ("Could not make connection to database server\n");
}

if (!is_dir ($dest_path)) {
  mkdir ($dest_path);
}

$sql = "select i.item_id, b.name, b.internal_id from bitstream b
inner join bundle2bitstream b2b ON b.bitstream_id=b2b.bitstream_id
inner join item2bundle i2b ON b2b.bundle_id=i2b.bundle_id
inner join item i ON i2b.item_id=i.item_id
inner join collection2item c2i ON i.item_id=c2i.item_id
WHERE c2i.collection_id=$coll_id AND b.source NOT LIKE 'Written by %' AND b.source NOT LIKE 'org.dspace.license.%'";

$res = pg_query ($conn, $sql);

while ($row = pg_fetch_object ($res)) {
  $id = $row->internal_id;
  $file = $base_path . "/" . substr ($id, 0, 2) . "/" . substr ($id, 2, 2) . "/" . substr ($id, 4, 2) . "/" . $id;

  if (!is_file($file)) {
    exit ("Fatal error: Could not find file at $file \n");
  }

  $ret = copy ($file, $dest_path . "/" . $row->name);

  if (!$ret) {
    exit ("Fatal error: could not copy $file to " .  $dest_path . "/" . $row->name);
  }

  print "Copied $file to " .  $dest_path . "/" . $row->name . "\n";
}


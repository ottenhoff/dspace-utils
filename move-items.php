<?php
define ('BITSTREAM', 0);
define ('BUNDLE', 1);
define ('ITEM', 2);
define ('COLLECTION', 3);
define ('COMMUNITY', 4);

define ('ADMIN_GROUP', 1748);
$groups = array(0, 1, 500, 443, 1546, 384, 1745, 1746, 1748, 2101);


$oldDb = trim($argv[1]);
$newDb = trim($argv[2]);
$commId = (int) trim($argv[3]);

$out = array();
$last_out = array();
$second_out = array();

$old = pg_connect ($oldDb);
$new = pg_connect ($newDb);

if (!$old) {
  exit ("Could not make connection to old database server\n");
}
if (!$new) {
  exit ("Could not make connection to new database server\n");
}

// Find all the possible communities
$communities = array();
$tmp_comm_id = $commId;
while (true) {
  $communities[$tmp_comm_id] = true;

  $sql = "SELECT child_comm_id FROM community2community WHERE parent_comm_id=$tmp_comm_id";
  $res = pg_query ($old, $sql);

  $num = (int) pg_num_rows ($res);

  if ($num) {
    while ($r = pg_fetch_object ($res)) {
     $communities[(int)$r->child_comm_id] = false;
    }
  }

  $break = true;
  foreach ($communities AS $id => $done) {
    if (!$done) {
      $tmp_comm_id = $id;
      $break = false;
      break;
    }
  }

  if ($break) {
    break;
  }
}

// we just want the community ids from here on out
$communities = array_keys($communities);

// Find all the possible collections
$collections = array();

foreach ($communities AS $id) {
  $sql = "SELECT collection_id FROM community2collection WHERE community_id=$id";
  $res = pg_query ($old, $sql);

  while ($r = pg_fetch_object ($res)) {
    $collections[] = (int) $r->collection_id;
  }
}

$items = array();

// Create all of the items
$sql = "SELECT * FROM item WHERE owning_collection IN (" . implode (",", $collections) . ")";
$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  foreach ($row AS $r) {
    $z[] = $r;
  }

  $out[] = "INSERT INTO item (" . implode (",", $fields) . ") VALUES ('" . implode ("','", $z) . "');";
  $items[] = (int) $row[0];
}

// Create all of the community-collection links
$sql = "SELECT * FROM metadatavalue WHERE item_id IN (" . implode (",", $items) . ")";
$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  foreach ($row AS $r) {
    $z[] = pg_escape_string ($new, $r);
  }

  $out[] = "INSERT INTO metadatavalue (" . implode (",", $fields) . ") VALUES ('" . implode ("','", $z) . "');";
}

$bundles = array();

// Create all of the community-collection links
$sql = "SELECT * FROM item2bundle WHERE item_id IN (" . implode (",", $items) . ")";
$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  foreach ($row AS $r) {
    $z[] = $r;
  }

  $last_out[] = "INSERT INTO item2bundle (" . implode (",", $fields) . ") VALUES (" . implode (",", $z) . ");";
  $bundles[] = (int) $row[2];
}

// Create all of the community-collection links
$sql = "SELECT * FROM bundle WHERE bundle_id IN (" . implode (",", $bundles) . ")";
$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  foreach ($row AS $r) {
    $z[] = pg_escape_string ($new, $r);
  }

  $tmp = "INSERT INTO bundle (" . implode (",", $fields) . ") VALUES ('" . implode ("','", $z) . "');";
  $tmp = str_replace ("''", 'null', $tmp);
  $second_out[] = $tmp;
}

$bitstreams = array();

$sql = "SELECT * FROM bundle2bitstream WHERE bundle_id IN (" . implode (",", $bundles) . ")";
$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  foreach ($row AS $r) {
    $z[] = $r;
  }

  $last_out[] = "INSERT INTO bundle2bitstream (" . implode (",", $fields) . ") VALUES (" . implode (",", $z) . ");";
  $bitstreams[] = (int) $row[2];
}


$file_path = array();


// Find all bitstreams
$sql = "SELECT * FROM bitstream WHERE bitstream_id IN (" . implode (",", $bitstreams) . ")";
$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  foreach ($row AS $r) {
    $z[] = pg_escape_string ($new, $r);
  }

  $out[] = "INSERT INTO bitstream (" . implode (",", $fields) . ") VALUES ('" . implode ("','", $z) . "');";
  $file_path[] = substr ($row[9], 0, 2) . "/" . substr ($row[9], 2, 2) . "/" . substr ($row[9], 4, 2) . "/" . $row[9];
}

// Collection2item table
$sql = "SELECT * FROM collection2item WHERE item_id IN (" . implode (",", $items) . ")";
$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  foreach ($row AS $r) {
    $z[] = $r;
  }

  $out[] = "INSERT INTO collection2item (" . implode (",", $fields) . ") VALUES (" . implode (",", $z) . ");";
}

// Resource policies
$sql = "SELECT * FROM resourcepolicy WHERE 
         (resource_type_id=" . BITSTREAM . " AND resource_id IN (" . implode (",", $bitstreams) . ")) OR
         (resource_type_id=" . BUNDLE . " AND resource_id IN (" . implode (",", $bundles) . ")) OR
         (resource_type_id=" . ITEM . " AND resource_id IN (" . implode (",", $items) . ")) OR
         (resource_type_id=" . COLLECTION . " AND resource_id IN (" . implode (",", $collections) . ")) OR
         (resource_type_id=" . COMMUNITY . " AND resource_id IN (" . implode (",", $communities) . ")) 
      ";

$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  foreach ($row AS $r) {
    $z[] = $r;
  }

  $resource_groups[] = (int) $row[5];
  if (!in_array($row[5], $groups)) {
    $row[5] = ADMIN_GROUP;
  }

  $line = "INSERT INTO resourcepolicy (" . implode (",", $fields) . ") VALUES (" . implode (",", $z) . ");";
  $line = str_replace ("''", "null", $line);
  $line = str_replace (",,", ",null,", $line);
  $line = str_replace (",)", ",null)", $line);
  $last_out[] = $line;
}

// write the files to a path
file_put_contents ("/tmp/files-for-community-$commId.txt", implode("\n", $file_path));

foreach ($items AS $id) {
  $sql2 = "SELECT * FROM handle WHERE resource_id=$id AND resource_type_id=" . ITEM;
  $res2 = pg_query ($old, $sql2);
  $row2 = pg_fetch_object($res2);
  if (!$row2) continue;

  $out[] = "INSERT INTO handle (handle_id, handle, resource_type_id, resource_id) VALUES ($row2->handle_id, '$row2->handle', $row2->resource_type_id, $row2->resource_id);";
}

foreach ($out AS $line) {
  $line = str_replace ("''", 'null', $line);

  $ret = pg_query ($new, $line);

  if (!$ret) {
    print $line . "\n";
    // exit(1);
  }
}

foreach ($second_out AS $line) {
  $line = str_replace ("''", 'null', $line);

  $ret = pg_query ($new, $line);

  if (!$ret) {
    print $line . "\n";
    // exit(1);
  }
}

foreach ($last_out AS $line) {
  $line = str_replace ("''", 'null', $line);

  $ret = pg_query ($new, $line);

  if (!$ret) {
    print $line . "\n";
    //exit(1);
  }
}

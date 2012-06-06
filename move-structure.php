<?php
define ('COLLECTION', 3);
define ('COMMUNITY', 4);

$oldDb = trim($argv[1]);
$newDb = trim($argv[2]);
$commId = (int) trim($argv[3]);
$out = array();

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

// Create all of the communities
$sql = "SELECT * FROM community WHERE community_id IN (" . implode (",", $communities) . ")";
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

  $out[] = "INSERT INTO community (" . implode (",", $fields) . ") VALUES ('" . implode ("','", $z) . "');";
}

// Create all of the community links
$sql = "SELECT * FROM community2community WHERE child_comm_id IN (" . implode (",", $communities) . ")";
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

  $out[] = "INSERT INTO community2community (" . implode (",", $fields) . ") VALUES (" . implode (",", $z) . ");";
}

// Find all the possible collections
$collections = array();
foreach ($communities AS $id) {
  $sql = "SELECT collection_id FROM community2collection WHERE community_id=$id";
  $res = pg_query ($old, $sql);

  while ($r = pg_fetch_object ($res)) {
    $collections[] = (int) $r->collection_id;
  }
}

// Create all of the collections
$sql = "SELECT * FROM collection WHERE collection_id IN (" . implode (",", $collections) . ")";
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

  $out[] = "INSERT INTO collection (" . implode (",", $fields) . ") VALUES ('" . implode ("','", $z) . "');";
}

// Create all of the community-collection links
$sql = "SELECT * FROM community2collection WHERE community_id IN (" . implode (",", $communities) . ")";
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

  $out[] = "INSERT INTO community2collection (" . implode (",", $fields) . ") VALUES (" . implode (",", $z) . ");";
}


foreach ($communities AS $id) {
  $sql2 = "SELECT * FROM handle WHERE resource_id=$id AND resource_type_id=" . COMMUNITY;
  $res2 = pg_query ($old, $sql2);
  $row2 = pg_fetch_object($res2);

  $out[] = "INSERT INTO handle (handle_id, handle, resource_type_id, resource_id) VALUES ($row2->handle_id, '$row2->handle', $row2->resource_type_id, $row2->resource_id);";
}

foreach ($collections AS $id) {
  $sql2 = "SELECT * FROM handle WHERE resource_id=$id AND resource_type_id=" . COLLECTION;
  $res2 = pg_query ($old, $sql2);
  $row2 = pg_fetch_object($res2);

  $out[] = "INSERT INTO handle (handle_id, handle, resource_type_id, resource_id) VALUES ($row2->handle_id, '$row2->handle', $row2->resource_type_id, $row2->resource_id);";
}
  
foreach ($out AS $line) {
  print $line . "\n";
}

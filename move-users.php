<?php
define ('COLLECTION', 3);
define ('COMMUNITY', 4);

$oldDb = trim($argv[1]);
$newDb = trim($argv[2]);
$domain = trim($argv[3]);

$out = array();
$last_out = array();

$old = pg_connect ($oldDb);
$new = pg_connect ($newDb);

if (!$old) {
  exit ("Could not make connection to old database server\n");
}
if (!$new) {
  exit ("Could not make connection to new database server\n");
}

$epeople = array();
$egroups = array();

$sql = "SELECT * FROM eperson WHERE email LIKE '%$domain'";
$res = pg_query ($old, $sql);

while ($row = pg_fetch_object ($res)) {
  $epeople[] = $row->eperson_id;

  $tmp = "INSERT INTO eperson (eperson_id, email, password, firstname, lastname, can_log_in, last_active, sub_frequency, phone, netid, language) VALUES ($row->eperson_id, '$row->email', '$row->password', '$row->firstname', '$row->lastname', '$row->can_log_in', '$row->last_active', '$row->sub_frequency', '$row->phone', '$row->netid', '$row->language');";
  $tmp = str_replace ("''", 'null', $tmp);
  $out[] = $tmp;
}

// just want the unique keys
$epeople = array_unique ($epeople);

// now find the groups
$sql = "SELECT * FROM epersongroup2eperson WHERE eperson_id IN (" . implode(",", $epeople) . ")";
$res = pg_query ($old, $sql);

$fields = array();
$i = pg_num_fields($res);
for ($j = 0; $j < $i; $j++) {
    $fields[] = pg_field_name($res, $j);
}

while ($row = pg_fetch_row ($res)) {
  $z = array();

  // save the groups
  $egroups[] = (int) $row[1];

  foreach ($row AS $r) {
    $z[] = $r;
  }

  $last_out[] = "INSERT INTO epersongroup2eperson (" . implode (",", $fields) . ") VALUES (" . implode (",", $z) . ");";
}

// Find the groups
$sql = "SELECT * FROM epersongroup WHERE eperson_group_id IN (" . implode (",", $egroups) . ")";
$res = pg_query ($old, $sql);

while ($row = pg_fetch_object ($res)) {
  $out[] = "INSERT InTO epersongroup (eperson_group_id, name) VALUES ($row->eperson_group_id, '$row->name');";
}


foreach ($out AS $line) {
  $ret = pg_query ($new, $line);
  print $line . "::" . $ret . "\n";
}
foreach ($last_out AS $line) {
  $ret = pg_query ($new, $line);
  print $line . "::" . $ret . "\n";
}

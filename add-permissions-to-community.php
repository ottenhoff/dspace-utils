#!/usr/bin/php
<?php

if (count($argv) !== 4) {
  print ("Bad number of arguments. Please supply database string, community id, and group id. \n\n");
  exit (1);
}

$connString = trim($argv[1]);
$com_id = (int) $argv[2];
$group_id = (int) $argv[3];

$conn = pg_connect ($connString);

if (!$conn) {
  exit ("Could not make connection to database server\n");
}

// get all of the collections
$res = pg_query ($conn, "select distinct item_id from communities2item where community_id=$com_id");

while ($row = pg_fetch_object ($res)) {
  $item_id = (int) $row->item_id;

  $res2 = pg_query ($conn, "SELECT * from resourcepolicy WHERE resource_id=$item_id AND resource_type_id=2 AND (epersongroup_id=$group_id OR epersongroup_id=0)");

  if (pg_num_rows ($res2) === 0) {
    $ret = pg_query ($conn, "INSERT INTO resourcepolicy (policy_id, resource_type_id, resource_id, epersongroup_id, action_id) VALUES (nextval('resourcepolicy_seq'), 2, $item_id, $group_id, 0)");
    print "Adding resourcepolicy ON $item_id :: $ret \n";
  }

  // get the bundles
  $res3 = pg_query ($conn, "SELECT bundle_id FROM item2bundle WHERE item_id=$item_id");

  while ($row3 = pg_fetch_object ($res3)) {
    $bundle_id = (int) $row3->bundle_id;

     $res4 = pg_query ($conn, "SELECT * from resourcepolicy WHERE resource_id=$bundle_id AND resource_type_id=1 AND (epersongroup_id=$group_id OR epersongroup_id=0)");

     if (pg_num_rows ($res4) === 0) {
       $ret = pg_query ($conn, "INSERT INTO resourcepolicy (policy_id, resource_type_id, resource_id, epersongroup_id, action_id) VALUES (nextval('resourcepolicy_seq'), 1, $bundle_id, $group_id, 0)");
       print "Added resourcepolicy ON bundles for $item_id :: $ret \n";
     }

     // get the bitstreams
     $res5 = pg_query ($conn, "SELECT bitstream_id FROM bundle2bitstream WHERE bundle_id=$bundle_id");

     while ($row4 = pg_fetch_object($res5)) {
       $bitstream_id = (int) $row4->bitstream_id;

       $res6 = pg_query ($conn, "SELECT * from resourcepolicy WHERE resource_type_id=0 AND resource_id=$bitstream_id AND (epersongroup_id=$group_id OR epersongroup_id=0)");

       if (pg_num_rows($res6) === 0) {
         $ret = pg_query ($conn, "INSERT INTO resourcepolicy (policy_id, resource_type_id, resource_id, epersongroup_id, action_id) VALUES (nextval('resourcepolicy_seq'), 0, $bitstream_id, $group_id, 0)");
         print "Added resourcepolicy ON bitstreams for $item_id :: $ret \n";
       }
     }
  }
}

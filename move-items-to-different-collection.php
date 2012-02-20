<?php


if( count($argv) !== 4) {
	exit("Bad number of arguments. Please supply database string, CSV filename, and boolean whether file contains header row.\n\n");
}

$connString = trim($argv[1]);
$csvFile = trim($argv[2]);
$headerRow = (boolean) trim($argv[3]);

$conn = pg_connect ($connString);

if (!$conn) {
  exit("Bad connection string. Example: host=sheep port=5432 dbname=test user=lamb password=bar");
}

$handle = fopen($csvFile, "r");

if (!$handle) {
  exit ("Could not open $csvFile ");
}

$row = 1;
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
  if ($headerRow && $row == 1) {
    $row++;
    continue;
  }

  $num = count($data);

  if ($num !== 2) {
    print "Row $row is malformed \n";
    continue;
  }

  $item = $data[0];
  $collection = $data[1];

  $item_id = getItemIdFromHandle ($conn, $item);
  $collection_id = getCollectionIdFromHandle ($conn, $collection);
  $community_id = getCommunityId ($conn, $collection_id);

  if (!$item_id || !$collection_id || !$community_id) {
    continue;
  }

  #print "Handle: $item; Collection: $collection \n";
  #print $item_id . "::" . $collection_id;

  $r1 = pg_query ($conn, "UPDATE item SET owning_collection=$collection_id WHERE item_id=$item_id");
  $r2 = pg_query ($conn, "UPDATE collection2item SET collection_id=$collection_id WHERE item_id=$item_id");
  $r3 = pg_query ($conn, "UPDATE communities2item SET community_id=$community_id WHERE item_id=$item_id");

  if (!$r1 || !$r2 || !$r3) {
    print "Bad update: $r1 :: $r2 :: $r2 \n";
  }
  $row++;
}

fclose($handle);

function getItemIdFromHandle ($conn, $handle) {
  $res = pg_query($conn, "SELECT resource_id FROM handle WHERE handle='$handle' AND resource_type_id=2");

  if (!$res) {
    print "Could not find item id for $handle \n";
    return false;
  }

  return pg_fetch_result ($res, 0, 0);
}

function getCollectionIdFromHandle ($conn, $handle) {
  $res = pg_query($conn, "SELECT resource_id FROM handle WHERE handle='$handle' AND resource_type_id=3");

  if (!$res || pg_num_rows($res) == 0) {
    print "Could not find collection id for $handle \n";
    return false;
  }

  return pg_fetch_result ($res, 0, 0);
}

function getCommunityId ($conn, $collection_id) {
  $res = pg_query($conn, "SELECT community_id FROM community2collection WHERE collection_id=$collection_id");

  if (!$res || pg_num_rows($res) == 0) {
    print "Could not find community id for $collection_id \n";
    return false;
  }

  return pg_fetch_result ($res, 0, 0);
}

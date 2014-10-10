<?php
##
## To change the parent collection of all items, workspaceitems, based on CSV mapping
## csv has:
## collection_id_old, collection_id_new
## 123              , 456
## You could perhaps just deal with moving the collections (changing the owning community of the collections)


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

##Transaction Begin
pg_query($conn,"BEGIN;");

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

  $collectionIDOld = $data[0];
  $collectionIDNew = $data[1];

  if (!$collectionIDOld || !$collectionIDNew) {
    continue;
  }

  #print "Handle: $item; Collection: $collection \n";
  #print $item_id . "::" . $collection_id;

  $r1 = pg_query ($conn, "UPDATE item SET owning_collection=$collectionIDNew WHERE owning_collection=$collectionIDOld");
  $r2 = pg_query ($conn, "UPDATE collection2item SET collection_id=$collectionIDNew WHERE collection_id=$collectionIDOld");
  $r3 = pg_query ($conn, "UPDATE workspaceitem SET collection_id=$collectionIDNew WHERE collection_id=$collectionIDOld");

  if (!$r1 || !$r2 || !$r3) {
    print "Bad update: $r1 :: $r2 :: $r3 \n";
  }
  $row++;
}

## Transaction Commit
pg_query($conn,"COMMIT;");

fclose($handle);

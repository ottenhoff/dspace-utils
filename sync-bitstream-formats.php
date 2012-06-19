<?php
$oldDb = trim($argv[1]);
$newDb = trim($argv[2]);

$old = pg_connect ($oldDb);
$new = pg_connect ($newDb);

if (!$old) {
  exit ("Could not make connection to old database server\n");
}
if (!$new) {
  exit ("Could not make connection to new database server\n");
}

$sql = "SELECT b.bitstream_id, f.mimetype FROM bitstream b INNER JOIN bitstreamformatregistry f ON b.bitstream_format_id=f.bitstream_format_id";
$res = pg_query ($old, $sql);

while ($row = pg_fetch_object ($res)) {
  $sql = "SELECT bitstream_format_id FROM bitstreamformatregistry where mimetype='$row->mimetype'";
  $res2 = pg_query ($new, $sql);
  $r = pg_fetch_object($res2);
  $id = (int) $r->bitstream_format_id;

  if ($id) {
    $sql = "UPDATE bitstream SET bitstream_format_id=$id WHERE bitstream_id=" . (int) $row->bitstream_id;
    $ret = pg_query ($new, $sql);
    print $row->bitstream_id .":" . $ret . "\n";
  }
}

#!/usr/bin/php
<?php

$arr = array(
1266,
1277,
1278,
1279,
1280,
1281,
1282,
1283,
1284,
1285,
1286,
1267,
1287,
1039,
1001,
1003,
1131,
1002,
954,
955,
956,
957,
958,
959,
960,
950,
1007,
1011,
930,
1010,
1012,
1013,
1034,
820,
821,
972,
1342,
971,
970,
969,
818,
968,
1344,
1014,
876,
875,
874,
873,
872,
871,
870,
869,
822,
823,
819,
973,
1343,
1015,
951,
952,
953,
1016,
927,
929,
928,
931,
933,
934,
936,
937,
932,
946,
939,
992,
993,
994,
995,
1099,
1101,
1100,
1104,
1102,
1105,
1106,
1108,
1107,
1110,
1109,
1116,
1117,
1118,
1023,
1024,
1120,
1025,
1119,
1125,
1026,
1027,
1126,
1028,
1128,
1129,
1113,
1114,
1314,
1315,
1316,
1029,
1132,
1194,
1195,
1196,
1197,
1226,
1313,
1227,
1228,
1230,
1231,
1247,
1248,
1252,
1253,
1254,
1296,
1297,
1298,
1312,
1309,
1310,
1311,
1308,
940,
941,
935,
948,
949,
1307,
943,
944,
942,
);

$z = 100;
foreach ($arr AS $coll) {
  mkdir ("exports/$coll");
  $cmd = "/home/dspace/dspace152/bin/export -t COLLECTION -i 10090/$coll -d ./exports/$coll -m -n $z"; 
  exec ($cmd);

  $z += 100;
}

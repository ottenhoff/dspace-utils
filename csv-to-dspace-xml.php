<?php
set_time_limit(0);

require_once 'XML/Serializer.php';

if (floatval(phpversion()) < 5.0) {
  require_once 'PHP/Compat.php';
  require_once 'PHP/Compat/Function/file_put_contents.php';
}

$err = "";
$badFiles = array();
global $err, $badFiles;

$commandsToRun = array();
$out = array();

if( count($argv) !== 4) {
	die("Bad number of arguments. Please supply a CSV filename to load, a directory to place the files, and the contents directory. \n\n");
}

$csvFile = trim($argv[1]);
$dir     = trim($argv[2]);
$assets  = trim($argv[3]);

if( !file_exists($csvFile)) {
	die("Bad filename.");
}

if( !is_dir($dir)) {
	die("Bad directory.");
}

if( !is_dir($assets)) {
	die("Bad assets directory.");
}

$handle = fopen($csvFile, "r");

$columns = array();

$count = 0;

while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
  sleep(1);

	if($count == 0) {
		$columns = $data; // assign the column names
	}
	else {
		$arr = array();
		$cmdArr = array();
		$fileNames = array();
		
		$cnt = count($data);

		$commands = array('collection id', 'item owner', 'file location', 'pdf');

		for($z = 0; $z < $cnt; $z++) {
			if(empty($data[$z])) {
				
			} 
			elseif(in_array(trim(strtolower($columns[$z])), $commands)) {
				
				switch(trim(strtolower($columns[$z]))) {

					case "item owner":
						$cmdArr['owner'] = $data[$z];
						break;

					case "collection id":
						if (!isset($cmdArr['coll'])) { // do not overwrite previous 
							$cmdArr['coll'] = $data[$z];
						}
						break;

					case "file location":
					case "pdf":
						$fileNames[] = trim($data[$z]);
						break;

					default:
						break;

				}

			}
			else {
			
				$dc = array();

				if(eregi('.', $columns[$z])) { // we have a DC qualifier

					$tmpArr = explode('.', $columns[$z]);

					if (trim($tmpArr[0]) == 'dc') {
						$dc['element'] = trim($tmpArr[1]);

						if(isset($tmpArr[2]) && trim($tmpArr[2]) != '') {
							$dc['qualifier'] = trim($tmpArr[2]);
						}
						else {
							$dc['qualifier'] = 'none';
						}
					
					}
					else {
						$dc['element'] = strtolower(trim($tmpArr[0]));

						if(isset($tmpArr[1]) && trim($tmpArr[1]) != '') {
							$dc['qualifier'] = strtolower(trim($tmpArr[1]));
						}
						else {
							$dc['qualifier'] = 'none';
						}
					}
				}

				$the_val = trim($data[$z]);
				$strlen  = strlen($the_val);
				if ( substr($the_val, ($strlen-1),1) == ";") {
					$the_val = substr($the_val, 0, ($strlen-1));
				}

				$arr[] = array('_content' => $the_val, '_attributes' => $dc);

			}
		}

		$dc_xml = serializeRow($arr);
	
		$contents = createContentsFile($fileNames);

		$destination_dir = $dir;
		$command_dir = $dir;

		if (eregi("/", $cmdArr['coll'])) {
			$tmp = explode("/", $cmdArr['coll']);
			$tmp2 = array_pop($tmp);

			$destination_dir .= "/" . $tmp2;
			$command_dir .= "/" . $tmp2;

			if (!is_dir($destination_dir)) {
				mkdir ($destination_dir);
			}
		}
		$destination_dir .= "/" . $count;

		mkdir($destination_dir);

		file_put_contents($destination_dir . "/dublin_core.xml", $dc_xml);
		file_put_contents($destination_dir . "/contents", $contents);

		var_dump($fileNames);
		moveFilesToImportDir($fileNames, $assets, $destination_dir);

		$command = createCommand($cmdArr, $command_dir) . "\n";
		$commandsToRun[md5($command)] = $command;
	}


	sleep(1);
  $count++;
}

file_put_contents("error.txt", $err);

$c = "";
foreach ($commandsToRun AS $key => $val) {
	$c .= $val . "\n";
}
file_put_contents("commands.sh", $c);

if (count($badFiles) > 0) {
  file_put_contents("badfiles.txt", implode("\n", $badFiles));
}


function moveFilesToImportDir($files, $assetDir, $dir) {
	global $err, $badFiles;
	
	foreach($files AS $filename) {
		if(!file_exists($assetDir . "/" . $filename)) {

      $filename = str_replace('\\', '/', $filename);
      if (strpos($filename, '/') !== FALSE) {
        $tt = explode('/', $filename);
        $filename = array_pop($tt);
      }

      if(!file_exists($assetDir . "/" . $filename)) {
			  $err .= "Bad file: " . $assetDir . "/" . $filename . "\n";
        echo  "Bad file: " . $assetDir . "/" . $filename . "\n";
        $badFiles[] = $filename;
			  continue;
      }
		}

		$targetFile = $filename;

		if(eregi("/", $filename)) {
			$parts = explode("/", $filename);

			$tmp = "";
			foreach($parts AS $part) {
				$targetFile = $part;
			}
		}

		if(!copy($assetDir . "/" . $filename, $dir . "/" . $targetFile)) {
			die("bad copy: " . $assetDir . "/" . $filename . " to " . $dir . "/" . $targetFile);
		}
	}

	return true;
}


function createContentsFile($files) {
	$justNames = array();
	foreach($files AS $filename) {
		if(eregi("/", $filename)) {
			$parts = explode("/", $filename);

			$tmp = "";
			foreach($parts AS $part) {
				$tmp = $part;
			}
	
			$justNames[] = $tmp;
		}	
		else {
			$justNames[] = $filename;
		}

	}

	return implode("\n", $justNames);
}

function createCommand($arr, $dir) {
	if (empty($arr['owner'])) {
		$arr['owner'] = 'user@example.edu';
  }
	return "bin/import -a -e " . $arr['owner'] . " -c " . $arr['coll'] . " -s " . $dir . " -m " . $dir . "/import.map";
}

function serializeRow($arr) {
	$serializer_options = array (
   'addDecl' => TRUE,
   'encoding' => 'utf-8',
   'indent' => "\t",
   'rootName' => 'dublin_core',
	 'rootAttributes' => array('schema' => 'dc'),
   'defaultTagName' => 'dcvalue',
   'scalarAsAttributes' => FALSE,
   'attributesArray' => '_attributes',
   'contentName' => '_content',
  );

  $serializer = &new XML_Serializer($serializer_options);
  $serializer->setOption(XML_SERIALIZER_OPTION_CDATA_SECTIONS, true);

  $status = $serializer->serialize($arr);

  // Check whether serialization worked
  if (PEAR::isError($status)) {
    die($status->getMessage());
  }

  return $serializer->getSerializedData();
}

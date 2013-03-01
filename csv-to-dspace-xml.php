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

	if($count == 0) {
		$columns = $data; // assign the column names
	}
	else {
		$arr = array();
		$acArr = array();
		$cmdArr = array();
		$fileNames = array();
		
		$cnt = count($data);

		$commands = array('collection id', 'collection', 'item owner', 'file location', 'file', 'filename', 'pdf', 'identifier');

		for($z = 0; $z < $cnt; $z++) {
			// clean up the user's column name
			$columns[$z] = strtolower(trim(str_replace("*","",$columns[$z])));

			if(empty($data[$z])) {

			}
			elseif(in_array(trim(strtolower($columns[$z])), $commands)) {

				switch($columns[$z]) {

					case "item owner":
						$cmdArr['owner'] = $data[$z];
						break;

					case "collection id":
          case "collection":
            if (!isset($cmdArr['coll'])) { // do not overwrite previous 
              $tmp = $data[$z];
              if (strpos($tmp, "|") !== FALSE) {
                $cmdArr['coll'] = substr ($tmp, 0, strpos ($tmp, "|"));
              }
              else {
                $cmdArr['coll'] = $data[$z];
              }
						}
						break;

					case "file location":
					case "identifier":
					case "filename":
					case "file":
					case "pdf":
						$fileNames[] = trim($data[$z]);
						break;

					default:
						break;

				}
			}
			elseif (strpos($columns[$z], "ac.") === 0) {
				$ac = array();

				if(eregi('.', $columns[$z])) { // we have a special AC element

					$tmpArr = explode('.', $columns[$z]);

					if (trim($tmpArr[0]) == 'ac') {
						$ac['element'] = trim($tmpArr[1]);

						if(isset($tmpArr[2]) && trim($tmpArr[2]) != '') {
							$ac['qualifier'] = trim($tmpArr[2]);
						}
						else {
							$ac['qualifier'] = 'none';
						}
					
					}
					else {
						$ac['element'] = strtolower(trim($tmpArr[0]));

						if(isset($tmpArr[1]) && trim($tmpArr[1]) != '') {
							$ac['qualifier'] = strtolower(trim($tmpArr[1]));
						}
						else {
							$ac['qualifier'] = 'none';
						}
					}
				}

				$the_val = trim($data[$z]);
				$strlen  = strlen($the_val);
				if ( substr($the_val, ($strlen-1),1) == ";") {
					$the_val = substr($the_val, 0, ($strlen-1));
        }

        // check if they are trying to do multiple values separated by pipes
        if (strpos($the_val, "||") !== FALSE) {
          $tmp = explode ("||", $the_val);
          foreach ($tmp AS $tt) {
            $acArr[] = array('_content' => $the_val, '_attributes' => $ac);
          }
        }
        else {
          $acArr[] = array('_content' => $the_val, '_attributes' => $ac);
        }
			}
			else {
				$dc = array();

        // we have a DC qualifier
				if(eregi('.', $columns[$z])) {

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

        // skip columns labeled ignore
        if (empty($dc['element']) || $dc['element'] == 'ignore') {
          continue;
        }

        // skip empty dates
        if ($the_val == '0000-00-00') {
          continue;
        }

        // split up on pipe character
        if (strpos($the_val, "||") !== FALSE) {
          $tmp = explode ("||", $the_val);
          foreach ($tmp AS $tt) {
            $arr[] = array('_content' => $tt, '_attributes' => $dc);
          }
        }
        else {
          $arr[] = array('_content' => $the_val, '_attributes' => $dc);
        }
			}
		}

		$dc_xml = serializeRow($arr);

		$ac_xml = serializeRow($acArr, "ac");

		$destination_dir = $dir;
		$command_dir = $dir;

		if (empty($cmdArr['coll'])) {
			echo "skipping row because no collection defined \n\n";
			continue;
		}

		if (strpos($cmdArr['coll'], "/") !== FALSE) {
			$tmp = explode("/", $cmdArr['coll']);
			$tmp2 = array_pop($tmp);

			$destination_dir .= "/" . $tmp2;
			$command_dir .= "/" . $tmp2;

			if (!is_dir($destination_dir)) {
				mkdir ($destination_dir);
			}
    }
    elseif (!empty($cmdArr['coll'])) {
			$destination_dir .= "/" . $cmdArr['coll'];
			$command_dir .= "/" . $cmdArr['coll'];

			if (!is_dir($destination_dir)) {
				mkdir ($destination_dir);
			}
    }

		$destination_dir .= "/" . $count;

		mkdir($destination_dir);

		file_put_contents($destination_dir . "/dublin_core.xml", $dc_xml);

    if (!empty($ac_xml)) {
      file_put_contents ($destination_dir . "/metadata_ac.xml", $ac_xml);
    }

    // moveFiles may modify the names of the files
		$fileNames = moveFilesToImportDir($fileNames, $assets, $destination_dir);

    // now can create the contents file
		$contents = createContentsFile($fileNames);
		file_put_contents($destination_dir . "/contents", $contents);

		$command = createCommand($cmdArr, $command_dir);
		$commandsToRun[md5($command)] = $command;
	}


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

  $filesToReturn = array();
	foreach($files AS $filename) {
		if(!file_exists($assetDir . "/" . $filename)) {

      $filename = str_replace('\\', '/', $filename);
      if (strpos($filename, '/') !== FALSE) {
        $tt = explode('/', $filename);
        $filename = array_pop($tt);
      }

      // try adding pdf to end of filename
      if(!file_exists($assetDir . "/" . $filename)) {
          $filename = str_replace ('  ', ' ', $filename);
      }

      if(!file_exists($assetDir . "/" . $filename)) {
          $filename = str_replace ('version ', 'exam version ', $filename);
      }

      if(!file_exists($assetDir . "/" . $filename)) {
          $filename = str_replace ('memo.pdf', 'exam memo.pdf', $filename);
      }
      if(!file_exists($assetDir . "/" . $filename)) {
          $filename = str_replace ('commentary.pdf', 'exam commentary.pdf', $filename);
      }

      if(!file_exists($assetDir . "/" . $filename)) {
          $filename = str_replace ('Creditors', "Creditor's", $filename);
      }

      if(!file_exists($assetDir . "/" . $filename)) {
          $filename = str_replace ('- .pdf', '- exam.pdf', $filename);
      }

      if(!file_exists($assetDir . "/" . $filename)) {
          $filename = str_replace ('with answers', 'exam with answers', $filename);
      }

      if(!file_exists($assetDir . "/" . $filename)) {
          $filename = str_replace ('2011 Fall', '2011 Fall - Memo', $filename);
      }

      // try adding pdf to end of filename
      if(!file_exists($assetDir . "/" . $filename)) {
          $filename .= '.pdf';
      }

      // final strip of .pdf.pdf
      if(!file_exists($assetDir . "/" . $filename)) {
        $filename = str_replace ('.pdf.pdf', '.pdf', $filename);
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

    $filesToReturn[] = $filename;
	}

	return $filesToReturn;
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
	return "bin/dspace import -a -e " . $arr['owner'] . " -c " . $arr['coll'] . " -s " . $dir . " -m " . $dir . "/import.map";
}

function serializeRow($arr, $schema="dc") {
	$serializer_options = array (
   'addDecl' => TRUE,
   'encoding' => 'utf-8',
   'indent' => "\t",
   'rootName' => 'dublin_core',
	 'rootAttributes' => array('schema' => $schema),
   'defaultTagName' => 'dcvalue',
   'scalarAsAttributes' => FALSE,
   'attributesArray' => '_attributes',
   'contentName' => '_content',
  );

  if (empty($arr) || count($arr) < 1) {
      return null;
  }

  $serializer = &new XML_Serializer($serializer_options);
  $serializer->setOption(XML_SERIALIZER_OPTION_CDATA_SECTIONS, true);

  $status = $serializer->serialize($arr);

  // Check whether serialization worked
  if (PEAR::isError($status)) {
    die($status->getMessage());
  }

  return $serializer->getSerializedData();
}

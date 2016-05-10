<?php

set_time_limit(1000);





$expire = 1;

session_start();

	

require_once('parsecsv.lib.php');
require_once('api.php');
require_once('functions.php');





echo "<html><body>";





if ( isset($_POST["submit"]) ) {

   if ( isset($_FILES["file"]) && in_array($_FILES['file']['type'],$mimes) ) {

            //if there was an error uploading the file
        if ($_FILES["file"]["error"] > 0) {
            echo "Return Code: " . $_FILES["file"]["error"] . "<br />";

        }
        else {
                 //Print file details
             echo "Upload: " . $_FILES["file"]["name"] . "<br />";
             echo "Type: " . $_FILES["file"]["type"] . "<br />";
             echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
             //echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
			 $fileloc = "/mmdu_" . time() . "_" . md5_file($_FILES['file']['tmp_name']) . ".csv";
			 
			 $filepath = platformSlashes(dirname($_FILES["file"]["tmp_name"]) . $fileloc);
			 deleteOldFiles(dirname($_FILES["file"]["tmp_name"]),"mmdu_");
			 $folder = dirname($_FILES["file"]["tmp_name"]);


        move_uploaded_file($_FILES['file']['tmp_name'], $filepath);
		$_SESSION['uploaded_filepath'] = $fileloc;

		session_write_close();
			
		$file = new parseCSV();
		$file->heading = false;
		$file->delimiter = ",";
		//$file->parse($_FILES['file']['tmp_name']);
		$file->parse($filepath);
		
			
		//$file = file_get_contents($_FILES['file']['tmp_name']);
			
        }
     } else {
             echo "No file selected <br />";
     }
}

$storage = array();



foreach($file->data as $key =>  $row)
{
	//echo "<ul><li>List<ul>";
	$temp = implode(",",$row);
	
	if(strpos($temp,$urls[0]) !== false || strpos($temp,$urls[1]) !== false)
	{
		$storage[] = $row;
	}
	/*
	foreach ($row as $value)
	{
		echo "<li>".$value."</li>"; 
	}
	echo "</ul></li></ul>";*/
}



findfirstURL($storage[0]);
deleteOldFiles($folder,"_products_",1);
deleteOldFiles($folder,"_category_",1);



?></body></html>
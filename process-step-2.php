<?php 

require_once('parsecsv.lib.php');
require_once('api.php');
require_once('functions.php');

$folder = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();


safeSession();

// need to filter var to prevent use of crap to access other files
// needs crap to filter out incorrect variables
// this section needs a rework for security.
$filePath = platformSlashes($folder.$_SESSION['uploaded_filepath']);
$storeID = $_SESSION['storeID'];



$file = new parseCSV();
$file->heading = false;
$file->delimiter = ",";
$file->parse($filePath);
$storage = array();

foreach($file->data as $key =>  $row)
{
	$temp = implode(",",$row);
	
	if(strpos($temp,$urls[0]) !== false || strpos($temp,$urls[1]) !== false)
	{
		$storage[] = $row;
	}
}

$selected = array();


for($i=0;$i<count($storage[0]);$i++)
{
	
	if(isset($_POST["field_".$i]))
	{
		$selected[] = filter_var($_POST["field_".$i],FILTER_SANITIZE_NUMBER_INT);
		//echo $_POST["field_".$i]."\n";
	}
}
	
$_SESSION['selected'] = $selected;





if(!array_search(1,$selected))
{
	echo "dick";
	die;
}

	$paths = getPath($storage,array_search(1,$selected));
	
	
	$fields = getPath($storage,array_search(3,$selected));
	$descs = getPath($storage,array_search(4,$selected));


	// for testing it's 0



	//lets implement caching


	$products_save = parse_url($siteUrl, PHP_URL_HOST)."_products_".filter_var($storeID,FILTER_SANITIZE_NUMBER_INT).".json";
	$category_save = parse_url($siteUrl, PHP_URL_HOST)."_category_".filter_var($storeID,FILTER_SANITIZE_NUMBER_INT).".json";


	//echo $folder; 

	deleteOldFiles($folder,"_products_",1);
	deleteOldFiles($folder,"_category_",1);

	
	if(!file_exists(platformSlashes($folder."/".$products_save)))
	{


		$products = getProductsData($client,$session,$storeID);
		$fp = fopen(platformSlashes($folder."/".$products_save), 'w');
		fwrite($fp, json_encode(utf8_converter($products)));
		fclose($fp);
		

		



	}
	else {
		$stats = stat(platformSlashes($folder."/".$products_save));
		echo "<ul>";
		echo "<li>Products checked: ".gmdate("Y-m-d\TH:i:s\Z", $stats[10])."</li>";
		echo "</ul>";
		$products = json_decode(file_get_contents(platformSlashes($folder."/".$products_save)),true);
		
	}


	if(!file_exists(platformSlashes($folder."/".$category_save)))
	{

	getCategoryId($client,$session,$storeID);
	getCategoryData($client,$session,$storeID);
		
		$fp = fopen(platformSlashes($folder."/".$category_save), 'w');
		fwrite($fp, json_encode(utf8_converter($categoryResult)));
		fclose($fp);
		



	}
	else {
		$stats = stat(platformSlashes($folder."/".$category_save));
		echo "<ul>";
		echo "<li>Categories checked: ".gmdate("Y-m-d\TH:i:s\Z", $stats[10])."</li>";
		echo "</ul>";
		$categoryResult = json_decode(file_get_contents(platformSlashes($folder."/".$category_save)),true);
		
	}
			





	$cat = allCategories();
	$metaData = checkActive($products);
	$allProducts = buildProductURLs($metaData,$products);





	$urlToID = pathsToItem($paths,$allProducts,$cat);



	buildTable($urlToID);


?>
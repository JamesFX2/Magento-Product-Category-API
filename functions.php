<?php




/*

if(!isset($_SESSION['username']))
{
	header('Location: '.$bounceBack("timeout",1);
	
	
}
*/

$querys = $_SERVER['QUERY_STRING'];
parse_str($querys, $output);

$categoryIds = array();
$crawled = array();
$categoryResult = array();
$categoryURLs = array();


function bounceBack($reason,$value)
{
	$currentP = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	$extension = explode("/",parse_url($currentP,PHP_URL_PATH));
	$currentP = str_replace($extension[(count($extension)-1)],"",$currentP);
	$currentP = preg_replace('/\?.*/', '', $currentP)."?".urlencode($reason)."=".$value;
	
	return $currentP;
}

function buildTable($data,$titlepos=2,$metapos=3)
{
	global $siteUrl;
	global $fields;
	global $descs;
	
	$scheme = parse_url($siteUrl, PHP_URL_SCHEME)."://";
	echo "<style> .inherited { color: #666 } .same { color: #933}</style>";
	
	echo "<form action=\"update.php\" method=\"post\"><table>\n";
	echo "<tr><th>Order</th><th>URL</th><th>Type</th><th>ID</th><th>Title</th><th>New Title</th><th>Meta</th><th>New Meta</th><th>Update?</th></tr>";
	foreach($data as $key => $item)
	{
		
		echo "<tr>";
		$title = "<em>NULL</em>";
		$meta = "<em>NULL</em>";
		$temp = determineType($item);
		$i_title = "";
		$i_meta = "";
		$same = "";
		$checked = "disabled";
		
		if($temp!="UNKNOWN")
		{
			$i_title = $item['Inherited Title'] == "TRUE" ? " class=\"inherited\"" : "";
			$i_meta = $item['Inherited Meta'] == "TRUE" ? " class=\"inherited\"" : "";			
			$title = $item['Title'];
			$meta = $item['Meta'];
			$checked = "checked";
			if ($fields[$key] == $title)
			{
				$same = " class=\"same\"";
			}
		}		
		$id = $item[$temp];
		echo "<td>".$key."</td>";
		echo "<td><a href=\"".$scheme.parse_url($siteUrl, PHP_URL_HOST).$item['URL']."\">".parse_url($siteUrl, PHP_URL_HOST).$item['URL']."</a></td>";
		echo "<td>".$temp."</td>";
		echo "<td>".$id."</td>";
		echo "<td".$i_title.">".$title."</td>";
		echo "<td".$same.">".$fields[$key]."</td>";
		echo "<td".$i_meta.">".$meta."</td>";
		echo "<td>".$descs[$key]."</td>";
		echo "<td><input type=\"checkbox\" name=\"update[]\" value=\"".$key."\" ".$checked."></td>";
		
		echo "</tr>";
	}
	
	echo "</table><input type=\"submit\" value=\"Update\"></form>";
	
}

function determineType($data)
{
	if(array_key_exists('Product',$data))
	{
		return "Product";
	}
	else if(array_key_exists('Category',$data))
	{
		return "Category";
	}
	else return "UNKNOWN";
	
}

function pathsToItem($paths,$allProducts,$cat) {
	
	$output = array();
	global $products;
	global $categoryResult;
	
	foreach($paths as $key => $item)
	{
		if(array_key_exists($item,$allProducts))
		{
			$output[$key]['URL'] = $item;					
			$output[$key]['Product'] = $allProducts[$item];
			$output[$key]['Title'] = $products[$allProducts[$item]]['meta_title'];			
			$output[$key]['Meta'] = $products[$allProducts[$item]]['meta_description'];	
			$output[$key]['Inherited Title'] = $products[$allProducts[$item]]['i_title'];			
			$output[$key]['Inherited Meta'] = $products[$allProducts[$item]]['i_desc'];				
			
		}
		else if(array_key_exists($item,$cat))
		{
			$output[$key]['URL'] = $item;					
			$output[$key]['Category'] = $cat[$item];	
			$output[$key]['Title'] = $categoryResult[$cat[$item]]['meta_title'];			
			$output[$key]['Meta'] = $categoryResult[$cat[$item]]['meta_description'];	
			$output[$key]['Inherited Title'] = $categoryResult[$cat[$item]]['i_title'];			
			$output[$key]['Inherited Meta'] = $categoryResult[$cat[$item]]['i_desc'];	
		}
		else 
		{
			$output[$key]['URL'] = $item;					
			$output[$key]['UNKNOWN'] = 1;					
		}
	}
	return $output;
	
}


function deleteOldFiles($location,$prefix,$hours=24)
{
	$files = array();
	$allList = scandir($location);
	foreach($allList as $item)
	{
		if(!is_dir($item) && strpos($item,$prefix) !== false)
		{
			$stats = stat($location."/".$item);

			$creation = $stats[10];

			if(time() > ($creation + (3600*$hours)))
			{	
				unlink($location."/".$item);
			}
			
		}
		
	}
			
}

function urlKeytoProduct($array)
{
	$data = array();
	foreach($array as $item)
	{
		$data[$item['url_key']] = $item['product_id'];
		$data[$item['url_path']] = $item['product_id'];
	}
	
	return $data;
}



function getCategoryData($client,$session,$storeId)
{
	global $categoryIds;
	global $categoryResult;
	
	foreach($categoryIds as $key => $item)
	{
		
		$temp = $client->catalogCategoryInfo($session, $key, $storeId);
		$temp = json_decode(json_encode($temp),true);

		
		$categoryDesc = isset($temp['description']) ? substr(strip_tags($temp['description']),0,255) : NULL;
		$temp['i_title'] = isset($temp['meta_title']) ? "FALSE" : "TRUE";
		$temp['i_desc'] = isset($temp['meta_description']) ? "FALSE" : "TRUE";
		$temp['meta_title'] = isset($temp['meta_title']) ? $temp['meta_title'] : $temp['name'];
		$temp['meta_description'] = isset($temp['meta_description']) ? $temp['meta_description'] : $categoryDesc;
		
		
		
		$categoryResult[$key] = $temp;
		
	}
}


function allCategories()
{
	global $categoryURLs;
	global $categoryResult;
	$allPaths = array();
	$root = determineRoot();
	if ($root == 0)
	{
		return;
	}
	
	foreach($categoryResult as $key => $item)
	{
		$allPaths["/".$item['url_path']] = $key;
		$current = $item['parent_id'];
		$longPath = "/".$item['url_key'];
		if($item['parent_id']!=$root)
		{
			while($current != $root)
			{
				$longPath = "/".$categoryResult[$current]['url_key'].$longPath;
				$current = $categoryResult[$current]['parent_id'];				
				
			}
			$allPaths[$longPath] = $key;
			$categoryURLs[$key] = $longPath;
			
		}
		else {
			
			
			$allPaths[$longPath] = $key;
			$categoryURLs[$key] = $longPath;
			
		}
		
		
	}
	return $allPaths;

	
}
	


function determineRoot()
{
	global $categoryResult;
	$return = array();
	
	foreach($categoryResult as $item)
	{
		if(!array_key_exists($item['parent_id'],$categoryResult))
		{
			$temp = $item['parent_id'];
			$return[$item['parent_id']] = $item['parent_id'];
		}
		
	}
	if (count($return) == 1)
	{
		return $temp;
	}
	else return 0;
}

function getCategoryId($client,$session,$storeId,$category=NULL) {
	
	global $categoryIds;
	global $crawled;
	
	
	$temp = $client->catalogCategoryLevel($session, NULL, $storeId,$category);
	$temp = json_decode(json_encode($temp),true);
	
	if(count($temp) == 0)
	{
		return;
	}
			
	do
	{
		foreach($temp as $item)
		{
			$categoryIds[$item['category_id']] = $item;
			if(!array_key_exists($item['category_id'],$crawled))
			{
				$crawled[$item['category_id']] = $item['category_id'];
				getCategoryId($client,$session,$storeId,$item['category_id']);
			}
		}
		
	}
	while(count($crawled)<(count($categoryIds)));
	
}

function checkActive($products)
{
	global $categoryResult;
	$active = array();
	
	if(count($categoryResult)<1)
	{
		return;
	}
	
	foreach($products as $item)
	{
		foreach($item['category_id'] as $category)
		{
			if(array_key_exists($category,$categoryResult))
			{
				$active[$item['product_id']][] = $category;		
			}
			
		}
	}
	
	return $active;
	
}


function buildProductURLs($metaData,$products)
{
	global $categoryResult;
	global $categoryURLs;
	if(count($categoryURLs) < 1)
	{
		return;
	}
	
	$productOutput = array();
	
	foreach($metaData as $key => $catList)
	{
		
		$productOutput['/'.$products[$key]['url_key']] = $key;
		$productOutput['/'.$products[$key]['url_path']] = $key;
		
		foreach($catList as $category)
		{
			$productOutput[$categoryURLs[$category].'/'.$products[$key]['url_key']] = $key;
			$productOutput[$categoryURLs[$category].'/'.$products[$key]['url_path']] = $key;
			
		}
	}
	return $productOutput;
}

function utf8_converter($array)
{
    array_walk_recursive($array, function(&$item, $key){
        if(!mb_detect_encoding($item, 'utf-8', true)){
                $item = utf8_encode($item);
        }
    });
 
    return $array;
}

function getProductsData($client,$session,$storeId) {
	
	$productListIDs = array();
	$result = array();
	$temp = $client->catalogProductList($session,array(),$storeId);
	$data = array();

	foreach($temp as $item)
	{
		if(count($item->category_ids)>0)
		{
			$productListIDs[] = $item->product_id;
		}
	}
	
	foreach($productListIDs as $key => $item)
	{
			$result[] = $client->catalogProductInfo($session,$item,$storeId);
	}
	
	
	
	foreach($result as $key => $item)
	{
		$product = isset($item->description) ? substr(strip_tags($item->description),0,255) : NULL;
		$title = isset($item->meta_title) ? $item->meta_title : $item->name;
		$inheritedtitle = isset($item->meta_title) ? "FALSE" : "TRUE";
		$description = isset($item->meta_description) ? $item->meta_description : $product;
		$inheriteddesc = isset($item->meta_description) ? "FALSE" : "TRUE";
		$category = $item->category_ids;
		
		
		$data[$item->product_id] = array("product_id" => $item->product_id, "sku" => $item->sku, "type" => $item->type, "name" => $item->name, "url_key" => $item->url_key, "url_path" => $item->url_path, "category_id" => $category, "meta_title" => $title, "i_title" => $inheritedtitle, "meta_description" => $description, "i_desc" => $inheriteddesc);
		
		
		
	}
	return $data;
	
}

function getTheTitles($client,$session,$storeId,$products) {
	// wasn't needed.
	foreach($products as $product)
	{
		if($product['meta_title'] == NULL)
		{
			
		}	
		
		
	}
	
}

function getPath($storage,$key)
{
$paths = array();
	
	foreach($storage as $index => $item)
	{
		$paths[] = parse_url($item[$key], PHP_URL_PATH);
	}
	return $paths;
}

function getField($storage,$key)
{
$fields = array();
	
	foreach($storage as $index => $item)
	{
		if(array_key_exists($key,$storage))
		{
			$fields[$index] = filter_var($item[$key], FILTER_SANITIZE_STRING);
		}
		else $fields[$index] = "<em>NULL</em>";
	}
	return $fields;
}


function buildDropDown($row,$options)
{
	$output = array();
	$selected = "";
	if(isset($options['Null']))
	{
		return "<em>Blank Field</em>";
	}
	$output[0] = "Unused";
	if(isset($options['URL']))
	{
		$output[1] = "URL";
		$selected = 1;
	}
	else {	
		
		$output[2] = "SKU";
		$output[3] = "Meta Title";
		$output[4] = "Meta Description";
			
		if(isset($options['Number']))
		{
			$output[5] = "Category ID";
			$output[6] = "Product ID";
			
		}

	
	}
	$returntext  = "<select id=\"chooser\" name=\"field_".$row."\">";
	foreach($output as $key => $item)
	{
		
		$ph = $key == $selected ? " selected" : "";
		
		$returntext .= "\n\t";
		$returntext .= "<option value=\"".$key."\"".$ph.">".$item."</option>";
				
	}
	$returntext .= "\n</toption>";
	return $returntext;
	
}

function findfirstURL($array)
{
	global $output;
	
	$current = $output['id'];
	echo "<h1>Sample line</h1>";
	echo "<form action=\"process-step-2.php\" method=\"post\"><table id=\"sample\">
	";
	$track = array();
	
	$counter = 0;
	foreach($array as $item)
	{
		
		$options = array();
		if(strlen($item)==0)
		{
			
			$item = "<em>NULL</em>";
			$options["Null"] = TRUE;
			
		}
		echo "<tr><td class=\"";
		
		if(filter_var($item, FILTER_VALIDATE_URL))
		{
			$options["URL"] = TRUE;
			echo "bold\" id=\"".$counter."\"";
			$item = "<a href=\"".$_SERVER['PHP_SELF']."?id=".$current."&sel=".$counter."\">".$item."</a>";
			$track[] = $counter;
			
			//echo $counter."\n";
		}
		else if(filter_var($item, FILTER_VALIDATE_INT))
		{
			echo "ident\"";
			$options["Number"] = TRUE;
			
			//echo $counter."\n";
		}
		else 
		{
			echo "normal\"";
		}
		echo ">".$item."</td><td>".buildDropDown($counter,$options)."</td></tr>";
		
		$counter++;
	}
	
	
	echo "<input type=\"submit\"></table></form>";
	
	if(count($track)==0)
	{
		echo "\n<h2>No URLs found</h2>";
		echo "\n<p>You're wasting my time bro.</p>";
	}
	else if (count($track)==1)
	{
		echo "\n<h2>Only 1 URL field found</h2>";
		echo "\n<p>We can proceed</p>";
	}
	else 
	{
		echo "\n<h2>".count($track)." URL fields found</h2>";
		echo "\n<p>You need to choose which is the page URL</p>";
	}
}



function platformSlashes($path) {
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        $path = str_replace('/', '\\', $path);
    }
    return $path;
}

function checkSettings()
{
	if ( !isset($_POST["siteURL"],$_POST["login"],$_POST["password"]) ) 
	{
		safeSession();
		
		if(!isset($_SESSION['login'],$_SESSION['siteURL'],$_SESSION['password']))
		{	
			header('Location: '.bounceBack("noauth",1));		
		}
		session_write_close();
		return true;
		
		
	}
	return false;
}

function safeSession() {
	if (session_status() == PHP_SESSION_NONE) 
	{
		session_start();
	}
}

$urls = array("http://","https://");

$mimes = array(
    'text/csv',
    'text/plain',
    'application/csv',
    'text/comma-separated-values',
    'application/excel',
    'application/vnd.ms-excel',
    'application/vnd.msexcel',
    'text/anytext',
    'application/octet-stream',
    'application/txt',
);




?>
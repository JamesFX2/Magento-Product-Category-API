<?php

require_once('functions.php');

if ( checkSettings())
{

	
}
else {

	$siteURL = "";
	$login = "";
	$password = "";
	
	$pURL = filter_var($_POST["siteURL"], FILTER_SANITIZE_URL);
	$scheme = (parse_url($pURL,PHP_URL_SCHEME) ? parse_url($pURL,PHP_URL_SCHEME) : "http")."://";
	
	if(strlen(parse_url($pURL,PHP_URL_HOST))>0)
	{
		session_start();
		$siteURL = $scheme.parse_url($pURL,PHP_URL_HOST)."/";
		$login = filter_var($_POST["login"],FILTER_SANITIZE_STRING);
		$password = filter_var($_POST["password"],FILTER_SANITIZE_STRING);
		$_SESSION['login'] = $login;
		$_SESSION['siteURL'] = $siteURL;
		$_SESSION['password'] = $password;
		session_write_close();
		
		
	}
	



}	
	
require_once 'api.php';

$rowcount = 1;

$querys = $_SERVER['QUERY_STRING'];
parse_str($querys, $output);




if(!isset($output['id']))
{
	$result = $client->storeList($session);
	$storeList = array();
	$storeInfo = array();


	foreach($result as $item)
	{
		if($item->is_active == 1)
		{
			$storeList[] = $item->store_id;
			$storeInfo[] = $item;
		}
	}

//print_r($storeList);

	?>
	<html>
	<head><link rel="stylesheet" type="text/css" href="import.css" />
	<body><h1><?= count($storeList); ?> Storefronts Found</h1>
	<ul id="container">
	<?php foreach($storeInfo as $item)
	{
		echo "<a href=\"".$_SERVER["PHP_SELF"]."?id=".$item->store_id."\"><li><ul>";
		echo "<li>Store ID: ".$item->store_id."</li>";
		echo "<li>Code: ".$item->code."</li>";
		echo "<li>Name: ".$item->name."</li>";
		echo "<li>Website ID: ".$item->website_id."</li>";
		echo "<li>Active: ".($item->is_active ? "Yes" : "No")."</li>";
		
		
		echo "</ul></li></a>";
	}
	
	echo "</ul></body></html>";
}
else {

$_SESSION['storeID'] = filter_var($output["id"],FILTER_SANITIZE_NUMBER_INT);

?>
	<html>
	<head><link rel="stylesheet" type="text/css" href="import.css" />
	<body><h1>Upload CSV File</h1>
	<table width="600">
	<form action="process.php?id=<?= $output['id'];?>" method="post" enctype="multipart/form-data">

	<tr>
	<td width="20%">Select file</td>
	<td width="80%"><input type="file" name="file" id="file" /></td>
	</tr>

	<tr>
	<td>Submit</td>
	<td><input type="submit" name="submit" /></td>
	</tr>

	</form>
	</table>


<?php


}
?>

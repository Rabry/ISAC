<?php

	if(!isset($_GET['ajax']))
		include_once 'layout/head.php';
	
	if(isset($_GET['modul']) && $_GET['modul'] == 'mining')
		include_once 'mining.php';
	else if(!isset($_GET['modul']))
	{
		include_once 'mining.php';
		$mining = new Mining();
		echo $mining->vypis_kategorie();
	}
	
	if(!isset($_GET['ajax']))
		include_once 'layout/end.php';
?>
<?php

require_once 'mining.class.php';

$mining = new Mining();

if(isset($_GET['vypis_kategorie']))
	echo $mining->vypis_kategorie();
	
if(isset($_GET['vypis_clanky']))
	echo $mining->vypis_clanky_bonus($_GET['vypis_clanky'], $_GET['dir']);

if(isset($_GET['hledej']))
{
	$mining->nacti_soubor($_GET['typ']);
	echo $mining->hledej($_POST['hledat'], $_GET['typ']);
}

if(isset($_GET['hledej_s_bonusem']))
{
	$mining->nacti_soubor($_GET['typ']);
	if(isset($_GET['hledat']))
		$hledat = $_GET['hledat'];
	else
		$hledat = $_POST['hledat'];
	echo $mining->hledej_s_bonusem($hledat, $_GET['typ']);
}

if(isset($_GET['hledej_s_bonusem_ngram']))
{
	echo $mining->hledej_s_bonusem_ngram($_POST['hledat'], $_GET['typ']);
}

if(isset($_GET['hledej_komplet']))
{
	echo $mining->hledej_komplet($_POST['hledat'], $_GET['typ']);
}

?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require('includes/classes/phppi.php');
	
if (is_file('../version.txt')) {
	$installed_version = file_get_contents('../version.txt');	
} else {
	$installed_version = "1.2.0";
}
	
$version = "1.2.0";
	
$phppi = new PHPPI;
$phppi->vars['installed_version'] = $installed_version;
$phppi->vars['version'] = $version;
$phppi->initialize();
?>
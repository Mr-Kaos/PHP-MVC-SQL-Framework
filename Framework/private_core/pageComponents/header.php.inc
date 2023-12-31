<!DOCTYPE HTML>
<html lang="en">

<?php
/**
 * This script contains all data that is contained in the top of each page.
 * This includes meta tags, style/script links and references, PHP scripts that need to be executed on every page etc.
 */
$theme = isset($_SESSION["applicationTheme"]) ? $_SESSION["applicationTheme"] : 'default';
?>

<head>
	<meta charset="utf-8">
	<title><?= $router->getPageName() ?></title>
	<link rel="icon" type="image/x-icon" href="res/img/favicon.ico">
	<link id="theme" href="res/css/themes/style_<?= $theme ?>.css" rel="stylesheet" type="text/css">
	<link href="res/css/layouts/layout_default.css" rel="stylesheet" type="text/css">
	<script src="https://use.fontawesome.com/1f8670fa31.js"></script>
	<script defer src="res/js/iframeAutoSize.js"></script>
</head>
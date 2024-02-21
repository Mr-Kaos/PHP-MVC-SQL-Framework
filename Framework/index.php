<?php

/**
 * This is front controller for the website. The Web server should be configured to route all requests through this file.
 */

require_once('config.php');
include_once('private_core/controllers/Controller.php');
require_once('private_core/objects/Router.php');

/** Array key used to store table identifiers in the $_SESSION array. */
const SESS_TABLES = "TABLES";

$router = new \EasyMVC\Router($_SERVER['REQUEST_URI']);
$router->navigate();
$controller = $router->getController();

// Header is only outputted if the request requires it.
if (!is_null($head = $router->outputHeader())) {
	include($head);
}

// Display the nav if it is required
if (!is_null($nav = $router->displayNav())) {
	include($nav);
}

// If a message needs to be displayed, display it.
$router->displayMessage();
// Display the requested URI. This could be a view or resource.
include($router->getRequestedURI());

// Display the footer if it is required
if (!is_null($footer = $router->displayFooter())) {
	include($footer);
}
session_write_close();

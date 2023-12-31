<?php

/** 
 * DEFAULT CONFIG FILE FOR The PHP MVC APPLICATION
 * 
 * To modify, please make a copy of this file named "config.php".
 */

/**
 * Authentication Configuration
 */

// Authentication method. Must be either "file" or "database".
define('AUTH_METHOD', 'file');

/**
 * Database Connection
 */
define('REQUIRE_DB', false); // If true, will attempt to connect to a database.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'sa');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'my_database');
define('DB_DRIVER', ''); // The database driver to use to connect to the database. Unused. Currently on sqlsrv is supported.

/**
 * Authentication
 * 
 * If authentication is required for your application, configure the settings below to suit your needs.
 */
define('REQUIRE_AUTH', false); // If authentication is required, set to true. If true and the user is not authenticated, it will redirect to the page specified by AUTH_PAGE.
define('AUTH_PAGE', 'Login'); // The view to direct the user to if they are not authenticated.
define('TIMEOUT_LOGIN', 560); // Login timeout. If user has been inactive for the time set, they will be logged out. Set to 0 for no timeout.

/**
 * Default Site Settings
 */
define('HOME_PAGE', 'Home'); // The view that acts as the main home/landing page of the website. If authentication is required, the user will be redirected to the page defined by AUTH_PAGE.

// ==================================================== //
// THE SETTINGS BELOW ARE UNFINISHED OR NOT IMPLEMENTED //
// ==================================================== //

/**
 *  Email Configuration 
 */
// E-mail address used for the "From" header (notifications)
define('MAIL_FROM', 'replace-me@mail.local');

// E-mail address used for the "Bcc" header to send a copy of all notifications
define('MAIL_BCC', '');

// Mail transport available: "smtp", "sendmail", "mail" (PHP mail function)
define('MAIL_TRANSPORT', 'mail');

define('MAIL_SMTP_HOSTNAME', '');
define('MAIL_SMTP_PORT', 25);
define('MAIL_SMTP_USERNAME', '');
define('MAIL_SMTP_PASSWORD', '');

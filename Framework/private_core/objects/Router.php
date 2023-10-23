<?php

namespace Application;

use Application\Controller\Controller;
use Application\SessionManagement\SessionManager;

include('SessionManager.php');

enum DisplayMode: String
{
		// Displays Navbar, main content and footer.
	case Default = "default";
		// Displays main content only (contents of <body> tag)
	case BodyOnly = "none";
		// Displays the navbar and main content. Footer is not displayed
	case NavAndMain = "navOnly";
		// Displays the Main content and the footer. Navbar is not displayed.
	case FooterAndMain = "footerOnly";
		// Used when displaying a resource such as a JavaScript file, image or other non-HTML file.
	case Resource = "resource";

	public function getByName(?string $name): ?DisplayMode
	{
		$mode = DisplayMode::Default;
		foreach (DisplayMode::cases() as $case) {
			if ($case->value == $name) {
				$mode = $case;
				break;
			}
		}

		return $mode;
	}
}

const SITE_NAME = 'FrameworkDemo';
const VIEW_DIR = 'private_core/views/';
const CONTROLLER_DIR = 'private_core/controllers/';
const MODAL_MESSAGE = "ModalResponse";
const APP_NAME = '/FrameworkDemo/';

/**
 * Router Class
 * 
 * This class contains the logic that parses a request from the client and retrieves the required resource(s) from the web server.
 * 
 */
class Router
{
	private array $requestParts;
	private array $getVars;
	private string $title;
	private string $page;
	private string $url;
	private string $destination;
	private ?Controller $controller;
	private DisplayMode $DisplayMode;
	private string $mode;
	private string $requestedURI;

	const EXPIRE_TIME_LIMIT = 60; // The time limit for if a user is inactive for too long.
	const SESSION_TOKEN_EXPIRY = 15; // The time limit from the start of a session where a session token will expire and be replaced with a new one. Session Hijacking prevention.

	/**
	 * Router constructor. Constructed on each page load.
	 * Takes the Request URI and parses it to determine what page is requested by the client.
	 * @param string $request The requested URI as provided by $_SESSION['REQUEST_URI'].
	 */
	public function __construct(string $request)
	{
		$page = str_replace([APP_NAME, '.php'], '', $request);
		$page = substr($page, 0, 1) == '/' ? substr($page, 1) : $page;
		$this->url = $page;
		$this->page = $this->url == '/' ? substr($page, 1, strpos($page, '?')) : substr($page, 0, strpos($page, '?') ? strpos($page, '?') : null);
		$this->requestParts = explode('/', $page);
		$this->getVars = $_GET;
		$this->DisplayMode = DisplayMode::Default;
		$this->title = SITE_NAME;
		$this->redirectCleansedURL();
		$this->getRequestedContent();
	}

	/**
	 * Returns the URI the navbar if the display mode requires it.
	 * @return The URI of the nav file. Should be used in an include() statement.
	 */
	public function displayNav(): ?string
	{
		$nav = null;

		if ($this->DisplayMode == DisplayMode::Default || $this->DisplayMode == DisplayMode::NavAndMain) {
			$nav = "private_core/pageComponents/nav.php.inc";
		}

		return $nav;
	}

	/**
	 * Determines if the footer needs to be displayed. If it does, returns the URI of the footer so it can be used in an include() call.
	 * 
	 * @return string|null The URI of the footer, if required. Else, returns null.
	 */
	public function displayFooter(): ?string
	{
		$footer = null;

		if ($this->DisplayMode == DisplayMode::Default || $this->DisplayMode == DisplayMode::FooterAndMain) {
			$footer = "private_core/pageComponents/footer.php.inc";
		}

		return $footer;
	}

	/**
	 * Retrieves the controller object, if one exists for the current page.
	 */
	public function getController(): ?Controller
	{
		return $this->controller;
	}

	/**
	 * Returns the URI of the resource requested by the client. This could be a page from a view or a file from the res/ directory.
	 */
	public function getRequestedURI(): string
	{
		return $this->requestedURI;
	}

	/**
	 * Returns a href for the specified page.
	 * If the hyperlink is built in a page that is within a subfolder of a view, e.g. "Orders/new", the href built will be a relative reference to the upper folder.
	 * 
	 * @param string $route The page from a view to route to.
	 */
	public function route(string $page): string
	{
		$href = $page;
		$part = $this->requestParts[0];
		$pattern = "/$part\/(.*)/";

		// If the page this was called from is within a folder, add a relative reference to the upper folder.
		if (preg_match($pattern, $_SERVER["REQUEST_URI"], $matches)) {
			$href = '';

			for ($i = 1; $i < count($matches); $i++) {
				$href .= '../';
			}
			$href .= $page;
		}

		return $href;
	}

	/**
	 * Echoes a message set in the session storage.
	 * Only displays one message at a time, with error messages taking priority.
	 * After the message is displayed it is cleared from the session.
	 */
	public function displayMessage(): void
	{
		// Error Message display
		if (isset($_SESSION["MSG_ERROR"])) {
			echo '<div class="alert-box alert-box-important">An error occurred:<br>' . $_SESSION["MSG_ERROR"] . '</div>';
			unset($_SESSION["MSG_ERROR"]);
		} elseif (isset($_SESSION["MSG_WARNING"])) {
			echo '<div class="alert-box alert-box-warning">' . $_SESSION["MSG_WARNING"] . '</div>';
			unset($_SESSION["MSG_WARNING"]);
		} elseif (isset($_SESSION["MSG_STATUS"])) {
			echo '<div class="alert-box alert-box-default">' . $_SESSION["MSG_STATUS"] . '</div>';
			unset($_SESSION["MSG_STATUS"]);
		}
	}

	/**
	 * Constructs and returns the name of the page to appear in the HTML <title> tag.
	 */
	public function getPageName()
	{
		return SITE_NAME . ' - ' . $this->title;
	}

	/**
	 * Returns the URI the header if the display mode requires it.
	 * @return The URI of the header file. Should be used in an include() statement.
	 */
	public function outputHeader(): ?string
	{
		$header = null;

		if ($this->DisplayMode != DisplayMode::Resource) {
			$header = 'private_core/pageComponents/header.php.inc';
		}

		return $header;
	}

	/**
	 * Includes the controller into the page.
	 */
	private function includeController(string $name, string $mode): Controller | null
	{
		$controller = null;
		if (file_exists(CONTROLLER_DIR . "Controller_" . $name . ".php")) {
			require_once(CONTROLLER_DIR . 'Controller_' . $name . '.php');
			$controller = $this->makeController($name, $mode);

			switch ($_SERVER['REQUEST_METHOD']) {
					// If submitting data to database:
				case 'POST':
					$mode = isset($_GET["mode"]) ? $_GET["mode"] : null;
					$redirect = $controller->postData($_POST, $mode);
					$redirect = empty($redirect) ? "Home" : $redirect;
					header('location:' . $redirect);
					die();
					break;
					// If retrieving data from database:
				case 'GET':
					$controller->retrieveData($_GET);
					break;
			}
		}

		return $controller;
	}

	/**
	 * Checks if the URL used to access a page contains a file extension or any invalid strings.
	 * If such artefacts are found, the webpage is redirected to a "cleansed" version of the URL.
	 */
	private function redirectCleansedURL()
	{
		if (str_contains($_SERVER['REQUEST_URI'], '.php')) {
			header('location:' . str_replace('.php', '', $this->page));
		} elseif (preg_match('/\b(\w+)(?:\/\1\b)+/', $this->page, $matches) === 1) {
			$this->page = $matches[1];
			header('location:' . APP_NAME . $this->page);
		} elseif (substr($this->page, -1) === '/') {
			$this->page = substr($this->page, 0, -1);
			header('location:' . APP_NAME . $this->page);
		}
	}

	/**
	 * Retrieves the content requested by the client as set in the constructor.
	 */
	private function getRequestedContent()
	{
		$pattern = '/res\/(.*)/';
		if (preg_match($pattern, $this->url, $matches)) {
			$pattern = '/[.].*/';
			$resource = $matches[1];

			preg_match($pattern, $resource, $matches);
			$contentType = $this->getContentTypeHeader($matches[0]);
			header('Content-Type:' . $contentType);
			$this->DisplayMode = DisplayMode::Resource;

			// For some reason, setting the requested URI here and including it via the index like all other pages does not work. Not sure why.
			// For now, the requested file is included here and the script is killed immediately after.
			$this->requestedURI = 'res/' . $resource;
			include($this->requestedURI);
			die();
		} else if (count($this->requestParts) >= 1 && $this->page !== '') {
			$sessionManager = new SessionManager();
			$valid = $sessionManager->validateRequest($this->page);
			if (!$valid) {
				$this->page = "Login";
				$this->DisplayMode = DisplayMode::Default;
				// } elseif ($this->page == 'Login') {
				// $this->page = "Home";
			}

			$pageData = $this->setupRoute($this->page);
			$this->destination = $pageData['view'];
			$this->mode = $pageData['mode'];

			// Check if the requested page exists. If not, direct to home page
			if (!is_dir(VIEW_DIR . $this->destination) || $this->destination === '') {
				$_SESSION["MSG_WARNING"] = 'DEBUG:<br>Could not find view: "' . VIEW_DIR . $this->destination . '"<hr>Page will redirect to home in release. <a href="Home">Click here</a> to go home.';
				header("location:Home");
			}

			if ($this->mode !== '') {
				$viewFile = VIEW_DIR . $this->destination . '/' . $this->mode . '.php';
			} else {
				$viewFile = VIEW_DIR . $this->destination . '.php';
			}

			if ($this->page == 'Table') {
				$this->DisplayMode = DisplayMode::BodyOnly;
			} else {
				$displayModeName = isset($this->getVars['mode']) ? $this->getVars['mode'] : null;
				$this->DisplayMode = $this->DisplayMode->getByName($displayModeName);
			}

			if (file_exists($viewFile)) {
				$this->requestedURI = $viewFile;
			} else {
				$redirect = VIEW_DIR . $this->destination . '/default.php';
				$_SESSION["MSG_WARNING"] = "[DEBUG] ERROR! Could not locate file: " . $viewFile . ". Loaded \"$redirect\" instead.";
				$this->requestedURI = $redirect;
			}

			$this->controller = $this->includeController($this->destination, $this->mode);
		} else {
			header("location:Home");
			die();
		}
	}

	/**
	 * Writes a new route to display the page.
	 */
	private function setupRoute(string $page): array
	{
		$pageParts = explode('/', $this->page);

		$view = $pageParts[0];
		$mode = "default";
		$get = '';

		$getRequest = explode('?', $pageParts[count($pageParts) - 1]);

		$i = 0;
		foreach (array_unique($pageParts) as $page) {
			$pageParts[$i] = $page;
			$i++;
		}
		if (isset($getRequest[1])) {
			if (count($pageParts) > 1) {
				$mode = $getRequest[0];
			} else {
				$view = $getRequest[0];
			}
			$get = $getRequest[0];
		} else {
			$view = $pageParts[0];
			if (isset($pageParts[1])) {
				$mode = $pageParts[1];
			}
		}

		$exactPage = "$view/$mode";
		$exactPage = str_replace('/default', '', $exactPage);
		if ($mode === 'default') {
			$this->title = "$view";
		} else {
			$this->title = "$view - $mode";
		}

		return ['view' => $view, 'mode' => $mode, 'get' => $get, 'exact' => $exactPage];
	}

	/**
	 * Creates a controller object dynamically based on the given class name
	 * @param string
	 * @param mixed
	 * @return @see{Application\Controller\Controller}
	 */
	private function makeController(string $className, string $mode, mixed $constructorParam = null): Controller
	{
		$class = 'Application\Controller\Controller_' . $className;

		if (is_null($constructorParam)) {
			$constructorParam = $_GET;
		}
		$constructorParam['mode'] = $mode;
		return new $class($constructorParam);
	}

	/**
	 * Returns a MIME type based on the given file extension.
	 * @param string $fileExtension
	 * @return string The MIME type for the specified file extension.
	 */
	private function getContentTypeHeader(string $fileExtension): string
	{
		$mimeType = 'text/html';
		switch ($fileExtension) {
				//Image Types
			case '.bmp':
				$mimeType = 'image/bmp';
				break;
			case '.jpg':
			case '.jpeg':
			case '.jpe':
				$mimeType = 'image/jpeg';
				break;
			case '.png':
				$mimeType = 'image/png';
				break;
			case '.gif':
				$mimeType = 'image/gif';
				break;
			case '.svg':
				$mimeType = 'image/svg+xml';
				break;
			case '.ico':
				$mimeType = 'image/x-icon';
				break;

				// Audio Types
			case '.mp3':
				$mimeType = 'audio/mpeg';
				break;
			case '.m4a':
				$mimeType = 'audio/mp4';
				break;
			case '.mid':
			case '.midi':
				$mimeType = 'audio/midi';
				break;

				// Video Types
			case '.mp4':
			case '.m4v':
			case '.mp4v':
				$mimeType = 'video/mp4';
				break;

				// Text Files
			case '.css':
				$mimeType = 'text/css';
				break;
			case '.js':
				$mimeType = 'application/javascript';
				break;
			case '.json':
				$mimeType = 'application/json';
				break;
			default:
				$mimeType = 'none';
				break;
		}

		return $mimeType;
	}

	/**
	 * Checks if this page is loaded with a message request in the GET variables.
	 * If it is, it creates a JavaScript function that executes on the page load and saves it to the client device for use.
	 */
	public function getWindowMessage()
	{
		if (isset($_GET[MODAL_MESSAGE])) {
			// $messageVars = explode('`', $_GET[MODAL_MESSAGE]);
			echo '<script>window.parent.postMessage("' . $_GET[MODAL_MESSAGE] . '")</script>';
		}
	}
}

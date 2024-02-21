<?php

namespace EasyMVC\Components;

const SESS_NOTIF = 'NOTIFICATIONS';

enum NotificationType
{
	case Default;
	case Warning;
	case Alert;
	case Error;
}

/**
 * Notification Class
 * 
 * This class provides an interface to store a notification and return it as HTML to be displayed to the user.
 */
class Notification
{
	private int $id;
	private string $message;
	private NotificationType $type;
	private bool $allowFadeOut;

	public function __construct(string $message, NotificationType $type = NotificationType::Default, bool $allowFade = true)
	{
		$this->message = $message;
		$this->type = $type;
		$this->id = 0;
		$this->allowFadeOut = $allowFade;

		$this->saveToSession();
	}

	/**
	 * Saves this notification to the session for displaying later.
	 * Sets the ID of this notification to the index of the session variable in which it will be stored in.
	 */
	private function saveToSession()
	{
		if (!isset($_SESSION[SESS_NOTIF])) {
			$_SESSION[SESS_NOTIF] = [];
		}
		$this->id = count($_SESSION[SESS_NOTIF]);
		array_push($_SESSION[SESS_NOTIF], $this);
	}

	/**
	 * Removes this notification from the session.
	 */
	private function removeFromSession()
	{
		if (isset($_SESSION[SESS_NOTIF])) {
			unset($_SESSION[SESS_NOTIF][$this->id]);
		}
	}

	/**
	 * Generates HTML for the given notification so it can be displayed to the user.
	 * Once this function has been called, the notification is removed from the $_SESSION variable.
	 */
	public function displayNotification(): string
	{
		$html = '<div class="alert-box ';

		switch ($this->type) {
			default:
			case NotificationType::Default:
				$html .= 'alert-box-default"';
				break;
			case NotificationType::Warning:
				$html .= 'alert-box-warning"';
				break;
			case NotificationType::Alert:
				$html .= 'alert-box-important"';
				break;
			case NotificationType::Error:
				$html .= 'alert-box-important"';
				$this->message = 'An error occurred:<br>' . $this->message;
				break;
		}

		if (!$this->allowFadeOut) {
			$html .= ' data-noFade="true"';
		}

		$html .= '><span>' . $this->message . '</span><span class="modal-close">&times;</span></div>';

		$this->removeFromSession();
		return $html;
	}
}

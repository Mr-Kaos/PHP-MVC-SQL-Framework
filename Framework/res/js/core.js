/**
 * core.js
 * This file contains JavaScript functions that are included across all pages of the site - i.e. "Global" functions.
 * This file should ONLY contain functions and scripts that can or are to be used on all or the majority of pages.
 * 
 * Additionally, if any changes are made to this file, please remember to minify it to ensure it is used on the website.
 */
"use strict";

// Constant used in notification display
const NOTIFICATION_FADE_TIMEOUT = 3000;

// Constant used in input validation
const ALERT_OK = '-ok';
const ALERT_WARN = '-warn';
const ALERT_ERROR = '-invalid';
const ALERT_NONE = '-none';

/**
 * Resizes the given iFrame within a modal to fit its content inside the modal and in the browser window.
 * @param {Element | String} iFrame The iFrame DOM element or ID of the iFrame to resize.
 * @param {Number} width The width to resize the iFrame to.
 * @param {Number} height The height to resize the iFrame to.
 * 
 * @todo This function mostly works except for a few small bugs:
 * - When the height grows and a scrollbar appears, the clientWidth decreases as the scrollbar takes up some of the width.
 * 	Because of this, when continuing to resize the iFrame, the width will slowly decrease in width.
 * - When the size of the iFrame changes and its height is less than the modal's div height, the modal's div height does not adjust correctly.
 * 	The modal's div will only decrease very slightly until the content in the iFrame matches or becomes greater than the div's height.
 */
function resizeFrame(iFrame) {
	const MIN_MODAL_HEIGHT = 50;

	if (!(iFrame instanceof Element)) {
		iFrame = document.getElementById(iFrame);
	}

	if (iFrame !== null) {
		let iFrameCWidth = iFrame.contentWindow.document.body.clientWidth;
		let iFrameCHeight = iFrame.contentWindow.document.body.clientHeight;
		let iFrameSWidth = iFrame.contentWindow.document.body.scrollWidth;
		let iFrameSHeight = iFrame.contentWindow.document.body.scrollHeight;

		// let width = (iFrameSWidth < 400) ? 400 : iFrameSWidth;
		let height = (iFrameCHeight < 100) ? 100 : iFrameCHeight;

		if (iFrame.contentWindow.document.body !== null) {
			iFrame.width = '100%';
			iFrame.height = height;
			let modalHeadingHeight = iFrame.parentElement.firstChild.clientHeight;
			let modalPadding = parseInt(window.getComputedStyle(iFrame.parentElement).getPropertyValue('padding').replace('px', ''));

			// ModalHeight variable defines the max height the iFrame can be within the modal.
			// It is the height of the modal div minus the height of the heading div combined with the padding * 3.
			// Multiplying the padding by 3 ensures that the iFrame's height is contained within the modal including padding.
			let modalHeight = iFrame.parentElement.clientHeight - (modalHeadingHeight + (modalPadding * 3));

			// Check if the scrollbar width/height is greater than the client's width or height. If it is, set the height to the scrollbar's.
			// console.log(iFrame, width, height);
			// console.info('iFrame dimensions:', iFrame.width, iFrame.height);
			// console.info("Scroll dimensions:", iFrameSWidth, iFrameSHeight);
			// console.info("Client dimensions:", iFrameCWidth, iFrameCHeight);
			// console.info("Modal dimensions:", iFrame.parentElement.clientWidth, iFrame.parentElement.clientHeight);
			if (iFrameSWidth > iFrameCWidth) {
				// console.warn("scroll width greater", iFrame.width, iFrameSWidth - iFrameCWidth);
				iFrame.width = iFrameSWidth + (iFrameSWidth - iFrameCWidth);
				// console.warn("width now", iFrame.width);
			}

			if (iFrameSHeight > (modalHeight) && modalHeight >= MIN_MODAL_HEIGHT) {
				// console.warn('iFrame height is greater than the modal\'s height:', iFrameCHeight, '>', modalHeight);
				iFrame.height = modalHeight - 20;
			}

			// If the new width is less than the old width, keep the old width.
			if (iFrame.width < iFrameCWidth) {
				// console.warn("keeping old width");
				iFrame.width = iFrameCWidth;
			}
			// console.info('iFrame final dimensions:', iFrame.width, iFrame.height);
		}
	} else {
		console.error('Could not resize iFrame as the specified iFrame could not be found on the page.');
	}
}

/**
 * Manages the dynamic display of notifications.
 */
function notificationDisplay() {
	let notificationDiv = document.getElementById("notification_bubbles");

	if (notificationDiv != null) {
		let i = 0;
		let btnClose = null;
		for (i; i < notificationDiv.childElementCount; i++) {
			if (notificationDiv.children[i].getAttribute('data-noFade') == null) {
				setTimeout(fadeOut, NOTIFICATION_FADE_TIMEOUT + (i * 1200), notificationDiv.children[i]);
			}
			if ((btnClose = notificationDiv.children[i].lastChild) != null) {
				btnClose.addEventListener('click', function (e) { removeNotification(e.target.parentElement); });
			}
		}
	}

	/**
	 * 
	 * @param {Element} element The notification element to fade out.
	 */
	function fadeOut(element) {
		element.style.opacity = 1;
		let interval = setInterval(function () {
			element.style.opacity -= 0.01;

			if (element.style.opacity <= 0) {
				removeNotification(element);
			}
		}, 10, element);
		setTimeout(function () { clearInterval(interval, NOTIFICATION_FADE_TIMEOUT); }, 2000);
	}

	/**
	 * Removes the given notification element from the DOM.
	 * @param {Element} element 
	 */
	function removeNotification(element) {
		element.remove();
	}
}
const NotificationTypes = {
	Default: "default",
	Warning: "warning",
	Alert: "alert",
	Error: "error"
};
/**
 * display a new notification on the current page
 * @param {String} message the message that should be displayed in the notification 
 * @param {NotificationTypes} type the type of notification to display
 * @param {boolean} allowFade toggles if the notification should fade after the set time; defaults to true.
 * 
 */
function addNotification(message, type, allowFade = true) {
	if (message != null && typeof message == "string" && message.trim() != "") {
		let notification = document.createElement("div");
		notification.classList.add("alert-box");
		switch (type) {
			case NotificationTypes.Alert:
				notification.classList.add('alert-box-important');
				break;
			case NotificationTypes.Warning:
				notification.classList.add('alert-box-warning');
				break;
			case NotificationTypes.Error:
				notification.classList.add('alert-box-important');
				notification.innerHTML = "An error occurred:<br>";
				break;
			default:
				notification.classList.add('alert-box-default');
				break;
		}
		if(!allowFade)
			notification.setAttribute("data-noFade","true");

		let notificationMessage = document.createElement("span");
		notificationMessage.innerHTML = message;
		let notificationClose = document.createElement("span");
		notificationClose.classList.add("modal-close");
		notificationClose.innerHTML = "&times;";
		notification.appendChild(notificationMessage);
		notification.appendChild(notificationClose);
		let notificationDiv = document.getElementById("notification_bubbles");
		notificationDiv.appendChild(notification);
		notificationDisplay();
	}
}

function setCookie(name, value, expiryDays) {
	const date = new Date();
	date.setTime(date.getTime() + (expiryDays * 24 * 60 * 60 * 1000));
	let expiry = "expires=" + date.toUTCString();
	document.cookie = `${name}=${value};${expiry};path=/WarRoom/;SameSite=strict`;
}

function getCookie(name) {
	name += "=";
	let decodedCookie = decodeURIComponent(document.cookie);
	let ca = decodedCookie.split(';');
	for (let i = 0; i < ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

/**
 * Adds an event listener to the input element based on its type.
 * @param {Element} input The input element
 */
function addValidationListeners(input) {
	if (input !== undefined) {
		switch (input.type) {
			case "number":
				let prevInput = null;
				if (prevInput === null) {
					prevInput = input.value;
				}
				input.addEventListener("keyup", function (e) {
					if (!isNaN(parseInt(e.key)) || e.key === '.') {
						prevInput = input.value;
					}
					validateNumericInput(input, prevInput)
				}, false);
				input.addEventListener("change", function () {
					prevInput = input.value;
					validateNumericInput(input, prevInput)
				});
				break;
			case "email":
				input.addEventListener("input", function () {
					validateEmailInput(input);
				});
				break;
			case "tel":
				input.addEventListener("input", function () {
					validateTelephoneInput(input);
				});
				break;
			case "text":
			case "textarea":
				input.addEventListener("input", function (e) {
					validateStringInput(input, e.data);
				});
				break;
			case "password":
				if (input.name != 'CurrentPassword') {
					input.addEventListener("input", function (e) {
						validatePasswordInput(input);
					})
				}
				break;
			default:
				if (input.tagName === 'SELECT') {
					input.addEventListener("change", function () {
						validateDropdown(input);
					});
				}
		}

		// Add listener to check if it is required.
		if (input.required) {
			input.addEventListener("invalid", function (e) {
				if (window.getComputedStyle(input).height === 'auto') {
					console.warn("The input", input.name, "is required and has not been filled in but is hidden!");
					displayErrorMessage(input, 'This field is required.');
				}
			});
		}
	} else {
		console.warn('Cannot add validation to an undefined input.')
	}

	/**
	 * Checks the passed number input element's value to make sure it does not exceed the minimum and maximum values.
	 * @param {Element} element The DOM Input element to be checked live. Must be of type "number"
	 * @param {Number} prevInput The previous value of the field before an invalid character (NaN) is entered.
	 */
	function validateNumericInput(element, prevInput) {
		let valid = false;
		let val = parseFloat(element.value);
		let min = parseInt(element.getAttribute("min"));
		let max = parseInt(element.getAttribute("max"));

		if (isNaN(val) && prevInput != '') {
			element.value = prevInput;
			displayErrorMessage(element, "Only numerical values are allowed here.");
		} else {
			valid = true;
			// If the step is less than 1 remove prepending 0s if they exist.
			if (element.step == 1) {
				element.value = val;
			}
			if (val > max)
				element.value = max;
			else if (val < min)
				element.value = min;
			displayErrorMessage(element);
		}

		return valid;
	}

	/**
	 * Checks the given element's value to ensure it's byte size does not exceed the length attribute of the input field.
	 * @param {Element} element The DOM input element to be checked live. Must be of type "text" or "textarea".
	 */
	function validateStringInput(element, newChar) {
		let valid = false;
		let value = element.value;
		let dataSize = new Blob([value]).size;
		let maxDataSize = (element.maxLength != -1) ? element.maxLength : 9999;

		if (dataSize > maxDataSize) {
			element.value = value.substring(0, value.length - newChar.length);
			displayErrorMessage(element, "The input has been truncated as it exceeded the maximum data size allowed.", ALERT_WARN);
		} else if (element.required && value == '') {
			displayErrorMessage(element, "A value must be provided.");
		} else {
			displayErrorMessage(element);
			valid = true;
		}

		return valid;
	}

	/**
	 * Validates the given input to check if it's a valid email.
	 * @param {Element} element The DOM input element to be checked live. Must be of type "text" or "textarea".
	 * @returns {Boolean}
	 */
	function validateEmailInput(element) {
		let valid = false;
		var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]$/;

		if (!emailPattern.test(element.value)) {
			displayErrorMessage(element, "Invalid email format.", ALERT_ERROR);
		} else {
			displayErrorMessage(element);
			valid = true;
		}

		return valid;
	}

	/**
	 * Validates a password input based on standard password complexity requirements.
	 * Passwords are validated to ensure they are at least 8 characters long and contain alphanumeric characters.
	 * Author: Precious Akande
	 * Modified: Kristian Oqueli Ambrose (7/12/2023) - Added validation with secondary password field, if one exists.
	 * @param {Element} element 
	 * @returns {Boolean} True if valid, else false.
	 */
	function validatePasswordInput(element) {
		let valid = false;
		let siblingField = null;

		var pwdupperreg = /[A-Z]/;
		var pwdlowerreg = /[a-z]/;
		var pwdnumberreg = /[0-9]/;

		if (!pwdupperreg.test(element.value) || !pwdlowerreg.test(element.value) || !pwdnumberreg.test(element.value) || element.value.length < 8) {
			displayErrorMessage(element, "Password is not strong enough");
		} else {
			if (element.id.endsWith('A')) {
				siblingField = document.getElementById(element.id.substring(0, element.id.length - 1) + 'B');
			} else if (element.id.endsWith('B')) {
				siblingField = document.getElementById(element.id.substring(0, element.id.length - 1) + 'A');
			}

			if (siblingField != null) {
				if (element.value !== siblingField.value) {
					displayErrorMessage(element, 'Passwords do not match!');
				} else {
					displayErrorMessage(element);
					displayErrorMessage(siblingField);
				}
			} else {
				displayErrorMessage(element);
				valid = true;
			}
		}

		return valid;
	}

	/**
	 * Validates a telephone input
	 * Author: Precious Akande
	 * @param {Element} element 
	 * @returns {Boolean} True if valid, else false.
	 */
	function validateTelephoneInput(element) {
		let valid = false;
		var phonePattern = /^\+(?:\d{1,3})?(\d{3,})$/;

		if (!phonePattern.test(element.value)) {
			displayErrorMessage(element, "Invalid Phone number format.");
		} else {
			displayErrorMessage(element);
			valid = true;
		}

		return valid;
	}

	/**
	 * Removes any messages on the dropdown input if it's value is not null.
	 * @param {Element} element The dropdown to be validated
	 */
	function validateDropdown(element) {

		if (element.required && (element.value != null || element.value != 'NULL')) {
			displayErrorMessage(element);
		}
	}
}

/**
 * Manages the displaying of messages beside a specified element, typically inputs.
 * If no message is given (i.e. message is null), it is assumed to remove any messages associated with the given element.
 * Else if a message is given, it is assumed that the specified message is to be displayed for the given element.
 * If a message already exists on the given element, the message is replaced with the next one given.
 * 
 * @param {Element} element The element to append or remove an error message from.
 * @param {String} message The message to append to the specified element.
 * @param {String} alertType The type of alert to display beside the error message.
 */
function displayErrorMessage(element, message = null, alertType = ALERT_ERROR) {

	if (element !== null) {
		if (message == null) {
			removeErrorMessage(element);
		} else {
			appendErrorMessage(element, message, alertType);
		}
	} else {
		console.warn('Could not display or hide error message as the target element is null.');
	}

	/**
	 * Appends a span element after the specified element with the specified message.
	 * Used to alert the user of invalid inputs if one is made.
	 * Also adds a red outline to the associated fieldset where the error occurs.
	 * 
	 * @param {Element} element The element to append the message beside. Should be an input element.
	 * @param {String} message The message to be appended next to the element.
	 * @param {String} alertType Optional. The type of alert to present to the user. Alerts can be ALERT_OK, ALERT_WARN or ALERT_ERROR
	 */
	function appendErrorMessage(element, message, alertType = ALERT_ERROR) {
		let associatedFieldset;
		let msgElement = document.getElementById(element.id + "_MSG");
		element.classList.add(`input${alertType}`);

		// Make sure the element being appended does not already exist
		if (msgElement === null) {
			msgElement = document.createElement("span");
			msgElement.id = element.id + "_MSG";
			msgElement.innerText = message;
			element.insertAdjacentElement('afterend', msgElement);
		} else {
			msgElement.innerText = message;
		}
		msgElement.className = `input-text${alertType}`;

		// Find the associated fieldset of the input and style it accordingly.
		if ((associatedFieldset = getInputFieldset(element)) !== undefined) {
			associatedFieldset.classList.add(`input${alertType}`);
			associatedFieldset.classList.remove('clear');
			setTimeout(function () {
				associatedFieldset.classList.remove(`input${alertType}`);
				associatedFieldset.classList.add('clear');
			}, 2000);
		}
	}

	/**
	 * Removes an error message from an input element if one exists.
	 * @param {Element} element 
	 * @returns {Boolean} True if an error message exists and is removed. Else returns false.
	 */
	function removeErrorMessage(element) {
		let removed = false;
		let msgElement = document.getElementById(element.id + "_MSG");
		let associatedFieldset = getInputFieldset(element);
		if (msgElement !== null) {
			msgElement.remove();
			removed = true;
		}
		element.classList.remove("input" + ALERT_ERROR);
		element.classList.remove("input" + ALERT_WARN);
		element.classList.remove("input" + ALERT_OK);

		// Find the associated fieldset of the input and style it accordingly.
		if ((associatedFieldset = getInputFieldset(element)) !== undefined) {
			associatedFieldset.classList.remove("input" + ALERT_ERROR);
			associatedFieldset.classList.remove("input" + ALERT_WARN);
			associatedFieldset.classList.remove("input" + ALERT_OK);
		}
		return removed;
	}

	/**
	 * Finds the fieldset that is the parent of the given input element and returns it.
	 * @param {Element} input The input element to find its fieldset for.
	 * @returns {Element|null} The fieldset if found. Else null.
	 */
	function getInputFieldset(input) {
		let fieldset = undefined;
		const MAX_ITERATIONS = 5;
		let i = 0;

		if (input !== null) {
			while (i < MAX_ITERATIONS && fieldset == null) {
				if (input.parentElement.tagName == 'FIELDSET') {
					fieldset = input.parentElement;
				} else {
					input = input.parentElement;
				}
				i++;
			}
		}
		return fieldset;
	}
}

function init() {
	function setColourScheme() {
		let original = getCookie('colour_scheme');
		setCookie('colour_scheme', (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) ? "dark" : "light", 30);
		if (original !== getCookie('colour_scheme')) {
			window.location = window.location;
		}
	}

	function liveFieldValidation() {
		let inputs = Array.from(document.getElementsByTagName("INPUT"));
		inputs = inputs.concat(Array.from(document.getElementsByTagName("TEXTAREA")));
		inputs = inputs.concat(Array.from(document.getElementsByTagName("SELECT")));

		for (let i = 0; i < inputs.length; i++) {
			addValidationListeners(inputs[i]);
		}
	}

	window.addEventListener('DOMContentLoaded', function (e) {
		let iFrames = document.getElementsByClassName("iframe-auto-resize");
		let anchors = document.getElementsByClassName("parent-redirect");

		for (let i = 0; i < iFrames.length; i++) {
			iFrames[i].addEventListener("load", function () {
				resizeFrame(iFrames[i]);
			});
		}

		// Add an event listener to any elements that need to redirect the parent (top) page.
		for (let i = 0; i < anchors.length; i++) {
			anchors[i].addEventListener("click", function () {
				top.location = anchors[i].getAttribute("href");
			});
		}
	});

	const resizeObserver = new ResizeObserver(entries => {
		for (const entry of entries) {
			if (entry.target.tagName === 'IFRAME') {
				// If the observed target is an iframe, call resizeFrame
				resizeFrame(entry.target);
			}
		}
	});

	// Find all iframes on the page
	const iframes = document.querySelectorAll('iframe');

	// Observe each iframe for resize events using a for loop
	for (let i = 0; i < iframes.length; i++) {
		resizeObserver.observe(iframes[i]);
	}

	liveFieldValidation()
	setColourScheme();
	notificationDisplay();
}

document.addEventListener("load", init());

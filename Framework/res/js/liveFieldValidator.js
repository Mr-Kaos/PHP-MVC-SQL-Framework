/**
 * liveFieldValidator.js
 * 
 * Author: Kristian Oqueli Ambrose
 * Created: 18/11/2022
 * 
 * Description:
 * Performs live data validation on all fields on the active webpage.
 * 
 * Modified:
 * 20/04/2023 - Kristian Oqueli Ambrose
 * 	- Updated numeric field validation to remove entered alpha characters from the input without resetting the whole input.
 * 	- Added String size validation to ensure the data size of the string entered does not exceed what the server can handle.
 */

"use strict";

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
		appendErrorMessage(element, "Only numerical values are allowed here.");
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
		removeErrorMessage(element);
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
	let maxDataSize = element.maxLength;

	if (dataSize > maxDataSize) {
		element.value = value.substring(0, value.length - newChar.length);
		appendErrorMessage(element, "The input has been truncated as it exceeded the maximum data size allowed.");
		element.classList.add("invalid-input");
	} else if (element.required && value == '') {
		appendErrorMessage(element, "A value must be provided.");
		element.classList.add("invalid-input");
	} else {
		removeErrorMessage(element);
		element.classList.remove("invalid-input");
		valid = true;
	}
	
	return valid;
}

//function that checks email format using regex 
function validateEmail(element) {
	let valid = false;
	var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;

	if (!emailPattern.test(element.value)) {
		appendErrorMessage(element, "Invalid email format.");
	} else {
		removeErrorMessage(element);
		valid = true;
	}

	return valid;
}

/**
 * Appends a span element after the specified element with the specified message.
 * Used to alter the user of invalid inputs if one is made.
 * 
 * @param {Element} element The element to append the message beside. Should be an input element.
 * @param {String} message The message to be appended next to the element.
 */
function appendErrorMessage(element, message) {
	if (element !== null) {
		let msgElement = document.getElementById(element.id + "_ALERT");

		// Make sure the element being appended does not already exist
		if (msgElement === null) {
			msgElement = document.createElement("span");
			msgElement.id = element.id + "_ALERT";
			msgElement.className = 'input-alert';
			msgElement.innerText = message;
			element.insertAdjacentElement('afterend', msgElement);
		} else {
			msgElement.innerText = message;
		}
	}
}

//returns true if there is an error message and it has been removed, returns false otherwise
function removeErrorMessage(element) {
	let removed = false;
	if (element !== null) {
		let msgElement = document.getElementById(element.id + "_ALERT");
		if (msgElement !== null) {
			msgElement.remove();
			removed = true;
		}
	}
	return removed;
}

/**
 * Adds an event listener to the input element based on its type.
 * @param {Element} input The input element
 */
function addValidationListener(input) {
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
			input.addEventListener("change", function (e) {
				prevInput = input.value;
				validateNumericInput(input, prevInput)
			});
			break;
		case "text":
			if(input && input.id === "email")
			{
				input.addEventListener("input", function (e) {
					validateEmail(input);
				});
			}
			break;
		case "textarea":
			input.addEventListener("input", function (e) {
				validateStringInput(input, e.data);
			});
			break;
	}
}

/**
 * When the page is loaded, this method loops though all inputs on the page and adds validation event listeners to them.
 */
function init() {
	let inputs = Array.from(document.getElementsByTagName("INPUT"));
	inputs = inputs.concat(Array.from(document.getElementsByTagName("TEXTAREA")));

	inputs.forEach(input => {
		addValidationListener(input);
	});
}

window.addEventListener("load", init());
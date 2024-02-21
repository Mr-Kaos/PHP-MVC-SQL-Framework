/**
 * FormManagement.js
 * 
 * This file contains a few function to help the management of tables.
 * 
 * Author: Kristian Oqueli Ambrose
 * Created: 05/12/2022
 * Last Modified: 02/10/2023
 * 
 */
"use strict"

/**
 * Gets the form element that is the parent of the given element.
 * If no form element is found, null is returned.
 * @param {DOM Element} element The element that is a child of the form element.
 */
let dropdownEventHandlers = Array();
function getForm(element) {
	let form = element.parentElement;

	if (form.tagName != 'FORM') {
		form = element.parentElement.parentElement;
	}

	if (form.tagName != 'FORM') {
		form = null;
	}

	return form;
}

/**
 * Moves a pill from its current pillbox to the pillbox passed in
 * @param {DOM Element} element the pill that has been clicked on
 * @param {DOM Element} pillboxOne the first pillbox it toggles between
 * @param {DOM Element} pillboxTwo the second pillbox it toggles between
 * @param {DOM Element} buttonOne the text to display on the button when in pillboxOne
 * @param {DOM Element} buttonTwo the text to display on the button when in pillboxTwo
 */
function PillBoxToggle(element, pillboxOne, pillboxTwo, buttonOne = "🢃", buttonTwo = "🢁") {
	let newPill = null;
	if (element.parentElement == pillboxOne) {
		newPill = movePillBetweenBoxes(element, pillboxTwo);
		newPill.getElementsByClassName("pill-btn-toggle")[0].innerHTML = buttonTwo;
	} else {
		newPill = movePillBetweenBoxes(element, pillboxOne);
		newPill.getElementsByClassName("pill-btn-toggle")[0].innerHTML = buttonOne;
	}
	return newPill;
}

/**
 * Moves a pill from its current pillbox to the pillbox passed in
 * @param {DOM Element} pill the pill to move
 * @param {DOM Element} toBox the pillbox to move the pill to
 */
function movePillBetweenBoxes(pill, toBox) {
	let newPill = pill.cloneNode(true);
	toBox.append(newPill);
	pill.remove();
	return newPill;
}

/**
 * Generates the Deleting X for pillboxes.
 * 
 * @return {Element} Returns the X element for pills.
 */
function pillClose(pill) {
	let pillClose = document.createElement('span');
	pillClose.className = 'pill-btn-del';
	pillClose.innerHTML = '&times;';
	pillClose.onclick = e => removePill(pill);
	return pillClose;
}
function validatePillboxTextInput(input) {
	let currentVal = input.value;
}
/**
 * Adds the selected option in a multi-foreign-key dropdown to its associated input field in a form.
 * 
 * @param {Element} input The input element that is associated with a multiInput field.
 */
function addMultiForeignKeySelection(input) {
	switch (input.tagName) {
		case "SELECT":
			if (input.selectedIndex != null) {
				let selectedValue = input.options[input.selectedIndex];
				let pillBox = document.getElementById(input.getAttribute("pillbox"));
				let multiInputId = pillBox.id.replace("pillbox-", "");
				if (selectedValue.value !== "modal_new" && selectedValue.value !== 'NULL') {
					let newPill = document.createElement('div');
					newPill.className = 'pill';
					newPill.setAttribute('dropdown', input.id);
					newPill.innerHTML = input.options[input.selectedIndex].innerHTML;
					let pillValue = document.createElement('input');
					pillValue.name = `multi-${multiInputId}_${selectedValue.value}`;
					pillValue.type = 'hidden';
					pillValue.setAttribute('dropdown', input.id);
					pillValue.value = selectedValue.value;
					let pillClose = document.createElement('span');
					pillClose.className = 'pill-btn-del';
					pillClose.innerHTML = '&times;';
					pillClose.onclick = e => removePill(newPill);
					newPill.prepend(pillValue);
					newPill.append(pillClose);
					pillBox.prepend(newPill);
					input.options[input.selectedIndex].classList.add('hidden');
				}
				input.selectedIndex = 0;
			}
			break;
		case "INPUT":
			let selectedValue = input.value.trim();
			let pillBox = input.parentElement.childNodes[4];
			let id = pillBox.id.replace("pillbox-", "");
			if (document.getElementsByName(id + "_" + selectedValue).length == 0 && selectedValue != "") {
				let newPill = document.createElement('div');
				newPill.className = 'pill';
				newPill.setAttribute('dropdown', input.id);
				newPill.innerHTML = selectedValue;
				let pillValue = document.createElement('input');
				pillValue.name = `multi-${id}_${input.value}`;
				pillValue.type = 'hidden';
				pillValue.setAttribute('dropdown', input.id);
				pillValue.value = selectedValue;
				let pillClose = document.createElement('span');
				pillClose.className = 'pill-btn-del';
				pillClose.innerHTML = '&times;';
				pillClose.onclick = e => removePill(newPill);
				newPill.prepend(pillValue);
				newPill.append(pillClose);
				pillBox.prepend(newPill);
				input.value = "";
			}
			break;
	}
}

/**
 * Removes all pills from the specified dropdown's pillbox.
 * @param {Element} pillbox
 */
function clearPillBox(pillbox) {
	let i = 0;
	let pillCount = pillbox.childElementCount;

	if (pillCount > 0) {
		for (i = 0; i < pillCount; i++) {
			removePill(pillbox.children[0]);
		}
	}
}

/** Removes the selected pill from its container and un-hides its respective dropdown item back to the list.
 * @param {Element} pill The pill element to remove.
*/
function removePill(pill) {
	let pillValue = pill.children[0].value;
	let dropdown = document.getElementById(pill.getAttribute('dropdown'));

	if (dropdown !== null) {
		if (dropdown.tagName == "SELECT") {
			let i = 0;
			for (i; i < dropdown.options.length; i++) {
				if (dropdown.options[i].value == pillValue) {
					dropdown.options[i].className = '';
					pill.remove();
					break;
				}
			}
		} else {
			pill.remove();
		}
	}
}

/**
 * Resets all inputs for non-standard inputs such as pillboxes.
 * @param {Element} form The form to reset
 */
function clearForm(form) {
	let pillboxes = form.querySelectorAll('.pill-box');
	let i = 0;

	for (i; i < pillboxes.length; i++) {
		clearPillBox(pillboxes[i]);
	}
}

/**
 * Ensures that textareas have enough height to fill all text inside them
 * @param {Element} textArea set to null to target all textareas, or specific the element
 */
function textAreaFitContent(textArea = null) {
	function resizeTextArea(txt) {
		txt.style.height = "";
		if (txt.innerHTML != "") {
			txt.style.height = txt.scrollHeight + "px";
		}
	}
	if (textArea == null) {
		let textAreas = document.getElementsByTagName("textarea");
		Array.from(textAreas).forEach(resizeTextArea);
	} else {
		if (textArea.tagName == "textarea") resizeTextArea(textArea);
	}
}


/**  
 * For searchable dropdowns - When the user clicks on the dropdown, toggle between hiding and showing the dropdown content 
 * @param {String} name the searchable dropdown's name
 */
function toggleSearchableDropdown(name) {
	if (document.getElementById(name + "-dropdown-content").classList.contains("show-search-dropdown")) {
		document.getElementById(name + "-dropdown-content").classList.remove("show-search-dropdown");
		document.removeEventListener('click', dropdownEventHandlers[name]);
	} else {
		const clickEventHandler = function (event) {
			const clickedElement = event.target;
			const excludedElement = document.getElementById(name + "-searchable-dropdown-container");
			if (clickedElement !== excludedElement && !excludedElement.contains(clickedElement)) {
				toggleSearchableDropdown(name);
			}
		};
		dropdownEventHandlers[name] = clickEventHandler;
		document.addEventListener('click', clickEventHandler);
		document.getElementById(name + "-dropdown-content").classList.add("show-search-dropdown");
	}
}
/**  
 * For searchable dropdowns - When the user clicks on the "new" button  opens the associated modal for the dropdown
 * @param {Element} dropdown the searchable dropdown
 */
function searchDropdownOpenModel(dropdown) {
	activeDropdown = dropdown;
	let modalName = activeDropdown.getAttribute("modal");
	let modal = document.getElementById(modalName);
	let iFrame = null;
	if (modal !== null) {
		activeModal.modal = modal;
		activeModal.src = activeModal.modal.firstChild.childNodes[1].src;
		displayModal(document.getElementById(modalName));
		iFrame = getIFrame(modal);
	} else {
		console.error('Could not find modal with id of "' + modalName + '"!');
	}
	if (iFrame !== null) {
		iFrame.onload = function () {
			updateNewOptionModal(iFrame);
		}
	}
}
/**  
 * For searchable dropdowns - filters the list of options based on the current text within the search box
 * @param {String} name the searchable dropdown's name
 */
function searchableDropdownSearch(name) {
	var input, filter, ul, li, p, i, div, txtValue;
	input = document.getElementById(name + "-search");
	filter = input.value.toUpperCase();
	div = document.getElementById(name + "-dropdown-content");
	p = div.getElementsByTagName("p");
	for (i = 0; i < p.length; i++) {
		txtValue = p[i].textContent || p[i].innerText;
		if (txtValue.toUpperCase().indexOf(filter) > -1) {
			p[i].style.display = "";
		} else {
			p[i].style.display = "none";
		}
	}
}
/**  
 * For searchable dropdowns - selected the value set when clicked
 * @param {Element} option the element of the selected option
 * @param {String} dropdownName the searchable dropdown's name
 */
function selectSearchableDropdownOption(option, dropdownName) {
	let value = option.getAttribute("optionValue");
	let label = option.innerHTML + '<i class="fa fa-angle-down"></i>';
	let dropdownInput = document.getElementById(dropdownName);
	let dropdownButton = document.getElementById(dropdownName + "-button");
	dropdownInput.value = value;
	dropdownButton.innerHTML = label;
	if (dropdownInput.getAttribute("onchange") != null) {
		var event = new Event('change');
		dropdownInput.dispatchEvent(event);
	}
	toggleSearchableDropdown(dropdownName);
}
/**  
 * For searchable dropdowns - resets the value of a searchable dropdown
 * @param {String} dropdownId the ID of the dropdown to update
 * @param {String} value the value to find 
 * @returns {Element} returns the found element, or null if not found
 */

function searchableDropdownGetOptionByValue(dropdownId, value) {
	let dropdown = document.getElementById(dropdownId + "-dropdown-list");
	for (var i = 0; i < dropdown.childElementCount; i++) {
		if (dropdown.childNodes[i].getAttribute("optionvalue") === value) {
			return dropdown.childNodes[i];
		}
	}
	return null; // Return null if option not found
}
/**  
 * For searchable dropdowns - resets the value of a searchable dropdown
 * @param {String} dropdownId the ID of the dropdown to update
 */
function clearSearchableDropdown(dropdownId) {
	let input = document.getElementById(dropdownId);
	let button = document.getElementById(dropdownId + "-button");
	button.innerHTML = "Select an Option <i class='fa fa-angle-down'></i>";
	input.value = "";
}

/**
 * Initialises all necessary components for this script to function.
 */
function init() {
	let btnReset = document.querySelector('button[type=reset]');
	let pillBoxes = document.querySelectorAll('div[id^=pillbox-]');
	for (let i = 0; i < pillBoxes.length; i++) {
		let prefill = document.getElementById(pillBoxes[i].id.replace('pillbox-', 'prefill_'));
		let input = document.getElementById(pillBoxes[i].getAttribute("input"));
		if (prefill != null) {
			let ii = 0;
			for (ii; ii < prefill.childElementCount; ii++) {
				input.value = prefill.children[ii].innerHTML;
				addMultiForeignKeySelection(input);
			}
		}
	}
	if (btnReset !== null) {
		btnReset.onclick = e => clearForm(btnReset.parentElement);
	}
}
window.addEventListener("load", init());
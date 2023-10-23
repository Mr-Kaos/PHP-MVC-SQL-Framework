/**
 * FormManagement.js
 * 
 * This file contains a few function to help the management of tables.
 * 
 * Author: Kristian Oqueli Ambrose
 * Created: 05/12/2022
 * Last Modified: 03/02/2023
 * 
 */
"use strict"

/**
 * Gets the form element that is the parent of the given element.
 * If no form element is found, null is returned.
 * @param {DOM Element} element The element that is a child of the form element.
 */
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
 * Adds the selected option in a multi-foreign-key dropdown to its associated input field in a form.
 * 
 * @param {Element} dropdown The drop-down element that is associated with a multi-foreign key field.
 */
function addMultiForeignKeySelection(dropdown) {
	let selectedValue = dropdown.options[dropdown.selectedIndex];
	let pillBox = document.getElementById('pillbox-' + dropdown.id.replace('multi-', ''));

	if (selectedValue.value !== MODAL_OPTION_NEW) {
		let newPill = document.createElement('div');
		newPill.className = 'pill';
		newPill.setAttribute('dropdown', dropdown.id);
		newPill.innerHTML = dropdown.options[dropdown.selectedIndex].innerHTML;
		let pillValue = document.createElement('input');
		pillValue.name = `${dropdown.id}_${selectedValue.value}`;
		pillValue.type = 'hidden';
		pillValue.value = selectedValue.value;
		let pillClose = document.createElement('span');
		pillClose.className = 'pill-btn-del';
		pillClose.innerHTML = '&times;';
		pillClose.addEventListener('click', removePill);

		newPill.append(pillValue);
		newPill.append(pillClose);
		pillBox.append(newPill);

		dropdown.options[dropdown.selectedIndex].classList.add('hidden');
	}

	dropdown.selectedIndex = 0;
}

/** Removes the selected pill from its container and un-hides its respective dropdown item back to the list.
 * @param {Element} pill The pill element to remove.
*/
function removePill(pill) {
	pill = (pill.target.parentElement);
	let pillValue = pill.children[0].value;
	let dropdown = document.getElementById(pill.getAttribute('dropdown'));

	let i = 0;
	for (i; i < dropdown.options.length; i++) {
		if (dropdown.options[i].value == pillValue) {
			dropdown.options[i].className = '';
			pill.remove();
			break;
		}
	}
}

function init() {
// Temp solution to checkboxes not loading 
	// document.querySelectorAll('input[type=checkbox]').forEach(checkbox => {
	// 	if (checkbox.value = 1) {
	// 		checkbox.checked = true;
	// 		checkbox.value = null;
	// 	}
	// });




	window.onchange = e => {
		if (e.target.id.substring(0, 6) == 'multi-') {
			addMultiForeignKeySelection(e.target);
		}
	}
	let pillBoxs = document.querySelectorAll('select[id^=multi-]');
	for (let i = 0; i < pillBoxs.length; i++) {
		console.log(pillBoxs[i].id);
		let prefill = document.getElementById(pillBoxs[i].id.replace('multi-', 'prefill_'));
		if (prefill != null) {
			console.log(prefill);

			let ii = 0;
			for (ii; ii < prefill.childElementCount; ii++) {
				pillBoxs[i].value = prefill.children[ii].innerHTML;
				addMultiForeignKeySelection(pillBoxs[i]);
			}
		}

	}


}

window.addEventListener("load", init());
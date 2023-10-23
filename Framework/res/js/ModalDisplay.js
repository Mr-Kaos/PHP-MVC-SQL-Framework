/** ModalDisplay.js
 * Author: Kristian Oqueli Ambrose
 * Created: 03/03/2023
 * Description: This script allow a modal to be displayed by passing an element ID into the modal function.
 * 
 * HOW TO USE:
 * For this script to work across any page, it must be included in that page.
 * To toggle a modal, the displayModal() function must be called via an inline event call.
 * That is, any page element that is to show/hide this modal must use an onclick="displayModal(this)" call.
 * 
 * If there are multiple modals on a page, another modal ID can be used.
 */

const MODAL_OPTION_NEW = "modal_new";
let activeModal = { modal: null, src: null };

/**
 * Toggles the display of the specified modal element.
 * @param {Element, String} modalBox - The DOM element that is the modal. This should not be an iFrame element, rather a div. A string of the modal's ID can also be passed.
 */
function displayModal(modalBox) {
	if (!(modalBox instanceof Element)) {
		modalBox = document.getElementById(modalBox);
	}

	if (modalBox !== null) {
		if (modalBox.classList.contains("modal-bg")) {
			// Toggle the modal display.
			if (modalBox.classList.contains("hidden")) {
				modalBox.classList.remove("hidden");

				// The iFrame in a modal box is always the second child in the modal's child.
				if (modalBox.firstChild.childNodes[1].tagName == 'IFRAME') {
					resizeFrame(modalBox.firstChild.childNodes[1]);
					activeModal.modal = modalBox;
					activeModal.src = modalBox.firstChild.children[1].src;
				}
			} else {
				modalBox.classList.add("hidden");

				// Check if the modal is linked to a dropdown. If it is, set the selected option to the first item in the list if the currently selected option is "new".
				let associatedDropdown = document.getElementById(modalBox.id.replace('modal_', ''));
				if (associatedDropdown !== null) {
					if (associatedDropdown.value == MODAL_OPTION_NEW) {
						associatedDropdown.value = associatedDropdown.options.item(0).value
					}
				}
			}
		}
	} else {
		console.error("Failed to retrieve Modal! Was an Element object passed or an ID?");
	}
}

/**
 * Appends a GET value to the form found in the page.
 * This function is only run if it is found to be within an iFrame.
 * @param {Element} iFrame The iFrame that is used in the modal. 
 */
function setModalFormGETRequest() {
	let forms = document.getElementsByTagName("form");

	if (forms.length >= 1) {
		let form = forms[0];
		form.action += '&isModal'
	}
}

/** 
 * The init function just adds an event listener to the page's modal BG.
 */
function init() {
	let activeDropdown = null;

	// If the script is found to be in an iFrame, append a GET value to signify that it is a modal when submitted.
	if (window.self !== window.top) {
		setModalFormGETRequest();
	}

	let modals = document.getElementsByClassName("modal-bg");
	let closeBtn = document.getElementsByClassName("modal-close");
	let closeObjects = Array.from(modals);
		closeObjects.concat(closeBtn);
	for (let i = 0; i < modals.length; i++) {
		modals.item(i).addEventListener("click", function (e) {
			//This allows the user to click on the grey background to close the modal. Currently adds hidden class to anything inside the modal that is clicked.
			if (e.target.className === 'modal-bg') {
				displayModal(e.target.id);
			} else if (e.target.className === 'modal-close') {
				displayModal(e.target.getAttribute('modalID'));
			}
			if (activeDropdown !== null) {
				activeDropdown.selectedIndex = 0;
			}
		});
	}

	// Check if the modal's second child's second element is an iframe
	// if (modals[i].children[0].children[1] !== null) {
	// 	if (modals[i].children[0].children[1].tagName == 'IFRAME') {
	// 		setModalFormGETRequest(modals[i].children[0].children[1]);
	// 	}
	// }

	window.addEventListener('change', (e) => {
		switch (e.target.value) {
			case 'NEW_ITEM':
				activeDropdown = e.target;
				let modalName = e.target.getAttribute('modal');
				let modal = document.getElementById(modalName);
				if (modal !== null) {
					activeModal.modal = modal;
					activeModal.src = activeModal.modal.firstChild.childNodes[1].src;
					displayModal(document.getElementById(modalName));
				} else {
					console.error('Could not find modal with id of "' + modalName + '"!');
				}
				break;
			case 'NEW_LIST':
				activeDropdown = e.target;
				if (document.getElementById("NewListSelector") !== null) {
					displayModal("NewListSelector");
				}
				break;
		}
	});

	window.addEventListener('message', function (e) {
		let newItem = e.data.split('`');
		if (activeDropdown !== null) {
			let newOption = this.document.createElement('option');
			console.log(newItem[0]);
			newOption.value = newItem[0];
			newOption.innerHTML = newItem[1];
			newOption.selected = true;
			activeDropdown.insertBefore(newOption, activeDropdown.lastChild);

			// Call function in tableCreator.js
			if (typeof attributeValueChange === 'function') {
				attributeValueChange(activeDropdown.id.replace('enumList', ''), 'enum');
			}

			if (activeModal.modal !== null) {
				activeModal.modal.firstChild.childNodes[1].src = activeModal.src;
				displayModal(activeModal.modal);
				activeModal.modal = null;
				activeModal.src = null;
			}
		}
	});

	// window.addEventListener("click", (e) => {
	// 	if (e.target.tagName == "BUTTON" && e.target.name == "remove") {
	// 		removeAttributeFromList(e.target);
	// 	} else if (e.target.id == "closeModal") {
	// 		e.target.parentElement.parentElement.remove();
	// 	}
	// });
}

window.addEventListener("load", init());

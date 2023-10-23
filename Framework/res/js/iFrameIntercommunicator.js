/**
 * iFrameIntercommunicator.js
 * 
 * Description:
 * Allows two iFrames on the same page to communicate with each other.
 * 
 * Author: Kristian Oqueli Ambrose
 * 
 * How To Use:
 * Include this script in each iFrame that appears on the same parent page.
 * To have iFrame A change the content within iFrame B (i.e. change its source), simply call the function "sendIFrameMessage()"
 * with a string of the message to be sent, and the ID of iFrame B.
 * 
 * The event listener defined in the init() function will look for messages, and if a valid one is found, it will
 * check to see that the target iFrame ID is valid and then proceed to change its src value to the message.
 */

"use strict";

/**
 * 
 * @param {String} message - A URI/URL to change the target (destination) iFrame's src value to.
 * @param {String} targetIFrameID - The DOM element ID of the target (destination) iFrame.
 */
function sendIFrameMessage(message, targetIFrameID) {
	let data = { msg: message, target: targetIFrameID }
	window.parent.postMessage(data)
}

function init() {
	/**
	 * This event listener listens for data being sent between iFrames.
	 * If a message is received from another iFrame, it will see if the specified iFrame from the message exists and replace the src to the specified message.
	 */
	window.addEventListener("message", function (e) {
		if (e.data.target !== undefined) {
			let targetIFrame = document.getElementById(e.data.target);
			if (targetIFrame !== null) {
				targetIFrame.src = e.data.msg;
			}
		}
	});
}

window.addEventListener('DOMContentLoaded', init);

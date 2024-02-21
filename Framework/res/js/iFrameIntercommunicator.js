/**
 * iFrameIntercommunicator.js
 * 
 * Description:
 * Allows two iFrames on the same page to communicate with each other.
 * 
 * Author: Lachlan Kearney
 * 
 * How To Use:
 * ensure this script is included in any page that will be communicating, ensure it is above any scripts that will be communicating between iframes. Note that this only supports communications between iFrames 1 layer deep (iFrames inside of iFrames are not supported)
 * 1- Setup a function that will run inside iFrame to send communication (Example: redirectTopPageFromiFrame(page) in this script). This should form and send a message. 
 * 2- Create a handling function, this function is run on the top level page. This function should take in the data JSON value sent in the message
 * 3- assign the handling function with a unique messageType using the addMessageListener function
 * */

"use strict";


/**
 * Object to use to send messages between iFrames.
 * @param {string} messageType A unique string to identify the type of message, this is so only 
 * @param {Object} prefillData A JSON object of data to prefill the form with.
 * @param {string} target currently unsured, in future will allow you to target iframes  
 */
function InterIframeMessage(messageType, data, target = "top") {
	this.type = messageType;
	this.target = target;
	this.data = data;

	this.Send = function () {
		let msg = { messageType: messageType, data: data };
		window.parent.postMessage(msg);
	}
}

function inIframe() {
	try {
		return window.self !== window.top;
	} catch (e) {
		return true;
	}
}


function iFrameCommunicator() {
	this.Messages = [];

	this.addMessageListener = function (messageType, handler) {
		if (messageType in this.Messages) {
			console.log("messageType already has a listener")
		} else {
			this.Messages[messageType] = handler;
		}
	}
	this.EventHandler = function (e) {
		if (e.data.messageType != null) {
			if (e.data.messageType in this.Messages) {
				this.Messages[e.data.messageType](e.data.data);
			}
		} else {
			console.log({ "ERROR": "NO MESSAGE TYPE", "Message": e });
		}
	}
	this.Setup = function () {
		let thisiFrameCommunicator = this;
		window.addEventListener("message", function (e) {
			thisiFrameCommunicator.EventHandler(e);
		});
	}

}


/** Example use:
 * Below you can see an example of InterIframe communication.
 * Note that redirectTopPageFromiFrame() is called elsewhere 
 *  
 * 
 */


/**
 * Run inside an iFrame to send the top page to a different page.
 * @param {string} page  A URL 
 */
function redirectTopPageFromiFrame(page) {
	console.log("clicked")
	let msg = new InterIframeMessage("TopPageRedirect", { page: page }); //note that since this message is going to the top page, no taget needs to be specified
	msg.Send();
}

function init() {
	if (!inIframe()) {
		function redirectTopPage(data) {
			console.log(data);
			let page = data.page;
			location.href = page;
		}
		function handleOpenJobOrder(data) {
			if (data.JobOrderId != null) {
				//setup data 
				let Id = data.JobOrderId;
				let iFrameId = "job_selection";
				if (data.iFrameId != null) iFrameId = data.iFrameId;
				let page = "Jobs/item?mode=none&Job=";
				if (data.isOrder) page = "Orders/item?mode=none&OrderId="
				//get iFrame
				let iframe = document.getElementById(iFrameId);
				// console.log("./" + page + "/item?mode=none&Job=" + Id);
				iframe.src = "./" + page + Id;
				let selected = document.getElementById("currentOrder");
				if (selected != null) {
					selected.value = Id;
				}
				console.log(page);

			}
		}
		function openProductModal(data) {
			if (data.componentId !== undefined && data.jobId !== undefined && data.ReferenceType !== undefined) {
				let modal = document.getElementById("iFrame_ProductModal");
				modal.src = "./Products/JobProduct?mode=none&Product=" + data.componentId + "&Id=" + data.jobId + "&RefType=" + data.ReferenceType;
				displayModal("Modal_ProductDetails");
			}
		}

		function openAddProductModal(data) {
			let modal = document.getElementById("iFrame_AddProductModal");
			modal.src = "./Orders/AddProduct?mode=none&Customer=" + data.Customer + "&Machine=" + data.Machine + "&OrderId=" + data.JobId + "&Setting=" + data.Setting;
			displayModal("Modal_AddProduct");
		}

		function openDeleteOrderConfirmationModal(data) {
			if (data.OrderId !== undefined) {
				// console.log("we get here");
				// let modal = document.getElementById("iFrame_DeleteOrderModal");
				// modal.src = "./Orders/delete?mode=none&OrderId=" + data.OrderId + "&DeleteProduct=1";
				displayModal("Modal_ConfirmDelete");
			}
		}

		let myIframeCommunicator = new iFrameCommunicator();
		myIframeCommunicator.Setup();
		myIframeCommunicator.addMessageListener("TopPageRedirect", redirectTopPage)
		myIframeCommunicator.addMessageListener("OpenJobOrder", handleOpenJobOrder)
		myIframeCommunicator.addMessageListener("OpenProductModal", openProductModal)
		myIframeCommunicator.addMessageListener("OpenAddProductModal", openAddProductModal)
		myIframeCommunicator.addMessageListener("OpenDeleteOrderConfirmationModal", openDeleteOrderConfirmationModal)

	}
	/**
	 * This event listener listens for data being sent between iFrames.
	 * If a message is received from another iFrame, it will see if the specified iFrame from the message exists and replace the src to the specified message.
	 */

}

window.addEventListener('DOMContentLoaded', init);




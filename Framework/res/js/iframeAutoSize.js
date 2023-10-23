/**
 * iFrameAutoSize.js
 * Author: Kristian Oqueli Ambrose
 * Created: 07/02/2023
 * Modified: 03/03/2023
 * 
 * Description:
 * Automatically resizes iFrames with the "iframe-auto-resize" class selector to match their content size.
 * 
 * Modifications:
 * - 03/03/2023 -
 * 	Added listener for hyperlinks that need to redirect the parent page.
 */
"use strict";

const HEIGHT_APPEND = 20;
//buffer parameters must be decimal values representing percentages eg .6 = 60% added to the size. 

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
	if (!(iFrame instanceof Element)) {
		iFrame = document.getElementById(iFrame);
	}

	if (iFrame !== null) {
		let iFrameCWidth = iFrame.contentWindow.document.body.clientWidth;
		let iFrameCHeight = iFrame.contentWindow.document.body.clientHeight;
		let iFrameSWidth = iFrame.contentWindow.document.body.scrollWidth;
		let iFrameSHeight = iFrame.contentWindow.document.body.scrollHeight;
		
		let width = (iFrameSWidth < 400) ? 400 : iFrameSWidth;
		let height = (iFrameCHeight < 100) ? 100 : iFrameCHeight;

		if (iFrame.contentWindow.document.body !== null) {
			iFrame.width = width;
			iFrame.height = height;
			let modalHeadingHeight = iFrame.parentElement.firstChild.clientHeight;
			let modalPadding = parseInt(window.getComputedStyle(iFrame.parentElement).getPropertyValue('padding').replace('px', ''));

			// ModalHeight variable defines the max height the iFrame can be within the modal.
			// It is the height of the modal div minus the height of the heading div combined with the padding * 3.
			// Multiplying the padding by 3 ensures that the iFrame's height is contained within the modal including padding.
			let modalHeight = iFrame.parentElement.clientHeight - (modalHeadingHeight + (modalPadding * 3));

			// Check if the scrollbar width/height is greater than the client's width or height. If it is, set the height to the scrollbar's.
			// console.info('iFrame dimensions:', iFrame.width, iFrame.height);
			// console.info("Scroll dimensions:", iFrameSWidth, iFrameSHeight);
			// console.info("Client dimensions:", iFrameCWidth, iFrameCHeight);
			// console.info("Modal dimensions:", iFrame.parentElement.clientWidth, iFrame.parentElement.clientHeight);
			if (iFrameSWidth > iFrameCWidth) {
				// console.warn("scroll width greater", iFrame.width, iFrameSWidth - iFrameCWidth);
				iFrame.width = iFrameSWidth + (iFrameSWidth - iFrameCWidth);
				// console.warn("width now", iFrame.width);
			}

			if (iFrameSHeight > (modalHeight)) {
				// console.warn('iFrame height is greater than the modal\'s height:', iFrameCHeight, '>', modalHeight);
				iFrame.height = modalHeight;
			}

			// If the new width is less than the old width, keep the old width.
			if (iFrame.width < iFrameCWidth) {
				// console.warn("keeping old width");
				iFrame.width = iFrameCWidth;
			}
			// console.info('iFrame final dimensions:', iFrame.width, iFrame.height);
			// console.log(' ');
		}
	} else {
		console.error('Could not resize iFrame as the specified iFrame could not be found on the page.');
	}
}

function init() {
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
}

document.addEventListener("DOMContentLoaded", init());

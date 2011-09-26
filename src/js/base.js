/**
 * This function generates a jQueryUi
 * error notification div with provided
 * error string
 * 
 * @depends {Library} jQuery, jQuery UI
 * @param {Object} oElement is the dialog to change
 * @param {String} sErrorText is the error string we wish to display
**/
function whvGenerateError(oElement, sErrorText) {
	
	// Store previous content
	var sOriginalHtml = oElement.html();
	
	// Create a jQueryUi error notification
	var sErrorHtml  = '<div class="ui-widget"><div class="ui-state-error ui-corner-all" style="padding: 0 .7em;">';
		sErrorHtml += '<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>';
		sErrorHtml += '<strong>Error</strong><br><p>' + sErrorText + '</p></p></div></div>';
		
	// Change the title of the dialog	
	oElement.dialog('option', 'title', 'An error has occurred ...');
	
	// Change the button set of the dialog
	oElement.dialog('option', 'buttons', {
		'Acknowledged': function() {
			
			// Close the dialog
			oElement.dialog('close');
		}
	});
	
	// Change the content of the dialog
	oElement.html(sErrorHtml);
}

/**
 * This function generates a jQueryUi
 * notification div inside of an also
 * created overlay div with the 
 * provided text
 * 
 * @depends {Library} jQuery, jQuery UI
 * @param {Object} oElement is the element to change
 * @param {String} sNotificationText is the text string we wish to display
**/
function whvGenerateNotice(oElement, sNotificationText) {
	
	// Build the overlay HTML
	var sOverlay  = '<div class="ui-widget"><div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;">'; 
		sOverlay += '<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>';
		sOverlay += '<strong>Notice</strong><br><p>' + sNotificationText + '</p></p></div></div>';
		
	// Apply overlay
	oElement.html(sOverlay);
}

/**
 * This function validates a form based
 * on the array of field objects sent 
 *
 * @depends {Library} jQuery, jQuery UI
 * @param {Object} oFields is a dictionary of field objects
 * @return {Object} oReturn is an object of failed fields and overall validation pass/fail
**/
function whvValidateForm(oFields) {
	
	// create the return placeholder
	var oReturn = {
		bIsValid: false, 
		aFailedFields: new Array()
	};
	
	// loop through each of the fields
	// and test them
	jQuery.each(oFields, function(sIdentifier, oField) {
		
		// Check to see if the
		// field is required
		if (oField.bIsRequired === true) {
			
			// Set the field
			var oFld = jQuery('#' + sIdentifier);
			
			// Test the field
			if ((oFld.val() == '') || (oFld.val() == null) || (oFld.val() == undefined)) {
			
				// This field has failed, 
				// let the caller know
				oReturn.bIsValid = false;
				oReturn.aFailedFields.push(sIdentifier);
					
			}
		}
	});
	
}

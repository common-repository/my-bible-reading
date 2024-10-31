/**
 * Toggle Class To Element On Click
 **/
function jswj_mbr_reminder_checkbox( jswj_checkbox ) {
	var elements = document.getElementsByClassName('mbr_reminder_row');

	for (var i = 0, len = elements.length; i < len; i++) {
		elements[i].classList.toggle('mbr_show_reminder_fields');
	}
}
function grbbHandleShortcode(id) {
	var input = document.querySelector('#grbbFrontShortcode-' + id + ' input');
	var tooltip = document.querySelector('#grbbFrontShortcode-' + id + ' .tooltip');
	input.select();
	input.setSelectionRange(0, 30);
	document.execCommand('copy');
	tooltip.innerHTML = wp.i18n.__('Copied Successfully!', 'business-review');
	setTimeout(() => {
		tooltip.innerHTML = wp.i18n.__('Copy To Clipboard', 'business-review');
	}, 1500);
}
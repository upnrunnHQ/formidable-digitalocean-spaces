(function ($) {
	"use strict";

	const largeFileSize = 50 * 1024 * 1024;

	$(document).ready(function () {
		$(`#file21_dropzone`)
			.get(0)
			.dropzone.on("addedfile", (file) => {
				if (file.size >= largeFileSize) {
					alert(formidable_digitalocean_spaces.wait_message);
				}
			});
	});
})(jQuery);

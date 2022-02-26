(function ($) {
	"use strict";

	const largeFileSize = 50 * 1024 * 1024;

	$(document).ready(function () {
		$(`#file${formidable_digitalocean_spaces.upload_field_id}_dropzone`)
			.get(0)
			.dropzone.on("addedfile", (file) => {
				if (file.size >= largeFileSize) {
					alert(formidable_digitalocean_spaces.wait_message);
				}
			});
	});
})(jQuery);

import "./index.scss";

(function ($) {
	"use strict";

	const largeFileSize = 50 * 1024 * 1024;

	$(document).ready(function () {
		$(".frm_digitalocean_dropzone").each(function () {
			$(this)
				.get(0)
				.dropzone.on("addedfile", (file) => {
					if (file.size >= largeFileSize) {
						alert(formidable_digitalocean_spaces.wait_message);
					}
				});
		});
	});
})(jQuery);
(function ($) {
	"use strict";

	const largeFileSize = 50 * 1024 * 1024;

	$(document).ready(function () {
		$("#file207_dropzone")
			.get(0)
			.dropzone.on("addedfile", (file) => {
				if (file.size >= largeFileSize) {
					alert(
						"Large file is uploading please be patient, take a sip of coffee and breathe."
					);
				}
			});
	});
})(jQuery);

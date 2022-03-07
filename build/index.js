/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
(function ($) {
  "use strict";

  const largeFileSize = 50 * 1024 * 1024;
  $(document).ready(function () {
    $(`#file21_dropzone`).get(0).dropzone.on("addedfile", file => {
      if (file.size >= largeFileSize) {
        alert(formidable_digitalocean_spaces.wait_message);
      }
    });
    $(`#file21_dropzone`).get(0).dropzone.on("complete", file => {
      console.log('ddd');

      if (typeof file.mediaID !== "undefined") {
        if (uploadFields[i].uploadMultiple) {
          jQuery(file.previewElement).append(getHiddenUploadHTML(uploadFields[i], file.mediaID, fieldName));
        }
      }
    });
  });
})(jQuery);
/******/ })()
;
//# sourceMappingURL=index.js.map
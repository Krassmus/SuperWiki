
STUDIP.SuperWiki = {
    merge: function (text1, text2, original) {
        var start1, start2, end1, end2;
        for(var i = 0; i < original.length; i++) {
            if (original[i] !== text1[i]) {
                start1 = i;
                break;
            }
        }
        for(var i = 0; i < original.length; i++) {
            if (original[i] !== text2[i]) {
                start2 = i;
                break;
            }
        }
        for(var i = original.length; i > 0; i--) {
            if (original[i] !== text1[i]) {
                end1 = i;
                break;
            }
        }
        for(var i = original.length; i > 0; i--) {
            if (original[i] !== text2[i]) {
                end2 = i;
                break;
            }
        }
        if (typeof start1 === "undefined") {
            start1 = 0;
        }
        if (typeof start2 === "undefined") {
            start2 = 0;
        }
        if (typeof end1 === "undefined") {
            end1 = 0;
        }
        if (typeof end2 === "undefined") {
            end2 = 0;
        }
        //console.log(start1 + " " + end1 + " | " + start2 + " " + end2);

        //now we sort the carets, so we can begin with the first:
        if (start1 <= start2) {
            if (end1 >= end2) {
                //now we have a dominant version1
                return text1;
            }
        } else {
            if (end2 >= end1) {
                //now we have a dominant version2
                return text2;
            }
            var k;
            k = start2;
            start2 = start1;
            start1 = k;
            k = end2;
            end2 = end1;
            end1 = k;
            k = text1;
            text1 = text2;
            text2 = k;
            //now we have switched carets and texts, so that text1 has earlier changes
        }
        if (end1 <= start2) {
            var text = text1.substr(0, text1.length - (original.length - end1));
            text += text2.substr(text1.length - (original.length - end1));
            return text;
        } else {
            //this is a conflict, take the more changed text as the result
            if (end1 - start1 > end2 - start2) {
                return text1;
            } else {
                return text2;
            }
        }
    },
    /**
     * When a file is dropped into the textarea, it will be uploaded with this function
     * to the server. This function is copied from blubber.
     * @param event
     */
    uploadFileToTextarea: function (event) {
        console.log("ha");
        var textarea = this;
        event.preventDefault();
        var files = 0;
        var file_info = event.originalEvent.dataTransfer.files || {};
        var data = new FormData();

        jQuery.each(file_info, function (index, file) {
            if (file.size > 0) {
                data.append(index, file);
                files += 1;
            }
        });
        if (files > 0) {
            jQuery(textarea).addClass("uploading");
            jQuery.ajax({
                'url': STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/superwiki/page"
                    + "/post_files?cid=" + jQuery("#seminar_id").val(),
                'data': data,
                'cache': false,
                'contentType': false,
                'processData': false,
                'type': 'POST',
                'xhr': function () {
                    var xhr = jQuery.ajaxSettings.xhr();
                    //workaround for FF<4 https://github.com/francois2metz/html5-formdata
                    if (data.fake) {
                        xhr.setRequestHeader("Content-Type", "multipart/form-data; boundary=" + data.boundary);
                        xhr.send = xhr.sendAsBinary;
                    }
                    return xhr;
                },
                'success': function (json) {
                    if (typeof json.inserts === "object") {
                        jQuery.each(json.inserts, function (index, text) {
                            jQuery(textarea).val(jQuery(textarea).val() + " " + text);
                        });
                    }
                    if (typeof json.errors === "object") {
                        alert(json.errors.join("\n"));
                    } else if (typeof json.inserts !== "object") {
                        alert("Fehler beim Dateiupload.");
                    }
                    jQuery(textarea).trigger("keydown");
                },
                'complete': function () {
                    jQuery(textarea).removeClass("hovered").removeClass("uploading");
                }
            });
        }
    }
};

jQuery(function () {
    if (jQuery("#superwiki_edit_form textarea").length > 0) {
        jQuery("#superwiki_edit_form textarea").bind('dragover dragleave', function (event) {
            jQuery(this).toggleClass('hovered', event.type === 'dragover');
            return false;
        }).on("drop", STUDIP.SuperWiki.uploadFileToTextarea);
    }
});
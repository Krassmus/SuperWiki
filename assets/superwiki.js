
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
    alterTextarea: function () {
        var textarea = this;
        jQuery(textarea).on("drop", function (event) {
            event.preventDefault();
            var files = 0;
            var file_info = event.originalEvent.dataTransfer.files || {};
            var data = new FormData();

            var thread = jQuery(textarea).closest("li.thread");
            if (thread && thread.find(".hiddeninfo input[name=context_type]").val() === "course") {
                var context_id = thread.find(".hiddeninfo input[name=context]").val();
                var context_type = "course";
            } else {
                var context_type = jQuery("#context_selector input[name=context_type]:checked").val();
                if ((jQuery("#stream").val() === "course") || jQuery("#context_selector input[name=context_type]:checked").val()) {
                    var context_id = jQuery("#context_selector input[name=context]").val();
                    context_type = context_type ? context_type : "course";
                }
                if (!context_id) {
                    var context_id = jQuery("#user_id").val();
                    context_type = "public";
                }
            }
            jQuery.each(file_info, function (index, file) {
                if (file.size > 0) {
                    data.append(index, file);
                    files += 1;
                }
            });
            if (files > 0) {
                jQuery(textarea).addClass("uploading");
                jQuery.ajax({
                    'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val()
                        + "/post_files?context=" + context_id
                        + "&context_type=" + context_type
                        + (context_type === "course" ? "&cid=" + context_id : ""),
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
        });
    }
};
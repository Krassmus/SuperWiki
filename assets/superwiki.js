
STUDIP.SuperWiki = {
    merge: function (text1, text2, original) {
        var replacements1 = STUDIP.SuperWiki._get_replacements(original, text1);
        var replacements2 = STUDIP.SuperWiki._get_replacements(original, text2);

        //now merge all replacements into one array of conflict-free replacements
        var conflict = false;
        for (var i in replacements1) {
            conflict = false;
            for (var j in replacements2) {
                if ((replacements1[i].start > replacements2[j].end
                    || replacements2[j].start > replacements1[i].end)) {
                    //no conflict
                } else {
                    conflict = true;
                    //now replace old replacement if this bigger
                    if (replacements2[j].text.length < replacements1[i].text.length) {
                        replacements2[j] = replacements1[i];
                    } //else discard replacements1[i]
                    break;
                }
            }
            if (!conflict) {
                replacements2.push(replacements1[i]);
            }
        }

        //now sort that array in ascending order of the start-value:
        replacements2 = _.sortBy(replacements2, function ($r) {
            return $r.start;
        });

        //and now we alter the original text by all replacements one after another
        var index_alteration = 0;
        var text = original;
        for (i in replacements2) {
            text = text.substr(0, replacements2[i].start + index_alteration)
                + replacements2[i].text
                + text.substr(replacements2[i].end + index_alteration);
            index_alteration += replacements2[i].text.length - replacements2[i].end + replacements2[i].start;
        }

        return text;
    },
    _get_replacements: function (original, text) {
        var replacements = [];
        var replacement = {};
        var text_start, text_end;
        for(var i = 0; i < original.length; i++) {
            if (original[i] !== text[i]) {
                replacement.start = i;
                text_start = i;
                break;
            }
        }
        for(var i = 0; i < original.length; i++) {
            if ((original[original.length - 1 - i] !== text[text.length - 1 - i])
                    || (original.length - i === replacement.start)) {
                replacement.end = original.length - i;
                text_end = text.length - i;
                break;
            }
        }
        replacement.text = text.substr(text_start, text_end - text_start);
        //We could be more specific and find sub-changes with the levenshtein-algorithm,
        //but for now we keep this simple algorithm.
        replacements.push(replacement);
        return replacements;
    },
    /**
     * When a file is dropped into the textarea, it will be uploaded with this function
     * to the server. This function is copied from blubber.
     * @param event
     */
    uploadFileToTextarea: function (event) {
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
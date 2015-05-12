
STUDIP.SuperWiki = {
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
    },
    requestFullscreen: function () {
        var page = jQuery(".full_wiki_page")[0];
        var presentation = jQuery("#superwiki_presentation")[0];
        var settings = jQuery(page).find(".superwiki_presentation.settings");

        var slides = jQuery(page).html().split(/<div class="superwiki_presentation newpage"[^>]*?><\/div>/);
        var transitions = jQuery(page).find(".superwiki_presentation.newpage");
        jQuery(presentation).html('');
        for (var i in slides) {
            var slide = jQuery('<div class="slide">' + slides[i] + '</div>');
            if (i > 0) {
                slide.data(jQuery(transitions[i - 1]).data());
            }
            jQuery(presentation).append(slide);
        }

        jQuery(presentation).children(":first-child").addClass("active");
        if (settings.data("background")) {
            jQuery(presentation).css('background-image', "url(" + settings.data("background") + ")");
        }
        if (settings.data("font")) {
            jQuery(presentation).css('font-family', settings.data("font"));
        }
        if (settings.data("top")) {
            jQuery(presentation).css('padding-top', settings.data("top") + "px");
        }
        if (settings.data("bottom")) {
            jQuery(presentation).css('padding-bottom', settings.data("bottom") + "px");
        }
        if (settings.data("left")) {
            jQuery(presentation).css('padding-left', settings.data("left") + "px");
        }
        if (settings.data("right")) {
            jQuery(presentation).css('padding-right', settings.data("right") + "px");
        }
        if (settings.data("align")) {
            jQuery(presentation).css('text-align', settings.data("align"));
        }
        if (presentation.requestFullscreen) {
            presentation.requestFullscreen();
        } else if (presentation.msRequestFullscreen) {
            presentation.msRequestFullscreen();
        } else if (presentation.mozRequestFullScreen) {
            presentation.mozRequestFullScreen();
        } else if (presentation.webkitRequestFullscreen) {
            presentation.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
        }
    },
    nextSlide: function () {
        var active = jQuery("#superwiki_presentation > .active");
        var next = active.next();
        if (next.length) {
            switch (next.data("transition")) {
                case "instant":
                    active.removeClass("active");
                    next.addClass("active");
                case "slide":
                    jQuery(active).hide("slide", {direction: "left"}, 500, function () {
                        active.removeClass("active");
                        jQuery(next).show("slide", {direction: "right"}, 500, function () {
                            next.addClass("active");
                        });
                    });
                    break;
                case "fade":
                default:
                    jQuery(active).fadeOut(200, function () {
                        jQuery(next).fadeIn(300, function () {
                            active.removeClass("active");
                            next.addClass("active");
                        });
                    });
                    break;

            }
        }
    },
    previousSlide: function () {
        var active = jQuery("#superwiki_presentation > .active");
        var previous = active.prev();
        if (previous.length) {
            active.removeClass("active").hide();
            previous.addClass("active").show();
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
    jQuery(document).on("keyup", function (ui, event) {
        if (window.fullScreen) {
            if ((ui.keyCode === 32) || (ui.keyCode === 39)) {
                STUDIP.SuperWiki.nextSlide();
            } else if (ui.keyCode === 37) {
                STUDIP.SuperWiki.previousSlide();
            }
        }
    });
    jQuery("#superwiki_presentation").click(function (ui, event) {
        STUDIP.SuperWiki.nextSlide();
    });
});
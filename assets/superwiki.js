
STUDIP.SuperWiki = {
    connections: {},        //current open webRTC connections
    formerOldVersion: null, //second latest version - just in case our request fails
    oldVersion: null,       //the latest version from the server to compare with our current version
    //oldVersionChdate: null,
    alreadyXHRactive: false, //only needed to prevent STUDIP.SuperWiki.pushData from firing when there is still a request open.
    /**
     * Sends our current version to the server and when the server returns an answer, call STUDIP.SuperWiki.updatePage
     */
    pushData: function () {
        if (STUDIP.SuperWiki.alreadyXHRactive === false) {
            STUDIP.SuperWiki.formerOldVersion = STUDIP.SuperWiki.oldVersion;
            var old_content = STUDIP.SuperWiki.oldVersion;
            STUDIP.SuperWiki.oldVersion = jQuery("#superwiki_edit_content").val();
            STUDIP.SuperWiki.alreadyXHRactive = new Promise(function (resolve, reject) {
                var data = {
                    "cid": jQuery("#seminar_id").val(),
                    "page_id": jQuery("#page_id").val(),
                    "mode": "edit"
                };
                if (jQuery("#superwiki_edit_content").val() !== old_content) {
                    data.content = jQuery("#superwiki_edit_content").val();
                    data.old_content = old_content;
                }
                jQuery.ajax({
                    "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/superwiki/updater/superwikiupdate",
                    "data": data,
                    "dataType": "json",
                    "type": "post",
                    "success": function (data) {
                        STUDIP.SuperWiki.updatePage(data);
                        resolve(data);
                    },
                    "error": function () {
                        STUDIP.SuperWiki.alreadyXHRactive = false;
                        //Now revert the odVersion so our next request will have the correct oldVersion again:
                        STUDIP.SuperWiki.oldVersion = STUDIP.SuperWiki.formerOldVersion;
                        reject();
                    }
                });
            });
        }
        return STUDIP.SuperWiki.alreadyXHRactive;
    },

    updatePage: function (data) {
        STUDIP.SuperWiki.alreadyXHRactive = false;
        if (data.content) {
            var old_content = STUDIP.SuperWiki.oldVersion;
            var new_content = data.content;
            var my_content = jQuery("#superwiki_edit_content").val();
            STUDIP.SuperWiki.oldVersion = data.content; //save the version from the server as the oldVersion
            //STUDIP.SuperWiki.oldVersion.oldVersionChdate = data.chdate;
            var content = Textmerger.get().merge(old_content, my_content, new_content);
            var replacements = Textmerger.get().getReplacements(old_content, my_content, new_content);
            if (content !== my_content) {
                var pos1 = null, pos2 = null;
                if (jQuery("#superwiki_edit_content").is(":focus")) {
                    pos1 = document.getElementById("superwiki_edit_content").selectionStart;
                    pos2 = document.getElementById("superwiki_edit_content").selectionEnd;
                }
                jQuery("#superwiki_edit_content").val(content);
                for (var i in replacements.replacements) {
                    var replacement = replacements.replacements[i];
                    if (replacement.origin == "text2") { //because we our own changes already changed the cursor position
                        if (replacement.end < pos1) {
                            pos1 = pos1 + replacement.text.length - (replacement.end - replacement.start);
                        }
                        if (replacement.end < pos2) {
                            pos2 = pos2 + replacement.text.length - (replacement.end - replacement.start);
                        }
                    }
                }
                if (pos1 !== null) {
                    document.getElementById("superwiki_edit_content").setSelectionRange(pos1, pos2);
                }
            }
        }

        //Mitarbeiter aktualisieren:
        jQuery(".coworkerlist").html(data.onlineusers);

        //WebRTC offers and answers:
        /*if (data.open_offers) {
            for (var i in data.open_offers) {
                var connection = new RTC.Connection({
                    'offer': data.open_offers[i].offer_sdp,
                    'sendAnswer': function (answer) {
                        jQuery.ajax({
                            "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/superwiki/rtc/answer_offer",
                            "data": {
                                "page_id": jQuery("#page_id").val(),
                                "remote_user_id": data.open_offers[i].user_id,
                                "sdp": answer
                            },
                            "type": "post"
                        });
                    },
                    'established': function () {
                        alert("yeah");
                    },
                    'error': function (error) {
                        console.log("Error:");
                        console.log(error);
                    },
                    'receive': function (data) {
                    }
                });
                STUDIP.SuperWiki.connections[data.open_offers[i].user_id] = connection;
            }
        }
        if (data.answered_connections) {
            for (var i in data.answered_connections) {
                //console.log(data.answered_connections[i]);
                var user_id = data.answered_connections[i].user_id;
                if (STUDIP.SuperWiki.connections[user_id]) {
                    STUDIP.SuperWiki.connections[user_id].insertAnswer(data.answered_connections[i].answer_sdp);
                    jQuery.ajax({
                     "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/superwiki/rtc/delete_offer",
                     "data": {
                     "page_id": jQuery("#page_id").val(),
                     "remote_user_id": user_id
                     },
                     "type": "post"
                     });
                }
            }
        }*/
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
    },
    requestFullscreen: function () {
        var page = jQuery(".full_wiki_page")[0];
        var presentation = jQuery("#superwiki_presentation")[0];
        var settings = jQuery(page).find(".superwiki_presentation.settings");

        var slides = jQuery(page).html().split(/<div class="superwiki_presentation newpage"[^>]*?><\/div>/);
        var transitions = jQuery(page).find(".superwiki_presentation.newpage");

        jQuery(presentation).html('');
        for (var i in slides) {
            var stoppoints = jQuery("<div/>").html(slides[i]).find(".superwiki_presentation.stoppoint");
            slides[i] = slides[i].replace(/<div class="superwiki_presentation stoppoint"([^>]*?)><\/div>/g, '<div class="superwiki_presentation stoppoint"$1>');
            for (var j in stoppoints) {
                slides[i] += "</div>";
            }
            var slide = jQuery('<div class="slide">' + slides[i] + '</div>');
            if (i > 0) {
                slide.data(jQuery(transitions[i - 1]).data());
            }
            jQuery(presentation).append(slide);
        }

        jQuery(presentation).children(":first-child").addClass("active");
        if (settings.data("background")) {
            jQuery(presentation).css('background-image', "url(" + settings.data("background") + ")");
        } else {
            jQuery(presentation).css('background-image', "url(" + STUDIP.ABSOLUTE_URI_STUDIP + "plugins_packages/RasmusFuhse/SuperWiki/assets/presentation_background.svg)");
        }
        if (settings.data("font")) {
            jQuery(presentation).css('font-family', settings.data("font").replace("_", " "));
        }
        if (settings.data("fontcolor")) {
            jQuery(presentation).css('color', settings.data("fontcolor"));
        }
        jQuery(presentation).css('padding-top', (settings.data("top") ? settings.data("top") : "20") + "vh");
        jQuery(presentation).css('padding-bottom', (settings.data("bottom") ? settings.data("bottom") : "0") + "vh");
        jQuery(presentation).css('padding-left', (settings.data("left") ? settings.data("left") : "10") + "vw");
        jQuery(presentation).css('padding-right', (settings.data("right") ? settings.data("right") : "10") + "vw");
        if (settings.data("align")) {
            jQuery(presentation).css('text-align', settings.data("align"));
        } else {
            jQuery(presentation).css('text-align', "center");
        }
        if (settings.data("valign")) {
            jQuery(presentation).css('align-self', settings.data("valign") === "top" ? "flex-start" : "flex-end");
        }
        if (presentation.requestFullscreen) {
            presentation.requestFullscreen();
        } else if (presentation.msRequestFullscreen) {
            presentation.msRequestFullscreen();
        } else if (presentation.mozRequestFullScreen) {
            presentation.mozRequestFullScreen();
        } else if (presentation.webkitRequestFullscreen) {
            presentation.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
        } else {
            jQuery("#layout_wrapper").hide();
            jQuery(presentation).addClass("forced");
            if (jQuery(presentation).parent().is("#layout_content")) {
                jQuery(presentation).appendTo("body");
            }
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
                case "slideleft":
                    jQuery(active).hide("slide", {direction: "left"}, 500, function () {
                        active.removeClass("active");
                        jQuery(next).show("slide", {direction: "right"}, 500, function () {
                            next.addClass("active");
                        });
                    });
                    break;
                case "slideright":
                    jQuery(active).hide("slide", {direction: "right"}, 500, function () {
                        active.removeClass("active");
                        jQuery(next).show("slide", {direction: "left"}, 500, function () {
                            next.addClass("active");
                        });
                    });
                    break;
                case "slidebottom":
                    jQuery(active).hide("slide", {direction: "bottom"}, 500, function () {
                        active.removeClass("active");
                        jQuery(next).show("slide", {direction: "top"}, 500, function () {
                            next.addClass("active");
                        });
                    });
                    break;
                case "slidetop":
                    jQuery(active).hide("slide", {direction: "top"}, 500, function () {
                        active.removeClass("active");
                        jQuery(next).show("slide", {direction: "bottom"}, 500, function () {
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
            active.find(".processed").removeClass("processed").css('visibility', 'hidden');
            previous.addClass("active").show();
            previous.find(".processed").removeClass("processed").css('visibility', 'hidden');
        }
    },
    nextStoppoint: function () {
        var active = jQuery("#superwiki_presentation > .active");
        var stoppoint = jQuery("#superwiki_presentation > .active .stoppoint:not(.processed)").first();
        var first_child = stoppoint.children(":not(br)").first();
        var point = stoppoint;
        if (first_child.is("ul, ol") && first_child.children("li:not(.processed)").length > 0) {
            stoppoint.show();
            stoppoint.find(".stoppoint").css('visibility', 'hidden');

            first_child.children("li:not(.processed)").css('visibility', 'hidden');
            point = first_child.children("li:not(.processed)").first();
        }

        if (stoppoint.data("bullets")) {
            stoppoint.find("li").css("background-image", "url(" + jQuery("<div/>").html(stoppoint.data("bullets")).text() + ")");
        }

        switch (stoppoint.data("transition")) {
            case "instant":
                point.addClass("processed").css('visibility', "visible").hide().show();
                break;
            case "puff":
                point.addClass("processed").css('visibility', "visible").hide().show('puff');
                break;
            case "bounce":
                point.addClass("processed").css('visibility', "visible").hide().show('bounce', 800);
                break;
            case "slide":
            case "slideleft":
                point.addClass("processed").css('visibility', "visible").hide().show('slide', {direction: "right"}, 500);
                break;
            case "slideright":
                point.addClass("processed").css('visibility', "visible").hide().show('slide', {direction: "left"}, 500);
                break;
            case "fade":
            default:
                point.addClass("processed").css('visibility', "visible").hide().show('fade');
                break;
        }
        if (first_child.is("ul, ol") && first_child.children("li:not(.processed)").length === 0) {
            stoppoint.addClass("processed");
        }
    },
    checkPageName: function () {
        var name = this.value;
        var seminar_id = jQuery("#seminar_id").val();
        jQuery.ajax({
            "url": STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/superwiki/page/check_new_page_name",
            "data": {
                "name": name,
                "seminar_id": seminar_id
            },
            "type": "get",
            "dataType": "json",
            "success": function (json) {
                if (json.error) {
                    window.alert(json.error);
                }
            }
        });
        console.log(name);
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
                if (jQuery("#superwiki_presentation > .active .stoppoint:not(.processed)").length > 0) {
                    STUDIP.SuperWiki.nextStoppoint();
                } else {
                    STUDIP.SuperWiki.nextSlide();
                }
            } else if (ui.keyCode === 37) {
                STUDIP.SuperWiki.previousSlide();
            }
        }
    });
    jQuery("#superwiki_presentation").click(function (ui, event) {
        if (jQuery("#superwiki_presentation > .active .stoppoint:not(.processed)").length > 0) {
            STUDIP.SuperWiki.nextStoppoint();
        } else {
            STUDIP.SuperWiki.nextSlide();
        }
    });
});
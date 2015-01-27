/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, _ */

TextMerger = function (params) {
    this.exceptionOnConflict = typeof params !== "undefined" && params.exceptionOnConflict
        ? params.exceptionOnConflict
        : false;
};
TextMerger.get = function (params) {
    return new TextMerger(params);
};
TextMerger.prototype.merge = function (original, text1, text2) {
    var replacements = _.sortBy(_.union(
        this._getReplacements(original, text1),
        this._getReplacements(original, text2)
    ), "start");

    //reduce conflicts
    var i = 1;
    while (i < replacements.length) {
        if ((replacements[i].start > replacements[i - 1].end
            || replacements[i - 1].start > replacements[i].end)) {
            //no conflict
            i++;
        } else {
            //we have conflict!
            var subreplacements1 = [];
            var subreplacements2 = [];
            if (replacements[i].indexOf("\n") !== -1 && replacements[i - 1].indexOf("\n") !== -1) {
                subreplacements1 = this._getSubReplacements(original, replacements[i - 1], "\n");
                subreplacements2 = this._getSubReplacements(original, replacements[i], "\n");
            } else if (replacements[i].indexOf(" ") !== -1 && replacements[i - 1].indexOf(" ") !== -1) {
                subreplacements1 = this._getSubReplacements(original, replacements[i - 1], " ");
                subreplacements2 = this._getSubReplacements(original, replacements[i], " ");
            } else if(replacements[i].length < 100 && replacements[i - 1].length < 100) {
                subreplacements1 = this._getSubReplacements(original, replacements[i - 1]);
                subreplacements2 = this._getSubReplacements(original, replacements[i]);
            }
            if (subreplacements1.length > 1 || subreplacements2 > 1) {
                replacements = _.sortBy(
                    _.union(replacements, subreplacements1, subreplacements2)
                    , "start");
            } else {
                if (this.exceptionOnConflict) {
                    throw new TextMerger.Exception("Texts have a conflict.", {
                        "original": original,
                        "text1": text1,
                        "text2": text2,
                        "conflictReplacement1": replacements[i - 1],
                        "conflictReplacement2": replacements[i]
                    });
                } else {
                    //now replace old replacement if this bigger
                    if (replacements[i - 1].text.length < replacements[i].text.length) {
                        replacements = _.without(replacements, replacements[i - 1]);
                    } else {
                        replacements = _.without(replacements, replacements[i]);
                    }
                }
            }
            //important: no i++ here
        }
    }

    //and now we alter the original text by all replacements one after another
    var index_alteration = 0;
    var text = original;
    for (i in replacements) {
        text = text.substr(0, replacements[i].start + index_alteration)
        + replacements[i].text
        + text.substr(replacements[i].end + index_alteration);
        index_alteration += replacements[i].text.length - replacements[i].end + replacements[i].start;
    }

    return text;
};

TextMerger.prototype._getReplacements = function (original, text) {
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
    //but we only do this when a conflict occurs (see above).
    replacements.push(replacement);
    return replacements;
};

TextMerger.prototype._getSubReplacements = function (original, replacement, delimiter) {
    if (typeof delimiter === "undefined") {
        delimiter = "";
    }
    var subreplacements = [];
    var old_parts = original.substr(replacement['start'], replacement['end'] - replacement['start']).split(delimiter);
    var new_parts = replacement['text'].split(delimiter);

    //now do some levenshtein action:
    var matrix = [];
    var eq, ins, repl, del;
    for (var i = 0; i <= old_parts.length; i++) {
        for (var k = 0; k <= new_parts.length; k++) {
            if (i === 0) {
                matrix[i][k] = k;
            } else if (k === 0) {
                matrix[i][k] = i;
            } else {
                eq = matrix[i - 1][k - 2] + (old_parts[i - 1] === new_parts[k - 1] ? 0 : 10000);
                repl = matrix[i - 2][k - 2] + 1;
                ins = matrix[i - 1][k - 2] + 1;
                del = matrix[i - 2][k - 1] + 1;
                matrix[i][k] = Math.min(eq, ins, repl, del);
            }
        }
    }
    var create_backtrace = function (i, k) {
        if (i > 0 && j > 0 && matrix[i - 1][k - 1] === matrix[i][k]) {
            return "0"
        } else {
            return "1";
        }
    };

    subreplacements.push(replacement);
    return subreplacements;
};


TextMerger.Exception = function (message, data) {
    this.message = message;
    this.data    = data || {};
};
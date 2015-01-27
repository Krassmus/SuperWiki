/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, _ */

/**
 * This class provides functions for textmerging. Most typically you can insert two texts
 * and an original text and the TextMerger-object returns the merged text with all
 * inserts and replacements.
 *
 *     $merged_text = TextMerger.get().merge(original, my_altered_text, their_altered_text);
 *
 * Easy as that.
 *
 * Usually TextMerger focusses on returning a text. But what happens if there
 * is any conflict? For example could both texts have replaced the same part
 * of the original. In this case TextMerger examines the replacements and takes
 * only that replacement which is larger. For example
 *
 *     TextMerger.get().merge("Hi there!", "Hi Master!", "Hello Dark Lord!")
 *
 * would return the string "Hello Dark Lord!", as the replacement "ello Dark Lord"
 * from the second text is larger as "Master", which would be the replacement of
 * the first text.
 *
 * But you can also tell TextMerger to throw an exception on a conflict by calling
 *
 *     TextMerger.get({exceptionOnConflict: true}).merge(original, my_altered_text, their_altered_text);
 *
 * That is also why TextMerger is an object and not a function. #get calls a contrsuctor and returns
 * and object of TextMerger-class. With the parameter of the constructor you alter the behaviour
 * of TextMerger.
 */

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
            //we have conflict
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
    //but for now we keep this simple algorithm.
    replacements.push(replacement);
    return replacements;
};


TextMerger.Exception = function (message, data) {
    this.message = message;
    this.data    = data || {};
};
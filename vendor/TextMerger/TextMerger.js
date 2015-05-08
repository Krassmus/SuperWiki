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
    this.levenshteinDelimiter = typeof params !== "undefined" && params.levenshteinDelimiter
        ? (typeof params.levenshteinDelimiter === "Array" ? params.levenshteinDelimiter : [params.levenshteinDelimiter])
        : ["\n", " ", ""];
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
            var delimiter;
            for (j in this.levenshteinDelimiter) {
                delimiter = this.levenshteinDelimiter[j];
                if (delimiter === "" || (replacements[i].text.indexOf(delimiter) !== -1
                        && replacements[i - 1].text.indexOf(delimiter) !== -1)) {
                    if (replacements[i].text.split(delimiter).length < 100 && replacements[i - 1].text.split(delimiter).length < 100) {
                        subreplacements1 = this._getSubReplacements(original, replacements[i - 1], delimiter);
                        subreplacements2 = this._getSubReplacements(original, replacements[i], delimiter);
                        break;
                    }
                }
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
        if ((original[i] !== text[i]) || (i === original.length - 1)) {
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
    if (typeof replacement.start !== "undefined" && typeof replacement.end !== "undefined") {
        if (replacement['text'] || (replacement.start !== replacement.end)) {
            replacements.push(replacement);
        }
    }
    return replacements;
};

TextMerger.prototype._getSubReplacements = function (original, replacement, delimiter) {
    return [replacement];
    if (typeof delimiter === "undefined") {
        delimiter = "";
    }
    var subreplacements = [];
    var old_parts = original.substr(replacement['start'], replacement['end'] - replacement['start']).split(delimiter);
    var new_parts = replacement['text'].split(delimiter);
    if (old_parts.length * new_parts.length > 10000) {
        subreplacements.push(replacement);
        return subreplacements;
    }

    //now do some levenshtein action:
    var matrix = [];
    for (var i = 0; i <= old_parts.length; i++) {
        matrix.push(new Array(new_parts.length));
    }
    var eq, ins, repl, del;
    for (var i = 0; i <= old_parts.length; i++) {
        for (var k = 0; k <= new_parts.length; k++) {
            if (i === 0) {
                matrix[i][k] = k;
            } else if (k === 0) {
                matrix[i][k] = i;
            } else {
                eq = matrix[i][k - 1] + (old_parts[i - 1] === new_parts[k - 1] ? 0 : 10000);
                repl = matrix[i - 1][k - 1] + 1;
                ins = matrix[i][k - 1] + 1;
                del = matrix[i - 1][k] + 1;
                matrix[i][k] = Math.min(eq, ins, repl, del);
            }
        }
    }

    //backtracing ...
    var last_replacement = {text: null};
    var i = new_parts.length - 1,
        k = old_parts.length - 1;
    while (i > 0 && k > 0) {
        if (matrix[i - 1][k - 1] === matrix[i][k]) {
            if (last_replacement.text !== null) {
                last_replacement.start = i;
                subreplacements.push(last_replacement);
                last_replacement.text = null;
                last_replacement.end = null;
            }
            i--;
            k--;
        } else {
            if (last_replacement.text !== null) {
                last_replacement.end = k + 1;
            }
            if (matrix[i - 1][k - 2] === matrix[i][k] - 1) {

            }
            i--;
        }
    }

    return subreplacements.reverse();
};

/**
 * Constructor for object of type TextMerger.Exception. This kind
 * of exception is used when TextMerger has some conflicts in merging
 * and is configured to throw exceptions on conflicts.
 * @param message : string that indicates what caused this exception
 * @param data : a plain object
 * @constructor
 */
TextMerger.Exception = function (message, data) {
    this.message = message;
    this.data    = data || {};
};
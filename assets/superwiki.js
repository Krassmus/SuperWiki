
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
        console.log(start1 + " " + end1 + " | " + start2 + " " + end2);

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
    }
};
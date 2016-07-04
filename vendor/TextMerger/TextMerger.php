<?php

/**
 * Class TextmergerException
 * Special Exception class for exceptions that are thrown when text-conflicts happen.
 */
class TextmergerException extends Exception {

    public $data = array();

    public function __construct($message, $data = array())
    {
        $this->data = $data;
        parent::__construct($message);
    }
}

/**
 * Class TextMergerReplacement
 * An object of TextMergerReplacement represents a replacement of text for another text.
 */
class TextmergerReplacement {

    public $start;
    public $end;
    public $text;
    public $origin_name;

    /**
     * TextMergerReplacement constructor.
     * Create a new Replacement. A replacement consists of a start-value (index within the text string),
     * an end-value and the text that replaces everything between start and end.
     * @param integer $start
     * @param integer $end : must be bigger or equal than start.
     * @param string $text
     * @param string|null $origin : the name of an origin
     */
    public function __construct($start = 0, $end = 0, $text = "", $origin = null)
    {
        $this->start   = $start;
        $this->end     = $end;
        $this->text    = $text;
        $this->origin  = $origin;
    }

    /**
     * When a text is changed by multiple replacements each replacement can change the
     * textlength (by simply adding or erasing characters). In this case the next replacements need
     * adjusted start and end numbers to work correctly.
     * @param $add
     */
    public function changeIndexesBy($add)
    {
        $this->start = $this->start + $add;
        $this->end   = $this->end + $add;
    }

    /**
     * Applies the Text-replacement to the given text and replaces the characters between
     * $this->start and $this->end with the $this->text, which can also be an empty string.
     * @param $text
     * @return string
     */
    public function applyTo($text)
    {
        return substr($text, 0, $this->start).$this->text.substr($text, $this->end);
    }

    /**
     * Finds out if this replacement is in conflict with the given replacement.
     * @param $replacement : the replacement to compare with.
     * @return bool : true if there is a conflict.
     */
    public function isConflictingWith($replacement)
    {
        return ($this->start < $replacement->end && $this->start >= $replacement->start)
                    || ($this->end < $replacement->end && $this->end >= $replacement->start)
                    || ($this->start < $replacement->start && $this->end > $replacement->end);
    }

    /**
     * @param $delimiter
     * @param $original
     * @return array
     */
    public function breakApart($delimiter, $original)
    {
        $original_snippet = substr($original, $this->start, $this->end);
        $parts = explode($delimiter, $this->text);
        $original_parts = explode($delimiter, $original_snippet);
        return array($this);
    }
}

class Textmerger3 {

    protected $conflictBehaviour = "select_larger_difference"; // "throw_exception", "select_text1", "select_text2"
    protected $exceptionOnConflict = false;
    protected $levenshteinDelimiter = null; // something like array("\n", " ", "")

    static protected $replacement_hash = array();

    static public function get($params = array())
    {
        return new TextMerger($params);
    }

    public function __construct($params = array())
    {
        $this->exceptionOnConflict = isset($params['exceptionOnConflict'])
            ? $params['exceptionOnConflict']
            : false;
        $this->levenshteinDelimiter = isset($params['levenshteinDelimiter'])
            ? $params['levenshteinDelimiter']
            : array("\n", " ", "");
    }

    /**
     * Implements a 3-way-merge between an original text and two independently derived texts. For this task
     * an algorithm needs to calculate all small changes that were made from original to text1 and from original
     * to text2 and merges these changes. We call these changes replacements, because they are simply that.
     * If replacements have conflicts the algorithm tries to break down the replacements in smaller ones and merge
     * them. But if this also won't work the conflicting replacement with the smaller change will be applied. But
     * you can also set $this->$exceptionOnConflict to receive an exception on text-conflicts instead.
     * @param string $original
     * @param string $text1
     * @param string $text2
     * @return string
     */
    public function merge($original, $text1, $text2)
    {
        $replacements = $this->getReplacements($original, $text1, $text2);

        $index_alteration = 0;
        $text = $original;
        foreach ($replacements as $replacement) {
            $replacement->changeIndexesBy($index_alteration);
            $text = $replacement->applyTo($text);
            $index_alteration += strlen($replacement->text) - $replacement->end + $replacement->start;
        }
        return $text;
    }

    public function calculateCursor($cursor_position, $original, $text1, $text2)
    {
        $replacements = $this->getReplacements($original, $text1, $text2);

        $index_alteration = 0;
        foreach ($replacements as $replacement) {
            $replacement->changeIndexesBy($index_alteration);
            if ($replacement->start <= $cursor_position) {
                $cursor_position += strlen($replacement->text) - $replacement->end + $replacement->start;
                $index_alteration += strlen($replacement->text) - $replacement->end + $replacement->start;
            } else {
                break;
            }
        }
        return $cursor_position;
    }

    /**
     * Returns an array of TextMergerReplacement which are not conflicting. Or if there are conflicts and
     * $this->$exceptionOnConflict is set to true, an exception will be thrown.
     * @param string $original
     * @param string $text1
     * @param string $text2
     */
    public function getReplacements($original, $text1, $text2)
    {
        $hash_id = md5($original."___".$text1."____".$text2);
        if (isset(self::$replacement_hash[$hash_id])) {
            return self::$replacement_hash[$hash_id];
        }
        //collect all major replacements:
        $replacements = array(
            $this->_getSimpleReplacement($original, $text1),
            $this->_getSimpleReplacement($original, $text2)
        );

        $i = 0;
        do {
            //sort them in order of their start-value:
            usort($replacements, function ($a, $b) {
                return $a->start >= $b->start ? 1 : -1;
            });
            $conflicting = false;
            foreach ($replacements as $key => $replacement) {
                if ($key === count($replacements) - 1) {
                    break;
                }
                if ($replacement->isConflictingWith($replacements[$key + 1])) {
                    $delimiter = "\n";
                    $parts = $replacement->breakApart($delimiter, $original);
                    if (count($parts) >= 2) {
                        $conflicting = true;
                        unset($replacements[$key]);
                        foreach ($parts as $new_replacement) {
                            $replacements[] = $new_replacement;
                        }
                    }
                    $parts = $replacements[$key + 1]->breakApart($delimiter, $original);
                    if (count($parts) >= 2) {
                        $conflicting = true;
                        unset($replacements[$key]);
                        foreach ($parts as $new_replacement) {
                            $replacements[] = $new_replacement;
                        }
                    }
                    break;
                }
            }
            $i++;
        } while ($conflicting && isset($this->levenshteinDelimiter[$i]));

        if ($conflicting) {
            if ($this->conflictBehaviour === "exceptionOnConflict") {
                throw new TextmergerException("Texts have a conflict.", array(
                    "original" => $original,
                    "text1" => $text1,
                    "text2" => $text2,
                    "replacement1" => $replacements[$key],
                    "replacement2" => $replacements[$key + 1]
                ));
            } elseif ($this->conflictBehaviour === "select_larger_difference") {
                
            }
        }

        self::$replacement_hash[$hash_id] = $replacements;
        return $replacements;
    }

    /**
     * Calculates the simple replacement between original and text in the way that all changed characters are between
     * start and end of the one replacement. For example if you change line 3 and line 20 of a document, the whole
     * block from line 3 to line 20 will be considered as the change. That is very simple, I know. But it's also fast.
     * @param string $original
     * @param string $text : the derived text
     * @return TextMergerReplacement
     */
    public function _getSimpleReplacement($original, $text)
    {
        $replacement = new TextmergerReplacement();
        $text_start = $text_end = null;
        for($i = 0; $i < strlen($original); $i++) {
            if (($original[$i] !== $text[$i]) || ($i === strlen($original) - 1)) {
                $replacement->start = $i;
                $text_start = $i;
                break;
            }
        }
        for($i = 0; $i < strlen($original); $i++) {
            if (($original[strlen($original) - 1 - $i] !== $text[strlen($text) - 1 - $i])
                || (strlen($original) - $i === $replacement->start)) {
                $replacement->end = strlen($original) - $i;
                $text_end = strlen($text) - $i;
                break;
            }
        }
        $replacement->text = substr($text, $text_start, $text_end - $text_start);
        /**if ($replacement->start !== null && $replacement->end !== null) {
            if ($replacement->text || ($replacement->start !== $replacement->end)) {
                $replacements[] = $replacement;
            } else {

            }
        }*/
        return $replacement;
    }

}










class TextMerger {

    protected $exceptionOnConflict = false;
    protected $levenshteinDelimiter = null;

    static public function get($params = array())
    {
        return new TextMerger($params);
    }

    public function __construct($params = array())
    {
        $this->exceptionOnConflict = isset($params['exceptionOnConflict'])
            ? $params['exceptionOnConflict']
            : false;
        $this->levenshteinDelimiter = isset($params['levenshteinDelimiter'])
            ? $params['levenshteinDelimiter']
            : array("\n", " ", "");
    }

    public function merge($original, $text1, $text2)
    {
        if (function_exists("xdiff_string_merge3")) {
            return xdiff_string_merge3($original, $text1, $text2);
        }
        $replacements = array_merge(
            $this->_getReplacements($original, $text1),
            $this->_getReplacements($original, $text2)
        );
        usort($replacements, function ($a, $b) { return $a['start'] >= $b['start'] ? 1 : -1; });
        //reduce conflicts
        //var_dump($replacements);
        $i = 1;
        while ($i < count($replacements)) {
            if (($replacements[$i]['start'] > $replacements[$i - 1]['end']
            || $replacements[$i - 1]['start'] > $replacements[$i]['end'])) {
                //no conflict
                $i++;
            } else {
                //we have conflict!
                $subreplacements1 = array();
                $subreplacements2 = array();
                foreach ($this->levenshteinDelimiter as $delimiter) {
                    if ($delimiter === "" || (strpos($replacements[$i]['text'], $delimiter) !== false
                                && strpos($replacements[$i - 1]['text'], $delimiter) !== false)) {
                        $parts = $delimiter !== ""
                            ? explode($delimiter, $replacements[$i]['text'])
                            : $replacements[$i]['text'];
                        $last_parts = $delimiter !== ""
                            ? explode($delimiter, $replacements[$i - 1]['text'])
                            : $replacements[$i - 1]['text'];
                        if (count($parts) < 100 && count($last_parts) < 100) {
                            $subreplacements1 = $this->_getSubReplacements($original, $replacements[$i - 1], $delimiter);
                            $subreplacements2 = $this->_getSubReplacements($original, $replacements[$i], $delimiter);
                            break;
                        }
                    }
                }
                if (count($subreplacements1) > 1 || count($subreplacements2) > 1) {
                        usort($replacements, function ($a, $b) { return $a['start'] >= $b['start'] ? 1 : -1; });
                    } else {
                        if ($this->exceptionOnConflict) {
                            throw new TextmergerException("Texts have a conflict.", array(
                            "original" => $original,
                            "text1" => $text1,
                            "text2" => $text2,
                            "conflictReplacement1" => $replacements[$i - 1],
                            "conflictReplacement2" => $replacements[$i]
                        ));
                    } else {
                        //now replace old replacement if this bigger
                        if (strlen($replacements[$i - 1]['text']) < strlen($replacements[i]['text'])) {
                            $replacements = array_splice($replacements, $i - 1);
                        } else {
                            $replacements = array_splice($replacements, $i);
                        }
                    }
                }
                //important: no i++ here
            }
        }

        //and now we alter the original text by all replacements one after another
        $index_alteration = 0;
        $text = $original;
        foreach ($replacements as $replacement) {
            $text = substr($text, 0, $replacement['start'] + $index_alteration)
                . $replacement['text']
                . substr($text, $replacement['end'] + $index_alteration);
            $index_alteration += strlen($replacement['text']) - $replacement['end'] + $replacement['start'];
        }
        return $text;
    }

    public function _getReplacements($original, $text)
    {
        $replacements = array();
        $replacement = array();
        $text_start = $text_end = null;
        for($i = 0; $i < strlen($original); $i++) {
            if (($original[$i] !== $text[$i]) || ($i === strlen($original) - 1)) {
                $replacement['start'] = $i;
                $text_start = $i;
                break;
            }
        }
        for($i = 0; $i < strlen($original); $i++) {
            if (($original[strlen($original) - 1 - $i] !== $text[strlen($text) - 1 - $i])
                    || (strlen($original) - $i === $replacement['start'])) {
                $replacement['end'] = strlen($original) - $i;
                $text_end = strlen($text) - $i;
                break;
            }
        }
        $replacement['text'] = substr($text, $text_start, $text_end - $text_start);
        //We could be more specific and find sub-changes with the levenshtein-algorithm,
        //but we only do this when a conflict occurs (see above).
        if ($replacement['start'] !== null && $replacement['end'] !== null) {
            if ($replacement['text'] || ($replacement['start'] !== $replacement['end'])) {
                $replacements[] = $replacement;
            }
        }
        return $replacements;
    }

    protected function _getSubReplacements($original, $replacement, $delimiter) {
        return array($replacement);
        //Of course we need to implement some more here.
        //But first let's wait until the JS is final.
    }
}
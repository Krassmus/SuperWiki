<?php

class TextMergerException extends Exception {

    public $data = array();

    public function __construct($message, $data = array())
    {
        $this->data = $data;
        parent::__construct($message);
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
                            throw new TextMergerException("Texts have a conflict.", array(
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

    protected function _getReplacements($original, $text)
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
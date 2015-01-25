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

    static public function get($params = array())
    {
        return new TextMerger($params);
    }

    public function __construct($params = array())
    {
        $this->exceptionOnConflict = isset($params['exceptionOnConflict'])
            ? $params['exceptionOnConflict']
            : false;
    }

    public function merge($original, $text1, $text2)
    {
        $replacements = array_merge(
            $this->_getReplacements($original, $text1),
            $this->_getReplacements($original, $text2)
        );
        usort($replacements, function ($a, $b) { return $a['start'] >= $b['start'] ? 1 : -1; });
        //reduce conflicts
        $i = 1;
        while ($i < count($replacements)) {
            if (($replacements[$i]['start'] > $replacements[$i - 1]['end']
            || $replacements[$i - 1]['start'] > $replacements[$i]['end'])) {
                //no conflict
                $i++;
            } else {
                //we have conflict
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
                    if (strlen($replacements[$i - 1]['text']) < strlen($replacements[$i]['text'])) {
                        $replacements = array_splice($replacements, $i - 1);
                    } else {
                        $replacements = array_splice($replacements, $i);
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
        for($i = 0; $i < strlen($original); $i++) {
            if ($original[$i] !== $text[$i]) {
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
        //but for now we keep this simple algorithm.
        $replacements[] = $replacement;
        return $replacements;
    }
}
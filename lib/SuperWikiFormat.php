<?php

class SuperWikiFormat extends StudipFormat
{
    private static $superwiki_rules = array(
        'presentation-settings' => array(
            'start'    => '{{presentation(.*?)}}',
            'callback' => 'SuperWikiFormat::markupPresentationSettings',
            'before' => "media"
        ),
        'presentation-newpage' => array(
            'start'    => '{{newpage(.*?)}}',
            'callback' => 'SuperWikiFormat::markupSlideNewpage'
        ),
        'wiki-links' => array(
            'start'    => '\[\[(.*?)(?:\|(.*?))?\]\]',
            'callback' => 'SuperWikiFormat::markupWikiLinks',
            'before'   => 'links'
        ),
    );

    /**
     * Adds a new markup rule to the superwiki markup set. This can
     * also be used to replace an existing markup rule. The end regular
     * expression is optional (i.e. may be NULL) to indicate that this
     * rule has an empty content model. The callback is called whenever
     * the rule matches and is passed the following arguments:
     *
     * - $markup    the markup parser object
     * - $matches   match results of preg_match for $start
     * - $contents  (parsed) contents of this markup rule
     *
     * Sometimes you may want your rule to apply before another specific rule
     * will apply. For this case the parameter $before defines a rulename of
     * existing markup, before which your rule should apply.
     *
     * @param string $name      name of this rule
     * @param string $start     start regular expression
     * @param string $end       end regular expression (optional)
     * @param callback $callback function generating output of this rule
     * @param string $before mark before which rule this rule should be appended
     */
    public static function addSuperWikiMarkup($name, $start, $end, $callback, $before = null)
    {
        $inserted = false;
        foreach (self::$superwiki_rules as $rule_name => $rule) {
            if ($rule_name === $before) {
                self::$wiki_rules[$name] = compact('start', 'end', 'callback');
                $inserted = true;
            }
            if ($inserted) {
                unset(self::$superwiki_rules[$rule_name]);
                self::$superwiki_rules[$rule_name] = $rule;
            }
        }
        if (!$inserted) {
            self::$superwiki_rules[$name] = compact('start', 'end', 'callback');
        }
    }

    public static function getWikiMarkup($name) {
        return self::$superwiki_rules[$name];
    }

    public static function removeWikiMarkup($name)
    {
        unset(self::$superwiki_rules[$name]);
    }

    public function __construct()
    {
        parent::__construct();
        foreach (self::$superwiki_rules as $name => $rule) {
            $this->addMarkup(
                $name,
                $rule['start'],
                $rule['end'],
                $rule['callback'],
                $rule['before'] ?: null
            );
        }
    }

    protected static function markupWikiLinks($markup, $matches) {
        $page_name = decodeHTML($matches[1]);
        $display_page = $matches[2] ? $markup->format($matches[2]) : htmlReady($page_name);

        $page = SuperwikiPage::findOneBySQL("name = ? AND content IS NOT NULL AND content != ''", array($page_name));

        if ($page) {
            return sprintf('<a href="%s">%s</a>',
                URLHelper::getLink("plugins.php/superwiki/page/view/".$page->getId()),
                $display_page
            );
        } else {
            return $display_page;
        }
    }

    protected static function markupPresentationSettings($markup, $matches) {
        $data = array();
        if ($matches[1]) {
            foreach (explode(" ", $matches[1]) as $parameter) {
                list($name, $value) = explode("=", $parameter, 2);
                if ($name && $value) {
                    $data[] = 'data-'.htmlReady($name).'="'.($value).'"';
                }
            }
        }
        return '<div class="superwiki_presentation settings" '.implode(" ", $data).'></div>';
    }

    protected static function markupSlideNewpage($markup, $matches) {
        $data = array();
        if ($matches[1]) {
            foreach (explode(" ", $matches[1]) as $parameter) {
                list($name, $value) = explode("=", $parameter, 2);
                if ($name && $value) {
                    $data[] = 'data-'.htmlReady($name).'="'.htmlReady($value).'"';
                }
            }
        }
        return '<div class="superwiki_presentation newpage" '.implode(" ", $data).'></div>';
    }

}

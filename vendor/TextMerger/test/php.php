<?php

require_once __DIR__."/../TextMerger.php";

    function escape($text) {
        return htmlentities($text);
    }

?><!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Testing TextMerger.php</title>
    <style>
        body {
            font-family: Sans-Serif;
        }
        table {
            margin: 20px;
        }
        table > caption {
            background-color: lightblue;
            text-align: left;
            font-size: 1.3em;
            padding: 5px;
        }
        table.failed caption {
            background-color: #cc6165;
        }
        table a {
            font-size: 0.8em;
            color: blue;
            cursor: pointer;
        }
    </style>
</head>
    <body>

    <h1>Testing  TextMerger.js</h1>

    <table id="resulttable">
        <tbody>
        <tr>
            <td>Successful tests</td>
            <td id="successful"></td>
        </tr>
        <tr>
            <td>Failed tests</td>
            <td id="failed"></td>
        </tr>
        </tbody>
    </table>

    <h2>Tests</h2>

    <?
    $tests = array();
    $tests[] = array(
        'title' => "Useless merge",
        'original' => "Hello World!",
        'mine' => "Hello World!",
        'theirs' => "Hello World!",
        'expected' => "Hello World!"
    );
    $tests[] = array(
        'title' => "Trivial addendum",
        'original' => "Hello World!",
        'mine' => "Hello World! You rock!",
        'theirs' => "Hello World!",
        'expected' => "Hello World! You rock!"
    );
    $tests[] = array(
        'title' => "Trivial merge",
        'original' => "Hello World!",
        'mine' => "Yeah World!",
        'theirs' => "Hello World!",
        'expected' => "Yeah World!"
    );
    $tests[] = array(
        'title' => "Distinct changes",
        'original' => "Hello World!",
        'mine' => "Hi World!",
        'theirs' => "Hello San Dimas!",
        'expected' => "Hi San Dimas!"
    );
    /*$tests[] = array(
        'title' => "Cumulative changes",
        'original' => "Hi,",
        'mine' => "Hi, I'm Ras!",
        'theirs' => "Hi, call me John.",
        'expected' => "Hi, call me John. I'm Ras!"
    );*/
    /*$tests[] = array(
        'title' => "Complicated Merging",
        'original' => "Hey lovely world",
        'mine' => "Hey graceful world",
        'theirs' => "Hello lovely world!",
        'expected' => "Hello graceful world!"
    );*/

    ?>
    <? foreach ($tests as $test) : ?>
        <? $result = TextMerger::get()->merge($test['original'], $test['mine'], $test['theirs']) ?>
        <table class="test <?= $result === $test['expected'] ? "" : "failed" ?>">
            <caption><?= escape($test['title']) ?></caption>
            <tbody>
            <tr>
                <td>Original</td>
                <td class="original"><?= escape($test['original']) ?></td>
            </tr>
            <tr>
                <td>Mine</td>
                <td class="mine"><?= escape($test['mine']) ?></td>
            </tr>
            <tr>
                <td>Theirs</td>
                <td class="theirs"><?= escape($test['theirs']) ?></td>
            </tr>
            <tr>
                <td>Expected Result</td>
                <td class="expected"><?= escape($test['expected']) ?></td>
            </tr>
            <tr>
                <td>Result</td>
                <td class="result"><?= escape($result) ?></td>
            </tr>
            </tbody>
        </table>
    <? endforeach ?>

    <script>
        function run(test) {
            var expected;
            var result = TextMerger.get().merge(
                test.querySelector(".original").innerHTML,
                test.querySelector(".mine").innerHTML,
                test.querySelector(".theirs").innerHTML
            );
            test.querySelector(".result").innerHTML = result;
            expected = test.querySelector(".expected").innerHTML;
            return expected === result;
        }
        document.addEventListener("DOMContentLoaded", function(event) {
            var tests = document.querySelectorAll(".test");
            document.querySelector("#resulttable .successful").innerHTML = "1";
        });
    </script>

    </body>
</html>
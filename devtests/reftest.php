<?php

/*
$json='{"params":{"model":"llama-3.3-70b-versatile","reasoning_format":"hidden","messages":[]}}';
$json = json_decode($json, true);

print_r($json);
*/

class myTest {
    public static function refTest() {
        print "test works";
    }
}

$className = "myTest";
$className::refTest();
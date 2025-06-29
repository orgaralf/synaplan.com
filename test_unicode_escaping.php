<?php
// Test file to verify Unicode and JSON handling in EscString function

// Include the database configuration
require_once 'web/inc/_confdb.php';

echo "Testing EscString function with Unicode and JSON data...\n\n";

// Test 1: German Umlauts
$testUmlauts = "Müllerstraße mit üöä";
echo "Test 1 - German Umlauts:\n";
echo "Original: " . $testUmlauts . "\n";
$escaped = db::EscString($testUmlauts);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Match: " . ($testUmlauts === stripslashes($escaped) ? "PASS" : "FAIL") . "\n\n";

// Test 2: JSON data
$testJson = '{"name": "Müller", "city": "Köln", "data": {"emoji": "😀", "text": "Hello üöä"}}';
echo "Test 2 - JSON data:\n";
echo "Original: " . $testJson . "\n";
$escaped = db::EscString($testJson);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Valid JSON: " . (json_decode(stripslashes($escaped)) !== null ? "PASS" : "FAIL") . "\n\n";

// Test 3: Special characters that might cause issues
$testSpecial = "Line 1\nLine 2\r\nLine 3\rLine 4";
echo "Test 3 - Special characters:\n";
echo "Original: " . str_replace(["\n", "\r"], ["\\n", "\\r"], $testSpecial) . "\n";
$escaped = db::EscString($testSpecial);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Match: " . ($testSpecial === stripslashes($escaped) ? "PASS" : "FAIL") . "\n\n";

// Test 4: SQL injection attempt
$testInjection = "'; DROP TABLE users; --";
echo "Test 4 - SQL injection attempt:\n";
echo "Original: " . $testInjection . "\n";
$escaped = db::EscString($testInjection);
echo "Escaped: " . $escaped . "\n";
echo "Contains dangerous patterns: " . (strpos($escaped, "DROP TABLE") !== false ? "FAIL" : "PASS") . "\n\n";

// Test 5: Complex Unicode
$testComplex = "Café résumé naïve façade";
echo "Test 5 - Complex Unicode:\n";
echo "Original: " . $testComplex . "\n";
$escaped = db::EscString($testComplex);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Match: " . ($testComplex === stripslashes($escaped) ? "PASS" : "FAIL") . "\n\n";

echo "Testing complete!\n";
?> 
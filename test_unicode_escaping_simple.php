<?php
// Simple test to verify Unicode handling logic without database connection

echo "Testing Unicode handling logic...\n\n";

// Simulate the EscString function logic without mbstring
function testEscString($str) {
    // Handle null values
    if (is_null($str)) return "";
    
    // Handle boolean values
    if (is_bool($str)) return $str ? '1' : '0';
    
    // Convert to string if not already
    if (!is_string($str)) $str = strval($str);
    
    // Normalize line endings to Unix style
    $str = str_replace(["\r\n", "\r"], "\n", $str);
    
    // Simulate mysqli_real_escape_string behavior
    // This is what mysqli_real_escape_string does for SQL injection prevention
    $escapedStr = addslashes($str);
    
    return $escapedStr;
}

// Test 1: German Umlauts
$testUmlauts = "Müllerstraße mit üöä";
echo "Test 1 - German Umlauts:\n";
echo "Original: " . $testUmlauts . "\n";
$escaped = testEscString($testUmlauts);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Match: " . ($testUmlauts === stripslashes($escaped) ? "PASS" : "FAIL") . "\n\n";

// Test 2: JSON data
$testJson = '{"name": "Müller", "city": "Köln", "data": {"emoji": "😀", "text": "Hello üöä"}}';
echo "Test 2 - JSON data:\n";
echo "Original: " . $testJson . "\n";
$escaped = testEscString($testJson);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Valid JSON: " . (json_decode(stripslashes($escaped)) !== null ? "PASS" : "FAIL") . "\n\n";

// Test 3: Special characters that might cause issues
$testSpecial = "Line 1\nLine 2\r\nLine 3\rLine 4";
echo "Test 3 - Special characters:\n";
echo "Original: " . str_replace(["\n", "\r"], ["\\n", "\\r"], $testSpecial) . "\n";
$escaped = testEscString($testSpecial);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Match: " . ($testSpecial === stripslashes($escaped) ? "PASS" : "FAIL") . "\n\n";

// Test 4: SQL injection attempt
$testInjection = "'; DROP TABLE users; --";
echo "Test 4 - SQL injection attempt:\n";
echo "Original: " . $testInjection . "\n";
$escaped = testEscString($testInjection);
echo "Escaped: " . $escaped . "\n";
echo "Contains dangerous patterns: " . (strpos($escaped, "DROP TABLE") !== false ? "FAIL" : "PASS") . "\n\n";

// Test 5: Complex Unicode
$testComplex = "Café résumé naïve façade";
echo "Test 5 - Complex Unicode:\n";
echo "Original: " . $testComplex . "\n";
$escaped = testEscString($testComplex);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Match: " . ($testComplex === stripslashes($escaped) ? "PASS" : "FAIL") . "\n\n";

// Test 6: Emojis and special Unicode
$testEmoji = "Hello 😀 🌍 🚀 with üöä";
echo "Test 6 - Emojis and special Unicode:\n";
echo "Original: " . $testEmoji . "\n";
$escaped = testEscString($testEmoji);
echo "Escaped: " . $escaped . "\n";
echo "Unescaped: " . stripslashes($escaped) . "\n";
echo "Match: " . ($testEmoji === stripslashes($escaped) ? "PASS" : "FAIL") . "\n\n";

echo "Testing complete!\n";
?> 
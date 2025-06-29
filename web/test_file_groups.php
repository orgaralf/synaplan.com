<?php
// Test file for file group functionality
session_start();

// Include the core files
require_once('inc/_coreincludes.php');

// Set a test user ID (you may need to adjust this based on your test data)
$_SESSION["USERPROFILE"]["BID"] = 2; // Using user ID 2 from the sample data

echo "<h1>Testing File Group Functionality</h1>";

// Test getAllFileGroups
echo "<h2>Testing getAllFileGroups()</h2>";
$groups = BasicAI::getAllFileGroups();
echo "<p>Available groups: " . implode(", ", $groups) . "</p>";
echo "<p>Total groups found: " . count($groups) . "</p>";

// Test changeGroupOfFile with a sample file
echo "<h2>Testing changeGroupOfFile()</h2>";
// First, let's find a file to test with
$fileSQL = "SELECT BID, BFILEPATH FROM BMESSAGES WHERE BUSERID = 2 AND BFILE > 0 LIMIT 1";
$fileRes = db::Query($fileSQL);
$fileArr = db::FetchArr($fileRes);

if ($fileArr) {
    echo "<p>Testing with file ID: " . $fileArr['BID'] . " (" . $fileArr['BFILEPATH'] . ")</p>";
    
    // Test changing to a new group
    $result = BasicAI::changeGroupOfFile($fileArr['BID'], 'TEST_GROUP');
    echo "<p>Change to 'TEST_GROUP': " . ($result['success'] ? 'SUCCESS' : 'FAILED - ' . $result['error']) . "</p>";
    
    // Test changing to empty group
    $result2 = BasicAI::changeGroupOfFile($fileArr['BID'], '');
    echo "<p>Change to empty group: " . ($result2['success'] ? 'SUCCESS' : 'FAILED - ' . $result2['error']) . "</p>";
    
    // Test changing to another group
    $result3 = BasicAI::changeGroupOfFile($fileArr['BID'], 'ANOTHER_TEST');
    echo "<p>Change to 'ANOTHER_TEST': " . ($result3['success'] ? 'SUCCESS' : 'FAILED - ' . $result3['error']) . "</p>";
    
} else {
    echo "<p>No files found for testing</p>";
}

// Show current file groups after changes
echo "<h2>Current File Groups After Testing</h2>";
$groupsAfter = BasicAI::getAllFileGroups();
echo "<p>Available groups: " . implode(", ", $groupsAfter) . "</p>";
echo "<p>Total groups found: " . count($groupsAfter) . "</p>";

echo "<h2>Test Complete</h2>";
?> 
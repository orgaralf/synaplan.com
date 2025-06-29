<?php
// Test file to verify message grouping logic
session_start();

// Include the core files
require_once('inc/_coreincludes.php');

// Set a test user ID (you may need to adjust this based on your test data)
$_SESSION["USERPROFILE"]["BID"] = 2; // Using user ID 2 from the sample data

echo "<h1>Testing Message Grouping Logic</h1>";

// Test the getLatestChats function
echo "<h2>Original Messages (before grouping):</h2>";
$originalSQL = "SELECT BID, BTRACKID, BUNIXTIMES, BDIRECT, BTEXT, BFILE, BFILEPATH, BFILETYPE FROM BMESSAGES WHERE BUSERID = 2 ORDER BY BID DESC LIMIT 20";
$originalRes = DB::Query($originalSQL);
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>BID</th><th>BTRACKID</th><th>BUNIXTIMES</th><th>BDIRECT</th><th>BTEXT</th><th>BFILE</th><th>BFILEPATH</th></tr>";
while($row = DB::FetchArr($originalRes)) {
    echo "<tr>";
    echo "<td>{$row['BID']}</td>";
    echo "<td>{$row['BTRACKID']}</td>";
    echo "<td>{$row['BUNIXTIMES']}</td>";
    echo "<td>{$row['BDIRECT']}</td>";
    echo "<td>" . substr($row['BTEXT'], 0, 50) . "...</td>";
    echo "<td>{$row['BFILE']}</td>";
    echo "<td>{$row['BFILEPATH']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Grouped Messages (after grouping):</h2>";
$groupedMessages = Frontend::getLatestChats(20, "DESC");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>BID</th><th>BTRACKID</th><th>BUNIXTIMES</th><th>BDIRECT</th><th>BTEXT</th><th>FILECOUNT</th><th>GROUPED_FILES</th></tr>";
foreach($groupedMessages as $message) {
    echo "<tr>";
    echo "<td>{$message['BID']}</td>";
    echo "<td>{$message['BTRACKID']}</td>";
    echo "<td>{$message['BUNIXTIMES']}</td>";
    echo "<td>{$message['BDIRECT']}</td>";
    echo "<td>" . substr($message['BTEXT'], 0, 50) . "...</td>";
    echo "<td>{$message['FILECOUNT']}</td>";
    echo "<td>" . (isset($message['GROUPED_FILES']) ? 'Yes' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the getMessageFiles function for a specific message
echo "<h2>Testing getMessageFiles for a grouped message:</h2>";
if(count($groupedMessages) > 0) {
    $testMessageId = $groupedMessages[0]['BID'];
    echo "<p>Testing message ID: $testMessageId</p>";
    
    $files = Frontend::getMessageFiles($testMessageId);
    echo "<p>Files found: " . count($files) . "</p>";
    
    if(count($files) > 0) {
        echo "<ul>";
        foreach($files as $file) {
            echo "<li>File: {$file['BFILEPATH']} (Type: {$file['BFILETYPE']})</li>";
        }
        echo "</ul>";
    }
}

echo "<h2>Summary:</h2>";
echo "<p>Original message count: " . DB::NumRows($originalRes) . "</p>";
echo "<p>Grouped message count: " . count($groupedMessages) . "</p>";
echo "<p>Grouping reduced message count by: " . (DB::NumRows($originalRes) - count($groupedMessages)) . " messages</p>";
?> 
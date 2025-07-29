<?php
// Simple test script to verify widget functionality
require_once('inc/_confsys.php');
require_once('inc/_confdb.php');

echo "<h1>Widget System Test</h1>";

// Test 1: Check if we can connect to the database
echo "<h2>Test 1: Database Connection</h2>";
try {
    $testSQL = "SELECT COUNT(*) as count FROM BCONFIG";
    $res = db::Query($testSQL);
    if ($res) {
        $row = db::FetchArr($res);
        echo "✅ Database connection successful. BCONFIG table has " . $row['count'] . " records.<br>";
    } else {
        echo "❌ Database connection failed.<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Test widget configuration retrieval
echo "<h2>Test 2: Widget Configuration Retrieval</h2>";
$testUserId = 1;
$testWidgetId = 1;
$group = "widget_" . $testWidgetId;

$sql = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = " . $testUserId . " AND BGROUP = '" . db::EscString($group) . "'";
$res = db::Query($sql);

if ($res) {
    $config = [
        'color' => '#007bff',
        'position' => 'bottom-right',
        'autoMessage' => '',
        'prompt' => 'general'
    ];
    
    while ($row = db::FetchArr($res)) {
        $config[$row['BSETTING']] = $row['BVALUE'];
    }
    
    echo "✅ Widget configuration retrieved successfully:<br>";
    echo "<pre>" . print_r($config, true) . "</pre>";
} else {
    echo "❌ Failed to retrieve widget configuration.<br>";
}

// Test 3: Test widget creation
echo "<h2>Test 3: Widget Creation</h2>";
$testUserId = 999; // Use a test user ID
$testWidgetId = 1;
$group = "widget_" . $testWidgetId;

// Clean up any existing test data
$cleanupSQL = "DELETE FROM BCONFIG WHERE BOWNERID = " . $testUserId . " AND BGROUP = '" . db::EscString($group) . "'";
db::Query($cleanupSQL);

// Create test widget
$settings = [
    'color' => '#ff6b6b',
    'position' => 'bottom-left',
    'autoMessage' => 'Hello from test widget!',
    'prompt' => 'general'
];

foreach ($settings as $setting => $value) {
    $insertSQL = "INSERT INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE) VALUES (" . $testUserId . ", '" . db::EscString($group) . "', '" . db::EscString($setting) . "', '" . db::EscString($value) . "')";
    $result = db::Query($insertSQL);
    if ($result) {
        echo "✅ Created setting: $setting = $value<br>";
    } else {
        echo "❌ Failed to create setting: $setting<br>";
    }
}

// Verify the widget was created
$verifySQL = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BOWNERID = " . $testUserId . " AND BGROUP = '" . db::EscString($group) . "'";
$verifyRes = db::Query($verifySQL);

if ($verifyRes && db::CountRows($verifyRes) == 4) {
    echo "✅ Widget creation verified successfully.<br>";
} else {
    echo "❌ Widget creation verification failed.<br>";
}

// Clean up test data
$cleanupSQL = "DELETE FROM BCONFIG WHERE BOWNERID = " . $testUserId . " AND BGROUP = '" . db::EscString($group) . "'";
db::Query($cleanupSQL);
echo "🧹 Test data cleaned up.<br>";

// Test 4: Test widget URL generation
echo "<h2>Test 4: Widget URL Generation</h2>";
$baseUrl = $GLOBALS["baseUrl"];
$widgetUrl = $baseUrl . "widget.php?uid=" . $testUserId . "&widgetid=" . $testWidgetId;
echo "✅ Widget URL generated: <a href='$widgetUrl' target='_blank'>$widgetUrl</a><br>";

// Test 5: Test widgetloader URL
echo "<h2>Test 5: Widget Loader URL</h2>";
$loaderUrl = $baseUrl . "widgetloader.php?uid=" . $testUserId . "&widgetid=" . $testWidgetId;
echo "✅ Widget loader URL: <a href='$loaderUrl' target='_blank'>$loaderUrl</a><br>";

echo "<h2>Test Complete!</h2>";
echo "If all tests passed, the widget system should be working correctly.<br>";
echo "You can now:<br>";
echo "1. Go to the web widget configuration page to create widgets<br>";
echo "2. Use the integration code to embed widgets on websites<br>";
echo "3. Test widgets using the widgettest.php page<br>";
?> 
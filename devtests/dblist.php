<?php
// -----------------------------------------------------
// Database Table Lister
// Lists all tables in the synaplan database
// -----------------------------------------------------

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define server variable for database connection
$server = $_SERVER['SERVER_NAME'] ?? 'localhost';

// Include the database configuration
require_once '../web/inc/_confdb.php';

// Set page title
$pageTitle = "Database Tables - Synaplan";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        .content {
            padding: 20px;
        }
        .db-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .db-info h3 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .db-info p {
            margin: 5px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #667eea;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 0.5px;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        tr:hover a {
            color: #764ba2 !important;
            text-decoration: underline !important;
        }
        .table-count {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: 600;
            color: #495057;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Database Tables</h1>
            <p>Synaplan Database Schema Overview</p>
        </div>
        
        <div class="content">
            <?php
            // Check if database connection is successful
            if (!$GLOBALS["dbcon"]) {
                echo '<div class="error">';
                echo '<strong>Database Connection Error:</strong> Could not connect to the database.';
                echo '</div>';
                exit;
            }
            
            // Display database connection information
            echo '<div class="db-info">';
            echo '<h3>Database Connection Details</h3>';
            echo '<p><strong>Host:</strong> ' . htmlspecialchars(DB_HOST) . '</p>';
            echo '<p><strong>Database:</strong> ' . htmlspecialchars(DB_NAME) . '</p>';
            echo '<p><strong>User:</strong> ' . htmlspecialchars(DB_USER) . '</p>';
            echo '<p><strong>Charset:</strong> ' . htmlspecialchars(DB_CHARSET) . '</p>';
            echo '<p><strong>Connection Status:</strong> <span style="color: green;">✓ Connected</span></p>';
            echo '</div>';
            
            try {
                // Query to get all tables in the database
                $sql = "SHOW TABLES FROM " . DB_NAME;
                $result = db::Query($sql);
                
                if ($result === false) {
                    echo '<div class="error">';
                    echo '<strong>Query Error:</strong> ' . htmlspecialchars(db::Error());
                    echo '</div>';
                } else {
                    $tableCount = db::CountRows($result);
                    
                    echo '<div class="table-count">';
                    echo 'Total Tables Found: ' . $tableCount;
                    echo '</div>';
                    
                    if ($tableCount > 0) {
                        echo '<table>';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>#</th>';
                        echo '<th>Table Name</th>';
                        echo '<th>Table Type</th>';
                        echo '<th>Engine</th>';
                        echo '<th>Rows</th>';
                        echo '<th>Data Length</th>';
                        echo '<th>Index Length</th>';
                        echo '<th>Auto Increment</th>';
                        echo '<th>Create Time</th>';
                        echo '<th>Update Time</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        $counter = 1;
                        
                        // First, get basic table list
                        $tableList = [];
                        while ($row = db::FetchArr($result)) {
                            $tableName = array_values($row)[0]; // Get the first (and only) value
                            $tableList[] = $tableName;
                        }
                        
                        // Now get detailed information for each table
                        foreach ($tableList as $tableName) {
                            $detailSql = "SHOW TABLE STATUS LIKE '" . db::EscString($tableName) . "'";
                            $detailResult = db::Query($detailSql);
                            
                            if ($detailResult && $detailRow = db::FetchArr($detailResult)) {
                                echo '<tr>';
                                echo '<td>' . $counter . '</td>';
                                echo '<td><strong><a href="dbentries.php?table=' . urlencode($tableName) . '" style="color: #667eea; text-decoration: none;">' . htmlspecialchars($tableName) . '</a></strong></td>';
                                echo '<td>' . htmlspecialchars($detailRow['Comment'] ?: 'N/A') . '</td>';
                                echo '<td>' . htmlspecialchars($detailRow['Engine'] ?: 'N/A') . '</td>';
                                echo '<td>' . number_format($detailRow['Rows'] ?: 0) . '</td>';
                                echo '<td>' . formatBytes($detailRow['Data_length'] ?: 0) . '</td>';
                                echo '<td>' . formatBytes($detailRow['Index_length'] ?: 0) . '</td>';
                                echo '<td>' . ($detailRow['Auto_increment'] ?: 'N/A') . '</td>';
                                echo '<td>' . ($detailRow['Create_time'] ?: 'N/A') . '</td>';
                                echo '<td>' . ($detailRow['Update_time'] ?: 'N/A') . '</td>';
                                echo '</tr>';
                            } else {
                                // Fallback if detailed info not available
                                echo '<tr>';
                                echo '<td>' . $counter . '</td>';
                                echo '<td><strong><a href="dbentries.php?table=' . urlencode($tableName) . '" style="color: #667eea; text-decoration: none;">' . htmlspecialchars($tableName) . '</a></strong></td>';
                                echo '<td colspan="8">Basic table information only</td>';
                                echo '</tr>';
                            }
                            
                            $counter++;
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        
                        echo '<div class="success">';
                        echo '<strong>Success!</strong> Retrieved ' . $tableCount . ' tables from the database.';
                        echo '</div>';
                    } else {
                        echo '<div class="error">';
                        echo '<strong>No Tables Found:</strong> The database appears to be empty or no tables were found.';
                        echo '</div>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<strong>Exception:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="footer">
            <p>Generated on <?php echo date('Y-m-d H:i:s'); ?> | Synaplan Database Schema</p>
        </div>
    </div>
</body>
</html>

<?php
// Helper function to format bytes into human readable format
function formatBytes($bytes, $precision = 2) {
    if ($bytes == 0) return '0 B';
    
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?> 
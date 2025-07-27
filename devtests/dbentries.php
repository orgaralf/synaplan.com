<?php
// -----------------------------------------------------
// Database Table Entries Viewer
// Shows entries from a specific table with pagination
// -----------------------------------------------------

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define server variable for database connection
$server = $_SERVER['SERVER_NAME'] ?? 'localhost';

// Include the database configuration
require_once '../web/inc/_confdb.php';

// Get table name from URL parameter
$tableName = $_GET['table'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$entriesPerPage = 20;
$offset = ($page - 1) * $entriesPerPage;

// Validate table name (basic security)
if (empty($tableName) || !preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
    $error = "Invalid table name provided.";
    $tableName = "";
}

// Set page title
$pageTitle = "Table Entries - " . htmlspecialchars($tableName);

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
            max-width: 1400px;
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
        .table-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .table-info h3 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .table-info p {
            margin: 5px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-size: 0.9em;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        th {
            background-color: #667eea;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8em;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            gap: 10px;
        }
        .pagination button {
            background-color: #667eea;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .pagination button:hover {
            background-color: #764ba2;
        }
        .pagination button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .pagination .page-info {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
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
        .data-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .data-cell:hover {
            white-space: normal;
            word-break: break-word;
            max-width: none;
            position: relative;
            z-index: 5;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border-radius: 3px;
        }
        .scroll-container {
            overflow-x: auto;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Table Entries</h1>
            <p>Viewing data from: <?php echo htmlspecialchars($tableName); ?></p>
        </div>
        
        <div class="content">
            <a href="dblist.php" class="back-link">← Back to Database Tables</a>
            
            <?php
            if (!empty($error)) {
                echo '<div class="error">';
                echo '<strong>Error:</strong> ' . htmlspecialchars($error);
                echo '</div>';
            } else {
                // Check if database connection is successful
                if (!$GLOBALS["dbcon"]) {
                    echo '<div class="error">';
                    echo '<strong>Database Connection Error:</strong> Could not connect to the database.';
                    echo '</div>';
                } else {
                    try {
                        // Get table information
                        $tableInfoSql = "SHOW TABLE STATUS LIKE '" . db::EscString($tableName) . "'";
                        $tableInfoResult = db::Query($tableInfoSql);
                        $tableInfo = db::FetchArr($tableInfoResult);
                        
                        // Display table information
                        echo '<div class="table-info">';
                        echo '<h3>Table Information</h3>';
                        echo '<p><strong>Table Name:</strong> ' . htmlspecialchars($tableName) . '</p>';
                        if ($tableInfo) {
                            echo '<p><strong>Engine:</strong> ' . htmlspecialchars($tableInfo['Engine'] ?: 'N/A') . '</p>';
                            echo '<p><strong>Total Rows:</strong> ' . number_format($tableInfo['Rows'] ?: 0) . '</p>';
                            echo '<p><strong>Data Size:</strong> ' . formatBytes($tableInfo['Data_length'] ?: 0) . '</p>';
                            echo '<p><strong>Index Size:</strong> ' . formatBytes($tableInfo['Index_length'] ?: 0) . '</p>';
                        }
                        echo '</div>';
                        
                        // Get total count of records
                        $countSql = "SELECT COUNT(*) as total FROM `" . db::EscString($tableName) . "`";
                        $countResult = db::Query($countSql);
                        $countRow = db::FetchArr($countResult);
                        $totalRecords = $countRow['total'] ?? 0;
                        $totalPages = ceil($totalRecords / $entriesPerPage);
                        
                        // Get column information
                        $columnsSql = "SHOW COLUMNS FROM `" . db::EscString($tableName) . "`";
                        $columnsResult = db::Query($columnsSql);
                        $columns = [];
                        while ($column = db::FetchArr($columnsResult)) {
                            $columns[] = $column;
                        }
                        
                        if (empty($columns)) {
                            echo '<div class="error">';
                            echo '<strong>Error:</strong> Could not retrieve column information for this table.';
                            echo '</div>';
                        } else {
                            // Get data with pagination
                            $dataSql = "SELECT * FROM `" . db::EscString($tableName) . "` LIMIT " . $offset . ", " . $entriesPerPage;
                            $dataResult = db::Query($dataSql);
                            
                            if ($dataResult === false) {
                                echo '<div class="error">';
                                echo '<strong>Query Error:</strong> ' . htmlspecialchars(db::Error());
                                echo '</div>';
                            } else {
                                $recordCount = db::CountRows($dataResult);
                                
                                echo '<div class="pagination">';
                                echo '<div class="page-info">';
                                echo 'Showing ' . ($offset + 1) . ' to ' . ($offset + $recordCount) . ' of ' . number_format($totalRecords) . ' entries';
                                echo '</div>';
                                echo '</div>';
                                
                                if ($recordCount > 0) {
                                    echo '<div class="scroll-container">';
                                    echo '<table>';
                                    echo '<thead>';
                                    echo '<tr>';
                                    foreach ($columns as $column) {
                                        echo '<th>' . htmlspecialchars($column['Field']) . '</th>';
                                    }
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    
                                    while ($row = db::FetchArr($dataResult)) {
                                        echo '<tr>';
                                        foreach ($columns as $column) {
                                            $fieldName = $column['Field'];
                                            $value = $row[$fieldName] ?? '';
                                            
                                            // Format the value based on data type
                                            $formattedValue = formatCellValue($value, $column['Type']);
                                            
                                            echo '<td class="data-cell" title="' . htmlspecialchars($value) . '">';
                                            echo htmlspecialchars($formattedValue);
                                            echo '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                    
                                    // Pagination controls
                                    if ($totalPages > 1) {
                                        echo '<div class="pagination">';
                                        
                                        // Previous button
                                        if ($page > 1) {
                                            echo '<a href="?table=' . urlencode($tableName) . '&page=' . ($page - 1) . '">';
                                            echo '<button type="button">← Previous</button>';
                                            echo '</a>';
                                        } else {
                                            echo '<button type="button" disabled>← Previous</button>';
                                        }
                                        
                                        // Page numbers
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        
                                        if ($startPage > 1) {
                                            echo '<a href="?table=' . urlencode($tableName) . '&page=1">';
                                            echo '<button type="button">1</button>';
                                            echo '</a>';
                                            if ($startPage > 2) {
                                                echo '<span style="padding: 10px;">...</span>';
                                            }
                                        }
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++) {
                                            if ($i == $page) {
                                                echo '<button type="button" style="background-color: #764ba2;" disabled>' . $i . '</button>';
                                            } else {
                                                echo '<a href="?table=' . urlencode($tableName) . '&page=' . $i . '">';
                                                echo '<button type="button">' . $i . '</button>';
                                                echo '</a>';
                                            }
                                        }
                                        
                                        if ($endPage < $totalPages) {
                                            if ($endPage < $totalPages - 1) {
                                                echo '<span style="padding: 10px;">...</span>';
                                            }
                                            echo '<a href="?table=' . urlencode($tableName) . '&page=' . $totalPages . '">';
                                            echo '<button type="button">' . $totalPages . '</button>';
                                            echo '</a>';
                                        }
                                        
                                        // Next button
                                        if ($page < $totalPages) {
                                            echo '<a href="?table=' . urlencode($tableName) . '&page=' . ($page + 1) . '">';
                                            echo '<button type="button">Next →</button>';
                                            echo '</a>';
                                        } else {
                                            echo '<button type="button" disabled>Next →</button>';
                                        }
                                        
                                        echo '</div>';
                                    }
                                    
                                    echo '<div class="success">';
                                    echo '<strong>Success!</strong> Retrieved ' . $recordCount . ' entries from table "' . htmlspecialchars($tableName) . '".';
                                    echo '</div>';
                                } else {
                                    echo '<div class="error">';
                                    echo '<strong>No Data:</strong> This table contains no data or the requested page is out of range.';
                                    echo '</div>';
                                }
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo '<div class="error">';
                        echo '<strong>Exception:</strong> ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                    }
                }
            }
            ?>
        </div>
        
        <div class="footer">
            <p>Generated on <?php echo date('Y-m-d H:i:s'); ?> | Synaplan Database Browser</p>
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

// Helper function to format cell values based on data type
function formatCellValue($value, $dataType) {
    if ($value === null) {
        return '<em>NULL</em>';
    }
    
    if ($value === '') {
        return '<em>empty</em>';
    }
    
    // Check if it's a date/time type
    if (preg_match('/(datetime|timestamp|date|time)/i', $dataType)) {
        if (strtotime($value) !== false) {
            return $value;
        }
    }
    
    // Check if it's a numeric type
    if (preg_match('/(int|decimal|float|double)/i', $dataType)) {
        if (is_numeric($value)) {
            return number_format($value);
        }
    }
    
    // For text fields, truncate if too long
    if (strlen($value) > 100) {
        return substr($value, 0, 100) . '...';
    }
    
    return $value;
}
?> 
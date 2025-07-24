<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="contentMain">
    <?php
        // Handle form submission for config updates
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config'])) {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'config_') === 0) {
                    $setting = str_replace('config_', '', $key);
                    $modelId = intval($value);
                    
                    // Update or insert config
                    $checkSQL = "SELECT BID FROM BCONFIG WHERE BGROUP = 'DEFAULTMODEL' AND BSETTING = '" . DB::EscString($setting) . "'";
                    $checkRES = DB::query($checkSQL);
                    
                    if (DB::CountRows($checkRES) > 0) {
                        $checkROW = DB::FetchArr($checkRES);
                        $updateSQL = "UPDATE BCONFIG SET BVALUE = '" . DB::EscString($modelId) . "' WHERE BID = " . intval($checkROW['BID']);
                        DB::query($updateSQL);
                    } else {
                        $insertSQL = "INSERT INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE) VALUES (0, 'DEFAULTMODEL', '" . DB::EscString($setting) . "', '" . DB::EscString($modelId) . "')";
                        DB::query($insertSQL);
                    }
                }
            }
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> Default model configurations have been updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
    ?>

    <!-- Config Section -->
    <div class="card mb-4 mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Default Model Configuration</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <?php
                        // Get all available tasks from BCONFIG
                        $taskSQL = "SELECT DISTINCT BSETTING FROM BCONFIG WHERE BGROUP = 'DEFAULTMODEL' ORDER BY BSETTING";
                        $taskRES = DB::query($taskSQL);
                        
                        // Get all available models for dropdowns
                        $allModelsSQL = "SELECT BID, BNAME, BTAG, BSERVICE, BSELECTABLE FROM BMODELS ORDER BY BTAG, BNAME";
                        $allModelsRES = DB::query($allModelsSQL);
                        $allModels = [];
                        while ($modelROW = DB::FetchArr($allModelsRES)) {
                            $allModels[] = $modelROW;
                        }
                        
                        // Get current config values
                        $configSQL = "SELECT BSETTING, BVALUE FROM BCONFIG WHERE BGROUP = 'DEFAULTMODEL'";
                        $configRES = DB::query($configSQL);
                        $currentConfig = [];
                        while ($configROW = DB::FetchArr($configRES)) {
                            $currentConfig[$configROW['BSETTING']] = $configROW['BVALUE'];
                        }
                        
                        while ($taskROW = DB::FetchArr($taskRES)) {
                            $task = $taskROW['BSETTING'];
                            $currentValue = isset($currentConfig[$task]) ? $currentConfig[$task] : '';
                            
                            echo '<div class="col-md-6 col-lg-4 mb-3">';
                            echo '<label for="config_' . htmlspecialchars($task) . '" class="form-label">' . htmlspecialchars($task) . '</label>';
                            echo '<select class="form-select" id="config_' . htmlspecialchars($task) . '" name="config_' . htmlspecialchars($task) . '">';
                            echo '<option value="">-- Select Model --</option>';
                            
                            // Group models by tag for better organization
                            $modelsByTag = [];
                            foreach ($allModels as $model) {
                                $tag = $model['BTAG'];
                                if (!isset($modelsByTag[$tag])) {
                                    $modelsByTag[$tag] = [];
                                }
                                $modelsByTag[$tag][] = $model;
                            }
                            
                            foreach ($modelsByTag as $tag => $models) {
                                echo '<optgroup label="' . htmlspecialchars(strtoupper($tag)) . '">';
                                foreach ($models as $model) {
                                    $selected = ($currentValue == $model['BID']) ? 'selected' : '';
                                    $disabled = ($model['BSELECTABLE'] == 0) ? 'disabled' : '';
                                    $modelLabel = htmlspecialchars($model['BNAME']) . ' (' . htmlspecialchars($model['BSERVICE']) . ')';
                                    if ($model['BSELECTABLE'] == 0) {
                                        $modelLabel .= ' [System Model]';
                                    }
                                    echo '<option value="' . $model['BID'] . '" ' . $selected . ' ' . $disabled . '>';
                                    echo $modelLabel;
                                    echo '</option>';
                                }
                                echo '</optgroup>';
                            }
                            
                            echo '</select>';
                            echo '</div>';
                        }
                    ?>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" name="update_config" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Configuration
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4 mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0">Models &amp; Purposes</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <?php
                        $tagSQL = "SELECT DISTINCT BTAG FROM BMODELS ORDER BY BTAG";
                        $tagRES = DB::query($tagSQL);
                        while($tagROW = DB::FetchArr($tagRES)) {
                            echo '<a href="index.php/aimodels?tag=' . htmlspecialchars($tagROW["BTAG"]) . '" class="btn btn-outline-primary me-2 mb-2">' . 
                                 htmlspecialchars($tagROW["BTAG"]) . '</a>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Models Table Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Available Models</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PURPOSE</th>
                            <th>SERVICE</th>
                            <th>NAME</th>
                            <th>DESCRIPTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $whereClause = "";
                            if (isset($_GET['tag']) && !empty($_GET['tag'])) {
                                $whereClause = "WHERE BTAG = '" . db::EscString($_GET['tag']) . "'";
                            }
                            if(isset($_REQUEST["tag"])) {
                                $whereClause = "WHERE BTAG like '".DB::EscString($_REQUEST["tag"])."'";
                            }
                            $modelsSQL = "SELECT * FROM BMODELS $whereClause ORDER BY BSERVICE,BID";
                            $modelsRES = db::Query($modelsSQL);
                            
                            if (db::CountRows($modelsRES) > 0) {
                                while($modelROW = db::FetchArr($modelsRES)) {
                                    $detailArr = json_decode($modelROW["BJSON"], true);
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($modelROW["BID"]) . "</td>";
                                    echo "<td><B>" . htmlspecialchars($modelROW["BTAG"]) . "</B></td>";
                                    echo "<td>" . htmlspecialchars($modelROW["BSERVICE"]) . "</td>";
                                    echo "<td><B>" . htmlspecialchars($modelROW["BNAME"]) . "</B></td>";
                                    echo "<td>" . htmlspecialchars($detailArr["description"]) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>No models found</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
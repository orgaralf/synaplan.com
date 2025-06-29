<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="contentMain">
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
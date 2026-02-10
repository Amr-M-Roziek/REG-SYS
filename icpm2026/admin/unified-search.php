<?php
session_start();
$currentPage = 'unified-search';
include 'dbconnection.php';
// Preserve main connection
$con_main = $con;

require_once 'includes/auth_helper.php';

// Check login
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

$searchResults = [];
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searched = false;

// Helper to connect to DB
function connectToDB($host, $user, $pass, $dbname) {
    try {
        $c = @mysqli_connect($host, $user, $pass, $dbname);
        return $c;
    } catch (Exception $e) {
        return false;
    }
}

// Helper to search a DB
function searchDB($con, $sourceName, $term, $dbParam) {
    $results = [];
    if (!$con) return [];
    
    $termLike = "%$term%";
    // Check if table users exists just in case
    if ($result = mysqli_query($con, "SHOW TABLES LIKE 'users'")) {
        if(mysqli_num_rows($result) > 0) {
            $sql = "SELECT * FROM users WHERE fname LIKE ? OR lname LIKE ? OR email LIKE ? OR id LIKE ? OR organization LIKE ?";
            $stmt = mysqli_prepare($con, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssss", $termLike, $termLike, $termLike, $termLike, $termLike);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) {
                    $row['source'] = $sourceName;
                    $row['db_param'] = $dbParam;
                    // Normalize date
                    if (isset($row['created_at'])) $row['display_date'] = $row['created_at'];
                    elseif (isset($row['posting_date'])) $row['display_date'] = $row['posting_date'];
                    else $row['display_date'] = 'N/A';
                    
                    $results[] = $row;
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    return $results;
}

if (!empty($searchTerm)) {
    $searched = true;
    
    // 1. Search Main DB (regsys_reg)
    $searchResults = array_merge($searchResults, searchDB($con_main, 'Main Admin', $searchTerm, 'reg'));
    
    // 2. Search Participant DB (regsys_participant)
    $con_part = connectToDB('localhost', 'root', '', 'regsys_participant');
    if (!$con_part) $con_part = connectToDB('localhost', 'regsys_part', 'regsys@2025', 'regsys_participant');
    
    if ($con_part) {
        $searchResults = array_merge($searchResults, searchDB($con_part, 'Participant Admin', $searchTerm, 'participant'));
        mysqli_close($con_part);
    }
    
    // 3. Search Poster DB (regsys_poster26)
    $con_poster = connectToDB('localhost', 'root', '', 'regsys_poster26');
    if (!$con_poster) $con_poster = connectToDB('localhost', 'regsys_poster', 'regsys@2025', 'regsys_poster26');
    
    if ($con_poster) {
        $searchResults = array_merge($searchResults, searchDB($con_poster, 'Poster Admin', $searchTerm, 'poster'));
        mysqli_close($con_poster);
    }

    // 4. Search Workshop DB (regsys_workshop)
    $con_workshop = connectToDB('localhost', 'root', '', 'regsys_workshop');
    if (!$con_workshop) $con_workshop = connectToDB('localhost', 'regsys_ws', 'regsys@2025', 'regsys_workshop');
    
    if ($con_workshop) {
        $searchResults = array_merge($searchResults, searchDB($con_workshop, 'Workshop Admin', $searchTerm, 'workshop'));
        mysqli_close($con_workshop);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Dashboard">
    <meta name="keyword" content="Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">
    <title>Unified Database Search | Admin</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
</head>

<body>

<section id="container" >
    <?php include("includes/header.php");?>
    <?php include("includes/sidebar.php");?>

    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Unified Database Search</h3>
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="content-panel">
                        <h4><i class="fa fa-search"></i> Search Across All Systems (Main, Participant, Poster)</h4>
                        <div class="panel-body">
                            <form class="form-inline" method="GET">
                                <div class="form-group">
                                    <input type="text" class="form-control" name="search" placeholder="Name, Email, ID..." value="<?php echo htmlspecialchars($searchTerm); ?>" style="width: 300px;">
                                </div>
                                <button type="submit" class="btn btn-theme">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($searched): ?>
            <div class="row mt">
                <div class="col-md-12">
                    <div class="content-panel">
                        <table class="table table-striped table-advance table-hover">
                            <h4><i class="fa fa-users"></i> Search Results (<?php echo count($searchResults); ?> found)</h4>
                            <hr>
                            <thead>
                            <tr>
                                <th>Source</th>
                                <th>Ref Number</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Organization</th>
                                <th>Reg. Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($searchResults) > 0): ?>
                                <?php foreach ($searchResults as $row): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            $badgeClass = 'label-default';
                                            if ($row['source'] == 'Main Admin') $badgeClass = 'label-primary';
                                            elseif ($row['source'] == 'Participant Admin') $badgeClass = 'label-success';
                                            elseif ($row['source'] == 'Poster Admin') $badgeClass = 'label-warning';
                                            elseif ($row['source'] == 'Workshop Admin') $badgeClass = 'label-info';
                                        ?>
                                        <span class="label <?php echo $badgeClass; ?> label-mini"><?php echo $row['source']; ?></span>
                                    </td>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['fname']; ?></td>
                                    <td><?php echo $row['lname']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['organization']; ?></td>
                                    <td><?php echo $row['display_date']; ?></td>
                                    <td>
                                        <a href="update-profile.php?uid=<?php echo $row['id']; ?>&db=<?php echo $row['db_param']; ?>" class="btn btn-primary btn-xs" target="_blank"><i class="fa fa-pencil"></i> Edit</a>
                                        <a href="welcome.php?uid=<?php echo $row['id']; ?>&db=<?php echo $row['db_param']; ?>" class="btn btn-warning btn-xs" target="_blank"><i class="fa fa-print"></i> Print Badge</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No results found for "<?php echo htmlspecialchars($searchTerm); ?>"</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </section>
    </section>
    
    <footer class="site-footer">
        <div class="text-center">
            2026 ICPM
            <a href="#" class="go-top">
                <i class="fa fa-angle-up"></i>
            </a>
        </div>
    </footer>
</section>

<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="assets/js/jquery.scrollTo.min.js"></script>
<script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
<script src="assets/js/common-scripts.js"></script>

</body>
</html>

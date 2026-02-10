<?php
session_start();
include '../admin/dbconnection.php';
require_once '../admin/includes/auth_helper.php';

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:index.php');
    exit();
}

if (!check_permission('user_view')) {
    header('location:../admin/manage-users.php?error=access_denied');
    exit();
}

// Reuse the connection logic from main-dashboard (duplicated for now to minimize risk of breaking main-dashboard by refactoring)
function get_workshop_connection()
{
    $whitelist = array('127.0.0.1', '::1', 'localhost');
    $dockerHost = getenv('DB_HOST');
    if ($dockerHost) {
        $host = $dockerHost;
        $user = 'regsys_ws';
        $pass = 'regsys@2025';
    } elseif (in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist)) {
        $host = '127.0.0.1';
        $user = 'root';
        $pass = '';
    } else {
        $host = 'localhost';
        $user = 'regsys_ws';
        $pass = 'regsys@2025';
    }
    $db = 'regsys_workshop';
    $conn = @mysqli_connect($host, $user, $pass, $db);
    if ($conn) {
        mysqli_set_charset($conn, 'utf8mb4');
    }
    return $conn;
}

function escape_html_ws($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$workshopCon = get_workshop_connection();
$workshopLatest = [];
if ($workshopCon) {
    // Fetch more than 20 since it's a dedicated page now, maybe 100? Or keep 20 as "Latest"
    // The user said "Latest 20" in the section title, but on a dedicated page "Latest 20" might be too few.
    // However, sticking to the user's "MOVE THIS SECTION" implies keeping the content similar.
    // But usually a dedicated page implies "All" or "More".
    // I'll fetch 50 for now to be safe, or just stick to 20 if I want to be literal.
    // Let's do 50.
    $res = mysqli_query($workshopCon, "SELECT id, fname, lname, email, category, organization FROM users ORDER BY id DESC LIMIT 100");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $workshopLatest[] = $row;
        }
    }
}

$currentPage = 'workshop-registrations';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshop Registrations</title>
    <link href="../admin/assets/css/bootstrap.css" rel="stylesheet">
    <link href="../admin/assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="../admin/assets/css/style.css" rel="stylesheet">
    <link href="../admin/assets/css/style-responsive.css" rel="stylesheet">
</head>
<body>
<section id="container">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Workshop Registrations</h3>
            
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="content-panel">
                        <h4><i class="fa fa-angle-right"></i> Latest Registrations</h4>
                        <table class="table table-striped table-advance table-hover">
                            <thead>
                            <tr>
                                <th>Ref Number</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Category</th>
                                <th>Organization</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($workshopLatest) === 0): ?>
                                <tr>
                                    <td colspan="5">No workshop registrations found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($workshopLatest as $row): ?>
                                    <tr>
                                        <td><?php echo escape_html_ws($row['id']); ?></td>
                                        <td><?php echo escape_html_ws(trim($row['fname'] . ' ' . $row['lname'])); ?></td>
                                        <td><?php echo escape_html_ws($row['email']); ?></td>
                                        <td><?php echo escape_html_ws($row['category']); ?></td>
                                        <td><?php echo escape_html_ws($row['organization']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </section>
</section>

<script src="../admin/assets/js/jquery.js"></script>
<script src="../admin/assets/js/bootstrap.min.js"></script>
<script class="include" type="text/javascript" src="../admin/assets/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="../admin/assets/js/jquery.scrollTo.min.js"></script>
<script src="../admin/assets/js/jquery.nicescroll.js" type="text/javascript"></script>
<script src="../admin/assets/js/common-scripts.js"></script>
</body>
</html>

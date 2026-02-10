<?php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

if (!check_permission('user_view')) {
    header('location:manage-users.php?error=access_denied');
    exit();
}

function get_module_connection($module)
{
    static $cache = [];
    if (isset($cache[$module])) {
        return $cache[$module];
    }

    $conn = null;

    if ($module === 'reg') {
        global $con;
        $conn = $con;
        if ($conn) {
            mysqli_set_charset($conn, 'utf8mb4');
        }
        $cache[$module] = $conn;
        return $conn;
    }

    if ($module === 'participant') {
        $whitelist = array('127.0.0.1', '::1', 'localhost');
        $dockerHost = getenv('DB_HOST');
        if ($dockerHost) {
            $host = $dockerHost;
            $user = 'regsys_part';
            $pass = 'regsys@2025';
        } elseif (in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist)) {
            $host = '127.0.0.1';
            $user = 'root';
            $pass = '';
        } else {
            $host = 'localhost';
            $user = 'regsys_part';
            $pass = 'regsys@2025';
        }
        $db = 'regsys_participant';
        $conn = @mysqli_connect($host, $user, $pass, $db);
        if ($conn) {
            mysqli_set_charset($conn, 'utf8mb4');
        }
        $cache[$module] = $conn;
        return $conn;
    }

    if ($module === 'poster26') {
        $host = getenv('DB_HOST') ?: 'localhost';
        $is_docker = getenv('DB_HOST') ? true : false;
        $user = 'regsys_poster';
        $pass = 'regsys@2025';
        $is_local = false;
        if (!$is_docker) {
            if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
                $is_local = true;
            }
            if (php_sapi_name() === 'cli') {
                $is_local = true;
            }
        }
        if ($is_local) {
            $user = 'root';
            $pass = '';
        }
        $db = 'regsys_poster26';
        $conn = @mysqli_connect($host, $user, $pass, $db);
        if (!$conn) {
            if ($is_local) {
                $user = 'regsys_poster';
                $pass = 'regsys@2025';
            } else {
                $user = 'root';
                $pass = '';
            }
            $conn = @mysqli_connect($host, $user, $pass, $db);
        }
        if ($conn) {
            mysqli_set_charset($conn, 'utf8mb4');
        }
        $cache[$module] = $conn;
        return $conn;
    }

    if ($module === 'workshop') {
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
        $cache[$module] = $conn;
        return $conn;
    }

    return null;
}

function fetch_single_int($conn, $sql)
{
    if (!$conn) {
        return null;
    }
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_row($res);
        return isset($row[0]) ? (int)$row[0] : null;
    }
    return null;
}

function escape_html_dash($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$regCon = get_module_connection('reg');
$participantCon = get_module_connection('participant');
$posterCon = get_module_connection('poster26');
$workshopCon = get_module_connection('workshop');

$totalReg = fetch_single_int($regCon, "SELECT COUNT(*) FROM users");
$totalParticipants = fetch_single_int($participantCon, "SELECT COUNT(*) FROM users");
$totalPosters = fetch_single_int($posterCon, "SELECT COUNT(*) FROM users WHERE source_system='poster' OR source_system='both'");
$totalWorkshop = fetch_single_int($workshopCon, "SELECT COUNT(*) FROM users");

$combinedTotal = 0;
foreach ([$totalReg, $totalParticipants, $totalPosters, $totalWorkshop] as $val) {
    if ($val !== null) {
        $combinedTotal += $val;
    }
}

$totalRegToday = fetch_single_int($regCon, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
$totalParticipantsToday = fetch_single_int($participantCon, "SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
$totalPostersToday = fetch_single_int($posterCon, "SELECT COUNT(*) FROM users WHERE (source_system='poster' OR source_system='both') AND DATE(posting_date) = CURDATE()");

$categoryStats = [
    'reg' => [],
    'participant' => [],
    'poster26' => [],
    'workshop' => []
];

if ($regCon) {
    $res = mysqli_query($regCon, "SELECT category, COUNT(*) AS total FROM users GROUP BY category ORDER BY total DESC LIMIT 10");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $categoryStats['reg'][] = $row;
        }
    }
}

if ($participantCon) {
    $res = mysqli_query($participantCon, "SELECT category, COUNT(*) AS total FROM users GROUP BY category ORDER BY total DESC LIMIT 10");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $categoryStats['participant'][] = $row;
        }
    }
}

if ($posterCon) {
    $res = mysqli_query($posterCon, "SELECT category, COUNT(*) AS total FROM users WHERE source_system='poster' OR source_system='both' GROUP BY category ORDER BY total DESC LIMIT 10");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $categoryStats['poster26'][] = $row;
        }
    }
}

$workshopLatest = [];
if ($workshopCon) {
    $res = mysqli_query($workshopCon, "SELECT id, fname, lname, email, category, organization FROM users ORDER BY id DESC LIMIT 20");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $workshopLatest[] = $row;
        }
    }
}

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterModule = isset($_GET['module']) ? $_GET['module'] : '';
$filterDateFrom = isset($_GET['from']) ? $_GET['from'] : '';
$filterDateTo = isset($_GET['to']) ? $_GET['to'] : '';
$searchResults = [];

if ($searchQuery !== '' || $filterModule !== '' || $filterDateFrom !== '' || $filterDateTo !== '') {
    $like = '%' . $searchQuery . '%';

    if ($regCon && ($filterModule === '' || $filterModule === 'reg')) {
        $sql = "SELECT id, fname, lname, email, category, created_at FROM users WHERE 1=1";
        $types = '';
        $params = [];
        if ($searchQuery !== '') {
            $sql .= " AND (CAST(id AS CHAR) LIKE ? OR fname LIKE ? OR lname LIKE ? OR email LIKE ?)";
            $types .= 'ssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($filterDateFrom !== '') {
            $sql .= " AND DATE(created_at) >= ?";
            $types .= 's';
            $params[] = $filterDateFrom;
        }
        if ($filterDateTo !== '') {
            $sql .= " AND DATE(created_at) <= ?";
            $types .= 's';
            $params[] = $filterDateTo;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 50";
        $stmt = mysqli_prepare($regCon, $sql);
        if ($stmt) {
            if ($types !== '') {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $row['module'] = 'Registration';
                $row['date'] = $row['created_at'];
                $row['detail_link'] = 'manage-users.php?search=' . urlencode($row['id']);
                $searchResults[] = $row;
            }
        }
    }

    if ($participantCon && ($filterModule === '' || $filterModule === 'participant')) {
        $sql = "SELECT id, fname, lname, email, category, posting_date FROM users WHERE 1=1";
        $types = '';
        $params = [];
        if ($searchQuery !== '') {
            $sql .= " AND (CAST(id AS CHAR) LIKE ? OR fname LIKE ? OR lname LIKE ? OR email LIKE ?)";
            $types .= 'ssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($filterDateFrom !== '') {
            $sql .= " AND DATE(posting_date) >= ?";
            $types .= 's';
            $params[] = $filterDateFrom;
        }
        if ($filterDateTo !== '') {
            $sql .= " AND DATE(posting_date) <= ?";
            $types .= 's';
            $params[] = $filterDateTo;
        }
        $sql .= " ORDER BY posting_date DESC LIMIT 50";
        $stmt = mysqli_prepare($participantCon, $sql);
        if ($stmt) {
            if ($types !== '') {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $row['module'] = 'Participant';
                $row['date'] = $row['posting_date'];
                $row['detail_link'] = '../participant/admin/manage-users.php?search=' . urlencode($row['id']);
                $searchResults[] = $row;
            }
        }
    }

    if ($posterCon && ($filterModule === '' || $filterModule === 'poster26')) {
        $sql = "SELECT id, fname, email, category, posting_date FROM users WHERE (source_system='poster' OR source_system='both')";
        $types = '';
        $params = [];
        if ($searchQuery !== '') {
            $sql .= " AND (CAST(id AS CHAR) LIKE ? OR fname LIKE ? OR email LIKE ?)";
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($filterDateFrom !== '') {
            $sql .= " AND DATE(posting_date) >= ?";
            $types .= 's';
            $params[] = $filterDateFrom;
        }
        if ($filterDateTo !== '') {
            $sql .= " AND DATE(posting_date) <= ?";
            $types .= 's';
            $params[] = $filterDateTo;
        }
        $sql .= " ORDER BY posting_date DESC LIMIT 50";
        $stmt = mysqli_prepare($posterCon, $sql);
        if ($stmt) {
            if ($types !== '') {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $row['module'] = 'Poster 26';
                $row['lname'] = '';
                $row['date'] = $row['posting_date'];
                $row['detail_link'] = '../poster26/admin/manage-users.php?search=' . urlencode($row['id']);
                $searchResults[] = $row;
            }
        }
    }

    if ($workshopCon && ($filterModule === '' || $filterModule === 'workshop')) {
        $sql = "SELECT id, fname, lname, email, category FROM users WHERE 1=1";
        $types = '';
        $params = [];
        if ($searchQuery !== '') {
            $sql .= " AND (CAST(id AS CHAR) LIKE ? OR fname LIKE ? OR lname LIKE ? OR email LIKE ?)";
            $types .= 'ssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= " ORDER BY id DESC LIMIT 50";
        $stmt = mysqli_prepare($workshopCon, $sql);
        if ($stmt) {
            if ($types !== '') {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $row['module'] = 'Workshop';
                $row['date'] = '';
                $row['detail_link'] = '';
                $searchResults[] = $row;
            }
        }
    }

    if (isset($_GET['export']) && $_GET['export'] === 'csv' && count($searchResults) > 0) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="dashboard_search_' . date('Y-m-d_H-i-s') . '.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['Module', 'Ref Number', 'First Name', 'Last Name', 'Email', 'Category', 'Date']);
        foreach ($searchResults as $row) {
            fputcsv($fp, [
                $row['module'],
                $row['id'],
                $row['fname'],
                isset($row['lname']) ? $row['lname'] : '',
                $row['email'],
                isset($row['category']) ? $row['category'] : '',
                $row['date']
            ]);
        }
        fclose($fp);
        exit;
    }
}

$currentPage = 'main-dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Admin Dashboard</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
</head>
<body>
<section id="container">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Main Admin Dashboard</h3>

            <div class="row mt">
                <div class="col-lg-3 col-md-3 col-sm-6 mb">
                    <div class="white-panel pn">
                        <div class="white-header">
                            <h5>Total Registrations</h5>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <p style="font-size:28px; font-weight:bold; margin-top:15px;">
                                    <?php echo $totalReg !== null ? (int)$totalReg : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-3 col-sm-6 mb">
                    <div class="white-panel pn">
                        <div class="white-header">
                            <h5>Total Participants</h5>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <p style="font-size:28px; font-weight:bold; margin-top:15px;">
                                    <?php echo $totalParticipants !== null ? (int)$totalParticipants : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-3 col-sm-6 mb">
                    <div class="white-panel pn">
                        <div class="white-header">
                            <h5>Total Poster Submissions</h5>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <p style="font-size:28px; font-weight:bold; margin-top:15px;">
                                    <?php echo $totalPosters !== null ? (int)$totalPosters : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-3 col-sm-6 mb">
                    <div class="white-panel pn">
                        <div class="white-header">
                            <h5>Combined Total</h5>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <p style="font-size:28px; font-weight:bold; margin-top:15px;">
                                    <?php echo (int)$combinedTotal; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt">
                <div class="col-lg-4 col-md-4 col-sm-6 mb">
                    <div class="white-panel pn">
                        <div class="white-header">
                            <h5>Today Registrations</h5>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <p style="font-size:24px; font-weight:bold; margin-top:15px;">
                                    <?php echo $totalRegToday !== null ? (int)$totalRegToday : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 mb">
                    <div class="white-panel pn">
                        <div class="white-header">
                            <h5>Today Participants</h5>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <p style="font-size:24px; font-weight:bold; margin-top:15px;">
                                    <?php echo $totalParticipantsToday !== null ? (int)$totalParticipantsToday : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 mb">
                    <div class="white-panel pn">
                        <div class="white-header">
                            <h5>Today Poster Submissions</h5>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <p style="font-size:24px; font-weight:bold; margin-top:15px;">
                                    <?php echo $totalPostersToday !== null ? (int)$totalPostersToday : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt">
                <div class="col-lg-12">
                    <div class="form-panel">
                        <h4 class="mb"><i class="fa fa-search"></i> Global Search</h4>
                        <form class="form-inline" method="get" action="main-dashboard.php">
                            <div class="form-group">
                                <input type="text" class="form-control" name="search" style="min-width:200px;"
                                       value="<?php echo escape_html_dash($searchQuery); ?>"
                                       placeholder="Search by Ref Number, Name, Email">
                            </div>
                            <div class="form-group" style="margin-left:10px;">
                                <select name="module" class="form-control">
                                    <option value="">All Modules</option>
                                    <option value="reg" <?php echo $filterModule === 'reg' ? 'selected' : ''; ?>>Registration</option>
                                    <option value="participant" <?php echo $filterModule === 'participant' ? 'selected' : ''; ?>>Participant</option>
                                    <option value="poster26" <?php echo $filterModule === 'poster26' ? 'selected' : ''; ?>>Poster 26</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-left:10px;">
                                <input type="date" class="form-control" name="from" value="<?php echo escape_html_dash($filterDateFrom); ?>">
                            </div>
                            <div class="form-group" style="margin-left:10px;">
                                <input type="date" class="form-control" name="to" value="<?php echo escape_html_dash($filterDateTo); ?>">
                            </div>
                            <button type="submit" class="btn btn-theme" style="margin-left:10px;">Search</button>
                            <?php if ($searchQuery !== '' && count($searchResults) > 0): ?>
                                <a href="main-dashboard.php?search=<?php echo urlencode($searchQuery); ?>&export=csv"
                                   class="btn btn-success" style="margin-left:10px;">
                                    <i class="fa fa-download"></i> Export CSV
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row mt">
                <div class="col-lg-12">
                    <div class="content-panel">
                        <h4><i class="fa fa-database"></i> Workshop Database</h4>
                        <p>
                            Transferring Classical HPLC workshop registrations are stored in MySQL database
                            <strong>regsys_workshop</strong>, table <strong>users</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row mt">
                <div class="col-lg-4">
                    <div class="content-panel">
                        <h4><i class="fa fa-angle-right"></i> Reg Categories</h4>
                        <table class="table table-condensed">
                            <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($categoryStats['reg']) === 0): ?>
                                <tr><td colspan="2">No data</td></tr>
                            <?php else: ?>
                                <?php foreach ($categoryStats['reg'] as $row): ?>
                                    <tr>
                                        <td><?php echo escape_html_dash($row['category']); ?></td>
                                        <td><?php echo (int)$row['total']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="content-panel">
                        <h4><i class="fa fa-angle-right"></i> Participant Categories</h4>
                        <table class="table table-condensed">
                            <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($categoryStats['participant']) === 0): ?>
                                <tr><td colspan="2">No data</td></tr>
                            <?php else: ?>
                                <?php foreach ($categoryStats['participant'] as $row): ?>
                                    <tr>
                                        <td><?php echo escape_html_dash($row['category']); ?></td>
                                        <td><?php echo (int)$row['total']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="content-panel">
                        <h4><i class="fa fa-angle-right"></i> Poster Categories</h4>
                        <table class="table table-condensed">
                            <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($categoryStats['poster26']) === 0): ?>
                                <tr><td colspan="2">No data</td></tr>
                            <?php else: ?>
                                <?php foreach ($categoryStats['poster26'] as $row): ?>
                                    <tr>
                                        <td><?php echo escape_html_dash($row['category']); ?></td>
                                        <td><?php echo (int)$row['total']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($searchQuery !== '' || $filterModule !== '' || $filterDateFrom !== '' || $filterDateTo !== ''): ?>
                <div class="row mt">
                    <div class="col-lg-12">
                        <div class="content-panel">
                            <h4><i class="fa fa-angle-right"></i> Search Results</h4>
                            <section id="unseen">
                                <table class="table table-bordered table-striped table-condensed">
                                    <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Ref Number</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (count($searchResults) === 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No records found for this search.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($searchResults as $row): ?>
                                            <tr>
                                                <td><?php echo escape_html_dash($row['module']); ?></td>
                                                <td><?php echo escape_html_dash($row['id']); ?></td>
                                                <td><?php echo escape_html_dash(trim($row['fname'] . ' ' . (isset($row['lname']) ? $row['lname'] : ''))); ?></td>
                                                <td><?php echo escape_html_dash($row['email']); ?></td>
                                                <td><?php echo escape_html_dash(isset($row['category']) ? $row['category'] : ''); ?></td>
                                                <td><?php echo escape_html_dash($row['date']); ?></td>
                                                <td>
                                                    <?php if (!empty($row['detail_link'])): ?>
                                                        <a href="<?php echo escape_html_dash($row['detail_link']); ?>" class="btn btn-primary btn-xs" target="_blank">
                                                            <i class="fa fa-external-link"></i> View
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </section>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </section>
</section>

<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="assets/js/jquery.scrollTo.min.js"></script>
<script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
<script src="assets/js/common-scripts.js"></script>
</body>
</html>

<?php
require_once 'session_setup.php';

include 'dbconnection.php';
require_once 'permission_helper.php';

if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape_html($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function highlight_text($text, $query) {
    if ($query === '' || $query === null) {
        return escape_html($text);
    }
    $parts = preg_split('/(' . preg_quote($query, '/') . ')/u', (string)$text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) {
        return escape_html($text);
    }
    $out = '';
    $i = 0;
    foreach ($parts as $part) {
        if ($i % 2 === 1) {
            $out .= '<mark class="search-highlight">' . escape_html($part) . '</mark>';
        } else {
            $out .= escape_html($part);
        }
        $i++;
    }
    return $out;
}

if (isset($_GET['ajax'])) {
    if (empty($_SESSION['id'])) {
        header('HTTP/1.1 401 Unauthorized');
        exit('Unauthorized');
    }
    $q = isset($_GET['search']) ? $_GET['search'] : '';
    $cat = isset($_GET['category']) ? $_GET['category'] : '';
    $limitArg = isset($_GET['limit']) ? $_GET['limit'] : 100;
    $limit = ($limitArg === 'ALL') ? 1000000 : intval($limitArg);
    $pattern = '%' . $q . '%';
    
    // Base WHERE clause with search fields wrapped in parentheses
    $whereSQL = "(
        CAST(id AS CHAR) COLLATE utf8mb4_bin LIKE ? OR
        fname COLLATE utf8mb4_bin LIKE ? OR
        nationality COLLATE utf8mb4_bin LIKE ? OR
        coauth1name COLLATE utf8mb4_bin LIKE ? OR
        coauth1nationality COLLATE utf8mb4_bin LIKE ? OR
        coauth2name COLLATE utf8mb4_bin LIKE ? OR
        coauth2nationality COLLATE utf8mb4_bin LIKE ? OR
        coauth3name COLLATE utf8mb4_bin LIKE ? OR
        coauth3nationality COLLATE utf8mb4_bin LIKE ? OR
        coauth4name COLLATE utf8mb4_bin LIKE ? OR
        coauth4nationality COLLATE utf8mb4_bin LIKE ? OR
        coauth5name COLLATE utf8mb4_bin LIKE ? OR
        coauth5nationality COLLATE utf8mb4_bin LIKE ? OR
        coauth1email COLLATE utf8mb4_bin LIKE ? OR
        coauth2email COLLATE utf8mb4_bin LIKE ? OR
        coauth3email COLLATE utf8mb4_bin LIKE ? OR
        coauth4email COLLATE utf8mb4_bin LIKE ? OR
        coauth5email COLLATE utf8mb4_bin LIKE ? OR
        email COLLATE utf8mb4_bin LIKE ? OR
        profession COLLATE utf8mb4_bin LIKE ? OR
        organization COLLATE utf8mb4_bin LIKE ? OR
        category COLLATE utf8mb4_bin LIKE ? OR
        postertitle COLLATE utf8mb4_bin LIKE ? OR
        password COLLATE utf8mb4_bin LIKE ? OR
        contactno COLLATE utf8mb4_bin LIKE ? OR
        userip COLLATE utf8mb4_bin LIKE ? OR
        companyref COLLATE utf8mb4_bin LIKE ? OR
        paypalref COLLATE utf8mb4_bin LIKE ? OR
        supervisor_name COLLATE utf8mb4_bin LIKE ? OR
        supervisor_nationality COLLATE utf8mb4_bin LIKE ? OR
        supervisor_contact COLLATE utf8mb4_bin LIKE ? OR
        supervisor_email COLLATE utf8mb4_bin LIKE ?
    ) AND (source_system='scientific' OR source_system='both')";

    // Prepare parameters for search fields (32 fields)
    $params = array_fill(0, 32, $pattern);
    $types = str_repeat('s', 32);

    // Append Category Filter if selected
    if (!empty($cat)) {
        $whereSQL .= " AND category = ?";
        $params[] = $cat;
        $types .= 's';
    }

    $sql = "SELECT * FROM users WHERE " . $whereSQL . " ORDER BY id ASC LIMIT $limit";
    
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        // Use call_user_func_array for compatibility with older PHP versions and bind_param references
        $bind_params = array($types);
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = false;
    }
    $cnt = 1;
    if ($result && mysqli_num_rows($result) > 0) {
        $can_edit = has_permission($con, 'edit_users');
        $can_delete = has_permission($con, 'delete_users');
        $wrap = function($val, $q, $short=false) {
            $txt = (string)$val;
            $hl = highlight_text($txt, $q);
            $cls = $short ? 'cell-content short' : 'cell-content';
            return '<div class="' . $cls . '" title="' . escape_html($txt) . '">' . $hl . '</div>';
        };
        while ($row = mysqli_fetch_assoc($result)) {
            $certStatus = isset($row['certificate_sent']) ? $row['certificate_sent'] : 0;
            echo "<tr>";
            echo "<td><input type='checkbox' class='user-checkbox' value='" . $row['id'] . "' onchange='updateBulkButton()'></td>";
            echo "<td>" . $wrap($cnt, '', true) . "</td>";
            echo "<td>" . $wrap($row['id'], $q) . "</td>";
            echo "<td>" . $wrap($row['fname'], $q) . "</td>";
            echo "<td>" . $wrap(isset($row['lname']) ? $row['lname'] : '', $q) . "</td>";
            echo "<td>" . $wrap(isset($row['nationality']) ? $row['nationality'] : '', $q) . "</td>";
            echo "<td>" . $wrap($row['coauth1name'], $q) . "</td>";
            echo "<td>" . $wrap(isset($row['coauth1nationality']) ? $row['coauth1nationality'] : '', $q) . "</td>";
            echo "<td>" . $wrap($row['coauth2name'], $q) . "</td>";
            echo "<td>" . $wrap(isset($row['coauth2nationality']) ? $row['coauth2nationality'] : '', $q) . "</td>";
            echo "<td>" . $wrap($row['coauth3name'], $q) . "</td>";
            echo "<td>" . $wrap(isset($row['coauth3nationality']) ? $row['coauth3nationality'] : '', $q) . "</td>";
            echo "<td>" . $wrap($row['coauth4name'], $q) . "</td>";
            echo "<td>" . $wrap(isset($row['coauth4nationality']) ? $row['coauth4nationality'] : '', $q) . "</td>";
            echo "<td>" . $wrap(isset($row['coauth5name']) ? $row['coauth5name'] : '', $q) . "</td>";
            echo "<td>" . $wrap(isset($row['coauth5nationality']) ? $row['coauth5nationality'] : '', $q) . "</td>";
            echo "<td>" . $wrap($row['coauth1email'], $q) . "</td>";
            echo "<td>" . $wrap($row['coauth2email'], $q) . "</td>";
            echo "<td>" . $wrap($row['coauth3email'], $q) . "</td>";
            echo "<td>" . $wrap($row['coauth4email'], $q) . "</td>";
            echo "<td>" . $wrap(isset($row['coauth5email']) ? $row['coauth5email'] : '', $q) . "</td>";
            echo "<td>" . $wrap(isset($row['supervisor_name']) ? $row['supervisor_name'] : '', $q) . "</td>";
            echo "<td>" . $wrap(isset($row['supervisor_nationality']) ? $row['supervisor_nationality'] : '', $q) . "</td>";
            echo "<td>" . $wrap(isset($row['supervisor_contact']) ? $row['supervisor_contact'] : '', $q) . "</td>";
            echo "<td>" . $wrap(isset($row['supervisor_email']) ? $row['supervisor_email'] : '', $q) . "</td>";
            echo "<td>" . $wrap($row['email'], $q) . "</td>";
            echo "<td>" . $wrap($row['profession'], $q) . "</td>";
            echo "<td>" . $wrap($row['organization'], $q) . "</td>";
            echo "<td>" . $wrap($row['category'], $q) . "</td>";
            echo "<td>" . $wrap($row['postertitle'], $q) . "</td>";
            echo "<td>" . $wrap($row['contactno'], $q) . "</td>";
            echo "<td>" . $wrap($row['password'], $q) . "</td>";
            echo "<td>" . $wrap($row['companyref'], $q) . "</td>";
            echo "<td>" . $wrap($row['posting_date'], $q) . "</td>";
            
            echo "<td>";
            echo "<select class='form-control input-sm' onchange='updateCertificateStatus(" . $row['id'] . ", this.value)' style='width: 100px; " . ($certStatus == 1 ? "border-color: #5cb85c; border-width: 2px;" : "") . "'>";
            echo "<option value='0' " . ($certStatus == 0 ? "selected" : "") . ">Pending</option>";
            echo "<option value='1' " . ($certStatus == 1 ? "selected" : "") . ">Sent</option>";
            echo "</select>";
            echo "</td>";

            echo "<td class='actions-cell'>";
            if (!empty($row['abstract_filename'])) {
                echo '<a href="download-abstract.php?id=' . $row['id'] . '" target="_blank" class="abstract-preview-link" title="Preview Abstract: ' . escape_html($row['abstract_filename']) . '"><button class="btn btn-success btn-xs"><i class="fa fa-file-text-o"></i></button></a> ';
            }
            echo '<a href="welcome.php?uid=' . escape_html($row['id']) . '"><button class="btn btn-primary btn-xs" aria-label="Print profile"><i class="fa fa-print"></i></button></a> ';
            if ($can_edit) {
                echo '<a href="certificate-editor.php?uid=' . escape_html($row['id']) . '"><button class="btn btn-warning btn-xs" aria-label="Certificate"><i class="fa fa-certificate"></i></button></a> ';
                echo '<a href="update-profile.php?uid=' . escape_html($row['id']) . '"><button class="btn btn-primary btn-xs" aria-label="Edit profile"><i class="fa fa-pencil"></i></button></a> ';
            }
            if ($can_delete) {
                echo '<a href="manage-users.php?id=' . escape_html($row['id']) . '"><button class="btn btn-danger btn-xs" onClick="return confirm(\'Do you really want to delete\');" aria-label="Delete user"><i class="fa fa-trash-o "></i></button></a>';
            }
            echo "</td>";
            echo "</tr>";
            $cnt++;
        }
    } else {
        echo '<tr><td colspan="21" class="text-center">No results found</td></tr>';
    }
    exit;
}

// checking session is valid for not 

if (strlen($_SESSION['id']==0)) {

  header('location:logout.php');

  } else{

if (isset($_GET['export_excel'])) {
    if (!has_permission($con, 'export_data')) {
        die("Permission denied");
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users_list.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'First Name', 'Last Name', 'Nationality', 'Email', 'Profession', 'Organization', 'Category', 'Contact No', 'Supervisor Name', 'Supervisor Email', 'Posting Date'));
    $query = "SELECT * from users WHERE (source_system='scientific' OR source_system='both') ORDER BY id DESC";
    $result = mysqli_query($con, $query);
    while($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, array(
            $row['id'], 
            $row['fname'], 
            isset($row['lname']) ? $row['lname'] : '', 
            $row['nationality'], 
            $row['email'], 
            $row['profession'], 
            $row['organization'], 
            $row['category'], 
            $row['contactno'], 
            isset($row['supervisor_name']) ? $row['supervisor_name'] : '',
            isset($row['supervisor_email']) ? $row['supervisor_email'] : '',
            $row['posting_date']
        ));
    }
    fclose($output);
    exit();
}

if (isset($_GET['export_db'])) {
    if (!has_permission($con, 'export_data')) {
        die("Permission denied");
    }
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'download';
    $dbHost = defined('DB_SERVER') ? DB_SERVER : 'localhost';
    $dbUser = defined('DB_USER') ? DB_USER : '';
    $dbPass = defined('DB_PASS') ? DB_PASS : '';
    $dbName = defined('DB_NAME') ? DB_NAME : '';
    $dumpBase = 'regsys_poster26_' . date('Ymd_His');
    $outDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'exports';
    if (!is_dir($outDir)) {
        @mkdir($outDir, 0777, true);
    }
    $outFile = $outDir . DIRECTORY_SEPARATOR . $dumpBase . '.sql';
    $mysqldump = getenv('MYSQLDUMP_PATH');
    if (!$mysqldump || !is_file($mysqldump)) {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB 10.6\\bin\\mysqldump.exe',
        ];
        foreach ($candidates as $cand) {
            if (is_file($cand)) { $mysqldump = $cand; break; }
        }
    }
    $didDump = false;
    if ($mysqldump && is_file($mysqldump)) {
        $cmd = '"' . $mysqldump . '"'
            . ' --host=' . escapeshellarg($dbHost)
            . ' --user=' . escapeshellarg($dbUser)
            . ' --password=' . escapeshellarg($dbPass)
            . ' --default-character-set=utf8mb4'
            . ' --single-transaction --quick --routines --events'
            . ' ' . escapeshellarg($dbName);
        if ($mode === 'file') {
            $cmd .= ' --result-file=' . escapeshellarg($outFile);
            $ret = 0;
            @exec($cmd, $o, $ret);
            if ($ret === 0 && is_file($outFile) && filesize($outFile) > 0) {
                $didDump = true;
                echo json_encode(['status' => 'ok', 'file' => $outFile]);
                exit;
            }
        } else {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $dumpBase . '.sql"');
            @passthru($cmd);
            exit;
        }
    }
    $filename = $mode === 'file' ? $outFile : null;
    if ($mode !== 'download' && $filename) {
        $fh = @fopen($filename, 'wb');
    } else {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $dumpBase . '.sql"');
        $fh = fopen('php://output', 'wb');
    }
    if (!$fh) {
        echo 'Failed to open output';
        exit;
    }
    fwrite($fh, "SET NAMES utf8mb4;\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
    $tables = [];
    $rt = mysqli_query($con, "SHOW TABLES");
    while ($row = mysqli_fetch_row($rt)) {
        $tables[] = $row[0];
    }
    foreach ($tables as $table) {
        $res = mysqli_query($con, "SHOW CREATE TABLE `".$table."`");
        $create = mysqli_fetch_assoc($res);
        $createSql = isset($create['Create Table']) ? $create['Create Table'] : '';
        fwrite($fh, "DROP TABLE IF EXISTS `".$table."`;\n");
        fwrite($fh, $createSql . ";\n");
        
        if ($table === 'users') {
            $data = mysqli_query($con, "SELECT * FROM users WHERE (source_system='scientific' OR source_system='both')");
        } else {
            $data = mysqli_query($con, "SELECT * FROM `".$table."`");
        }
        
        $cols = [];
        $colRes = mysqli_query($con, "SHOW COLUMNS FROM `".$table."`");
        while ($c = mysqli_fetch_assoc($colRes)) {
            $cols[] = $c['Field'];
        }
        while ($row = mysqli_fetch_assoc($data)) {
            $values = [];
            foreach ($cols as $col) {
                if ($row[$col] === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . mysqli_real_escape_string($con, $row[$col]) . "'";
                }
            }
            fwrite($fh, "INSERT INTO `".$table."` (`".implode("`,`", $cols)."`) VALUES (".implode(",", $values).");\n");
        }
    }
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
    if ($mode === 'file') {
        echo json_encode(['status' => 'ok', 'file' => $outFile]);
    }
    exit;
}

// for deleting user

if(isset($_GET['id']))
{
    if (!has_permission($con, 'delete_users')) {
        echo "<script>alert('Permission denied'); window.location='manage-users.php';</script>";
        exit;
    }
    $adminid = intval($_GET['id']);
    $msg = mysqli_query($con,"delete from users where id='$adminid' AND (source_system='scientific' OR source_system='both')");
    if($msg)
    {
        // Audit Log
        $admin_id = $_SESSION['id'];
        $admin_username = isset($_SESSION['login']) ? $_SESSION['login'] : 'Unknown';
        $action = 'delete_user';
        $details = "Deleted user ID: $adminid";
        $ip = $_SERVER['REMOTE_ADDR'];
        $system_context = 'scientific';
        
        $logStmt = mysqli_prepare($con, "INSERT INTO admin_audit_logs (admin_id, admin_username, action, details, ip_address, system_context) VALUES (?, ?, ?, ?, ?, ?)");
        if ($logStmt) {
            mysqli_stmt_bind_param($logStmt, 'isssss', $admin_id, $admin_username, $action, $details, $ip, $system_context);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);
        }

        echo "<script>alert('Data deleted');</script>";
    }
}

// Fetch Categories for Dropdown
$categoryOptions = [];
$excludedCategories = ['Poster Competition', 'Scientific Competition'];
$removedCategories = [];

$catQuery = "SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$catResult = mysqli_query($con, $catQuery);

if ($catResult) {
    while ($row = mysqli_fetch_assoc($catResult)) {
        $cat = $row['category'];
        if (in_array($cat, $excludedCategories)) {
            // Track removed categories for audit
            if (!in_array($cat, $removedCategories)) {
                $removedCategories[] = $cat;
            }
        } else {
            $categoryOptions[] = $cat;
        }
    }

    // Log removed categories if any were found in DB and excluded
    if (!empty($removedCategories)) {
        $admin_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
        $admin_username = isset($_SESSION['login']) ? $_SESSION['login'] : 'Unknown';
        $action = 'filter_categories';
        $details = 'Automatically excluded categories found in DB: ' . implode(', ', $removedCategories);
        $ip = $_SERVER['REMOTE_ADDR'];
        $system_context = 'scientific';
        
        // Log to admin_audit_logs if table exists
        $logSql = "INSERT INTO admin_audit_logs (admin_id, admin_username, action, details, ip_address, system_context) VALUES (?, ?, ?, ?, ?, ?)";
        $logStmt = mysqli_prepare($con, $logSql);
        if ($logStmt) {
            mysqli_stmt_bind_param($logStmt, 'isssss', $admin_id, $admin_username, $action, $details, $ip, $system_context);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);
        }
    }
} else {
    // Error handling for DB connection/query
    error_log("Database Error in manage-users.php (Category Fetch): " . mysqli_error($con));
    echo "<script>console.error('Failed to load categories: " . addslashes(mysqli_error($con)) . "');</script>";
}
// $categoryOptions is populated with valid DB categories only (excluding specific ones)
?><!DOCTYPE html>

<html lang="en">

  <head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="">

    <meta name="author" content="Dashboard">

    <meta name="keyword" content="Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">



    <title>Admin | Manage Users</title>

    <link href="assets/css/bootstrap.css" rel="stylesheet">

    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />

    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
      .search-highlight { background-color: #ffea00; }
      .search-toolbar { margin-bottom: 15px; }
      
      /* Table Display Enhancements */
      /* Loading Indicator */
      #loading-indicator {
          display: none;
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: rgba(0,0,0,0.8);
          color: white;
          padding: 20px 40px;
          border-radius: 8px;
          z-index: 9999;
          font-weight: bold;
          font-size: 16px;
          box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      }

      /* Ref Number Column - No Truncation */
      #users-table th:nth-child(2), #users-table td:nth-child(2) {
          min-width: 150px;
          width: auto;
      }
      /* Override .cell-content styles for Ref Number only */
      #users-table td:nth-child(2) .cell-content {
          white-space: nowrap !important;
          overflow: visible !important;
          text-overflow: clip !important;
          max-width: none !important;
          display: block !important;
          height: auto !important;
          max-height: none !important;
          -webkit-line-clamp: unset !important;
      }

      /* Hide specific columns by default (Co-authors and Emails) */
      #users-table th:nth-child(6), #users-table td:nth-child(6),
      #users-table th:nth-child(7), #users-table td:nth-child(7),
      #users-table th:nth-child(8), #users-table td:nth-child(8),
      #users-table th:nth-child(9), #users-table td:nth-child(9),
      #users-table th:nth-child(10), #users-table td:nth-child(10),
      #users-table th:nth-child(11), #users-table td:nth-child(11),
      #users-table th:nth-child(12), #users-table td:nth-child(12),
      #users-table th:nth-child(13), #users-table td:nth-child(13),
      #users-table th:nth-child(14), #users-table td:nth-child(14),
      #users-table th:nth-child(15), #users-table td:nth-child(15),
      #users-table th:nth-child(16), #users-table td:nth-child(16),
      #users-table th:nth-child(17), #users-table td:nth-child(17),
      #users-table th:nth-child(18), #users-table td:nth-child(18),
      #users-table th:nth-child(19), #users-table td:nth-child(19),
      #users-table th:nth-child(20), #users-table td:nth-child(20),
      #users-table th:nth-child(24), #users-table td:nth-child(24),
      #users-table th:nth-child(25), #users-table td:nth-child(25) {
          display: none;
      }
      
      .table-responsive-wrapper {
          width: 100%;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
          margin-bottom: 20px;
          border: 1px solid #ddd;
      }
      
      #users-table {
          width: 100%;
          margin-bottom: 0;
      }

      #users-table th, #users-table td {
          vertical-align: top !important;
          padding: 8px;
      }

      .cell-content {
          min-width: 120px;
          max-width: 300px;
          white-space: normal;
          word-wrap: break-word;
          word-break: break-word;
          display: -webkit-box;
          -webkit-line-clamp: 3;
          -webkit-box-orient: vertical;
          overflow: hidden;
          text-overflow: ellipsis;
          line-height: 1.4;
          max-height: 4.2em;
          cursor: help; /* Indicates tooltip available */
      }

      /* Adjustments for specific content types */
      .cell-content.short {
          min-width: 40px;
          max-width: 80px;
          -webkit-line-clamp: 1;
          max-height: 1.4em;
      }
      
      /* Ensure actions column is not truncated */
      .actions-cell {
          min-width: 140px;
          white-space: nowrap;
      }
      
      /* Column Visibility Toggles */
      .column-visibility-controls {
          padding: 15px;
          border-bottom: 1px solid #eee;
          margin-bottom: 15px;
          background-color: #f9f9f9;
      }
      .column-visibility-controls label {
          margin-right: 15px;
          cursor: pointer;
      }
      
      /* Table Display Enhancements */
    </style>
  </head>



  <body>



  <section id="container" >
      <div id="loading-indicator"><i class="fa fa-spinner fa-spin"></i> Refreshing Table...</div>
      <header class="header black-bg">

              <div class="sidebar-toggle-box">

                  <div class="fa fa-bars tooltips" data-placement="right" data-original-title="Toggle Navigation"></div>

              </div>

            <a href="#" class="logo"><b>Admin Dashboard</b></a>

            <div class="nav notify-row" id="top_menu">

               

                         

                   

                </ul>

            </div>

            <div class="top-menu">

            	<ul class="nav pull-right top-menu">

                    <li><a class="logout" href="logout.php">Logout</a></li>

            	</ul>

            </div>

        </header>

      <aside>

          <div id="sidebar"  class="nav-collapse ">

              <ul class="sidebar-menu" id="nav-accordion">

              

              	  <p class="centered"><a href="#"><img src="assets/img/ui-sam.jpg" class="img-circle" width="60"></a></p>

              	  <h5 class="centered"><?php echo $_SESSION['login'];?></h5>

              	  	

                  <li class="mt">

                      <a href="change-password.php">

                          <i class="fa fa-file"></i>

                          <span>Change Password</span>

                      </a>

                  </li>



                  <li class="sub-menu">

                      <a href="manage-users.php" >

                          <i class="fa fa-users"></i>

                          <span>Manage Users</span>

                      </a>

                  </li>

                  <?php if(has_permission($con, 'manage_admins')): ?>
                  <li class="sub-menu">
                      <a href="manage-admins.php">
                          <i class="fa fa-user-md"></i>
                          <span>Manage Admins</span>
                      </a>
                  </li>
                  <?php endif; ?>
                  <?php if(has_permission($con, 'manage_roles')): ?>
                  <li class="sub-menu">
                      <a href="manage-roles.php">
                          <i class="fa fa-lock"></i>
                          <span>Manage Roles</span>
                      </a>
                  </li>
                  <?php endif; ?>

              

                 

              </ul>

          </div>

      </aside>

      <section id="main-content">

          <section class="wrapper">

          	<h3><i class="fa fa-angle-right"></i> Manage Users</h3>
				<div class="row">
				
                  
	                  
                  <div class="col-md-12">
                    <div class="content-panel search-toolbar" role="search" aria-label="User table instant search">
                      <div class="row">
                          <div class="col-md-8">
                              <div class="input-group">
                                <span class="input-group-addon" id="users-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span>
                                <input type="text" id="users-search" class="form-control" placeholder="Search users..." aria-label="Search users" aria-controls="users-table" aria-describedby="users-search-icon">
                                <span class="input-group-btn">
                                  <button class="btn btn-default" type="button" id="clear-search" aria-label="Clear search">Clear</button>
                                </span>
                              </div>
                          </div>
                          <div class="col-md-4" style="display: flex; gap: 10px;">
                              <select id="category-filter" class="form-control" aria-label="Filter by Category" style="flex: 2;">
                                  <option value="">All Categories</option>
                                  <?php foreach ($categoryOptions as $opt): ?>
                                      <option value="<?php echo escape_html($opt); ?>"><?php echo escape_html($opt); ?></option>
                                  <?php endforeach; ?>
                              </select>
                              <select id="rows-per-page" class="form-control" style="flex: 1; min-width: 80px;" aria-label="Rows per page">
                                  <option value="25">25</option>
                                  <option value="50">50</option>
                                  <option value="100" selected>100</option>
                                  <option value="250">250</option>
                                  <option value="500">500</option>
                                  <option value="1000">1000</option>
                                  <option value="ALL">ALL</option>
                              </select>
                          </div>
                      </div>
                      <div id="search-status" class="sr-only" aria-live="polite"></div>
                      <div style="margin-top:10px;">
                        <a href="manage-users.php?export_db=1&mode=download" class="btn btn-success" aria-label="Export database">Export Database</a>
                        <a href="manage-users.php?export_excel=1" class="btn btn-info" aria-label="Export to Excel">Export to Excel</a>
                              <button class="btn btn-warning" id="btn-bulk-email" onclick="openBulkEmailModal()" disabled style="margin-left: 5px;"><i class="fa fa-envelope"></i> Send Certificates</button>
                              <button id="btn-manage-admins" class="btn btn-primary" style="margin-left: 5px;" aria-label="Manage Admins"><i class="fa fa-cogs"></i> Manage Admins</button>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-12">

                      <div class="content-panel">
                          <h4><i class="fa fa-angle-right"></i> All User Details </h4>
                          <hr>

                          <div class="column-visibility-controls">
                              <div style="margin-bottom: 10px;">
                                  <strong>Show Columns: </strong>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-coauth1"> Co-author 1</label>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-coauth2"> Co-author 2</label>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-coauth3"> Co-author 3</label>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-coauth4"> Co-author 4</label>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-coauth5"> Co-author 5</label>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-emails"> Emails</label>
                              </div>
                              <div>
                                  <button id="refresh-table" class="btn btn-info btn-sm" title="Reloads the table data from the source"><i class="fa fa-refresh"></i> Refresh Table</button>
                                  <button id="reset-settings" class="btn btn-warning btn-sm" title="Clear all saved column visibility settings"><i class="fa fa-eraser"></i> Reset Settings</button>
                                  <small class="text-muted" style="margin-left: 10px;">(Settings are saved automatically)</small>
                              </div>
                          </div>

                          <div class="table-responsive-wrapper">
                          <table id="users-table" class="table table-striped table-advance table-hover">

                              <thead>

                              <tr>
                                  <th><input type="checkbox" id="select-all-users" onchange="toggleSelectAll(this)"></th>
                                  <th>Sno.</th>

                                  <th> Ref Number</th>

                                  <th class="hidden-phone">Main Auth</th>
                                  <th> Last Name</th>
                                  <th> Nationality</th>
                                  <th> Co-auth 1</th>
                                  <th> Co-auth 1 Nationality</th>
                                  <th> Co-auth 2</th>
                                  <th> Co-auth 2 Nationality</th>
                                  <th> Co-auth 3</th>
                                  <th> Co-auth 3 Nationality</th>
                                  <th> Co-auth 4</th>
                                  <th> Co-auth 4 Nationality</th>
                                  <th> Co-auth 5</th>
                                  <th> Co-auth 5 Nationality</th>
                                  <th> Co-auth 1 Email</th>
                                  <th> Co-auth 2 Email</th>
                                  <th> Co-auth 3 Email</th>
                                  <th> Co-auth 4 Email</th>
                                  <th> Co-auth 5 Email</th>
                                  <th> Supervisor Name</th>
                                  <th> Supervisor Nationality</th>
                                  <th> Supervisor Contact</th>
                                  <th> Supervisor Email</th>
                                  <th> Email Id</th>
                                  <th> Profession</th>
                                  <th> Univerisity</th>
                                  <th> Category</th>
                                  <th> Topic</th>
                                  <th>Contact no.</th>

                                  <th>Password</th>

                                  <th>Company ref</th>
                                  <th>Reg. Date</th>
                                  <th>Cert. Status</th>
                                  <th>Action</th>
                              </tr>

                              </thead>

                              <tbody>

                              <?php 
                              $sql = "select * from users";
                              $sql .= " LIMIT $limit";
                              $ret=mysqli_query($con, $sql);
                              $cnt=1;
                              $wrapInit = function($val, $short=false) {
                                  $txt = (string)$val;
                                  $cls = $short ? 'cell-content short' : 'cell-content';
                                  return '<div class="' . $cls . '" title="' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</div>';
                              };
                              while($row=mysqli_fetch_array($ret))
                              {
                                  echo "<tr>";
                                  echo "<td><input type='checkbox' class='user-checkbox' value='" . $row['id'] . "' onchange='updateBulkButton()'></td>";
                                  echo "<td>" . $wrapInit($cnt, true) . "</td>";
                                  echo "<td>" . $wrapInit($row['id'], true) . "</td>";
                                  echo "<td>" . $wrapInit($row['fname']) . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['lname']) ? $row['lname'] : '') . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['nationality']) ? $row['nationality'] : '') . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth1name']) . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['coauth1nationality']) ? $row['coauth1nationality'] : '') . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth2name']) . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['coauth2nationality']) ? $row['coauth2nationality'] : '') . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth3name']) . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['coauth3nationality']) ? $row['coauth3nationality'] : '') . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth4name']) . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['coauth4nationality']) ? $row['coauth4nationality'] : '') . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['coauth5name']) ? $row['coauth5name'] : '') . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['coauth5nationality']) ? $row['coauth5nationality'] : '') . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth1email']) . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth2email']) . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth3email']) . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth4email']) . "</td>";
                                  echo "<td>" . $wrapInit($row['coauth5email']) . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['supervisor_name']) ? $row['supervisor_name'] : '') . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['supervisor_nationality']) ? $row['supervisor_nationality'] : '') . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['supervisor_contact']) ? $row['supervisor_contact'] : '') . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['supervisor_email']) ? $row['supervisor_email'] : '') . "</td>";
                                  echo "<td>" . $wrapInit($row['email']) . "</td>";
                                  echo "<td>" . $wrapInit($row['profession']) . "</td>";
                                  echo "<td>" . $wrapInit($row['organization']) . "</td>";
                                  echo "<td>" . $wrapInit($row['category']) . "</td>";
                                  echo "<td>" . $wrapInit($row['postertitle']) . "</td>";
                                  echo "<td>" . $wrapInit($row['contactno']) . "</td>";
                                  echo "<td>" . $wrapInit($row['password']) . "</td>";
                                  echo "<td>" . $wrapInit($row['companyref']) . "</td>";
                                  echo "<td>" . $wrapInit($row['posting_date']) . "</td>";
                                  
                                  // Certificate Status Dropdown
                                  $certStatus = isset($row['certificate_sent']) ? $row['certificate_sent'] : 0;
                                  echo "<td>";
                                  echo "<select class='form-control input-sm' onchange='updateCertificateStatus(" . $row['id'] . ", this.value)' style='width: 100px; " . ($certStatus == 1 ? "border-color: #5cb85c; border-width: 2px;" : "") . "'>";
                                  echo "<option value='0' " . ($certStatus == 0 ? "selected" : "") . ">Pending</option>";
                                  echo "<option value='1' " . ($certStatus == 1 ? "selected" : "") . ">Sent</option>";
                                  echo "</select>";
                                  echo "</td>";

                                  echo "<td class='actions-cell'>";
                                  if (!empty($row['abstract_filename'])) {
                                      echo '<a href="download-abstract.php?id=' . $row['id'] . '" target="_blank" class="abstract-preview-link" title="Preview Abstract: ' . htmlspecialchars($row['abstract_filename'], ENT_QUOTES, 'UTF-8') . '"><button class="btn btn-success btn-xs"><i class="fa fa-file-text-o"></i></button></a> ';
                                  }
                                  echo '<a href="welcome.php?uid=' . htmlspecialchars($row['id']) . '"><button class="btn btn-primary btn-xs"><i class="fa fa-print"></i></button></a> ';
                                  echo '<a href="update-profile.php?uid=' . htmlspecialchars($row['id']) . '"><button class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></button></a> ';
                                  echo '<a href="manage-users.php?id=' . htmlspecialchars($row['id']) . '"><button class="btn btn-danger btn-xs" onClick="return confirm(\'Do you really want to delete\');"><i class="fa fa-trash-o "></i></button></a>';
                                  echo "</td>";
                                  echo "</tr>";
                                  $cnt++;
                              }?>

                              </tbody>

                          </table>
                          </div>

                      </div>

                  </div>

              </div>

		</section>

      </section

  ></section>

                      <!-- Bulk Email Modal -->
                      <div class="modal fade" id="bulk-email-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                          <div class="modal-dialog" style="width: 95%; max-width: 1400px;">
                              <div class="modal-content">
                                  <div class="modal-header">
                                      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                      <h4 class="modal-title"><i class="fa fa-envelope"></i> Send Bulk Certificates</h4>
                                  </div>
                                  <div class="modal-body">
                                      <div class="row">
                                          <!-- Left Column: Settings & Progress -->
                                          <div class="col-md-4">
                                              <div class="alert alert-info">
                                                  <span id="bulk-selection-count-modal">0 users selected</span>
                                              </div>

                                              <!-- Settings Form -->
                                              <form id="bulkEmailForm">
                                                  <div class="form-group">
                                                      <label>Email Delay (seconds)</label>
                                                      <input type="number" id="email-delay" class="form-control" value="5" min="1">
                                                      <p class="help-block">Delay between emails to avoid server limits.</p>
                                                  </div>
                                                  
                                                  <div class="form-group" id="template-group">
                                                      <label>Select Template</label>
                                                      <div style="display: flex; gap: 5px;">
                                                          <select id="bulk-template-select" class="form-control">
                                                              <option value="">Loading...</option>
                                                          </select>
                                                          <button type="button" class="btn btn-info" id="btn-load-preview" onclick="loadTemplatePreview()">
                                                              <i class="fa fa-eye"></i> Preview
                                                          </button>
                                                      </div>
                                                  </div>

                                                  <div class="form-group">
                                                      <div class="checkbox">
                                                          <label>
                                                              <input type="checkbox" id="bulk-use-override-email" onchange="toggleBulkOverrideEmail()"> Send all to specific email
                                                          </label>
                                                      </div>
                                                      <input type="email" class="form-control" id="bulk-override-email" placeholder="Enter email address" style="display:none;">
                                                  </div>

                                                  <div id="preview-confirm-container" style="display:none; margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                                                      <label><input type="checkbox" id="confirm-preview"> I have verified the preview and am ready to send.</label>
                                                  </div>
                                              </form>

                                              <!-- Progress Section (Initially Hidden) -->
                                              <div id="bulk-progress-container" style="display:none;">
                                                  <h4 id="bulk-status-text">Initializing...</h4>
                                                  <div class="progress progress-striped active">
                                                      <div id="bulk-progress-bar" class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                                          0%
                                                      </div>
                                                  </div>
                                                  <div id="bulk-summary-stats" style="margin-bottom: 10px; font-weight: bold;"></div>
                                                  <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd;">
                                                      <table class="table table-striped table-condensed" id="bulk-report-table" style="font-size: 12px;">
                                                          <thead>
                                                              <tr>
                                                                  <th>#</th>
                                                                  <th>Time</th>
                                                                  <th>User</th>
                                                                  <th>Email</th>
                                                                  <th>Status</th>
                                                                  <th>Details</th>
                                                              </tr>
                                                          </thead>
                                                          <tbody></tbody>
                                                      </table>
                                                  </div>
                                              </div>
                                          </div>

                                          <!-- Right Column: Live Preview -->
                                          <div class="col-md-8">
                                              <div class="bulk-editor-interface" style="border: 1px solid #ccc; background: #f0f2f5; height: 600px; display: flex; flex-direction: column;">
                                                  <div class="bulk-editor-toolbar" style="height: 40px; background: #2c3e50; color: white; display: flex; align-items: center; padding: 0 15px;">
                                                      <h5 style="margin: 0; color: white;"><i class="fa fa-paint-brush"></i> Certificate Editor View</h5>
                                                      <span id="preview-template-name" style="margin-left: auto; font-size: 12px; color: #bdc3c7;">No template loaded</span>
                                                  </div>
                                                  <div id="bulk-preview-wrapper" style="flex: 1; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center; background-image: radial-gradient(#bdc3c7 1px, transparent 1px); background-size: 20px 20px;">
                                                      <div id="certificate-preview" style="width: 100%; height: 100%;">
                                                          <div style="display: flex; height: 100%; align-items: center; justify-content: center; color: #999;">Preview will appear here...</div>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                                  <div class="modal-footer">
                                      <button type="button" class="btn btn-default" data-dismiss="modal" id="btn-cancel-bulk">Close</button>
                                      <button type="button" class="btn btn-primary" id="btn-start-bulk" onclick="startBulkEmail()" disabled>Send Certificates</button>
                                  </div>
                              </div>
                          </div>
                      </div>

    <script src="assets/js/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>window.jsPDF = window.jspdf.jsPDF;</script>

    <script src="assets/js/bootstrap.min.js"></script>

    <script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>

    <script src="assets/js/jquery.scrollTo.min.js"></script>

    <script src="assets/js/common-scripts.js"></script>
  <script>
            function updateCertificateStatus(userId, status) {
          var select = event.target;
          var originalBorder = select.style.borderColor;
          
          select.disabled = true;
          
          $.ajax({
              url: 'ajax_handler.php',
              type: 'POST',
              data: {
                  action: 'update_certificate_status',
                  user_id: userId,
                  status: status
              },
              success: function(response) {
                  select.disabled = false;
                  try {
                      // Handle both JSON object or string response
                      var res = typeof response === 'object' ? response : JSON.parse(response);
                      
                      if (res.status === 'success') {
                          if (status == 1) {
                              select.style.borderColor = '#5cb85c';
                              select.style.borderWidth = '2px';
                          } else {
                              select.style.borderColor = '';
                              select.style.borderWidth = '1px';
                          }
                      } else {
                          alert('Error: ' + (res.message || 'Unknown error'));
                          // Revert
                          select.value = status == 1 ? 0 : 1;
                      }
                  } catch (e) {
                      console.error("JSON Parse Error:", e, response);
                      alert('Error updating status: Invalid server response');
                  }
              },
              error: function() {
                  select.disabled = false;
                  alert('Network error occurred');
                  // Revert
                  select.value = status == 1 ? 0 : 1;
              }
          });
      }

      $(function(){
          // Safely initialize customSelect if it exists
          if ($.fn.customSelect) {
              $('select.styled').customSelect();
          }

          // Cookie Helper Functions
          function setCookie(name, value, days) {
              var expires = "";
              if (days) {
                  var date = new Date();
                  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                  expires = "; expires=" + date.toUTCString();
              }
              // Secure and SameSite flags for security
              document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
          }

          function getCookie(name) {
              var nameEQ = name + "=";
              var ca = document.cookie.split(';');
              for(var i=0;i < ca.length;i++) {
                  var c = ca[i];
                  while (c.charAt(0)==' ') c = c.substring(1,c.length);
                  if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
              }
              return null;
          }

          function eraseCookie(name) {
              document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
          }

          // Column Visibility Logic - Direct DOM Manipulation
          var columnMap = {
              'hide-coauth1': [6, 7],
              'hide-coauth2': [8, 9],
              'hide-coauth3': [10, 11],
              'hide-coauth4': [12, 13],
              'hide-coauth5': [14, 15],
              'hide-emails': [16, 17, 18, 19, 20, 24, 25]
          };

          window.applyColumnVisibility = function() {
              $('.col-toggle').each(function() {
                  var target = $(this).data('target');
                  var isChecked = $(this).is(':checked');
                  var indices = columnMap[target];
                  
                  if (indices) {
                      $.each(indices, function(i, index) {
                          // Select both th and td at this index (nth-child is 1-based)
                          var $cells = $('#users-table tr > :nth-child(' + index + ')');
                          if (isChecked) {
                              $cells.show();
                          } else {
                              $cells.hide();
                          }
                      });
                  }
              });
          };

          // Initialize State from Cookies
           $('.col-toggle').each(function() {
               var target = $(this).data('target');
               var isChecked = getCookie('col_vis_' + target);
               // Default to false (unchecked/hidden) if not set
               if (isChecked === null) isChecked = 'false';
               $(this).prop('checked', isChecked === 'true');
           });

          // Apply initially
          applyColumnVisibility();

          // Change Handler with Loading Indicator and Cookie Storage
          $('.col-toggle').change(function() {
              var $this = $(this);
              var target = $this.data('target');
              var isChecked = $this.is(':checked');
              
              // Show loading indicator
              $('#loading-indicator').fadeIn(100);
              
              // Use setTimeout to allow UI to render the loader before processing
              setTimeout(function() {
                  // Save to Cookie (7 days expiry)
                  try {
                      setCookie('col_vis_' + target, isChecked, 7);
                  } catch(e) {
                      console.error("Cookie storage failed:", e);
                  }
                  
                  applyColumnVisibility();
                  
                  // Hide loading indicator
                  setTimeout(function() {
                      $('#loading-indicator').fadeOut(200);
                  }, 300); // Minimum visibility time for feedback
              }, 50);
          });
          
          // Reset Settings Button
          $('#reset-settings').click(function(e) {
              e.preventDefault();
              if(confirm('Are you sure you want to reset all column visibility settings?')) {
                  $('.col-toggle').each(function() {
                      var target = $(this).data('target');
                      eraseCookie('col_vis_' + target);
                      $(this).prop('checked', false); // Reset to default hidden
                  });
                  applyColumnVisibility();
                  alert('Settings have been reset.');
              }
          });
          
          // Refresh Button
          $('#refresh-table').click(function(e) {
              e.preventDefault();
              var $btn = $(this);
              $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Refreshing...');
              $('#loading-indicator').fadeIn(100);
              
              // Trigger fetchRows
              if (typeof window.fetchRows === 'function') {
                   // Use current search term or empty
                   var q = $('#users-search').val() || '';
                   window.fetchRows(q, function() {
                       // Callback after success
                       $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Refresh Table');
                       $('#loading-indicator').fadeOut(200);
                   });
              } else {
                  // Fallback to page reload
                  location.reload();
              }
          });
      });

      (function(){
        var $input = $('#users-search');
        var $clear = $('#clear-search');
        var $filter = $('#category-filter');
        var $rowsPerPage = $('#rows-per-page');
        var $tbody = $('#users-table tbody');
        var $status = $('#search-status');
        var timer = null;
        function updateStatus(text){ $status.text(text); }
        
        // Expose fetchRows to window
        var currentRequest = null;
        window.fetchRows = function(q, callback){
          if(currentRequest) {
              currentRequest.abort();
          }
          var cat = $filter.val();
          var limit = $rowsPerPage.val();
          $('#loading-indicator').fadeIn(100);

          currentRequest = $.ajax({
            url: 'manage-users.php',
            method: 'GET',
            data: { ajax: 1, search: q, category: cat, limit: limit },
            success: function(html){
              $('#loading-indicator').fadeOut(200);
              $tbody.html(html);
              // Re-apply visibility after AJAX update
              if (window.applyColumnVisibility) {
                  window.applyColumnVisibility();
              }
              var count = $tbody.find('tr').length;
              if (count === 1 && $tbody.find('td').length === 1) {
                updateStatus('No results found');
              } else {
                updateStatus('Showing ' + count + ' result' + (count===1?'':'s'));
              }
              if (callback) callback();
            },
            error: function(xhr, status, error){
              if(status !== 'abort') {
                $('#loading-indicator').fadeOut(200);
                alert('Failed to load data. Please check your connection.');
                updateStatus('Error fetching results');
              }
              if (callback) callback();
            }
          });
        };
        
        // Check for saved search query
        var savedSearch = (function(name) {
          var v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
          return v ? v[2] : null;
        })('search_query');

        if (savedSearch && savedSearch !== '') {
          $input.val(savedSearch);
          setTimeout(function() { window.fetchRows(savedSearch); }, 100);
        }

        $input.on('input', function(){
          var q = $input.val();
          var d = new Date();
          d.setTime(d.getTime() + (7 * 24 * 60 * 60 * 1000));
          document.cookie = "search_query=" + q + "; path=/; SameSite=Lax; expires=" + d.toUTCString();

          if (timer) { clearTimeout(timer); }
          timer = setTimeout(function(){ window.fetchRows(q); }, 300);
        });
        
        $filter.on('change', function(){
            var q = $input.val();
            window.fetchRows(q);
        });

        $rowsPerPage.on('change', function(){
            var q = $input.val();
            window.fetchRows(q);
        });

        $clear.on('click', function(){
          $input.val('');
          document.cookie = 'search_query=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
          $filter.val(''); // Reset filter too
          window.fetchRows('');
          $input.focus();
        });
        $input.on('keydown', function(e){
          if (e.key === 'Escape') {
            $clear.click();
          }
        });
      })();

  </script>



  <!-- Admin Management Modal -->
  <div class="modal fade" id="adminModal" tabindex="-1" role="dialog" aria-labelledby="adminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title" id="adminModalLabel">Admin Management</h4>
        </div>
        <div class="modal-body">
          <!-- Tabs -->
          <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#tab-admins" aria-controls="tab-admins" role="tab" data-toggle="tab">Admins</a></li>
            <li role="presentation"><a href="#tab-roles" aria-controls="tab-roles" role="tab" data-toggle="tab">Roles & Permissions</a></li>
          </ul>

          <div class="tab-content" style="padding-top: 15px;">
            
            <!-- ADMINS TAB -->
            <div role="tabpanel" class="tab-pane active" id="tab-admins">
              <!-- View: List -->
              <div id="admin-list-view">
                  <div class="text-right mb-10" style="margin-bottom: 10px;">
                      <button class="btn btn-success" id="btn-add-admin"><i class="fa fa-plus"></i> Add New Admin</button>
                  </div>
                  <div class="table-responsive">
                      <table class="table table-striped table-hover" id="admin-table">
                          <thead>
                              <tr>
                                  <th>ID</th>
                                  <th>Username</th>
                                  <th>Email</th>
                                  <th>Roles</th>
                                  <th>Created At</th>
                                  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody></tbody>
                      </table>
                  </div>
              </div>
              
              <!-- View: Form -->
              <div id="admin-form-view" style="display:none;">
                  <form id="admin-form">
                      <input type="hidden" id="admin-id" name="id" value="0">
                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                      
                      <div class="form-group">
                          <label>Username</label>
                          <input type="text" class="form-control" name="username" id="admin-username" required>
                      </div>
                      
                      <div class="form-group">
                          <label>Email</label>
                          <input type="email" class="form-control" name="email" id="admin-email">
                      </div>
                      
                      <div class="form-group">
                          <label>Password <small class="text-muted" id="pass-help">(Leave blank to keep unchanged)</small></label>
                          <input type="password" class="form-control" name="password" id="admin-password">
                          <div id="pass-strength" style="height: 5px; margin-top: 5px; transition: all 0.3s;"></div>
                          <small id="pass-msg"></small>
                      </div>
                      
                      <div class="form-group">
                          <label>Roles</label>
                          <div id="roles-container">
                              <!-- Roles checkboxes generated by JS -->
                          </div>
                      </div>
                      
                      <div class="form-group text-right">
                          <button type="button" class="btn btn-default" id="btn-cancel-admin">Cancel</button>
                          <button type="submit" class="btn btn-primary" id="btn-save-admin">Save Admin</button>
                      </div>
                  </form>
              </div>
            </div>

            <!-- ROLES TAB -->
            <div role="tabpanel" class="tab-pane" id="tab-roles">
              <!-- Role List View -->
              <div id="role-list-view">
                  <div class="text-right mb-10" style="margin-bottom: 10px;">
                      <button class="btn btn-success" id="btn-add-role"><i class="fa fa-plus"></i> Create New Role</button>
                  </div>
                  <div class="table-responsive">
                      <table class="table table-striped table-hover" id="role-table">
                          <thead>
                              <tr>
                                  <th>ID</th>
                                  <th>Role Name</th>
                                  <th>Description</th>
                                  <th>Permissions</th>
                                  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody></tbody>
                      </table>
                  </div>
              </div>

              <!-- Role Form View -->
              <div id="role-form-view" style="display:none;">
                  <form id="role-form">
                      <input type="hidden" id="role-id" name="id" value="0">
                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                      
                      <div class="form-group">
                          <label>Role Name</label>
                          <input type="text" class="form-control" name="name" id="role-name" required>
                      </div>

                      <div class="form-group">
                          <label>Description</label>
                          <input type="text" class="form-control" name="description" id="role-description" required>
                      </div>

                      <div class="form-group">
                          <label>Permissions</label>
                          <div class="well" style="max-height: 300px; overflow-y: auto;">
                              <div id="permissions-container">
                                  <!-- Permissions checkboxes generated by JS -->
                              </div>
                          </div>
                      </div>

                      <div class="form-group text-right">
                          <button type="button" class="btn btn-default" id="btn-cancel-role">Cancel</button>
                          <button type="submit" class="btn btn-primary">Save Role</button>
                      </div>
                  </form>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  $(document).ready(function() {
      var csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
      
      // Open Modal
      $('#btn-manage-admins').click(function(e) {
          e.preventDefault();
          loadAdmins();
          $('#admin-list-view').show();
          $('#admin-form-view').hide();
          $('#adminModal').modal('show');
      });
      
      // Load Admins
      function loadAdmins() {
          $.ajax({
              url: 'admin_action.php',
              data: { action: 'get_admins' },
              dataType: 'json',
              success: function(res) {
                  var tbody = $('#admin-table tbody');
                  tbody.empty();
                  if (res.admins && res.admins.length > 0) {
                      $.each(res.admins, function(i, admin) {
                          var roles = admin.role_names.join(', ');
                          var tr = $('<tr>');
                          tr.append('<td>' + admin.id + '</td>');
                          tr.append('<td>' + $('<div>').text(admin.username).html() + '</td>');
                          tr.append('<td>' + $('<div>').text(admin.email || '').html() + '</td>');
                          tr.append('<td><span class="label label-info">' + roles + '</span></td>');
                          tr.append('<td>' + (admin.created_at || 'N/A') + '</td>');
                          
                          var actions = $('<td>');
                          var editBtn = $('<button class="btn btn-primary btn-xs mr-5"><i class="fa fa-pencil"></i></button>');
                          editBtn.click(function() { editAdmin(admin); });
                          
                          var delBtn = $('<button class="btn btn-danger btn-xs" style="margin-left:5px;"><i class="fa fa-trash-o"></i></button>');
                          delBtn.click(function() { deleteAdmin(admin.id); });
                          
                          actions.append(editBtn).append(delBtn);
                          tr.append(actions);
                          tbody.append(tr);
                      });
                  } else {
                      tbody.append('<tr><td colspan="6" class="text-center">No admins found</td></tr>');
                  }
              }
          });
      }
      
      // Load Roles for Selection (Admin Form)
      function loadRoles(selectedIds) {
          $.ajax({
              url: 'admin_action.php',
              data: { action: 'get_roles' },
              dataType: 'json',
              success: function(res) {
                  var container = $('#roles-container');
                  container.empty();
                  if (res.roles) {
                      $.each(res.roles, function(i, role) {
                          // selectedIds is array of strings
                          var checked = (selectedIds && selectedIds.includes(String(role.id))) ? 'checked' : '';
                          var div = $('<div class="checkbox">');
                          var label = $('<label>');
                          label.append('<input type="checkbox" name="roles[]" value="' + role.id + '" ' + checked + '> ' + role.name);
                          label.append('<small class="text-muted" style="display:block; margin-left: 20px;">' + role.description + '</small>');
                          div.append(label);
                          container.append(div);
                      });
                  }
              }
          });
      }
      
      // Load Role List (Role Tab)
      function loadRoleList() {
          $.ajax({
              url: 'admin_action.php',
              data: { action: 'get_roles' },
              dataType: 'json',
              success: function(res) {
                  var tbody = $('#role-table tbody');
                  tbody.empty();
                  if (res.roles && res.roles.length > 0) {
                      $.each(res.roles, function(i, role) {
                          var perms = role.permissions ? role.permissions.join(', ') : '';
                          var tr = $('<tr>');
                          tr.append('<td>' + role.id + '</td>');
                          tr.append('<td>' + $('<div>').text(role.name).html() + '</td>');
                          tr.append('<td>' + $('<div>').text(role.description).html() + '</td>');
                          tr.append('<td><span class="label label-default" style="white-space:normal; display:inline-block; text-align:left;">' + perms + '</span></td>');
                          
                          var actions = $('<td>');
                          var editBtn = $('<button class="btn btn-primary btn-xs mr-5"><i class="fa fa-pencil"></i></button>');
                          editBtn.click(function() { editRole(role); });
                          
                          var delBtn = $('<button class="btn btn-danger btn-xs" style="margin-left:5px;"><i class="fa fa-trash-o"></i></button>');
                          delBtn.click(function() { deleteRole(role.id); });
                          
                          actions.append(editBtn).append(delBtn);
                          tr.append(actions);
                          tbody.append(tr);
                      });
                  } else {
                      tbody.append('<tr><td colspan="5" class="text-center">No roles found</td></tr>');
                  }
              }
          });
      }

      // Load Available Permissions (Role Form)
      function loadPermissions(selectedPerms) {
          $.ajax({
              url: 'admin_action.php',
              data: { action: 'get_permissions' },
              dataType: 'json',
              success: function(res) {
                  var container = $('#permissions-container');
                  container.empty();
                  if (res.permissions) {
                      $.each(res.permissions, function(key, labelText) {
                          var checked = (selectedPerms && selectedPerms.includes(key)) ? 'checked' : '';
                          var div = $('<div class="checkbox">');
                          var label = $('<label>');
                          label.append('<input type="checkbox" name="permissions[]" value="' + key + '" ' + checked + '> ' + labelText);
                          div.append(label);
                          container.append(div);
                      });
                  }
              }
          });
      }

      // Tab Switching Logic
      $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
          var target = $(e.target).attr("href"); // activated tab
          if (target === '#tab-roles') {
              loadRoleList();
              $('#role-form-view').hide();
              $('#role-list-view').show();
          } else if (target === '#tab-admins') {
              loadAdmins();
              $('#admin-form-view').hide();
              $('#admin-list-view').show();
          }
      });

      // --- ROLE CRUD ---

      // Add Role Button
      $('#btn-add-role').click(function() {
          $('#role-form')[0].reset();
          $('#role-id').val('0');
          loadPermissions([]);
          $('#role-list-view').slideUp();
          $('#role-form-view').slideDown();
      });

      // Edit Role
      window.editRole = function(role) {
          $('#role-form')[0].reset();
          $('#role-id').val(role.id);
          $('#role-name').val(role.name);
          $('#role-description').val(role.description);
          loadPermissions(role.permissions || []);
          $('#role-list-view').slideUp();
          $('#role-form-view').slideDown();
      };

      // Cancel Role Form
      $('#btn-cancel-role').click(function() {
          $('#role-form-view').slideUp();
          $('#role-list-view').slideDown();
      });

      // Save Role
      $('#role-form').submit(function(e) {
          e.preventDefault();
          var formData = $(this).serialize();
          formData += '&action=save_role';
          
          $.ajax({
              url: 'admin_action.php',
              type: 'POST',
              data: formData,
              dataType: 'json',
              success: function(res) {
                  if (res.status === 'ok') {
                      alert('Role saved successfully');
                      $('#btn-cancel-role').click();
                      loadRoleList();
                  } else {
                      alert('Error: ' + res.error);
                  }
              },
              error: function(xhr, status, error) {
                  console.log('AJAX Error:', status, error);
                  console.log('Response:', xhr.responseText);
                  alert('System error occurred: ' + status + '\n' + xhr.responseText.substring(0, 200));
              }
          });
      });

      // Delete Role
      window.deleteRole = function(id) {
          if (confirm('Are you sure you want to delete this role?')) {
              $.ajax({
                  url: 'admin_action.php',
                  type: 'POST',
                  data: { action: 'delete_role', id: id, csrf_token: csrfToken },
                  dataType: 'json',
                  success: function(res) {
                      if (res.status === 'ok') {
                          loadRoleList();
                      } else {
                          alert('Error: ' + res.error);
                      }
                  }
              });
          }
      };

      // Add Admin
      $('#btn-add-admin').click(function() {
          $('#admin-form')[0].reset();
          $('#admin-id').val('0');
          $('#adminModalLabel').text('Add New Admin');
          $('#pass-help').text('(Required)');
          $('#admin-username').prop('readonly', false);
          loadRoles([]);
          $('#admin-list-view').slideUp();
          $('#admin-form-view').slideDown();
      });
      
      // Edit Admin
      function editAdmin(admin) {
          $('#admin-form')[0].reset();
          $('#admin-id').val(admin.id);
          $('#admin-username').val(admin.username); 
          $('#admin-email').val(admin.email);
          $('#adminModalLabel').text('Edit Admin');
          $('#pass-help').text('(Leave blank to keep unchanged)');
          
          var roleIds = admin.role_ids.map(function(id) { return String(id); });
          loadRoles(roleIds);
          
          $('#admin-list-view').slideUp();
          $('#admin-form-view').slideDown();
      }
      
      // Cancel Form
      $('#btn-cancel-admin').click(function() {
          $('#admin-form-view').slideUp();
          $('#admin-list-view').slideDown();
          $('#adminModalLabel').text('Admin Management');
      });
      
      // Save Admin
      $('#admin-form').submit(function(e) {
          e.preventDefault();
          var btn = $('#btn-save-admin');
          var originalText = btn.text();
          btn.prop('disabled', true).text('Saving...');
          
          var formData = $(this).serialize();
          formData += '&action=save_admin';
          
          $.ajax({
              url: 'admin_action.php',
              type: 'POST',
              data: formData,
              dataType: 'json',
              success: function(res) {
                  if (res.status === 'ok') {
                      alert('Saved successfully');
                      $('#btn-cancel-admin').click();
                      loadAdmins();
                  } else {
                      alert('Error: ' + res.error);
                  }
              },
              error: function(xhr) {
                  var msg = 'System error occurred';
                  if (xhr.responseJSON && xhr.responseJSON.error) {
                      msg = xhr.responseJSON.error;
                  }
                  alert(msg + ' (' + xhr.status + ')');
              },
              complete: function() {
                  btn.prop('disabled', false).text(originalText);
              }
          });
      });
      
      // Delete Admin
      function deleteAdmin(id) {
          if (confirm('Are you sure you want to delete this admin? This cannot be undone.')) {
              $.ajax({
                  url: 'admin_action.php',
                  type: 'POST',
                  data: { action: 'delete_admin', id: id, csrf_token: csrfToken },
                  dataType: 'json',
                  success: function(res) {
                      if (res.status === 'ok') {
                          loadAdmins();
                      } else {
                          alert('Error: ' + res.error);
                      }
                  }
              });
          }
      }
      
      // Password Strength
      $('#admin-password').on('input', function() {
          var val = $(this).val();
          var strength = 0;
          if (val.length >= 8) strength++;
          if (val.match(/[a-z]+/)) strength++;
          if (val.match(/[A-Z]+/)) strength++;
          if (val.match(/[0-9]+/)) strength++;
          if (val.match(/[$@#&!]+/)) strength++;
          
          var color = 'red';
          var width = '0%';
          if (val.length > 0) {
              if (strength < 2) { width = '20%'; color = '#ff4d4d'; }
              else if (strength < 4) { width = '60%'; color = '#ffa64d'; }
              else { width = '100%'; color = '#2ecc71'; }
          }
          
          $('#pass-strength').css({ 'width': width, 'background-color': color });
      });
  });

  // Bulk Email Logic
  var selectedUsers = [];
  var isBulkSending = false;
  var bulkStop = false;

  function toggleSelectAll(source) {
      $('.user-checkbox').prop('checked', source.checked);
      updateBulkButton();
  }

  function updateBulkButton() {
      var count = $('.user-checkbox:checked').length;
      var btn = $('#btn-bulk-email');
      if (count > 0) {
          btn.prop('disabled', false);
          btn.html('<i class="fa fa-envelope"></i> Send Certificates (' + count + ')');
      } else {
          btn.prop('disabled', true);
          btn.html('<i class="fa fa-envelope"></i> Send Certificates');
      }
  }

  function openBulkEmailModal() {
      selectedUsers = [];
      $('.user-checkbox:checked').each(function() {
          selectedUsers.push($(this).val());
      });
      
      if (selectedUsers.length === 0) return;
      
      $('#bulk-selection-count').text(selectedUsers.length + ' users selected');
      $('#bulk-progress-container').hide();
      $('#bulk-log').empty();
      
      // Load templates
      $.ajax({
          url: 'ajax_handler.php',
          type: 'POST',
          data: { action: 'get_templates' },
          success: function(response) {
              var data = JSON.parse(response);
              var select = $('#bulk-template-select');
              select.empty();
              select.append('<option value="">Select a template...</option>');
              if (data.status === 'success') {
                  data.templates.forEach(function(t) {
                      select.append('<option value="' + t.id + '">' + t.name + '</option>');
                  });
              }
          }
      });
      
      $('#bulk-email-modal').css('display', 'flex');
      updateEstimatedTime();
  }

  function closeBulkEmailModal() {
      if (isBulkSending) {
          if (!confirm('Sending in progress. Are you sure you want to stop?')) return;
          bulkStop = true;
      }
      $('#bulk-email-modal').hide();
  }

  function toggleTemplateSelect() {
      var checked = $('#bulk-use-template').is(':checked');
      $('#bulk-template-select').prop('disabled', !checked);
  }

  function updateEstimatedTime() {
      var delay = parseInt($('#bulk-delay').val()) || 5;
      var count = selectedUsers.length;
      var seconds = count * delay;
      var minutes = Math.floor(seconds / 60);
      var remainingSeconds = seconds % 60;
      $('#bulk-estimated-time').text(minutes + 'm ' + remainingSeconds + 's');
  }

  function logBulk(msg, type) {
      var color = type === 'error' ? 'red' : (type === 'success' ? 'green' : 'black');
      $('#bulk-log').append('<div style="color:' + color + ';">' + msg + '</div>');
      var d = $('#bulk-log');
      d.scrollTop(d.prop("scrollHeight"));
  }

  function toggleBulkOverrideEmail() {
      var checked = $('#bulk-use-override-email').is(':checked');
      if (checked) {
          $('#bulk-override-email').slideDown();
      } else {
          $('#bulk-override-email').slideUp();
      }
  }

  async function startBulkEmail() {
      if (selectedUsers.length === 0) return;
      
      var useTemplate = $('#bulk-use-template').is(':checked'); // Note: this checkbox doesn't exist in HTML above, but kept for logic if it exists elsewhere
      // Actually, in the HTML I saw, there is no #bulk-use-template checkbox, just the select.
      // But let's stick to fixing the override email and action first.
      
      var templateId = $('#bulk-template-select').val();
      var overrideEmail = '';
      if ($('#bulk-use-override-email').is(':checked')) {
          overrideEmail = $('#bulk-override-email').val();
          if (!overrideEmail || !overrideEmail.includes('@')) {
              alert('Please enter a valid override email address');
              return;
          }
      }

      if (!templateId) { // Simplified check as useTemplate checkbox is missing in HTML block I saw
           // However, if the logic relies on useTemplate, I should check if it exists.
           // For now, let's assume template is required if selected.
      }
      
      if (!templateId) {
          alert('Please select a template');
          return;
      }
      
      var delay = parseInt($('#email-delay').val()) || 5; // ID in HTML is email-delay, JS had bulk-delay
      var files = []; // No attachment input in HTML I saw, but JS had bulk-attachments
      // The HTML I read has:
      // <input type="number" id="email-delay" ...>
      // No file input.
      // JS had: var delay = parseInt($('#bulk-delay').val()) || 5;
      // JS had: var files = $('#bulk-attachments')[0].files;
      
      // I need to fix these ID mismatches.
      
      isBulkSending = true;
      bulkStop = false;
      
      $('#btn-start-bulk').prop('disabled', true);
      $('#bulk-progress-container').show();
      $('#bulk-progress-bar').css('width', '0%').text('0%');
      
      var processed = 0;
      var total = selectedUsers.length;
      
      // Fetch template data
      var templateData = null;
      // Always fetch template as we require it
      try {
          var tRes = await $.ajax({
              url: 'ajax_handler.php',
              type: 'POST',
              data: { action: 'load_template', id: templateId }
          });
          var tJson = JSON.parse(tRes);
          if (tJson.status === 'success') {
              templateData = JSON.parse(tJson.data);
          } else {
              throw new Error(tJson.message);
          }
      } catch (e) {
          logBulk('Error loading template: ' + e.message, 'error');
          isBulkSending = false;
          $('#btn-start-bulk').prop('disabled', false);
          return;
      }
      
      for (var i = 0; i < total; i++) {
          if (bulkStop) {
              logBulk('Process stopped by user.', 'error');
              break;
          }
          
          var userId = selectedUsers[i];
          $('#bulk-status-text').text('Processing user ' + (i + 1) + ' of ' + total + ' (ID: ' + userId + ')...');
          
          try {
              // Fetch user data
              var uRes = await $.ajax({
                  url: 'ajax_handler.php',
                  type: 'POST',
                  data: { action: 'get_user_data', user_id: userId }
              });
              var uData = JSON.parse(uRes);
              
              if (uData.status !== 'success') {
                  throw new Error('User data not found');
              }
              
              var formData = new FormData();
              formData.append('action', 'send_bulk_single'); // Corrected action
              formData.append('uid', userId); // ajax_handler expects 'uid', JS had 'user_id'
              if (overrideEmail) {
                  formData.append('override_email', overrideEmail);
              }
              
              // Append files - skipped as input missing
              
              // Generate PDF
              if (templateData) {
                  var pdfBlob = await generateCertificatePDF(uData.data, templateData);
                  formData.append('pdf_data', await blobToBase64(pdfBlob)); // ajax_handler expects pdf_data (base64) not file upload
                  // Wait, ajax_handler send_bulk_single expects 'pdf_data' (base64 string) at line 482: $pdfData = isset($_POST['pdf_data']) ? $_POST['pdf_data'] : '';
                  // JS code was sending 'certificate_pdf' as file.
                  // I need to convert blob to base64.
              }
              
              // Send email
              var sendRes = await $.ajax({
                  url: 'ajax_handler.php',
                  type: 'POST',
                  data: formData,
                  processData: false,
                  contentType: false
              });
              var sendJson = JSON.parse(sendRes);
              
              if (sendJson.status === 'success') {
                  logBulk('Sent to ID ' + userId + ': OK', 'success');
                  // Update UI status to Sent
                  var select = $('select[onchange*="updateCertificateStatus(' + userId + ',"]').first();
                  if (select.length) {
                      select.val('1');
                      select.css({ 'border-color': '#5cb85c', 'border-width': '2px' });
                  }
              } else {
                  logBulk('Error ID ' + userId + ': ' + sendJson.message, 'error');
              }
              
          } catch (e) {
              logBulk('Error ID ' + userId + ': ' + e.message, 'error');
          }
          
          processed++;
          var pct = Math.round((processed / total) * 100);
          $('#bulk-progress-bar').css('width', pct + '%').text(pct + '%');
          
          if (i < total - 1) {
              await new Promise(r => setTimeout(r, delay * 1000));
          }
      }
      
      isBulkSending = false;
      $('#btn-start-bulk').prop('disabled', false);
      $('#bulk-status-text').text('Completed. Processed ' + processed + ' users.');
      alert('Bulk sending completed.');
  }
  
  function blobToBase64(blob) {
      return new Promise((resolve, _) => {
          const reader = new FileReader();
          reader.onloadend = () => resolve(reader.result.split(',')[1]);
          reader.readAsDataURL(blob);
      });
  }

  function generateCertificatePDF(user, templateData) {
      return new Promise((resolve, reject) => {
          // Create a hidden container
          var container = document.createElement('div');
          container.style.position = 'fixed';
          container.style.left = '-9999px';
          container.style.top = '0';
          container.style.width = '1123px'; // A4 Landscape roughly
          container.style.height = '794px';
          container.style.backgroundColor = '#fff';
          document.body.appendChild(container);
          
          // Render elements
          templateData.forEach(el => {
              var div = document.createElement('div');
              div.style.cssText = el.style;
              div.style.position = 'absolute'; // Ensure absolute positioning
              
              // Replace placeholders
              var content = el.content;
              
              // Check for data-variable
              if (el.dataVariable) {
                  var val = user[el.dataVariable] || '';
                  content = val;
              } else {
                  // Fallback to text replacement
                  content = content.replace(/{name}/g, (user.fname + ' ' + (user.lname || '')).trim())
                                   .replace(/{ref}/g, user.id)
                                   .replace(/{paper_title}/g, user.postertitle || '')
                                   .replace(/{category}/g, user.category || '')
                                   .replace(/{organization}/g, user.organization || '')
                                   .replace(/{profession}/g, user.profession || '');
              }
              
              div.innerHTML = content;
              
              // Special handling for QR codes
              if (el.content === '[QR CODE]') {
                  div.innerHTML = ''; // Clear placeholder
                  // Create QR code
                  new QRCode(div, {
                      text: "https://reg-sys.com/verify.php?id=" + user.id,
                      width: parseInt(div.style.width) || 100,
                      height: parseInt(div.style.height) || 100
                  });
              }
              
              container.appendChild(div);
          });
          
          // Generate PDF
          html2canvas(container, { scale: 2 }).then(canvas => {
              var imgData = canvas.toDataURL('image/jpeg', 0.95);
              var pdf = new window.jsPDF({
                  orientation: 'landscape',
                  unit: 'px',
                  format: [1123, 794]
              });
              pdf.addImage(imgData, 'JPEG', 0, 0, 1123, 794);
              var blob = pdf.output('blob');
              document.body.removeChild(container);
              resolve(blob);
          }).catch(err => {
              document.body.removeChild(container);
              reject(err);
          });
      });
  }
  </script>
  </body>

</html>

<?php } ?>

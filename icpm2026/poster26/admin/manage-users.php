<?php
require_once 'session_setup.php';
include'dbconnection.php';
require_once 'permission_helper.php';

// Auth Check
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Permission Check
if (!has_permission($con, 'view_users')) {
    die("You do not have permission to access this page.");
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
        if (preg_match('/^(\d+)-co[1-5]$/i', $q, $m)) {
            $q = $m[1];
        }
        $categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
        $limitArg = isset($_GET['limit']) ? $_GET['limit'] : 100;
        $limit = ($limitArg === 'ALL') ? 1000000 : intval($limitArg);
        $pattern = '%' . $q . '%';
        $categoryClause = '';
        if ($categoryFilter !== '') {
            $safeCategory = mysqli_real_escape_string($con, $categoryFilter);
            $categoryClause = " AND category COLLATE utf8mb4_bin LIKE '%" . $safeCategory . "%'";
        }
        $sql = "SELECT * FROM users WHERE
        (CAST(id AS CHAR) COLLATE utf8mb4_bin LIKE ? OR
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
        password COLLATE utf8mb4_bin LIKE ? OR
        contactno COLLATE utf8mb4_bin LIKE ? OR
        userip COLLATE utf8mb4_bin LIKE ? OR
        companyref COLLATE utf8mb4_bin LIKE ? OR
        paypalref COLLATE utf8mb4_bin LIKE ? OR
        supervisor_name COLLATE utf8mb4_bin LIKE ? OR
        supervisor_nationality COLLATE utf8mb4_bin LIKE ? OR
        supervisor_contact COLLATE utf8mb4_bin LIKE ? OR
        supervisor_email COLLATE utf8mb4_bin LIKE ? OR
        postertitle COLLATE utf8mb4_bin LIKE ?)
        AND (source_system='poster' OR source_system='both')" . $categoryClause . "
        ORDER BY id ASC LIMIT $limit";
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            str_repeat('s', 32),
            $pattern, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern,
            $pattern, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern,
            $pattern, $pattern, $pattern, $pattern, $pattern,
            $pattern, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern,
            $pattern, $pattern, $pattern, $pattern, $pattern, $pattern
        );
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = false;
    }
    $cnt = 1;
    if ($result && mysqli_num_rows($result) > 0) {
        $wrap = function($val, $q, $short=false) {
            $txt = (string)$val;
            $hl = highlight_text($txt, $q);
            $cls = $short ? 'cell-content short' : 'cell-content';
            return '<div class="' . $cls . '" title="' . escape_html($txt) . '">' . $hl . '</div>';
        };
        while ($row = mysqli_fetch_assoc($result)) {
            $certStatus = isset($row['certificate_sent']) ? $row['certificate_sent'] : 0;
            echo "<tr data-user-id=\"" . intval($row['id']) . "\">";
            echo "<td><input type='checkbox' class='user-checkbox' value='" . $row['id'] . "' onchange='updateBulkButton()'></td>";
            echo "<td><button class=\"btn btn-primary btn-xs print-user-btn\" data-user-id=\"" . intval($row['id']) . "\" data-main=\"" . escape_html($row['fname']) . "\" data-co1=\"" . escape_html($row['coauth1name']) . "\" data-co2=\"" . escape_html($row['coauth2name']) . "\" data-co3=\"" . escape_html($row['coauth3name']) . "\" data-co4=\"" . escape_html($row['coauth4name']) . "\" data-co5=\"" . escape_html(isset($row['coauth5name']) ? $row['coauth5name'] : '') . "\"><i class=\"fa fa-print\"></i></button></td>";
            echo "<td>" . $wrap($cnt, '', true) . "</td>";
            echo "<td>" . $wrap($row['id'], $q, false) . "</td>";
            echo "<td>" . $wrap($row['fname'], $q) . "</td>";
            echo "<td>" . $wrap(isset($row['postertitle']) ? $row['postertitle'] : '', $q) . "</td>";
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
            echo "<td>" . $wrap($row['contactno'], $q) . "</td>";
            echo "<td><span class=\"password-display\" data-user-id=\"" . intval($row['id']) . "\">******</span></td>";
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
            echo '<a href="certificate-editor.php?uid=' . escape_html($row['id']) . '"><button class="btn btn-warning btn-xs" aria-label="Certificate"><i class="fa fa-certificate"></i></button></a> ';
            echo '<a href="update-profile.php?uid=' . escape_html($row['id']) . '"><button class="btn btn-primary btn-xs" aria-label="Edit profile"><i class="fa fa-pencil"></i></button></a> ';
            echo '<a href="manage-users.php?id=' . escape_html($row['id']) . '"><button class="btn btn-danger btn-xs" onClick="return confirm(\'Do you really want to delete\');" aria-label="Delete user"><i class="fa fa-trash-o "></i></button></a>';
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

if (isset($_GET['export_db'])) {
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
            $data = mysqli_query($con, "SELECT * FROM users WHERE (source_system='poster' OR source_system='both')");
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

if (isset($_GET['export_excel'])) {
    if (!has_permission($con, 'export_data')) {
        die("Permission denied");
    }
    $scope = isset($_GET['scope']) ? $_GET['scope'] : 'all';
    if ($scope !== 'visible' && $scope !== 'all') {
        $scope = 'all';
    }
    $visibleColsParam = isset($_GET['visible_cols']) ? $_GET['visible_cols'] : '';
    $visibleCols = array();
    if ($visibleColsParam !== '') {
        $parts = explode(',', $visibleColsParam);
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $visibleCols[] = $p;
            }
        }
    }
    $showCo1 = in_array('hide-coauth1', $visibleCols, true);
    $showCo2 = in_array('hide-coauth2', $visibleCols, true);
    $showCo3 = in_array('hide-coauth3', $visibleCols, true);
    $showCo4 = in_array('hide-coauth4', $visibleCols, true);
    $showCo5 = in_array('hide-coauth5', $visibleCols, true);
    $showEmails = in_array('hide-emails', $visibleCols, true);
    $idsParam = isset($_GET['ids']) ? $_GET['ids'] : '';
    $idList = array();
    if ($idsParam !== '') {
        $rawIds = explode(',', $idsParam);
        foreach ($rawIds as $idStr) {
            $id = intval($idStr);
            if ($id > 0) {
                $idList[] = $id;
            }
        }
    }
    header('Content-Type: text/csv; charset=utf-8');
    $filename = 'users_list_' . date('Ymd_His') . '.csv';
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    if (!$output) {
        exit;
    }
    $headers = array(
        'Sno.',
        'Ref Number',
        'Main Auth',
        'Poster Title',
        'Nationality'
    );
    if ($showCo1) {
        $headers[] = 'Co-auth 1';
        $headers[] = 'Co-auth 1 Nationality';
    }
    if ($showCo2) {
        $headers[] = 'Co-auth 2';
        $headers[] = 'Co-auth 2 Nationality';
    }
    if ($showCo3) {
        $headers[] = 'Co-auth 3';
        $headers[] = 'Co-auth 3 Nationality';
    }
    if ($showCo4) {
        $headers[] = 'Co-auth 4';
        $headers[] = 'Co-auth 4 Nationality';
    }
    if ($showCo5) {
        $headers[] = 'Co-auth 5';
        $headers[] = 'Co-auth 5 Nationality';
    }
    if ($showEmails) {
        $headers[] = 'Co-auth 1 Email';
        $headers[] = 'Co-auth 2 Email';
        $headers[] = 'Co-auth 3 Email';
        $headers[] = 'Co-auth 4 Email';
        $headers[] = 'Co-auth 5 Email';
    }
    $headers[] = 'Supervisor Name';
    $headers[] = 'Supervisor Nationality';
    $headers[] = 'Supervisor Contact';
    if ($showEmails) {
        $headers[] = 'Supervisor Email';
        $headers[] = 'Email Id';
    }
    $headers[] = 'Profession';
    $headers[] = 'University';
    $headers[] = 'Category';
    $headers[] = 'Contact No';
    $headers[] = 'Password';
    $headers[] = 'Company Ref';
    $headers[] = 'Posting Date';
    fputcsv($output, $headers);
    $query = "SELECT * FROM users WHERE (source_system='poster' OR source_system='both')";
    if ($scope === 'visible') {
        if (!empty($idList)) {
            $idString = implode(',', $idList);
            $query .= " AND id IN (" . $idString . ")";
            $query .= " ORDER BY FIELD(id," . $idString . ")";
        } else {
            fputcsv($output, array('No data', 'No visible rows selected for export.'));
            fclose($output);
            exit;
        }
    } else {
        $query .= " ORDER BY id DESC";
    }
    $result = mysqli_query($con, $query);
    if (!$result) {
        fputcsv($output, array('Error', mysqli_error($con)));
        fclose($output);
        exit;
    }
    if (mysqli_num_rows($result) === 0) {
        fputcsv($output, array('No data', 'Current filters returned no records.'));
        fclose($output);
        exit;
    }
    $sno = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $line = array(
            $sno,
            $row['id'],
            $row['fname'],
            isset($row['postertitle']) ? $row['postertitle'] : '',
            isset($row['nationality']) ? $row['nationality'] : ''
        );
        if ($showCo1) {
            $line[] = $row['coauth1name'];
            $line[] = isset($row['coauth1nationality']) ? $row['coauth1nationality'] : '';
        }
        if ($showCo2) {
            $line[] = $row['coauth2name'];
            $line[] = isset($row['coauth2nationality']) ? $row['coauth2nationality'] : '';
        }
        if ($showCo3) {
            $line[] = $row['coauth3name'];
            $line[] = isset($row['coauth3nationality']) ? $row['coauth3nationality'] : '';
        }
        if ($showCo4) {
            $line[] = $row['coauth4name'];
            $line[] = isset($row['coauth4nationality']) ? $row['coauth4nationality'] : '';
        }
        if ($showCo5) {
            $line[] = isset($row['coauth5name']) ? $row['coauth5name'] : '';
            $line[] = isset($row['coauth5nationality']) ? $row['coauth5nationality'] : '';
        }
        if ($showEmails) {
            $line[] = $row['coauth1email'];
            $line[] = $row['coauth2email'];
            $line[] = $row['coauth3email'];
            $line[] = $row['coauth4email'];
            $line[] = $row['coauth5email'];
        }
        $line[] = isset($row['supervisor_name']) ? $row['supervisor_name'] : '';
        $line[] = isset($row['supervisor_nationality']) ? $row['supervisor_nationality'] : '';
        $line[] = isset($row['supervisor_contact']) ? $row['supervisor_contact'] : '';
        if ($showEmails) {
            $line[] = isset($row['supervisor_email']) ? $row['supervisor_email'] : '';
            $line[] = $row['email'];
        }
        $line[] = $row['profession'];
        $line[] = $row['organization'];
        $line[] = $row['category'];
        $line[] = $row['contactno'];
        $line[] = $row['password'];
        $line[] = $row['companyref'];
        $line[] = $row['posting_date'];
        fputcsv($output, $line);
        $sno++;
    }
    fclose($output);
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
    $msg = mysqli_query($con,"delete from users where id='$adminid' AND (source_system='poster' OR source_system='both')");
    if($msg)
    {
        // Audit Log
        $admin_id = $_SESSION['id'];
        $admin_username = isset($_SESSION['login']) ? $_SESSION['login'] : 'Unknown';
        $action = 'delete_user';
        $details = "Deleted user ID: $adminid";
        $ip = $_SERVER['REMOTE_ADDR'];
        $system_context = 'poster';
        
        $logStmt = mysqli_prepare($con, "INSERT INTO admin_audit_logs (admin_id, admin_username, action, details, ip_address, system_context) VALUES (?, ?, ?, ?, ?, ?)");
        if ($logStmt) {
            mysqli_stmt_bind_param($logStmt, 'isssss', $admin_id, $admin_username, $action, $details, $ip, $system_context);
            mysqli_stmt_execute($logStmt);
            mysqli_stmt_close($logStmt);
        }

        echo "<script>alert('Data deleted');</script>";
    }
}
$categoryOptions = array();
$currentCategory = isset($_GET['category']) ? $_GET['category'] : '';
$catRes = mysqli_query($con, "SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category <> '' AND (source_system='poster' OR source_system='both') ORDER BY category ASC");
if ($catRes) {
    while ($cRow = mysqli_fetch_assoc($catRes)) {
        $categoryOptions[] = $cRow['category'];
    }
}
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

              	  <h5 class="centered"><?php echo htmlspecialchars($_SESSION['login'] ?? 'Admin'); ?></h5>
              	  	
                  <li class="mt">
                      <a href="manage-users.php">
                          <i class="fa fa-users"></i>
                          <span>Manage Users</span>
                      </a>
                  </li>
                  <li class="sub-menu">
                      <a href="manage-attendance.php">
                          <i class="fa fa-clock-o"></i>
                          <span>Attendance</span>
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

                   

                  </li>

              

                 

              </ul>

          </div>

      </aside>

      <section id="main-content">

          <section class="wrapper">

          	<h3><i class="fa fa-angle-right"></i> Manage Users</h3>
				<div class="row">
				
                  
	                  
                  <div class="col-md-12">
                    <div class="content-panel search-toolbar" role="search" aria-label="User table instant search">
                      <div class="input-group">
                        <span class="input-group-addon" id="users-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span>
                        <input type="text" id="users-search" class="form-control" placeholder="Search users..." aria-label="Search users" aria-controls="users-table" aria-describedby="users-search-icon">
                        <span class="input-group-btn">
                          <button class="btn btn-default" type="button" id="clear-search" aria-label="Clear search">Clear</button>
                        </span>
                      </div>
                      <div id="search-status" class="sr-only" aria-live="polite"></div>
                      <div class="row" style="margin-top:10px;">
                        <div class="col-sm-6">
                          <div class="form-inline">
                            <label for="rows-per-page" style="margin-right:5px;">Rows:</label>
                            <select id="rows-per-page" class="form-control" style="margin-right:15px; width:auto; display:inline-block;">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100" selected>100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                                <option value="1000">1000</option>
                                <option value="ALL">ALL</option>
                            </select>

                            <label for="category-filter" style="margin-right:8px;">Category:</label>
                            <select id="category-filter" class="form-control">
                              <option value="">All Categories</option>
                              <?php foreach ($categoryOptions as $cat) {
                                  $sel = ($currentCategory === $cat) ? 'selected' : '';
                                  echo '<option value="' . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>' . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') . '</option>';
                              } ?>
                            </select>
                            <button type="button" class="btn btn-default" id="clear-category" style="margin-left:5px;">Clear Filter</button>
                          </div>
                        </div>
                        <div class="col-sm-6 text-right">
                          <a href="add-user.php" class="btn btn-primary" id="add-user-btn" aria-label="Add New User"><i class="fa fa-plus"></i> Add User</a>
                          <a href="manage-users.php?export_db=1&mode=download" class="btn btn-success" id="export-db" aria-label="Export database">Export Database</a>
                          <button type="button" class="btn btn-info" id="export-visible" aria-label="Export filtered rows to Excel">Export Filtered to Excel</button>
                          <button type="button" class="btn btn-info" id="export-all" aria-label="Export all rows to Excel">Export All to Excel</button>
                          <button class="btn btn-warning" id="btn-bulk-email" onclick="openBulkEmailModal()" disabled style="margin-left: 5px;"><i class="fa fa-envelope"></i> Send Certificates</button>
                          <label class="checkbox-inline" style="margin-left: 10px;">
                              <input type="checkbox" id="togglePasswords"> Show Passwords
                          </label>
                        </div>
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
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-supervisor"> Supervisor</label>
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
                                  <th>Print</th>

                                  <th style="width:60px; text-align:center;">Sno.</th>

                                  <th>Ref Number</th>

                                  <th class="hidden-phone">Main Auth</th>
                                  <th> Poster Title</th>
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
                                  <th>Contact no.</th>

                                  <th>Password</th>

                                  <th>Company ref</th>
                                  <th>Reg. Date</th>
                                  <th>Action</th>
                              </tr>

                              </thead>

                              <tbody>

                              <?php $ret=mysqli_query($con,"select * from users WHERE (source_system='poster' OR source_system='both') ORDER BY id DESC");
                              $cnt=1;
                              $wrapInit = function($val, $short=false) {
                                  $txt = (string)$val;
                                  $cls = $short ? 'cell-content short' : 'cell-content';
                                  return '<div class="' . $cls . '" title="' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</div>';
                              };
                              while($row=mysqli_fetch_array($ret))
                              {
                                  $certStatus = isset($row['certificate_sent']) ? $row['certificate_sent'] : 0;
                                  echo "<tr data-user-id=\"" . intval($row['id']) . "\">";
                                  echo "<td><input type='checkbox' class='user-checkbox' value='" . $row['id'] . "' onchange='updateBulkButton()'></td>";
                                  echo "<td><button class=\"btn btn-primary btn-xs print-user-btn\" data-user-id=\"" . intval($row['id']) . "\" data-main=\"" . htmlspecialchars($row['fname'], ENT_QUOTES, 'UTF-8') . "\" data-co1=\"" . htmlspecialchars($row['coauth1name'], ENT_QUOTES, 'UTF-8') . "\" data-co2=\"" . htmlspecialchars($row['coauth2name'], ENT_QUOTES, 'UTF-8') . "\" data-co3=\"" . htmlspecialchars($row['coauth3name'], ENT_QUOTES, 'UTF-8') . "\" data-co4=\"" . htmlspecialchars($row['coauth4name'], ENT_QUOTES, 'UTF-8') . "\" data-co5=\"" . htmlspecialchars(isset($row['coauth5name']) ? $row['coauth5name'] : '', ENT_QUOTES, 'UTF-8') . "\"><i class=\"fa fa-print\"></i></button></td>";
                                  echo "<td>" . $wrapInit($cnt, true) . "</td>";
                                  echo "<td>" . $wrapInit($row['id'], false) . "</td>";
                                  echo "<td>" . $wrapInit($row['fname']) . "</td>";
                                  echo "<td>" . $wrapInit(isset($row['postertitle']) ? $row['postertitle'] : '') . "</td>";
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
                                  echo "<td>" . $wrapInit($row['contactno']) . "</td>";
                                  echo "<td><span class=\"password-display\" data-user-id=\"" . intval($row['id']) . "\">******</span></td>";
                                  echo "<td>" . $wrapInit($row['companyref']) . "</td>";
                                  echo "<td>" . $wrapInit($row['posting_date']) . "</td>";
                                  
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
                                  echo '<button class="btn btn-primary btn-xs print-user-btn" data-user-id="' . intval($row['id']) . '"><i class="fa fa-print"></i></button> ';
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
>

  ></section>

  <div aria-hidden="true" aria-labelledby="passwordVerifyLabel" role="dialog" tabindex="-1" id="passwordVerifyModal" class="modal fade">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h4 class="modal-title" id="passwordVerifyLabel">Admin Verification Required</h4>
              </div>
              <div class="modal-body">
                  <p>Please enter your admin password to reveal user passwords.</p>
                  <div class="form-group">
                      <label>Admin Password</label>
                      <input type="password" class="form-control" id="verify-admin-password" placeholder="Password">
                  </div>
                  <div class="alert alert-danger" id="verify-error" style="display:none;"></div>
              </div>
              <div class="modal-footer">
                  <button data-dismiss="modal" class="btn btn-default" type="button">Cancel</button>
                  <button class="btn btn-theme" type="button" onclick="verifyAndShowPasswords()">Verify</button>
              </div>
          </div>
      </div>
  </div>

  <div aria-hidden="true" aria-labelledby="printChoiceLabel" role="dialog" tabindex="-1" id="printChoiceModal" class="modal fade">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                  <h4 class="modal-title" id="printChoiceLabel">Select Author To Print</h4>
              </div>
              <div class="modal-body">
                  <input type="hidden" id="print-user-id">
                  <div class="radio">
                      <label><input type="radio" name="print-author" value="main" checked> Main author</label>
                  </div>
                  <div class="radio">
                      <label><input type="radio" name="print-author" value="co1"> Co-author 1</label>
                  </div>
                  <div class="radio">
                      <label><input type="radio" name="print-author" value="co2"> Co-author 2</label>
                  </div>
                  <div class="radio">
                      <label><input type="radio" name="print-author" value="co3"> Co-author 3</label>
                  </div>
                  <div class="radio">
                      <label><input type="radio" name="print-author" value="co4"> Co-author 4</label>
                  </div>
                  <div class="radio">
                      <label><input type="radio" name="print-author" value="co5"> Co-author 5</label>
                  </div>
              </div>
              <div class="modal-footer">
                  <button data-dismiss="modal" class="btn btn-default" type="button">Cancel</button>
                  <button class="btn btn-primary" type="button" id="print-author-confirm">Print</button>
              </div>
          </div>
      </div>
  </div>

  <!-- Bulk Email/Certificate Modal -->
  <div aria-hidden="true" aria-labelledby="bulkEmailLabel" role="dialog" tabindex="-1" id="bulk-email-modal" class="modal fade">
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
                          
                          <form id="bulkEmailForm">
                              <div class="form-group">
                                  <label>Email Delay (seconds)</label>
                                  <input type="number" id="email-delay" class="form-control" value="5" min="1">
                                  <p class="help-block">Estimated time: <span id="estimated-time">0s</span></p>
                              </div>
                              
                              <hr>
                              <h5>Certificate Options</h5>
                              <div class="checkbox">
                                  <label>
                                      <input type="checkbox" id="use-template" checked> Generate Certificate from Template
                                  </label>
                              </div>
                              
                              <div class="form-group" id="template-group">
                                  <label>Select Template</label>
                                  <div style="display: flex; gap: 5px;">
                                      <select id="certificate-template" class="form-control">
                                          <option value="">Loading...</option>
                                      </select>
                                      <button type="button" class="btn btn-info" id="btn-load-preview" onclick="loadTemplatePreview()" title="Load and preview template">
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
                              
                              <div class="checkbox" id="preview-confirm-container" style="display:none; margin-top: 15px; background: #e8f8f5; padding: 10px; border-radius: 4px; border: 1px solid #2ecc71;">
                                  <label style="font-weight: bold; color: #27ae60;">
                                      <input type="checkbox" id="confirm-preview"> I confirm the certificate template is correct and ready for sending.
                                  </label>
                              </div>
                              
                              <div class="form-group">
                                  <label>Additional Attachments (Optional)</label>
                                  <input type="file" id="bulk-attachments" class="form-control" multiple>
                                  <p class="help-block">These files will be sent to ALL selected users.</p>
                              </div>
                          </form>
                          
                          <div id="bulk-progress-container" style="display:none; margin-top: 15px;">
                              <label>Sending Progress:</label>
                              <div class="progress progress-striped active">
                                  <div id="bulk-progress-bar" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                      0%
                                  </div>
                              </div>
                              <p id="bulk-status-text" class="text-center">Initializing...</p>
                          </div>
                      </div>
                      
                      <!-- Right Column: Live Preview -->
                      <div class="col-md-8">
                          <div class="bulk-editor-interface" style="border: 1px solid #ccc; background: #f0f2f5; height: 460px; display: flex; flex-direction: column;">
                              <!-- Editor Toolbar Simulation -->
                              <div class="bulk-editor-toolbar" style="height: 40px; background: #2c3e50; color: white; display: flex; align-items: center; padding: 0 15px;">
                                  <h5 style="margin: 0; color: white;"><i class="fa fa-paint-brush"></i> Certificate Editor View</h5>
                                  <span id="preview-template-name" style="margin-left: auto; font-size: 12px; color: #bdc3c7;">No template loaded</span>
                              </div>
                              <!-- Workspace -->
                              <div id="bulk-preview-wrapper" style="flex: 1; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center; background-image: radial-gradient(#bdc3c7 1px, transparent 1px); background-size: 20px 20px;">
                                  <div id="bulk-preview-scale-container" style="width: 100%; height: 100%;">
                                      <div id="certificate-preview" style="width: 100%; height: 100%;">
                                          <div style="display: flex; height: 100%; align-items: center; justify-content: center; color: #999;">
                                              Preview will appear here...
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  
                  <!-- Bottom: Detailed Report -->
                  <div class="row" style="margin-top: 20px;">
                      <div class="col-md-12">
                          <label>Processing Report</label>
                          <div style="height: 200px; overflow-y: auto; border: 1px solid #ddd;">
                              <table class="table table-striped table-condensed" id="bulk-report-table">
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
                                  <tbody>
                                  </tbody>
                              </table>
                          </div>
                          <div style="margin-top: 10px; text-align: right;">
                               <span id="bulk-summary-stats" style="margin-right: 15px; font-weight: bold;"></span>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="modal-footer">
                  <button data-dismiss="modal" class="btn btn-default" id="btn-cancel-bulk" type="button">Close</button>
                  <button class="btn btn-primary" id="btn-start-bulk" type="button" onclick="startBulkProcess()">Start Sending</button>
              </div>
          </div>
      </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="assets/js/jquery.js"></script>

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

      // --- Bulk Certificate Logic (Standardized) ---
      
      var bulkQueue = [];
      var bulkProcessing = false;
      var bulkSuccessCount = 0;
      var bulkFailCount = 0;
      var bulkTemplateId = null;
      var bulkOverrideEmail = '';

      function toggleBulkOverrideEmail() {
          var checked = $('#bulk-use-override-email').is(':checked');
          if (checked) {
              $('#bulk-override-email').slideDown();
          } else {
              $('#bulk-override-email').slideUp();
          }
      }

      function updateBulkButton() {
          var count = $('.user-checkbox:checked').length;
          var btn = $('#btn-bulk-email');
          // If button doesn't exist, try to find it or create it (usually it should exist in DOM)
          if(btn.length === 0) {
             // In case it's named differently or we need to add it dynamically
             // For now assume it exists or will be added
          }
          
          if (count > 0) {
              btn.prop('disabled', false);
              btn.text('Send Certificates (' + count + ')');
          } else {
              btn.prop('disabled', true);
              btn.text('Send Certificates');
          }
          $('#bulk-selection-count-modal').text(count + ' users selected');
      }

      // Open Modal
      $(document).on('click', '#btn-bulk-email', function() {
          var count = $('.user-checkbox:checked').length;
          if (count === 0) return;
          
          // Load Templates
          var $select = $('#certificate-template');
          $select.html('<option>Loading...</option>');
          
          $.post('ajax_handler.php', { action: 'get_templates' }, function(res) {
              try {
                  var data = typeof res === 'object' ? res : JSON.parse(res);
                  if (data.status === 'success') {
                      var html = '<option value="">-- Select Template --</option>';
                      data.data.forEach(function(t) {
                          html += '<option value="' + t.id + '">' + t.name + '</option>';
                      });
                      $select.html(html);
                  } else {
                      $select.html('<option>Error loading templates</option>');
                  }
              } catch(e) { $select.html('<option>Error</option>'); }
          });
          
          $('#bulk-email-modal').modal('show');
      });

      // Load Preview
      function loadTemplatePreview() {
          var tplId = $('#certificate-template').val();
          if (!tplId) {
              alert('Please select a template');
              return;
          }
          
          var $container = $('#certificate-preview');
          $container.html('<iframe id="preview-frame" src="certificate-editor.php?preview_template=' + tplId + '" style="width:100%; height:100%; border:none; pointer-events:none;"></iframe>');
          
          $('#preview-template-name').text($('#certificate-template option:selected').text());
          $('#preview-confirm-container').show();
      }

      // Start Bulk Process
      function startBulkProcess() {
          if (!$('#confirm-preview').is(':checked')) {
              alert('Please confirm the template preview.');
              return;
          }
          
          bulkTemplateId = $('#certificate-template').val();
          if (!bulkTemplateId) {
              alert('Please select a template.');
              return;
          }
          
          bulkOverrideEmail = '';
          if ($('#bulk-use-override-email').is(':checked')) {
              bulkOverrideEmail = $('#bulk-override-email').val();
              if (!bulkOverrideEmail || !bulkOverrideEmail.includes('@')) {
                  alert('Please enter a valid override email address');
                  return;
              }
          }

          bulkQueue = [];
          $('.user-checkbox:checked').each(function() {
              bulkQueue.push($(this).val());
          });
          
          if (bulkQueue.length === 0) return;
          
          // UI Reset
          $('#bulkEmailForm').hide();
          $('#bulk-progress-container').show();
          $('#btn-start-bulk').prop('disabled', true);
          $('#btn-cancel-bulk').prop('disabled', true); // Prevent closing during process
          
          $('#bulk-report-table tbody').empty();
          bulkSuccessCount = 0;
          bulkFailCount = 0;
          
          bulkProcessing = true;
          processNextBulkUser();
      }

      function processNextBulkUser() {
          if (bulkQueue.length === 0 || !bulkProcessing) {
              finishBulkProcess();
              return;
          }
          
          var uid = bulkQueue.shift();
          var delay = parseInt($('#email-delay').val()) * 1000 || 5000; // Delay in ms
          
          // Update Status
          var total = $('.user-checkbox:checked').length;
          var current = total - bulkQueue.length;
          var percent = Math.round((current / total) * 100);
          
          $('#bulk-progress-bar').css('width', percent + '%').text(percent + '%');
          $('#bulk-status-text').text('Processing User ID: ' + uid + ' (' + current + '/' + total + ')');
          
          // Use Iframe to Generate & Send
          var $container = $('#certificate-preview');
          // Load Editor in Iframe with autogen params
          // autogen=true, template_id=..., uid=...
          var iframeUrl = 'certificate-editor.php?uid=' + uid + '&autogen=true&template_id=' + bulkTemplateId;
          if (bulkOverrideEmail) {
              iframeUrl += '&override_email=' + encodeURIComponent(bulkOverrideEmail);
          }
          
          $container.html('<iframe id="processing-frame-' + uid + '" src="' + iframeUrl + '" style="width:100%; height:100%; border:none;"></iframe>');
          
          // Timeout failsafe (e.g. 30 seconds per user)
          var timeout = setTimeout(function() {
              if (document.getElementById('processing-frame-' + uid)) {
                  handleBulkResult(uid, 'error', 'Timeout');
              }
          }, 30000);
          
          // Listen for message from iframe
          var messageHandler = function(event) {
              if (event.data && event.data.action === 'CERT_PROCESSED' && event.data.uid == uid) {
                  clearTimeout(timeout);
                  window.removeEventListener('message', messageHandler);
                  handleBulkResult(uid, event.data.status, event.data.message);
              }
          };
          window.addEventListener('message', messageHandler);
      }

      function handleBulkResult(uid, status, message) {
          var rowClass = status === 'success' ? 'success' : 'danger';
          var statusIcon = status === 'success' ? '<i class="fa fa-check"></i> Success' : '<i class="fa fa-times"></i> Failed';
          
          if (status === 'success') bulkSuccessCount++;
          else bulkFailCount++;
          
          // Get User Name (from table)
          var name = $('tr[data-user-id="' + uid + '"] td:nth-child(5)').text() || 'Unknown';
          var email = $('tr[data-user-id="' + uid + '"] td:nth-child(26)').text() || 'Unknown'; // Adjust index if needed
          
          var row = '<tr class="' + rowClass + '">' +
              '<td>' + ($('#bulk-report-table tbody tr').length + 1) + '</td>' +
              '<td>' + new Date().toLocaleTimeString() + '</td>' +
              '<td>' + name + '</td>' +
              '<td>' + email + '</td>' +
              '<td>' + statusIcon + '</td>' +
              '<td>' + (message || '-') + '</td>' +
              '</tr>';
          
          $('#bulk-report-table tbody').prepend(row);
          
          // Wait delay then next
          var delay = parseInt($('#email-delay').val()) * 1000 || 5000;
          setTimeout(processNextBulkUser, delay);
      }

      function finishBulkProcess() {
          bulkProcessing = false;
          $('#bulk-status-text').text('Completed! Success: ' + bulkSuccessCount + ', Failed: ' + bulkFailCount);
          $('#btn-cancel-bulk').prop('disabled', false).text('Close');
          
          // Update stats
          $('#bulk-summary-stats').text('Success: ' + bulkSuccessCount + ' | Failed: ' + bulkFailCount);
          
          // Enable retry for failed
          if (bulkFailCount > 0) {
             // Add logic to re-queue failed users if needed
             var btnRetry = $('<button class="btn btn-warning btn-sm" style="margin-right:10px;">Retry Failed</button>');
             btnRetry.click(function() {
                 // Select only failed rows
                 $('.user-checkbox').prop('checked', false);
                 $('#bulk-report-table tbody tr.danger').each(function() {
                     var txt = $(this).find('td:nth-child(3)').text(); // Name... hard to map back to ID efficiently without ID in table
                     // Better: store ID in row data
                 });
                 // For now simple alert
                 alert('Please manually select failed users to retry.');
             });
             $('#bulk-summary-stats').append(btnRetry);
          }
          
          // Refresh main table to show Sent status
          // location.reload(); // Optional
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
              'hide-coauth1': [7, 8],
              'hide-coauth2': [9, 10],
              'hide-coauth3': [11, 12],
              'hide-coauth4': [13, 14],
              'hide-coauth5': [15, 16],
              'hide-supervisor': [22, 23, 24],
              'hide-emails': [17, 18, 19, 20, 21, 25, 26]
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
          
          $('#category-filter, #rows-per-page').on('change', function() {
              var q = $('#users-search').val() || '';
              if (window.fetchRows) {
                  window.fetchRows(q);
              }
          });
          
          $('#clear-category').on('click', function(e) {
              e.preventDefault();
              $('#category-filter').val('');
              var q = $('#users-search').val() || '';
              if (window.fetchRows) {
                  window.fetchRows(q);
              }
          });
          
          $('#export-db').on('click', function() {
              $('#loading-indicator').html('<i class="fa fa-spinner fa-spin"></i> Preparing database export...').fadeIn(100);
          });
          
          function getVisibleColumnKeys() {
              var cols = [];
              $('.col-toggle').each(function() {
                  var key = $(this).data('target');
                  if ($(this).is(':checked')) {
                      cols.push(key);
                  }
              });
              return cols;
          }
          
          $('#export-all').on('click', function(e) {
              e.preventDefault();
              var params = { export_excel: 1, scope: 'all' };
              var visibleCols = getVisibleColumnKeys();
              if (visibleCols.length > 0) {
                  params.visible_cols = visibleCols.join(',');
              }
              var query = $.param(params);
              $('#loading-indicator').html('<i class="fa fa-spinner fa-spin"></i> Preparing Excel export...').fadeIn(100);
              window.location = 'manage-users.php?' + query;
          });

          $('#togglePasswords').change(function() {
              if($(this).is(':checked')) {
                  $('#verify-error').hide();
                  $('#verify-admin-password').val('');
                  $('#passwordVerifyModal').modal('show');
              } else {
                  $('.password-display').text('******');
              }
          });
          
          $('#export-visible').on('click', function(e) {
              e.preventDefault();
              var rows = $('#users-table tbody tr[data-user-id]');
              if (rows.length === 0) {
                  alert('No data to export. Adjust your filters first.');
                  return;
              }
              var ids = [];
              rows.each(function() {
                  var id = $(this).data('user-id');
                  if (id) {
                      ids.push(id);
                  }
              });
              if (ids.length === 0) {
                  alert('No data to export. Adjust your filters first.');
                  return;
              }
              var params = { export_excel: 1, scope: 'visible', ids: ids.join(',') };
              var visibleCols = getVisibleColumnKeys();
              if (visibleCols.length > 0) {
                  params.visible_cols = visibleCols.join(',');
              }
              var query = $.param(params);
              $('#loading-indicator').html('<i class="fa fa-spinner fa-spin"></i> Preparing Excel export...').fadeIn(100);
              window.location = 'manage-users.php?' + query;
          });

          var currentPrintUserId = null;
          $(document).on('click', '.print-user-btn', function(e) {
              e.preventDefault();
              var $btn = $(this);
              var tr = $btn.closest('tr');
              currentPrintUserId = tr.data('user-id') || $btn.data('user-id');
              if (!currentPrintUserId) {
                  return;
              }
              $('#print-user-id').val(currentPrintUserId);
              
              var roles = {
                  'main': $btn.data('main'),
                  'co1': $btn.data('co1'),
                  'co2': $btn.data('co2'),
                  'co3': $btn.data('co3'),
                  'co4': $btn.data('co4'),
                  'co5': $btn.data('co5')
              };
              
              $.each(roles, function(role, name) {
                  var $input = $('input[name="print-author"][value="' + role + '"]');
                  var $container = $input.closest('.radio');
                  var $label = $container.find('label');
                  
                  // Remove old name
                  $label.find('.author-name').remove();
                  
                  if (name && String(name).trim() !== '') {
                      $label.append(' <span class="author-name text-muted" style="font-weight:bold; color:#666;">(' + name + ')</span>');
                      $container.show();
                  } else {
                      if (role !== 'main') {
                          $container.hide();
                      } else {
                          $container.show();
                      }
                  }
              });

              $('input[name="print-author"][value="main"]').prop('checked', true);
              $('#printChoiceModal').modal('show');
          });

          $('#print-author-confirm').on('click', function() {
              var uid = $('#print-user-id').val();
              if (!uid) {
                  $('#printChoiceModal').modal('hide');
                  return;
              }
              var role = $('input[name="print-author"]:checked').val() || 'main';
              var includeSupervisor = $('#print-inc-supervisor').is(':checked') ? 1 : 0;
              var url = 'welcome.php?uid=' + encodeURIComponent(uid) + '&show_supervisor=' + includeSupervisor;
              if (role !== 'main') {
                  url += '&role=' + encodeURIComponent(role);
              }
              window.open(url, '_blank');
              $('#printChoiceModal').modal('hide');
          });
      });

      (function(){
        var $input = $('#users-search');
        var $clear = $('#clear-search');
        var $tbody = $('#users-table tbody');
        var $status = $('#search-status');
        var timer = null;
        function updateStatus(text){ $status.text(text); }
        
        // Expose fetchRows to window
        window.fetchRows = function(q, callback){
          $.ajax({
            url: 'manage-users.php',
            method: 'GET',
            data: { ajax: 1, search: q, category: $('#category-filter').val() || '', limit: $('#rows-per-page').val() || '100' },
            success: function(html){
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
            error: function(){
              updateStatus('Error fetching results');
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
        $clear.on('click', function(){
          $input.val('');
          document.cookie = 'search_query=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
          window.fetchRows('');
          $input.focus();
        });
        $input.on('keydown', function(e){
          if (e.key === 'Escape') {
            $clear.click();
          }
        });
      })();

      $('#passwordVerifyModal').on('hidden.bs.modal', function () {
          var firstPwd = $('.password-display').first().text();
          if (firstPwd === '******') {
              $('#togglePasswords').prop('checked', false);
          }
      });

      window.verifyAndShowPasswords = function() {
          var password = $('#verify-admin-password').val();
          if (password === '') {
              $('#verify-error').text('Password is required').show();
              return;
          }
          $.ajax({
              url: 'ajax_handler.php',
              type: 'POST',
              data: {
                  action: 'get_all_passwords',
                  admin_password: password
              },
              dataType: 'json',
              success: function(response) {
                  if (response.status === 'success') {
                      var passwords = response.passwords;
                      $('.password-display').each(function() {
                          var uid = $(this).data('user-id');
                          if (passwords[uid]) {
                              $(this).text(passwords[uid]);
                          }
                      });
                      $('#passwordVerifyModal').modal('hide');
                  } else {
                      $('#verify-error').text(response.message).show();
                  }
              },
              error: function() {
                  $('#verify-error').text('System error').show();
              }
          });
      };

  </script>



  <script>
// Bulk Email Logic
var bulkCancelled = false;

window.openBulkEmailModal = function() {
    var count = $('.user-checkbox:checked').length;
    $('#bulk-selection-count-modal').text(count + ' users selected');
    $('#bulk-email-modal').modal('show');
    
    // Reset UI
    $('#bulkEmailForm').show();
    $('#bulk-progress-container').hide();
    
    // Preview & Confirmation Reset
    $('#preview-confirm-container').hide();
    $('#confirm-preview').prop('checked', false);
    $('#btn-start-bulk').show().prop('disabled', true); // Require confirmation first
    $('#preview-template-name').text('No template loaded');
    
    // Clear preview area
    $('#certificate-preview').html('<div style="display: flex; height: 100%; align-items: center; justify-content: center; color: #999;">Preview will appear here...</div>');
    
    $('#btn-cancel-bulk').text('Close').prop('disabled', false);
    $('#bulk-report-table tbody').empty();
    $('#bulk-summary-stats').text('');
    
    // Load Templates
    $.ajax({
        url: 'ajax_handler.php',
        method: 'POST',
        data: { action: 'get_templates' },
        dataType: 'json',
        success: function(res) {
            var html = '<option value="">-- Select Template --</option>';
            if(res.status === 'success') {
                res.data.forEach(function(t) {
                    html += '<option value="' + t.id + '">' + t.name + '</option>';
                });
            }
            $('#certificate-template').html(html);
        }
    });
};

function addReportRow(index, name, email, status, details) {
    var color = status === 'Success' ? 'green' : 'red';
    var icon = status === 'Success' ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';
    var row = '<tr>' +
        '<td>' + index + '</td>' +
        '<td>' + new Date().toLocaleTimeString() + '</td>' +
        '<td>' + name + '</td>' +
        '<td>' + email + '</td>' +
        '<td style="color:' + color + '; font-weight:bold;">' + icon + ' ' + status + '</td>' +
        '<td>' + details + '</td>' +
        '</tr>';
    $('#bulk-report-table tbody').prepend(row);
}

$('#btn-cancel-bulk').on('click', function() {
    if ($('#btn-start-bulk').prop('disabled')) {
        // Process running
        if(confirm('Stop sending?')) {
            bulkCancelled = true;
        }
    }
});

// Checkbox listener for confirmation
$(document).on('change', '#confirm-preview', function() {
    $('#btn-start-bulk').prop('disabled', !this.checked);
});

window.loadTemplatePreview = async function() {
    var templateId = $('#certificate-template').val();
    if (!templateId) {
        alert('Please select a template first.');
        return;
    }
    
    // Get first selected user
    var firstUid = $('.user-checkbox:checked').first().val();
    if (!firstUid) {
        alert('No users selected. Please select at least one user to preview.');
        return;
    }
    
    var btn = $('#btn-load-preview');
    var originalText = btn.html();
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
    
    try {
        // Use iframe for 100% accurate preview
        const previewUrl = 'certificate-editor.php?uid=' + firstUid + '&template_id=' + templateId;
        
        // Clear previous content
        $('#certificate-preview').html('');
        
        // Create iframe
        const iframe = document.createElement('iframe');
        iframe.src = previewUrl;
        iframe.style.width = '100%';
        iframe.style.height = '100%'; 
        iframe.style.border = 'none';
        
        $('#certificate-preview').append(iframe);
        
        // Update Header
        $('#preview-template-name').text('Live Preview (User ID: ' + firstUid + ')');
        
        // Show Confirmation UI
        $('#preview-confirm-container').slideDown();
        $('#confirm-preview').prop('checked', false); 
        $('#btn-start-bulk').prop('disabled', true);
        
    } catch (e) {
        console.error(e);
        alert('Preview failed: ' + e.message);
        $('#preview-template-name').text('Preview Failed');
    } finally {
        btn.prop('disabled', false).html(originalText);
    }
};

window.startBulkProcess = async function() {
    var useTemplate = $('#use-template').is(':checked');
    var templateId = $('#certificate-template').val();
    var delay = parseInt($('#email-delay').val()) || 5;
    
    if (useTemplate && !templateId) {
        alert('Please select a certificate template');
        return;
    }
    
    if (!confirm('Are you sure you want to send emails to ' + $('.user-checkbox:checked').length + ' users?')) {
        return;
    }
    
    // UI Setup
    $('#bulkEmailForm').hide();
    $('#bulk-progress-container').show();
    $('#btn-start-bulk').prop('disabled', true);
    $('#btn-cancel-bulk').text('Cancel');
    bulkCancelled = false;
    $('#bulk-report-table tbody').empty();
    $('#bulk-summary-stats').text('');
    
    // Collect Data
    var bulkUsers = [];
    $('.user-checkbox:checked').each(function() {
        bulkUsers.push($(this).val());
    });
    
    // Create/Reset Hidden Iframe for Processing
    const iframeId = 'bulk-process-iframe';
    let iframe = document.getElementById(iframeId);
    if (iframe) iframe.remove();
    
    iframe = document.createElement('iframe');
    iframe.id = iframeId;
    iframe.style.width = '1280px';
    iframe.style.height = '800px';
    iframe.style.position = 'fixed';
    iframe.style.top = '0';
    iframe.style.left = '0';
    iframe.style.zIndex = '-9999';
    iframe.style.opacity = '0';
    document.body.appendChild(iframe);
    
    let processed = 0;
    let successCount = 0;
    let failCount = 0;
    let failedUids = [];
    
    for (const uid of bulkUsers) {
        if (bulkCancelled) break;
        
        $('#bulk-status-text').text('Processing user ' + (processed + 1) + ' of ' + bulkUsers.length + ' (ID: ' + uid + ')...');
        
        try {
            const result = await new Promise((resolve, reject) => {
                const handler = (event) => {
                    if (event.data.type === 'CERT_PROCESSED' && event.data.uid == uid) {
                        window.removeEventListener('message', handler);
                        resolve(event.data);
                    }
                };
                window.addEventListener('message', handler);
                
                // Timeout (45s)
                const timer = setTimeout(() => {
                    window.removeEventListener('message', handler);
                    reject(new Error('Timeout waiting for editor response'));
                }, 45000);
                
                // Trigger Iframe Load
                iframe.src = 'certificate-editor.php?uid=' + uid + '&template_id=' + templateId + '&autogen=true';
            });
            
            if (result.status === 'success') {
                addReportRow(processed + 1, 'User ' + uid, 'Sent', 'Success', 'Sent via Editor');
                successCount++;
            } else {
                throw new Error(result.message || 'Error from editor');
            }
            
        } catch (e) {
            console.error(e);
            addReportRow(processed + 1, 'User ' + uid, 'N/A', 'Failed', e.message);
            failCount++;
            failedUids.push(uid);
        }
        
        processed++;
        const pct = Math.round((processed / bulkUsers.length) * 100);
        $('#bulk-progress-bar').css('width', pct + '%').text(pct + '%');
        $('#bulk-summary-stats').text('Processed: ' + processed + '/' + bulkUsers.length + ' | Success: ' + successCount + ' | Failed: ' + failCount);
        
        if (processed < bulkUsers.length && !bulkCancelled) {
             await new Promise(r => setTimeout(r, delay * 1000));
        }
    }
    
    // Cleanup
    if (iframe) iframe.remove();
    
    $('#bulk-status-text').text('Completed. Success: ' + successCount + ', Failed: ' + failCount);
    $('#btn-start-bulk').hide().prop('disabled', false); 
    $('#btn-cancel-bulk').text('Close').prop('disabled', false);
    
    if (failCount > 0) {
        const retryBtn = $('<button class="btn btn-warning btn-xs" style="margin-left:10px">Retry Failed (' + failCount + ')</button>');
        retryBtn.click(function() {
            $('.user-checkbox').prop('checked', false);
            failedUids.forEach(uid => {
                $('.user-checkbox[value="' + uid + '"]').prop('checked', true);
            });
            if(typeof updateBulkButton === 'function') updateBulkButton();
            else $('#bulk-selection-count-modal').text(failedUids.length + ' users selected');
            
            $('#bulk-progress-container').hide();
            $('#bulkEmailForm').show();
            $('#btn-start-bulk').show();
            
            alert('Failed users selected (' + failedUids.length + '). You can now click "Send Certificates" to retry.');
            $(this).remove();
        });
        $('#bulk-summary-stats').append(retryBtn);
    }
    
    alert('Bulk sending completed!');
};

// Helper for select-all checkboxes updating the button
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

// Toggle Select All
window.toggleSelectAll = function(source) {
    $('.user-checkbox').prop('checked', source.checked);
    updateBulkButton();
}
</script>
<script>
    $(document).ready(function() {
        $('#rows-per-page').on('change', function() {
            var limit = $(this).val();
            var url = window.location.href;
            if (url.indexOf('?') > -1) {
                if (url.indexOf('limit=') > -1) {
                    url = url.replace(/limit=[^&]+/, 'limit=' + limit);
                } else {
                    url = url + '&limit=' + limit;
                }
            } else {
                url = url + '?limit=' + limit;
            }
            window.location.href = url;
        });
    });
</script>
</body>

</html>

<?php } ?>

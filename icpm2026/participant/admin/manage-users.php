<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
include 'dbconnection.php';
mysqli_set_charset($con, 'utf8mb4');
// checking session is valid for not
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

// AJAX Handler for Table Updates
if (isset($_GET['ajax'])) {
    $q = isset($_GET['search']) ? $_GET['search'] : '';
    $cat = isset($_GET['category']) ? $_GET['category'] : '';
    $limitArg = isset($_GET['limit']) ? $_GET['limit'] : 'ALL';
    $limit = ($limitArg === 'ALL') ? 1000000 : intval($limitArg);
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    $pattern = '%' . $q . '%';

    // Base WHERE
    $whereSQL = "1=1";
    $params = [];
    $types = "";

    // Search
    if ($q !== '') {
        $whereSQL .= " AND (
            CAST(id AS CHAR) LIKE ? OR
            fname LIKE ? OR
            lname LIKE ? OR
            email LIKE ? OR
            profession LIKE ? OR
            organization LIKE ? OR
            category LIKE ?
        )";
        for($i=0; $i<7; $i++) { $params[] = $pattern; $types .= "s"; }
    }

    // Category
    if ($cat !== '') {
        $whereSQL .= " AND category = ?";
        $params[] = $cat;
        $types .= "s";
    }

    // Certificate Status
    $certStatus = isset($_GET['certificate_status']) ? $_GET['certificate_status'] : '';
    if ($certStatus !== '') {
        $whereSQL .= " AND certificate_sent = ?";
        $params[] = $certStatus;
        $types .= "i";
    }
    
    // Date Range
    if (!empty($_GET['start_date'])) {
        $whereSQL .= " AND DATE(posting_date) >= ?";
        $params[] = $_GET['start_date'];
        $types .= "s";
    }
    if (!empty($_GET['end_date'])) {
        $whereSQL .= " AND DATE(posting_date) <= ?";
        $params[] = $_GET['end_date'];
        $types .= "s";
    }

    // Count
    $countSql = "SELECT COUNT(*) as cnt FROM users WHERE " . $whereSQL;
    $stmt = mysqli_prepare($con, $countSql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $countRes = mysqli_stmt_get_result($stmt);
    $totalRecords = mysqli_fetch_assoc($countRes)['cnt'];
    $totalPages = ceil($totalRecords / $limit);

    // Fetch
    $sql = "SELECT * FROM users WHERE " . $whereSQL . " ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $stmt = mysqli_prepare($con, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Generate HTML
    $html = '';
    $cnt = $offset + 1;
    $wrapInit = function($val, $short=false) {
        $txt = (string)$val;
        $cls = $short ? 'cell-content short' : 'cell-content';
        return '<div class="' . $cls . '" title="' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</div>';
    };

    while ($row = mysqli_fetch_assoc($result)) {
        $certStatus = isset($row['certificate_sent']) ? $row['certificate_sent'] : 0;
        $html .= "<tr>";
        $html .= "<td><input type='checkbox' class='user-checkbox' value='" . $row['id'] . "' aria-label='Select user " . $row['id'] . "' onchange='updateBulkButton()'></td>";
        $html .= "<td>" . $wrapInit($cnt++, true) . "</td>";
        $html .= "<td>" . $wrapInit($row['id']) . "</td>";
        $html .= "<td>" . $wrapInit($row['fname']) . "</td>";
        $html .= "<td>" . $wrapInit($row['lname']) . "</td>";
        $html .= "<td>" . $wrapInit($row['email']) . "</td>";
        $html .= "<td>" . $wrapInit($row['profession']) . "</td>";
        $html .= "<td>" . $wrapInit($row['organization']) . "</td>";
        $html .= "<td>" . $wrapInit($row['category']) . "</td>";
        $html .= "<td>" . $wrapInit($row['contactno']) . "</td>";
        $html .= "<td>" . $wrapInit($row['password']) . "</td>";
        $html .= "<td>" . $wrapInit(date("Y-m-d H:i:s", strtotime($row['posting_date']))) . "</td>";
        $html .= "<td><select class='form-control' onchange='updateCertificateStatus(" . $row['id'] . ", this.value)' style='width: 120px; font-size: 12px; height: 30px; padding: 2px; " . ($certStatus == 1 ? "border-color: #5cb85c; border-width: 2px;" : "") . "'><option value='0' " . ($certStatus == 0 ? 'selected' : '') . ">Not Sent</option><option value='1' " . ($certStatus == 1 ? 'selected' : '') . ">Sent</option></select></td>";
        $html .= "<td class='actions-cell'>";
        $html .= '<a href="certificate-editor.php?uid=' . $row['id'] . '" title="Design Certificate" class="btn btn-warning btn-xs"><i class="fa fa-certificate"></i></a> ';
        $html .= '<a href="welcome.php?uid=' . $row['id'] . '" class="btn btn-primary btn-xs"><i class="fa fa-print"></i></a> ';
        
        // WhatsApp Button
        $secret_salt = 'ICPM2026_Secure_Salt';
        $hash = md5($row['id'] . $secret_salt);
        $certLink = "https://reg-sys.com/icpm2026/download-certificate.php?id=" . $row['id'] . "&hash=" . $hash;
        $cleanPhone = preg_replace('/[^0-9]/', '', $row['contactno']);
        $waMsg = "Dear " . $row['fname'] . ", please download your certificate here: " . $certLink;
        $waUrl = "https://wa.me/" . $cleanPhone . "?text=" . urlencode($waMsg);
        
        $html .= '<a href="' . $waUrl . '" target="_blank" title="Send via WhatsApp" class="btn btn-success btn-xs"><i class="fa fa-comments"></i></a> ';
        
        $html .= '<a href="update-profile.php?uid=' . $row['id'] . '" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a> ';
        $html .= '<a href="manage-users.php?id=' . $row['id'] . '" class="btn btn-danger btn-xs" onClick="return confirm(\'Do you really want to delete\');"><i class="fa fa-trash-o "></i></a>';
        $html .= "</td>";
        $html .= "</tr>";
    }

    $json = json_encode([
        'html' => $html,
        'totalRecords' => $totalRecords,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);

    if ($json === false) {
        $json = json_encode(['error' => 'JSON Encode Error: ' . json_last_error_msg()]);
    }
    echo $json;
    exit();
}

// Fetch Categories for Dropdown
$catQuery = "SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category != '' ORDER BY category";
$catResult = mysqli_query($con, $catQuery);
$categories = [];
while($cRow = mysqli_fetch_assoc($catResult)) {
    $categories[] = $cRow['category'];
}

// Fetch Certificate Status Counts
$sentCountQuery = "SELECT COUNT(*) as cnt FROM users WHERE certificate_sent = 1";
$notSentCountQuery = "SELECT COUNT(*) as cnt FROM users WHERE certificate_sent = 0";
$sentRes = mysqli_query($con, $sentCountQuery);
$notSentRes = mysqli_query($con, $notSentCountQuery);
$sentCount = ($sentRes) ? mysqli_fetch_assoc($sentRes)['cnt'] : 0;
$notSentCount = ($notSentRes) ? mysqli_fetch_assoc($notSentRes)['cnt'] : 0;

// Export DB Logic
if (isset($_GET['export_db']) && $_GET['export_db'] == 1) {
    $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
    $filename = "users_export_" . date('Y-m-d_H-i-s');
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    // Build Query
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if (!empty($startDate)) {
        $sql .= " AND DATE(posting_date) >= ?";
        $types .= 's';
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $sql .= " AND DATE(posting_date) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($params)) {
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($con, $sql);
    }

    if ($format == 'pdf') {
        // Try to include TCPDF
        $tcpdfPath = '../../../wp-content/plugins/user-registration-pro/vendor/tecnickcom/tcpdf/tcpdf.php';
        if (file_exists($tcpdfPath)) {
            require_once($tcpdfPath);
            $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle('User List Export');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(10, 10, 10);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 9);

            $html = '<h2 style="text-align:center;">User List' . ($category ? ' - ' . htmlspecialchars($category) : '') . '</h2>';
            $html .= '<table border="1" cellpadding="4">
            <thead>
                <tr style="background-color:#f0f0f0; font-weight:bold;">
                    <th width="5%">Sno</th>
                    <th width="15%">Name</th>
                    <th width="20%">Email</th>
                    <th width="15%">Profession</th>
                    <th width="15%">Organization</th>
                    <th width="10%">Category</th>
                    <th width="15%">Reg. Date</th>
                </tr>
            </thead>
            <tbody>';

            $cnt = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $html .= '<tr>
                    <td>'.$cnt.'</td>
                    <td>'.htmlspecialchars($row['fname'] . ' ' . $row['lname']).'</td>
                    <td>'.htmlspecialchars($row['email']).'</td>
                    <td>'.htmlspecialchars($row['profession']).'</td>
                    <td>'.htmlspecialchars($row['organization']).'</td>
                    <td>'.htmlspecialchars($row['category']).'</td>
                    <td>'.htmlspecialchars($row['posting_date']).'</td>
                </tr>';
                $cnt++;
            }
            $html .= '</tbody></table>';

            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($filename . '.pdf', 'D');
            exit;
        } else {
            echo "PDF Library not found.";
            exit;
        }
    } elseif ($format == 'excel') {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename.xls\"");
        echo "Sno\tRef Number\tFirst Name\tLast Name\tEmail Id\tProfession\tOrganization\tCategory\tContact no.\tReg. Date\n";
        $cnt = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            // Clean data for tab-delimited
            $line = array(
                $cnt,
                $row['id'],
                $row['fname'],
                $row['lname'],
                $row['email'],
                $row['profession'],
                $row['organization'],
                $row['category'],
                $row['contactno'],
                $row['created_at']
            );
            array_walk($line, function(&$str){
                $str = preg_replace("/\t/", "\\t", preg_replace("/\r?\n/", "\\n", $str));
            });
            echo implode("\t", $line) . "\n";
            $cnt++;
        }
        exit;
    } else {
        // CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, array('Sno.', 'Ref Number', 'First Name', 'Last Name', 'Email Id', 'Profession', 'Organization', 'Category', 'Contact no.', 'Password', 'Reg. Date'));
        $cnt = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($fp, array(
                $cnt, $row['id'], $row['fname'], $row['lname'], $row['email'],
                $row['profession'], $row['organization'], $row['category'],
                $row['contactno'], $row['password'], $row['posting_date']
            ));
            $cnt++;
        }
        fclose($fp);
        exit;
    }
}


// for deleting user
if(isset($_GET['id']))
{
$adminid=$_GET['id'];
$msg=mysqli_query($con,"delete from users where id='$adminid'");
if($msg)
{
echo "<script>alert('Data deleted');</script>";
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

    <title>Admin | Manage Users</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
      .search-highlight { background-color: #ffea00; }
      .search-toolbar { margin-bottom: 15px; }
      
      /* Table Display Enhancements */
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
      #users-table th:nth-child(3), #users-table td:nth-child(3) {
          min-width: 150px;
          width: auto;
      }
      
      /* Reg. Date Column - Ensure timestamp fits */
      #users-table th:nth-child(12), #users-table td:nth-child(12) {
          min-width: 160px;
          width: auto;
      }
      #users-table td:nth-child(3) .cell-content {
          white-space: nowrap !important;
          overflow: visible !important;
          text-overflow: clip !important;
          max-width: none !important;
          display: block !important;
          height: auto !important;
          max-height: none !important;
          -webkit-line-clamp: unset !important;
      }

      /* Hide specific columns by default if needed (none for now) */
      /* Example: #users-table th:nth-child(10), #users-table td:nth-child(10) { display: none; } */
      
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
          cursor: help;
      }

      .cell-content.short {
          min-width: 40px;
          max-width: 80px;
          -webkit-line-clamp: 1;
          max-height: 1.4em;
      }
      
      .actions-cell {
          min-width: 140px;
          white-space: nowrap;
      }
      
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
      
      /* Certificate Generation Styles */
      .cert-container {
          position: relative;
          background: white;
          overflow: hidden;
      }
      .cert-border {
          position: absolute;
          top: 20px;
          left: 20px;
          right: 20px;
          bottom: 20px;
          border: 5px solid #b22222;
          pointer-events: none;
          z-index: 0;
      }
      .cert-element {
          position: absolute;
          z-index: 10;
      }
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
                  <li class="sub-menu">
                      <a href="whatsapp-dashboard.php" >
                          <i class="fa fa-comments"></i>
                          <span>WhatsApp Bulk</span>
                      </a>
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
                      <div class="row">
                          <div class="col-md-4">
                              <div class="input-group">
                                <span class="input-group-addon" id="users-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span>
                                <input type="text" id="users-search" class="form-control" placeholder="Search users..." aria-label="Search users" aria-controls="users-table" aria-describedby="users-search-icon">
                                <span class="input-group-btn">
                                  <button class="btn btn-default" type="button" id="clear-search" aria-label="Clear search">Clear</button>
                                </span>
                              </div>
                          </div>
                          <div class="col-md-2">
                              <select id="category-filter" class="form-control" aria-label="Filter by Category">
                                  <option value="">All Categories</option>
                                  <?php foreach($categories as $cat): ?>
                                      <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                          <div class="col-md-2">
                              <select id="certificate-status-filter" class="form-control" aria-label="Filter by Certificate Status">
                                  <option value="">All Statuses</option>
                                  <option value="1">Sent (<?php echo $sentCount; ?>)</option>
                                  <option value="0">Not Sent (<?php echo $notSentCount; ?>)</option>
                              </select>
                          </div>
                          <div class="col-md-4">
                              <div class="input-group">
                                  <input type="date" id="date-start" class="form-control" placeholder="Start Date" title="Start Date">
                                  <span class="input-group-addon" style="border-left: 0; border-right: 0;">to</span>
                                  <input type="date" id="date-end" class="form-control" placeholder="End Date" title="End Date">
                              </div>
                          </div>
                          <div class="col-md-12 text-right" style="margin-top: 10px;">
                              <div class="btn-group">
                                <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                  Export Report <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                  <li><a href="#" class="export-btn" data-format="csv">Export as CSV</a></li>
                                  <li><a href="#" class="export-btn" data-format="excel">Export as Excel</a></li>
                                  <li><a href="#" class="export-btn" data-format="pdf">Export as PDF</a></li>
                                </ul>
                              </div>
                              <button class="btn btn-primary" id="btn-bulk-upload" onclick="openBulkUploadModal()" style="margin-left: 5px;"><i class="fa fa-upload"></i> Bulk Upload</button>
                              <a href="force_category_update.php" class="btn btn-warning" onclick="return confirm('This will set ALL users (current and future) to \'Participant\' category. Are you sure?')" style="margin-left: 5px;"><i class="fa fa-wrench"></i> Fix Categories</a>
                              <button class="btn btn-warning" id="btn-bulk-email" onclick="openBulkEmailModal()" disabled style="margin-left: 5px;"><i class="fa fa-envelope"></i> Send Certificates</button>
                              <button class="btn btn-danger" id="btn-bulk-delete" onclick="openBulkDeleteModal()" disabled style="margin-left: 5px;"><i class="fa fa-trash-o"></i> Delete Selected</button>
                          </div>
                      </div>
                      
                      <!-- Bulk Delete Modal -->
                      <div id="bulk-delete-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
                          <div style="background:white; padding:20px; border-radius:8px; width:400px; text-align: left;">
                              <h4 class="text-danger"><i class="fa fa-exclamation-triangle"></i> Confirm Bulk Deletion</h4>
                              <div class="alert alert-warning">
                                  You are about to permanently delete <strong id="delete-count">0</strong> users. This action cannot be undone.
                              </div>
                              
                              <div class="form-group">
                                  <label>Enter Admin Password to Confirm</label>
                                  <input type="password" id="delete-password" class="form-control" placeholder="Admin Password">
                              </div>
                              
                              <div id="delete-error" class="text-danger" style="display:none; margin-bottom:10px;"></div>
                              
                              <div style="display: flex; gap: 10px; margin-top: 20px;">
                                  <button class="btn btn-danger btn-block" onclick="confirmBulkDelete()" id="btn-confirm-delete">Delete Users</button>
                                  <button class="btn btn-default btn-block" onclick="closeBulkDeleteModal()" id="btn-cancel-delete">Cancel</button>
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

                                                  <div class="checkbox">
                                                      <label>
                                                          <input type="checkbox" id="use-override-email"> Send to specific email (Override User Email)
                                                      </label>
                                                  </div>
                                                  
                                                  <div class="form-group" id="override-email-group" style="display:none;">
                                                      <label>Override Email Address</label>
                                                      <input type="email" id="override-email" class="form-control" placeholder="Enter email address">
                                                      <p class="help-block text-warning">Warning: All selected certificates will be sent to this email.</p>
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
                                                   <!-- Retry button could go here -->
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


                      <div id="search-status" class="sr-only" aria-live="polite"></div>
                    </div>
                  </div>

                  <div class="col-md-12">
                      <div class="content-panel">
                          <h4><i class="fa fa-angle-right"></i> All User Details </h4>
                          <hr>
                          
                          <div class="column-visibility-controls">
                              <div style="margin-bottom: 10px;">
                                  <strong>Show Columns: </strong>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-work"> Work Info</label>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-contact"> Contact Info</label>
                                  <label class="checkbox-inline"><input type="checkbox" class="col-toggle" data-target="hide-sensitive"> Sensitive Info</label>
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
                                  <th width="30"><input type="checkbox" id="select-all" aria-label="Select all users"></th>
                                  <th>Sno.</th>
                                  <th> Ref Number</th>
                                  <th class="hidden-phone">First Name</th>
                                  <th> Last Name</th>
                                  <th> Email Id</th>
                                  <th> Profession</th>
                                  <th> Organization</th>
                                  <th> Category</th>
                                  <th>Contact no.</th>
                                  <th>Password</th>
                                  <th>Reg. Date</th>
                                  <th>Cert. Status</th>
                                  <th>Action</th>
                              </tr>
                              </thead>
                              <tbody>
                              <?php 
                              $sql = "select * from users";
                              if(isset($_GET['category']) && !empty($_GET['category'])) {
                                  $cat = mysqli_real_escape_string($con, $_GET['category']);
                                  $sql .= " WHERE category='$cat'";
                              }
                              
                              // Dynamic Limit
                              $limitArg = isset($_GET['limit']) ? $_GET['limit'] : 'ALL';
                              $limit = ($limitArg === 'ALL') ? 1000000 : intval($limitArg);
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
                                  echo "<td><input type='checkbox' class='user-checkbox' value='" . $row['id'] . "' aria-label='Select user " . $row['id'] . "'></td>";
                                  echo "<td>" . $wrapInit($cnt, true) . "</td>";
                                  echo "<td>" . $wrapInit($row['id']) . "</td>";
                                  echo "<td>" . $wrapInit($row['fname']) . "</td>";
                                  echo "<td>" . $wrapInit($row['lname']) . "</td>";
                                  echo "<td>" . $wrapInit($row['email']) . "</td>";
                                  echo "<td>" . $wrapInit($row['profession']) . "</td>";
                                  echo "<td>" . $wrapInit($row['organization']) . "</td>";
                                  echo "<td>" . $wrapInit($row['category']) . "</td>";
                                  echo "<td>" . $wrapInit($row['contactno']) . "</td>";
                                  echo "<td>" . $wrapInit($row['password']) . "</td>";
                                  echo "<td>" . $wrapInit(date("Y-m-d H:i:s", strtotime($row['posting_date']))) . "</td>";
                                  echo "<td><select class='form-control' onchange='updateCertificateStatus(" . $row['id'] . ", this.value)' style='width: 120px; font-size: 12px; height: 30px; padding: 2px; " . ($row['certificate_sent'] == 1 ? "border-color: #5cb85c; border-width: 2px;" : "") . "'><option value='0' " . (($row['certificate_sent'] == 0) ? 'selected' : '') . ">Not Sent</option><option value='1' " . (($row['certificate_sent'] == 1) ? 'selected' : '') . ">Sent</option></select></td>";
                                  echo "<td class='actions-cell'>";
                                  echo '<a href="certificate-editor.php?uid=' . $row['id'] . '" title="Design Certificate" class="btn btn-warning btn-xs" target="_blank"><i class="fa fa-certificate"></i></a> ';
                                  echo '<a href="welcome.php?uid=' . $row['id'] . '" class="btn btn-primary btn-xs" target="_blank"><i class="fa fa-print"></i></a> ';
                                  
                                  // WhatsApp Button
                                  $secret_salt = 'ICPM2026_Secure_Salt';
                                  $hash = md5($row['id'] . $secret_salt);
                                  $certLink = "https://reg-sys.com/icpm2026/participant/download-certificate.php?id=" . $row['id'] . "&hash=" . $hash;
                                  $cleanPhone = preg_replace('/[^0-9]/', '', $row['contactno']);
                                  $waMsg = "Dear " . $row['fname'] . ", please download your certificate here: " . $certLink;
                                  $waUrl = "https://wa.me/" . $cleanPhone . "?text=" . urlencode($waMsg);
                                  echo '<a href="' . $waUrl . '" target="_blank" title="Send via WhatsApp" class="btn btn-success btn-xs"><i class="fa fa-comments"></i></a> ';
                                  
                                  echo '<a href="update-profile.php?uid=' . $row['id'] . '" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a> ';
                                  echo '<a href="manage-users.php?id=' . $row['id'] . '" class="btn btn-danger btn-xs" onClick="return confirm(\'Do you really want to delete\');"><i class="fa fa-trash-o "></i></a>';
                                  echo "</td>";
                                  echo "</tr>";
                                  $cnt++;
                              }?>
                              </tbody>
                          </table>
                          </div>
                          <div class="row" style="margin: 20px 0; display: flex; align-items: center;">
                              <div class="col-md-4"></div>
                              <div class="col-md-4 text-center">
                                  <div id="pagination-controls"></div>
                              </div>
                              <div class="col-md-4 text-right">
                                  <label for="rows-per-page" style="font-weight:normal; margin-right:5px;">Rows per page:</label>
                                  <select id="rows-per-page" class="form-control" style="display:inline-block; width:auto; height:30px; padding:2px; font-size:12px;">
                                      <option value="25">25</option>
                                      <option value="50">50</option>
                                      <option value="100">100</option>
                                      <option value="250">250</option>
                                      <option value="500">500</option>
                                      <option value="1000">1000</option>
                                      <option value="ALL" selected>ALL</option>
                                  </select>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
		</section>
      </section>
  </section>

<!-- Bulk Upload Modal -->
<div id="bulk-upload-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:5px; width:700px; max-height:90vh; overflow-y:auto; display:flex; flex-direction:column; box-shadow:0 0 20px rgba(0,0,0,0.3);">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Bulk User Upload & Registration</h3>
        
        <div style="background:#f9f9f9; padding:10px; border-radius:4px; margin-bottom:15px; border:1px solid #eee;">
            <p style="margin:0 0 5px 0;"><strong>Step 1:</strong> 
                <a href="bulk-upload-users.php?action=template&amp;format=csv" target="_blank" style="text-decoration:underline; color:#337ab7; margin-right:10px;">Download CSV Template</a>
                <a href="bulk-upload-users.php?action=template&amp;format=xlsx" target="_blank" style="text-decoration:underline; color:#337ab7;">Download Excel Template (.xlsx)</a>
            </p>
            <p style="margin:0; font-size:0.9em; color:#666;">Ensure your CSV or Excel file follows the template format. Email is required.</p>
        </div>
        
        <div style="margin-bottom:15px;">
            <label>Select CSV or Excel File (Max 50MB):</label>
            <input type="file" id="bulk-upload-file" accept=".csv,.xlsx" class="form-control">
        </div>
        
        <div id="bulk-preview-container" style="display:none; margin-bottom:15px; border:1px solid #ddd; padding:10px; border-radius:4px;">
            <div style="font-weight:bold; margin-bottom:8px;">Preview (first 5 records)</div>
            <div style="max-height:200px; overflow-y:auto;">
                <table class="table table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Profession</th>
                            <th>Organization</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="bulk-preview-tbody"></tbody>
                </table>
            </div>
        </div>
        
        <div id="bulk-upload-progress-container" style="display:none; margin-bottom:15px; border:1px solid #ddd; padding:15px; border-radius:4px;">
            <div class="progress" style="height:25px; margin-bottom:10px; background:#ddd;">
                <div id="bulk-upload-progress-bar" class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" style="width: 0%; line-height:25px; font-weight:bold;">0%</div>
            </div>
            
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div id="bulk-upload-status-text" style="font-weight:bold; font-size:1.1em;">Initializing...</div>
                    <div id="bulk-upload-timer" style="color:#666; font-size:0.9em; margin-top:2px;"></div>
                </div>
                <div style="text-align:right;">
                    <div style="color:green; font-weight:bold;">Success: <span id="bulk-stats-success">0</span></div>
                    <div style="color:orange; font-weight:bold;">Email Exists: <span id="bulk-stats-email-exists">0</span></div>
                    <div style="color:red; font-weight:bold;">Failed: <span id="bulk-stats-failed">0</span></div>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom:5px; font-weight:bold;">Process Log:</div>
        <div id="bulk-upload-log" style="height:250px; overflow-y:auto; border:1px solid #ccc; padding:10px; background:#fff; margin-bottom:15px; font-family:monospace; font-size:12px;"></div>
        
        <div style="display:flex; gap:10px; justify-content:flex-end; border-top:1px solid #eee; padding-top:15px;">
            <button id="btn-preview-upload" class="btn btn-info" onclick="previewBulkUpload()"><i class="fa fa-eye"></i> Preview Data</button>
            <button id="btn-start-upload" class="btn btn-success" style="display:none;" onclick="startBulkUpload()"><i class="fa fa-play"></i> Confirm Import</button>
            <button id="btn-pause-upload" class="btn btn-warning" style="display:none;" onclick="pauseBulkUpload()"><i class="fa fa-pause"></i> Pause</button>
            <button id="btn-resume-upload" class="btn btn-info" style="display:none;" onclick="resumeBulkUpload()"><i class="fa fa-play"></i> Resume</button>
            <button id="btn-download-report" class="btn btn-primary" style="display:none;" onclick="downloadBulkReport()"><i class="fa fa-download"></i> Report</button>
            <button id="btn-close-upload" class="btn btn-default" onclick="closeBulkUploadModal()">Close</button>
        </div>
    </div>
</div>

    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="assets/js/jquery.scrollTo.min.js"></script>
    <script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
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
                  select.value = status == 1 ? 0 : 1;
              }
          });
      }

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
    
    // Override Email Logic
    var overrideEmail = '';
    if ($('#use-override-email').is(':checked')) {
        overrideEmail = $('#override-email').val();
        if (!overrideEmail) {
            alert('Please enter an override email address.');
            return;
        }
        // Strict confirmation for override
        if (!confirm('WARNING: You are about to send ALL certificates to ' + overrideEmail + '. Are you sure?')) {
            return;
        }
    }
    
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
                var src = 'certificate-editor.php?uid=' + uid + '&template_id=' + templateId + '&autogen=true';
                if (overrideEmail) {
                    src += '&override_email=' + encodeURIComponent(overrideEmail);
                }
                iframe.src = src;
            });
            
            if (result.status === 'success') {
                addReportRow(processed + 1, 'User ' + uid, 'Sent', 'Success', 'Sent via Editor' + (overrideEmail ? ' (Override)' : ''));
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

// Toggle Override Email Input
$(document).on('change', '#use-override-email', function() {
    if($(this).is(':checked')) {
        $('#override-email-group').slideDown();
    } else {
        $('#override-email-group').slideUp();
    }
});
  </script>
  
  <!-- PDF Libraries for Bulk Generation -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>window.jsPDF = window.jspdf.jsPDF;</script>
  <script>
      $(document).ready(function() {
          if ($.fn.customSelect) {
              $('select.styled').customSelect();
          }

          function setCookie(name, value, days) {
              var expires = "";
              if (days) {
                  var date = new Date();
                  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                  expires = "; expires=" + date.toUTCString();
              }
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

          // Column Visibility Logic - Adapted for icpm2026 columns
          var columnMap = {
              'hide-work': [6, 7, 8],
              'hide-contact': [5, 9],
              'hide-sensitive': [10]
          };

          window.applyColumnVisibility = function() {
              $('.col-toggle').each(function() {
                  var target = $(this).data('target');
                  var isChecked = $(this).is(':checked');
                  var indices = columnMap[target];
                  
                  if (indices) {
                      $.each(indices, function(i, index) {
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

          // Initialize State
           $('.col-toggle').each(function() {
               var target = $(this).data('target');
               var isChecked = getCookie('col_vis_' + target);
               // Default: Show all (checked = true)
               // Note: poster26 default was false (hidden). Here we probably want to show by default?
               // But wait, the checkbox label is "Show Columns". So checked = show.
               // In poster26, "Co-authors" were hidden by default.
               // Let's set default to true (checked) for basic info.
               if (isChecked === null) isChecked = 'true';
               $(this).prop('checked', isChecked === 'true');
           });

          applyColumnVisibility();

          $('.col-toggle').change(function() {
              var $this = $(this);
              var target = $this.data('target');
              var isChecked = $this.is(':checked');
              
              $('#loading-indicator').fadeIn(100);
              
              setTimeout(function() {
                  try {
                      setCookie('col_vis_' + target, isChecked, 7);
                  } catch(e) {
                      console.error("Cookie storage failed:", e);
                  }
                  applyColumnVisibility();
                  setTimeout(function() {
                      $('#loading-indicator').fadeOut(200);
                  }, 300);
              }, 50);
          });
          
          $('#reset-settings').click(function(e) {
              e.preventDefault();
              if(confirm('Are you sure you want to reset all column visibility settings?')) {
                  $('.col-toggle').each(function() {
                      var target = $(this).data('target');
                      eraseCookie('col_vis_' + target);
                      $(this).prop('checked', true); // Reset to show all
                  });
                  applyColumnVisibility();
                  alert('Settings have been reset.');
              }
          });
          
          $('#refresh-table').click(function(e) {
              e.preventDefault();
              var $btn = $(this);
              $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Refreshing...');
              $('#loading-indicator').fadeIn(100);
              
              if (typeof window.fetchRows === 'function') {
                   var q = $('#users-search').val() || '';
                   window.fetchRows(q, function() {
                       $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Refresh Table');
                       $('#loading-indicator').fadeOut(200);
                   });
              } else {
                  location.reload();
              }
          });

          // Checkbox Logic
          function updateBulkButton() {
              var count = $('.user-checkbox:checked').length;
              $('#btn-bulk-email').prop('disabled', count === 0);
              $('#btn-bulk-delete').prop('disabled', count === 0);
              $('#bulk-selection-count').text(count + ' users selected');
          }

          $('#select-all').on('click', function() {
              var isChecked = $(this).is(':checked');
              $('.user-checkbox').prop('checked', isChecked);
              updateBulkButton();
          });

          $('#users-table').on('change', '.user-checkbox', function() {
              var allChecked = $('.user-checkbox').length > 0 && $('.user-checkbox').length === $('.user-checkbox:checked').length;
              $('#select-all').prop('checked', allChecked);
              updateBulkButton();
          });
          
          // Certificate Status Update - Handled by inline onchange
          
          // Initial check
          updateBulkButton();

          window.openBulkDeleteModal = function() {
              var count = $('.user-checkbox:checked').length;
              $('#delete-count').text(count);
              $('#delete-password').val('');
              $('#delete-error').hide();
              $('#bulk-delete-modal').css('display', 'flex');
          };

          window.closeBulkDeleteModal = function() {
              $('#bulk-delete-modal').hide();
          };

          window.confirmBulkDelete = function() {
              var password = $('#delete-password').val();
              if (!password) {
                  $('#delete-error').text('Password is required').show();
                  return;
              }
              
              var ids = [];
              $('.user-checkbox:checked').each(function() {
                  ids.push($(this).val());
              });
              
              var $btn = $('#btn-confirm-delete');
              $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Deleting...');
              
              $.ajax({
                  url: 'ajax_handler.php',
                  method: 'POST',
                  data: {
                      action: 'delete_users',
                      ids: ids,
                      password: password,
                      csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                  },
                  dataType: 'json',
                  success: function(response) {
                      if (response.status === 'success') {
                          alert('Users deleted successfully');
                          closeBulkDeleteModal();
                          window.fetchRows($('#users-search').val());
                      } else {
                          $('#delete-error').text(response.message).show();
                      }
                  },
                  error: function() {
                      $('#delete-error').text('Network error occurred').show();
                  },
                  complete: function() {
                      $btn.prop('disabled', false).text('Delete Users');
                  }
              });
          };

          // Bulk Upload Logic
          let uploadState = {
              filename: '',
              totalRecords: 0,
              logId: 0,
              processed: 0,
              filePos: 0,
              successCount: 0,
              failCount: 0,
              emailExistCount: 0,
              allSuccess: [],
              allErrors: [],
              allEmailExists: [],
              paused: false,
              startTime: 0,
              timerInterval: null
          };

          window.openBulkUploadModal = function() {
              $('#bulk-upload-file').val('');
              $('#bulk-upload-progress-container').hide();
              $('#bulk-preview-container').hide();
              $('#bulk-upload-log').html('');
              $('#bulk-upload-status-text').text('');
              $('#bulk-stats-success').text('0');
              $('#bulk-stats-failed').text('0');
              $('#bulk-stats-email-exists').text('0');
              
              $('#btn-preview-upload').show().prop('disabled', false);
              $('#btn-start-upload').hide().prop('disabled', false);
              
              $('#btn-pause-upload').hide();
              $('#btn-resume-upload').hide();
              $('#btn-download-report').hide();
              $('#bulk-upload-modal').css('display', 'flex');
              
              // Reset State
              uploadState = {
                  filename: '',
                  totalRecords: 0,
                  logId: 0,
                  processed: 0,
                  filePos: 0,
                  successCount: 0,
                  failCount: 0,
                  emailExistCount: 0,
                  allSuccess: [],
                  allErrors: [],
                  allEmailExists: [],
                  paused: false,
                  startTime: 0,
                  timerInterval: null
              };
          };

          window.closeBulkUploadModal = function() {
              if ($('#bulk-upload-progress-container').is(':visible') && $('#btn-start-upload').prop('disabled') && !uploadState.paused && uploadState.processed < uploadState.totalRecords) {
                  if (!confirm('Upload in progress. Are you sure you want to stop?')) return;
              }
              $('#bulk-upload-modal').hide();
          };

          window.pauseBulkUpload = function() {
              uploadState.paused = true;
              $('#btn-pause-upload').hide();
              $('#btn-resume-upload').show();
              $('#bulk-upload-status-text').text('Paused');
              if (uploadState.timerInterval) clearInterval(uploadState.timerInterval);
          };

          window.resumeBulkUpload = function() {
              uploadState.paused = false;
              $('#btn-resume-upload').hide();
              $('#btn-pause-upload').show();
              $('#bulk-upload-status-text').text('Resuming...');
              processBatch(uploadState.processed); // Restart loop
              startTimer();
          };
          
          window.downloadBulkReport = function() {
               let csvContent = "data:text/csv;charset=utf-8,";
               csvContent += "Row,Status,Details\n";
               
               uploadState.allSuccess.forEach(function(msg) {
                   csvContent += "Success," + msg.replace(/,/g, ";") + "\n"; 
               });
               uploadState.allEmailExists.forEach(function(msg) {
                   csvContent += "Email Exists," + msg.replace(/,/g, ";") + "\n";
               });
               uploadState.allErrors.forEach(function(msg) {
                   csvContent += "Error," + msg.replace(/,/g, ";") + "\n";
               });
               
               var encodedUri = encodeURI(csvContent);
               var link = document.createElement("a");
               link.setAttribute("href", encodedUri);
               link.setAttribute("download", "bulk_upload_report_" + Date.now() + ".csv");
               document.body.appendChild(link);
               link.click();
          };

          function startTimer() {
              if (uploadState.timerInterval) clearInterval(uploadState.timerInterval);
              uploadState.timerInterval = setInterval(function() {
                  if (uploadState.paused) return;
                  let elapsed = Math.floor((Date.now() - uploadState.startTime) / 1000);
                  let mins = Math.floor(elapsed / 60);
                  let secs = elapsed % 60;
                  $('#bulk-upload-timer').text('Elapsed: ' + mins + 'm ' + secs + 's');
              }, 1000);
          }
          
          window.previewBulkUpload = function() {
              var fileInput = $('#bulk-upload-file')[0];
              if (fileInput.files.length === 0) {
                  alert('Please select a file.');
                  return;
              }
              
              var file = fileInput.files[0];
              if (file.size > 50 * 1024 * 1024) { // 50MB
                  alert('File size exceeds 50MB limit.');
                  return;
              }

              var formData = new FormData();
              formData.append('action', 'upload_users');
              formData.append('file', file);
              formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
              
              $('#btn-preview-upload').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
              
              $.ajax({
                  url: 'bulk-upload-users.php',
                  method: 'POST',
                  data: formData,
                  processData: false,
                  contentType: false,
                  dataType: 'json',
                  success: function(response) {
                      if (response.status === 'success') {
                          uploadState.filename = response.filename;
                          uploadState.totalRecords = response.total_records;
                          uploadState.logId = response.log_id;
                          uploadState.filePos = response.start_pos;
                          
                          if (uploadState.totalRecords > 0) {
                               // Fetch Preview
                               fetchPreviewData(response.filename);
                          } else {
                              alert('File is empty.');
                              $('#btn-preview-upload').prop('disabled', false).html('<i class="fa fa-eye"></i> Preview Data');
                          }
                      } else {
                          alert('Upload Failed: ' + response.message);
                          $('#btn-preview-upload').prop('disabled', false).html('<i class="fa fa-eye"></i> Preview Data');
                      }
                  },
                  error: function(xhr, status, error) {
                      alert('Network Error: ' + error);
                      $('#btn-preview-upload').prop('disabled', false).html('<i class="fa fa-eye"></i> Preview Data');
                  }
              });
          };
          
          function fetchPreviewData(filename) {
               $('#btn-preview-upload').html('<i class="fa fa-spinner fa-spin"></i> Loading Preview...');
               
               $.ajax({
                  url: 'bulk-upload-users.php',
                  method: 'POST',
                  data: {
                      action: 'preview_file',
                      filename: filename,
                      csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                  },
                  dataType: 'json',
                  success: function(response) {
                      if (response.status === 'success') {
                          var tbody = $('#bulk-preview-tbody');
                          tbody.empty();
                          
                          response.rows.forEach(function(row) {
                              var tr = $('<tr>');
                              tr.append('<td>' + (row.fullname || (row.fname + ' ' + row.lname)) + '</td>');
                              tr.append('<td>' + row.email + '</td>');
                              tr.append('<td>' + row.profession + '</td>');
                              tr.append('<td>' + row.organization + '</td>');
                              
                              var statusBadge = '<span class="label label-' + (row.status_color === 'green' ? 'success' : (row.status_color === 'orange' ? 'warning' : 'danger')) + '">' + row.status + '</span>';
                              if (row.notes) statusBadge += ' <small>(' + row.notes + ')</small>';
                              
                              tr.append('<td>' + statusBadge + '</td>');
                              tbody.append(tr);
                          });
                          
                          $('#bulk-preview-container').slideDown();
                          $('#btn-preview-upload').hide();
                          $('#btn-start-upload').show();
                      } else {
                          alert('Preview Error: ' + response.message);
                          $('#btn-preview-upload').prop('disabled', false).html('<i class="fa fa-eye"></i> Preview Data');
                      }
                  },
                  error: function() {
                      alert('Failed to load preview.');
                      $('#btn-preview-upload').prop('disabled', false).html('<i class="fa fa-eye"></i> Preview Data');
                  }
               });
          }

          window.startBulkUpload = function() {
              // If we have state (from preview), just start
              if (uploadState.filename && uploadState.totalRecords > 0) {
                  startProcessing();
                  return;
              }
              
              // Fallback if somehow called without preview (should not happen with new UI)
              alert('Please preview data first.');
          };
          
          function startProcessing() {
              $('#btn-start-upload').prop('disabled', true).hide();
              $('#btn-preview-upload').hide();
              $('#bulk-preview-container').slideUp();
              
              $('#btn-pause-upload').show();
              $('#bulk-upload-progress-container').show();
              $('#bulk-upload-progress-bar').css('width', '0%').text('0%');
              $('#bulk-upload-status-text').text('Starting sequential processing...');
              $('#bulk-upload-log').html('');
              $('#bulk-stats-success').text('0');
              $('#bulk-stats-failed').text('0');
              $('#bulk-stats-email-exists').text('0');
              
              uploadState.startTime = Date.now();
              startTimer();
              
              processBatch();
          }

          function processBatch() {
              if (uploadState.paused) return;

              const limit = 1; // Sequential processing
              let current = uploadState.processed + 1;
              $('#bulk-upload-status-text').text('Processing user ' + current + ' of ' + uploadState.totalRecords + '...');
              
              $.ajax({
                  url: 'bulk-upload-users.php',
                  method: 'POST',
                  dataType: 'json',
                  data: {
                      action: 'process_batch',
                      filename: uploadState.filename,
                      file_pos: uploadState.filePos,
                      processed_global: uploadState.processed,
                      limit: limit,
                      log_id: uploadState.logId,
                      csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                  },
                  success: function(response) {
                      if (uploadState.paused) return; // Check again in case paused during request

                      if (response.status === 'success') {
                          uploadState.processed += response.processed_count;
                          uploadState.filePos = response.next_pos;
                          
                          // Logs
                          if (response.success_data) {
                              response.success_data.forEach(msg => {
                                  uploadState.successCount++;
                                  uploadState.allSuccess.push(msg);
                                  let color = msg.includes("Email Failed") ? "orange" : "green";
                                  $('#bulk-upload-log').append('<div style="color:'+color+'">' + msg + '</div>');
                              });
                          }
                          if (response.error_data) {
                              response.error_data.forEach(msg => {
                                  if (msg.includes("Email exists")) {
                                      uploadState.emailExistCount++;
                                      uploadState.allEmailExists.push(msg);
                                      $('#bulk-upload-log').append('<div style="color:orange">' + msg + '</div>');
                                  } else {
                                      uploadState.failCount++;
                                      uploadState.allErrors.push(msg);
                                      $('#bulk-upload-log').append('<div style="color:red">' + msg + '</div>');
                                  }
                              });
                          }

                          // Update Stats
                          $('#bulk-stats-success').text(uploadState.successCount);
                          $('#bulk-stats-failed').text(uploadState.failCount);
                          $('#bulk-stats-email-exists').text(uploadState.emailExistCount);

                          // Progress
                          let percent = Math.round((uploadState.processed / uploadState.totalRecords) * 100);
                          $('#bulk-upload-progress-bar').css('width', percent + '%').text(percent + '%');
                          
                          // Scroll log
                          let logBox = document.getElementById("bulk-upload-log");
                          if(logBox) logBox.scrollTop = logBox.scrollHeight;

                          if (uploadState.processed < uploadState.totalRecords) {
                              if (!uploadState.paused) processBatch();
                          } else {
                              $('#bulk-upload-status-text').text('Processing Complete!');
                              alert('Bulk upload completed!\nSuccess: ' + uploadState.successCount + '\nEmail Exists: ' + uploadState.emailExistCount + '\nFailed: ' + uploadState.failCount);
                              window.fetchRows($('#users-search').val());
                              finishUpload();
                          }
                      } else {
                           $('#bulk-upload-log').append('<div style="color:red">Batch Error: ' + response.message + '</div>');
                           finishUpload(); // Or continue?
                      }
                  },
                  error: function(xhr) {
                      $('#bulk-upload-log').append('<div style="color:red">Network Error. Retrying in 5s...</div>');
                      setTimeout(function() {
                          if (!uploadState.paused) processBatch();
                      }, 5000);
                  }
              });
          }

          function finishUpload() {
              if (uploadState.timerInterval) clearInterval(uploadState.timerInterval);
              $('#btn-pause-upload').hide();
              $('#btn-resume-upload').hide();
              $('#btn-start-upload').hide();
              $('#btn-download-report').show();
          }
      });

      $(document).ready(function(){
        var $input = $('#users-search');
        var $clear = $('#clear-search');
        var $tbody = $('#users-table tbody');
        var $status = $('#search-status');
        var $pagination = $('#pagination-controls');
        var timer = null;
        var currentPage = 1;
        var currentRequest = null;

        function updateStatus(text){ $status.text(text); }
        
        // Filter Event Listeners
        $('#category-filter').on('change', function(){
            window.fetchRows($('#users-search').val());
        });
        $('#certificate-status-filter').on('change', function(){
            window.fetchRows($('#users-search').val());
        });
        
        window.fetchRows = function(q, callback, page){
          var cat = $('#category-filter').val();
          var certStatus = $('#certificate-status-filter').val();
          var start = $('#date-start').val();
          var end = $('#date-end').val();
          var limit = $('#rows-per-page').val() || 'ALL';
          page = page || 1;
          currentPage = page;
          
          // Abort previous request if running to prevent race conditions
          if(currentRequest) {
              currentRequest.abort();
          }
          
          currentRequest = $.ajax({
            url: 'manage-users.php',
            method: 'GET',
            dataType: 'json',
            data: { 
                ajax: 1, 
                search: q, 
                category: cat,
                certificate_status: certStatus,
                start_date: start,
                end_date: end,
                page: page,
                limit: limit
            },
            success: function(data){
              $tbody.html(data.html);
              renderPagination(data.totalPages, data.currentPage);
              
              $('#select-all').prop('checked', false); // Reset select all
              $('#btn-bulk-email').prop('disabled', true); // Reset button
              $('#btn-bulk-delete').prop('disabled', true);
              $('#bulk-selection-count').text('0 users selected');
              
              if (window.applyColumnVisibility) {
                  window.applyColumnVisibility();
              }
              
              var count = data.totalRecords; // Use server-side total count
              if (count === 0) {
                updateStatus('No results found');
              } else {
                updateStatus('Showing ' + count + ' result' + (count===1?'':'s'));
              }
              if (callback) callback();
            },
            error: function(xhr, status, error){
              if (status === 'abort') return;
              console.error("AJAX Error:", status, error);
              console.log(xhr.responseText); // Debugging
              updateStatus('Error fetching results');
              if (callback) callback();
            },
            complete: function() {
                currentRequest = null;
            }
          });
        };
        
        function renderPagination(totalPages, currentPage) {
            if (totalPages <= 1) {
                $pagination.empty();
                return;
            }
            
            var html = '<nav aria-label="Page navigation"><ul class="pagination">';
            
            // Previous
            if (currentPage > 1) {
                html += '<li><a href="#" data-page="' + (currentPage - 1) + '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
            } else {
                html += '<li class="disabled"><a href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
            }
            
            // Page info (Simplified for large numbers)
            // Show: 1 ... [current-2] [current-1] [current] [current+1] [current+2] ... [total]
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                html += '<li><a href="#" data-page="1">1</a></li>';
                if (startPage > 2) html += '<li class="disabled"><span>...</span></li>';
            }
            
            for (var i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += '<li class="active"><span>' + i + ' <span class="sr-only">(current)</span></span></li>';
                } else {
                    html += '<li><a href="#" data-page="' + i + '">' + i + '</a></li>';
                }
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) html += '<li class="disabled"><span>...</span></li>';
                html += '<li><a href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>';
            }
            
            // Next
            if (currentPage < totalPages) {
                html += '<li><a href="#" data-page="' + (currentPage + 1) + '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
            } else {
                html += '<li class="disabled"><a href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
            }
            
            html += '</ul></nav>';
            $pagination.html(html);
        }
        
        // Rows Per Page Change Handler
        $('#rows-per-page').change(function() {
            var q = $input.val();
            window.fetchRows(q, null, 1);
        });
        
        // Pagination Click Handler
        $pagination.on('click', 'a', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            if (page) {
                var q = $input.val();
                window.fetchRows(q, null, page);
                // Scroll to top of table
                $('html, body').animate({
                    scrollTop: $("#users-table").offset().top - 100
                }, 500);
            }
        });
        
        var savedSearch = (function(name) {
          var v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
          return v ? v[2] : null;
        })('search_query');

        if (savedSearch && savedSearch !== '') {
          $input.val(savedSearch);
          setTimeout(function() { window.fetchRows(savedSearch); }, 100);
        } else {
            // Initial load for pagination
            setTimeout(function() { window.fetchRows(''); }, 100);
        }

        $input.on('input', function(){
          var q = $input.val();
          var d = new Date();
          d.setTime(d.getTime() + (7 * 24 * 60 * 60 * 1000));
          document.cookie = "search_query=" + q + "; path=/; SameSite=Lax; expires=" + d.toUTCString();

          if (timer) { clearTimeout(timer); }
          timer = setTimeout(function(){ window.fetchRows(q); }, 300);
        });
        
        $('#category-filter, #date-start, #date-end').on('change', function() {
            var q = $input.val();
            window.fetchRows(q);
        });

        $('.export-btn').on('click', function(e) {
            e.preventDefault();
            var format = $(this).data('format');
            var cat = $('#category-filter').val();
            var start = $('#date-start').val();
            var end = $('#date-end').val();
            
            var url = 'manage-users.php?export_db=1&format=' + format;
            if (cat) url += '&category=' + encodeURIComponent(cat);
            if (start) url += '&start_date=' + encodeURIComponent(start);
            if (end) url += '&end_date=' + encodeURIComponent(end);
            
            window.location.href = url;
        });

        $clear.on('click', function(){
          $input.val('');
          document.cookie = 'search_query=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
          $('#category-filter').val('').trigger('change'); 
          window.fetchRows('');
          $input.focus();
        });
        $input.on('keydown', function(e){
          if (e.key === 'Escape') {
            $clear.click();
          }
        });
      });
    </script>

  </body>
</html>

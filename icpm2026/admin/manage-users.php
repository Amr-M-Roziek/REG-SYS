<?php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check session
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

// For deleting user
if(isset($_GET['id']))
{
    $adminid=$_GET['id'];
    $msg=mysqli_query($con,"delete from users where id='$adminid'");
    if($msg)
    {
        echo "<script>alert('Data deleted');window.location.href='manage-users.php';</script>";
    }
}

// Stream abstract file
if (isset($_GET['download_abstract']) && isset($_GET['uid'])) {
  $uid = intval($_GET['uid']);
  $stmt = mysqli_prepare($con, "SELECT abstract_blob, abstract_filename, abstract_mime FROM users WHERE id=?");
  mysqli_stmt_bind_param($stmt, 'i', $uid);
  mysqli_stmt_execute($stmt);
  
  $res = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($res);
  if ($row && !empty($row['abstract_blob'])) {
    $mime = $row['abstract_mime'] ?: 'application/octet-stream';
    $name = $row['abstract_filename'] ?: ('abstract_'.$uid.'.dat');
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'inline';
    $disp = ($mode === 'download') ? 'attachment' : 'inline';
    header('Content-Type: '.$mime);
    header('Content-Disposition: '.$disp.'; filename="'.$name.'"');
    echo $row['abstract_blob'];
    exit;
  } else {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No abstract stored for this user';
    exit;
  }
}

// Export DB Logic
if (isset($_GET['export_db']) && $_GET['export_db'] == 1) {
    $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
    $filename = "users_export_" . date('Y-m-d_H-i-s');
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

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
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if (!empty($endDate)) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        $sql .= " AND (fname LIKE ? OR lname LIKE ? OR email LIKE ? OR profession LIKE ? OR organization LIKE ? OR contactno LIKE ? OR id LIKE ?)";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= "sssssss";
    }

    if (!empty($params)) {
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_execute_compat($stmt, $types, $params);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($con, $sql);
    }

    if ($format == 'pdf') {
        // Try to include TCPDF
        $tcpdfPath = '../../wp-content/plugins/user-registration-pro/vendor/tecnickcom/tcpdf/tcpdf.php';
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
                    <td>'.htmlspecialchars($row['created_at']).'</td>
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
        
        $csv_headers = array('Sno.', 'Ref Number', 'First Name', 'Last Name', 'Email Id', 'Profession', 'Organization', 'Category', 'Contact no.');
        // Only Super Admin can export passwords
        if(isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
            $csv_headers[] = 'Password';
        }
        $csv_headers[] = 'Reg. Date';
        
        fputcsv($fp, $csv_headers);
        $cnt = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $csv_row = array(
                $cnt, $row['id'], $row['fname'], $row['lname'], $row['email'],
                $row['profession'], $row['organization'], $row['category'],
                $row['contactno']
            );
            
            if(isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
                $csv_row[] = $row['password'];
            }
            
            $csv_row[] = $row['created_at'];
            
            fputcsv($fp, $csv_row);
            $cnt++;
        }
        fclose($fp);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Manage Users</title>
    
    <!-- Bootstrap core CSS -->
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <!--external css-->
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
        /* Ref Number Column - No Truncation */
        #unseen table th:nth-child(3), #unseen table td:nth-child(3) {
            min-width: 150px;
            width: auto;
            white-space: nowrap;
        }
        
        /* Certificate Generation Styles */
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
            cursor: move;
            text-align: center;
            min-width: 50px;
            padding: 5px;
            border: 1px dashed transparent;
            touch-action: none;
            user-select: none;
            box-sizing: border-box;
            z-index: 10;
        }
        
        .cert-element img {
            width: 100%;
            height: 100%;
            object-fit: fill;
            pointer-events: none;
            max-width: none !important;
            max-height: none !important;
        }

        .cert-logo {
            max-width: 150px;
            height: auto;
        }

        /* Default Positions */
        #logo-left { top: 50px; left: 60px; }
        #logo-center { top: 40px; left: 50%; transform: translateX(-50%); width: 300px; }
        #logo-right { top: 50px; right: 60px; }
        
        #title-header {
            top: 200px;
            width: 100%;
            font-size: 28px;
            color: #7f8c8d;
            font-weight: 300;
            border-bottom: 2px solid #eee;
            width: 60%;
            left: 20%;
        }

        #awarded-to {
            top: 260px;
            width: 100%;
            font-size: 18px;
            color: #333;
        }

        #recipient-name {
            top: 300px;
            width: 100%;
            font-size: 42px;
            font-weight: bold;
            font-family: 'Times New Roman', serif;
            color: #000;
        }

        #participation-text {
            top: 380px;
            width: 100%;
            font-size: 16px;
            color: #333;
        }

        #conference-title {
            top: 420px;
            width: 100%;
            font-size: 24px;
            font-weight: bold;
            color: #003366;
        }

        #date-text {
            top: 480px;
            width: 100%;
            font-size: 16px;
            color: #333;
        }

        #venue-text {
            top: 520px;
            width: 100%;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        
        #accreditation-text {
            top: 580px;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            color: #ff4500;
        }

        #sig-left { bottom: 80px; left: 80px; text-align: left; }
        #sig-center { bottom: 60px; left: 50%; transform: translateX(-50%); }
        #sig-right-img { bottom: 100px; right: 80px; text-align: center; z-index: 2; }
        #sig-right-text { bottom: 60px; right: 80px; text-align: center; z-index: 1; }
        #icpm-stamp-right { bottom: 90px; right: 260px; z-index: 1; opacity: 0.9; }

        #qr-code-container {
            bottom: 140px;
            left: 50px;
            width: 100px;
            height: 100px;
            z-index: 10;
        }

        #ref-no {
            bottom: 30px;
            width: 100%;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<section id="container" >
    <!--TOP BAR CONTENT & NOTIFICATIONS-->
    <?php include 'includes/header.php'; ?>
    
    <!--MAIN SIDEBAR MENU-->
    <?php include 'includes/sidebar.php'; ?>
    
    <!--MAIN CONTENT-->
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Manage Users</h3>
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="content-panel">
                        <h4><i class="fa fa-angle-right"></i> All User Details</h4>
                        
                        <!-- Search & Filter Toolbar -->
                        <div class="row" style="margin-bottom: 20px; padding: 10px;">
                            <form method="GET" action="manage-users.php" class="form-inline" id="searchForm">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                    </div>
                                    <div class="form-group">
                                        <select name="category" class="form-control">
                                            <option value="">All Categories</option>
                                            <?php 
                                            // Fetch Categories
                                            $catQuery = mysqli_query($con, "SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category != ''");
                                            while($cRow = mysqli_fetch_array($catQuery)) {
                                                $selected = (isset($_GET['category']) && $_GET['category'] == $cRow['category']) ? 'selected' : '';
                                                echo "<option value='".htmlspecialchars($cRow['category'])."' $selected>".htmlspecialchars($cRow['category'])."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <input type="date" name="start_date" class="form-control" placeholder="Start Date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>" title="Start Date">
                                    </div>
                                    <div class="form-group">
                                        <input type="date" name="end_date" class="form-control" placeholder="End Date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>" title="End Date">
                                    </div>
                                    <div class="form-group">
                                        <select name="limit" class="form-control" onchange="this.form.submit()">
                                            <option value="25" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '25') ? 'selected' : ''; ?>>25</option>
                                            <option value="50" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '50') ? 'selected' : ''; ?>>50</option>
                                            <option value="100" <?php echo (!isset($_GET['limit']) || $_GET['limit'] == '100') ? 'selected' : ''; ?>>100</option>
                                            <option value="250" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '250') ? 'selected' : ''; ?>>250</option>
                                            <option value="500" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '500') ? 'selected' : ''; ?>>500</option>
                                            <option value="1000" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '1000') ? 'selected' : ''; ?>>1000</option>
                                            <option value="ALL" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 'ALL') ? 'selected' : ''; ?>>ALL</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-theme">Search</button>
                                    <a href="manage-users.php" class="btn btn-default">Reset</a>
                                    
                                    <div class="btn-group pull-right">
                                        <?php if(isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                                        <div class="checkbox" style="display: inline-block; margin-right: 15px; vertical-align: middle;">
                                            <label><input type="checkbox" id="togglePasswords"> Show Passwords</label>
                                        </div>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-danger" id="btn-bulk-delete" onclick="openBulkDeleteModal()" disabled style="margin-right: 5px;">
                                            <i class="fa fa-trash-o"></i> Delete Selected (<span id="delete-count-badge">0</span>)
                                        </button>
                                        <button type="button" class="btn btn-warning" id="btn-bulk-email" onclick="openBulkEmailModal()" disabled style="margin-right: 5px;">
                                            <i class="fa fa-envelope"></i> Send Certificates
                                        </button>
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#bulkUploadModal" style="margin-right: 5px;">
                                            <i class="fa fa-upload"></i> Bulk Upload
                                        </button>
                                        <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                            Export <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li><a href="#" onclick="submitExport('csv'); return false;">CSV</a></li>
                                            <li><a href="#" onclick="submitExport('excel'); return false;">Excel</a></li>
                                            <li><a href="#" onclick="submitExport('pdf'); return false;">PDF</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <input type="hidden" name="export_db" id="export_db" value="0">
                                <input type="hidden" name="format" id="export_format" value="">
                            </form>
                        </div>
                        
                        <script>
                        function submitExport(format) {
                            document.getElementById('export_db').value = '1';
                            document.getElementById('export_format').value = format;
                            document.getElementById('searchForm').submit();
                            // Reset after submit so normal search doesn't export
                            setTimeout(function() {
                                document.getElementById('export_db').value = '0';
                                document.getElementById('export_format').value = '';
                            }, 100);
                        }
                        </script>

                        <section id="unseen">
                            <table class="table table-striped table-advance table-hover">
                                <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>Sno.</th>
                                    <th> Ref Number</th>
                                    <th class="hidden-phone">First Name</th>
                                    <th> Last Name</th>
                                    <th> Email Id</th>
                                    <th> Profession</th>
                                    <th> Organization</th>
                                    <th> Category</th>
                                    <th>Contact no.</th>
                                    <?php if(isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                                    <th>Password</th>
                                    <?php endif; ?>
                                    <th>Reg. Date</th>
                                    <th>Cert. Status</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php 
                                // Build Query
                                $sql = "SELECT * FROM users WHERE 1=1";
                                $params = [];
                                $types = "";

                                if (isset($_GET['search']) && !empty($_GET['search'])) {
                                    $search = '%' . $_GET['search'] . '%';
                                    $sql .= " AND (fname LIKE ? OR lname LIKE ? OR email LIKE ? OR profession LIKE ? OR organization LIKE ? OR contactno LIKE ? OR id LIKE ?)";
                                    // Add params 7 times
                                    $params = array_merge($params, [$search, $search, $search, $search, $search, $search, $search]);
                                    $types .= "sssssss";
                                }

                                if (isset($_GET['category']) && !empty($_GET['category'])) {
                                    $sql .= " AND category = ?";
                                    $params[] = $_GET['category'];
                                    $types .= "s";
                                }
                                
                                if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
                                    $sql .= " AND DATE(created_at) >= ?";
                                    $params[] = $_GET['start_date'];
                                    $types .= "s";
                                }
                                
                                if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
                                    $sql .= " AND DATE(created_at) <= ?";
                                    $params[] = $_GET['end_date'];
                                    $types .= "s";
                                }
                                
                                // Execute Query
                                $limitArg = isset($_GET['limit']) ? $_GET['limit'] : '100';
                                if ($limitArg !== 'ALL') {
                                    $sql .= " LIMIT ?";
                                    $params[] = intval($limitArg);
                                    $types .= "i";
                                }

                                if (!empty($params)) {
                                    $stmt = mysqli_prepare($con, $sql);
                                    mysqli_stmt_execute_compat($stmt, $types, $params);
                                    $ret = mysqli_stmt_get_result($stmt);
                                } else {
                                    $ret = mysqli_query($con, $sql);
                                }

                                $cnt=1;
                                while($row=mysqli_fetch_array($ret))
                                {?>
                                <tr>
                                    <td><input type="checkbox" class="user-checkbox" value="<?php echo $row['id'];?>"></td>
                                    <td><?php echo $cnt;?></td>
                                    <td><?php echo $row['id'];?></td>
                                    <td><?php echo $row['fname'];?></td>
                                    <td><?php echo $row['lname'];?></td>
                                    <td><?php echo $row['email'];?></td>
                                    <td><?php echo $row['profession'];?></td>
                                    <td><?php echo $row['organization'];?></td>
                                    <td><?php echo $row['category'];?></td>
                                    <td><?php echo $row['contactno'];?></td>
                                    <?php if(isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                                    <td style="word-break: break-all; min-width: 100px;"><span class="password-display" id="pwd-<?php echo $row['id']; ?>">******</span></td>
                                    <?php endif; ?>
                                    <td><?php echo $row['created_at'];?></td>
                                    <td>
                                        <select class="form-control" onchange="updateCertificateStatus(<?php echo $row['id'];?>, this.value)" style="width: 120px; font-size: 12px; height: 30px; padding: 2px; <?php echo (isset($row['certificate_sent']) && $row['certificate_sent'] == 1) ? 'border-color: #5cb85c; border-width: 2px;' : ''; ?>">
                                            <option value="0" <?php echo (isset($row['certificate_sent']) && $row['certificate_sent'] == 0) ? 'selected' : ''; ?>>Not Sent</option>
                                            <option value="1" <?php echo (isset($row['certificate_sent']) && $row['certificate_sent'] == 1) ? 'selected' : ''; ?>>Sent</option>
                                        </select>
                                    </td>
                                    <td>
                                       <a href="certificate-editor.php?uid=<?php echo $row['id'];?>" title="Design Certificate" target="_blank" class="btn btn-warning btn-xs"><i class="fa fa-certificate"></i></a>
                                       <a href="welcome.php?uid=<?php echo $row['id'];?>" title="Print" target="_blank" class="btn btn-primary btn-xs"><i class="fa fa-print"></i></a>
                                       <a href="update-profile.php?uid=<?php echo $row['id'];?>" title="Edit" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a>
                                       <a href="manage-users.php?id=<?php echo $row['id'];?>" title="Delete" class="btn btn-danger btn-xs" onClick="return confirm('Do you really want to delete');"><i class="fa fa-trash-o "></i></a>
                                    </td>
                                </tr>
                                <?php $cnt=$cnt+1; }?>
                                </tbody>
                            </table>
                        </section>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <!--footer start-->
    <footer class="site-footer">
        <div class="text-center">
            2026 ICPM
            <a href="#" class="go-top">
                <i class="fa fa-angle-up"></i>
            </a>
        </div>
    </footer>
    <!--footer end-->
</section>

<!-- Bulk Upload Modal -->
<div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="bulkUploadModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Bulk User Upload & Registration</h4>
            </div>
            <div class="modal-body">
                <p>Step 1: <a href="bulk-upload.php?action=download_template" target="_blank">Download CSV Template</a><br>
                <small>Ensure your CSV follows the template format. Email is required.</small></p>
                
                <form id="bulkUploadForm">
                    <div class="form-group">
                        <label>Select CSV File (Max 50MB):</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control">
                    </div>
                </form>

                <div class="row" style="margin-bottom: 10px; text-align: center; font-weight: bold;">
                    <div class="col-md-4" style="color: green;">Success: <span id="stats-success">0</span></div>
                    <div class="col-md-4" style="color: orange;">Email Exists: <span id="stats-exist">0</span></div>
                    <div class="col-md-4" style="color: red;">Failed: <span id="stats-fail">0</span></div>
                </div>

                <label>Process Log:</label>
                <textarea id="processLog" class="form-control" rows="10" readonly style="font-family: monospace; font-size: 12px; background: #f9f9f9;"></textarea>
                
                <div class="progress mt" style="display:none; margin-top: 10px;">
                    <div id="uploadProgress" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        <span id="progressText">0%</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button data-dismiss="modal" class="btn btn-default" type="button">Close</button>
                <button class="btn btn-theme" type="button" onclick="startBulkUpload()">Start Upload</button>
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

<!-- Bulk Delete Modal -->
<div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="bulk-delete-modal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Confirm Bulk Deletion</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="delete-count" style="font-weight:bold;">0</span> users?</p>
                <p class="text-danger">This action cannot be undone.</p>
                
                <div class="form-group">
                    <label>Enter Admin Password to Confirm:</label>
                    <input type="password" class="form-control" id="delete-password" placeholder="Password">
                </div>
                <div class="alert alert-danger" id="delete-error" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button data-dismiss="modal" class="btn btn-default" type="button">Cancel</button>
                <button class="btn btn-danger" id="btn-confirm-delete" type="button" onclick="confirmBulkDelete()">Delete Users</button>
            </div>
        </div>
    </div>
</div>

<!-- Admin Password Verification Modal -->
<div aria-hidden="true" aria-labelledby="myModalLabel" role="dialog" tabindex="-1" id="passwordVerifyModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Admin Verification Required</h4>
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

<!-- js placed at the end of the document so the pages load faster -->
<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="assets/js/jquery.scrollTo.min.js"></script>
<script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
<!-- PDF Libraries for Bulk Generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>window.jsPDF = window.jspdf.jsPDF;</script>
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script src="../participant/admin/assets/js/qrcode.min.js"></script>
<script>
    // Global variable for certificate editor core
    var currentUserData = {}; 
</script>
<script src="../participant/admin/assets/js/certificate-editor-core.js"></script>
<script src="assets/js/common-scripts.js"></script>

<script>
var totalRecords = 0;
var currentFilename = '';
var stopProcess = false;
var successCount = 0;
var existCount = 0;
var failCount = 0;

function updateBulkStats() {
    $('#stats-success').text(successCount);
    $('#stats-exist').text(existCount);
    $('#stats-fail').text(failCount);
}

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

function logMessage(msg) {
    var log = $('#processLog');
    log.val(log.val() + msg + "\n");
    log.scrollTop(log[0].scrollHeight);
}

function startBulkUpload() {
    var fileInput = document.getElementById('csv_file');
    if (fileInput.files.length === 0) {
        alert("Please select a CSV file first.");
        return;
    }

    var formData = new FormData();
    formData.append('csv_file', fileInput.files[0]);
    formData.append('action', 'upload');

    $('#processLog').val("Uploading file...\n");
    $('.progress').show();
    $('#uploadProgress').css('width', '10%').removeClass('progress-bar-success').addClass('progress-bar-striped active');
    
    successCount = 0;
    existCount = 0;
    failCount = 0;
    updateBulkStats();
    
    // Step 1: Upload File
    $.ajax({
        url: 'bulk_process_ajax.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                logMessage(response.message);
                totalRecords = response.total_records;
                currentFilename = response.filename;
                
                if (totalRecords > 0) {
                    processRow(0); // Start Sequential Loop
                } else {
                    logMessage("No records found to process.");
                    $('#uploadProgress').css('width', '100%').removeClass('active');
                }
            } else {
                logMessage("Error: " + response.message);
            }
        },
        error: function() {
            logMessage("System Error: Upload failed.");
        }
    });
}

function processRow(index) {
    if (index >= totalRecords) {
        logMessage("-----------------------------------");
        logMessage("Batch Process Completed.");
        $('#uploadProgress').css('width', '100%').removeClass('active').addClass('progress-bar-success');
        $('#progressText').text("Completed");
        return;
    }

    var percent = Math.round(((index) / totalRecords) * 100);
    $('#uploadProgress').css('width', percent + '%');
    $('#progressText').text(percent + '%');

    $.ajax({
        url: 'bulk_process_ajax.php',
        type: 'POST',
        data: {
            action: 'process_row',
            filename: currentFilename,
            index: index
        },
        dataType: 'json',
        success: function(response) {
            logMessage(response.message);
            
            if (response.status === 'success') {
                successCount++;
            } else if (response.status === 'warning') {
                existCount++;
            } else {
                failCount++;
            }
            updateBulkStats();

            // Recursive call for next row
            processRow(index + 1);
        },
        error: function(xhr, status, error) {
            logMessage("Row " + (index + 1) + ": System Error - " + error);
            failCount++;
            updateBulkStats();
            // Continue to next even if error
            processRow(index + 1);
        }
    });
}

// Bulk Delete Logic
$(document).ready(function() {
    // Checkbox Logic
    function updateBulkButton() {
        var count = $('.user-checkbox:checked').length;
        $('#btn-bulk-delete').prop('disabled', count === 0);
        $('#btn-bulk-email').prop('disabled', count === 0);
        $('#delete-count-badge').text(count);
    }

    $('#select-all').on('click', function() {
        var isChecked = $(this).is(':checked');
        $('.user-checkbox').prop('checked', isChecked);
        updateBulkButton();
    });

    // Use delegation for body checkboxes
    $(document).on('change', '.user-checkbox', function() {
        var allChecked = $('.user-checkbox').length > 0 && $('.user-checkbox').length === $('.user-checkbox:checked').length;
        $('#select-all').prop('checked', allChecked);
        updateBulkButton();
    });
    
    // Initial check
    updateBulkButton();
});

window.openBulkDeleteModal = function() {
    var count = $('.user-checkbox:checked').length;
    $('#delete-count').text(count);
    $('#delete-password').val('');
    $('#delete-error').hide();
    $('#bulk-delete-modal').modal('show');
};

// Bulk Email Logic
var bulkCancelled = false;

window.openBulkEmailModal = function() {
    var count = $('.user-checkbox:checked').length;
    $('#bulk-selection-count-modal').text(count + ' users selected');
    $('#bulk-email-modal').modal('show');
    
    // Reset UI
    $('#bulkEmailForm').show();
    $('#bulk-progress-container').hide();
    $('#use-override-email').prop('checked', false);
    $('#override-email').val('');
    $('#override-email-group').hide();
    
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

// Checkbox listener for override email
    $(document).on('change', '#use-override-email', function() {
        if(this.checked) {
            $('#override-email-group').slideDown();
        } else {
            $('#override-email-group').slideUp();
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
    
    var overrideEmail = '';
    if ($('#use-override-email').is(':checked')) {
        overrideEmail = $('#override-email').val();
        if (!overrideEmail) {
            alert('Please enter an override email address.');
            return;
        }
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
            csrf_token: '<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ""; ?>'
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('Users deleted successfully');
                $('#bulk-delete-modal').modal('hide');
                window.location.reload(); // Reload to refresh list
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

$('#togglePasswords').change(function() {
    if($(this).is(':checked')) {
        // Open Modal
        $('#verify-error').hide();
        $('#verify-admin-password').val('');
        $('#passwordVerifyModal').modal('show');
    } else {
        // Hide passwords
        $('.password-display').text('******');
    }
});

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
                    var id = $(this).attr('id').replace('pwd-', '');
                    if (passwords[id]) {
                        $(this).text(passwords[id]);
                    }
                });
                $('#passwordVerifyModal').modal('hide');
            } else {
                $('#verify-error').text(response.message).show();
            }
        },
        error: function() {
            $('#verify-error').text('System Error').show();
        }
    });
};
</script>

<script>
$(document).ready(function() {
    // Other initializations if needed
});
</script>

</body>
</html>

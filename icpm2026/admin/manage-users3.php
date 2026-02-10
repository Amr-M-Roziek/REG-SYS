<?php
session_start();
include 'dbconnection.php';
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
        echo "<script>alert('Data deleted');</script>";
    }
}

// Stream abstract file
if (isset($_GET['download_abstract']) && isset($_GET['uid'])) {
  $uid = intval($_GET['uid']);
  $stmt = mysqli_prepare($con, "SELECT abstract_blob, abstract_filename, abstract_mime FROM users WHERE id=?");
  // Modern execute for PHP 8.1+ (using variables for bind_param is fine, or execute array)
  // Staying consistent with the fix:
  if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
      mysqli_stmt_execute($stmt, [$uid]);
  } else {
      mysqli_stmt_bind_param($stmt, 'i', $uid);
      mysqli_stmt_execute($stmt);
  }
  
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
        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            mysqli_stmt_execute($stmt, $params);
        } else {
            mysqli_stmt_execute($stmt, $params);
        }
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
        fputcsv($fp, array('Sno.', 'Ref Number', 'First Name', 'Last Name', 'Email Id', 'Profession', 'Organization', 'Category', 'Contact no.', 'Password', 'Reg. Date'));
        $cnt = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($fp, array(
                $cnt, $row['id'], $row['fname'], $row['lname'], $row['email'],
                $row['profession'], $row['organization'], $row['category'],
                $row['contactno'], $row['password'], $row['created_at']
            ));
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
    <title>Admin | Manage Users 3</title>
    
    <!-- Bootstrap core CSS -->
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <!--external css-->
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
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
            <h3><i class="fa fa-angle-right"></i> Manage Users 3 (Test)</h3>
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="content-panel">
                        <h4><i class="fa fa-angle-right"></i> All User Details</h4>
                        
                        <!-- Search & Filter Toolbar -->
                        <div class="row" style="margin-bottom: 20px; padding: 10px;">
                            <form method="GET" action="manage-users3.php" class="form-inline" id="searchForm">
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
                                    <button type="submit" class="btn btn-theme">Search</button>
                                    <a href="manage-users3.php" class="btn btn-default">Reset</a>
                                    
                                    <div class="btn-group pull-right">
                                        <div class="checkbox" style="display: inline-block; margin-right: 15px; vertical-align: middle;">
                                            <label><input type="checkbox" id="togglePasswords"> Show Passwords</label>
                                        </div>
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
                                    <th>Password</th>
                                    <th>Reg. Date</th>
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
                                if (!empty($params)) {
                                    $stmt = mysqli_prepare($con, $sql);
                                    if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
                                        mysqli_stmt_execute($stmt, $params);
                                    } else {
                                        // Fallback for older PHP if needed, but we know it's 8.2
                                        mysqli_stmt_execute($stmt, $params);
                                    }
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
                                    <td style="word-break: break-all; min-width: 100px;"><span class="password-display" id="pwd-<?php echo $row['id']; ?>">******</span></td>
                                    <td><?php echo $row['created_at'];?></td>
                                    <td>
                                       <a href="certificate-editor.php?uid=<?php echo $row['id'];?>" title="Design Certificate">
                                       <button class="btn btn-warning btn-xs"><i class="fa fa-certificate"></i></button></a>
                                       <a href="welcome.php?uid=<?php echo $row['id'];?>" title="Print">
                                       <button class="btn btn-primary btn-xs"><i class="fa fa-print"></i></button></a>
                                       <a href="update-profile.php?uid=<?php echo $row['id'];?>" title="Edit">
                                       <button class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></button></a>
                                       <a href="manage-users3.php?id=<?php echo $row['id'];?>" title="Delete">
                                       <button class="btn btn-danger btn-xs" onClick="return confirm('Do you really want to delete');"><i class="fa fa-trash-o "></i></button></a>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><i class="fa fa-envelope"></i> Send Bulk Certificates</h4>
            </div>
            <div class="modal-body">
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
                        <select id="certificate-template" class="form-control">
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Additional Attachments (Optional)</label>
                        <input type="file" id="bulk-attachments" class="form-control" multiple>
                        <p class="help-block">These files will be sent to ALL selected users (e.g. Agenda, Brochure).</p>
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
                    <div id="bulk-log" style="height: 100px; overflow-y: auto; background: #f5f5f5; border: 1px solid #ccc; padding: 5px; font-size: 11px; font-family: monospace;"></div>
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
<script src="assets/js/common-scripts.js"></script>

<script>
var totalRecords = 0;
var currentFilename = '';
var stopProcess = false;

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

// Bulk Email Logic
var bulkCancelled = false;

window.openBulkEmailModal = function() {
    var count = $('.user-checkbox:checked').length;
    $('#bulk-selection-count-modal').text(count + ' users selected');
    $('#bulk-email-modal').modal('show');
    
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
                    html += '<option value="'+t.id+'">'+t.name+'</option>';
                });
            }
            $('#certificate-template').html(html);
        }
    });
};

function logBulk(msg, color) {
    var div = $('<div>').text(msg).css('color', color || 'black');
    $('#bulk-log').append(div);
    $('#bulk-log').scrollTop($('#bulk-log')[0].scrollHeight);
}

$('#btn-cancel-bulk').on('click', function() {
    if ($('#btn-start-bulk').prop('disabled')) {
        // Process running
        if(confirm('Stop sending?')) {
            bulkCancelled = true;
        }
    }
});

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
    $('#bulk-log').empty();
    
    // Collect Data
    var bulkUsers = [];
    $('.user-checkbox:checked').each(function() {
        bulkUsers.push($(this).val());
    });
    
    // Prepare Batch
    var bulkBatchId = 'batch_' + Date.now();
    logBulk('Starting batch: ' + bulkBatchId);
    
    // Step 1: Upload Attachments if any
    var fileInput = document.getElementById('bulk-attachments');
    if (fileInput.files.length > 0) {
        logBulk('Uploading attachments...');
        var formData = new FormData();
        formData.append('action', 'prepare_bulk_upload');
        formData.append('batch_id', bulkBatchId);
        for(var i=0; i<fileInput.files.length; i++) {
            formData.append('attachments[]', fileInput.files[i]);
        }
        
        try {
            // Promisify $.ajax for upload
            await new Promise((resolve, reject) => {
                $.ajax({
                    url: 'ajax_handler.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') resolve(res);
                        else reject(new Error(res.message));
                    },
                    error: function(xhr, status, err) {
                        reject(new Error(err));
                    }
                });
            });
            logBulk('Attachments uploaded successfully.', 'green');
        } catch (e) {
            logBulk('Error uploading attachments: ' + e.message, 'red');
            alert('Failed to upload attachments. Aborting.');
            $('#btn-start-bulk').prop('disabled', false);
            return;
        }
    }
    
    // Step 2: Sending Loop
    let processed = 0;
    let successCount = 0;
    let failCount = 0;
    
    for (const uid of bulkUsers) {
        if (bulkCancelled) {
            logBulk('Process cancelled by user.', 'orange');
            break;
        }
        
        $('#bulk-status-text').text('Processing user ' + (processed + 1) + ' of ' + bulkUsers.length + '...');
        
        try {
            const res = await sendSingleEmail(uid, bulkBatchId, useTemplate ? templateId : null);
            if (res.status === 'success') {
                logBulk('User ' + uid + ': Sent successfully.', 'green');
                successCount++;
            } else {
                logBulk('User ' + uid + ': Failed - ' + res.message, 'red');
                failCount++;
            }
        } catch (e) {
            logBulk('User ' + uid + ': Network Error - ' + e.message, 'red');
            failCount++;
        }
        
        processed++;
        const pct = Math.round((processed / bulkUsers.length) * 100);
        $('#bulk-progress-bar').css('width', pct + '%').text(pct + '%');
        
        if (processed < bulkUsers.length && !bulkCancelled) {
            // Wait for delay
            await new Promise(r => setTimeout(r, delay * 1000));
        }
    }
    
    $('#bulk-status-text').text('Completed. Success: ' + successCount + ', Failed: ' + failCount);
    $('#btn-start-bulk').hide(); // Hide start button
    $('#btn-cancel-bulk').text('Close').prop('disabled', false);
    alert('Bulk sending completed!');
};

async function sendSingleEmail(uid, batchId, templateId) {
    let pdfData = '';
    
    if (templateId) {
        try {
            pdfData = await generatePdfForUser(uid, templateId);
        } catch (e) {
            return { status: 'error', message: 'PDF Gen Failed: ' + e.message };
        }
    }
    
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'ajax_handler.php',
            method: 'POST',
            data: {
                action: 'send_bulk_single',
                uid: uid,
                batch_id: batchId,
                pdf_data: pdfData
            },
            dataType: 'json',
            success: resolve,
            error: function(xhr, status, err) {
                // Return a fake error object instead of rejecting to keep loop going
                resolve({ status: 'error', message: 'Ajax Error: ' + err });
            }
        });
    });
}

async function generatePdfForUser(uid, templateId) {
     // 1. Fetch User Data
     const userRes = await $.ajax({ url: 'ajax_handler.php', method: 'POST', data: { action: 'get_user_data', uid: uid }, dataType: 'json' });
     if(userRes.status !== 'success') throw new Error('User data not found');
     const user = userRes.data;
     
     // 2. Fetch Template Data
     // Optimization: Cache template data in a global var instead of fetching every time
     if (!window.cachedTemplate || window.cachedTemplateId !== templateId) {
         const tplRes = await $.ajax({ url: 'ajax_handler.php', method: 'POST', data: { action: 'load_template', id: templateId }, dataType: 'json' });
         if(tplRes.status !== 'success') throw new Error('Template not found');
         window.cachedTemplate = JSON.parse(tplRes.data.data);
         window.cachedTemplateId = templateId;
     }
     const templateData = window.cachedTemplate;
     
     // 3. Render in hidden container
     let container = document.getElementById('bulk-render-container');
     if (!container) {
         container = document.createElement('div');
         container.id = 'bulk-render-container';
         container.style.position = 'fixed';
         container.style.top = '-10000px';
         container.style.left = '-10000px';
         // container.style.visibility = 'hidden'; // html2canvas needs it visible-ish
         container.style.width = '1123px'; // A4 Landscape px at 96dpi approx (297mm)
         container.style.height = '794px'; // (210mm)
         container.style.background = 'white';
         container.style.zIndex = '-9999';
         document.body.appendChild(container);
     }
     container.innerHTML = ''; // Clear
     
     // Render elements
     templateData.forEach(data => {
          const div = document.createElement('div');
          div.id = data.id;
          div.className = 'cert-element';
          div.style.cssText = data.style;
          div.style.border = 'none'; // Clean up edit borders
          
          // Inject Data
          if (div.id === 'recipient-name') div.innerHTML = user.fname + ' ' + user.lname;
          else if (div.id === 'ref-no') div.innerHTML = 'Certificate Ref No. ' + user.id;
          else div.innerHTML = data.content;
          
          if (data.dataX) {
               const x = data.dataX || 0;
               const y = data.dataY || 0;
               const rot = data.dataRotation || 0;
               div.style.transform = `translate(${x}px, ${y}px) rotate(${rot}deg)`;
          }
          
          container.appendChild(div);
     });
     
     // 4. Snapshot
     if (typeof html2canvas === 'undefined') {
         throw new Error('html2canvas missing');
     }
     
     const canvas = await html2canvas(container, { 
         scale: 2, 
         useCORS: true, 
         logging: false,
         allowTaint: true
     });
     const imgData = canvas.toDataURL('image/jpeg', 0.8);
     
     // 5. Create PDF
     const { jsPDF } = window.jsPDF;
     const pdf = new jsPDF('l', 'mm', 'a4');
     const pdfWidth = pdf.internal.pageSize.getWidth();
     const pdfHeight = pdf.internal.pageSize.getHeight();
     pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
     
     // Return base64 without prefix
     return pdf.output('datauristring').split(',')[1];
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
            // Recursive call for next row
            processRow(index + 1);
        },
        error: function(xhr, status, error) {
            logMessage("Row " + (index + 1) + ": System Error - " + error);
            // Continue to next even if error
            processRow(index + 1);
        }
    });
}

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

</body>
</html>

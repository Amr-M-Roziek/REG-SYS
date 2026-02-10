<?php
require_once 'session_setup.php';
include 'dbconnection.php';
require_once 'permission_helper.php';

if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Fetch lectures for dropdowns
$lectures_result = mysqli_query($con, "SELECT id, title FROM lectures ORDER BY start_time DESC");
$lectures_opts = "";
while ($row = mysqli_fetch_assoc($lectures_result)) {
    $lectures_opts .= "<option value='{$row['id']}'>{$row['title']}</option>";
}
// Reset pointer for second dropdown
mysqli_data_seek($lectures_result, 0);
$lectures_opts_report = "";
while ($row = mysqli_fetch_assoc($lectures_result)) {
    $lectures_opts_report .= "<option value='{$row['id']}'>{$row['title']}</option>";
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

    <title>Attendance Management</title>

    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" type="text/javascript"></script>
    
    <style>
        .nav-tabs { margin-bottom: 20px; }
        #reader { width: 100%; max-width: 600px; margin: 0 auto; border: 1px solid #ccc; }
        .scan-result-card {
            border: 1px solid #ddd;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            text-align: center;
            display: none;
        }
        .scan-success { background-color: #dff0d8; border-color: #d6e9c6; color: #3c763d; }
        .scan-error { background-color: #f2dede; border-color: #ebccd1; color: #a94442; }
        .scan-warning { background-color: #fcf8e3; border-color: #faebcc; color: #8a6d3b; }
        .scan-feedback { 
            position: fixed; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            background: rgba(0,0,0,0.8); 
            color: white; 
            padding: 20px; 
            border-radius: 10px; 
            z-index: 9999; 
            display: none; 
            font-size: 1.5em;
        }
        .nav-tabs > li > a { color: #666; }
        .nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus {
            color: #555;
            cursor: default;
            background-color: #fff;
            border: 1px solid #ddd;
            border-bottom-color: transparent;
        }
    </style>
</head>

<body>

<section id="container" >
    <!--header start-->
    <header class="header black-bg">
        <div class="sidebar-toggle-box">
            <div class="fa fa-bars tooltips" data-placement="right" data-original-title="Toggle Navigation"></div>
        </div>
        <a href="index.php" class="logo"><b>Admin Dashboard</b></a>
        <div class="top-menu">
            <ul class="nav pull-right top-menu">
                <li><a class="logout" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>
    <!--header end-->
    
    <div id="scan-feedback" class="scan-feedback">
        <i class="fa fa-spinner fa-spin"></i> Processing...
    </div>

    <!--sidebar start-->
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
                    <a class="active" href="manage-attendance.php">
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
            </ul>
        </div>
    </aside>
    <!--sidebar end-->
    
    <!--main content start-->
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-users"></i> Lecture Attendance Management</h3>
            
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="content-panel" style="padding: 20px;">
                        
                        <ul class="nav nav-tabs" id="myTab">
                            <li class="active"><a href="#lectures" data-toggle="tab">Lectures</a></li>
                            <li><a href="#scan" data-toggle="tab">Live Scan</a></li>
                            <li><a href="#reports" data-toggle="tab">Reports</a></li>
                        </ul>

                        <div class="tab-content">
                            <!-- Lectures Tab -->
                            <div class="tab-pane active" id="lectures">
                                <div style="margin-top: 15px; margin-bottom: 15px;">
                                    <button class="btn btn-success" data-toggle="modal" data-target="#addLectureModal">
                                        <i class="fa fa-plus"></i> Add New Lecture
                                    </button>
                                </div>
                                <div id="lectures-table-container">
                                    <table class="table table-striped table-advance table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Title</th>
                                                <th>Lecturer</th>
                                                <th>Time</th>
                                                <th>Location</th>
                                                <th>Attendees</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lectures-list">
                                            <!-- Loaded via AJAX -->
                                            <tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Live Scan Tab -->
                            <div class="tab-pane" id="scan">
                                <div class="row" style="margin-top: 20px;">
                                    <div class="col-md-6 col-md-offset-3">
                                        <div class="form-group">
                                            <label>Select Lecture to Scan For:</label>
                                            <select class="form-control" id="scan-lecture-select">
                                                <option value="">-- Select Lecture --</option>
                                                <?php echo $lectures_opts; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="panel panel-default">
                                            <div class="panel-heading">QR Code Scan</div>
                                            <div class="panel-body">
                                                <div id="reader-container" style="display:none; margin-bottom: 20px;">
                                                    <div id="reader"></div>
                                                    <div class="text-center mt">
                                                        <button class="btn btn-danger" id="stop-scan" style="margin-top: 10px;">Stop Scanning</button>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-center mt" id="start-scan-container">
                                                    <button class="btn btn-primary btn-lg" id="start-scan" disabled>
                                                        <i class="fa fa-camera"></i> Start Camera Scan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="panel panel-default" style="margin-top: 20px;">
                                            <div class="panel-heading">Manual Entry</div>
                                            <div class="panel-body">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="manual-input" placeholder="Enter Email or User ID">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-warning" id="btn-manual-add" type="button" disabled>Add Manually</button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="scan-result" class="scan-result-card">
                                            <h4 id="scan-status-title"></h4>
                                            <p id="scan-message" style="font-size: 1.2em;"></p>
                                            <div id="user-details" style="font-size: 1.1em; margin-top: 10px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reports Tab -->
                            <div class="tab-pane" id="reports">
                                <div class="row mb" style="margin-top: 20px; margin-bottom: 20px;">
                                    <div class="col-md-4">
                                        <select class="form-control" id="report-lecture-filter">
                                            <option value="">All Lectures</option>
                                            <?php echo $lectures_opts_report; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-primary" id="btn-filter-report">Filter</button>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <button class="btn btn-success" id="btn-export-csv"><i class="fa fa-download"></i> Export CSV</button>
                                    </div>
                                </div>
                                
                                <div class="row" style="margin-bottom: 30px;">
                                    <div class="col-md-12">
                                        <div style="width: 100%; height: 300px;">
                                            <canvas id="attendanceChart"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Time</th>
                                                <th>Lecture</th>
                                                <th>Student Name</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="report-list">
                                            <tr><td colspan="5" class="text-center">Select a lecture or click Filter to load data</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </section>
    <!--main content end-->
</section>

<!-- Add Lecture Modal -->
<div class="modal fade" id="addLectureModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add New Lecture</h4>
            </div>
            <div class="modal-body">
                <form id="add-lecture-form">
                    <input type="hidden" name="action" value="add_lecture">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Lecturer Name</label>
                        <input type="text" name="lecturer" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="datetime-local" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="datetime-local" name="end_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn-save-lecture">Save Lecture</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/chart-master/Chart.js"></script>
<script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="assets/js/jquery.scrollTo.min.js"></script>
<script src="assets/js/common-scripts.js"></script>

<script>
$(document).ready(function() {
    loadLectures();
    
    // Auto load report if we just want to see something
    loadReport();

    // --- Lectures Management ---
    $('#btn-save-lecture').click(function() {
        $.post('attendance_handler.php', $('#add-lecture-form').serialize(), function(res) {
            if (res.status === 'success') {
                $('#addLectureModal').modal('hide');
                $('#add-lecture-form')[0].reset();
                loadLectures();
                alert('Lecture added successfully!');
                location.reload(); // Reload to update dropdowns
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    });

    function loadLectures() {
        $.post('attendance_handler.php', {action: 'get_lectures'}, function(res) {
            if (res.status === 'success') {
                var html = '';
                if(res.data.length === 0) {
                    html = '<tr><td colspan="7" class="text-center">No lectures found. Add one to start.</td></tr>';
                } else {
                    res.data.forEach(function(l) {
                        html += '<tr>' +
                            '<td>' + l.id + '</td>' +
                            '<td>' + l.title + '</td>' +
                            '<td>' + l.lecturer_name + '</td>' +
                            '<td>' + l.start_time + '</td>' +
                            '<td>' + l.location + '</td>' +
                            '<td><span class="badge bg-theme">' + l.attendee_count + '</span></td>' +
                            '<td><button class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></button></td>' +
                            '</tr>';
                    });
                }
                $('#lectures-list').html(html);
            }
        }, 'json');
    }

    // --- QR Scanning ---
    var html5QrcodeScanner = null;

    $('#scan-lecture-select').change(function() {
        if ($(this).val()) {
            $('#start-scan').prop('disabled', false);
        } else {
            $('#start-scan').prop('disabled', true);
        }
    });

    $('#start-scan').click(function() {
        $('#start-scan-container').hide();
        $('#reader-container').show();
        
        // Use Html5Qrcode class (not scanner) for custom control if needed, 
        // but scanner is easier for UI. Using Html5Qrcode for custom element #reader
        html5QrcodeScanner = new Html5Qrcode("reader");
        
        // Prefer back camera
        var config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        html5QrcodeScanner.start(
            { facingMode: "environment" }, 
            config,
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            console.error("Error starting scanner", err);
            alert("Error starting camera: " + err + "\nPlease ensure you are using HTTPS or localhost.");
            $('#reader-container').hide();
            $('#start-scan-container').show();
        });
    });

    $('#stop-scan').click(function() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then((ignore) => {
                html5QrcodeScanner.clear();
                $('#reader-container').hide();
                $('#start-scan-container').show();
            }).catch((err) => {
                console.log("Stop failed: ", err);
            });
        }
    });

    var isProcessing = false;

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return;
        isProcessing = true;
        
        // Show visual feedback
        $('#scan-feedback').show();
        
        // Optional: Beep
        // var audio = new Audio('assets/beep.mp3'); audio.play().catch(e=>{});

        var lectureId = $('#scan-lecture-select').val();
        
        console.log("Scanned Code:", decodedText);

        $.post('attendance_handler.php', {
            action: 'record_attendance',
            lecture_id: lectureId,
            qr_data: decodedText
        }, function(res) {
            $('#scan-feedback').hide();
            showScanResult(res);
            // Pause scanning for 3 seconds to show result
            setTimeout(function() {
                isProcessing = false;
                $('#scan-result').fadeOut();
            }, 3000);
        }, 'json').fail(function(xhr, status, error) {
            $('#scan-feedback').hide();
            isProcessing = false;
            console.error("Ajax error:", error);
            alert("Network error or server error: " + error);
        });
    }

    function onScanFailure(error) {
        // console.warn(`Code scan error = ${error}`);
    }

    function showScanResult(res) {
        var el = $('#scan-result');
        el.removeClass('scan-success scan-error scan-warning').show();
        
        if (res.status === 'success') {
            el.addClass('scan-success');
            $('#scan-status-title').html('<i class="fa fa-check-circle"></i> Check-in Successful');
            $('#scan-message').text('Welcome, ' + res.user.fname);
            $('#user-details').html('<strong>' + (res.user.postertitle || '') + '</strong><br>' + (res.att_status === 'late' ? '<span class="label label-warning">Late</span>' : ''));
        } else if (res.status === 'warning') {
            el.addClass('scan-warning');
            $('#scan-status-title').html('<i class="fa fa-exclamation-triangle"></i> Already Checked In');
            $('#scan-message').text(res.user.fname + ' is already marked present.');
            $('#user-details').empty();
        } else {
            el.addClass('scan-error');
            $('#scan-status-title').html('<i class="fa fa-times-circle"></i> Error');
            $('#scan-message').text(res.message);
            $('#user-details').empty();
        }
    }

    // --- Reports ---
    $('#btn-filter-report').click(function() {
        loadReport();
    });

    function loadReport() {
        var lid = $('#report-lecture-filter').val();
        $.post('attendance_handler.php', {action: 'get_report', lecture_id: lid}, function(res) {
            if (res.status === 'success') {
                var html = '';
                if(res.data.length === 0) {
                    html = '<tr><td colspan="5" class="text-center">No attendance records found.</td></tr>';
                } else {
                    res.data.forEach(function(r) {
                        html += '<tr>' +
                            '<td>' + r.scan_time + '</td>' +
                            '<td>' + r.lecture_title + '</td>' +
                            '<td>' + r.fname + '</td>' +
                            '<td>' + r.email + '</td>' +
                            '<td>' + r.status + '</td>' +
                            '</tr>';
                    });
                }
                $('#report-list').html(html);
                updateChart(res.data);
            }
        }, 'json');
    }

    function updateChart(data) {
        // Reset canvas
        $('#attendanceChart').replaceWith('<canvas id="attendanceChart" width="800" height="300" style="width: 100%; height: 300px;"></canvas>');
        var ctx = document.getElementById("attendanceChart").getContext("2d");
        
        var lid = $('#report-lecture-filter').val();
        
        if (lid) {
            // Pie Chart
            var counts = { present: 0, late: 0 };
            data.forEach(function(r) {
                if(r.status === 'present') counts.present++;
                if(r.status === 'late') counts.late++;
            });
            
            var pieData = [
                { value: counts.present, color: "#5cb85c", highlight: "#4cae4c", label: "Present" },
                { value: counts.late, color: "#f0ad4e", highlight: "#eea236", label: "Late" }
            ];
            // Chart.js v1 doesn't show labels in legend automatically, but tooltips work
            new Chart(ctx).Pie(pieData);
        } else {
            // Bar Chart
            var lecCounts = {};
            data.forEach(function(r) {
                if (!lecCounts[r.lecture_title]) lecCounts[r.lecture_title] = 0;
                lecCounts[r.lecture_title]++;
            });
            
            var labels = Object.keys(lecCounts);
            var values = labels.map(function(k) { return lecCounts[k]; });
            
            if (labels.length === 0) {
                // Empty chart
                return;
            }

            var barData = {
                labels: labels,
                datasets: [{
                    fillColor: "rgba(151,187,205,0.5)",
                    strokeColor: "rgba(151,187,205,0.8)",
                    highlightFill: "rgba(151,187,205,0.75)",
                    highlightStroke: "rgba(151,187,205,1)",
                    data: values
                }]
            };
            new Chart(ctx).Bar(barData);
        }
    }

    // --- Manual Add ---
    $('#manual-input').on('input', function() {
        $('#btn-manual-add').prop('disabled', $(this).val().trim() === '');
    });

    $('#btn-manual-add').click(function() {
        var val = $('#manual-input').val().trim();
        var lid = $('#scan-lecture-select').val();
        
        if (!lid) {
            alert('Please select a lecture first.');
            return;
        }
        
        $.post('attendance_handler.php', {
            action: 'manual_attendance',
            lecture_id: lid,
            email_or_id: val
        }, function(res) {
            showScanResult(res);
            $('#manual-input').val(''); 
            $('#btn-manual-add').prop('disabled', true);
        }, 'json').fail(function() {
            alert("Network error");
        });
    });

    $('#btn-export-csv').click(function() {
        var rows = [['Time', 'Lecture', 'Student Name', 'Email', 'Status']];
        $('#report-list tr').each(function() {
            if($(this).find('td').length > 1) { // Skip "No records" row
                var row = [];
                $(this).find('td').each(function() {
                    row.push('"' + $(this).text().replace(/"/g, '""') + '"');
                });
                rows.push(row);
            }
        });
        
        var csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "attendance_report.csv");
        document.body.appendChild(link);
        link.click();
    });
});
</script>

</body>
</html>

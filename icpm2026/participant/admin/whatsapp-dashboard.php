<?php
session_start();
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}
include 'dbconnection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Bulk Messaging | ICPM 2026</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
        .wa-status-box { padding: 20px; border-radius: 5px; text-align: center; margin-bottom: 20px; }
        .status-ready { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .status-disconnected { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .status-waiting { background-color: #fcf8e3; color: #8a6d3b; border: 1px solid #faebcc; }
        #qr-container img { max-width: 250px; border: 5px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .queue-stats { display: flex; justify-content: space-around; margin-top: 15px; }
        .stat-item { text-align: center; }
        .stat-val { font-size: 24px; font-weight: bold; }
        .stat-label { font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <section id="container">
        <header class="header black-bg">
              <div class="sidebar-toggle-box">
                  <div class="fa fa-bars tooltips" data-placement="right" data-original-title="Toggle Navigation"></div>
              </div>
            <a href="#" class="logo"><b>Admin Dashboard</b></a>
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
                      <a href="whatsapp-dashboard.php" class="active">
                          <i class="fa fa-comments"></i>
                          <span>WhatsApp Bulk</span>
                      </a>
                  </li>
              </ul>
          </div>
      </aside>

        <section id="main-content">
            <section class="wrapper">
                <h3><i class="fa fa-comments"></i> WhatsApp Bulk Messaging</h3>
                
                <div class="row">
                    <!-- Service Status -->
                    <div class="col-md-4">
                        <div class="content-panel" style="padding: 15px;">
                            <h4><i class="fa fa-plug"></i> Connection Status</h4>
                            <div id="status-box" class="wa-status-box status-disconnected">
                                <span id="status-text">Checking...</span>
                            </div>
                            <div id="qr-container" style="display:none; text-align: center;">
                                <p>Scan this QR code with WhatsApp on your phone</p>
                                <img id="qr-image" src="" alt="QR Code">
                            </div>
                            <div class="text-center" style="margin-top: 10px;">
                                <button class="btn btn-danger btn-sm" onclick="logoutWhatsApp()"><i class="fa fa-sign-out"></i> Logout Device</button>
                                <button class="btn btn-default btn-sm" onclick="checkStatus()"><i class="fa fa-refresh"></i> Refresh Status</button>
                                <button class="btn btn-warning btn-sm" onclick="initDB()"><i class="fa fa-database"></i> Init DB</button>
                            </div>
                        </div>
                    </div>

                    <!-- Queue Management -->
                    <div class="col-md-8">
                        <div class="content-panel" style="padding: 15px;">
                            <h4><i class="fa fa-tasks"></i> Queue Management</h4>
                            
                            <div class="queue-stats">
                                <div class="stat-item">
                                    <div class="stat-val" id="stat-pending">0</div>
                                    <div class="stat-label">Pending</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-val" id="stat-sent">0</div>
                                    <div class="stat-label">Sent</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-val" id="stat-failed">0</div>
                                    <div class="stat-label">Failed</div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5><i class="fa fa-flask"></i> Test Mode</h5>
                            <div class="input-group">
                                <input type="text" id="test-phone" class="form-control" placeholder="Phone (e.g., 971501234567)">
                                <span class="input-group-btn">
                                    <button class="btn btn-warning" type="button" onclick="sendTestMessage()">Send Test</button>
                                </span>
                            </div>

                            <hr>

                            <div class="text-center" style="margin-bottom: 20px;">
                                <button id="btn-process" class="btn btn-primary btn-lg" onclick="toggleProcessing()">
                                    <i class="fa fa-play"></i> Start Processing Queue
                                </button>
                                <p class="help-block mt-2">Sends 1 message every 2-5 seconds (Safe Mode)</p>
                                <div id="process-log" style="max-height: 150px; overflow-y: auto; background: #f9f9f9; border: 1px solid #ddd; padding: 10px; text-align: left; font-family: monospace; font-size: 11px; margin-top: 10px;"></div>
                            </div>

                            <hr>
                            
                            <h5>Add to Queue (Database)</h5>
                            <div class="form-group">
                                <label>Recipient Criteria</label>
                                <select class="form-control" id="queue-criteria">
                                    <option value="all_participants">All Participants</option>
                                    <option value="pending_certificates">Participants with Pending Certificates</option>
                                    <option value="speakers">Speakers Only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Schedule (Optional)</label>
                                <input type="datetime-local" class="form-control" id="queue-schedule">
                                <span class="help-block">Leave blank to send immediately</span>
                            </div>
                            <div class="form-group">
                                <label>Message Template</label>
                                <textarea class="form-control" id="queue-message" rows="15">International Conference of Pharmacy and Medicine (ICPM)

Thank you for participating at ICPM 14 - 2026

Dear {name},

We sincerely appreciate your participation in the 14th International Conference of pharmacy and medcine (ICPM).
We are pleased to provide you with your Certificate of Attendance, which is attached to this message.
We hope you found the sessions insightful and valuable.

To activate your certificate please download ICPM Mobile app and login

Download From the App Store:
https://apps.apple.com/ae/app/icpm/id6757741792

Download For Android:
https://regsys.cloud/download.html

*NB: Looking to see you at ICPM 15 - 2027
Date: 27,28 March 2027
Venue: Dubai - UAE ( V Hotel Dubai )

Best Regards,
ICPM Organizing Committee</textarea>
                                <span class="help-block">Available variables: {name}, {certificate_link} (PDF will be attached automatically)</span>
                            </div>
                            <button class="btn btn-primary" onclick="addToQueue()">Add to Queue</button>
                            <button class="btn btn-danger pull-right" onclick="clearQueue()">Clear Pending Queue</button>
                        </div>
                    </div>
                </div>

                <!-- Bulk Upload Section -->
                <div class="row mt">
                    <div class="col-md-12">
                        <div class="content-panel" style="padding: 15px;">
                            <h4><i class="fa fa-upload"></i> Bulk Upload from CSV</h4>
                            <p>Upload a CSV file with columns: <b>Name</b>, <b>Phone</b> (or Mobile/Contact)</p>
                            <form id="uploadForm" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <input type="file" name="file" class="form-control" accept=".csv" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-info"><i class="fa fa-eye"></i> Preview Recipients</button>
                                    </div>
                                </div>
                            </form>
                            
                            <div id="preview-section" style="display:none; margin-top: 20px;">
                                <hr>
                                <h5>Preview (<span id="preview-count">0</span> recipients)</h5>
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-striped table-advance table-hover">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="selectAllPreview" checked></th>
                                                <th>Name</th>
                                                <th>Phone</th>
                                                <th>Original Input</th>
                                            </tr>
                                        </thead>
                                        <tbody id="preview-body"></tbody>
                                    </table>
                                </div>
                                <div class="form-group mt">
                                    <label>Message Template</label>
                                    <textarea class="form-control" id="bulk-message" rows="15">International Conference of Pharmacy and Medicine (ICPM)

Thank you for participating at ICPM 14 - 2026

Dear {name},

We sincerely appreciate your participation in the 14th International Conference of pharmacy and medcine (ICPM).
We are pleased to provide you with your Certificate of Attendance, which is attached to this message.
We hope you found the sessions insightful and valuable.

To activate your certificate please download ICPM Mobile app and login

Download From the App Store:
https://apps.apple.com/ae/app/icpm/id6757741792

Download For Android:
https://regsys.cloud/download.html

*NB: Looking to see you at ICPM 15 - 2027
Date: 27,28 March 2027
Venue: Dubai - UAE ( V Hotel Dubai )

Best Regards,
ICPM Organizing Committee</textarea>
                                    <span class="help-block">Available variables: {name}, {certificate_link} (PDF will be attached automatically if certificate available)</span>
                                </div>
                                <button class="btn btn-success" onclick="addToQueueFromUpload()"><i class="fa fa-plus"></i> Add Selected to Queue</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt">
                    <!-- Bulk Compose -->
                    <div class="col-md-12">
                        <div class="content-panel" style="padding: 15px;">
                            <h4><i class="fa fa-envelope"></i> Compose Bulk Message</h4>
                            <div class="form-horizontal">
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Recipients</label>
                                    <div class="col-sm-10">
                                        <select class="form-control" id="recipient-source">
                                            <option value="all">All Participants (with Phone Numbers)</option>
                                            <option value="pending_cert">Participants Pending Certificate</option>
                                            <!-- Future: Upload CSV -->
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2 control-label">Message Template</label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" id="msg-template" rows="6">Dear {name},

Thank you for participating in ICPM 2026.
Please download your certificate here: {certificate_link}

Best regards,
ICPM Organizing Committee</textarea>
                                        <span class="help-block">Available placeholders: <code>{name}</code>, <code>{email}</code>, <code>{certificate_link}</code></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-2 col-sm-10">
                                        <button class="btn btn-success" onclick="addToQueue()"><i class="fa fa-plus-circle"></i> Add to Queue</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </section>
        </section>
    </section>

    <!-- Scripts -->
    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.scrollTo.min.js"></script>
    <script src="assets/js/common-scripts.js"></script>
    
    <script>
        let isProcessing = false;
        let processInterval = null;

        function sendTestMessage() {
            let phone = $('#test-phone').val();
            if(!phone) { alert('Enter phone number'); return; }
            
            $.post('whatsapp_handler.php', {
                action: 'send_test',
                phone: phone
            }, function(res) {
                alert(res.message);
            }, 'json');
        }

        function checkStatus() {
            $.post('whatsapp_handler.php', { action: 'get_status' }, function(data) {
                if (data.status === 'READY' || data.status === 'AUTHENTICATED') {
                    $('#status-box').removeClass().addClass('wa-status-box status-ready').html('<h3><i class="fa fa-check-circle"></i> Connected</h3>');
                    $('#qr-container').hide();
                } else if (data.status === 'WAITING_FOR_SCAN' || data.qr) {
                    $('#status-box').removeClass().addClass('wa-status-box status-waiting').html('<h3><i class="fa fa-qrcode"></i> Scan QR Code</h3>');
                    $('#qr-image').attr('src', data.qr);
                    $('#qr-container').show();
                    setTimeout(checkStatus, 3000); // Poll frequently while waiting
                } else {
                    let errorMsg = data.message || data.status || 'Disconnected';
                    $('#status-box').removeClass().addClass('wa-status-box status-disconnected').html('<h3><i class="fa fa-exclamation-triangle"></i> Error</h3><p>' + errorMsg + '</p>');
                    $('#qr-container').hide();
                }
            }, 'json').fail(function() {
                 $('#status-box').removeClass().addClass('wa-status-box status-disconnected').html('<h3><i class="fa fa-times-circle"></i> Service Offline</h3><p>Ensure Node.js service is running</p>');
            });
            
            updateStats();
        }

        function logoutWhatsApp() {
            if(!confirm('Are you sure you want to disconnect?')) return;
            $.post('whatsapp_handler.php', { action: 'logout' }, function(data) {
                alert(data.message);
                checkStatus();
            }, 'json');
        }

        function initDB() {
            $.get('setup_whatsapp_db.php?json=1', function(res) {
                let msg = res.messages ? res.messages.join('\n') : 'Database initialized';
                alert(msg);
            }, 'json').fail(function() {
                alert('Error initializing database');
            });
        }

        function updateStats() {
            $.post('whatsapp_handler.php', { action: 'get_queue_stats' }, function(res) {
                if(res.status === 'success') {
                    $('#stat-pending').text(res.data.pending || 0);
                    $('#stat-processing').text(res.data.processing || 0);
                    $('#stat-sent').text(res.data.sent || 0);
                    $('#stat-failed').text(res.data.failed || 0);
                    
                    if ((res.data.pending || 0) == 0 && isProcessing) {
                        toggleProcessing(); // Stop if done
                        log("Queue completed.");
                    }
                }
            }, 'json');
        }

        function addToQueue() {
            let criteria = $('#queue-criteria').val();
            let msg = $('#queue-message').val();
            let schedule = $('#queue-schedule').val();
            
            if(!confirm('This will add messages to the queue. Continue?')) return;
            
            $.post('whatsapp_handler.php', { 
                action: 'add_bulk_by_criteria', 
                criteria: criteria, 
                message: msg,
                scheduled_at: schedule
            }, function(res) {
                alert(res.message);
                updateStats();
            }, 'json').fail(function() {
                alert('Error adding to queue');
            });
        }
        
        function addToQueueFromUpload() {
            let selectedRecipients = [];
            $('.preview-chk:checked').each(function() {
                selectedRecipients.push({
                    phone: $(this).data('phone'),
                    name: $(this).data('name')
                });
            });
            
            if (selectedRecipients.length === 0) {
                alert('Please select at least one recipient.');
                return;
            }
            
            let msg = $('#bulk-message').val();
            
            if (!confirm('Add ' + selectedRecipients.length + ' recipients to queue?')) return;
            
            $.post('whatsapp_handler.php', {
                action: 'add_bulk_csv',
                recipients: JSON.stringify(selectedRecipients),
                message: msg
            }, function(res) {
                alert(res.message);
                $('#preview-section').hide();
                $('#uploadForm')[0].reset();
                updateStats();
            }, 'json');
        }

        function clearQueue() {
            if(!confirm('Are you sure you want to clear the PENDING queue?')) return;
             $.post('whatsapp_handler.php', { action: 'clear_queue' }, function(res) {
                alert(res.message);
                updateStats();
            }, 'json');
        }

        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: 'whatsapp_bulk_parser.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        $('#preview-section').show();
                        $('#preview-count').text(res.count);
                        var html = '';
                        res.recipients.forEach(function(r, idx) {
                            html += '<tr>';
                            html += '<td><input type="checkbox" class="preview-chk" value="'+idx+'" checked data-phone="'+r.phone+'" data-name="'+r.name+'"></td>';
                            html += '<td>'+r.name+'</td>';
                            html += '<td>'+r.phone+'</td>';
                            html += '<td>'+r.original_phone+'</td>';
                            html += '</tr>';
                        });
                        $('#preview-body').html(html);
                    } else {
                        alert(res.message);
                    }
                }
            });
        });

        function toggleProcessing() {
            if (isProcessing) {
                isProcessing = false;
                $('#btn-process').removeClass('btn-danger').addClass('btn-primary').html('<i class="fa fa-play"></i> Start Processing Queue');
                clearInterval(processInterval);
                log("Processing paused.");
            } else {
                isProcessing = true;
                $('#btn-process').removeClass('btn-primary').addClass('btn-danger').html('<i class="fa fa-pause"></i> Pause Processing');
                processInterval = setInterval(processBatch, 5000); // 5 seconds per batch
                processBatch(); // Run immediately
                log("Processing started...");
            }
        }

        function processBatch() {
            if (!isProcessing) return;
            $('#process-log').append('<div>Processing batch...</div>');
            $.post('whatsapp_handler.php', { action: 'process_queue' }, function(res) {
                if (res.status === 'success') {
                    log(`Processed: ${res.processed}, Errors: ${res.errors}, Remaining: ${res.remaining}`);
                    updateStats();
                } else {
                    log(`Error: ${res.message}`);
                }
            }, 'json').fail(function() {
                log("Network error processing batch.");
            });
            
            // Scroll to bottom
            let d = $('#process-log');
            d.scrollTop(d.prop("scrollHeight"));
        }

        function log(msg) {
            let time = new Date().toLocaleTimeString();
            $('#process-log').append(`<div>[${time}] ${msg}</div>`);
            let d = $('#process-log');
            d.scrollTop(d.prop("scrollHeight"));
        }

        // Init
        $(document).ready(function() {
            checkStatus();
            // Poll status every 10s
            setInterval(function() { if(!isProcessing) checkStatus(); }, 10000);
        });
    </script>
</body>
</html>

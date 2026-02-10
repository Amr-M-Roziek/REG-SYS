<?php
session_start();
// Include Unit Tests logic (but capturing output)
ob_start();
include 'test_rbac_unit.php';
$unit_test_output = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin System Tests</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .test-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 4px; }
        .pass { color: green; }
        .fail { color: red; }
    </style>
</head>
<body>
    <h1>Admin System Test Runner</h1>
    
    <div class="test-section">
        <h3>1. Unit Tests (RBAC Logic)</h3>
        <div class="well">
            <?php echo $unit_test_output; ?>
        </div>
    </div>

    <div class="test-section">
        <h3>2. API Integration Tests</h3>
        <p>These tests make real AJAX calls to <code>ajax_handler.php</code>. Ensure you are logged in as Admin.</p>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Test Case</th>
                    <th>Action</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Get User Data (ID: <?php echo $_SESSION['id'] ?? 'Current'; ?>)</td>
                    <td><button class="btn btn-primary btn-sm" onclick="runTest('get_user_data', {uid: <?php echo $_SESSION['id'] ?? 1; ?>})">Run Test</button></td>
                    <td><pre id="res-get_user_data">Waiting...</pre></td>
                </tr>
                <tr>
                    <td>Get Certificate Templates</td>
                    <td><button class="btn btn-primary btn-sm" onclick="runTest('get_templates', {})">Run Test</button></td>
                    <td><pre id="res-get_templates">Waiting...</pre></td>
                </tr>
                <tr>
                    <td>Invalid Action Check</td>
                    <td><button class="btn btn-warning btn-sm" onclick="runTest('invalid_action_xyz', {})">Run Test</button></td>
                    <td><pre id="res-invalid">Waiting...</pre></td>
                </tr>
            </tbody>
        </table>
    </div>

    <script src="../assets/js/jquery.js"></script>
    <script>
    function runTest(action, data) {
        data.action = action;
        $(`#res-${action === 'invalid_action_xyz' ? 'invalid' : action}`).text('Running...');
        
        $.post('../ajax_handler.php', data, function(response) {
            $(`#res-${action === 'invalid_action_xyz' ? 'invalid' : action}`).text(JSON.stringify(response, null, 2));
            
            // Simple assertion check visually
            if (response.status === 'success' || (action === 'invalid_action_xyz' && response.status === 'error')) {
                $(`#res-${action === 'invalid_action_xyz' ? 'invalid' : action}`).css('border-left', '5px solid green');
            } else {
                $(`#res-${action === 'invalid_action_xyz' ? 'invalid' : action}`).css('border-left', '5px solid red');
            }
        }, 'json').fail(function(xhr) {
             $(`#res-${action === 'invalid_action_xyz' ? 'invalid' : action}`).text('Request Failed: ' + xhr.statusText);
        });
    }
    </script>
</body>
</html>

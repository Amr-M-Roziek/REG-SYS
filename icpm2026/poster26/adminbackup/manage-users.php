<?php
include'dbconnection.php';

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
    $q = isset($_GET['search']) ? $_GET['search'] : '';
    $pattern = '%' . $q . '%';
    $sql = "SELECT * FROM users WHERE
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
        password COLLATE utf8mb4_bin LIKE ? OR
        contactno COLLATE utf8mb4_bin LIKE ? OR
        userip COLLATE utf8mb4_bin LIKE ? OR
        companyref COLLATE utf8mb4_bin LIKE ? OR
        paypalref COLLATE utf8mb4_bin LIKE ?
        ORDER BY id ASC LIMIT 500";
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            str_repeat('s', 27),
            $pattern, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern,
            $pattern, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern,
            $pattern, $pattern, $pattern, $pattern, $pattern,
            $pattern, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern,
            $pattern
        );
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = false;
    }
    $cnt = 1;
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $cnt . "</td>";
            echo "<td>" . highlight_text($row['id'], $q) . "</td>";
            echo "<td>" . highlight_text($row['fname'], $q) . "</td>";
            echo "<td>" . highlight_text(isset($row['lname']) ? $row['lname'] : '', $q) . "</td>";
            echo "<td>" . highlight_text(isset($row['nationality']) ? $row['nationality'] : '', $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth1name'], $q) . "</td>";
            echo "<td>" . highlight_text(isset($row['coauth1nationality']) ? $row['coauth1nationality'] : '', $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth2name'], $q) . "</td>";
            echo "<td>" . highlight_text(isset($row['coauth2nationality']) ? $row['coauth2nationality'] : '', $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth3name'], $q) . "</td>";
            echo "<td>" . highlight_text(isset($row['coauth3nationality']) ? $row['coauth3nationality'] : '', $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth4name'], $q) . "</td>";
            echo "<td>" . highlight_text(isset($row['coauth4nationality']) ? $row['coauth4nationality'] : '', $q) . "</td>";
            echo "<td>" . highlight_text(isset($row['coauth5name']) ? $row['coauth5name'] : '', $q) . "</td>";
            echo "<td>" . highlight_text(isset($row['coauth5nationality']) ? $row['coauth5nationality'] : '', $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth1email'], $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth2email'], $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth3email'], $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth4email'], $q) . "</td>";
            echo "<td>" . highlight_text($row['coauth5email'], $q) . "</td>";
            echo "<td>" . highlight_text($row['email'], $q) . "</td>";
            echo "<td>" . highlight_text($row['profession'], $q) . "</td>";
            echo "<td>" . highlight_text($row['organization'], $q) . "</td>";
            echo "<td>" . highlight_text($row['category'], $q) . "</td>";
            echo "<td>" . highlight_text($row['contactno'], $q) . "</td>";
            echo "<td>" . highlight_text($row['password'], $q) . "</td>";
            echo "<td>" . highlight_text($row['companyref'], $q) . "</td>";
            echo "<td>" . escape_html($row['posting_date']) . "</td>";
            echo "<td>";
            echo '<a href="welcome.php?uid=' . escape_html($row['id']) . '"><button class="btn btn-primary btn-xs" aria-label="Print profile"><i class="fa fa-print"></i></button></a> ';
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

session_start();

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
        $data = mysqli_query($con, "SELECT * FROM `".$table."`");
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
$adminid=$_GET['id'];
$msg=mysqli_query($con,"delete from users where id='$adminid'");
if($msg)
{
echo "<script>alert('Data deleted');</script>";
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
    </style>
  </head>



  <body>



  <section id="container" >

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
                      <div style="margin-top:10px;">
                        <a href="manage-users.php?export_db=1&mode=download" class="btn btn-success" aria-label="Export database">Export Database</a>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-12">

                      <div class="content-panel">
                          <table id="users-table" class="table table-striped table-advance table-hover">
	                  	  	  <h4><i class="fa fa-angle-right"></i> All User Details </h4>
	                  	  	  <hr>

                              <div style="margin-bottom:10px;">
                                <a href="manage-users.php?export_excel=1" class="btn btn-info" aria-label="Export to Excel">Export to Excel</a>
                              </div>
                              <thead>

                              <tr>

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
                                  <th> Email Id</th>
                                  <th> Profession</th>
                                  <th> Univerisity</th>
                                  <th> Category</th>
                                  <th>Contact no.</th>

                                  <th>Password</th>

                                  <th>Company ref</th>
                                  <th>Reg. Date</th>

                              </tr>

                              </thead>

                              <tbody>

                              <?php $ret=mysqli_query($con,"select * from users");

							  $cnt=1;

							  while($row=mysqli_fetch_array($ret))
							  {?>

                              <tr>

                              <td><?php echo $cnt;?></td>

                                  <td><?php echo $row['id'];?></td>

                                  <td><?php echo $row['fname'];?></td>
                                  <td><?php echo isset($row['lname']) ? htmlspecialchars($row['lname']) : '';?></td>
                                  <td><?php echo isset($row['nationality']) ? htmlspecialchars($row['nationality']) : '';?></td>
                                  <td><?php echo $row['coauth1name'];?></td>
                                  <td><?php echo isset($row['coauth1nationality']) ? htmlspecialchars($row['coauth1nationality']) : '';?></td>
                                   <td><?php echo $row['coauth2name'];?></td>
                                  <td><?php echo isset($row['coauth2nationality']) ? htmlspecialchars($row['coauth2nationality']) : '';?></td>
                                  <td><?php echo $row['coauth3name'];?></td>
                                  <td><?php echo isset($row['coauth3nationality']) ? htmlspecialchars($row['coauth3nationality']) : '';?></td>
                                  <td><?php echo $row['coauth4name'];?></td>
                                  <td><?php echo isset($row['coauth4nationality']) ? htmlspecialchars($row['coauth4nationality']) : '';?></td>
                                  <td><?php echo isset($row['coauth5name']) ? htmlspecialchars($row['coauth5name']) : '';?></td>
                                  <td><?php echo isset($row['coauth5nationality']) ? htmlspecialchars($row['coauth5nationality']) : '';?></td>
                                  <td><?php echo $row['coauth1email'];?></td>
                                  <td><?php echo $row['coauth2email'];?></td>
                                  <td><?php echo $row['coauth3email'];?></td>
                                  <td><?php echo $row['coauth4email'];?></td>
                                  <td><?php echo $row['coauth5email'];?></td>
                                  <td><?php echo $row['email'];?></td>
                                  <td><?php echo $row['profession'];?></td>
                                  <td><?php echo $row['organization'];?></td>
                                  <td><?php echo $row['category'];?></td>
                                  <td><?php echo $row['contactno'];?></td>

                                  <td><?php echo $row['password'];?></td>

                                  <td><?php echo $row['companyref'];?></td>

                                  <td><?php echo $row['posting_date'];?></td>

                                  <td>

                                     <a href="welcome.php?uid=<?php echo $row['id'];?>">

                                     <button class="btn btn-primary btn-xs"><i class="fa fa-print"></i></button></a>

                                     <a href="update-profile.php?uid=<?php echo $row['id'];?>"> 

                                     <button class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></button></a>

                                     <a href="manage-users.php?id=<?php echo $row['id'];?>"> 

                                     <button class="btn btn-danger btn-xs" onClick="return confirm('Do you really want to delete');"><i class="fa fa-trash-o "></i></button></a>

                                  </td>

                              </tr>

                              <?php $cnt=$cnt+1; }?>

                              </tbody>

                          </table>

                      </div>

                  </div>

              </div>

		</section>

      </section

  ></section>

    <script src="assets/js/jquery.js"></script>

    <script src="assets/js/bootstrap.min.js"></script>

    <script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>

    <script src="assets/js/jquery.scrollTo.min.js"></script>

    <script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>

    <script src="assets/js/common-scripts.js"></script>
  <script>
      $(function(){
          $('select.styled').customSelect();
      });

      (function(){
        var $input = $('#users-search');
        var $clear = $('#clear-search');
        var $tbody = $('#users-table tbody');
        var $status = $('#search-status');
        var timer = null;
        function updateStatus(text){ $status.text(text); }
        function fetchRows(q){
          $.ajax({
            url: 'manage-users.php',
            method: 'GET',
            data: { ajax: 1, search: q },
            success: function(html){
              $tbody.html(html);
              var count = $tbody.find('tr').length;
              if (count === 1 && $tbody.find('td').length === 1) {
                updateStatus('No results found');
              } else {
                updateStatus('Showing ' + count + ' result' + (count===1?'':'s'));
              }
            },
            error: function(){
              updateStatus('Error fetching results');
            }
          });
        }
        $input.on('input', function(){
          var q = $input.val();
          if (timer) { clearTimeout(timer); }
          timer = setTimeout(function(){ fetchRows(q); }, 300);
        });
        $clear.on('click', function(){
          $input.val('');
          fetchRows('');
          $input.focus();
        });
        $input.on('keydown', function(e){
          if (e.key === 'Escape') {
            $clear.click();
          }
        });
      })();

  </script>



  </body>

</html>

<?php } ?>

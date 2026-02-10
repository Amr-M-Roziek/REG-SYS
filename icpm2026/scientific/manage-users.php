<?php

session_start();

include'dbconnection.php';

// checking session is valid for not 

if (strlen($_SESSION['id']==0)) {

  header('location:logout.php');

  } else{



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

                      <div class="content-panel">

                          <table class="table table-striped table-advance table-hover">

	                  	  	  <h4><i class="fa fa-angle-right"></i> All User Details </h4>
                              <div style="float: right; margin-bottom: 10px; margin-right: 10px;">
                                  <form method="GET" action="" class="form-inline">
                                      <label for="limit">Rows per page: </label>
                                      <select name="limit" id="limit" class="form-control" onchange="this.form.submit()">
                                          <option value="25" <?php if(isset($_GET['limit']) && $_GET['limit'] == '25') echo 'selected'; ?>>25</option>
                                          <option value="50" <?php if(isset($_GET['limit']) && $_GET['limit'] == '50') echo 'selected'; ?>>50</option>
                                          <option value="100" <?php if(!isset($_GET['limit']) || $_GET['limit'] == '100') echo 'selected'; ?>>100</option>
                                          <option value="250" <?php if(isset($_GET['limit']) && $_GET['limit'] == '250') echo 'selected'; ?>>250</option>
                                          <option value="500" <?php if(isset($_GET['limit']) && $_GET['limit'] == '500') echo 'selected'; ?>>500</option>
                                          <option value="1000" <?php if(isset($_GET['limit']) && $_GET['limit'] == '1000') echo 'selected'; ?>>1000</option>
                                          <option value="ALL" <?php if(isset($_GET['limit']) && $_GET['limit'] == 'ALL') echo 'selected'; ?>>ALL</option>
                                      </select>
                                  </form>
                              </div>
                              <div style="margin-bottom:10px;">
                                <a href="manage-users.php?export_excel=1" class="btn btn-info" aria-label="Export to Excel">Export to Excel</a>
                              </div>

	                  	  	  <hr>

                              <thead>

                              <tr>

                                  <th>Sno.</th>

                                  <th> Ref Number</th>

                                  <th class="hidden-phone">Main Auth</th>

                                  <th> Co-auth 1</th>

                                  <th> Co-auth 1</th>

                                  <th> Email Id</th>

                                  <th> Profession</th>

                                  <th> Univerisity</th>

                                  <th> Category</th>

                                  <th>Contact no.</th>

                                  <th>Password</th>

                                  <th>Company ref</th>

                                  <th>Paypal ref</th>

                                  <th>Reg. Date</th>

                              </tr>

                              </thead>

                              <tbody>
                              <?php 
                              $limitArg = isset($_GET['limit']) ? $_GET['limit'] : '100';
                              $sql = "select * from users";
                              if ($limitArg !== 'ALL') {
                                  $sql .= " LIMIT " . intval($limitArg);
                              }
                              $ret=mysqli_query($con, $sql);
							  $cnt=1;
							  while($row=mysqli_fetch_array($ret))
							  {?>

                              <tr>

                              <td><?php echo $cnt;?></td>

                                  <td><?php echo $row['id'];?></td>

                                  <td><?php echo $row['fname'];?></td>

                                  <td><?php echo $row['lname'];?></td>

                                  <td><?php echo $row['email'];?></td>

                                  <td><?php echo $row['profession'];?></td>

                                  <td><?php echo $row['organization'];?></td>

                                  <td><?php echo $row['category'];?></td>

                                  <td><?php echo $row['contactno'];?></td>

                                  <td><?php echo $row['password'];?></td>

                                  <td><?php echo $row['companyref'];?></td>

                                  <td><?php echo $row['paypalref'];?></td>

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



  </script>



  </body>

</html>

<?php } ?>
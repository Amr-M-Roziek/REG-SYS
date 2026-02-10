<?php
session_start();
if (strlen($_SESSION['id']==0)) {
  header('location:logout.php');
  } else{

?><!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Welcome </title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/heroic-features.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="https://icpm.ae/" style="padding: 5px 15px;">
                    <img src="https://reg-sys.com/icpm2026/images/icpm-logo.png" alt="ICPM 2026 Conference Logo" style="height: 40px; width: auto;">
                </a>
                <a class="navbar-brand" href="#">Welcome !</a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="#"><?php echo $_SESSION['name'];?></a>
                    </li>
                    <li>
                        <a href="logout.php">Logout</a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    <div class="container" style="text-align: center;">
        <header class="jumbotron hero-spacer">
            <h1>Welcome! <?php echo $_SESSION['name'];?> <?php echo $_SESSION['slname'];?></h1>

            <h1>Reg No: <?php echo $_SESSION['id'];?></h1>
            <h1> <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $_SESSION['id'];?>" title="Your reference Number is <?php echo $_SESSION['id'];?>" /></h1>
            <br>
                <h1 style="text-transform:uppercase">Participant</h1>
            <div>
            <p>Welcome To ICPM 2026</p>
            </div>
              <p><a  href="logout.php" class="btn btn-primary btn-large"> Logout </a>  <a  href="https://icpm.ae/" class="btn btn-primary btn-large"> Back Home </a>
              <button id="btn-download-cert" class="btn btn-success btn-large" onclick="generateCertificate()"><i class="glyphicon glyphicon-download-alt"></i> Download Certificate</button>
            </p>
        </header>

  </div>

    </div>
    
    <!-- Certificate Rendering Container -->
    <div id="cert-render-container" style="position: fixed; top: -10000px; left: -10000px; width: 1123px; height: 794px; background: white; overflow: hidden;"></div>

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script>
        const userData = <?php echo json_encode($user); ?>;
        const templateData = <?php echo $templateData; ?>;
        
        async function generateCertificate() {
            if (!templateData || templateData.length === 0) {
                alert("Certificate template not available.");
                return;
            }
            
            const btn = document.getElementById('btn-download-cert');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></i> Generating...';
            btn.disabled = true;
            
            try {
                const container = document.getElementById('cert-render-container');
                container.innerHTML = '';
                
                // Render Template
                templateData.forEach(data => {
                    const div = document.createElement('div');
                    div.id = data.id;
                    div.className = 'cert-element';
                    div.style.cssText = data.style;
                    div.style.position = 'absolute'; // Ensure absolute positioning
                    div.style.border = 'none'; // Remove any edit borders
                    
                    // Fix Image Paths
                    // Admin saves paths relative to admin/, e.g., "assets/img/..." or "../../images/..."
                    // We are in participant/, so:
                    // "assets/img/..." -> "admin/assets/img/..."
                    // "../../images/..." -> "../images/..."
                    
                    if (div.style.backgroundImage) {
                        div.style.backgroundImage = div.style.backgroundImage.replace('url("assets/img/', 'url("admin/assets/img/').replace('url("assets/img/', 'url("admin/assets/img/'); // basic replace
                    }
                    
                    // Handle Content
                    if (data.type === 'image' && data.src) {
                         const img = document.createElement('img');
                         let src = data.src;
                         if (src.indexOf('assets/img/') === 0) {
                             src = 'admin/' + src;
                         } else if (src.indexOf('../../images/') === 0) {
                             src = '../images/' + src.substring(13); // remove ../../images/
                         }
                         img.src = src;
                         img.style.width = '100%';
                         img.style.height = '100%';
                         div.appendChild(img);
                    } else {
                        // Text Content with Variable Replacement
                        let text = data.content || '';
                        
                        if (data.dataVariable) {
                            switch(data.dataVariable) {
                                case 'name':
                                    const isParticipant = (userData.category && userData.category.toLowerCase().indexOf('participant') !== -1);
                                    text = isParticipant ? "« " + userData.fname + ' ' + userData.lname + " »" : userData.fname + ' ' + userData.lname;
                                    break;
                                case 'id':
                                    text = userData.id;
                                    break;
                                case 'ref_no':
                                    text = userData.id;
                                    break;
                                case 'category':
                                    text = userData.category;
                                    break;
                            }
                        }
                        div.innerText = text;
                    }
                    
                    container.appendChild(div);
                });
                
                // Add QR Code if needed (simple placeholder if library not present, or skip)
                // Assuming template has QR container or we just skip it for now if not in template data
                
                // Generate PDF
                const canvas = await html2canvas(container, { 
                    scale: 2, 
                    useCORS: true, 
                    logging: false,
                    allowTaint: true
                });
                
                const imgData = canvas.toDataURL('image/jpeg', 0.9);
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('l', 'mm', 'a4');
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                
                pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('Certificate_' + userData.id + '.pdf');
                
            } catch (e) {
                console.error(e);
                alert("An error occurred while generating the certificate.");
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>
<?php } ?>

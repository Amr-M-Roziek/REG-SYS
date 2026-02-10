<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Instantiation and passing [ICODE]true[/ICODE] enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = 2;                                       // Enable verbose debug output
    $mail->isSMTP();                                            // Set mailer to use SMTP
    $mail->Host       = 'reg-sys.com';  // Specify main and backup SMTP servers
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'icpm2025@reg-sys.com';                     // SMTP username
    $mail->Password   = 'icpm@2025';                               // SMTP password
    $mail->SMTPSecure = 'ssl';                                  // Enable TLS encryption, [ICODE]ssl[/ICODE] also accepted
    $mail->Port       = 465;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('amrroziek@gmail.com', 'Mailer');
    // $mail->addAddress('amrroziek@gmail.com', 'amr roziek');     // Add a recipient

    // Attachments
    // $mail->addAttachment('/home/regsys/attachment.txt');         // Add attachments
    // $mail->addAttachment('/home/regsys/image.jpg', 'new.jpg');    // Optional name

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = '		You have been Registered Successfully
        Please go to login page
        To login too your account
        ---------------
        Important note:
        ---------------
        Please register through the website before coming to the Event.
        Please save your registration reference number you can get it by login in at: https://icpm.ae/regnew/
                    then select Login
                     <b>in bold!</b>';
    $mail->AltBody = '		You have been Registered Successfully
        Please go to login page
        To login too your account
        ---------------
        Important note:
        ---------------
        Please register through the website before coming to the Event.
        Please save your registration reference number you can get it by login in at: https://icpm.ae/regnew/
                    then select Login';

    $mail->send();
    echo 'Message has been sent';

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

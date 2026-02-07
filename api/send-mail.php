<?php
use PHPMailer\PHPMailer\PHPMailer;

require '../vendor/autoload.php';

$data = json_decode(file_get_contents('../storage/answers.json'), true);

$mail = new PHPMailer(true);
$mail->setFrom('anaskedada20@gmail.com', 'From My Heart 💖');
$mail->addAddress($data['anaskedada20@gmail.com']);
$mail->Subject = 'A Gift Made Just For You 💕';

$mail->Body = "
I made this little gift just for you.
Thank you for being you 💖
";

$mail->addAttachment('../storage/pdf/love-profile.pdf');
$mail->send();

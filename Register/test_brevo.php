<?php
require __DIR__ . "/brevo_mailer.php";
$config = require __DIR__ . "/mail_config.php";

$to = $_GET["to"] ?? $config["from_email"];
$name = "Test User";
$subject = "Brevo API Test";
$html = "<p>This is a test email from ICSS CRM.</p>";
$text = "This is a test email from ICSS CRM.";

$result = brevo_send_email($config, $to, $name, $subject, $html, $text);
header("Content-Type: application/json");
echo json_encode($result);

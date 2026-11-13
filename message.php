<?php
/**
 * Kalki Media Group - Contact Form Processor
 * Handles contact form submissions and delivers enquiries to kalkimediagroups@gmail.com
 */

// Error handling settings
error_reporting(0);
ini_set('display_errors', '0');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html');
    exit;
}

// Recipient email address
$recipient_email = 'kalkimediagroups@gmail.com';

// Check if request is AJAX
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

/**
 * Send JSON or Redirect response
 */
function send_response($success, $message, $is_ajax) {
    if ($is_ajax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status'  => $success ? 'success' : 'error',
            'message' => $message
        ]);
        exit;
    } else {
        if ($success) {
            header('Location: contact.html?status=success#contact-form');
        } else {
            header('Location: contact.html?status=error&msg=' . urlencode($message) . '#contact-form');
        }
        exit;
    }
}

// Check honeypot field (anti-spam)
if (!empty($_POST['website_hp'])) {
    // Silently succeed for automated spam bots
    send_response(true, 'Thank you! Your message has been sent successfully.', $is_ajax);
}

// Retrieve and sanitize inputs
$username = isset($_POST['username']) ? trim(strip_tags($_POST['username'])) : '';
$phone    = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : '';
$services = isset($_POST['services']) ? trim(strip_tags($_POST['services'])) : '';
$subject  = isset($_POST['subject']) ? trim(strip_tags($_POST['subject'])) : '';
$message  = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

// Validation
$errors = [];

// Validate Name
if (empty($username)) {
    $errors[] = 'Please enter your name.';
} elseif (mb_strlen($username) < 2) {
    $errors[] = 'Name must be at least 2 characters.';
} elseif (mb_strlen($username) > 100) {
    $errors[] = 'Name must not exceed 100 characters.';
}

// Validate Phone
if (empty($phone)) {
    $errors[] = 'Please enter your phone number.';
} elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number.';
}

// Allowed Services
$valid_services = [
    'Letter Cutting',
    'Offset Printing',
    'Screen Print',
    'Frame',
    'LED Board',
    'Printing',
    'Painting',
    'Seal'
];

// Validate Selected Service
if (empty($services) || strtolower($services) === 'choose services' || !in_array($services, $valid_services, true)) {
    $errors[] = 'Please select a valid service from the dropdown.';
}

// Validate Subject
if (empty($subject)) {
    $errors[] = 'Please enter a subject.';
} elseif (mb_strlen($subject) > 200) {
    $errors[] = 'Subject must not exceed 200 characters.';
}

// Validate Message / Description
if (empty($message)) {
    $errors[] = 'Please enter a product description or message.';
} elseif (mb_strlen($message) > 5000) {
    $errors[] = 'Message must not exceed 5000 characters.';
}

// Return validation errors if any
if (!empty($errors)) {
    send_response(false, implode(' ', $errors), $is_ajax);
}

// Clean fields for email headers (prevent CRLF injection)
$clean_name    = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $username);
$clean_subject = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $subject);
$clean_phone   = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $phone);

// Email subject line
$mail_subject = "New Contact Enquiry: " . $clean_subject . " - from " . $clean_name;

// Prepare HTML content
$date_time     = date('d M Y, h:i A') . ' IST';
$safe_name     = htmlspecialchars($clean_name, ENT_QUOTES, 'UTF-8');
$safe_phone    = htmlspecialchars($clean_phone, ENT_QUOTES, 'UTF-8');
$safe_service  = htmlspecialchars($services, ENT_QUOTES, 'UTF-8');
$safe_subject  = htmlspecialchars($clean_subject, ENT_QUOTES, 'UTF-8');
$safe_message  = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

$email_html = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>New Website Enquiry</title>
</head>
<body style="margin:0; padding:20px; background-color:#f4f6f8; font-family:Arial, sans-serif; color:#333;">
  <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:600px; margin:0 auto; background:#ffffff; border:1px solid #e1e4e8; border-radius:8px; overflow:hidden;">
    <tr>
      <td style="background-color:#1a1a1a; padding:25px 30px; text-align:center; border-bottom:3px solid #fdbb0e;">
        <h1 style="color:#ffffff; font-size:22px; margin:0; font-weight:700; letter-spacing:1px;">KALKI MEDIA GROUP</h1>
        <p style="color:#fdbb0e; font-size:13px; margin:6px 0 0 0; text-transform:uppercase; letter-spacing:0.5px;">New Website Contact Enquiry</p>
      </td>
    </tr>
    <tr>
      <td style="padding:30px;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td style="padding-bottom:15px; border-bottom:1px solid #f0f0f0;">
              <span style="font-size:12px; color:#888888; text-transform:uppercase; font-weight:bold; display:block; margin-bottom:4px;">Customer Name</span>
              <span style="font-size:16px; color:#111111; font-weight:600;">' . $safe_name . '</span>
            </td>
          </tr>
          <tr>
            <td style="padding:15px 0; border-bottom:1px solid #f0f0f0;">
              <span style="font-size:12px; color:#888888; text-transform:uppercase; font-weight:bold; display:block; margin-bottom:4px;">Phone Number</span>
              <a href="tel:' . $safe_phone . '" style="font-size:16px; color:#0066cc; text-decoration:none; font-weight:600;">' . $safe_phone . '</a>
            </td>
          </tr>
          <tr>
            <td style="padding:15px 0; border-bottom:1px solid #f0f0f0;">
              <span style="font-size:12px; color:#888888; text-transform:uppercase; font-weight:bold; display:block; margin-bottom:4px;">Selected Service</span>
              <span style="display:inline-block; background-color:#fff8e1; color:#b78103; padding:5px 12px; border-radius:4px; font-size:14px; font-weight:600; border:1px solid #ffe082;">' . $safe_service . '</span>
            </td>
          </tr>
          <tr>
            <td style="padding:15px 0; border-bottom:1px solid #f0f0f0;">
              <span style="font-size:12px; color:#888888; text-transform:uppercase; font-weight:bold; display:block; margin-bottom:4px;">Subject</span>
              <span style="font-size:15px; color:#222222;">' . $safe_subject . '</span>
            </td>
          </tr>
          <tr>
            <td style="padding:15px 0 5px 0;">
              <span style="font-size:12px; color:#888888; text-transform:uppercase; font-weight:bold; display:block; margin-bottom:6px;">Product Description / Message</span>
              <div style="background-color:#f9fafb; border-left:4px solid #fdbb0e; padding:15px; border-radius:4px; font-size:14px; line-height:1.6; color:#333333;">' . $safe_message . '</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="background-color:#f8f9fa; padding:15px 30px; text-align:center; border-top:1px solid #e9ecef; font-size:12px; color:#777777;">
        Submitted via Kalki Media Group website on ' . $date_time . '
      </td>
    </tr>
  </table>
</body>
</html>';

// Sender domain and address
$server_host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'kalkimediagroups.com';
$from_domain = preg_replace('/^www\./i', '', $server_host);
$from_email  = 'noreply@' . $from_domain;

// Email headers
$headers   = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/html; charset=UTF-8';
$headers[] = 'From: Kalki Media Website <' . $from_email . '>';
$headers[] = 'Reply-To: ' . $recipient_email;
$headers[] = 'X-Mailer: PHP/' . phpversion();

// Send email using PHP mail()
$mail_sent = @mail($recipient_email, $mail_subject, $email_html, implode("\r\n", $headers));

if ($mail_sent) {
    send_response(true, 'Thank you! Your message has been sent successfully. We will contact you soon.', $is_ajax);
} else {
    // If mail server failed to dispatch
    send_response(false, 'Unable to send your message right now due to a server error. Please call us directly or email kalkimediagroups@gmail.com.', $is_ajax);
}
?>

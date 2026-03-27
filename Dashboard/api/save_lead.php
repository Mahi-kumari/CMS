<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config/session.php";
$role = $_GET["role"] ?? $_POST["role"] ?? "";
start_secure_session_from_role($role);

require __DIR__ . "/../../config/crm.php";

$hasUser = !empty($_SESSION["user_id"]);
$hasAdmin = !empty($_SESSION["is_admin"]);
if (!$hasUser && !$hasAdmin) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Unauthorized. Please login."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}
function val($key) {
    if (!isset($_POST[$key])) return null;
    $v = trim((string)$_POST[$key]);
    return $v === "" ? null : $v;
}

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0 && !empty($_SESSION["admin_id"])) {
    $userId = (int)$_SESSION["admin_id"];
}

$data = [
    "user_id" => $userId,

    "enquiry_status" => val("enquiryStatus"),
    "student_type" => val("studentType"),
    "full_name" => val("fullName"),
    "gender" => val("gender"),
    "date_of_birth" => val("dateOfBirth"),
    "phone_number" => val("phoneNumber"),
    "whatsapp_number" => val("whatsappNumber"),
    "email_address" => val("emailAddress"),
    "student_city" => val("studentCity"),
    "student_state" => val("studentState"),
    "student_country" => val("studentCountry"),

    "goal_purpose" => val("goalPurpose"),
    "institution_name" => val("institutionName"),
    "degree" => val("degree"),
    "branch_stream" => val("branchStream"),
    "year_of_study" => val("yearOfStudy"),
    "prior_knowledge" => val("priorKnowledge"),
    "working_details" => val("workingDetails"),

    "source_of_lead" => val("sourceOfLead"),
    "referral_person" => val("referralPerson"),

    "inquiry_date" => val("inquiryDate"),
    "counselor_name" => val("counselorName"),
    "counseling_status" => val("counselingStatus"),
    "follow_up_date" => val("followUpDate"),
    "counselor_notes" => val("counselorNotes"),
    "demo_status" => val("demoStatus"),
    "demo_date" => val("demoDate"),
    "office_visited_date" => val("officeVisitedDate"),

    "course_applied" => val("courseApplied"),
    "training_mode" => val("trainingMode"),
    "center_location" => val("centerLocation"),
    "course_start_date" => val("courseStartDate"),
    "course_end_date" => val("courseEndDate"),
    "next_course_suggested" => val("nextCourseSuggested"),

    "course_fee" => val("courseFee"),
    "discount_applied" => val("discountApplied"),
    "final_fee" => val("finalFee"),
    "voucher_amount" => val("voucherAmount"),
    "payment_mode" => val("paymentMode"),
    "payment_date" => val("paymentDate"),
    "token_status" => val("tokenStatus"),
    "token_amount" => val("tokenAmount"),
    "installment_plan" => val("installmentPlan"),
    "installment_count" => val("installmentCount"),
    "next_payment_date" => val("nextPaymentDate"),
    "total_due_amount" => val("totalDueAmount"),
    "next_payable_amount" => val("nextPayableAmount"),
    "one_time_payment" => val("oneTimePayment"),
    "payment_remark" => val("paymentRemark"),

    "class_allotted" => val("classAllotted"),
    "tentative_joining_month" => val("tentativeJoiningMonth"),
    "preferred_time_slot" => val("preferredTimeSlot"),
    "batch_type" => val("batchType"),
    "class_mode" => val("classMode"),
    "batch_start_date" => val("batchStartDate"),
    "batch_end_date" => val("batchEndDate"),
    "ongoing_batch_course" => val("ongoingBatchCourse"),
    "preferred_language" => val("preferredLanguage"),
    "batch_assigned_date" => val("batchAssignedDate"),
    "batch_assigned_time" => val("batchAssignedTime"),
    "trainer_assigned" => val("trainerAssigned"),

    "receipt_number" => val("receiptNumber"),
    "receipt_student_name" => val("receiptStudentName"),
    "receipt_student_id" => val("receiptStudentId"),
    "receipt_course_name" => val("receiptCourseName"),
    "receipt_batch_name" => val("receiptBatchName"),
    "receipt_amount_paid" => val("receiptAmountPaid"),
    "receipt_payment_mode" => val("receiptPaymentMode"),
    "receipt_transaction_id" => val("receiptTransactionId"),
    "receipt_payment_date" => val("receiptPaymentDate"),
    "receipt_balance_amount" => val("receiptBalanceAmount"),
    "receipt_authorized_signature" => val("receiptAuthorizedSignature"),

    "certificate_issued" => val("certificateIssued"),
    "remark" => val("remark"),
];

if ($data["full_name"] === null || $data["full_name"] === "") {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Full name is required."]);
    exit;
}
if ($data["phone_number"] === null || $data["phone_number"] === "") {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Phone number is required."]);
    exit;
}

function handleReceiptUpload(): ?string {
    if (!isset($_FILES["paymentReceiptPdf"])) {
        return null;
    }
    $file = $_FILES["paymentReceiptPdf"];
    if ($file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file["error"] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if ($ext !== "pdf") {
        return null;
    }
    $dir = __DIR__ . "/../uploads/receipts";
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $safeName = "receipt_" . time() . "_" . bin2hex(random_bytes(4)) . ".pdf";
    $target = $dir . "/" . $safeName;
    if (!move_uploaded_file($file["tmp_name"], $target)) {
        return null;
    }
    return "uploads/receipts/" . $safeName;
}

$data["receipt_pdf_path"] = handleReceiptUpload();

$mysqli = null;
try {
    $mysqli = db_connect();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$placeholders = rtrim(str_repeat('?,', 76), ',');
$sql = "INSERT INTO leads (
    user_id,
    enquiry_status, student_type, full_name, gender, date_of_birth, phone_number, whatsapp_number, email_address,
    student_city, student_state, student_country,
    goal_purpose, institution_name, degree, branch_stream, year_of_study, prior_knowledge, working_details,
    source_of_lead, referral_person,
    inquiry_date, counselor_name, counseling_status, follow_up_date, counselor_notes, demo_status, demo_date,
    office_visited_date,
    course_applied, training_mode, center_location, course_start_date, course_end_date, next_course_suggested,
    course_fee, discount_applied, final_fee, voucher_amount, payment_mode, payment_date, token_status, token_amount,
    installment_plan, installment_count, next_payment_date, total_due_amount, next_payable_amount, one_time_payment,
    payment_remark,
    class_allotted, tentative_joining_month, preferred_time_slot, batch_type, class_mode, batch_start_date,
    batch_end_date, ongoing_batch_course, preferred_language, batch_assigned_date, batch_assigned_time,
    trainer_assigned,
    receipt_number, receipt_student_name, receipt_student_id, receipt_course_name, receipt_batch_name,
    receipt_amount_paid, receipt_payment_mode, receipt_transaction_id, receipt_payment_date, receipt_balance_amount,
    receipt_authorized_signature, receipt_pdf_path,
    certificate_issued, remark
) VALUES ($placeholders)";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Database error.",
        "error" => $mysqli->error
    ]);
    $mysqli->close();
    exit;
}

$stmt->bind_param(
    "i" . str_repeat("s", 75),
    $data["user_id"],
    $data["enquiry_status"],
    $data["student_type"],
    $data["full_name"],
    $data["gender"],
    $data["date_of_birth"],
    $data["phone_number"],
    $data["whatsapp_number"],
    $data["email_address"],
    $data["student_city"],
    $data["student_state"],
    $data["student_country"],
    $data["goal_purpose"],
    $data["institution_name"],
    $data["degree"],
    $data["branch_stream"],
    $data["year_of_study"],
    $data["prior_knowledge"],
    $data["working_details"],
    $data["source_of_lead"],
    $data["referral_person"],
    $data["inquiry_date"],
    $data["counselor_name"],
    $data["counseling_status"],
    $data["follow_up_date"],
    $data["counselor_notes"],
    $data["demo_status"],
    $data["demo_date"],
    $data["office_visited_date"],
    $data["course_applied"],
    $data["training_mode"],
    $data["center_location"],
    $data["course_start_date"],
    $data["course_end_date"],
    $data["next_course_suggested"],
    $data["course_fee"],
    $data["discount_applied"],
    $data["final_fee"],
    $data["voucher_amount"],
    $data["payment_mode"],
    $data["payment_date"],
    $data["token_status"],
    $data["token_amount"],
    $data["installment_plan"],
    $data["installment_count"],
    $data["next_payment_date"],
    $data["total_due_amount"],
    $data["next_payable_amount"],
    $data["one_time_payment"],
    $data["payment_remark"],
    $data["class_allotted"],
    $data["tentative_joining_month"],
    $data["preferred_time_slot"],
    $data["batch_type"],
    $data["class_mode"],
    $data["batch_start_date"],
    $data["batch_end_date"],
    $data["ongoing_batch_course"],
    $data["preferred_language"],
    $data["batch_assigned_date"],
    $data["batch_assigned_time"],
    $data["trainer_assigned"],
    $data["receipt_number"],
    $data["receipt_student_name"],
    $data["receipt_student_id"],
    $data["receipt_course_name"],
    $data["receipt_batch_name"],
    $data["receipt_amount_paid"],
    $data["receipt_payment_mode"],
    $data["receipt_transaction_id"],
    $data["receipt_payment_date"],
    $data["receipt_balance_amount"],
    $data["receipt_authorized_signature"],
    $data["receipt_pdf_path"],
    $data["certificate_issued"],
    $data["remark"]
);

$ok = $stmt->execute();
if (!$ok) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Failed to save lead.",
        "error" => $stmt->error
    ]);
    $stmt->close();
    $mysqli->close();
    exit;
}

$mailError = null;
try {
    // Check email notification preference
    $userEmailNotif = 0;
    $userEmail = $_SESSION["email"] ?? "";
    $userName = $_SESSION["full_name"] ?? "User";

    if ($check = $mysqli->prepare("SELECT email_notifications FROM users WHERE user_id = ? LIMIT 1")) {
        $uid = (int)$_SESSION["user_id"];
        $check->bind_param("i", $uid);
        $check->execute();
        $res = $check->get_result();
        if ($row = $res->fetch_assoc()) {
            $userEmailNotif = (int)($row["email_notifications"] ?? 0);
        }
        $check->close();
    }

    if ($userEmailNotif === 1) {
        require_once __DIR__ . "/../../Register/brevo_mailer.php";
        $mailConfig = require __DIR__ . "/../../Register/mail_config.php";

        $leadName = $data["full_name"] ?? "Student";
        $leadEmail = $data["email_address"] ?? "";

        $leadSubject = "New Lead Created - {$leadName}";
        $leadHtml = "<p>Hi {$leadName},</p><p>Your lead has been created in ICSS CRM.</p><p>We will contact you shortly.</p>";
        $leadText = "Hi {$leadName}, your lead has been created in ICSS CRM. We will contact you shortly.";

        $userSubject = "New Lead Created - {$leadName}";
        $userHtml = "<p>Hello {$userName},</p><p>A new lead has been created.</p><p><strong>Name:</strong> {$leadName}<br><strong>Phone:</strong> {$data["phone_number"]}</p>";
        $userText = "Hello {$userName}, a new lead has been created. Name: {$leadName}, Phone: {$data["phone_number"]}.";

        if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $send = brevo_send_email($mailConfig, $userEmail, $userName, $userSubject, $userHtml, $userText);
            if (!$send["ok"]) {
                $mailError = "User email failed: " . ($send["error"] ?? "Brevo error");
            }
        }

        if ($leadEmail && filter_var($leadEmail, FILTER_VALIDATE_EMAIL)) {
            $sendLead = brevo_send_email($mailConfig, $leadEmail, $leadName, $leadSubject, $leadHtml, $leadText);
            if (!$sendLead["ok"]) {
                $mailError = ($mailError ? $mailError . " | " : "") . "Lead email failed: " . ($sendLead["error"] ?? "Brevo error");
            }
        }

        if (!empty($data["follow_up_date"])) {
            $followDate = $data["follow_up_date"];
            $followSubject = "Follow-up Scheduled - {$leadName}";
            $followHtml = "<p>Hello,</p><p>A follow-up has been scheduled for <strong>{$leadName}</strong> on <strong>{$followDate}</strong>.</p>";
            $followText = "A follow-up has been scheduled for {$leadName} on {$followDate}.";

            if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $sendF = brevo_send_email($mailConfig, $userEmail, $userName, $followSubject, $followHtml, $followText);
                if (!$sendF["ok"]) {
                    $mailError = ($mailError ? $mailError . " | " : "") . "User follow-up email failed: " . ($sendF["error"] ?? "Brevo error");
                }
            }
            if ($leadEmail && filter_var($leadEmail, FILTER_VALIDATE_EMAIL)) {
                $sendFL = brevo_send_email($mailConfig, $leadEmail, $leadName, $followSubject, $followHtml, $followText);
                if (!$sendFL["ok"]) {
                    $mailError = ($mailError ? $mailError . " | " : "") . "Lead follow-up email failed: " . ($sendFL["error"] ?? "Brevo error");
                }
            }
        }
    }
} catch (Throwable $e) {
    $mailError = "Email could not be sent.";
}

$stmt->close();
$mysqli->close();

header("Content-Type: application/json");
echo json_encode([
    "message" => $mailError ? "Lead saved, but email could not be sent." : "Lead saved successfully.",
    "mail_error" => $mailError
]);
exit;
?>
    "payment_date" => val("paymentDate"),
    "token_status" => val("tokenStatus"),
    "token_amount" => val("tokenAmount"),
    "installment_plan" => val("installmentPlan"),
    "installment_count" => val("installmentCount"),
    "next_payment_date" => val("nextPaymentDate"),
    "total_due_amount" => val("totalDueAmount"),
    "next_payable_amount" => val("nextPayableAmount"),
    "one_time_payment" => val("oneTimePayment"),
    "payment_remark" => val("paymentRemark"),

    "class_allotted" => val("classAllotted"),
    "tentative_joining_month" => val("tentativeJoiningMonth"),
    "preferred_time_slot" => val("preferredTimeSlot"),
    "batch_type" => val("batchType"),
    "class_mode" => val("classMode"),
    "batch_start_date" => val("batchStartDate"),
    "batch_end_date" => val("batchEndDate"),
    "ongoing_batch_course" => val("ongoingBatchCourse"),
    "preferred_language" => val("preferredLanguage"),
    "batch_assigned_date" => val("batchAssignedDate"),
    "batch_assigned_time" => val("batchAssignedTime"),
    "trainer_assigned" => val("trainerAssigned"),

    "receipt_number" => val("receiptNumber"),
    "receipt_student_name" => val("receiptStudentName"),
    "receipt_student_id" => val("receiptStudentId"),
    "receipt_course_name" => val("receiptCourseName"),
    "receipt_batch_name" => val("receiptBatchName"),
    "receipt_amount_paid" => val("receiptAmountPaid"),
    "receipt_payment_mode" => val("receiptPaymentMode"),
    "receipt_transaction_id" => val("receiptTransactionId"),
    "receipt_payment_date" => val("receiptPaymentDate"),
    "receipt_balance_amount" => val("receiptBalanceAmount"),
    "receipt_authorized_signature" => val("receiptAuthorizedSignature"),

    "certificate_issued" => val("certificateIssued"),
    "remark" => val("remark"),
];

if ($data["full_name"] === null || $data["full_name"] === "") {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Full name is required."]);
    exit;
}
if ($data["phone_number"] === null || $data["phone_number"] === "") {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Phone number is required."]);
    exit;
}

function handleReceiptUpload(): ?string {
    if (!isset($_FILES["paymentReceiptPdf"])) {
        return null;
    }
    $file = $_FILES["paymentReceiptPdf"];
    if ($file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file["error"] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if ($ext !== "pdf") {
        return null;
    }
    $dir = __DIR__ . "/../uploads/receipts";
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $safeName = "receipt_" . time() . "_" . bin2hex(random_bytes(4)) . ".pdf";
    $target = $dir . "/" . $safeName;
    if (!move_uploaded_file($file["tmp_name"], $target)) {
        return null;
    }
    return "uploads/receipts/" . $safeName;
}

$data["receipt_pdf_path"] = handleReceiptUpload();

$mysqli = null;
try {
    $mysqli = db_connect();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$placeholders = rtrim(str_repeat('?,', 76), ',');
$sql = "INSERT INTO leads (
    user_id,
    enquiry_status, student_type, full_name, gender, date_of_birth, phone_number, whatsapp_number, email_address,
    student_city, student_state, student_country,
    goal_purpose, institution_name, degree, branch_stream, year_of_study, prior_knowledge, working_details,
    source_of_lead, referral_person,
    inquiry_date, counselor_name, counseling_status, follow_up_date, counselor_notes, demo_status, demo_date,
    office_visited_date,
    course_applied, training_mode, center_location, course_start_date, course_end_date, next_course_suggested,
    course_fee, discount_applied, final_fee, voucher_amount, payment_mode, payment_date, token_status, token_amount,
    installment_plan, installment_count, next_payment_date, total_due_amount, next_payable_amount, one_time_payment,
    payment_remark,
    class_allotted, tentative_joining_month, preferred_time_slot, batch_type, class_mode, batch_start_date,
    batch_end_date, ongoing_batch_course, preferred_language, batch_assigned_date, batch_assigned_time,
    trainer_assigned,
    receipt_number, receipt_student_name, receipt_student_id, receipt_course_name, receipt_batch_name,
    receipt_amount_paid, receipt_payment_mode, receipt_transaction_id, receipt_payment_date, receipt_balance_amount,
    receipt_authorized_signature, receipt_pdf_path,
    certificate_issued, remark
) VALUES ($placeholders)";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Database error.",
        "error" => $mysqli->error
    ]);
    $mysqli->close();
    exit;
}

$stmt->bind_param(
    "i" . str_repeat("s", 75),
    $data["user_id"],
    $data["enquiry_status"],
    $data["student_type"],
    $data["full_name"],
    $data["gender"],
    $data["date_of_birth"],
    $data["phone_number"],
    $data["whatsapp_number"],
    $data["email_address"],
    $data["student_city"],
    $data["student_state"],
    $data["student_country"],
    $data["goal_purpose"],
    $data["institution_name"],
    $data["degree"],
    $data["branch_stream"],
    $data["year_of_study"],
    $data["prior_knowledge"],
    $data["working_details"],
    $data["source_of_lead"],
    $data["referral_person"],
    $data["inquiry_date"],
    $data["counselor_name"],
    $data["counseling_status"],
    $data["follow_up_date"],
    $data["counselor_notes"],
    $data["demo_status"],
    $data["demo_date"],
    $data["office_visited_date"],
    $data["course_applied"],
    $data["training_mode"],
    $data["center_location"],
    $data["course_start_date"],
    $data["course_end_date"],
    $data["next_course_suggested"],
    $data["course_fee"],
    $data["discount_applied"],
    $data["final_fee"],
    $data["voucher_amount"],
    $data["payment_mode"],
    $data["payment_date"],
    $data["token_status"],
    $data["token_amount"],
    $data["installment_plan"],
    $data["installment_count"],
    $data["next_payment_date"],
    $data["total_due_amount"],
    $data["next_payable_amount"],
    $data["one_time_payment"],
    $data["payment_remark"],
    $data["class_allotted"],
    $data["tentative_joining_month"],
    $data["preferred_time_slot"],
    $data["batch_type"],
    $data["class_mode"],
    $data["batch_start_date"],
    $data["batch_end_date"],
    $data["ongoing_batch_course"],
    $data["preferred_language"],
    $data["batch_assigned_date"],
    $data["batch_assigned_time"],
    $data["trainer_assigned"],
    $data["receipt_number"],
    $data["receipt_student_name"],
    $data["receipt_student_id"],
    $data["receipt_course_name"],
    $data["receipt_batch_name"],
    $data["receipt_amount_paid"],
    $data["receipt_payment_mode"],
    $data["receipt_transaction_id"],
    $data["receipt_payment_date"],
    $data["receipt_balance_amount"],
    $data["receipt_authorized_signature"],
    $data["receipt_pdf_path"],
    $data["certificate_issued"],
    $data["remark"]
);

$ok = $stmt->execute();
if (!$ok) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Failed to save lead.",
        "error" => $stmt->error
    ]);
    $stmt->close();
    $mysqli->close();
    exit;
}

$mailError = null;
try {
    // Check email notification preference
    $userEmailNotif = 0;
    $userEmail = $_SESSION["email"] ?? "";
    $userName = $_SESSION["full_name"] ?? "User";

    if ($check = $mysqli->prepare("SELECT email_notifications FROM users WHERE user_id = ? LIMIT 1")) {
        $uid = (int)$_SESSION["user_id"];
        $check->bind_param("i", $uid);
        $check->execute();
        $res = $check->get_result();
        if ($row = $res->fetch_assoc()) {
            $userEmailNotif = (int)($row["email_notifications"] ?? 0);
        }
        $check->close();
    }

    if ($userEmailNotif === 1) {
        require_once __DIR__ . "/../../Register/brevo_mailer.php";
        $mailConfig = require __DIR__ . "/../../Register/mail_config.php";

        $leadName = $data["full_name"] ?? "Student";
        $leadEmail = $data["email_address"] ?? "";

        $leadSubject = "New Lead Created - {$leadName}";
        $leadHtml = "<p>Hi {$leadName},</p><p>Your lead has been created in ICSS CRM.</p><p>We will contact you shortly.</p>";
        $leadText = "Hi {$leadName}, your lead has been created in ICSS CRM. We will contact you shortly.";

        $userSubject = "New Lead Created - {$leadName}";
        $userHtml = "<p>Hello {$userName},</p><p>A new lead has been created.</p><p><strong>Name:</strong> {$leadName}<br><strong>Phone:</strong> {$data["phone_number"]}</p>";
        $userText = "Hello {$userName}, a new lead has been created. Name: {$leadName}, Phone: {$data["phone_number"]}.";

        if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $send = brevo_send_email($mailConfig, $userEmail, $userName, $userSubject, $userHtml, $userText);
            if (!$send["ok"]) {
                $mailError = "User email failed: " . ($send["error"] ?? "Brevo error");
            }
        }

        if ($leadEmail && filter_var($leadEmail, FILTER_VALIDATE_EMAIL)) {
            $sendLead = brevo_send_email($mailConfig, $leadEmail, $leadName, $leadSubject, $leadHtml, $leadText);
            if (!$sendLead["ok"]) {
                $mailError = ($mailError ? $mailError . " | " : "") . "Lead email failed: " . ($sendLead["error"] ?? "Brevo error");
            }
        }

        if (!empty($data["follow_up_date"])) {
            $followDate = $data["follow_up_date"];
            $followSubject = "Follow-up Scheduled - {$leadName}";
            $followHtml = "<p>Hello,</p><p>A follow-up has been scheduled for <strong>{$leadName}</strong> on <strong>{$followDate}</strong>.</p>";
            $followText = "A follow-up has been scheduled for {$leadName} on {$followDate}.";

            if ($userEmail && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $sendF = brevo_send_email($mailConfig, $userEmail, $userName, $followSubject, $followHtml, $followText);
                if (!$sendF["ok"]) {
                    $mailError = ($mailError ? $mailError . " | " : "") . "User follow-up email failed: " . ($sendF["error"] ?? "Brevo error");
                }
            }
            if ($leadEmail && filter_var($leadEmail, FILTER_VALIDATE_EMAIL)) {
                $sendFL = brevo_send_email($mailConfig, $leadEmail, $leadName, $followSubject, $followHtml, $followText);
                if (!$sendFL["ok"]) {
                    $mailError = ($mailError ? $mailError . " | " : "") . "Lead follow-up email failed: " . ($sendFL["error"] ?? "Brevo error");
                }
            }
        }
    }
} catch (Throwable $e) {
    $mailError = "Email could not be sent.";
}

$stmt->close();
$mysqli->close();

header("Content-Type: application/json");
echo json_encode([
    "message" => $mailError ? "Lead saved, but email could not be sent." : "Lead saved successfully.",
    "mail_error" => $mailError
]);
exit;
?>




<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
start_secure_session('CRM_USERSESSID');

$hasUser = !empty($_SESSION["user_id"]);
if (!$hasUser) {
  header("Location: logout.php");
  exit;
}

$fullName = $_SESSION["full_name"] ?? "User";
$email = $_SESSION["email"] ?? "user@example.com";

$parts = preg_split("/\\s+/", trim($fullName));
$initials = "";
foreach ($parts as $p) {
  if ($p !== "") {
    $initials .= strtoupper($p[0]);
  }
  if (strlen($initials) >= 2) break;
}
if ($initials === "") $initials = "U";

require_once __DIR__ . "/../config/crm.php";
$leadCount = 0;
try {
    $mysqli = db_connect();
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads");
    if ($res) {
        $leadCount = (int)($res->fetch_assoc()["c"] ?? 0);
        $res->free();
    }
    $mysqli->close();
} catch (Throwable $e) {
    $leadCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ICSS CRM � Create a new lead with student, course, and admission details." />
    <title>Create Lead | ICSS CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="css/global.css?v=2" />
    <link rel="stylesheet" href="css/navbar.css?v=2" />
    <link rel="stylesheet" href="css/sidebar.css?v=2" />
    <link rel="stylesheet" href="css/create-lead.css?v=2" />
</head>

<body>
    <!-- --------------- TOP NAVBAR --------------- -->
    <nav class="navbar" id="navbar">
        <div class="navbar-brand">
            <button class="navbar-hamburger show-mobile" id="hamburgerBtn" aria-label="Toggle menu">?</button>
            <div class="navbar-logo">IC</div>
            <div class="navbar-brand-text hide-mobile">
                <span class="navbar-brand-name">ICSS CRM</span>
                <span class="navbar-brand-sub">Management Suite</span>
            </div>
        </div>
        </div>
        <div class="navbar-actions">
            <button class="navbar-action-btn" data-tooltip="Notifications" id="notificationBtn">&#x1F514;<span
                    class="notification-dot"></span></button>
            <div class="profile-wrapper">
                <div class="profile-trigger">
                    <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <div class="profile-info hide-mobile">
                        <span class="profile-name"><?php echo htmlspecialchars($fullName); ?></span>
                        </div>
                    <span class="profile-chevron hide-mobile">&#x25BE;</span>
                </div>
                <div class="profile-dropdown">
                    <div class="dropdown-header">
                        <div class="profile-name"><?php echo htmlspecialchars($fullName); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">&#x1F464;</span> My
                            Profile</a>
                        <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">&#x2699;&#xFE0F;</span> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item danger" href="logout.php"><span
                                class="dropdown-icon">&#x1F6AA;</span> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-shell">
        <!-- --------------- LEFT SIDEBAR --------------- -->
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </button>
                    <nav class="sidebar-nav">
          <div class="sidebar-section">
            <div class="sidebar-section-label">Main</div>
            <a href="index.php" class="sidebar-link" data-tooltip="Dashboard">
              <span class="sidebar-icon">&#x1F4CA;</span>
              <span class="sidebar-link-text">Dashboard</span>
            </a>
            <a href="create-lead.php" class="sidebar-link active" data-tooltip="Create Lead">
              <span class="sidebar-icon">&#x2795;</span>
              <span class="sidebar-link-text">Create Lead</span>
            </a>
            <a href="view-leads.php" class="sidebar-link" data-tooltip="View Leads">
              <span class="sidebar-icon">&#x1F4CB;</span>
              <span class="sidebar-link-text">View Leads</span>
              <span class="sidebar-badge"><?php echo (int)$leadCount; ?></span>
            </a>
            <a href="followups.php" class="sidebar-link" data-tooltip="Today''s Follow-up">
              <span class="sidebar-icon">&#x1F4C5;</span>
              <span class="sidebar-link-text">Today''s Follow-up</span>
            </a>
            <a href="faculty-routine.php" class="sidebar-link" data-tooltip="Faculty Routine">
              <span class="sidebar-icon">&#x1F4DA;</span>
              <span class="sidebar-link-text">Faculty Routine</span>
            </a>
          </div>

          <div class="sidebar-section">
            <div class="sidebar-section-label">System</div>
            <a href="settings.php" class="sidebar-link" data-tooltip="Settings">
              <span class="sidebar-icon">&#x2699;&#xFE0F;</span>
              <span class="sidebar-link-text">Settings</span>
            </a>
          </div>
        </nav>
</aside>

        <!-- --------------- MAIN CONTENT --------------- -->
        <main class="main-content">
            <div class="page-header">
                <h1>Create New Lead</h1>
                <div class="breadcrumb">
                    <a href="index.php">Dashboard</a>
                    <span class="separator">/</span>
                    <span>Create Lead</span>
                </div>
            </div>
            <div id="formMessage" class="form-message"></div>


            <form id="createLeadForm" class="lead-form-container" novalidate enctype="multipart/form-data">

                <!-- ------- SECTION 1: Student Basic Profile ------- -->
                <div class="form-section" id="sectionStudent">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F464;</div>
                        <div class="section-title">
                            <h3>Student Basic Profile</h3>
                            <p>Core personal details and enquiry status</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="enquiryStatus">Enquiry Status</label>
                                <select class="form-control" id="enquiryStatus" name="enquiryStatus">
                                    <option value="">Select Status</option>
                                    <option value="Open">Open</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="studentType">Student Type</label>
                                <select class="form-control" id="studentType" name="studentType">
                                    <option value="">Select Type</option>
                                    <option value="Old">Old</option>
                                    <option value="New">New</option>
                                </select>
                            </div>

                            <div class="form-group span-2">
                                <label class="form-label" for="fullName">Full Name <span class="required">*</span></label>
                                <input type="text" class="form-control" id="fullName" name="fullName"
                                    placeholder="Enter full name" required />
                                <span class="form-error">Full name is required</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="gender">Gender</label>
                                <select class="form-control" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="dateOfBirth">Date of Birth</label>
                                <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="phoneNumber">Mobile Number <span class="required">*</span></label>
                                <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber" placeholder="Enter mobile number" required inputmode="numeric" pattern="\\d{10}" maxlength="10" minlength="10" />
                                <span class="form-error">Mobile number is required</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="whatsappNumber">WhatsApp Number</label>
                                <input type="tel" class="form-control" id="whatsappNumber" name="whatsappNumber" placeholder="Enter WhatsApp number" inputmode="numeric" pattern="\\d{10}" maxlength="10" minlength="10" />
                            </div>

                            <div class="form-group span-2">
                                <label class="form-label" for="emailAddress">Email Address</label>
                                <input type="email" class="form-control" id="emailAddress" name="emailAddress"
                                    placeholder="student@email.com" />
                                <span class="form-error">Enter a valid email address</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="studentCity">City</label>
                                <input type="text" class="form-control" id="studentCity" name="studentCity"
                                    placeholder="Enter city" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="studentState">State</label>
                                <select class="form-control" id="studentState" name="studentState">
                                    <option value="">Select State</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="studentCountry">Country</label>
                                <input type="text" class="form-control" id="studentCountry" name="studentCountry"
                                    placeholder="Enter country" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 2: Academic Information ------- -->
                <div class="form-section" id="sectionAcademic">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F4DA;</div>
                        <div class="section-title">
                            <h3>Academic Information</h3>
                            <p>Education background and goals</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group span-full">
                                <label class="form-label" for="goalPurpose">Goal / Purpose of Admission</label>
<select class="form-control" id="goalPurpose" name="goalPurpose">
    <option value="">Select Purpose</option>
    <option value="Skill Upgrade">Skill Upgrade</option>
    <option value="Certification">Certification</option>
    <option value="Career Switch">Career Switch</option>
    <option value="Job">Job</option>
    <option value="Higher Studies">Higher Studies</option>
    <option value="Other">Other</option>
</select>
                            </div>

                            <div class="form-group span-2">
                                <label class="form-label" for="institutionName">School / College / University Name</label>
                                <input type="text" class="form-control" id="institutionName" name="institutionName"
                                    placeholder="Enter institution name" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="degree">Degree</label>
                                <select class="form-control" id="degree" name="degree">
                                    <option value="">Select Degree</option>
                                    <option value="B.Tech">B.Tech</option>
                                    <option value="BCA">BCA</option>
                                    <option value="MCA">MCA</option>
                                    <option value="BSc">BSc</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="branchStream">Branch / Stream</label>
                                <input type="text" class="form-control" id="branchStream" name="branchStream"
                                    placeholder="Enter branch or stream" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="yearOfStudy">Year of Study</label>
                                <select class="form-control" id="yearOfStudy" name="yearOfStudy">
                                    <option value="">Select Status</option>
                                    <option value="Pursuing">Pursuing</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="priorKnowledge">Prior Knowledge / Profession</label>
                                <select class="form-control" id="priorKnowledge" name="priorKnowledge">
                                    <option value="">Select Profession</option>
                                    <option value="Student">Student</option>
                                    <option value="Working">Working</option>
                                    <option value="Fresher">Fresher</option>
                                    <option value="Self Employed">Self Employed</option>
                                    <option value="Unemployed">Unemployed</option>
                                </select>
                            </div>

                            <div class="form-group" id="workingDetailsGroup">
                                <label class="form-label" for="workingDetails">If Working, Then What</label>
                                <input type="text" class="form-control" id="workingDetails" name="workingDetails"
                                    placeholder="Role or company" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 3: Lead Source Tracking ------- -->
                <div class="form-section" id="sectionSource">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F517;</div>
                        <div class="section-title">
                            <h3>Lead Source Tracking</h3>
                            <p>How the lead was acquired</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="sourceOfLead">Lead Source</label>
                                <select class="form-control" id="sourceOfLead" name="sourceOfLead">
                                    <option value="">Select Source</option>
                                    <option value="Sales Group">Sales Group</option>
                                    <option value="Whatsapp">WhatsApp</option>
                                    <option value="Direct Call">Direct Call</option>
                                    <option value="Instagram">Instagram</option>
                                    <option value="LinkedIn">LinkedIn</option>
                                    <option value="Website">Website</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Referral">Referral</option>
                                    <option value="Walk-in">Walk-in</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nextCourseSuggested">Next Course Suggested</label>
                                <select class="form-control" id="nextCourseSuggested" name="nextCourseSuggested">
                                    <option value="">Select Course</option>
                                    <option value="Advance AWS">Advance AWS</option>
                                    <option value="SOC Analyst">SOC Analyst</option>
                                    <option value="Cyber Security">Cyber Security</option>
                                </select>
                            </div>

                            <div class="form-group span-2">
                                <label class="form-label" for="referralPerson">Referral Person Name</label>
                                <input type="text" class="form-control" id="referralPerson" name="referralPerson"
                                    placeholder="Enter referral name" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 4: Counseling Details ------- -->
                <div class="form-section" id="sectionCounseling">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F4CC;</div>
                        <div class="section-title">
                            <h3>Counseling Details</h3>
                            <p>Inquiry, counseling, and follow-up tracking</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="inquiryDate">Inquiry Date</label>
                                <input type="date" class="form-control" id="inquiryDate" name="inquiryDate" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="counselorName">Counselor Name</label>
                                <input type="text" class="form-control" id="counselorName" name="counselorName"
                                    placeholder="Enter counselor name" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="counselingStatus">Counseling Status</label>
                                <select class="form-control" id="counselingStatus" name="counselingStatus">
                                    <option value="">Select Status</option>
                                    <option value="Positive">Positive</option>
                                    <option value="Considering">Considering</option>
                                    <option value="Negative">Negative</option>
                                    <option value="May Enroll Later">May Enroll Later</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="followUpDate">Follow-up Date</label>
                                <input type="date" class="form-control" id="followUpDate" name="followUpDate" />
                            </div>

                            <div class="form-group span-full">
                                <label class="form-label" for="counselorNotes">Counselor Notes</label>
                                <textarea class="form-control" id="counselorNotes" name="counselorNotes" rows="3"
                                    placeholder="Enter counseling notes"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="demoStatus">Demo Status</label>
                                <select class="form-control" id="demoStatus" name="demoStatus">
                                    <option value="">Select Status</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="demoDate">Demo Date</label>
                                <input type="date" class="form-control" id="demoDate" name="demoDate" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="officeVisitedDate">Office Visited Date</label>
                                <input type="date" class="form-control" id="officeVisitedDate"
                                    name="officeVisitedDate" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 5: Course Enrollment ------- -->
                <div class="form-section" id="sectionEnrollment">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F393;</div>
                        <div class="section-title">
                            <h3>Course Enrollment</h3>
                            <p>Course and training preferences</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="courseApplied">Course Name</label>
                                <select class="form-control" id="courseApplied" name="courseApplied">
                                    <option value="">Select Course</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="trainingMode">Training Mode</label>
                                <select class="form-control" id="trainingMode" name="trainingMode">
                                    <option value="">Select Mode</option>
                                    <option value="Online">Online</option>
                                    <option value="Offline">Offline</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="centerLocation">Training Center</label>
                                <select class="form-control" id="centerLocation" name="centerLocation">
                                    <option value="">Select Location</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="courseStartDate">Course Start Date</label>
                                <input type="date" class="form-control" id="courseStartDate" name="courseStartDate" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="courseEndDate">Course End Date</label>
                                <input type="date" class="form-control" id="courseEndDate" name="courseEndDate" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 6: Fee Management ------- -->
                <div class="form-section" id="sectionFee">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F4B0;</div>
                        <div class="section-title">
                            <h3>Fee Management</h3>
                            <p>Fees, payments, and installments</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="courseFee">Course Fee</label>
                                <input type="number" class="form-control" id="courseFee" name="courseFee"
                                    placeholder="0.00" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="discountApplied">Discount Applied (%)</label>
                                <input type="number" class="form-control" id="discountApplied" name="discountApplied" placeholder="0-100" min="0" max="100" step="0.01" inputmode="decimal" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="finalFee">Final Fee</label>
                                <input type="text" class="form-control" id="finalFee" name="finalFee"
                                    placeholder="0.00" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="voucherAmount">Voucher Amount</label>
                                <input type="number" class="form-control" id="voucherAmount" name="voucherAmount"
                                    placeholder="0.00" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="paymentMode">Payment Mode</label>
                                <select class="form-control" id="paymentMode" name="paymentMode">
                                    <option value="">Select Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Card">Card</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="paymentDate">Payment Date</label>
                                <input type="date" class="form-control" id="paymentDate" name="paymentDate" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tokenStatus">Token Amount</label>
                                <select class="form-control" id="tokenStatus" name="tokenStatus">
                                    <option value="">Select Option</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>

                            <div class="form-group" id="tokenAmountGroup">
                                <label class="form-label" for="tokenAmount">Token Amount Value</label>
                                <input type="number" class="form-control" id="tokenAmount" name="tokenAmount"
                                    placeholder="0.00" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="installmentPlan">Installment Plan</label>
                                <select class="form-control" id="installmentPlan" name="installmentPlan">
                                    <option value="">Select Option</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>

                            <div class="form-group" id="installmentCountGroup">
                                <label class="form-label" for="installmentCount">Number of Installments (1-10)</label>
                                <input type="number" class="form-control" id="installmentCount" name="installmentCount"
                                    min="1" max="10" />
                            </div>

                            <div class="form-group" id="nextPaymentDateGroup">
                                <label class="form-label" for="nextPaymentDate">Next Payment Due Date</label>
                                <input type="date" class="form-control" id="nextPaymentDate" name="nextPaymentDate" />
                            </div>

                            <div class="form-group" id="totalDueAmountGroup">
                                <label class="form-label" for="totalDueAmount">Total Due</label>
                                <input type="number" class="form-control" id="totalDueAmount" name="totalDueAmount"
                                    placeholder="0.00" />
                            </div>

                            <div class="form-group" id="nextPayableAmountGroup">
                                <label class="form-label" for="nextPayableAmount">Next Payable Amount</label>
                                <input type="number" class="form-control" id="nextPayableAmount"
                                    name="nextPayableAmount" placeholder="0.00" />
                            </div>

                            <div class="form-group" id="oneTimePaymentGroup">
                                <label class="form-label" for="oneTimePayment">One Time Payment</label>
                                <input type="text" class="form-control" id="oneTimePayment" name="oneTimePayment"
                                    placeholder="Enter one-time payment details" />
                            </div>

                            <div class="form-group span-full">
                                <label class="form-label" for="paymentRemark">Payment Remark</label>
                                <input type="text" class="form-control" id="paymentRemark" name="paymentRemark"
                                    placeholder="Enter payment remark" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 10: Payment Receipt ------- -->
                <div class="form-section" id="sectionReceipt">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F465;</div>
                        <div class="section-title">
                            <h3>Payment Receipt</h3>
                            <p>Receipt details and PDF upload</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="receiptNumber">Receipt Number</label>
                                <input type="text" class="form-control" id="receiptNumber" name="receiptNumber"
                                    placeholder="Enter receipt number" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptStudentName">Student Name</label>
                                <input type="text" class="form-control" id="receiptStudentName" name="receiptStudentName"
                                    placeholder="Enter student name" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptStudentId">Student ID</label>
                                <input type="text" class="form-control" id="receiptStudentId" name="receiptStudentId"
                                    placeholder="Enter student ID" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptCourseName">Course Name</label>
                                <input type="text" class="form-control" id="receiptCourseName" name="receiptCourseName"
                                    placeholder="Enter course name" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptBatchName">Batch Name</label>
                                <input type="text" class="form-control" id="receiptBatchName" name="receiptBatchName"
                                    placeholder="Enter batch name" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptAmountPaid">Amount Paid</label>
                                <input type="number" class="form-control" id="receiptAmountPaid" name="receiptAmountPaid"
                                    placeholder="0.00" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptPaymentMode">Payment Mode</label>
<select class="form-control" id="receiptPaymentMode" name="receiptPaymentMode">
    <option value="">Select Mode</option>
    <option value="Cash">Cash</option>
    <option value="UPI">UPI</option>
    <option value="Card">Card</option>
    <option value="Bank Transfer">Bank Transfer</option>
    <option value="Cheque">Cheque</option>
    <option value="Other">Other</option>
</select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptTransactionId">Transaction ID</label>
                                <input type="text" class="form-control" id="receiptTransactionId"
                                    name="receiptTransactionId" placeholder="Enter transaction ID" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptPaymentDate">Payment Date</label>
                                <input type="date" class="form-control" id="receiptPaymentDate" name="receiptPaymentDate" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptBalanceAmount">Balance Amount</label>
                                <input type="number" class="form-control" id="receiptBalanceAmount"
                                    name="receiptBalanceAmount" placeholder="0.00" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="receiptAuthorizedSignature">Authorized Signature</label>
                                <input type="text" class="form-control" id="receiptAuthorizedSignature"
                                    name="receiptAuthorizedSignature" placeholder="Enter signer name" />
                            </div>

                            <div class="form-group span-full">
                                <label class="form-label" for="paymentReceiptPdf">Payment Receipt PDF Upload</label>
                                <input type="file" class="form-control" id="paymentReceiptPdf" name="paymentReceiptPdf"
                                    accept="application/pdf" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 11: Certificate Issued ------- -->
                <div class="form-section" id="sectionCertificate">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F9FE;</div>
                        <div class="section-title">
                            <h3>Certificate Issued</h3>
                            <p>Certificate status</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="certificateIssued">Certificate Issued</label>
                                <select class="form-control" id="certificateIssued" name="certificateIssued">
                                    <option value="">Select Option</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 9: Class Details ------- -->
                <div class="form-section" id="sectionClassDetails">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F4DC;</div>
                        <div class="section-title">
                            <h3>Class Details</h3>
                            <p>Batch and class scheduling information</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="classAllotted">Class Allotted</label>
                                <select class="form-control" id="classAllotted" name="classAllotted">
                                    <option value="">Select Option</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tentativeJoiningMonth">Tentative Joining Month</label>
                                <input type="month" class="form-control" id="tentativeJoiningMonth"
                                    name="tentativeJoiningMonth" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="preferredTimeSlot">Preferred Time Slot</label>
                                <select class="form-control" id="preferredTimeSlot" name="preferredTimeSlot">
                                    <option value="">Select Slot</option>
                                    <option value="Morning">Morning</option>
                                    <option value="Afternoon">Afternoon</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="batchType">Batch Type</label>
                                <select class="form-control" id="batchType" name="batchType">
                                    <option value="">Select Type</option>
                                    <option value="Weekday">Weekday</option>
                                    <option value="Weekend">Weekend</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="classMode">Mode</label>
                                <select class="form-control" id="classMode" name="classMode">
                                    <option value="">Select Mode</option>
                                    <option value="Online">Online</option>
                                    <option value="Offline">Offline</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="batchStartDate">Batch Start Date</label>
                                <input type="date" class="form-control" id="batchStartDate" name="batchStartDate" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="batchEndDate">Batch End Date</label>
                                <input type="date" class="form-control" id="batchEndDate" name="batchEndDate" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="ongoingBatchCourse">Ongoing Batch Details (Course Name)</label>
                                <input type="text" class="form-control" id="ongoingBatchCourse" name="ongoingBatchCourse"
                                    placeholder="Enter course name" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="preferredLanguage">Preferred Language</label>
<select class="form-control" id="preferredLanguage" name="preferredLanguage">
    <option value="">Select Language</option>
    <option value="English">English</option>
    <option value="Hindi">Hindi</option>
    <option value="Bengali">Bengali</option>
    <option value="Tamil">Tamil</option>
    <option value="Telugu">Telugu</option>
    <option value="Marathi">Marathi</option>
    <option value="Other">Other</option>
</select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="batchAssignedDate">Batch Assigned Date</label>
                                <input type="date" class="form-control" id="batchAssignedDate" name="batchAssignedDate" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="batchAssignedTime">Batch Assigned Time</label>
                                <input type="time" class="form-control" id="batchAssignedTime" name="batchAssignedTime" />
                            </div>

                            <div class="form-group span-2">
                                <label class="form-label" for="trainerAssigned">Trainer Assigned</label>
                                <input type="text" class="form-control" id="trainerAssigned" name="trainerAssigned"
                                    placeholder="Enter trainer name" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ------- SECTION 12: Remarks ------- -->
                <div class="form-section" id="sectionRemarks">
                    <div class="form-section-header">
                        <div class="section-icon">&#x1F4DD;</div>
                        <div class="section-title">
                            <h3>Remarks</h3>
                            <p>Additional notes about this lead</p>
                        </div>
                        <span class="section-collapse-icon">&#x25BE;</span>
                    </div>
                    <div class="form-section-body">
                        <div class="form-grid form-grid-1">
                            <div class="form-group">
                                <label class="form-label" for="remark">Remark</label>
                                <textarea class="form-control" id="remark" name="remark" rows="4"
                                    placeholder="Type your remarks here�"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </form>

            <!-- ------- FORM FOOTER ------- -->
            <div class="form-footer">
                <div class="form-footer-left">
                    <div class="autosave-indicator">
                        <span class="autosave-dot"></span>
                        <span class="autosave-text">Draft auto-saved</span>
                    </div>
                </div>
                <div class="form-footer-right">
<button class="btn btn-secondary" id="saveContinueBtn" type="button">
                        <span class="btn-text">Save & Continue</span>
                    </button>
                    <button class="btn btn-primary" id="saveBtn" type="button">
                        <span class="btn-text">&#x1F4BE; Save Lead</span>
                    </button>
                </div>
            </div>

        </main>
    </div>
    <script src="js/sweetalert2.min.js" data-swal="true"></script>
    <script src="js/app.js?v=4"></script>
    <script src="js/create-lead.js?v=7"></script>
  </body>

</html>


<?php
require_once __DIR__ . '/../config/db.php';

/* ---------------------------
   CSRF VALIDATION
----------------------------*/
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(BASE_URL);
}

/* ---------------------------
   AUTH CHECK
----------------------------*/
requireLogin();

/* ---------------------------
   ONLY POST REQUEST
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL);
}

/* ---------------------------
   INPUTS
----------------------------*/
$type = (isset($_POST['type']) && in_array($_POST['type'], ['lost', 'found']))
    ? $_POST['type']
    : null;

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');

// Feature 3: Campus Location Taxonomy
$locationZone = trim($_POST['location_zone'] ?? '');
$locationDetails = trim($_POST['location_details'] ?? '');

// Check if we have the new location columns
$columnCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'location_zone'");
$hasLocationColumns = $columnCheck->num_rows > 0;

if ($hasLocationColumns) {
    // Combine zone and details for the location field
    $campusLocations = require __DIR__ . '/../includes/campus_locations.php';
    $zoneName = '';
    foreach ($campusLocations as $zoneGroup) {
        foreach ($zoneGroup['locations'] as $key => $name) {
            if ($key === $locationZone) {
                $zoneName = $name;
                break 2;
            }
        }
    }

    $location = $zoneName;
    if (!empty($locationDetails)) {
        $location .= ' - ' . $locationDetails;
    }
} else {
    // Fallback to old location input for backward compatibility
    $location = trim($_POST['location'] ?? '');
}

$contactNumber = trim($_POST['contact_number'] ?? '');

// Feature 2: Secret Identifier fields (for found items)
$verificationQuestion = trim($_POST['verification_question'] ?? '');
$verificationAnswer = trim($_POST['verification_answer'] ?? '');

// Feature 4: Office Custody fields (for found items)
$custodyOffice = trim($_POST['custody_office'] ?? '');
$custodyReference = null;

// Generate reference number if surrendered to office
if ($type === 'found' && !empty($custodyOffice)) {
    $custodyReference = 'CTU-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/* ---------------------------
   VALIDATION
----------------------------*/
// Check which validation to apply based on schema
$columnCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'location_zone'");
$hasLocationColumns = $columnCheck->num_rows > 0;

if ($hasLocationColumns) {
    if (!$type || !$title || !$description || !$category || !$locationZone) {
        setFlash('error', 'Please fill in all required fields.');
        redirect(BASE_URL);
    }
} else {
    if (!$type || !$title || !$description || !$category || !$location) {
        setFlash('error', 'Please fill in all required fields.');
        redirect(BASE_URL);
    }
}

/* OPTIONAL: validate PH number */
if (!empty($contactNumber)) {
    $contactNumber = preg_replace('/[^0-9+]/', '', $contactNumber);

    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contactNumber)) {
        setFlash('error', 'Invalid contact number format.');
        redirect(BASE_URL);
    }
}

/* ---------------------------
   IMAGE UPLOAD
----------------------------*/
$imageName = null;

if (!empty($_FILES['image']['name'])) {
    $file = $_FILES['image'];

    // Use secure file validation
    $validationError = validateUploadedFile($file);
    if ($validationError !== null) {
        setFlash('error', $validationError);
        redirect(BASE_URL);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $imageName = uniqid('item_', true) . '.' . $ext;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $imageName)) {
        setFlash('error', 'Failed to upload image.');
        redirect(BASE_URL);
    }
}

/* ---------------------------
   DATABASE INSERT
----------------------------*/
// Check which columns exist in the posts table
$columnCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'location_zone'");
$hasLocationColumns = $columnCheck->num_rows > 0;

$verificationCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'verification_question'");
$hasVerificationColumns = $verificationCheck->num_rows > 0;

$custodyCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'custody_office'");
$hasCustodyColumns = $custodyCheck->num_rows > 0;

$contactCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'contact_number'");
$hasContactColumn = $contactCheck->num_rows > 0;

$userId = $_SESSION['user_id'];

// Only store verification details for found items and if question is provided
$finalVerificationQuestion = ($type === 'found' && !empty($verificationQuestion)) ? $verificationQuestion : null;
$finalVerificationAnswer = ($type === 'found' && !empty($verificationAnswer)) ? $verificationAnswer : null;

// Only store custody details for found items and if office is selected
$finalCustodyOffice = ($type === 'found' && !empty($custodyOffice)) ? $custodyOffice : null;
$finalCustodyReference = ($type === 'found' && !empty($custodyOffice)) ? $custodyReference : null;

// Build appropriate INSERT statement based on available columns
if ($hasLocationColumns && $hasVerificationColumns && $hasCustodyColumns && $hasContactColumn) {
    // Full new schema
    $stmt = $conn->prepare("
        INSERT INTO posts 
        (user_id, type, title, description, category, location, location_zone, location_details, contact_number, image, verification_question, verification_answer, custody_office, custody_reference)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'isssssssssssss',
        $userId, $type, $title, $description, $category, $location, $locationZone, $locationDetails,
        $contactNumber, $imageName, $finalVerificationQuestion, $finalVerificationAnswer, $finalCustodyOffice, $finalCustodyReference
    );
} elseif ($hasContactColumn) {
    // Original schema with contact_number
    $stmt = $conn->prepare("
        INSERT INTO posts 
        (user_id, type, title, description, category, location, contact_number, image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'isssssss',
        $userId, $type, $title, $description, $category, $location, $contactNumber, $imageName
    );
} else {
    // Minimal original schema
    $stmt = $conn->prepare("
        INSERT INTO posts 
        (user_id, type, title, description, category, location, image)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'issssss',
        $userId, $type, $title, $description, $category, $location, $imageName
    );
}

if (!$stmt) {
    setFlash('error', 'Database error: ' . $conn->error);
    redirect(BASE_URL);
}

/* ---------------------------
   EXECUTE
----------------------------*/
if ($stmt->execute()) {
    $newPostId = $stmt->insert_id;
    
    // Trigger smart matching for the new post
    require_once __DIR__ . '/../includes/smart_matching.php';
    $matchResults = findAndStoreMatches($newPostId, $type);
    
    // Feature 5: Student ID Detection
    require_once __DIR__ . '/../includes/student_id_detection.php';
    $idDetectionResults = processPostForIdDetection($newPostId, $title, $description, $category);
    
    $message = 'Your item has been posted successfully!';
    if ($matchResults['matches_found'] > 0) {
        $message .= " We found {$matchResults['matches_found']} potential match(es)!";
    }
    if ($idDetectionResults['detected'] && $idDetectionResults['matches'] > 0) {
        $message .= " We detected student ID(s) and notified {$idDetectionResults['matches']} potential owner(s)!";
    }
    setFlash('success', $message);
} else {
    setFlash('error', 'Failed to post item.');
}

$stmt->close();

redirect(BASE_URL);
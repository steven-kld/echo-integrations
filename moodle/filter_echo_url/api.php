<?php
define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once(dirname(__DIR__, 2) . '/config.php');

\core\session\manager::init_empty_session();
\core\session\manager::set_user(get_admin());

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/enrol/manual/locallib.php');

header('Content-Type: application/json');

// 🔐 Token check
$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
$authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');

if (strpos($authHeader, 'Bearer ') !== 0 || trim(substr($authHeader, 7)) !== 'yourSuperSecretTokenHere') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// 📥 Input
$input = json_decode(file_get_contents("php://input"), true);
$userid = intval($input['userid'] ?? 0);
$courseid = intval($input['courseid'] ?? 0);
$score = floatval($input['score'] ?? 100);
$gradeitemname = trim($input['gradeitem'] ?? '');
$review = trim($input['review'] ?? 'Completed via Echo interview.');

// 📌 Validate input
if (empty($userid) || empty($courseid) || empty($gradeitemname)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// 👤 Validate user
$user = $DB->get_record('user', ['id' => $userid]);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// 🎓 Validate course
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    http_response_code(404);
    echo json_encode(['error' => 'Course not found']);
    exit;
}

// 🎯 Validate grade item
$grade_item_record = $DB->get_record('grade_items', [
    'courseid' => $courseid,
    'itemtype' => 'manual',
    'itemname' => $gradeitemname
]);
if (!$grade_item_record) {
    http_response_code(404);
    echo json_encode(['error' => 'Grade item not found']);
    exit;
}

// ✅ Fetch the grade_item object properly
try {
    $grade_item = grade_item::fetch(['id' => $grade_item_record->id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'grade_item_fetch_failed', 'message' => $e->getMessage()]);
    exit;
}

// 📝 Update grade and feedback
try {
    $grade_item->update_final_grade(
        userid: $user->id,
        finalgrade: $score,
        source: 'filter_echo_url',
        feedback: round($score) . '/100 - ' . $review,
        feedbackformat: FORMAT_PLAIN,
        usermodified: get_admin()->id
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'grade_update_failed', 'message' => $e->getMessage()]);
    exit;
}

echo json_encode(['status' => 'ok']);

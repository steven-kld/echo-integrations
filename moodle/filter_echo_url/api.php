<?php
define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once(dirname(__DIR__, 2) . '/config.php');

\core\session\manager::init_empty_session();
\core\session\manager::set_user(get_admin());

require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/enrol/manual/locallib.php');

header('Content-Type: application/json');

// 🔐 Token check (replace with your actual secret token)
$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
$authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');

if (strpos($authHeader, 'Bearer ') !== 0 || trim(substr($authHeader, 7)) !== 'yourSuperSecretTokenHere') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// 📥 Input
$input = json_decode(file_get_contents("php://input"), true);
$email = trim($input['email'] ?? '');
$courseid = (int) ($input['courseid'] ?? 0);
$score = (float) ($input['score'] ?? 100);
$feedback = $input['comment'] ?? 'Completed via Echo interview.';

// 📌 Validate input
if (empty($email) || empty($courseid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// 👤 Find user
$user = $DB->get_record('user', ['email' => $email]);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// 🎓 Find course
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    http_response_code(404);
    echo json_encode(['error' => 'Course not found']);
    exit;
}

// 🧮 Grade item check/create
try {
    $grade_item = grade_item::fetch([
        'courseid' => $courseid,
        'itemtype' => 'manual',
        'itemname' => 'Echo Interview'
    ]);

    if (!$grade_item) {
        $grade_item = new grade_item([
            'courseid' => $courseid,
            'itemtype' => 'manual',
            'itemname' => 'Echo Interview',
            'gradetype' => GRADE_TYPE_VALUE,
            'grademax' => 100,
            'grademin' => 0
        ]);
        $grade_item->insert();
    }

    // force-correct broken grade items
    if (empty($grade_item->id)) {
        $grade_item->insert();
    }
    if ($grade_item->gradetype != GRADE_TYPE_VALUE || $grade_item->grademax < $score) {
        $grade_item->gradetype = GRADE_TYPE_VALUE;
        $grade_item->grademax = 100;
        $grade_item->grademin = 0;
        $grade_item->scaleid = null;
        $grade_item->itemnumber = null;
        $grade_item->update();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'grade_item_error', 'message' => $e->getMessage()]);
    exit;
}

// 📝 Grade update
try {
    $grade_item->update_final_grade(
        $user->id,
        $score
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'grade_update_error',
        'message' => $e->getMessage(),
        'debug' => [
            'userid' => $user->id,
            'score' => $score,
            'source' => 'plugin',
            'component' => 'local_echo_integration'
        ]
    ]);
    exit;
}

// 🎉 Success
echo json_encode(['status' => 'ok']);

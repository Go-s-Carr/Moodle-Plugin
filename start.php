<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_login();
require_capability('local/autoteacher:use', context_system::instance());

global $DB, $USER, $OUTPUT;

$PAGE->set_url(new moodle_url('/local/autoteacher/start.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('startquiz', 'local_autoteacher'));
$PAGE->set_heading(get_string('pluginname', 'local_autoteacher'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $subject = trim(required_param('subject', PARAM_TEXT));
    $level   = required_param('level', PARAM_TEXT);
    $goal    = required_param('goal', PARAM_TEXT);

    if ($subject === '') {
        redirect(new moodle_url('/local/autoteacher/start.php'));
    }

    // Generate the array of 3 questions
    $questions = local_autoteacher_generate_test($subject, $level, $goal);

    $id = $DB->insert_record('local_autoteacher_session', (object) [
        'userid'       => $USER->id,
        'subject'      => $subject,
        'level'        => $level,
        'goal'         => $goal,
        'questions'    => json_encode($questions),
        'timecreated'  => time(),
    ]);

    redirect(new moodle_url('/local/autoteacher/take.php', ['id' => $id]));
}

echo $OUTPUT->header();

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/autoteacher/start.php'),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// Subject Input
echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', 'What topic do you want to be tested on?', ['for' => 'subject']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'subject', 'id' => 'subject',
    'class' => 'form-control', 'required' => 'required',
    'placeholder' => 'e.g. Photosynthesis, Python loops',
]);
echo html_writer::end_div();

// Level Select
echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', 'What is your current level?', ['for' => 'level']);
echo html_writer::select([
    'beginner' => 'Beginner (I am just starting)',
    'intermediate' => 'Intermediate (I know the basics)',
    'advanced' => 'Advanced (I want a challenge)'
], 'level', 'beginner', false, ['id' => 'level', 'class' => 'form-control']);
echo html_writer::end_div();

// Goal Input
echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', 'What is your goal for this session?', ['for' => 'goal']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'goal', 'id' => 'goal',
    'class' => 'form-control', 'required' => 'required',
    'placeholder' => 'e.g. I have a test tomorrow and need to focus on definitions',
]);
echo html_writer::end_div();

echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => 'Generate My Custom Test', 'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

echo html_writer::link(
    new moodle_url('/local/autoteacher/index.php'),
    get_string('backtolist', 'local_autoteacher'),
    ['class' => 'btn btn-link mt-3']
);
echo $OUTPUT->footer();

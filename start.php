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
    if ($subject === '') {
        redirect(new moodle_url('/local/autoteacher/start.php'));
    }

    $q = local_autoteacher_generate_question($subject);

    $id = $DB->insert_record('local_autoteacher_quiz', (object) [
        'userid'       => $USER->id,
        'subject'      => $subject,
        'questiontext' => $q['question'],
        'options'      => json_encode($q['options']),
        'correctindex' => $q['correct_index'],
        'explanation'  => $q['explanation'],
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

echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', get_string('subject', 'local_autoteacher'), ['for' => 'subject']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'subject', 'id' => 'subject',
    'class' => 'form-control', 'required' => 'required',
    'placeholder' => 'e.g. Photosynthesis',
]);
echo html_writer::end_div();

echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('generate', 'local_autoteacher'), 'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

echo html_writer::link(
    new moodle_url('/local/autoteacher/index.php'),
    get_string('backtolist', 'local_autoteacher'),
    ['class' => 'btn btn-link mt-3']
);
echo $OUTPUT->footer();

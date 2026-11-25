<?php
require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/autoteacher:use', context_system::instance());

global $DB, $USER, $OUTPUT;

$PAGE->set_url(new moodle_url('/local/autoteacher/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_autoteacher'));
$PAGE->set_heading(get_string('pluginname', 'local_autoteacher'));

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url('/local/autoteacher/start.php'),
    get_string('startquiz', 'local_autoteacher'),
    ['class' => 'btn btn-primary mb-3']
);

$quizzes = $DB->get_records('local_autoteacher_quiz', ['userid' => $USER->id], 'timecreated DESC');

if (!$quizzes) {
    echo $OUTPUT->notification(get_string('nomyquizzes', 'local_autoteacher'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('subject', 'local_autoteacher'),
        get_string('status', 'local_autoteacher'),
        get_string('grade', 'local_autoteacher'),
        '',
    ];
    foreach ($quizzes as $q) {
        $answered = ($q->chosenindex !== null);
        $status   = $answered ? get_string('answered', 'local_autoteacher')
                              : get_string('pending', 'local_autoteacher');
        $grade    = $answered ? ($q->iscorrect ? '1 / 1' : '0 / 1') : '-';
        $action   = html_writer::link(
            new moodle_url('/local/autoteacher/take.php', ['id' => $q->id]),
            $answered ? get_string('youranswer', 'local_autoteacher') : get_string('chooseanswer', 'local_autoteacher')
        );
        $table->data[] = [format_string($q->subject), $status, $grade, $action];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();

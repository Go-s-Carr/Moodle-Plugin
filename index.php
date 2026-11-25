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
    'Start a new diagnostic test',
    ['class' => 'btn btn-primary mb-3']
);

$sessions = $DB->get_records('local_autoteacher_session', ['userid' => $USER->id], 'timecreated DESC');

if (!$sessions) {
    echo $OUTPUT->notification(get_string('nomyquizzes', 'local_autoteacher'), 'info');
} else {
    $table = new html_table();
    $table->head = ['Topic', 'Level', 'Status', 'Score', ''];
    foreach ($sessions as $s) {
        $answered = ($s->answers !== null);
        $status   = $answered ? 'Completed' : 'Pending';
        $score    = $answered ? "{$s->score} / 3" : '-';
        $action   = html_writer::link(
            new moodle_url('/local/autoteacher/take.php', ['id' => $s->id]),
            $answered ? 'Review Answers' : 'Take Test'
        );
        $table->data[] = [format_string($s->subject), ucfirst($s->level), $status, $score, $action];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();

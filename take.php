<?php
require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/autoteacher:use', context_system::instance());

global $DB, $USER, $OUTPUT;

$id   = required_param('id', PARAM_INT);
$quiz = $DB->get_record('local_autoteacher_quiz', ['id' => $id, 'userid' => $USER->id], '*', MUST_EXIST);
$options = json_decode($quiz->options, true);

$PAGE->set_url(new moodle_url('/local/autoteacher/take.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(format_string($quiz->subject));
$PAGE->set_heading(get_string('pluginname', 'local_autoteacher'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $quiz->chosenindex === null) {
    require_sesskey();
    $chosen = required_param('option', PARAM_INT);

    if ($chosen < 0 || $chosen >= count($options)) {
        redirect(
            new moodle_url('/local/autoteacher/take.php', ['id' => $id]),
            get_string('selectanswer', 'local_autoteacher'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $DB->update_record('local_autoteacher_quiz', (object) [
        'id'           => $quiz->id,
        'chosenindex'  => $chosen,
        'iscorrect'    => ($chosen === (int) $quiz->correctindex) ? 1 : 0,
        'timeanswered' => time(),
    ]);

    redirect(new moodle_url('/local/autoteacher/take.php', ['id' => $id]));
}

echo $OUTPUT->header();

echo html_writer::tag('h3', format_string($quiz->subject));
echo html_writer::tag('p', format_string($quiz->questiontext), ['class' => 'lead']);

if ($quiz->chosenindex === null) {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/autoteacher/take.php', ['id' => $quiz->id]),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::start_div('mb-3');
    foreach ($options as $i => $opt) {
        $inputid = 'opt' . $i;
        echo html_writer::start_div('form-check');
        echo html_writer::empty_tag('input', [
            'type' => 'radio', 'name' => 'option', 'id' => $inputid,
            'value' => $i, 'class' => 'form-check-input',
        ]);
        echo html_writer::tag('label', format_string($opt),
            ['for' => $inputid, 'class' => 'form-check-label']);
        echo html_writer::end_div();
    }
    echo html_writer::end_div();

    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => get_string('submitanswer', 'local_autoteacher'),
        'class' => 'btn btn-primary',
    ]);
    echo html_writer::end_tag('form');
} else {
    $chosen = (int) $quiz->chosenindex;
    foreach ($options as $i => $opt) {
        $label = format_string($opt);
        if ($i === (int) $quiz->correctindex) {
            $label .= ' ' . html_writer::tag('span', ' ✔ ' . get_string('correctanswer', 'local_autoteacher'),
                ['class' => 'badge bg-success']);
        }
        if ($i === $chosen) {
            $label .= ' ' . html_writer::tag('span', ' ← ' . get_string('youranswer', 'local_autoteacher'),
                ['class' => 'badge bg-secondary']);
        }
        echo html_writer::tag('div', $label, ['class' => 'mb-2']);
    }

    $verdict = $quiz->iscorrect
        ? html_writer::tag('div', get_string('correct', 'local_autoteacher'), ['class' => 'alert alert-success'])
        : html_writer::tag('div', get_string('incorrect', 'local_autoteacher'), ['class' => 'alert alert-danger']);
    echo $verdict;

    echo html_writer::tag('p', get_string('grade', 'local_autoteacher') . ': '
        . ($quiz->iscorrect ? '1 / 1' : '0 / 1'));

    if (!empty($quiz->explanation)) {
        echo html_writer::tag('h5', get_string('explanation', 'local_autoteacher'));
        echo html_writer::tag('p', format_string($quiz->explanation));
    }
}

echo html_writer::link(
    new moodle_url('/local/autoteacher/index.php'),
    get_string('backtolist', 'local_autoteacher'),
    ['class' => 'btn btn-link mt-3']
);
echo $OUTPUT->footer();

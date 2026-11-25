<?php
require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/autoteacher:use', context_system::instance());

global $DB, $USER, $OUTPUT;

$id   = required_param('id', PARAM_INT);
$session = $DB->get_record('local_autoteacher_session', ['id' => $id, 'userid' => $USER->id], '*', MUST_EXIST);
$questions = json_decode($session->questions, true);

$PAGE->set_url(new moodle_url('/local/autoteacher/take.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(format_string($session->subject));
$PAGE->set_heading(get_string('pluginname', 'local_autoteacher'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $session->answers === null) {
    require_sesskey();
    
    $answers = [];
    $score = 0;
    
    // Process the 3 answers
    for ($i = 0; $i < 3; $i++) {
        $chosen = required_param('option_' . $i, PARAM_INT);
        $answers[$i] = $chosen;
        if ($chosen === (int) $questions[$i]['correct_index']) {
            $score++;
        }
    }

    $DB->update_record('local_autoteacher_session', (object) [
        'id'      => $session->id,
        'answers' => json_encode($answers),
        'score'   => $score,
    ]);

    redirect(new moodle_url('/local/autoteacher/take.php', ['id' => $id]));
}

echo $OUTPUT->header();

echo html_writer::tag('h3', format_string($session->subject) . ' - Test Results');
echo html_writer::tag('p', 'Level: ' . ucfirst($session->level) . ' | Goal: ' . format_string($session->goal), ['class' => 'text-muted']);

if ($session->answers === null) {
    // SHOW THE TEST
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/autoteacher/take.php', ['id' => $session->id]),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    foreach ($questions as $qIndex => $q) {
        echo html_writer::tag('h5', 'Question ' . ($qIndex + 1) . ': ' . format_string($q['question']), ['class' => 'mt-4']);
        
        foreach ($q['options'] as $oIndex => $opt) {
            $inputid = "opt_{$qIndex}_{$oIndex}";
            echo html_writer::start_div('form-check');
            echo html_writer::empty_tag('input', [
                'type' => 'radio', 'name' => 'option_' . $qIndex, 'id' => $inputid,
                'value' => $oIndex, 'class' => 'form-check-input', 'required' => 'required'
            ]);
            echo html_writer::tag('label', format_string($opt), ['for' => $inputid, 'class' => 'form-check-label']);
            echo html_writer::end_div();
        }
    }

    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => 'Submit Test', 'class' => 'btn btn-primary mt-4',
    ]);
    echo html_writer::end_tag('form');
} else {
    // SHOW THE RESULTS
    $answers = json_decode($session->answers, true);
    
    echo html_writer::tag('div', "Your Score: {$session->score} / 3", ['class' => 'alert alert-info h4']);

    foreach ($questions as $qIndex => $q) {
        echo html_writer::tag('h5', 'Question ' . ($qIndex + 1) . ': ' . format_string($q['question']), ['class' => 'mt-4']);
        
        $correctIdx = (int) $q['correct_index'];
        $chosenIdx = (int) $answers[$qIndex];
        $isCorrect = ($chosenIdx === $correctIdx);

        foreach ($q['options'] as $oIndex => $opt) {
            $label = format_string($opt);
            if ($oIndex === $correctIdx) {
                $label .= ' ' . html_writer::tag('span', ' ✔ Correct', ['class' => 'badge bg-success']);
            }
            if ($oIndex === $chosenIdx && !$isCorrect) {
                $label .= ' ' . html_writer::tag('span', ' ✘ Your answer', ['class' => 'badge bg-danger']);
            } elseif ($oIndex === $chosenIdx && $isCorrect) {
                $label .= ' ' . html_writer::tag('span', ' ← Your answer', ['class' => 'badge bg-secondary']);
            }
            echo html_writer::tag('div', $label, ['class' => 'mb-1']);
        }
        
        echo html_writer::tag('div', 'Explanation: ' . format_string($q['explanation']), ['class' => 'text-muted mb-3 small']);
    }
}

echo html_writer::link(
    new moodle_url('/local/autoteacher/index.php'),
    get_string('backtolist', 'local_autoteacher'),
    ['class' => 'btn btn-link mt-3']
);
echo $OUTPUT->footer();

<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

function local_autoteacher_generate_test(string $subject, string $level, string $goal): array {
    $endpoint = trim((string) get_config('local_autoteacher', 'apiendpoint'));
    $model    = trim((string) get_config('local_autoteacher', 'model'));
    $apikey   = trim((string) get_config('local_autoteacher', 'apikey'));

    if ($endpoint === '' || $model === '') {
        return local_autoteacher_fallback_test();
    }

    $prompt = "You are a teacher. A student wants a test on: \"{$subject}\".\n"
        . "Their level is: \"{$level}\".\n"
        . "Their goal is: \"{$goal}\".\n"
        . "Write exactly 3 multiple-choice questions tailored to these needs.\n"
        . "Return ONLY valid JSON (no markdown, no code fences) in exactly this shape:\n"
        . '{"questions":[{"question":"...","options":["a","b","c","d"],"correct_index":0,"explanation":"..."}, {"question":"...","options":["a","b","c","d"],"correct_index":1,"explanation":"..."}, {"question":"...","options":["a","b","c","d"],"correct_index":2,"explanation":"..."}]}' . "\n"
        . "Rules: exactly 3 questions, exactly 4 options each, exactly one correct, correct_index is 0-3.";

    $payload = json_encode([
        'model'    => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You reply with valid JSON only.'],
            ['role' => 'user',   'content' => $prompt],
        ],
    ]);

    $headers = ['Content-Type: application/json'];
    if ($apikey !== '') {
        $headers[] = 'Authorization: Bearer ' . $apikey;
    }

    try {
        $curl = new \curl();
        $curl->setHeader($headers);
        $raw = $curl->post($endpoint, $payload, ['CURLOPT_TIMEOUT' => 45]);
        $info = $curl->get_info();

        if (($info['http_code'] ?? 0) !== 200) {
            debugging('Auto Teacher: HTTP ' . ($info['http_code'] ?? '?') . ' | ' . $raw, DEBUG_DEVELOPER);
            return local_autoteacher_fallback_test();
        }

        $decoded = json_decode($raw, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        
        // Clean up potential markdown from AI
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        
        $parsed = json_decode(trim($content), true);
        
        if (isset($parsed['questions']) && is_array($parsed['questions']) && count($parsed['questions']) === 3) {
            return $parsed['questions'];
        }
        
        return local_autoteacher_fallback_test();
    } catch (\Throwable $e) {
        debugging('Auto Teacher Exception: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return local_autoteacher_fallback_test();
    }
}

function local_autoteacher_fallback_test(): array {
    $q = [];
    for ($i = 1; $i <= 3; $i++) {
        $q[] = [
            'question'      => "Demo Question $i: Which option is correct?",
            'options'       => ['Correct answer', 'Distractor 1', 'Distractor 2', 'Distractor 3'],
            'correct_index' => 0,
            'explanation'   => 'This is a demo explanation.',
        ];
    }
    return $q;
}

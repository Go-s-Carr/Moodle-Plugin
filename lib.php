<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

function local_autoteacher_generate_question(string $subject): array {
    $endpoint = trim((string) get_config('local_autoteacher', 'apiendpoint'));
    $model    = trim((string) get_config('local_autoteacher', 'model'));
    $apikey   = trim((string) get_config('local_autoteacher', 'apikey'));

    if ($endpoint === '' || $model === '') {
        return local_autoteacher_fallback_question();
    }

    $prompt = "You are a teacher. Write ONE multiple-choice question about the subject: \"{$subject}\".\n"
        . "Return ONLY valid JSON (no markdown, no code fences) in exactly this shape:\n"
        . '{"question":"...","options":["a","b","c","d"],"correct_index":0,"explanation":"..."}' . "\n"
        . "Rules: exactly 4 options, exactly one correct, correct_index is 0-3, explanation <= 2 sentences.";

    $payload = json_encode([
        'model'    => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You reply with JSON only.'],
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
        $raw = $curl->post($endpoint, $payload, ['CURLOPT_TIMEOUT' => 30]);
        $info = $curl->get_info();

        // DIAGNOSTIC MODE: Print the raw response if it's not a 200 OK.
        if (($info['http_code'] ?? 0) !== 200) {
            $error_msg = 'Auto Teacher Debug: HTTP ' . ($info['http_code'] ?? '?') . ' | Raw Response: ' . $raw;
            debugging($error_msg, DEBUG_DEVELOPER);
            return local_autoteacher_fallback_question();
        }

        $decoded = json_decode($raw, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        $parsed  = local_autoteacher_parse_ai_json($content);
        return $parsed ?? local_autoteacher_fallback_question();
    } catch (\Throwable $e) {
        debugging('Auto Teacher Exception: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return local_autoteacher_fallback_question();
    }
}

// Keep the rest of the functions identical to before
function local_autoteacher_parse_ai_json(string $raw): ?array {
    $raw = trim($raw);
    $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
    $raw = preg_replace('/\s*```$/', '', $raw);

    $data = json_decode($raw, true);
    if (!is_array($data)) { return null; }
    if (!isset($data['question'], $data['options'], $data['correct_index'])) { return null; }
    if (!is_array($data['options']) || count($data['options']) < 2) { return null; }

    $data['options'] = array_values(array_map('strval', $data['options']));
    $idx = (int) $data['correct_index'];
    if ($idx < 0 || $idx >= count($data['options'])) { return null; }

    return [
        'question'      => (string) $data['question'],
        'options'       => $data['options'],
        'correct_index' => $idx,
        'explanation'   => (string) ($data['explanation'] ?? ''),
    ];
}

function local_autoteacher_fallback_question(): array {
    return [
        'question'      => 'Demo mode: which option is correct?',
        'options'       => [
            'This is the correct answer (demo mode).',
            'This is a distractor.',
            'This is another distractor.',
            'This is the last distractor.',
        ],
        'correct_index' => 0,
        'explanation'   => 'Demo mode is active because the AI endpoint is not configured or the call failed.',
    ];
}

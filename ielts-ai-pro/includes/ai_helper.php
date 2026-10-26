<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/scoring.php';

function evaluate_writing_response(string $prompt, string $essay, string $taskType = 'writing_task2'): array {
    $wordCount = count_words($essay);
    $minWords  = ($taskType === 'writing_task1') ? 150 : 250;

    $provider  = strtolower(get_setting('ai_provider', AI_PROVIDER));
    $geminiKey = get_setting('gemini_api_key', GEMINI_API_KEY);
    $openaiKey = get_setting('openai_api_key', OPENAI_API_KEY);

    if ($provider === 'gemini' && $geminiKey !== '' && function_exists('curl_init')) {
        $live = call_gemini_ielts_grader('writing', $prompt, $essay, $minWords, $geminiKey);
        if ($live !== null) {
            return $live;
        }
    } elseif ($provider === 'openai' && $openaiKey !== '' && function_exists('curl_init')) {
        $live = call_openai_ielts_grader('writing', $prompt, $essay, $minWords, $openaiKey);
        if ($live !== null) {
            return $live;
        }
    }

    return offline_evaluate_writing($essay, $minWords, $taskType);
}

function evaluate_speaking_response(string $prompt, string $transcript): array {
    $provider  = strtolower(get_setting('ai_provider', AI_PROVIDER));
    $geminiKey = get_setting('gemini_api_key', GEMINI_API_KEY);
    $openaiKey = get_setting('openai_api_key', OPENAI_API_KEY);

    if ($provider === 'gemini' && $geminiKey !== '' && function_exists('curl_init')) {
        $live = call_gemini_ielts_grader('speaking', $prompt, $transcript, 120, $geminiKey);
        if ($live !== null) {
            return $live;
        }
    } elseif ($provider === 'openai' && $openaiKey !== '' && function_exists('curl_init')) {
        $live = call_openai_ielts_grader('speaking', $prompt, $transcript, 120, $openaiKey);
        if ($live !== null) {
            return $live;
        }
    }

    return offline_evaluate_speaking($transcript);
}

function count_words(string $text): int {
    $trimmed = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    if ($trimmed === '') {
        return 0;
    }
    return count(explode(' ', $trimmed));
}

function offline_evaluate_writing(string $essay, int $minWords, string $taskType): array {
    $words = count_words($essay);
    if ($words < 10) {
        return [
            'band' => 2.0,
            'word_count' => $words,
            'min_words' => $minWords,
            'provider' => 'offline_rubric',
            'criteria' => [
                'task_achievement' => 2.0,
                'coherence_cohesion' => 2.0,
                'lexical_resource' => 2.0,
                'grammatical_range' => 2.0,
            ],
            'strengths' => ['Attempt submitted.'],
            'improvements' => [
                "Your response is only {$words} words. Aim for at least {$minWords} words to meet the IELTS requirement."
            ],
            'summary' => "Response is far below the {$minWords}-word minimum requirement."
        ];
    }

    $lower = strtolower($essay);
    $paragraphs = array_filter(preg_split('/\R{2,}/', trim($essay)), fn($p) => trim($p) !== '');
    $paraCount = count($paragraphs);

    $transitions = ['however', 'furthermore', 'moreover', 'consequently', 'therefore', 'nevertheless',
                    'in addition', 'on the other hand', 'for instance', 'for example', 'in conclusion',
                    'overall', 'significantly', 'whereas', 'while', 'although', 'despite'];
    $transitionHits = 0;
    foreach ($transitions as $t) {
        if (str_contains($lower, $t)) {
            $transitionHits++;
        }
    }

    preg_match_all('/[a-z]{3,}/', $lower, $matches);
    $tokens = $matches[0] ?? [];
    $uniqueRatio = count($tokens) > 0 ? (count(array_unique($tokens)) / count($tokens)) : 0.4;
    $longWords = array_filter($tokens, fn($w) => strlen($w) >= 7);
    $longWordRatio = count($tokens) > 0 ? (count($longWords) / count($tokens)) : 0.1;

    $sentences = preg_split('/[.!?]+/', trim($essay), -1, PREG_SPLIT_NO_EMPTY);
    $sentenceCount = max(1, count($sentences));
    $avgSentenceLen = $words / $sentenceCount;

    $lengthRatio = min(1.15, $words / max(1, $minWords));
    $ta = 5.0 + ($lengthRatio * 2.2) + ($paraCount >= 3 ? 0.6 : -0.5);
    if ($words < $minWords) {
        $ta -= min(1.5, (($minWords - $words) / $minWords) * 2.0);
    }

    $cc = 5.2 + min(2.0, $transitionHits * 0.35) + (($paraCount >= 3 && $paraCount <= 6) ? 0.7 : 0.0);
    $lr = 5.0 + min(1.8, $uniqueRatio * 2.4) + min(1.5, $longWordRatio * 4.5);
    $gra = 5.2 + (($avgSentenceLen >= 14 && $avgSentenceLen <= 26) ? 1.4 : 0.5) + min(1.2, $transitionHits * 0.2);

    $criteria = [
        'task_achievement'   => round_ielts_band(max(3.5, min(8.5, $ta))),
        'coherence_cohesion' => round_ielts_band(max(3.5, min(8.5, $cc))),
        'lexical_resource'   => round_ielts_band(max(3.5, min(8.5, $lr))),
        'grammatical_range'  => round_ielts_band(max(3.5, min(8.5, $gra))),
    ];
    $overall = calculate_criteria_band($criteria);

    $strengths = [];
    $improvements = [];

    if ($words >= $minWords) {
        $strengths[] = "Meets the word count target ({$words} words vs {$minWords} minimum).";
    } else {
        $improvements[] = "Increase length to at least {$minWords} words (currently {$words} words) to avoid a Task Achievement penalty.";
    }

    if ($paraCount >= 3) {
        $strengths[] = "Clear multi-paragraph organization ({$paraCount} paragraphs).";
    } else {
        $improvements[] = "Organize your writing into 4 distinct paragraphs (Introduction, Overview/Body 1, Body 2, Conclusion).";
    }

    if ($transitionHits >= 4) {
        $strengths[] = "Effective use of cohesive devices and linking expressions ({$transitionHits} detected).";
    } else {
        $improvements[] = "Incorporate more varied academic linking phrases (e.g., 'consequently', 'on the other hand', 'furthermore').";
    }

    if ($longWordRatio >= 0.22) {
        $strengths[] = "Strong academic vocabulary range and lexical precision.";
    } else {
        $improvements[] = "Replace repetitive common words with more precise academic collocations and topic-specific vocabulary.";
    }

    return [
        'band'         => $overall,
        'word_count'   => $words,
        'min_words'    => $minWords,
        'provider'     => 'offline_rubric',
        'criteria'     => $criteria,
        'strengths'    => $strengths,
        'improvements' => $improvements,
        'summary'      => "Estimated Band {$overall} ({$words} words). Evaluated across official IELTS Writing criteria."
    ];
}

function offline_evaluate_speaking(string $transcript): array {
    $words = count_words($transcript);
    if ($words < 10) {
        return [
            'band' => 3.0,
            'word_count' => $words,
            'provider' => 'offline_rubric',
            'criteria' => [
                'fluency_coherence'  => 3.0,
                'lexical_resource'   => 3.0,
                'grammatical_range'  => 3.0,
                'pronunciation'      => 3.0,
            ],
            'strengths' => ['Response recorded.'],
            'improvements' => ['Provide fuller, multi-sentence answers across Parts 1, 2, and 3 (aim for 120+ total words).'],
            'summary' => 'Response is too brief for a high band score. Expand each answer with examples and reasons.'
        ];
    }

    $lower = strtolower($transcript);
    $markers = ['actually', 'for example', 'in my opinion', 'personally', 'furthermore', 'because', 'although', 'however', 'firstly', 'moreover'];
    $markerCount = 0;
    foreach ($markers as $m) {
        if (str_contains($lower, $m)) {
            $markerCount++;
        }
    }

    preg_match_all('/[a-z]{3,}/', $lower, $matches);
    $tokens = $matches[0] ?? [];
    $uniqueRatio = count($tokens) > 0 ? (count(array_unique($tokens)) / count($tokens)) : 0.45;

    $fc  = round_ielts_band(min(8.5, 5.0 + min(2.0, $words / 95.0) + min(1.2, $markerCount * 0.3)));
    $lr  = round_ielts_band(min(8.5, 5.0 + min(2.0, $uniqueRatio * 2.5) + min(1.0, $words / 140.0)));
    $gra = round_ielts_band(min(8.5, 5.0 + min(1.8, $words / 110.0) + min(1.2, $markerCount * 0.25)));
    $pro = round_ielts_band(($fc + $lr + $gra) / 3.0);

    $criteria = [
        'fluency_coherence' => $fc,
        'lexical_resource'  => $lr,
        'grammatical_range' => $gra,
        'pronunciation'     => $pro,
    ];
    $overall = calculate_criteria_band($criteria);

    return [
        'band'       => $overall,
        'word_count' => $words,
        'provider'   => 'offline_rubric',
        'criteria'   => $criteria,
        'strengths'  => [
            "Spoken response length of {$words} words demonstrates sustained communication.",
            "Used {$markerCount} discourse markers to connect ideas naturally."
        ],
        'improvements' => [
            'Extend Part 2 and Part 3 responses with concrete real-world examples and hypothetical conditionals.',
            'Vary intonation and stress key content words to enhance Pronunciation clarity.'
        ],
        'summary' => "Estimated Speaking Band {$overall} ({$words} words transcribed)."
    ];
}

function call_gemini_ielts_grader(string $module, string $prompt, string $responseText, int $minWords, string $apiKey): ?array {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent?key=' . urlencode($apiKey);
    $systemInstruction = "You are a senior certified IELTS examiner. Evaluate the student's {$module} response strictly according to official IELTS band descriptors (0.0 to 9.0 in 0.5 increments). Return ONLY valid JSON with keys: band (number), criteria (object with 4 numeric scores), strengths (array of strings), improvements (array of strings), summary (string).";
    $payload = [
        'contents' => [[
            'parts' => [[
                'text' => $systemInstruction . "

Prompt:
" . $prompt . "

Student Response:
" . $responseText
            ]]
        ]],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'temperature' => 0.2
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$raw) {
        return null;
    }
    $decoded = json_decode($raw, true);
    $jsonText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $parsed = json_decode($jsonText, true);
    if (!is_array($parsed) || !isset($parsed['band'])) {
        return null;
    }
    $parsed['band'] = round_ielts_band((float)$parsed['band']);
    $parsed['word_count'] = count_words($responseText);
    $parsed['min_words'] = $minWords;
    $parsed['provider'] = 'gemini';
    return $parsed;
}

function call_openai_ielts_grader(string $module, string $prompt, string $responseText, int $minWords, string $apiKey): ?array {
    $url = 'https://api.openai.com/v1/chat/completions';
    $payload = [
        'model' => OPENAI_MODEL,
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.2,
        'messages' => [
            ['role' => 'system', 'content' => "You are a certified IELTS examiner. Return JSON with keys: band (number), criteria (object with 4 criteria scores), strengths (array), improvements (array), summary (string)."],
            ['role' => 'user', 'content' => "Module: {$module}
Prompt: {$prompt}
Response:
{$responseText}"]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$raw) {
        return null;
    }
    $decoded = json_decode($raw, true);
    $jsonText = $decoded['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode($jsonText, true);
    if (!is_array($parsed) || !isset($parsed['band'])) {
        return null;
    }
    $parsed['band'] = round_ielts_band((float)$parsed['band']);
    $parsed['word_count'] = count_words($responseText);
    $parsed['min_words'] = $minWords;
    $parsed['provider'] = 'openai';
    return $parsed;
}

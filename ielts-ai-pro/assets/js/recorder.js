/**
 * IELTS AI Pro - Speaking Module Microphone Speech-to-Text & Cue Card Stopwatch
 */
(function () {
    'use strict';

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let activeRecognition = null;

    document.querySelectorAll('.btn-mic-record').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-target');
            const textarea = document.getElementById(targetId);
            if (!textarea) return;

            if (!SpeechRecognition) {
                alert('Your current browser does not support Web Speech-to-Text transcription. Please type your spoken response directly in the box below or use Google Chrome / Microsoft Edge.');
                textarea.focus();
                return;
            }

            if (btn.dataset.recording === '1') {
                if (activeRecognition) activeRecognition.stop();
                btn.dataset.recording = '0';
                btn.textContent = 'Record with Microphone (Speech-to-Text)';
                return;
            }

            const recognition = new SpeechRecognition();
            recognition.lang = 'en-US';
            recognition.interimResults = false;
            recognition.continuous = true;

            recognition.onresult = function (event) {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        transcript += event.results[i][0].transcript + ' ';
                    }
                }
                textarea.value = (textarea.value + ' ' + transcript).trim();
            };

            recognition.onerror = function () {
                btn.dataset.recording = '0';
                btn.textContent = 'Record with Microphone (Speech-to-Text)';
            };

            recognition.onend = function () {
                btn.dataset.recording = '0';
                btn.textContent = 'Record with Microphone (Speech-to-Text)';
            };

            activeRecognition = recognition;
            btn.dataset.recording = '1';
            btn.textContent = 'Stop Recording... (Listening)';
            recognition.start();
        });
    });

    // Part 2 Cue Card timers (1-min prep, 2-min speak)
    const prepBtn = document.getElementById('btn-prep-timer');
    const speakBtn = document.getElementById('btn-speak-timer');
    const cueStatus = document.getElementById('cue-timer-status');
    let cueInterval = null;

    function startCueCountdown(seconds, label) {
        if (cueInterval) clearInterval(cueInterval);
        let left = seconds;
        cueStatus.textContent = label + ': ' + left + 's';
        cueInterval = setInterval(function () {
            left--;
            if (left <= 0) {
                clearInterval(cueInterval);
                cueStatus.textContent = label + ' Completed!';
                return;
            }
            cueStatus.textContent = label + ': ' + left + 's';
        }, 1000);
    }

    if (prepBtn && cueStatus) {
        prepBtn.addEventListener('click', function () {
            startCueCountdown(60, 'Prep Time');
        });
    }
    if (speakBtn && cueStatus) {
        speakBtn.addEventListener('click', function () {
            startCueCountdown(120, 'Speaking Time');
        });
    }
})();

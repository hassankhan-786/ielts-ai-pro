/**
 * IELTS AI Pro - Main Frontend Interactions (AJAX Exam Submission, Word Counter, Audio TTS, Highlighting)
 */
(function () {
    'use strict';

    // 1. Live Word Counter for Writing Tasks
    document.querySelectorAll('.essay-textarea[data-counter]').forEach(function (ta) {
        const counterId = ta.getAttribute('data-counter');
        const badge = document.getElementById(counterId);
        if (!badge) return;
        const minWords = parseInt(badge.getAttribute('data-min') || '150', 10);

        function updateCount() {
            const text = ta.value.trim();
            const words = text === '' ? 0 : text.split(/\s+/).length;
            badge.textContent = words + ' / ' + minWords + '+ words';
            badge.style.background = words >= minWords ? '#dcfce7' : '#dbeafe';
            badge.style.color = words >= minWords ? '#166534' : '#1e40af';
        }
        ta.addEventListener('input', updateCount);
        updateCount();
    });

    // 2. Reading Passage Text Highlighter
    const highlightBtn = document.getElementById('btn-highlight-selection');
    if (highlightBtn) {
        highlightBtn.addEventListener('click', function () {
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0 || sel.isCollapsed) return;
            const range = sel.getRangeAt(0);
            const span = document.createElement('mark');
            span.className = 'highlight-mark';
            try {
                range.surroundContents(span);
                sel.removeAllRanges();
            } catch (e) {
                // Ignore cross-node selection edge case
            }
        });
    }

    // 3. Built-in Offline Listening Audio Synthesizer (Web Speech API)
    const playTtsBtn = document.getElementById('btn-play-tts');
    const stopTtsBtn = document.getElementById('btn-stop-tts');
    const toggleScriptBtn = document.getElementById('btn-toggle-transcript');
    const scriptBox = document.getElementById('listening-script');

    if (toggleScriptBtn && scriptBox) {
        toggleScriptBtn.addEventListener('click', function () {
            scriptBox.style.display = scriptBox.style.display === 'none' ? 'block' : 'none';
        });
    }

    if (playTtsBtn && scriptBox) {
        playTtsBtn.addEventListener('click', function () {
            if (!('speechSynthesis' in window)) {
                scriptBox.style.display = 'block';
                alert('Speech synthesis is not supported in this browser. The transcript has been opened for you.');
                return;
            }
            window.speechSynthesis.cancel();
            const utter = new SpeechSynthesisUtterance(scriptBox.textContent);
            utter.lang = 'en-GB';
            utter.rate = 0.95;
            window.speechSynthesis.speak(utter);
        });
    }

    if (stopTtsBtn && ('speechSynthesis' in window)) {
        stopTtsBtn.addEventListener('click', function () {
            window.speechSynthesis.cancel();
        });
    }

    // 4. Unified Exam Form AJAX Submission Handler
    document.querySelectorAll('form[data-endpoint]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (form.dataset.submitted === '1') return;
            form.dataset.submitted = '1';

            const endpoint = form.getAttribute('data-endpoint');
            const submitBtn = form.querySelector('#btn-submit-exam');
            const originalText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Grading & Calculating Official IELTS Band Score...';
            }

            const formData = new FormData(form);
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': (window.IELTS_CONFIG && window.IELTS_CONFIG.csrfToken) || ''
                },
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && data.success && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        form.dataset.submitted = '0';
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                        alert((data && data.error) || 'Submission error. Please check your connection.');
                    }
                })
                .catch(function (err) {
                    form.dataset.submitted = '0';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                    alert('Network error while submitting exam: ' + err.message);
                });
        });
    });

    // 5. AI Study Plan Generator on Dashboard
    const planBtn = document.getElementById('btn-generate-plan');
    const planContainer = document.getElementById('study-plan-container');
    if (planBtn && planContainer && window.IELTS_CONFIG) {
        planBtn.addEventListener('click', function () {
            planBtn.disabled = true;
            planBtn.textContent = 'Generating Personalized Plan...';
            fetch(window.IELTS_CONFIG.baseUrl + '/api/generate_study_plan.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': window.IELTS_CONFIG.csrfToken
                }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    planBtn.disabled = false;
                    planBtn.textContent = 'Generate AI Study Plan';
                    if (data && data.success && Array.isArray(data.plan)) {
                        let html = '<div class="card"><h3>Your Personalized AI 7-Day Study Plan (Focus: ' +
                            data.weakest_module.toUpperCase() + ')</h3><ul class="plan-list">';
                        data.plan.forEach(function (item) {
                            html += '<li><strong>' + item.day + ':</strong> ' + item.task + '</li>';
                        });
                        html += '</ul></div>';
                        planContainer.innerHTML = html;
                    }
                })
                .catch(function () {
                    planBtn.disabled = false;
                    planBtn.textContent = 'Generate AI Study Plan';
                });
        });
    }
})();

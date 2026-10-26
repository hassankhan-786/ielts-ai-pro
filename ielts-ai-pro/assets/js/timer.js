/**
 * IELTS AI Pro - Accurate Exam Countdown Timer & Auto-Submit Engine
 */
(function () {
    'use strict';

    const timerBox = document.getElementById('exam-timer');
    const display = document.getElementById('timer-display');
    const spentInput = document.getElementById('time_spent_seconds');
    if (!timerBox || !display) return;

    const totalSeconds = parseInt(timerBox.getAttribute('data-duration') || '3600', 10);
    let remaining = totalSeconds;
    let elapsed = 0;

    function formatTime(sec) {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    const intervalId = setInterval(function () {
        remaining--;
        elapsed++;
        if (spentInput) {
            spentInput.value = String(elapsed);
        }
        if (remaining <= 300) {
            timerBox.classList.add('timer-warning');
        }
        if (remaining <= 0) {
            clearInterval(intervalId);
            display.textContent = '00:00';
            const examForm = document.querySelector('form[data-endpoint]');
            if (examForm && !examForm.dataset.submitted) {
                alert('Time is up! Your IELTS exam attempt will now be submitted automatically.');
                examForm.requestSubmit();
            }
            return;
        }
        display.textContent = formatTime(remaining);
    }, 1000);
})();

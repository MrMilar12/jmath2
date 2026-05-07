(function () {
  const bubbles = document.querySelectorAll('.bubble');
  bubbles.forEach((bubble) => {
    bubble.addEventListener('click', () => {
      bubble.style.transform = 'scale(0.93)';
      setTimeout(() => { bubble.style.transform = ''; }, 140);
    });
  });

  document.querySelectorAll('.progress > span').forEach((el) => {
    const w = el.style.width;
    el.style.width = '0';
    requestAnimationFrame(() => {
      setTimeout(() => { el.style.width = w; }, 100);
    });
  });

  const wizard = document.querySelector('form[data-quiz-wizard="1"]');
  if (wizard) {
    const steps = Array.from(wizard.querySelectorAll('.quiz-step'));
    let current = 0;

    const showStep = (idx) => {
      steps.forEach((s, i) => s.classList.toggle('active', i === idx));
      current = idx;
    };

    if (steps.length > 0) {
      showStep(0);
    }

    wizard.addEventListener('click', (event) => {
      const next = event.target.closest('.js-next');
      const prev = event.target.closest('.js-prev');

      if (prev) {
        event.preventDefault();
        if (current > 0) showStep(current - 1);
        return;
      }

      if (next) {
        event.preventDefault();
        const checked = steps[current].querySelector('input[type="radio"]:checked');
        if (!checked) {
          if (window.Swal) {
            window.Swal.fire({
              icon: 'warning',
              title: 'Choose an answer first',
              text: 'Please select one option before continuing.'
            });
          }
          return;
        }
        if (current < steps.length - 1) showStep(current + 1);
      }
    });
  }

  const scoreBox = document.querySelector('[data-quiz-result="1"]');
  if (scoreBox && window.Swal) {
    const score = Number(scoreBox.getAttribute('data-score') || 0);
    window.Swal.fire({
      icon: score >= 70 ? 'success' : 'info',
      title: score >= 70 ? 'Great work!' : 'Keep practicing!',
      text: 'Your score is ' + score + '%.',
      confirmButtonText: 'Continue'
    });
  }

  const dragZone = document.querySelector('[data-drag-game="1"]');
  if (dragZone) {
    const target = dragZone.querySelector('.drop-target');
    dragZone.querySelectorAll('.drag-item').forEach((item) => {
      item.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('text/plain', item.getAttribute('data-value') || '');
      });
    });

    if (target) {
      target.addEventListener('dragover', (e) => e.preventDefault());
      target.addEventListener('drop', (e) => {
        e.preventDefault();
        const value = e.dataTransfer.getData('text/plain');
        const correct = target.getAttribute('data-correct');
        const ok = value === correct;
        target.textContent = ok ? 'Correct! ' + value : 'Try again';
        target.style.background = ok ? '#dcfce7' : '#fee2e2';
      });
    }
  }

  document.querySelectorAll('[data-fill-check="1"]').forEach((box) => {
    const input = box.querySelector('input[data-correct]');
    const btn = box.querySelector('.js-check-fill');
    const msg = box.querySelector('.fill-msg');

    if (!input || !btn || !msg) return;
    btn.addEventListener('click', () => {
      const answer = (input.value || '').trim();
      const correct = input.getAttribute('data-correct') || '';
      if (answer === correct) {
        msg.textContent = 'Correct! Great step-by-step reasoning.';
        msg.style.color = '#15803d';
      } else {
        msg.textContent = 'Not yet. Hint: substitute x=3 carefully.';
        msg.style.color = '#b91c1c';
      }
    });
  });
})();

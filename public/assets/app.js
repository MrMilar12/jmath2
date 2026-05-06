(function () {
  const bubbles = document.querySelectorAll('.bubble');

  bubbles.forEach((bubble) => {
    bubble.addEventListener('click', (event) => {
      bubble.classList.add('pop');
      bubble.style.transform = 'scale(0.9)';
      setTimeout(() => {
        bubble.style.transform = '';
      }, 180);
    });
  });
})();

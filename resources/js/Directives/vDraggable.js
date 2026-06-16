export const vDraggable = {
  mounted(el) {
    let isDragging = false;
    let startX, startY, initialX, initialY;
    let hasDragged = false;

    const onMouseDown = (e) => {
      // Ignore if clicking on interactive elements like buttons, inputs, selects, switches, links, etc.
      if (e.target.closest('button, input, select, textarea, a, [contenteditable], .toggle-switch, .settings-panel, .control-btn, .btn-zen-primary, .btn-zen-secondary, .btn-cancel-session, .dock-audio-pill')) return;

      isDragging = true;
      hasDragged = false;
      el.classList.add('dragging');

      startX = e.clientX;
      startY = e.clientY;
      initialX = el.offsetLeft;
      initialY = el.offsetTop;

      document.addEventListener('mousemove', onMouseMove);
      document.addEventListener('mouseup', onMouseUp);
    };

    const onMouseMove = (e) => {
      if (!isDragging) return;
      const dx = e.clientX - startX;
      const dy = e.clientY - startY;

      if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
        if (!hasDragged) {
          hasDragged = true;
          // Clear any right/bottom constraints to avoid CSS conflicting with left/top
          el.style.bottom = 'auto';
          el.style.right = 'auto';
        }
      }

      if (hasDragged) {
        el.style.left = `${initialX + dx}px`;
        el.style.top = `${initialY + dy}px`;
      }
    };

    const onMouseUp = () => {
      isDragging = false;
      el.classList.remove('dragging');
      document.removeEventListener('mousemove', onMouseMove);
      document.removeEventListener('mouseup', onMouseUp);

      // Prevent click events if we actually dragged the element
      if (hasDragged) {
        el.dataset.preventClick = "true";
        setTimeout(() => {
          delete el.dataset.preventClick;
        }, 50);

        // Convert positioning dynamically to top or bottom based on screen half
        const rect = el.getBoundingClientRect();
        const elementMiddleY = rect.top + rect.height / 2;
        const viewportHeight = window.innerHeight;

        if (elementMiddleY < viewportHeight / 2) {
          // Top half: anchor to top
          el.style.top = `${el.offsetTop}px`;
          el.style.bottom = 'auto';
        } else {
          // Bottom half: anchor to bottom
          el.style.bottom = `${viewportHeight - (el.offsetTop + el.offsetHeight)}px`;
          el.style.top = 'auto';
        }
      }
    };

    el.addEventListener('mousedown', onMouseDown);
    el._dragCleanup = () => {
      el.removeEventListener('mousedown', onMouseDown);
    };
  },
  unmounted(el) {
    if (el._dragCleanup) {
      el._dragCleanup();
    }
  }
};

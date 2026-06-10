export const vDraggable = {
  mounted(el) {
    let isDragging = false;
    let startX, startY, initialX, initialY;
    let hasDragged = false;

    // Search for a specific handle, otherwise use the whole element
    const dragHandle = el.querySelector('.drag-handle') || el;

    const onMouseDown = (e) => {
      // Ignore if clicking on interactive elements like buttons, inputs or switches
      if (e.target.closest('button, input, select, .toggle-switch, .settings-panel')) return;
      // If we have a dedicated handle, ensure we clicked inside it
      if (el.querySelector('.drag-handle') && !e.target.closest('.drag-handle')) return;

      isDragging = true;
      hasDragged = false;
      el.classList.add('dragging');

      startX = e.clientX;
      startY = e.clientY;
      initialX = el.offsetLeft;
      initialY = el.offsetTop;

      // Clear any right/bottom constraints to avoid CSS conflicting with left/top
      el.style.bottom = 'auto';
      el.style.right = 'auto';

      document.addEventListener('mousemove', onMouseMove);
      document.addEventListener('mouseup', onMouseUp);
    };

    const onMouseMove = (e) => {
      if (!isDragging) return;
      const dx = e.clientX - startX;
      const dy = e.clientY - startY;

      if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
        hasDragged = true;
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
      }
    };

    dragHandle.addEventListener('mousedown', onMouseDown);
    el._dragCleanup = () => {
      dragHandle.removeEventListener('mousedown', onMouseDown);
    };
  },
  unmounted(el) {
    if (el._dragCleanup) {
      el._dragCleanup();
    }
  }
};

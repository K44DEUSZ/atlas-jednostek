(() => {
  const normalize = (value) =>
    value
      .toLocaleLowerCase('pl')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/ł/g, 'l');

  document.querySelectorAll('.botud').forEach((directory) => {
    const controls = [];
    directory.querySelectorAll('.botud-filters select').forEach((select) => {
      const label = directory.querySelector(`label[for="${select.id}"]`);
      if (!label || !select.options.length) return;
      const wrapper = document.createElement('div');
      wrapper.className = 'botud-select';
      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.id = `${select.id}-control`;
      trigger.className = 'botud-select-trigger';
      trigger.setAttribute('role', 'combobox');
      trigger.setAttribute('aria-haspopup', 'listbox');
      trigger.setAttribute('aria-expanded', 'false');
      const value = document.createElement('span');
      value.id = `${select.id}-value`;
      value.className = 'botud-select-value';
      trigger.append(value);
      label.id = `${select.id}-label`;
      label.htmlFor = trigger.id;
      trigger.setAttribute('aria-labelledby', `${label.id} ${value.id}`);
      const list = document.createElement('div');
      list.id = `${select.id}-list`;
      list.className = 'botud-select-list';
      list.setAttribute('role', 'listbox');
      list.setAttribute('aria-labelledby', label.id);
      list.hidden = true;
      trigger.setAttribute('aria-controls', list.id);
      const nativeOptions = [...select.options];
      const rows = nativeOptions.map((option, index) => {
        const row = document.createElement('div');
        row.id = `${select.id}-option-${index}`;
        row.className = 'botud-select-option';
        row.setAttribute('role', 'option');
        const text = document.createElement('span');
        text.textContent = option.label;
        row.append(text);
        if (option.dataset.count !== undefined) {
          const count = document.createElement('span');
          count.className = 'botud-option-count';
          count.textContent = option.dataset.count;
          row.append(count);
        }
        row.addEventListener('pointerdown', (event) => event.preventDefault());
        row.addEventListener('click', () => choose(index));
        row.addEventListener('pointermove', (event) => {
          if (event.pointerType === 'mouse') setActive(index, false);
        });
        list.append(row);
        return row;
      });
      wrapper.append(trigger, list);
      select.after(wrapper);
      select.hidden = true;
      select.tabIndex = -1;
      select.setAttribute('aria-hidden', 'true');
      let expanded = false;
      let active = select.selectedIndex;
      let search = '';
      let searchAt = 0;

      function sync() {
        trigger.disabled = select.disabled;
        if (expanded && select.disabled) close(false);
        value.textContent = select.selectedOptions[0]?.label || '';
        rows.forEach((row, index) => {
          row.setAttribute(
            'aria-selected',
            String(index === select.selectedIndex)
          );
        });
        if (!expanded) active = select.selectedIndex;
      }

      function setActive(index, scroll = true) {
        active = Math.max(0, Math.min(rows.length - 1, index));
        rows.forEach((row, at) =>
          row.classList.toggle('is-active', at === active)
        );
        trigger.setAttribute('aria-activedescendant', rows[active].id);
        if (scroll) rows[active].scrollIntoView({ block: 'nearest' });
      }

      function position() {
        if (!expanded) return;
        const rect = trigger.getBoundingClientRect();
        const below = window.innerHeight - rect.bottom - 16;
        const above = rect.top - 16;
        const upwards =
          below < Math.min(260, list.scrollHeight) && above > below;
        wrapper.classList.toggle('opens-up', upwards);
        list.style.maxHeight = `${Math.max(72, Math.min(320, upwards ? above : below))}px`;
      }

      function open() {
        controls.forEach((control) => control.close(false));
        search = '';
        searchAt = 0;
        expanded = true;
        wrapper.classList.add('is-open');
        list.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        position();
        setActive(select.selectedIndex);
      }

      function close(commit = false) {
        if (!expanded) return;
        const chosen = active;
        expanded = false;
        list.hidden = true;
        wrapper.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.removeAttribute('aria-activedescendant');
        if (commit && select.selectedIndex !== chosen) {
          select.selectedIndex = chosen;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        sync();
      }

      function choose(index) {
        active = index;
        close(true);
        trigger.focus({ preventScroll: true });
      }

      trigger.addEventListener('click', () =>
        expanded ? close(false) : open()
      );
      trigger.addEventListener('keydown', (event) => {
        const key = event.key;
        if (event.ctrlKey || event.metaKey) return;
        if (key === 'Tab') {
          close(true);
          return;
        }
        if (key === 'Escape') {
          if (expanded) event.preventDefault();
          close(false);
          return;
        }
        if (key === 'Enter' || key === ' ') {
          event.preventDefault();
          if (expanded) close(true);
          else open();
          return;
        }
        if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(key)) {
          event.preventDefault();
          const wasOpen = expanded;
          if (!expanded) open();
          if (key === 'Home') setActive(0);
          else if (key === 'End') setActive(rows.length - 1);
          else if (key === 'ArrowUp' && event.altKey) close(true);
          else if (wasOpen) setActive(active + (key === 'ArrowDown' ? 1 : -1));
          return;
        }
        if (key.length === 1 && !event.altKey) {
          event.preventDefault();
          if (!expanded) open();
          const now = Date.now();
          search =
            now - searchAt < 700 ? search + normalize(key) : normalize(key);
          searchAt = now;
          const query = [...search].every(
            (character) => character === search[0]
          )
            ? search[0]
            : search;
          for (let step = 1; step <= nativeOptions.length; step++) {
            const index = (active + step) % nativeOptions.length;
            if (normalize(nativeOptions[index].label).startsWith(query)) {
              setActive(index);
              break;
            }
          }
        }
      });
      trigger.addEventListener('blur', () => close(true));
      document.addEventListener('pointerdown', (event) => {
        if (!wrapper.contains(event.target)) close(false);
      });
      window.addEventListener('resize', position);
      select.addEventListener('change', sync);
      controls.push({ close, sync });
      sync();
    });
    directory.addEventListener('botud:filter', () =>
      controls.forEach((control) => control.sync())
    );
  });
})();

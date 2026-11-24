(() => {
  const fields = [...document.querySelectorAll('[data-botud-icon-field]')];
  if (!fields.length) return;

  function refreshComposite() {
    const combined = fields.find(
      (field) => field.dataset.iconSlug === 'wielopoziomowe'
    );
    if (!combined || Number(combined.querySelector('[data-icon-id]').value) > 0)
      return;
    const icon = document.createElement('span');
    icon.className = 'botud-method-icon botud-method-icon--combined';
    icon.setAttribute('aria-hidden', 'true');
    ['zuchy', 'harcerze', 'starsi', 'wedrownicy'].forEach((slug) => {
      const source = fields
        .find((field) => field.dataset.iconSlug === slug)
        ?.querySelector('[data-icon-preview] > .botud-method-icon');
      if (!source) return;
      const part = document.createElement('span');
      part.className = 'botud-method-icon-part';
      source.childNodes.forEach((child) => part.append(child.cloneNode(true)));
      icon.append(part);
    });
    combined.querySelector('[data-icon-preview]').replaceChildren(icon);
  }

  fields.forEach((field) => {
    const input = field.querySelector('[data-icon-id]');
    const preview = field.querySelector('[data-icon-preview]');
    const fallback = field.querySelector('[data-icon-fallback]');
    const choose = field.querySelector('[data-icon-choose]');
    const remove = field.querySelector('[data-icon-remove]');
    const status = field.querySelector('[data-icon-status]');
    let frame;

    function updateId(id) {
      input.value = String(id);
      input.dispatchEvent(new Event('change', { bubbles: true }));
      remove.disabled = !id;
      refreshComposite();
    }

    choose.addEventListener('click', () => {
      if (!window.wp?.media) {
        status.textContent =
          'Nie udało się uruchomić biblioteki mediów. Odśwież stronę.';
        return;
      }
      if (!frame) {
        frame = window.wp.media({
          title: 'Wybierz ikonę metodyki',
          button: { text: 'Użyj tej ikony' },
          library: { type: 'image' },
          multiple: false,
        });
        frame.on('select', () => {
          const selected = frame.state().get('selection').first();
          if (!selected) return;
          const attachment = selected.toJSON();
          const id = Number(attachment.id);
          const source = attachment.sizes?.thumbnail?.url || attachment.url;
          if (typeof source !== 'string' || !source.trim()) {
            status.textContent = 'Wybierz poprawny obraz z biblioteki mediów.';
            return;
          }
          let url;
          try {
            url = new URL(source, window.location.href);
          } catch {
            status.textContent = 'Wybierz poprawny obraz z biblioteki mediów.';
            return;
          }
          if (
            !Number.isSafeInteger(id) ||
            id < 1 ||
            attachment.type !== 'image' ||
            !['http:', 'https:'].includes(url.protocol)
          ) {
            status.textContent = 'Wybierz poprawny obraz z biblioteki mediów.';
            return;
          }
          const image = document.createElement('img');
          image.src = url.href;
          image.alt = '';
          image.width = 32;
          image.height = 32;
          const icon = document.createElement('span');
          icon.className = 'botud-method-icon';
          icon.setAttribute('aria-hidden', 'true');
          icon.append(image);
          preview.replaceChildren(icon);
          updateId(id);
          status.textContent = 'Ikona została wybrana. Zapisz ustawienia.';
          choose.focus();
        });
        frame.on('open', () => {
          const selection = frame.state().get('selection');
          selection.reset();
          const id = Number(input.value);
          if (Number.isSafeInteger(id) && id > 0) {
            selection.add(window.wp.media.attachment(id));
          }
        });
      }
      frame.open();
    });

    remove.addEventListener('click', () => {
      preview.replaceChildren(fallback.content.cloneNode(true));
      updateId(0);
      status.textContent =
        'Przywrócono domyślną ikonę. Zapisz ustawienia, aby zachować zmianę.';
      choose.focus();
    });
  });
  refreshComposite();
})();

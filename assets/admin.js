(() => {
  const mapPalette = document.getElementById('botud-setting-map_palette');
  if (mapPalette) {
    const custom = document.querySelector('[data-botud-custom-map]');
    const updateMapFields = () => {
      custom.hidden = mapPalette.value !== 'custom';
      custom.classList.toggle('hidden', custom.hidden);
    };
    mapPalette.addEventListener('change', updateMapFields);
    updateMapFields();
  }
  const type = document.getElementById('botud-unit_type');
  if (!type || !window.botudEditor) return;
  const role = document.getElementById('botud-leader_role');
  const gender = document.getElementById('botud-leader_gender');
  const specialty = document.getElementById('botud-specialty');
  const club = document.getElementById('botud-club_type');
  const methods = [
    ...document.querySelectorAll('[name="botud_unit[methodologies][]"]'),
  ];
  const config = window.botudEditor;
  let previousType = type.value;
  function update() {
    document.querySelectorAll('[data-botud-types]').forEach((section) => {
      const visible = section.dataset.botudTypes
        .split(' ')
        .includes(type.value);
      section.hidden = !visible;
      section.classList.toggle('hidden', !visible);
      section.querySelectorAll('input, select').forEach((field) => {
        field.disabled = !visible;
      });
    });
    if (type.value !== previousType) {
      const choices = config.roleChoices[type.value] || {
        default: 'Funkcja wynikająca z typu jednostki',
      };
      role.replaceChildren(
        ...Object.entries(choices).map(
          ([value, label]) => new Option(label, value)
        )
      );
      previousType = type.value;
    }
    if (type.value === 'club' && club.value === 'rescue_group')
      specialty.value = 'rescue';
    document.querySelectorAll('[data-botud-specialty]').forEach((section) => {
      const visible =
        specialty.value === section.dataset.botudSpecialty &&
        ['troop', 'club'].includes(type.value);
      section.hidden = !visible;
      section.classList.toggle('hidden', !visible);
      section.querySelectorAll('input').forEach((field) => {
        field.disabled = !visible;
      });
    });
    const selected = methods
      .filter((field) => field.checked)
      .map((field) => field.value);
    let label = config.types[type.value] || 'Wybierz typ jednostki';
    if (type.value === 'troop')
      label =
        selected.length > 1 || (!selected.length && config.legacyMultilevel)
          ? 'Drużyna Wielopoziomowa'
          : config.troopLabels[selected[0]] || 'Drużyna';
    document.getElementById('botud-type-preview').textContent = label;
    const automaticAge = ['pack', 'troop'].includes(type.value);
    const manualAge = document.querySelector('[data-botud-manual-age]');
    manualAge.hidden = automaticAge;
    manualAge.classList.toggle('hidden', automaticAge);
    manualAge.querySelector('input').readOnly = automaticAge;
    const ageMethods = type.value === 'pack' ? ['cub'] : selected;
    const ranges = ageMethods
      .map((method) => config.ageRanges[method])
      .filter(Boolean);
    document.getElementById('botud-age-preview').textContent = ranges.length
      ? `${Math.min(...ranges.map((range) => range[0]))}–${Math.max(...ranges.map((range) => range[1]))} lat`
      : 'Uzupełnij metodyki';
    document.getElementById('botud-role-preview').textContent =
      config.roles[type.value]?.[role.value]?.[gender.value] ||
      'Osoba prowadząca';
  }
  [type, role, gender, specialty, club, ...methods].forEach((field) =>
    field.addEventListener('change', update)
  );
  update();
})();

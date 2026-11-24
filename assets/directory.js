(() => {
  let mapAssets;

  function readOptions(element, key) {
    try {
      return JSON.parse(element.dataset[key] || '{}');
    } catch {
      return null;
    }
  }

  function loadMapAssets(options) {
    if (mapAssets) return mapAssets;
    const stylesheet = new Promise((resolve, reject) => {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = options.styleUrl;
      link.onload = resolve;
      link.onerror = () =>
        reject(new Error('Map stylesheet could not be loaded.'));
      document.head.append(link);
    });
    const library = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = options.scriptUrl;
      script.onload = () => {
        if (window.L?.map) resolve(window.L.noConflict());
        else reject(new Error('Map library is unavailable.'));
      };
      script.onerror = () =>
        reject(new Error('Map library could not be loaded.'));
      document.head.append(script);
    });
    mapAssets = Promise.all([library, stylesheet]).then(([leaflet]) => leaflet);
    return mapAssets;
  }

  function fitLabels(mapElement, pins) {
    const box = mapElement.getBoundingClientRect();
    pins.forEach((pin) => {
      const label = pin.querySelector('.botud-pin-label');
      label.style.marginLeft = '0px';
      label.style.marginTop = '0px';
      const pinBox = pin.getBoundingClientRect();
      const isOutside =
        pinBox.right < box.left ||
        pinBox.left > box.right ||
        pinBox.bottom < box.top ||
        pinBox.top > box.bottom;
      pin.style.visibility = isOutside ? 'hidden' : '';
      pin.tabIndex = isOutside ? -1 : 0;
      if (isOutside) return;
      const bounds = label.getBoundingClientRect();
      const dx =
        Math.max(0, box.left + 4 - bounds.left) -
        Math.max(0, bounds.right - box.right + 4);
      const dy =
        Math.max(0, box.top + 4 - bounds.top) -
        Math.max(0, bounds.bottom - box.bottom + 4);
      label.style.marginLeft = dx + 'px';
      label.style.marginTop = dy + 'px';
    });
  }

  function attributionLink(label, url) {
    const link = document.createElement('a');
    link.href = url;
    link.textContent = label;
    link.target = '_blank';
    link.rel = 'noopener';
    return link.outerHTML;
  }

  async function initializeMap(directory, pins) {
    const mapElement = directory.querySelector('.botud-map');
    if (!mapElement) return;
    const options = readOptions(mapElement, 'mapConfig');
    const errorMessage = directory.querySelector('.botud-map-error');
    if (!options) {
      errorMessage.textContent =
        'Nie udało się przygotować mapy. Możesz korzystać z wykazu jednostek.';
      errorMessage.hidden = false;
      return;
    }
    let map;
    const staticElements = [...mapElement.childNodes];
    try {
      const leaflet = await loadMapAssets(options);
      const reducedMotion = matchMedia(
        '(prefers-reduced-motion: reduce)'
      ).matches;
      map = leaflet.map(mapElement, {
        zoomControl: false,
        dragging: true,
        scrollWheelZoom: true,
        touchZoom: true,
        keyboard: true,
        zoomAnimation: !reducedMotion,
        fadeAnimation: !reducedMotion,
        markerZoomAnimation: !reducedMotion,
        maxZoom: options.maxZoom,
        minZoom: 2,
        zoomSnap: 0.25,
      });
      mapElement.classList.add('is-interactive');
      const bounds = leaflet.latLngBounds(
        [options.bounds.south, options.bounds.west],
        [options.bounds.north, options.bounds.east]
      );
      const lines = leaflet
        .layerGroup(
          options.lines.map((line) =>
            leaflet.polyline(
              line.map(([lon, lat]) => [lat, lon]),
              {
                color: options.lineColor,
                weight: 5,
                opacity: 0.85,
                interactive: false,
              }
            )
          )
        )
        .addTo(map);

      const markers = new Map();
      pins.forEach((pin) => {
        leaflet.DomEvent.disableClickPropagation(pin);
        const marker = leaflet
          .marker([Number(pin.dataset.lat), Number(pin.dataset.lon)], {
            icon: leaflet.divIcon({
              html: pin,
              className: 'botud-marker',
              iconSize: [44, 44],
              iconAnchor: [22, 22],
            }),
            keyboard: false,
            zIndexOffset: 100,
          })
          .addTo(map);
        markers.set(pin, marker);
        pin.addEventListener('focus', () => marker.setZIndexOffset(1000));
        pin.addEventListener('blur', () =>
          marker.setZIndexOffset(
            pin.getAttribute('aria-pressed') === 'true' ? 1000 : 100
          )
        );
      });
      directory.addEventListener('botud:filter', () => {
        markers.forEach((marker, pin) =>
          marker.setZIndexOffset(
            pin.getAttribute('aria-pressed') === 'true' ? 1000 : 100
          )
        );
      });
      leaflet.control.scale({ imperial: false, maxWidth: 100 }).addTo(map);

      let hasManualView = false;
      let fittedZoom;
      function fitMap() {
        map.invalidateSize({ pan: false });
        map.fitBounds(bounds, {
          padding: [48, 42],
          maxZoom: Math.min(13, options.maxZoom),
          animate: false,
        });
        fittedZoom = map.getZoom();
        map.setMinZoom(Math.max(2, fittedZoom - 2));
        hasManualView = false;
      }
      function updateView() {
        fitLabels(mapElement, pins);
        directory.querySelector('[data-map-action="in"]').disabled =
          map.getZoom() >= map.getMaxZoom();
        directory.querySelector('[data-map-action="out"]').disabled =
          map.getZoom() <= map.getMinZoom();
        const ratio = Math.pow(2, map.getZoom() - fittedZoom);
        directory.querySelector('.botud-zoom-status').textContent =
          ratio === 1
            ? 'Widok ogólny'
            : 'Powiększenie ' +
              (ratio >= 1
                ? Math.round(ratio) + '×'
                : Math.round(ratio * 100) + '%');
      }
      fitMap();
      map.on('movestart', () => {
        hasManualView = true;
      });
      map.on('moveend zoomend', updateView);
      directory.querySelectorAll('[data-map-action]').forEach((button) => {
        button.addEventListener('click', () => {
          const action = button.dataset.mapAction;
          if (action === 'fit') fitMap();
          else if (action === 'in') map.zoomIn(1, { animate: !reducedMotion });
          else if (action === 'out')
            map.zoomOut(1, { animate: !reducedMotion });
          else {
            const offset = Math.max(
              60,
              Math.round(
                Math.min(mapElement.clientWidth, mapElement.clientHeight) / 3
              )
            );
            const directions = {
              north: [0, -offset],
              south: [0, offset],
              west: [-offset, 0],
              east: [offset, 0],
            };
            if (directions[action])
              map.panBy(directions[action], { animate: !reducedMotion });
          }
          updateView();
        });
      });
      new ResizeObserver(() => {
        map.invalidateSize({ pan: false });
        if (!hasManualView) fitMap();
        updateView();
      }).observe(mapElement);

      // No external layer exists until the visitor explicitly chooses the road map.
      let tiles;
      const mapMode =
        options.map_mode ||
        mapElement.dataset.mapMode ||
        (options.allowRoad ? 'switchable' : 'schematic');
      const modeButtons = [
        ...directory.querySelectorAll('button[data-map-mode]'),
      ];
      const activation = mapElement.querySelector('.botud-map-activation');
      if (activation) {
        leaflet.DomEvent.disableClickPropagation(activation);
        leaflet.DomEvent.disableScrollPropagation(activation);
      }
      function updateMode(isRoad) {
        mapElement.classList.toggle('is-road-map', isRoad);
        mapElement.dataset.activeMapMode = isRoad ? 'road' : 'schematic';
        modeButtons.forEach((button) =>
          button.setAttribute(
            'aria-pressed',
            String((button.dataset.mapMode === 'road') === isRoad)
          )
        );
        if (activation) activation.hidden = isRoad || mapMode !== 'road';
      }
      function showSchematic(error = false) {
        tiles?.off('tileerror');
        tiles?.remove();
        tiles = null;
        lines.addTo(map);
        updateMode(false);
        errorMessage.hidden = !error;
        if (error) {
          errorMessage.textContent =
            'Nie udało się pobrać podkładu mapy. Pokazujemy schemat. Punkty i wykaz nadal działają.';
        }
      }
      const sourceCredit =
        '© ' +
        attributionLink(
          'OpenStreetMap contributors',
          'https://www.openstreetmap.org/copyright'
        );
      const providerText = document.createElement('span');
      providerText.textContent = options.providerAttribution;
      const providerCredit = options.providerAttribution
        ? ' | ' +
          (options.providerAttributionUrl
            ? attributionLink(
                options.providerAttribution,
                options.providerAttributionUrl
              )
            : providerText.innerHTML)
        : '';
      function showRoad() {
        if (mapMode === 'schematic' || !options.allowRoad || tiles) return;
        try {
          const requestedTiles = leaflet.tileLayer(options.tilesUrl, {
            maxZoom: options.maxZoom,
            attribution: sourceCredit + providerCredit,
            referrerPolicy: 'strict-origin-when-cross-origin',
            updateWhenIdle: true,
            updateWhenZooming: false,
            keepBuffer: 0,
            detectRetina: false,
          });
          requestedTiles.on('tileerror', () => {
            if (tiles === requestedTiles) showSchematic(true);
          });
          tiles = requestedTiles;
          lines.remove();
          errorMessage.hidden = true;
          const moveFocus = activation?.contains(document.activeElement);
          updateMode(true);
          requestedTiles.addTo(map);
          if (moveFocus) mapElement.focus({ preventScroll: true });
        } catch {
          showSchematic(true);
        }
      }
      modeButtons.forEach((button) => {
        button.addEventListener('click', () => {
          if (button.dataset.mapMode === 'road') showRoad();
          else showSchematic();
        });
      });
      activation
        ?.querySelector('[data-map-activate]')
        ?.addEventListener('click', showRoad);
      updateMode(false);
      directory.querySelector('.botud-map-controls').hidden = false;
      directory.querySelector('.botud-map-tools').hidden = false;
      updateView();
    } catch {
      map?.remove();
      mapElement.replaceChildren(...staticElements);
      mapElement.className = 'botud-map';
      pins.forEach((pin) => {
        pin.style.visibility = '';
        pin.tabIndex = 0;
      });
      errorMessage.textContent =
        'Interaktywna mapa nie jest teraz dostępna. Możesz wybrać punkt na schemacie lub skorzystać z wykazu.';
      errorMessage.hidden = false;
    }
  }

  function initializeDirectory(directory) {
    const place = directory.querySelector('[data-place-select]');
    const kind = directory.querySelector('[data-kind-select]');
    const type = directory.querySelector('[data-type-select]');
    const facets = [...directory.querySelectorAll('[data-facet-select]')];
    const contextFields = [
      ...directory.querySelectorAll('[data-context-type]'),
    ];
    let selectedPoint = null;
    if (!place || !kind) return;
    const options = readOptions(directory, 'options') || {};
    const groups = [...directory.querySelectorAll('.botud-locality')];
    const pins = [
      ...directory.querySelectorAll('[data-place-filter], [data-point-filter]'),
    ];
    const kindButtons = [...directory.querySelectorAll('[data-kind-filter]')];
    const legacyHashes = options.legacyHashes || {};
    const initialKind = legacyHashes[location.hash.slice(1)];
    if (
      initialKind &&
      [...kind.options].some((option) => option.value === initialKind)
    ) {
      if (type) type.value = 'troop';
      kind.value = initialKind;
    }
    directory.querySelector('.botud-filters').hidden = false;
    const mapElement = directory.querySelector('.botud-map');
    if (mapElement) {
      new ResizeObserver(() => fitLabels(mapElement, pins)).observe(mapElement);
      document.fonts?.ready.then(() => fitLabels(mapElement, pins));
    }

    function matchesFacet(unit) {
      const keys = (unit.dataset.methods || unit.dataset.kind || '').split(' ');
      return (
        (!type?.value || type.value === unit.dataset.unitType) &&
        (!kind.value || keys.includes(kind.value)) &&
        facets.every(
          (select) =>
            !select.value ||
            unit.dataset[select.dataset.unitField] === select.value
        )
      );
    }

    function updateContext() {
      const activeType = type?.value || '';
      contextFields.forEach((field) => {
        const select = field.querySelector('select');
        const visible =
          field.dataset.contextType === activeType && select.options.length > 1;
        field.hidden = !visible;
        select.disabled = !visible;
        if (!visible) select.value = '';
      });
      directory.querySelector('.botud-kinds').hidden =
        activeType !== 'troop' ||
        options.showKindFilters === false ||
        !kindButtons.length;
      const columns = [
        ...directory.querySelectorAll('.botud-filter-field'),
      ].filter((field) => !field.hidden).length;
      directory
        .querySelector('.botud-filters')
        .style.setProperty('--botud-filter-columns', columns);
    }

    function filter() {
      updateContext();
      const visible = new Set();
      const selectedIds = selectedPoint
        ? new Set(selectedPoint.dataset.unitIds.split(' '))
        : null;
      groups.forEach((group) => {
        group.getAnimations().forEach((animation) => animation.cancel());
        group.style.overflow = '';
        let count = 0;
        group.querySelectorAll('.botud-unit').forEach((unit) => {
          unit.hidden = Boolean(
            (place.value && place.value !== group.dataset.place) ||
            !matchesFacet(unit) ||
            (selectedIds && !selectedIds.has(unit.dataset.unitId))
          );
          if (!unit.hidden) {
            count++;
            visible.add(unit.dataset.unitId);
          }
        });
        group.hidden = count === 0;
        group.querySelector('.botud-locality-count').textContent = count;
        group.open =
          Boolean(
            place.value ||
            kind.value ||
            facets.some((select) => select.value) ||
            type?.value ||
            selectedPoint ||
            options.expandGroups
          ) && count > 0;
      });
      pins.forEach((pin) => {
        const candidates = pin.dataset.placeFilter
          ? groups
              .find((group) => group.dataset.place === pin.dataset.placeFilter)
              .querySelectorAll('.botud-unit')
          : directory.querySelectorAll('.botud-unit');
        const ids = pin.dataset.unitIds
          ? new Set(pin.dataset.unitIds.split(' '))
          : null;
        const available = new Set(
          [...candidates]
            .filter(
              (unit) =>
                matchesFacet(unit) && (!ids || ids.has(unit.dataset.unitId))
            )
            .map((unit) => unit.dataset.unitId)
        );
        const count = available.size;
        const name = pin.querySelector('.botud-pin-label > span').textContent;
        pin.querySelector('small').textContent = count;
        pin.setAttribute('aria-label', name + ', liczba jednostek: ' + count);
        pin.setAttribute(
          'aria-pressed',
          String(
            pin.dataset.placeFilter
              ? place.value === pin.dataset.placeFilter
              : selectedPoint === pin
          )
        );
        pin.classList.toggle('is-empty', count === 0);
      });
      kindButtons.forEach((button) =>
        button.setAttribute(
          'aria-pressed',
          String(kind.value === button.dataset.kindFilter)
        )
      );
      const selection = directory.querySelector('.botud-selection');
      const facet = facets.find((select) => select.value);
      const hasFilters =
        place.value || kind.value || type?.value || facet || selectedPoint;
      if (selection) {
        const label = selectedPoint
          ? selectedPoint.querySelector('.botud-pin-label > span').textContent
          : place.value
            ? place.selectedOptions[0].textContent
            : kind.value
              ? kind.selectedOptions[0].textContent
              : facet
                ? facet.selectedOptions[0].textContent
                : type?.value
                  ? type.selectedOptions[0].textContent
                  : 'Wszystkie obszary';
        selection.textContent =
          label + (hasFilters ? ' · Liczba jednostek: ' + visible.size : '');
      }
      const showResults = directory.querySelector('.botud-show-results');
      if (showResults) showResults.hidden = !hasFilters;
      directory.querySelector('.botud-count').textContent =
        'Liczba jednostek: ' + visible.size;
      directory.querySelector('.botud-empty').hidden = visible.size !== 0;
      const hash = location.hash.slice(1);
      if (legacyHashes[hash] && legacyHashes[hash] !== kind.value)
        history.replaceState(
          history.state,
          '',
          location.pathname + location.search
        );
      if (mapElement) fitLabels(mapElement, pins);
      directory.dispatchEvent(new Event('botud:filter'));
    }
    [place, kind, type, ...facets].filter(Boolean).forEach((select) =>
      select.addEventListener('change', () => {
        if (select === place) selectedPoint = null;
        if (select === type) {
          kind.value = '';
          facets.forEach((facet) => {
            facet.value = '';
          });
        }
        filter();
      })
    );
    pins.forEach((pin) =>
      pin.addEventListener('click', () => {
        if (pin.dataset.placeFilter) {
          selectedPoint = null;
          place.value =
            place.value === pin.dataset.placeFilter
              ? ''
              : pin.dataset.placeFilter;
        } else {
          selectedPoint = selectedPoint === pin ? null : pin;
          place.value = '';
        }
        filter();
      })
    );
    kindButtons.forEach((button) =>
      button.addEventListener('click', () => {
        kind.value =
          kind.value === button.dataset.kindFilter
            ? ''
            : button.dataset.kindFilter;
        filter();
      })
    );
    directory.querySelector('.botud-reset').addEventListener('click', () => {
      place.value = '';
      kind.value = '';
      if (type) type.value = '';
      facets.forEach((facet) => {
        facet.value = '';
      });
      selectedPoint = null;
      filter();
    });
    groups.forEach((group) => {
      let animation;
      group.querySelector('summary').addEventListener('click', (event) => {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        event.preventDefault();
        if (animation?.playState === 'running') return;
        const wasOpen = group.open;
        const start = group.getBoundingClientRect().height;
        group.open = true;
        const end = wasOpen
          ? group.querySelector('summary').getBoundingClientRect().height + 2
          : group.scrollHeight + 2;
        group.style.overflow = 'hidden';
        animation = group.animate(
          { height: [start + 'px', end + 'px'] },
          { duration: 180, easing: 'ease-out' }
        );
        animation.onfinish = () => {
          group.open = !wasOpen;
          group.style.overflow = '';
          animation = null;
        };
      });
    });
    filter();
    initializeMap(directory, pins);
  }

  document.querySelectorAll('.botud').forEach(initializeDirectory);
})();

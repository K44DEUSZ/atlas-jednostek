(() => {
  const config = window.botudLocation;
  if (!config) return;
  const cache = new Map();
  let lastRequest = 0;
  const validPoint = (latitude, longitude) =>
    Number.isFinite(latitude) &&
    Number.isFinite(longitude) &&
    Math.abs(latitude) <= 85 &&
    Math.abs(longitude) <= 180;

  document
    .querySelectorAll('[data-botud-location-picker]')
    .forEach((picker) => {
      const find = (name) => picker.querySelector(`[data-location-${name}]`);
      const latitude = picker.querySelector('[name$="[latitude]"]');
      const longitude = picker.querySelector('[name$="[longitude]"]');
      const open = find('open');
      const view = find('view');
      const canvas = find('map');
      const status = find('status');
      const search = find('search');
      const query = find('query');
      const results = find('results');
      let map;
      let marker;
      let controller;
      let generation = 0;

      function readPoint() {
        const lat = Number(latitude.value.replace(',', '.'));
        const lon = Number(longitude.value.replace(',', '.'));
        return latitude.value !== '' &&
          longitude.value !== '' &&
          validPoint(lat, lon)
          ? [lat, lon]
          : null;
      }

      function showPoint() {
        const incomplete = (latitude.value === '') !== (longitude.value === '');
        latitude.setCustomValidity(
          incomplete && latitude.value === ''
            ? 'Wpisz szerokość geograficzną albo usuń cały punkt.'
            : ''
        );
        longitude.setCustomValidity(
          incomplete && longitude.value === ''
            ? 'Wpisz długość geograficzną albo usuń cały punkt.'
            : ''
        );
        const point = readPoint();
        if (map && marker) {
          marker.remove();
          marker = null;
        }
        if (point && map) {
          marker = L.marker(point, {
            draggable: true,
            title: 'Miejsce działania',
            autoPan: true,
          }).addTo(map);
          marker.on('dragend', () => setPoint(marker.getLatLng().wrap()));
        }
        status.textContent = point
          ? `Wybrany punkt: ${point[0]}, ${point[1]}. Zapisz zmiany w WordPressie.`
          : picker.dataset.locationEmpty;
      }

      function setPoint(point) {
        if (!validPoint(point.lat, point.lng)) return;
        latitude.value = String(Number(point.lat.toFixed(7)));
        longitude.value = String(Number(point.lng.toFixed(7)));
        [latitude, longitude].forEach((input) => {
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        showPoint();
      }

      open.addEventListener('click', () => {
        if (map) {
          generation += 1;
          controller?.abort();
          map.remove();
          map = null;
          marker = null;
          view.hidden = true;
          open.setAttribute('aria-expanded', 'false');
          open.textContent = 'Wybierz miejsce na mapie';
          return;
        }
        if (!window.L) {
          status.textContent =
            'Nie udało się uruchomić mapy. Możesz wpisać współrzędne ręcznie.';
          return;
        }
        view.hidden = false;
        open.setAttribute('aria-expanded', 'true');
        open.textContent = 'Zamknij mapę';
        map = L.map(canvas, {
          scrollWheelZoom: false,
          maxZoom: config.maxZoom,
          minZoom: 1,
          worldCopyJump: true,
        }).setView([20, 0], 2);
        L.tileLayer(config.tilesUrl, {
          maxZoom: config.maxZoom,
          attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' +
            (config.providerAttribution
              ? ` | ${config.providerAttribution}`
              : ''),
        })
          .on('tileerror', () => {
            status.textContent =
              'Podkład jest chwilowo niedostępny. Możesz wybrać wynik wyszukiwania lub wpisać współrzędne.';
          })
          .addTo(map);
        const point = readPoint();
        if (point) map.setView(point, 15);
        else if (config.points.length)
          map.fitBounds(config.points, { padding: [20, 20], maxZoom: 12 });
        map.on('click', (event) =>
          setPoint({
            lat: event.latlng.lat,
            lng: ((((event.latlng.lng + 180) % 360) + 360) % 360) - 180,
          })
        );
        showPoint();
      });

      find('center').addEventListener('click', () => {
        if (map) {
          const point = map.getCenter().wrap();
          setPoint(point);
        }
      });
      function clearPoint() {
        latitude.value = '';
        longitude.value = '';
        [latitude, longitude].forEach((input) =>
          input.dispatchEvent(new Event('change', { bubbles: true }))
        );
        showPoint();
      }
      find('clear').addEventListener('click', clearPoint);
      [latitude, longitude].forEach((input) => {
        input.addEventListener('change', showPoint);
        input.addEventListener('invalid', () => {
          find('coordinates').open = true;
        });
      });

      function showResults(features) {
        results.replaceChildren();
        features.slice(0, 5).forEach((feature) => {
          const coordinates = feature?.geometry?.coordinates;
          if (
            feature?.geometry?.type !== 'Point' ||
            !Array.isArray(coordinates) ||
            !validPoint(coordinates[1], coordinates[0])
          )
            return;
          const properties = feature.properties || {};
          const street = [properties.street, properties.housenumber]
            .filter((value) => typeof value === 'string')
            .join(' ');
          const label = [
            ...new Set(
              [
                properties.name,
                street,
                properties.postcode,
                properties.city || properties.town || properties.village,
                properties.state,
                properties.country,
              ].filter((value) => typeof value === 'string' && value)
            ),
          ]
            .join(', ')
            .replace(/[\u2013\u2014]/g, '-')
            .slice(0, 500);
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'button-link';
          button.textContent = label || 'Wybierz znaleziony punkt';
          button.addEventListener('click', () => {
            if (!map) return;
            setPoint({ lat: coordinates[1], lng: coordinates[0] });
            map.setView(
              [coordinates[1], coordinates[0]],
              Math.min(16, config.maxZoom)
            );
            status.textContent = `Wybrane miejsce: ${button.textContent}. Zapisz zmiany w WordPressie.`;
          });
          const row = document.createElement('li');
          row.append(button);
          results.append(row);
        });
        status.textContent = results.children.length
          ? 'Wybierz miejsce z wyników wyszukiwania.'
          : 'Nie znaleziono miejsca. Doprecyzuj adres albo wskaż punkt na mapie.';
      }

      async function searchAddress() {
        const text = query.value.trim();
        if (text.length < 3) {
          status.textContent =
            'Wpisz co najmniej 3 znaki adresu lub miejscowości.';
          return;
        }
        const key = `${config.searchUrl}|${text.toLocaleLowerCase()}`;
        if (cache.has(key)) {
          showResults(cache.get(key));
          return;
        }
        if (Date.now() - lastRequest < 1200) {
          status.textContent = 'Odczekaj chwilę przed kolejnym wyszukiwaniem.';
          return;
        }
        const current = ++generation;
        controller?.abort();
        controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        lastRequest = Date.now();
        search.disabled = true;
        results.replaceChildren();
        status.textContent = 'Wyszukiwanie miejsca...';
        try {
          const url = new URL(config.searchUrl, location.origin);
          url.searchParams.set('q', text);
          url.searchParams.set('limit', '5');
          const response = await fetch(url, {
            signal: controller.signal,
            credentials: 'omit',
            referrerPolicy: 'strict-origin-when-cross-origin',
          });
          if (!response.ok)
            throw new Error('Location provider request failed.');
          const body = await response.json();
          if (!Array.isArray(body.features))
            throw new Error('Invalid location provider response.');
          if (current !== generation) return;
          if (cache.size >= 50) cache.delete(cache.keys().next().value);
          cache.set(key, body.features);
          showResults(body.features);
        } catch {
          if (current === generation)
            status.textContent =
              'Wyszukiwarka jest chwilowo niedostępna. Spróbuj ponownie lub wskaż punkt na mapie.';
        } finally {
          clearTimeout(timeout);
          search.disabled = false;
        }
      }
      if (search) {
        search.addEventListener('click', searchAddress);
        query.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
            if (!search.disabled) searchAddress();
          }
        });
      }
      const form = picker.closest('form');
      const table = document.getElementById('the-list');
      if (form?.id === 'addtag' && table) {
        const validateLocation = (event) => {
          showPoint();
          const invalid = [latitude, longitude].find(
            (input) => !input.validity.valid
          );
          if (invalid) {
            event.preventDefault();
            event.stopImmediatePropagation();
            find('coordinates').open = true;
            invalid.reportValidity();
          }
        };
        form
          .querySelector('#submit')
          ?.addEventListener('click', validateLocation, true);
        form.addEventListener('submit', validateLocation, true);
        // WordPress clears only visible fields after AJAX term creation, without a reset event.
        const rows = new Set(
          [...table.querySelectorAll('tr[id]')].map((row) => row.id)
        );
        new MutationObserver(() => {
          const added = [...table.querySelectorAll('tr[id]')].filter(
            (row) => !rows.has(row.id)
          );
          added.forEach((row) => rows.add(row.id));
          if (added.length && form.querySelector('#tag-name')?.value === '') {
            clearPoint();
            results?.replaceChildren();
          }
        }).observe(table, { childList: true });
      }
      showPoint();
    });
})();

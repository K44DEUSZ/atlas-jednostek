# Atlas Jednostek

Wersja 1.0.1. Autor: [kaadeusz.pl](https://kaadeusz.pl/).

Wtyczka WordPress do prowadzenia publicznego wykazu jednostek ZHP z typami, metodykami, osobami prowadzącymi, kontaktami i mapą miejsc działania. Panel korzysta z natywnych tabel, formularzy, metaboxów i biblioteki mediów WordPressa. Nie dodaje własnego arkusza CSS panelu.

## Instalacja

Wymagania: WordPress 6.6+, PHP 8.1+, współczesna przeglądarka. Brak zależności od ACF, Breakdance i płatnych licencji.

1. Wgraj `atlas-jednostek-1.0.1.zip` przez Wtyczki, Dodaj wtyczkę, Wyślij wtyczkę, a następnie ją aktywuj.
2. W menu Atlas Jednostek dodaj obszary, następnie jednostki.
3. Wstaw [atlas_jednostek] do bloku Krótki kod lub elementu Shortcode używanego buildera.

Nowa instalacja zawiera słownik oznaczeń, bez przykładowych jednostek, osób i miejsc. Zewnętrzny podkład jest domyślnie wyłączony.

Przy przejściu z wcześniejszego pakietu o folderze `bot-unit-directory` wykonaj pełną kopię WordPressa, dezaktywuj wcześniejszą wtyczkę i zainstaluj nowy ZIP. Nie uruchamiaj obu pakietów jednocześnie. Nowy pakiet korzysta z tych samych danych; usuń stare pliki dopiero po sprawdzeniu wykazu i panelu. Dotychczasowy shortcode `[bot_unit_directory]` pozostaje obsługiwanym aliasem. Kolejne aktualizacje pakietu `atlas-jednostek` wgrywaj przez zastąpienie istniejącej wtyczki.

## Typ jednostki i metodyki

Obsługiwane typy: Gromada Zuchowa, Drużyna, Klub, Krąg, Szczep, Związek Drużyn i Hufiec. Numer lub identyfikator jest osobnym, opcjonalnym polem tekstowym. Pełna nazwa jednostki pozostaje tytułem.

Gromada ma automatycznie metodykę zuchową. Dla drużyny wybierasz jedną lub więcej metodyk: zuchową, harcerską, starszoharcerską i wędrowniczą. Co najmniej dwie dają oznaczenie Drużyny Wielopoziomowej. Sama metodyka zuchowa zapisuje Gromadę Zuchową. Drużyna nadal pojawia się w filtrach każdej z wybranych metodyk.

Klub ma wybór HKS albo HGR. Dla HKS wybierasz specjalność. HGR otrzymuje specjalność ratowniczą. Lista obejmuje standardowe specjalności harcerskie i możliwość opisania innej zatwierdzonej specjalności.

Krąg może być akademicki, klerycki, instruktorski, starszyzny, seniorów lub łączyć wskazane grupy instruktorów, starszyzny i seniorów. Szczep, Związek Drużyn i Hufiec mają prosty formularz bez metodyk drużyny.

Pola niedotyczące wybranego typu są ukrywane. Weryfikacja wyborów działa również na serwerze.

## Osoba prowadząca i kontakty

Wpisujesz stopień oraz imię i nazwisko osoby. Funkcja i tekst przycisku mailowego powstają z typu jednostki i wybranej formy żeńskiej, męskiej lub neutralnej.

Gromada i drużyna mają drużynową lub drużynowego, szczep i związek drużyn odpowiedniego komendanta lub komendantkę, a hufiec komendanta lub komendantkę hufca. Dla kręgu wybierasz wariant komendanta lub przewodniczącego. W klubie domyślny jest przewodniczący, z gotowymi wariantami szefa, prezesa i komandora stosownie do tradycji środowiska. Nazw funkcji nie wpisuje się ręcznie.

Mail, telefon, Facebook, Instagram i strona internetowa są opcjonalne. Pusty kontakt nie tworzy przycisku. Przycisk maila otwiera program pocztowy; wtyczka nie wysyła wiadomości.

Zdjęcie lub znak jednostki wybierasz w natywnym bocznym panelu biblioteki mediów. Przygotuj WebP z opisową nazwą i poprawnym tekstem alternatywnym. Publiczna karta używa wariantu medium, wymiarów, srcset, altu i opóźnionego ładowania. Obraz zachowuje proporcje.

## Miejsce działania i współrzędne

Jednostce możesz przypisać kilka obszarów lub miejscowości. Każdy obszar ma własne pola szerokości i długości geograficznej. Opcjonalny import przyjmuje pełny link OpenStreetMap ze współrzędnymi, gdy pola liczbowe są puste.

Jednostka może dodatkowo otrzymać nazwę miejsca i własną parę współrzędnych. Tworzy wtedy osobną pinezkę, którą wybierzesz jednostkę w wykazie. Bez własnych współrzędnych korzysta z punktów obszarów. Jednostki wskazane dokładnie w tym samym punkcie są zgrupowane pod jedną pinezką. Liczniki nie powielają jednostki przypisanej do kilku obszarów.

Przycisk Wybierz miejsce na mapie jest dostępny w formularzu jednostki i obszaru. Otwórz mapę, wpisz adres lub miejscowość, kliknij Szukaj i wybierz wynik. Możesz też kliknąć na mapie albo przeciągnąć pinezkę. Współrzędne uzupełniają się automatycznie. Klawiaturą przesuniesz mapę strzałkami, a przyciskiem Ustaw punkt w środku mapy wybierzesz położenie. Usuń punkt czyści oba pola. Zmiany zapisuje dopiero zwykły przycisk WordPressa. Ręczna edycja współrzędnych pozostaje opcjonalna.

Podkład pobiera się po otwarciu edytora mapy. Wyszukiwanie wysyła do dostawcy tylko wpisany adres lub nazwę miejsca, po kliknięciu Szukaj lub naciśnięciu Enter. Nie ma automatycznego odpytywania podczas pisania ani odwrotnego wyszukiwania przy przesuwaniu pinezki. Wyniki są buforowane w pamięci karty, bez cookies i localStorage. Wyszukiwanie jest ograniczone do pięciu wyników i nie częściej niż co 1,2 sekundy. Błąd dostawcy nie usuwa punktu ani nie blokuje ręcznego wpisania współrzędnych.

Domyślna wyszukiwarka to publiczna instancja Photon prowadzona przez komoot. Jest usługą demonstracyjną z ograniczeniami rozsądnego użycia i bez gwarancji dostępności. W Ustawieniach, Zaawansowane możesz wyłączyć wyszukiwanie albo wskazać własny zgodny serwer Photon z obsługą CORS. Wtyczka nie korzysta z publicznego API Nominatim. Dostawca otrzymuje adres wpisany w wyszukiwaniu, IP i dane żądania przeglądarki. Używaj wyłącznie nazw miejsc przeznaczonych do publicznego pokazania. Wybór miejsca jest funkcją panelu, nie dodaje zewnętrznej wyszukiwarki odwiedzającym witrynę.

Współrzędne WGS84 podaje się w kolejności szerokość, długość. Obsługiwany zakres mapy: szerokość od -85 do 85, długość od -180 do 180. Wartość 0 jest poprawna. Puste muszą być oba pola. Błędny punkt nie zastępuje poprzednich danych. Redaktor wybiera miejsce przeznaczone do publicznego pokazania; wtyczka nie pobiera lokalizacji odwiedzającego.

Mapa obsługuje przybliżanie, oddalanie, dopasowanie do punktów, przeciąganie, dotyk, klawiaturę i przyciski kierunków. Kółko myszy przewija stronę. Wybór punktu, typu jednostki i metodyki można łączyć. Powtórne kliknięcie pinezki lub przycisku metodyki odznacza ten wybór, zachowując pozostałe filtry.

## Szkice i publikowanie

Szkice, oczekujące, prywatne i zaplanowane jednostki pozostają w panelu. Do publicznego wykazu, mapy i liczników trafiają tylko opublikowane rekordy. Niekompletnego nowego rekordu nie można opublikować również przez szybką edycję. Przy błędach widoczny jest komunikat, a istniejące poprawne dane pozostają zachowane.

Filtry statusów są natywne. Nie ma filtra Moje. Na liście można filtrować typ jednostki. W Opcjach ekranu włączysz dodatkowe kolumny numeru i kontaktu; domyślnie są ukryte dla czytelności nazw.

## Ustawienia

Wykaz: nazwa menu, nagłówki, wstęp i objaśnienie mapy.

Wygląd: paleta wykazu, własne kolory, zaokrąglenia, odstępy, układ obok siebie, mapa nad listą lub sama lista. Czcionka jest dziedziczona z motywu.

Mapa: lokalny schemat albo schemat z opcjonalnym podkładem OpenStreetMap, gładkie tło lub siatka punktów, kolorystyka jasna, leśna, ciepła lub własna, wysokości na komputerze i telefonie oraz etykiety i liczniki.

Zaawansowane: dostawca kafli, jego oznaczenia i polityka prywatności, limit przybliżenia, opcjonalne granice i lokalne linie GeoJSON. Zapis jednej zakładki zachowuje ustawienia pozostałych.

Kolory oznaczeń: natywna lista kolorów i kolejności oznaczeń. Typ jednostki i metodyki wybiera się w jej formularzu.

## Podkład drogowy i prywatność

Domyślny widok jest lokalnym schematem. Dopiero kliknięcie Mapa drogowa tworzy warstwę kafli od dostawcy. Powrót do schematu usuwa ją i zatrzymuje pobieranie nowych kafli. Zakończonych żądań nie można cofnąć. Po odświeżeniu ponownie widoczny jest schemat.

Wtyczka nie stosuje cookies, localStorage, analityki ani geolokalizacji. Nie przekazuje dostawcy nazw jednostek, kontaktów ani tekstu filtrów. Dostawca otrzymuje żądania kafli, w tym IP, dane przeglądarki, pochodzenie strony i obszar mapy. Przed publikacją dostosuj informację o prywatności do faktycznie używanej usługi.

Domyślny adres to https://tile.openstreetmap.org/{z}/{x}/{y}.png. Dopuszczalny jest inny adres HTTPS lub własna ścieżka rozpoczynająca się od /. Obsługiwane pola: {z}, {x}, {y}, opcjonalnie {s} i {r}. Adres jest publiczny, nie wpisuj tajnych kluczy. Oznaczenie autorów OpenStreetMap pozostaje widoczne.

Publiczny serwer OSM nie zapewnia SLA. Wtyczka korzysta z normalnego cache przeglądarki, bez pobierania obszarów na zapas i map offline. Dla większego ruchu wybierz odpowiedniego dostawcę.

## Aktualizacje i zgodność danych

Przed aktualizacją wykonaj pełną kopię WordPressa. Aktualizacje są ręczne przez ZIP. Wtyczka nie ma serwera aktualizacji ani kluczy licencyjnych. Dezaktywacja i usunięcie jej plików zachowują dane do odzyskania.

Źródło danych: `botud_unit`, `botud_place`, `botud_kind`, meta `_botud_unit` i `_botud_place` oraz opcja `botud_settings`. Te identyfikatory i stary alias shortcode są zachowane dla zgodności z istniejącymi instalacjami. Zmiany modelu mają osobny numer schematu `botud_schema_version`, niezależny od wersji wydania.

Aktualizacja schematu zachowuje nazwy, numery, kontakty, przypisania obszarów i kolory. Rozdziela znane etykiety funkcji od danych osoby. Wielopoziomowe jednostki o niepotwierdzonych metodykach zachowują oznaczenie i otrzymują wskazówkę do uzupełnienia w formularzu. Migracja nie przypisuje ich metodyk automatycznie.

## Kod i licencje

Kod jest rozdzielony na model organizacyjny, migracje, formularze, ustawienia, listę panelu, renderowanie i mapę. PHP używa PSR-12 oraz zachowanych kontraktów WordPressa. JS i CSS są formatowane Prettierem. Nazwy techniczne i komentarze są po angielsku, komunikaty oraz README po polsku. Nie ma procesu budowania wymaganego do działania ani zależności od repozytorium Standardu.

Leaflet 1.9.4 jest dołączony lokalnie. Ładuje się raz na dokument z mapą. Sam wykaz nie ładuje biblioteki mapowej. Błąd biblioteki przywraca statyczny schemat z filtrowaniem. Przy błędzie podkładu lista i punkty pozostają dostępne; przyciskiem Schemat można wrócić do lokalnego widoku.

Kod wtyczki: GPL-2.0-or-later, tekst w LICENSE.txt.
Leaflet: BSD-2-Clause, oryginalny tekst w assets/vendor/leaflet/LICENSE.
ZIP nie zawiera danych klienta, jednostek, osób, testów ani narzędzi rozwojowych.

## Rozwój i przygotowanie paczki

Repozytorium jest źródłem samodzielnej wtyczki. Skopiuj jego pliki do `wp-content/plugins/atlas-jednostek` w lokalnej instalacji WordPressa lub zainstaluj wygenerowany ZIP. Konfigurację, jednostki i media zapisuje WordPress; nie dodawaj bazy danych ani `wp-config.php` do repozytorium. Wtyczka nie wymaga kompilacji, Composera ani npm.

Z katalogu repozytorium wykonaj:

```text
php tools/check.php
node --check assets/admin.js
node --check assets/directory.js
node --check assets/location-picker.js
php tools/package.php
```

PHP 8.1+ jest wymagane do kontroli i pakowania. Pakowanie wymaga rozszerzenia PHP ZipArchive. Node.js jest potrzebny wyłącznie do sprawdzenia składni JavaScript. Narzędzie kontroli sprawdza składnię PHP i pliki wymagane przez pakiet. Gotowy ZIP trafi do `dist/`; można wskazać inny katalog jako argument `php tools/package.php /sciezka/do/katalogu`. Paczka korzysta z jawnej listy plików i pomija konfigurację Git, automatyzację oraz narzędzia rozwojowe.

Jeśli lokalne PHP ma zainstalowane, ale wyłączone rozszerzenie ZIP, możesz włączyć je dla jednej komendy: `php -d extension=zip tools/package.php`.

Przed wydaniem sprawdź na testowym WordPressie pustą instalację, zapis jednostki i obszaru, filtrowanie, mapę, klawiaturę, telefon oraz aktualizację z zachowaniem danych. Kontrola składni nie zastępuje tych prób. Nie ma automatycznych testów integracyjnych WordPressa w repozytorium.

Gałąź rozwojowa to `develop`. Zmianę wersji w nagłówku wtyczki, stałych `VERSION` i `ASSET_VERSION` oraz pliku `readme.txt` zapisuj razem z wpisem w `CHANGELOG.md`. Przed aktualizacją zachowaj poprzedni ZIP i pełną kopię bazy; powrót do starszego kodu po zmianie schematu może wymagać odtworzenia bazy. Problemy zgłaszaj opiekunowi przez [kaadeusz.pl](https://kaadeusz.pl/), podając wersje WordPressa, PHP i wtyczki oraz kroki odtworzenia bez danych osobowych.

## Źródła modelu i mapy

- Instrukcja PJO z 1 września 2025 r.: https://14.pruszkow.zhp.pl/wp-content/uploads/2026/03/Instrukcja-PJO-01.09.2025.pdf
- Instrukcja szczepów i związków drużyn: https://swidnica.zhp.pl/wp-content/uploads/2022/08/INSTRUKCJA-TWORZENIA-I-DZIALANIA-SZCZEPU-I-ZWIAZKU-DRUZYN.pdf
- Wykaz specjalności, z możliwością innych zatwierdzonych specjalności: https://www.wola.zhp.pl/wp-content/2014/05/zasady-pracy-specjalnosci.pdf
- Leaflet: https://leafletjs.com/download
- Kafle OpenStreetMap: https://operations.osmfoundation.org/policies/tiles/
- Autorzy mapy: https://www.openstreetmap.org/copyright
- Prywatność dostawcy: https://osmfoundation.org/wiki/Privacy_Policy
- Photon, API i zasady publicznej instancji: https://github.com/komoot/photon

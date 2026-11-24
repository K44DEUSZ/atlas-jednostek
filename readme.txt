=== Atlas Jednostek ===
Contributors: k44deusz
Tags: directory, map, scouting
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Wykaz jednostek ZHP z kontaktami, metodykami i mapą miejsc działania.

== Description ==

Atlas Jednostek pozwala prowadzić wykaz jednostek, przypisywać obszary działania i publikować mapę przez shortcode [atlas_jednostek]. Nie wymaga ACF, Breakdance ani płatnych licencji.

Nowa instalacja jest pusta. Jednostki, kontakty i ustawienia należą do witryny. Samodzielna wtyczka nie zawiera danych organizacji.

Lokalny schemat nie łączy się z dostawcą mapy. Opcjonalne kafle OpenStreetMap pobierają się po aktywacji mapy drogowej przez odwiedzającego. W panelu opcjonalne wyszukiwanie Photon działa po kliknięciu Szukaj. Usługi te otrzymują adres IP i dane żądania, a Photon także wpisaną nazwę miejsca. Dostawców można zmienić lub wyłączyć. Wtyczka nie używa cookies, analityki ani geolokalizacji. Szczegóły konfiguracji i prywatności są w README.md.

== Installation ==

1. Wgraj ZIP przez Wtyczki, Dodaj wtyczkę, Wyślij wtyczkę i aktywuj pakiet.
2. Dodaj obszary oraz jednostki w menu Atlas Jednostek.
3. Wstaw [atlas_jednostek] do strony.

Przed aktualizacją wykonaj pełną kopię WordPressa. Przy przejściu ze starego folderu bot-unit-directory najpierw dezaktywuj stary pakiet. Klucze danych i poprzedni shortcode są zachowane.

== Changelog ==

= 1.0.1 =
Samodzielny pakiet atlas-jednostek, zgodność dotychczasowych danych, dokumentacja i narzędzia wydania.

= 1.0 =
Wykaz, typy jednostek, metodyki, kontakty, obszary, mapa i wybór miejsca w panelu.

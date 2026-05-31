# Changelog

## 3.0.3

- Aktueller Stand basiert auf Cropper.js 2.x mit klar getrennter Asset-Struktur (`assets/vendor/cropper` fuer Vendor-Dateien, `assets/js` fuer Addon-Logik).
- Die PHP-Klassen sind auf den Namespace `FriendsOfRedaxo\Cropper` (inkl. Unternamespace `FriendsOfRedaxo\Cropper\Cropper`) ausgerichtet.
- Fuer Frontend-Assets ist ein pnpm-basierter Buildprozess vorgesehen (`pnpm install`, `pnpm build`).
- Der Arbeitsbereich im Medienpool ist auf stabile, kompakte Bedienung ausgelegt (Buehne, Werkzeugleiste, Sidebar und Vorschau).
- Es stehen zwei Ansichtsmodi fuer die Werkzeugleiste zur Verfuegung: `legacy` und `default`.
- Die Kompression ist konfigurierbar mit folgenden Defaults: JPEG-Qualitaet `100`, PNG-Kompression `9`.
- Die Anzeige der Kompressionseinstellungen im Zuschneiden-Formular wird ueber ein gemeinsames Setting gesteuert.
- Der Zuschneiden-Link in der Medienliste und in der Detailansicht wird nur bei unterstuetzten Formaten angezeigt (`jpg`, `jpeg`, `png`, `gif`).
- Beim Speichern als neue Datei bleiben relevante Metadaten erhalten; das Speichern ist auf stabile Rueckmeldung im Medienpool ausgelegt.
- Das YForm-Value `media_crop` bleibt Bestandteil des Addons.
- Der Image-Adapter basiert auf Zebra_Image 3.x.
- Die Addon-Analyse (Rexstan/PHPStan) ist auf den aktuellen Stand ausgerichtet.
# Changelog

## 3.0.3

- Erste veroeffentlichte 3.x-Version: Es gibt keinen Release-Upgradepfad innerhalb von 3.0.0 bis 3.0.2.
- Wichtiger Hinweis fuer Updates aus 2.x: Der Umstieg auf 3.x enthaelt technische Breaking Changes (Namespace, Asset-Struktur und JS-Basis).
- Bestehende Projektintegrationen aus 2.x sollten vor dem Update auf alte Klassenreferenzen, alte Asset-Pfade und Cropper-1.x-/jQuery-bezogene Anbindungen geprueft werden.

- Aktueller Stand basiert auf Cropper.js 2.x mit klar getrennter Asset-Struktur (`assets/vendor/cropper` fuer Vendor-Dateien, `assets/js` fuer Addon-Logik).
- Die PHP-Klassen sind auf den Namespace `FriendsOfRedaxo\Cropper` (inkl. Unternamespace `FriendsOfRedaxo\Cropper\Cropper`) ausgerichtet.
- Fuer bestehende Integrationen ist die Umstellung mit Ruecksicht auf deprecated-Kompatibilitaet fuer den alten Namespace erfolgt.
- Fuer Frontend-Assets ist ein pnpm-basierter Buildprozess vorgesehen (`pnpm install`, `pnpm build`).
- Der Arbeitsbereich im Medienpool ist auf stabile, kompakte Bedienung ausgelegt (Buehne, Werkzeugleiste, Sidebar und Vorschau).
- Es stehen zwei Ansichtsmodi fuer die Werkzeugleiste zur Verfuegung: `legacy` und `default`.
- Die Werkzeugsteuerung umfasst weiterhin Aktionen wie Drehen/Spiegeln sowie `fitImage` zum direkten Einpassen in die Buehne.
- Medienpool- und YForm-JavaScript sind auf die aktuelle Cropper.js-API ausgerichtet.
- Die Kompression ist konfigurierbar mit folgenden Defaults: JPEG-Qualitaet `100`, PNG-Kompression `9`.
- Die Anzeige der Kompressionseinstellungen im Zuschneiden-Formular wird ueber ein gemeinsames Setting gesteuert.
- Der Zuschneiden-Link in der Medienliste und in der Detailansicht wird nur bei unterstuetzten Formaten angezeigt (`jpg`, `jpeg`, `png`, `gif`).
- Beim Speichern als neue Datei bleiben relevante Metadaten erhalten; das Speichern ist auf stabile Rueckmeldung im Medienpool ausgelegt.
- Beim Speichern wird die Oberflaeche mit Ladehinweis abgesichert, um Mehrfachklicks und parallele Verarbeitung zu vermeiden.
- Die Sidebar-/Toolbar-Umschaltung ist auf stabiles Re-Layout der Buehne und Auswahl ausgelegt.
- Konfigurationswerte werden robust ausgewertet (inkl. legacy/checkbox-naher Formate).
- Typ- und Null-Sicherheitsprobleme in CropperExecutor, Medienpool-Seite und YForm-Value sind bereinigt.
- Das Verhalten im `MEDIA_LIST_FUNCTIONS`-Hook ist gegen Null-Callable-Faelle abgesichert.
- Das YForm-Value `media_crop` bleibt Bestandteil des Addons.
- Der Image-Adapter basiert auf Zebra_Image 3.x.
- Die Addon-Analyse (Rexstan/PHPStan) ist auf den aktuellen Stand ausgerichtet.
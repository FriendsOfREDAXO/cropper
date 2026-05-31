# Changelog

## 3.0.3

- Wichtiger Hinweis für Updates aus 2.x: Der Umstieg auf 3.x enthält technische Breaking Changes (Namespace, Asset-Struktur und JS-Basis).
- Bestehende Projektintegrationen aus 2.x sollten vor dem Update auf alte Klassenreferenzen, alte Asset-Pfade und Cropper-1.x-/jQuery-bezogene Anbindungen geprüft werden.
- Aktueller Stand basiert auf Cropper.js 2.x mit klar getrennter Asset-Struktur (`assets/vendor/cropper` für Vendor-Dateien, `assets/js` für Addon-Logik).
- Die PHP-Klassen sind auf den Namespace `FriendsOfRedaxo\Cropper` (inkl. Unternamespace `FriendsOfRedaxo\Cropper\Cropper`) ausgerichtet.
- Für bestehende Integrationen ist die Umstellung mit Rücksicht auf deprecated-Kompatibilität für den alten Namespace erfolgt.
- Für Frontend-Assets ist ein pnpm-basierter Buildprozess vorgesehen (`pnpm install`, `pnpm build`).
- Der Arbeitsbereich im Medienpool ist auf stabile, kompakte Bedienung ausgelegt (Bühne, Werkzeugleiste, Sidebar und Vorschau).
- Es stehen zwei Ansichtsmodi für die Werkzeugleiste zur Verfügung: `legacy` und `default`.
- Die Werkzeugsteuerung umfasst weiterhin Aktionen wie Drehen/Spiegeln sowie `fitImage` zum direkten Einpassen in die Bühne.
- Medienpool- und YForm-JavaScript sind auf die aktuelle Cropper.js-API ausgerichtet.
- Die Kompression ist konfigurierbar mit folgenden Defaults: JPEG-Qualität `100`, PNG-Kompression `9`.
- Die Anzeige der Kompressionseinstellungen im Zuschneiden-Formular wird über ein gemeinsames Setting gesteuert.
- Der Zuschneiden-Link in der Medienliste und in der Detailansicht wird nur bei unterstützten Formaten angezeigt (`jpg`, `jpeg`, `png`, `gif`).
- Beim Speichern als neue Datei bleiben relevante Metadaten erhalten; das Speichern ist auf stabile Rückmeldung im Medienpool ausgelegt.
- Beim Speichern wird die Oberfläche mit Ladehinweis abgesichert, um Mehrfachklicks und parallele Verarbeitung zu vermeiden.
- Die Sidebar-/Toolbar-Umschaltung ist auf stabiles Re-Layout der Bühne und Auswahl ausgelegt.
- Konfigurationswerte werden robust ausgewertet (inkl. legacy/checkbox-naher Formate).
- Typ- und Null-Sicherheitsprobleme in CropperExecutor, Medienpool-Seite und YForm-Value sind bereinigt.
- Das Verhalten im `MEDIA_LIST_FUNCTIONS`-Hook ist gegen Null-Callable-Fälle abgesichert.
- Das YForm-Value `media_crop` bleibt Bestandteil des Addons.
- Der Image-Adapter basiert auf Zebra_Image 3.x.
- Die Addon-Analyse (Rexstan/PHPStan) ist auf den aktuellen Stand ausgerichtet.

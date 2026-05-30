# Changelog

## 3.0.0

### Breaking Changes

- Namespace-Umstellung aus PR #57: PHP-Klassen liegen jetzt unter `FriendsOfRedaxo\Cropper` beziehungsweise in `FriendsOfRedaxo\Cropper\Cropper` für Klassen aus `lib/Cropper`.
- Die bisherige jQuery-Cropper-1.x-Einbindung wurde durch Cropper.js 2.x ersetzt. Alte gebündelte Vendor-Dateien in `assets/js` wurden entfernt.
- Vendor-Assets und Addon-eigene Assets sind jetzt getrennt. Generierte Fremdassets liegen unter `assets/vendor/cropper`, eigene Logik unter `assets/js`.

### Hinzugefügt

- pnpm-basierter Buildprozess für Browser-Assets. Cropper.js wird aus `node_modules` nach `assets/vendor/cropper` gebaut.
- Addon-lokale PHPStan/Rexstan-Konfiguration für reproduzierbare Analyse des Addons.
- Adapter für Zebra_Image, damit Vendor-Code sauber von Addon-Code getrennt bleibt.

### Geändert

- PR #57 von christophboecker: Namespace auf `FriendsOfRedaxo\Cropper` umgestellt und deprecated-Kompatibilität für den alten Namespace ergänzt.
- PR #58 von tyrant88: Beim Zuschneiden bleiben Titel und `med_*`-Metafelder erhalten; damit ist Issue #44 adressiert.
- PR #55: Zebra_Image auf 3.0.0 aktualisiert.
- PR #52: YForm-Value `media_crop` hinzugefügt, inklusive Cropper-UI, Upload-/Delete-Handling, Required-Regeln, Preview-Konfiguration und begleitender Sprach-/README-Anpassungen.
- Medienpool- und YForm-JavaScript auf die aktuelle Cropper.js-API umgestellt.
- Bootstrapping und Asset-Ladepfade bereinigt, sodass Vendor- und Addon-Dateien klar getrennt sind.

### Behoben

- Rexstan-Lauf für das Addon bereinigt; die aktuelle Version läuft ohne Analysefehler.
- Typ- und Null-Sicherheitsprobleme in CropperExecutor, Medienpool-Seite und YForm-Value behoben.
- Zuschneiden behält jetzt die normalen REDAXO-Metadaten im Speichervorgang konsistent bei.
- Beim Speichern wird die UI jetzt mit Ladehinweis blockiert, um Mehrfachklicks und parallele Verarbeitungen zu verhindern (Issue #56).

### Zusammenfassung der gemergten PRs seit 2.0.3

- #52 Yform Value hinzugefügt
- #55 zebra update to 3.0.0 now with namespace
- #57 Namespace geändert in FriendsOfRedaxo\Cropper
- #58 Keep title and meta info fields when cropping (#44)
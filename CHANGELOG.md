# Changelog

## 3.0.2

### Geaendert

- Neue Einstellung `toolbar_mode` als Select mit den Modi Modern, Compact und Legacy Compact.
- Standardmodus fuer neue und migrierte Installationen auf Legacy Compact vereinheitlicht.
- Overlay-Positionierung und Verdichtung der Legacy-Toolbars in der Buehne weiter optimiert.

### Behoben

- Backend-Fatal in den Einstellungen behoben (`rex_form_select_element::addOption()` wurde auf die korrekte Select-API umgestellt).
- "Speichern als neue Datei" stabilisiert: robustere Dateierstellung inkl. sauberem Mediapool-Sync und klareren Fehlermeldungen.
- Legacy-Toolbar ueberdeckt den Modus-Hinweis nicht mehr; obere Toolbar bleibt einzeilig und bricht nicht um.

## 3.0.0

### Breaking Changes

- Namespace-Umstellung aus PR #57: PHP-Klassen liegen jetzt unter `FriendsOfRedaxo\Cropper` beziehungsweise in `FriendsOfRedaxo\Cropper\Cropper` für Klassen aus `lib/Cropper`.
- Die bisherige jQuery-Cropper-1.x-Einbindung wurde durch Cropper.js 2.x ersetzt. Alte gebündelte Vendor-Dateien in `assets/js` wurden entfernt.
- Vendor-Assets und Addon-eigene Assets sind jetzt getrennt. Generierte Fremdassets liegen unter `assets/vendor/cropper`, eigene Logik unter `assets/js`.

### Hinzugefügt

- pnpm-basierter Buildprozess für Browser-Assets. Cropper.js wird aus `node_modules` nach `assets/vendor/cropper` gebaut.
- Addon-lokale PHPStan/Rexstan-Konfiguration für reproduzierbare Analyse des Addons.
- Adapter für Zebra_Image, damit Vendor-Code sauber von Addon-Code getrennt bleibt.
- Optionaler Kompaktmodus fuer die Werkzeugleiste als rechte Overlay-Sidebar in der Buehne (inkl. Toggle-Button und integriertem Close-Button).
- Neue direkte `Fit`-Aktion in der Werkzeugleiste (`fitImage`), um das Bild wieder auf die Buehne einzupassen.
- Neue Einstellung `compact_toolbar_in_stage` inkl. Install-/Update-Defaults.

### Geändert

- PR #57 von christophboecker: Namespace auf `FriendsOfRedaxo\Cropper` umgestellt und deprecated-Kompatibilität für den alten Namespace ergänzt.
- PR #58 von tyrant88: Beim Zuschneiden bleiben Titel und `med_*`-Metafelder erhalten; damit ist Issue #44 adressiert.
- PR #55: Zebra_Image auf 3.0.0 aktualisiert.
- PR #52: YForm-Value `media_crop` hinzugefügt, inklusive Cropper-UI, Upload-/Delete-Handling, Required-Regeln, Preview-Konfiguration und begleitender Sprach-/README-Anpassungen.
- Medienpool- und YForm-JavaScript auf die aktuelle Cropper.js-API umgestellt.
- Bootstrapping und Asset-Ladepfade bereinigt, sodass Vendor- und Addon-Dateien klar getrennt sind.
- Cropper-Workspace visuell und funktional ueberarbeitet: kompaktere Werkzeugsteuerung, bessere Buehnennutzung und zusaetzliche Sidebar-Toggles.
- Tooltip-/Label-Texte und i18n-Keys fuer neue Actions und Sidebar-Steuerung erweitert.

### Behoben

- Rexstan-Lauf für das Addon bereinigt; die aktuelle Version läuft ohne Analysefehler.
- Typ- und Null-Sicherheitsprobleme in CropperExecutor, Medienpool-Seite und YForm-Value behoben.
- Zuschneiden behält jetzt die normalen REDAXO-Metadaten im Speichervorgang konsistent bei.
- Beim Speichern wird die UI jetzt mit Ladehinweis blockiert, um Mehrfachklicks und parallele Verarbeitungen zu verhindern (Issue #56).
- Robuste Auswertung von REDAXO-Checkbox-Konfigurationswerten (u.a. `|1|`) fuer Einstellungen wie `hide_edit_in_list` und `compact_toolbar_in_stage`.
- Fehler "Value of type null is not callable" im `MEDIA_LIST_FUNCTIONS`-Hook behoben.
- Re-Layout nach Sidebar-/Toolbar-Toggle stabilisiert, damit sich Buehne und Auswahl korrekt neu ausrichten und nicht "links haengen" bleiben.
- Overlay-Toolbar auf scrollbare, begrenzte Darstellung innerhalb der Buehne angepasst.

### Zusammenfassung der gemergten PRs seit 2.0.3

- #52 Yform Value hinzugefügt
- #55 zebra update to 3.0.0 now with namespace
- #57 Namespace geändert in FriendsOfRedaxo\Cropper
- #58 Keep title and meta info fields when cropping (#44)
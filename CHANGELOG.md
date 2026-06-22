# Changelog

## 3.0.8

- **Fix**: Falsches Seitenverhältnis im gespeicherten Ergebnis bei festen Ratios (z. B. 16:9) behoben.
  *Hintergrund*: Die Umrechnung von Auswahlkoordinaten auf Originalpixel erfolgte anhand der gesamten Canvas-Fläche. Bei letterboxed Darstellung (contain) führte das zu systematischen Abweichungen im Export.
  *Lösung*: Die sichtbare Bildbox (x/y/Breite/Höhe) wird nun vom Frontend mitgesendet und serverseitig als primäre Mapping-Basis verwendet; das bisherige Canvas-Mapping bleibt als Fallback erhalten.

## 3.0.7

Diese Version behebt primär tiefgreifende Vendor-Bugs und Unzulänglichkeiten der zugrundeliegenden Cropper.js 2.x Bibliothek durch eigene, robustere Logik-Überschreibungen:

- **Fix (Vendor-Workaround)**: 1px-Zittern (Jittering) der Auswahlkanten beim Skalieren behoben (tritt primär im Firefox und bei Subpixel-Zoomstufen auf). 
  *Hintergrund*: Die native Cropper.js Bibliothek zwingt die Maus-Koordinaten beim Ziehen standardmäßig in ein starres Pixelraster (`Math.round()`), was im Firefox zu einem permanenten 1px-Flackern zwischen Maus und Box führt. 
  *Lösung*: Wir haben den internen `precise`-Modus erzwungen, damit Fließkomma-Koordinaten erhalten bleiben.
- **Fix (Vendor-Workaround)**: Kontinuierliches "Wegdriften" des Auswahlrahmens beim freien Skalieren ohne festes Aspect-Ratio behoben.
  *Hintergrund*: Cropper.js summiert bei Drag-Bewegungen intern Deltas auf, was bei jeder Mausbewegung winzige Verschiebungen anhäuft und das Element staucht/verrutschen lässt.
  *Lösung*: Das native Scale-Event von Cropper.js wird nun unterbunden und durch einen eigenen, ankerbasierten Resize-Algorithmus ersetzt. Hierbei wird strikt die fixe, gegenüberliegende Kante als Ausgangspunkt genommen, wodurch rechnerisch kein Driften mehr entstehen kann.
- **Fix**: Verzerren des Vorschaubildes im Medienpool behoben.
  *Hintergrund*: Die Vendor-Styles des Croppers kamen bei dynamischen Zuschnitten nicht sauber mit dem Container-Platz zurecht, sodass die Vorschau gestaucht/gestreckt wurde.
  *Lösung*: Das Verhalten wurde durch unsere eigene CSS-Struktur (Flexbox und ein striktes `object-fit: contain`) überstimmt, damit immer der exakte Ausschnitt angezeigt wird.

## 3.0.6

- **Fix**: Darstellung der Tooltips (Hover) repariert, diese rendern nun sauber über allen Containern (`container: 'body'`).
- **Fix**: Erster Logik-Eingriff gegen das stetige Wegdriften der Cropper-Auswahl bei festen Aspect-Ratios (feste Kantenanker statt relativer Skalierung eingebaut).

## 3.0.5

- UI-Fixes im Medienpool-Cropper: Werkzeug- und Verhältnisleisten kompakter gestaltet und visuell vereinheitlicht.
- Aktiver Zustand in den Verhältnis-Buttons klar auf Blau vereinheitlicht.
- Sidebar-Toggle als Info-Button geschärft (Icon und Wording konsistent auf Info-Bereich).
- Vorschau-Skalierung verbessert: verfügbare Fläche wird besser genutzt.
- Zuschnittdaten erweitert: Auswahl zeigt jetzt Ansichtspixel und berechnete Output-Pixel.
- Responsive-Verhalten verbessert: stabilere Auswahl-/Positionswiederherstellung bei Resize und Layout-Änderungen.

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

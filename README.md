REDAXO-AddOn: Cropper und yform_mediacrop
================================================================================

Bildbearbeitung für REDAXO.

Stellt den Image-Cropper [Cropper.js](https://fengyuanchen.github.io/cropperjs/) im Medienpool zur Verfügung. Lizenz: [MIT](https://github.com/fengyuanchen/cropperjs/blob/master/LICENSE)

Das AddOn nutzt für die Browser-Assets Cropper.js 2.x. Vendor-Dateien liegen getrennt von den AddOn-eigenen Skripten unter `assets/vendor/cropper`, eigene Logik unter `assets/js`.

![Screenshot](https://github.com/FriendsOfREDAXO/cropper/raw/assets/cropper_screenshot.png)

## Features
- Zuschneiden 
- Drehen
- Spiegeln
- Speichern als neue Datei
- Speichern in ausgewählte Kategorie
- Überschreiben des Originals
- Rechte für Benutzer
- Einstellungsmöglichkeit für "Aspect Ratios"
- Einstellung für Zoom-Modus: Touch, Mouse
- Werkzeugleisten-Modus wählbar: Modern, Compact oder Legacy Compact
- Optionale Info-Sidebar (initial ein-/ausblendbar)

## Beschreibung 

Cropper stellt eine einfache Bildbearbeitung im Medienpoool zur Verfügung. Der Aufruf der Bildbearbeitung erfolgt über den Button `Bild bearbeiten`in der Detailansicht des Bildes. Die bearbeiteten Bilder, werden per default als neue Datei gespeichert. Ein Überschreiben des Originals ist möglich. Nur Admins erhalten das Recht die Qualität der Bilder zu verringern.

## Installation

1. Über Installer laden oder Zip-Datei im AddOn-Ordner entpacken, der Ordner muss „cropper“ heißen.
2. AddOn installieren und aktivieren.
3. Rechte für Rollen anpassen
4. Wenn gewünscht: eigene Vorgaben für Seitenverhältnisse in den Einstellungen hinterlegen

## Frontend-Build

Für Vendor-Updates von Cropper.js wird pnpm verwendet.

1. Im AddOn-Ordner `pnpm install` ausführen.
2. Danach `pnpm build` ausführen.

Der Build schreibt die Vendor-Artefakte nach `assets/vendor/cropper`. `node_modules` gehört nicht ins Repository.

## Yform media_crop

Das AddOn liefert ein Cropper Value media_crop mit. Es ist im Table-Manager und auch im Frontend verfügbar. 

Pipe-Schreibwiese 

`media_crop|crops|Bild |0|1|800|600|`

PHP Schreibweise 

`$yform->setValueField('media_crop', ['crops','Zugeschnitten ','0','1','800','600']);`

## Bugtracker

Du hast einen Fehler gefunden oder ein nettes Feature parat? [Lege bitte ein Issue an]

## Changelog

siehe [CHANGELOG.md](CHANGELOG.md) und [Release notes](https://github.com/FriendsOfREDAXO/cropper/releases)

## Hinweise zu 3.0.2

- Fokus auf UX-Stabilisierung der Overlay-Toolbars im Legacy-Modus.
- "Speichern als neue Datei" wurde intern robuster umgesetzt.
- Einstellungen fuer Toolbar-Modus wurden auf eine klarere Select-Auswahl umgestellt.

## Lizenz

siehe [LICENSE.md](https://github.com/FriendsOfREDAXO/cropper/blob/master/LICENSE.md)


## Autor

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

**Projekt-Lead**

[Alex Wenz](https://github.com/alexwenz)


**Credits**

Initiator: 
[Thomas Skerbis](https://github.com/skerbis)

1st Developer: 
[Joachim Dörr](https://github.com/joachimdoerr)

Danke an: 

- [Wasserverbund Niederrhein](https://wv-n.de)
- [REXFamilyWeek](https://ferien-am-tressower-see.de/rexfamilyweek-2023/)
- [Sven Haustein](https://github.com/shauste)




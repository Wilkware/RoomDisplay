# Raumdisplay (Room Display)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-7.0-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.2.20240823-orange.svg?style=flat-square)](https://github.com/Wilkware/RoomDisplay)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/RoomDisplay/style.yml?branch=main&label=CheckStyle&style=flat-square)](https://github.com/Wilkware/RoomDisplay/actions)

Das Modul verbindet ein openHASP-Display über MQTT mit dem IPS-System. Die für das Display gestalteten Seiten und ihren Objekten können mit Variablen oder Skripten von IPS synchronisiert werden.

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#user-content-4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#user-content-5-statusvariablen-und-profile)
6. [Visualisierung](#user-content-6-visualisierung)
7. [PHP-Befehlsreferenz](#user-content-7-php-befehlsreferenz)
8. [Versionshistorie](#user-content-8-versionshistorie)

### 1. Funktionsumfang

Das Modul übersetzt Aktionen und Ereignisse in IP-Symcon und aktualisiert umgekehrt die Variablen in IP-Symcon zur Darstellung auf 
dem Display.

Derzeit werden folgende UI-Objekte unterstützt:

* Arc
* Button
* Dropdown
* Gauge
* Image
* Label
* LED Inicator
* Line Meter
* Object
* Roller
* Slider
* Switch
* Toggle Button

Was macht bzw. was kann das Modul?

- Mapping von IPS-Werten auf UI-Objekte im Mini-Display
- Aktionen auf dem Display werden nach IPS gesendet
- Verschiedenste Konfigurationsmöglichkeiten zur visuellen Darstellung der Werte
- Unterstützt verschiedene Displayformate und Layouts.
- Übergabe der Aktion und Ausführung via Script
- Abfrage von (Status-)Informationen
- Ausführung von OpenHASP System- bzw. Globalen Kommandos

### 2. Voraussetzungen

* IP-Symcon ab Version 7.0

### 3. Installation

* Über den im Forum veröffentlichten [Test-Link](https://account.symcon.de/konto/test-einladung?code=58139433149646766846445124842805&bundle=de.wilkware.ips.modul.roomdisplay).
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/RoomDisplay` oder `git://github.com/Wilkware/RoomDisplay.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das _'Room Display'_-Modul (Alias: _'Raumdisplay'_) unter dem Hersteller _'(Geräte)'_ aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Anzeigegerät …

Name                        | Beschreibung
--------------------------- | ----------------------------------
Gerätenamen                 | Name des Gerätes (= Hostname)
Geräteadresse               | IP ist optional , aber ohne gehen Downloads von Screenshots und der Designdatei (pages.jsonl) nicht.

> Objektzuordnung …

Name                        | Beschreibung
--------------------------- | ----------------------------------
Objekte                     | Table zur Zuordnung zwischen UI- und IPS-Objekt

Hier eine kurze Erklärung der Spalten:

* _Typ_, _Seite_ und _ID_ - sollten selbsterklärend sein und identifizieren das UI-Object
* _Kommentar_ - auch klar, aber nicht ganz unwichtig um sich bei der Vielzahl von Mappings zurecht zu finden. Ich habe es extra eingeführt, weil ich zum Teil die Übersicht zu behalten. Empfind es es als sehr hilfreich.
* _Beschriftung_ - eigentlich alles was man bei der Visu an Text sieht. Also bei Buttons die Icons oder Titel, bei Labels die Beschriftung usw.
* _Wert_ - Zustände von Toggle Buttons, Sourcen von Images oder Hintergrundfarben von Objekten
* _Umrechnung_ - Transformationsweg von IPS zum DISPLAY, als Platzhalter kann {{val}} verwendet werden, was den Roh-Wert der Variablenänderung beinhaltet. Im Endeffekt ist das ein PHP eval() Ausdruck (ohne Klammern und Semikolon drum herum). Das Ergebnis davon wird dann bei Beschriftung oder/und Wert eingesetzt. Spezialwert -1 bedeutet keine weitere Auswertung vornehmen, also den Workflow stoppen.
* _ Rückrechnung_ - Transformationsweg von DISPLAY zu IPS. Das Gleiche wie bei Umrechnung nur Umgekehrt, d.h. eine -1 bewirkt keine Weiterverarbeitung in IPS.
* _Verknüpfung_ - die Verknüpfung zwischen Design-Objekt und IPS-Variable.

> Erweiterte Einstellungen …

Name                        | Beschreibung
--------------------------- | ----------------------------------
Hintergrundbeleuchtung automatisch dimmen! | Dimmt die Beleuchtung im kurzen Leerlauf (idle->short) auf ca 20% ab
Hintergrundbeleuchtung automatisch abschalten! | Schaltet die Beleuchtung im Leerlauf (idle->long) automatisch ab
Im Ruhezustand auf Seite 1 wechseln! | Schaltet im kurzen Leerlauf auf Seite 1 um (idle->short)
Nachricht an Skript weiterleiten: | Leitet die Aktion bzw. das Ereignis direkt weiter. Die Daten können im Script mit der Variable $_IPS['Data'] empfangen und ausgewertet werden.

Aktionsbereich:

> Buttonleiste ...

* _Vorher_, _Zurück_, _Weiter_ - eigentlich auch selbsterklärend, mit den Buttons kann man auf den Seiten navigieren
* _Seiten neu laden_ - liest die pages.jsonl neu ein und rendert die Seiten neu
* _Seiten löschen_ - alle Seiten löschen

> Aktion ausführen …

* _Stimmungslicht_ - Anfrage nach Informationen zu den aktuellen Moodlight-Einstellungen
* _Status_ - Anfrage nach Geräte-Status-Update-Informationen
* _Bildschirmfoto_ - Kommando zum Erstellen eines Screenshots senden
* _Synchronisieren_ - gerade in der Einstellung- bzw. Entwicklungsphase ein ganz wichtiger Button, er geht durch die Mappingliste und ruft für die verknüpften Variablen deren Werte ab und stellt sie dar. Diese Funktion wird auch immer aufgerufen wenn ein Gerät ONLINE geht! Gerade wenn man alles Seiten gelöscht hat und neu lädt ist der Button sehr hilfreich!
* _Neustart_ - Reboot des Gerätes

> Abfrage von …

* _Stimmungslicht_ - Anzeige der aktuellen Moodlight-Einstellungen
* _Status_ - Anzeige der Status-Update-Infos
* _Bildschirmfoto_ - Download und Speichern des angeforderten Screenshots (aktuelle Seite), siehe auch imgs Ordner im Modul
* _Seitenaufbau_ - Download und Speichern der pages.jsonl Datei (Backup-Zwecke)

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                        | Typ       | Beschreibung
--------------------------- | --------- | ----------------
Status                      | Boolean   | Verfügbarkeitsstaus (siehe WWXRD.Status)
Leerlauf                    | Integer   | Zeigt den Leerlaufzustand an (siehe WWXRD.Status)
Hintergrundbeleuchtung      | Integer   | Wert der Hintergrundbeleuchtung (siehe WWXRD.Backlight)
Seite                       | Integer   | Aktuell aufgezeigte Seite (siehe WWXRD.Page)

Folgendes Profil wird angelegt:

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
WWXRD.Status         | Boolean   | Online (true) bzw. Offline (false)
WWXRD.Idle           | Integer   | Aus, Kurz oder Lang (0, 1, 2)
WWXRD.Backlight      | Integer   | Helligkeitswert (1 .. 255)
WWXRD.Page           | Integer   | Seitennummer (1 .. 12)

### 6. Visualisierung

Man könnte die Statusvariablen direkt in die Visualisierung verlinken.

### 7. PHP-Befehlsreferenz

```php
void WWXRD_SendCommand(int $InstanzID, string $command);
```

Sendet ein Kommando via MQTT an das Display.  
Die Funktion liefert keinerlei Rückgabewert.

__Beispiel__: `WWXRD_SendCommand(12345, '["p1b2.text=Hallo"]');`

```php
void WWXRD_SendJSONL(int $InstanzID, array $data);
```

Sendet JSON Lines (JSONL) via MQTT an das Display.  
Die Funktion liefert keinerlei Rückgabewert.

__Beispiel__: `WWXRD_SendJSONL(12345, ['{"comment":" --- KOMMENTAR ZEILE --- "}']);`

### 8. Versionshistorie

v1.2.202408728

* _NEU_: Hintergrundbilder und Beispielbilder hinzugefügt
* _NEU_: Bibliotheks- bzw. Modulinfos vereinheitlicht
* _FIX_: Fehler beim Abrufen von 'MoodLight' korrigiert
* _FIX_: Problem beim Auswerten von booleschen Werten gelöst

v1.1.20240730

- NEU: Actionbereich komplett erweitert (Navigation, Aktionen, Informationen) 

v1.0.20240723

* _NEU_: Initialversion

## Danksagung

Ich möchte mich für die Unterstützung bei der Entwicklung dieses Moduls bedanken bei ...

* _firebuster_ : für die geniale und hervorragende Vorarbeit mit seinem __Modul openHASP__ :1:
* _ralf_: für den stetigen Austausch rund um das Display und Modulfunktionlitäten :-)

Vielen Dank an Euch!

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

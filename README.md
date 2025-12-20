# Raumdisplay (Room Display)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-4.0.20251220-orange.svg?style=flat-square)](https://github.com/Wilkware/RoomDisplay)
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

<details>
<summary>Derzeit werden folgende UI-Objekte unterstützt:</summary>

* Arc
* Bar
* Button
* Checkbox
* Dropdown
* Gauge
* Image
* Label
* LED Inicator
* Line Meter
* MessageBox
* Object
* Roller
* Slider
* Spinner
* Switch
* Toggle Button

</details>

Was macht bzw. was kann das Modul?

- Mapping von IPS-Werten auf UI-Objekte im Mini-Display
- Aktionen auf dem Display werden nach IPS gesendet
- Verschiedenste Konfigurationsmöglichkeiten zur visuellen Darstellung der Werte
- Unterstützt verschiedene Displayformate und Layouts.
- Übergabe der Aktion und Ausführung via Script
- Abfrage von (Status-)Informationen
- Ausführung von OpenHASP System- bzw. Globalen Kommandos
- Automatische Schaltung für Helligkeit und Einbrennschutz
- Verwaltung, Prüfung und Sicherung des Seitenlayouts
- Automatische Umwandlung von Seitenlayout in Objektliste
- Import und Export von Objektkonfigurationen
- Unterstützung der TileVisu via HTML-SDK

### 2. Voraussetzungen

* IP-Symcon ab Version 8.1

### 3. Installation

* Über den Modul Store das Modul _Raumdisplay_ installieren.
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

> Seitenaufbau …

Name                        | Beschreibung
--------------------------- | ----------------------------------
Datei (pages.jsonl)         | JSONL formatierter Inhalt zur Syncronisierung des Seitenaufbaus
HERUNTERLADEN¹              | Schalter um den Seitenaufbau vom Gerät herunterzuladen
HOCHRLADEN²                 | Schalter um den Seitenaufbau zum Gerät senden.
SICHERN¹                    | Schalter um den Seitenaufbau vom Gerät herunterzuladen und in eine Datei zu schreiben.
PRÜFEN²                     | Schalter um den Inhalt des Seitenaufbaus auf JSONL Konformität zu prüfen.
EINLESEN³                   | Startet den Prozess zum Einlesen und Umwandeln des Seitenaufbaus in eine Objektzuordnung.

¹) Bei dieser Aktion werden die Daten direkt vom Gerät (Display) heruntergeladen und weiterverarbeitet.  
²) Bei dieser Aktion werden die aktuell im Konfigurationsformular angezeigten Daten verwendet, ungeachtet ihres Speicherstatus.  
³) Bei dieser Aktion werden die aktuell gespeicherten Daten der Instanz verwendet (müssen nicht mit den aktuell angezeigten Daten übereinstimmen).  

> Objektzuordnung …

Name                        | Beschreibung
--------------------------- | ----------------------------------
Objekte                     | Table zur Zuordnung zwischen UI- und IPS-Objekt
AUSWAHL¹                    | Schalter um den Zustand der Auswahlboxen umzukehren, Basis ist die Checkbox des ersten Elementes
LÖSCHEN¹                    | Schalter um selektierte Zeile zu löschen
DUPLIZIEREN¹                | Schalter um selektierte Zeile zu duplizieren (und gleich einzusortieren)
PRÜFEN¹                     | Schalter um die hinterlegten Eingaben (Berechnungen) eines ausgewählten Eintrag zu prüfen.
ÜBERTRAGEN¹                 | Öffnet ein Dialog um die zuvor selektierten Zeilen in eine Datei zu exportieren, oder eine abgespeicherte Konfiguration aus einer Datei zu importieren.
NEUSORTIEREN²               | Schalter um die Liste endsprechend _Seite_ und _ID_ zu sortieren
ABGLEICHEN²                 | Schalter um die hinterlegten Objekte mit dem Inhalt des Seitenaufbaus gegen zu prüfen. den (Umkehr-)Folgezustand.

¹) Diese Aktionen verwenden die aktuell ausgewählten Daten aus der Objektliste.  
²) Diese Aktionen verwenden alle Daten aus der Objektliste, unabhängig von ihrem Auswahlstatus.  

__Hier eine kurze Erklärung der Spalten:__

* Spalte mit Checkboxen für die Auswahl der Objekte für ein Export (Schalter: Übertragen)
* _Typ_, _Seite_ und _ID_ - sollten selbsterklärend sein und identifizieren das UI-Object
* _Kommentar_ - auch klar, aber nicht ganz unwichtig um sich bei der Vielzahl von Mappings zurecht zu finden. Ich habe es extra eingeführt, weil ich zum Teil die Übersicht zu behalten. Empfind es es als sehr hilfreich.
* _Beschriftung_ - eigentlich alles was man bei der Visu an Text sieht. Also bei Buttons die Icons oder Titel, bei Labels die Beschriftung usw.
* _Wert_ - Zustände von Toggle Buttons, Sourcen von Images oder Hintergrundfarben von Objekten
* _Umrechnung_ - Transformationsweg von IPS zum DISPLAY, als Platzhalter kann {{val}} verwendet werden, was den Roh-Wert der Variablenänderung beinhaltet. Im Endeffekt ist das ein PHP eval() Ausdruck (ohne Klammern und Semikolon drum herum). Das Ergebnis davon wird dann bei Beschriftung oder/und Wert eingesetzt. Spezialwert -1 bedeutet keine weitere Auswertung vornehmen, also den Workflow stoppen.
* _ Rückrechnung_ - Transformationsweg von DISPLAY zu IPS. Das Gleiche wie bei Umrechnung nur Umgekehrt, d.h. eine -1 bewirkt keine Weiterverarbeitung in IPS.
* _Verknüpfung_ - die Verknüpfung zwischen Design-Objekt und IPS-Variable.

> Visualisierung …

Name                                   | Beschreibung
-------------------------------------- | ----------------------------------
Kachelhintergrundfarbe (online)        | Farbauswahl für den Zustand 'ONLINE'
Kachelhintergrundfarbe (offline)       | Farbauswahl für den Zustand 'OFFLINE'
Navigationsleiste anzeigen (Vor, Zurück, Weiter)?  | Schaltet die Anzeige der Navigationsbuttons an bzw. aus
Aktionsleiste anzeigen (Seiten löschen, Seiten neu laden, Synchronisieren, Neustart) | Schaltet die Anzeige der Aktionsbuttons an bzw. aus

> Widgets …

Name                                   | Beschreibung
-------------------------------------- | ----------------------------------
An / Aus (Analoguhr)                   | Aktualisierung der Uhrzeit an bzw-ausschalten
Seite (Analoguhr)                      | Seiten ID, auf welche sich die Uhr befindet
Stundenzeiger (Analoguhr)              | Gauge ID des Stundenzeigers
Minutenzeiger (Analoguhr)              | Gauge ID des Minutenzeigers
An / Aus  (Qlocktwo Earth)             | Aktualisierung der Uhrzeit an bzw-ausschalten
Seite (Qlocktwo Earth)                 | Seiten ID, auf welche sich die Uhr befindet
Startreihe (Qlocktwo Earth)            | Label ID der ersten durchnummerierten Textreihe
Hervorhebungsfarbe (Qlocktwo Earth)    | Farbe, welche zur hervorhebung der Uhrzeit genutzt werden soll
Präfix anzeigen (Qlocktwo Earth)       | Schalter, ob die Wörter "ES IST" immer hervorgehoben werden sollen
Suffix anzeigen (Qlocktwo Earth)       | Schalter, ob das Wort "UHR" immer hervorgehoben werden sollen (sonst nur wenn notwendig)
An / Aus (Fallblattuhr)                | Aktualisierung der Uhrzeit an bzw-ausschalten
Seite (Fallblattuhr)                   | Seiten ID, auf welche sich die Uhr befindet
Stundenfallblatt (Fallblattuhr)        | Start ID des Labels, welches die Stunden darstellt
Minutenfallblatt (Fallblattuhr)        | Start ID des Labels, welches die Minuten darstellt
An / Aus (HASP-Uhr)                    | Aktualisierung der Uhrzeit an bzw-ausschalten
Seite (HASP-Uhr)                       | Seiten ID, auf welche sich die Uhr befindet
Minutenkreis (HASP-Uhr)                | Objekt ID des Kreises(Arc), welches die Minuten darstellt

> Erweiterte Einstellungen …

Name                                   | Beschreibung
-------------------------------------- | ----------------------------------
Hintergrundbeleuchtung automatisch schalten! | Schaltet die Beleuchtung in Abhängigkeit der zeitlichen Nutzung
Normal (kein Leerlauf)                 | Wert der Helligkeit bei normaler Nutzung des Displays
Kurzer Leerlauf                        | Wert der Helligkeit nach kürzeren Nicht-Nutzung des Displays
Langer Leerlauf                        | Wert der Helligkeit nach längerer Nicht-Nutzung des Displays
Variable für den dunklen Modus         | Variable (bool), welche den Dunkelmodus steuert (WAHR = AUS| FALSCH = AN)
Normal (Dunkelmodus)                   | Wert der Helligkeit bei normaler Nutzung des Displays
Kurzer Leerlauf (Dunkelmodus)          | Wert der Helligkeit nach kürzeren Nicht-Nutzung des Displays
Langer Leerlauf (Dunkelmodus)          | Wert der Helligkeit nach längerer Nicht-Nutzung des Displays
Einbrennschutz automatisch aktivieren! | Schalter, ob der Einbrennschutz automatisch im definierten Zyklus gestartet werden soll!
Zyklus                                 | Zeitlicher Zyklus in Minuten in dem der Einbrennschutz für 30 Sekunden eingeschaltet wird.
Hintergrundbeleuchtung dimmen          | Schaltet die Beleuchtung während des Einbrennschutzes (30s) auf die eingestellte Beleuchtungsstärke, solange sie kleiner ist als der Wert für den langen Leerlauf und nicht Null ist!
Im Ruhezustand keine Syncronisierung!  | Schaltet die Synchrinistaion im Leerlauf ab (idle->long)
Im Ruhezustand auf eine bestimmte Seite wechseln!   | Schalter,  ob im langen Leerlauf eine bestimmte Seite angezeigt werden soll!
Feste Seite                            | Statische Seitenummer auf welche gewechselt werden soll im langen Leerlauf-Modus
Dynmische Seite                        | Variable, welche die Seitenummer enthält auf welche gewechselt werden soll im langen Leerlauf-Modus
Seitenverzweigung                      | Boolsche Variable, welche entscheidet auf welche Seite gewechselt werden soll im langen Leerlauf-Modus
Seite (Wahr)                           | Seite im Zustand wahr
Seite (Flasch)                         | Seite im Zustand falsch
Im Ruhezustand durch die Seiten blättern!   | Schalter, ob im langem Leerlauf die Seiten durchgeblättert werden sollen!
Intervall                              | Zeitspanne zwischen den einzelnen Seitenwechseln in Minuten (1-60)
Popup-Meldung schließen nach           | Standardwert in Sekunden nachdem eine MessageBox automatisch geschlossen wird.
Nachricht an Skript weiterleiten:      | Leitet die Aktion bzw. das Ereignis direkt weiter. Die Daten können im Script mit der Variable $_IPS['Data'] empfangen und ausgewertet werden.

Aktionsbereich:

> Aktion ausführen …

* _Seiten neu laden_ - liest die pages.jsonl neu ein und rendert die Seiten neu
* _Seiten löschen_ - alle Seiten löschen
* _Synchronisieren_ - gerade in der Einstellung- bzw. Entwicklungsphase ein ganz wichtiger Button, er geht durch die Mappingliste und ruft für die verknüpften Variablen deren Werte ab und stellt sie dar. Diese Funktion wird auch immer aufgerufen wenn ein Gerät ONLINE geht! Gerade wenn man alles Seiten gelöscht hat und neu lädt ist der Button sehr hilfreich!
* _Neustart_ - Reboot des Gerätes

> Steitennavigation ...

* _Vorher_, _Zurück_, _Weiter_ - eigentlich auch selbsterklärend, mit den Buttons kann man auf den Seiten navigieren

> Abfrage von …

* _Stimmungslicht_ - Anzeige der aktuellen Moodlight-Einstellungen
* _Status_ - Anzeige der Status-Update-Infos
* _Bildschirmfoto_ - Erstellen und Speichern eines aktuellen Screenshots (aktuelle Seite), siehe auch imgs Ordner im Modul

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
void WWXRD_DisableIdle(int $InstanzID, bool $disable);
```

Verhindert das automatische abschalten des Display (Hintergrundbeleuchtung).  
Wird _true_ übergeben geht das Display nicht mehr in den Leerlaufmodus; abschalten kann man es wieder mit _false_.  
Die Funktion liefert keinerlei Rückgabewert.

__Beispiel__: `WWXRD_DisableIdle(12345, true);`

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

__Beispiel__: `WWXRD_SendJSONL(12345, ['page' => 1,'id' => 99),'obj' => 'msgbox','text' => 'A message box with two buttons','options' => ['Open','Close']]);`

### 8. Versionshistorie

v4.0.20251220
* _NEU_: Projektumstrukturierung hin zu einer globalen CI/CD-Pipeline
* _NEU_: Kompatibilität auf IPS 8.1 hoch gesetzt
* _NEU_: Umstellung auf IPSModuleStrict
* _NEU_: Umschalten im Leerlauf unterstützt jetzt 3 Modi (Fix, Dynamisch und Verzweigung)
* _FIX_: DarkMode wird nach Antiburn wieder korrekt hergestellt
* _FIX_: SendJSONL behandelt Backslash's jetzt korrekt
* _FIX_: Bibliotheksfunktionen angeglichen

v3.8.20250515
* _NEU_: DarkMode für Leerlauf
* _FIX_: Register/Unregister von Objekten korrigiert

v3.7.20250318
* _NEU_: Konfigurationsformular überarbeitet (Aktionshinweise, Anordnung uvm.)
* _NEU_: Löschen von ausgewählten Objektmappings (Mehrfachauswahl)
* _NEU_: Duplizierung von ausgewählten Objektmappings (Mehrfachauswahl)
* _NEU_: Neue Prüfungsmethodik eingeführt (nur noch über Checkbox); Mehrfachauswahl listet fehlerbehaftete Mappings auf; Einfachauswahl zeigt einzelne Verarbeitungsergebnise an
* _NEU_: Neues Uhren-Widgets HASP
* _NEU_: Vorlagen für die Widgets können jetzt direkt angezeigt werden (Button) und dadurch einfaches Kopieren ermöglicht
* _FIX_: Fehler in Prüfung korrigiert
* _FIX_: Aufwachen beschleunigt (Hintergrundlicht vor Synchronisierung)
* _FIX_: Einige Übersetzungsfehler korrigiert

v3.6.20250310
* _NEU_: Unterstützung von Uhren-Widgets für Leerlaufzeiten
* _NEU_: Vorlagen für die Widgets Analoguhr, Qlocktwo Earth und Fallblattuhr (docs Verzeichnis)
* _FIX_: Status Profil - vertauschte Farben - korrigiert

v3.5.20250305
* _NEU_: Neuer Platzhalter {{fmt}} für profilformatierte Werte wird jetzt auch bei Wert und Beschriftung unterstützt
* _NEU_: Seiten(bereichs)angabe für automatisches Durchblättern der Seiten im Leerlauf
* _NEU_: Freie Definition einer Seite, welche beim Seitenwechsel im Leerlauf dargestellt/geschaltet werden soll
* _FIX_: Veraltete Konfigurationsangaben ersetzt

v3.4.20250303
* _NEU_: Auswahl der zu aktivierenden Seite im Leerlauf
* _NEU_: Automatisches durchblättern der Seiten im Leerlauf
* _FIX_: Bei Aktivierung der Löschfunktion beim Einlesen des Seitenlayouts wurden alle Zurodnungen gelöscht

v3.3.20250220
* _NEU_: Auswahl(umkehr) für Objektkonfiguration

v3.2.20250218
* _NEU_: Import und Export von Objektkonfigurationen zum Austausch zwischen verschiedenen Instanzen.

v3.1.20250214
* _NEU_: Definierte Objekte mit dem Inhalt des Seitenaufbaus abgleichen (Ausgabe: Liste der nicht definierten UI-Elemente).
* _NEU_: Einlesen und Umwandeln des Seitenaufbaus in die Objektzuordnung (Neue anlegen, Fehlerhafte korrigieren und nicht vorhandene löschen).
* _NEU_: Statusvariablen für Navigation und Aktionen (bessere Unterstützung von IPSView)
* _FIX_: Variablenupdates werden jetzt nur verarbeitet wenn eine wirkliche Änderung vorliegt (neuer Wert).
* _FIX_: Variablenupdates werden jetzt nur im Status Online verarbeitet.
* _FIX_: Übersetzungen in TileVisu korrigiert.
* _FIX_: Fehler in Debugausgabe korrigiert.

v3.0.20250205
* _NEU_: Unterstützung für TileVisu (Status, Navigation, Actions)
* _NEU_: Zeiteinstellung für automatisches Schliessen von Messageboxen
* _NEU_: Beispielseiten und -bilder erweitert
* _FIX_: Beim (manuellen) Syncronisationsdurchlauf werden Messageboxen unterdrückt.

v2.1.20250131
* _NEU_: Unterstützung für MESSAGEBOX (über Beschriftung => Text, über Wert => Buttons, über Rückrechnung => ScriptID für senden der Event-Werte)
* _FIX_: Objektzuordnungstabelle nutzt jetzt gesamte verfügbare Breite im Konfigurationsdialog

v2.0.20241129
* _NEU_: Rudimentäres Prüfen der Umrechnungen pro Verlinkung
* _NEU_: Verhalten von Spinner erweitert, Speed & Direction über (+/-) Wert und Beschriftung hinzugefügt
* _FIX_: Interner Umbau der Wertübername für Dorpdown, Gauge und Switch (Vereinheitlichung)

v1.9.20241122
* _NEU_: Unterstützung für SPINNER (über Beschriftung kann Drehrichtung, über Wert die Geschwindigkeit gesetzt werden)
* _NEU_: Schaltung der Hintergrundbeleutung während des Einbrennschutzes
* _NEU_: Prüfung der Objektverlinkung umgebaut bzw. eingeführt, Status bei Fehler wird auf 201 gesetzt
* _FIX_: Synchronisatzionslauf testet auf Existieren des verlinkten Objekts
* _FIX_: Synchronisatzionsfehler bei Skript-Verlinkungen behoben (kein -1 bei Umrechnung gesetzt)
* _FIX_: Fehler beim Duplizieren behoben

v1.8.20241110
* _NEU_: Neue Sektion zum verwalten des Seitenaufbaus (Layout)
* _FIX_: Beispielbilder teilweise umbennant/korriegiert
* _FIX_: Dokumentation vervollständigt

v1.7.20241106

* _NEU_: Buttons für das Sortieren der Objekt-Einträge nach _Seite_ und _ID_
* _NEU_: Buttons für das Duplizieren eines Mapping-Eintages
* _NEU_: Funktion _DisableIdle_ hinzugefügt (Hilfreich bei Konfigurations-Sessions)
* _FIX_: Automatisches Schalten auf Seite 1 erfolgt nun erst bei _langem_ Leerlauf
* _FIX_: Dokumentation/Markdown gefixt

v1.6.20241023

* _NEU_: Syncronisation deaktivierbar im Leerlauf
* _NEU_: Buttons im Aktionsbereich komplett neu organisiert
* _FIX_: Aktionen benötigen keine 2 Buttons mehr
* _FIX_: Namen und Übersetzungen überarbeitet

v1.5.20240906

* _NEU_: Helligkeitssteuerung (Erweiterte Einstellungen) komplett überarbeitet
* _NEU_: Automatische Abschaltung durch Intervallschaltung für Einbrennschutz ersetzt
* _FIX_: Kommentare und Debug-Meldungen vereinheitlicht und optimiert
* _FIX_: Dokumentation überarbeitet

v1.4.20240905

* _NEU_: UI-Objekt Checkbox wird jetzt unterstützt
* _FIX_: Textausgabe für Toogle Button korrigiert
* _FIX_: Konfigurationsbeispiele (Button) gefixt

v1.3.20240827

* _NEU_: Neue Beispielbilder und Konfigurationen hinzugefügt
* _NEU_: Neben dem Platzhalter {{val}} wird jetzt auch {{txt}} bei Rückrechnung unterstützt
* _FIX_: Unterstützung für ARC, BUTTON, LINEMETER und ROLLER hinzugefügt bzw. verbessert
* _FIX_: Fehler bei Umrechnung und Rückrechnung korrigiert
* _FIX_: Fehler bei Auswertung von booleschen Werten nochmal korrigiert
* _FIX_: Textausgabe mit Sonderzeichen jetzt bei allen Objekten möglich
* _FIX_: Bessere und mehr Debug-Meldungen

v1.2.20240728

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

* _firebuster_ : für die geniale und hervorragende Vorarbeit mit seinem __Modul openHASP__ 👍
* _ralf_, _Helmut_, _richimaint_: für den stetigen Austausch rund um das Display und Modulfunktionlitäten 👏
* _Norden_ : für seine sehr nette persönliche Unterstützung 👏

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

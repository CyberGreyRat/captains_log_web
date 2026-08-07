-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 07. Aug 2026 um 11:15
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `captainslog_db`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `iso14001_templates`
--

CREATE TABLE `iso14001_templates` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `phase` varchar(50) DEFAULT 'Produktion'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `iso14001_templates`
--

INSERT INTO `iso14001_templates` (`id`, `category`, `title`, `description`, `phase`) VALUES
(1, 'Leiterplatte', 'Homeoffice', 'Homeoffice', 'Entwurf'),
(2, 'Leiterplatte', 'Homeoffice', 'Homeoffice', 'Entwicklung'),
(3, 'Leiterplatte', 'Materialeinsatz-Auswahl nach \'CO2-Fußabdruck\'', 'Materialeinsatz-Auswahl nach \'CO2-Fußabdruck\'', 'Rohstoffe'),
(4, 'Leiterplatte', 'Neue Technologien bewerten (höhere Integration)', 'Neue Technologien bewerten (höhere Integration)', 'Produktion'),
(5, 'Leiterplatte', 'Anlieferung nicht in einzeln abgepackten Schachteln', 'Anlieferung nicht in einzeln abgepackten Schachteln', 'Lieferung'),
(6, 'Leiterplatte', 'Verbindungstechnologien bewerten (Einschub & Click statt Schrauben)', 'Verbindungstechnologien bewerten (Einschub & Click statt Schrauben)', 'Installation/Wartung'),
(7, 'Leiterplatte', 'geringer Strombedarf, Stromsparmodus', 'geringer Strombedarf, Stromsparmodus', 'Betrieb'),
(8, 'Leiterplatte', 'Auslegung auf lange Lebensdauer', 'Auslegung auf lange Lebensdauer', 'EOL'),
(9, 'Leiterplatte', 'Teams-Meeting', 'Teams-Meeting', 'Entwurf'),
(10, 'Leiterplatte', 'Teams-Meeting', 'Teams-Meeting', 'Entwicklung'),
(11, 'Leiterplatte', 'lokale Anbieter vorziehen', 'lokale Anbieter vorziehen', 'Rohstoffe'),
(12, 'Leiterplatte', 'Welche Maschine wofür?', 'Welche Maschine wofür?', 'Produktion'),
(13, 'Leiterplatte', 'Verpackung aus nachwachsenden Rohstoffen', 'Verpackung aus nachwachsenden Rohstoffen', 'Lieferung'),
(14, 'Leiterplatte', 'Wartungsfreundliche Gestaltung (Batteriewechsel durch Wartungszugang)', 'Wartungsfreundliche Gestaltung (Batteriewechsel durch Wartungszugang)', 'Installation/Wartung'),
(15, 'Leiterplatte', 'Standbymodus möglich, abschaltbar', 'Standbymodus möglich, abschaltbar', 'Betrieb'),
(16, 'Leiterplatte', 'Reparierbarkeit ermöglichen', 'Reparierbarkeit ermöglichen', 'EOL'),
(17, 'Leiterplatte', 'ÖPNV', 'ÖPNV', 'Entwurf'),
(18, 'Leiterplatte', 'ÖPNV', 'ÖPNV', 'Entwicklung'),
(19, 'Leiterplatte', 'Abmessungen so gering wie möglich', 'Abmessungen so gering wie möglich', 'Rohstoffe'),
(20, 'Leiterplatte', 'Material- und Prozess-Auswahl nach Maschinenverfügbarkeit', 'Material- und Prozess-Auswahl nach Maschinenverfügbarkeit', 'Produktion'),
(21, 'Leiterplatte', 'Routenoptimiert und mit hoher Auslastung', 'Routenoptimiert und mit hoher Auslastung', 'Installation/Wartung'),
(22, 'Leiterplatte', 'Nutze regenerative Energie (energy harvesting)', 'Nutze regenerative Energie (energy harvesting)', 'Betrieb'),
(23, 'Leiterplatte', 'einfacher Ausbau / Austausch ermöglichen', 'einfacher Ausbau / Austausch ermöglichen', 'EOL'),
(24, 'Leiterplatte', 'Moderne Rechner', 'Moderne Rechner', 'Entwurf'),
(25, 'Leiterplatte', 'Moderne Rechner', 'Moderne Rechner', 'Entwicklung'),
(26, 'Leiterplatte', 'Neue Technologien bewerten (höhere Integration)', 'Neue Technologien bewerten (höhere Integration)', 'Rohstoffe'),
(27, 'Leiterplatte', 'Fertigungs-Schritte reduzieren', 'Fertigungs-Schritte reduzieren', 'Produktion'),
(28, 'Leiterplatte', 'Abgestimmte Wartungsinvervalle (große zeitliche Abstände, schnelle Durchführung)', 'Abgestimmte Wartungsinvervalle (große zeitliche Abstände, schnelle Durchführung)', 'Installation/Wartung'),
(29, 'Leiterplatte', 'Reparierbarkeit ermöglichen', 'Reparierbarkeit ermöglichen', 'Betrieb'),
(30, 'Leiterplatte', 'Iterationen reduzieren durch Nutzung bekannter Quellen', 'Iterationen reduzieren durch Nutzung bekannter Quellen', 'Entwicklung'),
(31, 'Leiterplatte', 'Verwendung umweltfreundlicher Putz- und Reinigungsmittel', 'Verwendung umweltfreundlicher Putz- und Reinigungsmittel', 'Installation/Wartung'),
(32, 'Leiterplatte', 'Material-Wieder-Verwendung', 'Material-Wieder-Verwendung', 'Entwicklung'),
(33, 'Leiterplatte', 'Lagenaufbau minimieren', 'Lagenaufbau minimieren', 'Entwicklung'),
(34, 'Leiterplatte', 'Schutzlacke wenn möglich weglassen', 'Schutzlacke wenn möglich weglassen', 'Entwicklung'),
(35, 'Leiterplatte', 'Abmessungen so gering wie möglich', 'Abmessungen so gering wie möglich', 'Entwicklung'),
(36, 'Leiterplatte', 'keine unnützen Funktionsumfänge', 'keine unnützen Funktionsumfänge', 'Entwicklung'),
(37, 'Gehäuse', 'keine/wenig Muster, Skizzen auf Whiteboard statt auf Papier', 'keine/wenig Muster, Skizzen auf Whiteboard statt auf Papier', 'Entwurf'),
(38, 'Gehäuse', 'wie Entwurf + Muster als Massstabsgerechter 3D Druck', 'wie Entwurf + Muster als Massstabsgerechter 3D Druck', 'Entwicklung'),
(39, 'Gehäuse', 'wenig \'Sonderwuensche\' sondern Material von der Stange -> reduktion beim Hersteller', 'wenig \'Sonderwuensche\' sondern Material von der Stange -> reduktion beim Hersteller', 'Rohstoffe'),
(40, 'Gehäuse', 'Stangenware wenig Sonderwuensche -> vom Hersteller optimiert um Kosten zu senken', 'Stangenware wenig Sonderwuensche -> vom Hersteller optimiert um Kosten zu senken', 'Produktion'),
(41, 'Gehäuse', 'Packungsdichte optimieren, grosse Losgroessen', 'Packungsdichte optimieren, grosse Losgroessen', 'Lieferung'),
(42, 'Gehäuse', 'Hebezeuge/Vorrichtungen wiederverwenden', 'Hebezeuge/Vorrichtungen wiederverwenden', 'Installation/Wartung'),
(43, 'Gehäuse', 'einfache Demontage ohne schweres Geraet', 'einfache Demontage ohne schweres Geraet', 'EOL'),
(44, 'Gehäuse', 'Größe des Gehäuses,  welche funktionalität muss oder soll es haben, daraus erschließt sich das zu be...', 'Größe des Gehäuses,  welche funktionalität muss oder soll es haben, daraus erschließt sich das zu benutzende Material, neue Technologien bewerten', 'Rohstoffe'),
(45, 'Gehäuse', 'neue Technologien bewerten (höhere Integration); Welche Maschine wofür? Maschinen nutzen die geringe...', 'neue Technologien bewerten (höhere Integration); Welche Maschine wofür? Maschinen nutzen die geringe Energiekosten haben, Materialnutzen das es als Massenware gibt', 'Produktion'),
(46, 'Gehäuse', 'Kurze Transport wege, nicht einzeln Liefern, Pendelverpackungen nutzen', 'Kurze Transport wege, nicht einzeln Liefern, Pendelverpackungen nutzen', 'Lieferung'),
(47, 'Gehäuse', 'Kurze Service Zeiten, schelle austauschbare Komponenten', 'Kurze Service Zeiten, schelle austauschbare Komponenten', 'Installation/Wartung'),
(48, 'Gehäuse', 'Gehäuse so groß wie nötig,', 'Gehäuse so groß wie nötig,', 'Betrieb'),
(49, 'Gehäuse', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner;', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner;', 'Entwurf'),
(50, 'Gehäuse', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; Iterationen reduzieren durch Nutzung bekannter Qu...', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; Iterationen reduzieren durch Nutzung bekannter Quellen; Material-Verwendung, Abmessungen;', 'Entwicklung'),
(51, 'Gehäuse', 'wenn möglich, Standardgehäuse verwenden, wenig bearbeiten; stückzahlabhängig Spritzguß, Blech', 'wenn möglich, Standardgehäuse verwenden, wenig bearbeiten; stückzahlabhängig Spritzguß, Blech', 'Rohstoffe'),
(52, 'Gehäuse', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; Iterationen reduzieren durch Nutzung bekannter Qu...', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; Iterationen reduzieren durch Nutzung bekannter Quellen; Material-Verwendung; Beschichtung, Abmessungen, Standardgehäuse; übliche Materialen und Befestigungsverfahren', 'Entwicklung'),
(53, 'Gehäuse', 'optimale Stückzahlen ordern', 'optimale Stückzahlen ordern', 'Rohstoffe'),
(54, 'Gehäuse', 'optimale Stückzahlen ordern; wenig Lagerbestand', 'optimale Stückzahlen ordern; wenig Lagerbestand', 'Produktion'),
(55, 'Gehäuse', 'Anlieferung nicht in einzeln abgepackten Schachteln; Gehäuse so stabil, dass nur Einschlagen mit Pap...', 'Anlieferung nicht in einzeln abgepackten Schachteln; Gehäuse so stabil, dass nur Einschlagen mit Papier notwendig ist', 'Lieferung'),
(56, 'Gehäuse', 'grundsätzlich überlegen und nachweisen, ob der Nutzen des Produktes den Material- / Energie-Verbrauc...', 'grundsätzlich überlegen und nachweisen, ob der Nutzen des Produktes den Material- / Energie-Verbrauch, Emissionen, Abfallerzeugung übersteigt und dann über die entscheiden, ob es überhaupt sinnvoll ist, es zu entwickeln und zu produzieren;  - Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; moderne Konstruktionswerkzeuge (Software), Nutzung von 3D-Modellen der Hersteller', 'Entwurf'),
(57, 'Gehäuse', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; Iterationen reduzieren durch Nutzung bekannter Qu...', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; Iterationen reduzieren durch Nutzung bekannter Quellen; Material-Verwendung, Abmessungen; Verwendung von Standardmaterialien (Gehäuse und Zubehör)', 'Entwicklung'),
(58, 'Gehäuse', 'wenig oder nicht bearbeitete Standardgehäuse verwenden; dem Einastzfall entsprechend auswählen', 'wenig oder nicht bearbeitete Standardgehäuse verwenden; dem Einastzfall entsprechend auswählen', 'Rohstoffe'),
(59, 'Gehäuse', 'regionale Fertiger bevorzugen um lange Transportwege zu vermeiden', 'regionale Fertiger bevorzugen um lange Transportwege zu vermeiden', 'Produktion'),
(60, 'Gehäuse', 'keine Verwendung von überdimensionierten Verpackungen', 'keine Verwendung von überdimensionierten Verpackungen', 'Lieferung'),
(61, 'Gehäuse', 'Verwendung moderner Technologien', 'Verwendung moderner Technologien', 'Installation/Wartung'),
(62, 'Gehäuse', 'bestimmungsgemäßer Gebrauch', 'bestimmungsgemäßer Gebrauch', 'Betrieb'),
(63, 'Gehäuse', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; Mustermengen minimieren', 'Homeoffice; Teams Meetings; ÖPNV; moderne Rechner; Mustermengen minimieren', 'Entwurf'),
(64, 'Gehäuse', '3D Druck statt aufwendige Musterfertigung in Metall oder Kunststoff', '3D Druck statt aufwendige Musterfertigung in Metall oder Kunststoff', 'Entwicklung'),
(65, 'Gehäuse', 'Material aus umweltfreundlichen Rohstoffen verwenden (ROHS, REACH konform)', 'Material aus umweltfreundlichen Rohstoffen verwenden (ROHS, REACH konform)', 'Rohstoffe'),
(66, 'Gehäuse', 'effiziente Fertigung auf neuen Maschinen', 'effiziente Fertigung auf neuen Maschinen', 'Produktion'),
(67, 'Gehäuse', 'Anlieferung nicht in einzeln abgepackten schachteln', 'Anlieferung nicht in einzeln abgepackten schachteln', 'Lieferung'),
(68, 'Gehäuse', 'möglichst einheitliche Befestigungsmaterialien', 'möglichst einheitliche Befestigungsmaterialien', 'Installation/Wartung'),
(69, 'Gehäuse', 'Kundenhinweise für die Verwendung mitgeben (als pdf und NICHT ausdrucken!)', 'Kundenhinweise für die Verwendung mitgeben (als pdf und NICHT ausdrucken!)', 'Betrieb'),
(70, 'Gehäuse', 'Rapidprototyping verwenden (3D-Druck), Simulation über 3D-Modellierung, Normteile verwenden', 'Rapidprototyping verwenden (3D-Druck), Simulation über 3D-Modellierung, Normteile verwenden', 'Entwicklung'),
(71, 'Gehäuse', 'Größe und Material des Gehäuses, minimierung weiterer Bearbeitungen (Nachfräsen und Anpassungen)', 'Größe und Material des Gehäuses, minimierung weiterer Bearbeitungen (Nachfräsen und Anpassungen)', 'Rohstoffe'),
(72, 'Gehäuse', 'Neue Technologien bewerten (höhere Integration); Welche Maschine wofür?', 'Neue Technologien bewerten (höhere Integration); Welche Maschine wofür?', 'Produktion'),
(73, 'Gehäuse', 'für die Stückzahlen optimierte Verpackung, keine Einzelverpackung', 'für die Stückzahlen optimierte Verpackung, keine Einzelverpackung', 'Lieferung'),
(74, 'Gehäuse', 'Befestigungen über Normteile', 'Befestigungen über Normteile', 'Installation/Wartung'),
(75, 'Gehäuse', 'Wie bei Leiterplatte; geeignete Programme; gängige Dateitypen verwenden;', 'Wie bei Leiterplatte; geeignete Programme; gängige Dateitypen verwenden;', 'Entwurf'),
(76, 'Gehäuse', 'Wie bei Leiterplatte; Recyclebare Materialien nutzen; gängige Dateitypen verwenden (konvertierungen ...', 'Wie bei Leiterplatte; Recyclebare Materialien nutzen; gängige Dateitypen verwenden (konvertierungen vermeiden); Konstruktionen mit wenig Materialeintrag konstruieren;', 'Entwicklung'),
(77, 'Gehäuse', 'Wie bei Leiterplatte', 'Wie bei Leiterplatte', 'Rohstoffe'),
(78, 'Software', 'Green Software Design & Algorithmen', 'Auswahl ressourcenschonender Algorithmen und Datenstrukturen zur Minimierung von Rechenzeit und Speicherbedarf.', 'Entwurf'),
(79, 'Software', 'Minimale Hardware-Anforderungen definieren', 'Software so schlank konzipieren, dass sie auch auf älterer Hardware flüssig läuft (verhindert vorzeitigen Elektroschrott).', 'Entwurf'),
(80, 'Software', 'Hardwarenahe/Effiziente Sprachen nutzen', 'Wo performancerelevant, ressourcenschonende Sprachen (z.B. C/C++ oder Rust) statt ressourcenhungriger Frameworks einsetzen.', 'Entwicklung'),
(81, 'Software', 'CI/CD Pipelines optimieren', 'Automatisierte Tests und Builds so konfigurieren, dass Server-Laufzeiten und Energieverbrauch im Rechenzentrum minimiert werden.', 'Entwicklung'),
(82, 'Software', 'Grünes Hosting & Cloud-Infrastruktur', 'Auswahl von Rechenzentren und Server-Anbietern, die nachweislich mit 100% erneuerbaren Energien arbeiten (Green IT).', 'Rohstoffe'),
(83, 'Software', 'Build-Caching & Redundanzen vermeiden', 'Nutzung von inkrementellen Builds und Caching, damit nicht bei jedem Commit das gesamte Projekt neu kompiliert wird.', 'Produktion'),
(84, 'Software', 'Digitale Auslieferung (Downloads / OTA)', 'Vollständiger Verzicht auf physische Datenträger (CDs, USB-Sticks) und unnötige Papierhandbücher.', 'Lieferung'),
(85, 'Software', 'Delta-Updates implementieren', 'Bei Updates nur die geänderten Code-Blöcke übertragen (Delta) statt des gesamten Images, um Netzwerklast zu minimieren.', 'Lieferung'),
(86, 'Software', 'Remote-Wartung & Ferndiagnose', 'Telemetriedaten und Remote-Zugriffe implementieren, um physische Service-Fahrten von Technikern zu vermeiden.', 'Installation/Wartung'),
(87, 'Software', 'CPU Wake-ups & Polling minimieren', 'Übergang von permanentem \"Polling\" (Abfragen) zu Event-Driven-Architecture, damit Microcontroller länger im Deep-Sleep bleiben können.', 'Betrieb'),
(88, 'Software', 'Netzwerk-Traffic reduzieren', 'Daten komprimieren (z.B. Binärprotokolle statt schwerem XML/JSON) und in Batches (gesammelt) senden, um Sendezeit zu verkürzen.', 'Betrieb'),
(89, 'Software', 'Dark Mode für UIs', 'Bietet die UI einen Dark Mode, wird bei OLED-Displays signifikant Strom eingespart.', 'Betrieb'),
(90, 'Software', 'Schonende Datenlöschung (SSDs)', 'Datenbereinigung über TRIM-Befehle statt durch mehrfaches Überschreiben, um die Lebensdauer der Flash-Speicher nicht künstlich zu verkürzen.', 'EOL'),
(91, 'Software', 'Open-Source Freigabe am Lebensende', 'Wenn das Produkt End-of-Life erreicht, Code/APIs freigeben, damit die Community die Hardware mit Custom-Firmware weiter nutzen kann.', 'EOL'),
(92, 'Software', 'ESP32 Deep-Sleep & ULP Coprozessor', 'Konsequente Nutzung der Deep-Sleep-Modi. Auslagerung von einfachen Sensorabfragen an den Ultra-Low-Power (ULP) Coprozessor, um den Hauptkern (Xtensa) schlafen zu lassen und den Stromverbrauch in den Mikroampere-Bereich zu drücken.', 'Betrieb'),
(93, 'Software', 'Event-Driven Wakeups', 'Verzicht auf zyklisches Aufwachen (Timer-Polling). Der ESP32 wird stattdessen ausschließlich über externe Interrupts (z.B. Taster, Sensor-Thresholds) geweckt, um maximale Akkulaufzeiten bei autarken Systemen zu erreichen.', 'Entwurf'),
(94, 'Software', 'ESP32 OTA-Updates (Over-The-Air)', 'Firmware-Updates zwingend über WLAN/Bluetooth (OTA) realisieren. Vermeidet Techniker-Einsätze vor Ort oder gar den physischen Rückversand von Geräten bei Softwarefehlern.', 'Installation/Wartung'),
(95, 'Leiterplatte', 'E-Paper / E-Ink Displays bevorzugen', 'Einsatz bistabiler Displays (E-Paper) anstelle von LCDs/OLEDs. Das Display verbraucht ausschließlich beim Bildwechsel Energie (Zero-Power im statischen Zustand), was die Energieeffizienz enorm steigert.', 'Entwurf'),
(96, 'Leiterplatte', 'Physisches Power-Gating (MOSFETs)', 'Ressourcenhungrige Peripherie (wie GPS-Module, Transceiver oder SD-Kartenleser) über MOSFETs physisch komplett von der Stromversorgung trennen, während der ESP32 im Deep-Sleep ist (Vermeidung von Leckströmen).', 'Entwicklung'),
(97, 'Leiterplatte', 'Energy Harvesting (Solar) integrieren', 'Implementierung von kleinen Solar-Ladeschaltungen (z.B. für kleine Pumpen, Sensor-Nodes oder smarte Displays), um den Batteriewechsel-Zyklus massiv zu verlängern oder Akkus komplett durch Superkondensatoren zu ersetzen.', 'Entwurf');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `iso14001_templates`
--
ALTER TABLE `iso14001_templates`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `iso14001_templates`
--
ALTER TABLE `iso14001_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

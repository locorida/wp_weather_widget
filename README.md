# wp_weather_widget
Wetter Widget für Wordpress

# 🌦️ Wettervorhersage Widget (WordPress Plugin)

Ein erweitertes Wettervorhersage-Widget für WordPress, das aktuelle Wetterdaten sowie Vorhersagen für mehrere Tage anzeigt – inklusive grafischer Icons, DWD-Wetterwarnungen und detaillierter Stundenvorschau. Es nutzt die [OpenWeatherMap API](https://openweathermap.org/) sowie optional die DWD-Warn-API.

## 🧩 Features

- Anzeige aktueller Wetterdaten (Temperatur, Wind, Sonnenauf-/untergang, Regen/Schnee)
- Mehrtägige Wettervorhersage mit Accordions
- Stundenweise Vorschau für den aktuellen Tag
- Integration von Wetterwarnungen des Deutschen Wetterdienstes (DWD)
- Verwendung von [Weather Icons](https://github.com/erikflowers/weather-icons)
- Vollständig konfigurierbar über das WordPress-Backend

## 🛠️ Installation

1. Lade das Plugin-Verzeichnis `wettervorhersage-widget` in dein WordPress-Plugin-Verzeichnis `/wp-content/plugins/`
2. Aktiviere das Plugin im WordPress-Backend unter `Plugins`
3. Füge das Widget über `Design > Widgets` zu deiner Seitenleiste oder einem anderen Widget-Bereich hinzu

## 🔧 Konfiguration

Im Widget selbst kannst du folgende Optionen festlegen:

| Feld           | Beschreibung                                       |
|----------------|----------------------------------------------------|
| **Titel**      | Überschrift für das Widget                         |
| **Stadt**      | Stadtname für die Wetterabfrage (z. B. `Berlin`)   |
| **API Key**    | Dein persönlicher OpenWeatherMap API-Schlüssel     |
| **DWD Region ID** | Regionalschlüssel für Wetterwarnungen (optional) |

## 📦 Voraussetzungen

- PHP 7.4 oder höher
- WordPress 6.1 oder höher
- Ein kostenloser API-Key von [OpenWeatherMap](https://openweathermap.org/appid)

## 🌐 APIs genutzt

- **OpenWeatherMap API** – aktuelle Wetterdaten und Vorhersage
- **DWD Warn-API** – aktuelle Wetterwarnungen für deutsche Regionen (JSON-Feed)

## 📷 Screenshots

![{F7B2F77A-4AD0-4CA4-975F-2B27841821A8}](https://github.com/user-attachments/assets/434f3ca4-8992-4357-b99b-a5f4488c5de6)
![{313F6D85-0F8D-4557-8D4F-9D6AE60BF648}](https://github.com/user-attachments/assets/18aa9f08-dc4e-49f9-85ff-7d4cb9905bdf)


## 🚀 Entwicklung

Der Haupt-Plugin-Code befindet sich in der Datei `wettervorhersage-widget.php`.

### Beispielhafter Funktionsumfang:

- `fetch_weather()` ruft aktuelle Wetterdaten und Vorhersagen ab
- `fetch_dwd_warnings()` lädt aktuelle Warnmeldungen vom DWD
- Wetterdaten werden optisch mit `Weather Icons` gerendert
- Forecasts und Warnungen werden in Accordions dargestellt (mit Bootstrap-Kompatibilität)

## 🧪 ToDo / Ideen

- Caching der Wetterdaten zur Performance-Verbesserung
- Unterstützung für weitere Wetteranbieter
- Backend-Seite zur API-Key-Verwaltung
- Mehrsprachige Unterstützung über `text_domain`

## 👤 Autor

**Matthias Max**  
[GitHub Profil](https://github.com/locorida)

## 📄 Lizenz

Dieses Plugin steht unter der [MIT Lizenz](LICENSE).

---

### ⚠️ Hinweis

Dieses Plugin ist inoffiziell und steht in keiner Verbindung zu OpenWeatherMap oder dem Deutschen Wetterdienst.

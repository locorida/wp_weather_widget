<?php
/*
    Plugin Name: Wettervorhersage Widget
    Description: Ein erweitertes Wettervorhersage-Widget mit der OpenWeatherMap API, das auch Vorhersagen für mehrere Tage und grafische Icons anzeigt.
    Version: 1.7
    Author: Matthias Max
    Author URI: https://github.com/locorida
*/

class FL_WeatherWidget extends WP_Widget
{
    // Vereinfachtes Mapping mit nur den Basis-Codes
    private const WEATHER_ICON_BASE_MAP = [
        '01' => 'wi-day-sunny',
        '02' => 'wi-cloudy',
        '03' => 'wi-cloud',
        '04' => 'wi-cloudy',
        '09' => 'wi-showers',
        '10' => 'wi-rain',
        '11' => 'wi-thunderstorm',
        '13' => 'wi-snow',
        '50' => 'wi-fog',
    ];

    public function __construct()
    {
        parent::__construct(
            'fl_weather_widget',
            __('Wettervorhersage Widget', 'text_domain'),
            ['description' => __('Zeigt die Wettervorhersage an, einschließlich Vorhersagen für mehrere Tage.', 'text_domain')]
        );
        add_action('wp_enqueue_scripts', [$this, 'load_widget_scripts']);
    }

    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);
        $city = $instance['city'];
        $api_key = $instance['api_key'];
        $region_id = $instance['region_id'];

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        if (!empty($city) && !empty($api_key)) {
            $weather_data = $this->fetch_weather($city, $api_key);
            $dwd_warnings = $this->fetch_dwd_warnings($region_id);

            if ($weather_data) {
                echo '<div style="text-align: center;">';

                // Hauptaccordion für alle Wetterinformationen
                echo '<div class="accordion mt-3" id="weatherAccordion">';

                // Aktuelles Wetter als ersten Accordion-Eintrag
                echo '<div class="accordion-item">';
                echo '<h2 class="accordion-header" id="heading-current">';
                echo '<button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapse-current" aria-expanded="true" aria-controls="collapse-current" data-bs-parent="#weatherAccordion">';
                echo '<div class="d-flex align-items-center w-100">';
                echo '<div class="me-3">';
                echo '<span class="fw-bold">Aktuell</span>';
                echo '</div>';
                echo '<i class="wi ' . esc_attr($this->get_weather_icon_class($weather_data['current']['icon'])) . ' me-3" style="font-size: 1.2em;"></i>';
                echo '<span class="fw-bold">' . esc_html($weather_data['current']['temp']) . '°C</span>';
                echo '</div>';
                echo '</button>';
                echo '</h2>';

                echo '<div id="collapse-current" class="accordion-collapse collapse show" aria-labelledby="heading-current">';
                echo '<div class="accordion-body">';
                echo '<p class="mb-2"><b>' . esc_html($weather_data['current']['description']) . '</b></p>';

                // Wind
                echo '<div class="mb-1">';
                echo '<i class="wi ' . $this->get_beaufort_icon_class($weather_data['current']['wind_speed'] * 3.6) . ' me-1"></i> ';
                echo round($weather_data['current']['wind_speed'] * 3.6, 1) . ' km/h';
                echo ' <i title="Wind von ' . $weather_data['current']['wind_direction'] . '" class="wi ' . esc_attr($this->get_cardinal_wind_icon_class($weather_data['current']['wind_direction'])) . '"></i>';
                echo '</div>';

                // Sonnenauf- und -untergang
                echo '<div class="mb-1">';
                echo '<i class="wi wi-sunrise me-1"></i> ' . esc_html($weather_data['current']['sunrise']);
                echo ' <i class="wi wi-sunset ms-3 me-1"></i> ' . esc_html($weather_data['current']['sunset']);
                echo '</div>';

                // Bewölkung, Luftdruck und Luftfeuchtigkeit
                echo '<div class="mb-1">';
                echo '<i class="wi wi-cloudy me-1"></i> ' . esc_html($weather_data['current']['clouds']) . '% ';
                echo '<i class="wi wi-barometer ms-3 me-1"></i> ' . esc_html($weather_data['current']['pressure']) . ' hPa';
                echo '</div><div class="mb-1">';
                echo '<i class="wi wi-humidity me-1"></i> ' . esc_html($weather_data['current']['humidity']) . '%';
                echo '</div>';

                // Niederschlag
                if ($weather_data['current']['rainfall'] > 0 || $weather_data['current']['snowfall'] > 0) {
                    if ($weather_data['current']['rainfall'] > 0) {
                        echo '<div class="mb-1">';
                        echo '<i class="wi wi-raindrop me-1"></i> ' . esc_html($weather_data['current']['rainfall']) . ' mm';
                        echo '</div>';
                    }
                    if ($weather_data['current']['snowfall'] > 0) {
                        echo '<div class="mb-1">';
                        echo '<i class="wi wi-snowflake-cold me-1"></i> ' . esc_html($weather_data['current']['snowfall']) . ' mm';
                        echo '</div>';
                    }
                }

                echo '</div></div></div>';

                // Stündliche Vorhersagen für heute
                if (!empty($weather_data['hourly'])) {
                    foreach ($weather_data['hourly'] as $hIdx => $hour) {
                        $hourId = 'hour-' . $hIdx;
                        $isOpen = $hIdx === 0 ? ' show' : '';

                        echo '<div class="accordion-item">';
                        echo '<h2 class="accordion-header" id="heading-' . $hourId . '">';
                        echo '<button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#collapse-' . $hourId . '" aria-expanded="false" aria-controls="collapse-' . $hourId . '" data-bs-parent="#weatherAccordion">';

                        // Header mit Zeit und Icon
                        echo '<div class="d-flex align-items-center w-100">';
                        echo '<div class="me-3 text-nowrap">';
                        echo '<span class="fw-bold">' . date('d.m. ', strtotime($hour['dt_txt'])) . esc_html($hour['time']) . '</span>';
                        echo '</div>';
                        echo '<i class="wi ' . esc_attr($this->get_weather_icon_class($hour['icon'])) . ' me-3" style="font-size: 1.2em;"></i>';
                        echo '<span class="fw-bold text-nowrap">' . esc_html($hour['temp']) . '°C</span>';
                        echo '</div>';

                        echo '</button>';
                        echo '</h2>';

                        // Collapsible Body
                        echo '<div id="collapse-' . $hourId . '" class="accordion-collapse collapse" aria-labelledby="heading-' . $hourId . '">';
                        echo '<div class="accordion-body py-2">';

                        // Wetterbeschreibung
                        echo '<p class="mb-2"><b>' . esc_html($hour['description']) . '</b></p>';

                        // Niederschlagswahrscheinlichkeit
                        echo '<div class="mb-1 text-nowrap">';
                        echo '<i class="wi wi-raindrop me-1"></i> ';
                        echo round($hour['pop'] * 100) . '%';
                        if (isset($hour['rain']['3h'])) {
                            echo ' (' . number_format($hour['rain']['3h'], 1) . ' mm/3h)';
                        }
                        echo '</div>';

                        // Bewölkung, Luftdruck und Luftfeuchtigkeit
                        echo '<div class="mb-1">';
                        echo '<i class="wi wi-cloudy me-1"></i> ' . esc_html($hour['clouds']) . '% ';
                        echo '<i class="wi wi-barometer ms-3 me-1"></i> ' . esc_html($hour['pressure']) . ' hPa';
                        echo '</div><div class="mb-1">';
                        echo '<i class="wi wi-humidity me-1"></i> ' . esc_html($hour['humidity']) . '%';
                        echo '</div>';

                        // Windgeschwindigkeit
                        echo '<div>';
                        echo '<i class="wi ' . $this->get_beaufort_icon_class($hour['wind']['speed'] * 3.6) . ' me-1"></i> ';
                        echo round($hour['wind']['speed'] * 3.6, 1) . ' km/h';
                        echo '</div>';

                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                }

                // Wetterwarnungen
                if (!empty($dwd_warnings)) {
                    echo '<div class="accordion-item">';
                    echo '<h2 class="accordion-header" id="heading-warnings">';
                    echo '<button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#collapse-warnings" aria-expanded="false" aria-controls="collapse-warnings" data-bs-parent="#weatherAccordion">';
                    echo '<span class="me-2 fw-bold text-danger">' . __('Aktuelle Wetterwarnungen', 'text_domain') . '</span>';
                    echo '</button>';
                    echo '</h2>';
                    echo '<div id="collapse-warnings" class="accordion-collapse collapse" aria-labelledby="heading-warnings" data-bs-parent="#weatherAccordion">';
                    echo '<div class="accordion-body">';

                    foreach ($dwd_warnings as $warning) {
                        echo '<div class="alert alert-warning mb-3">';
                        echo '<h5 class="alert-heading">' . esc_html($warning['title']) . '</h5>';
                        echo '<p class="mb-1">' . esc_html($warning['description']) . '</p>';
                        echo '<small class="text-muted">' . esc_html(date('d.m.Y H:i', (substr($warning['start'], 0, -3))))
                            . ' bis ' . esc_html(date('d.m.Y H:i', (substr($warning['end'], 0, -3)))) . '</small>';
                        echo '</div>';
                    }

                    echo '</div></div></div>';
                }

                // Tagesvorhersage
                foreach ($weather_data['forecast'] as $dayIndex => $day) {
                    $dateId = 'day-' . $dayIndex;
                    echo '<div class="accordion-item">';
                    echo '<h2 class="accordion-header" id="heading-' . $dateId . '">';
                    echo '<button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#collapse-' . $dateId . '" aria-expanded="false" aria-controls="collapse-' . $dateId . '" data-bs-parent="#weatherAccordion">';
                    echo '<div class="d-flex align-items-center w-100">';
                    echo '<div class="me-3 text-nowrap">';
                    echo '<span class="fw-bold">' . esc_html($day['day']) . '</span>';
                    echo '</div>';
                    echo '<i class="wi ' . esc_attr($this->get_weather_icon_class($day['icon'])) . ' me-3"></i>';
                    echo '<span class="fw-bold text-nowrap">' . esc_html($day['temp_max']) . '° / ' . esc_html($day['temp_min']) . '°</span>';
                    echo '</div>';
                    echo '</button>';
                    echo '</h2>';

                    echo '<div id="collapse-' . $dateId . '" class="accordion-collapse collapse" aria-labelledby="heading-' . $dateId . '">';
                    echo '<div class="accordion-body">';
                    echo '<p class="mb-1"><b>' . esc_html($day['description']) . '</b></p>';

                    // Niederschlagswahrscheinlichkeit und Menge
                    echo '<div class="mb-1 text-nowrap">';
                    echo '<i class="wi wi-raindrop me-1"></i> ' . round($day['pop'] * 100) . '%';
                    if (isset($day['rain']['3h'])) {
                        echo ' (' . number_format($day['rain']['3h'], 1) . ' mm/3h)';
                    }
                    echo '</div>';

                    // Bewölkung, Luftdruck und Luftfeuchtigkeit
                    echo '<div class="mb-1">';
                    echo '<i class="wi wi-cloudy me-1"></i> ' . esc_html($day['clouds']) . '% ';
                    echo '<i class="wi wi-barometer ms-3 me-1"></i> ' . esc_html($day['pressure']) . ' hPa';
                    echo '</div><div class="mb-1">';
                    echo '<i class="wi wi-humidity me-1"></i> ' . esc_html($day['humidity']) . '%';
                    echo '</div>';

                    // Wind
                    echo '<div class="mb-1 text-nowrap">';
                    echo '<i class="wi ' . $this->get_beaufort_icon_class($day['wind']['speed'] * 3.6) . '"></i> ';
                    echo round($day['wind']['speed'] * 3.6, 1) . ' km/h';
                    echo '</div>';
                    echo '</div></div></div>';
                }

                echo '</div>';
                echo '</div>';
            } else {
                echo '<p style="text-align: center;">' . __('Wetterdaten konnten nicht abgerufen werden.', 'text_domain') . '</p>';
            }
        } else {
            echo '<p style="text-align: center;">' . __('Bitte konfigurieren Sie das Widget.', 'text_domain') . '</p>';
        }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Wettervorhersage', 'text_domain');
        $city = !empty($instance['city']) ? $instance['city'] : '';
        $api_key = !empty($instance['api_key']) ? $instance['api_key'] : '';
        $region_id = !empty($instance['region_id']) ? $instance['region_id'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Titel:', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('city')); ?>"><?php _e('Stadt:', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('city')); ?>" name="<?php echo esc_attr($this->get_field_name('city')); ?>" type="text" value="<?php echo esc_attr($city); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('api_key')); ?>"><?php _e('API Key:', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('api_key')); ?>" name="<?php echo esc_attr($this->get_field_name('api_key')); ?>" type="text" value="<?php echo esc_attr($api_key); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('region_id')); ?>"><?php _e('DWD Region ID:', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('region_id')); ?>" name="<?php echo esc_attr($this->get_field_name('region_id')); ?>" type="text" value="<?php echo esc_attr($region_id); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance): array
    {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['city'] = (!empty($new_instance['city'])) ? strip_tags($new_instance['city']) : '';
        $instance['api_key'] = (!empty($new_instance['api_key'])) ? strip_tags($new_instance['api_key']) : '';
        $instance['region_id'] = (!empty($new_instance['region_id'])) ? strip_tags($new_instance['region_id']) : '';

        return $instance;
    }

    private function fetch_weather($city, $api_key): bool|array
    {
        $url_current = sprintf('https://api.openweathermap.org/data/2.5/weather?q=%s&appid=%s&units=metric&lang=de', urlencode($city), $api_key);
        $url_forecast = sprintf('https://api.openweathermap.org/data/2.5/forecast?q=%s&appid=%s&units=metric&lang=de', urlencode($city), $api_key);

        $current_response = wp_remote_get($url_current);
        $forecast_response = wp_remote_get($url_forecast);

        if (is_wp_error($current_response) || is_wp_error($forecast_response)) {
            return false;
        }

        $current_data = json_decode(wp_remote_retrieve_body($current_response), true);
        $forecast_data = json_decode(wp_remote_retrieve_body($forecast_response), true);



        if (!isset($current_data['main']['temp']) || !isset($current_data['weather'][0]['icon'])) {
            return false;
        }

        $weather = [
            'current' => [
                'temp' => $this->sanitize_temperature(round($current_data['main']['temp'])),
                'temp_max' => $this->sanitize_temperature(round($current_data['main']['temp_max'])),
                'temp_min' => $this->sanitize_temperature(round($current_data['main']['temp_min'])),
                'feels_like' => $this->sanitize_temperature(round($current_data['main']['feels_like'])),
                'wind_speed' => $current_data['wind']['speed'],
                'wind_direction' => $this->get_wind_direction($current_data['wind']['deg']),
                'sunrise' => date('H:i', $current_data['sys']['sunrise']),
                'sunset' => date('H:i', $current_data['sys']['sunset']),
                'description' => $current_data['weather'][0]['description'],
                'icon' => $current_data['weather'][0]['icon'],
                'snowfall' => $current_data['snow']['1h'] ?? 0,
                'rainfall' => $current_data['rain']['1h'] ?? 0,
                'clouds' => $current_data['clouds']['all'],
                'pressure' => $current_data['main']['pressure'],
                'humidity' => $current_data['main']['humidity'],
            ],
            'forecast' => [],
            'hourly' => [],

        ];

        if (isset($forecast_data['list'])) {
            $weather['hourly'] = [];
            $today = date('Y-m-d');

            // Stündliche Vorhersagen für heute
            foreach ($forecast_data['list'] as $entry) {
                $entry_date = date('Y-m-d', strtotime($entry['dt_txt']));

                // Nur Einträge für heute sammeln
                if ($entry_date === $today) {
                    $weather['hourly'][] = [
                        'time' => date('H:i', strtotime($entry['dt_txt'])),
                        'dt_txt' => $entry['dt_txt'],
                        'temp' => round($entry['main']['temp']),
                        'description' => $entry['weather'][0]['description'],
                        'icon' => $entry['weather'][0]['icon'],
                        'wind' => [
                            'speed' => $entry['wind']['speed'],
                        ],
                        'pop' => $entry['pop'] ?? 0,
                        'rain' => $entry['rain'] ?? [],
                        'clouds' => $entry['clouds']['all'],
                        'pressure' => $entry['main']['pressure'],
                        'humidity' => $entry['main']['humidity'],
                    ];
                }
            }

            $daily_data = [];

            foreach ($forecast_data['list'] as $entry) {
                $date = date('Y-m-d', strtotime($entry['dt_txt']));

                if ($date === date('Y-m-d')) {
                    continue;
                }

                if (!isset($daily_data[$date])) {
                    $daily_data[$date] = [
                        'temp_max' => $entry['main']['temp_max'],
                        'temp_min' => $entry['main']['temp_min'],
                        'icon' => $entry['weather'][0]['icon'],
                        'description' => $entry['weather'][0]['description'],
                        'wind' => [
                            'speed' => $entry['wind']['speed'],
                        ],
                        'pop' => $entry['pop'] ?? 0,
                        'rain' => $entry['rain'] ?? [],
                        'clouds' => $entry['clouds']['all'],
                        'pressure' => $entry['main']['pressure'],
                        'humidity' => $entry['main']['humidity'],
                    ];
                } else {
                    $daily_data[$date]['temp_max'] = max($daily_data[$date]['temp_max'], $entry['main']['temp_max']);
                    $daily_data[$date]['temp_min'] = min($daily_data[$date]['temp_min'], $entry['main']['temp_min']);
                }
            }

            $counter = 0;
            foreach ($daily_data as $date => $data) {
                $weather['forecast'][] = [
                    'day' => date('d.m.', strtotime($date)),
                    'temp_max' => $this->sanitize_temperature(round($data['temp_max'])),
                    'temp_min' => $this->sanitize_temperature(round($data['temp_min'])),
                    'description' => $data['description'],
                    'icon' => $data['icon'],
                    'wind' => $data['wind'],
                    'pop' => $data['pop'],
                    'rain' => $data['rain'],
                    'clouds' => $data['clouds'],
                    'pressure' => $data['pressure'],
                    'humidity' => $data['humidity'],
                ];
                $counter++;
            }
        }

        return $weather;
    }

    private function fetch_dwd_warnings($region_id): bool|array
    {
        $url = 'https://www.dwd.de/DWD/warnungen/warnapp/json/warnings.json';
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $raw_body = wp_remote_retrieve_body($response);

        // Versuchen, den JSON-Teil zu extrahieren
        $start_pos = strpos($raw_body, '{');
        $end_pos = strrpos($raw_body, '}');
        if ($start_pos === false || $end_pos === false) {
            return [];
        }

        $json_string = substr($raw_body, $start_pos, $end_pos - $start_pos + 1);

        // JSON-Daten dekodieren
        $data = json_decode($json_string, true);

        if (!isset($data['warnings'][$region_id])) {
            return [];
        }

        $warnings = [];
        foreach ($data['warnings'][$region_id] as $warning) {
            $warnings[] = [
                'title' => $warning['headline'] ?? 'Keine Angabe',
                'severity' => $warning['severity'] ?? 'Unbekannt',
                'description' => $warning['description'] ?? 'Keine Beschreibung verfügbar',
                'start' => $warning['start'] ?? '',
                'end' => $warning['end'] ?? '',
            ];
        }

        return $warnings;
    }


    private function get_wind_direction($degree): string
    {
        $directions = ['N', 'NNO', 'NO', 'ONO', 'O', 'OSO', 'SO', 'SSO', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];

        return $directions[round($degree / 22.5) % 16];
    }

    private function get_beaufort_icon_class($wind_speed_kmh): string
    {
        $beaufort_scale = [
            0 => 'wi-wind-beaufort-0',  // Windstille        0-1 km/h
            1 => 'wi-wind-beaufort-1',  // Leiser Zug        1-5 km/h
            2 => 'wi-wind-beaufort-2',  // Leichte Brise     6-11 km/h
            3 => 'wi-wind-beaufort-3',  // Schwache Brise    12-19 km/h
            4 => 'wi-wind-beaufort-4',  // Mäßige Brise      20-28 km/h
            5 => 'wi-wind-beaufort-5',  // Frische Brise     29-38 km/h
            6 => 'wi-wind-beaufort-6',  // Starker Wind      39-49 km/h
            7 => 'wi-wind-beaufort-7',  // Steifer Wind      50-61 km/h
            8 => 'wi-wind-beaufort-8',  // Stürmischer Wind  62-74 km/h
            9 => 'wi-wind-beaufort-9',  // Sturm             75-88 km/h
            10 => 'wi-wind-beaufort-10', // Schwerer Sturm    89-102 km/h
            11 => 'wi-wind-beaufort-11', // Orkanart. Sturm   103-117 km/h
            12 => 'wi-wind-beaufort-12', // Orkan             > 117 km/h
        ];

        // Windgeschwindigkeit in m/s umrechnen
        $wind_ms = $wind_speed_kmh / 3.6;

        // Beaufort-Stufe ermitteln
        return match (true) {
            $wind_ms < 0.3 => $beaufort_scale[0],
            $wind_ms < 1.6 => $beaufort_scale[1],
            $wind_ms < 3.4 => $beaufort_scale[2],
            $wind_ms < 5.5 => $beaufort_scale[3],
            $wind_ms < 8.0 => $beaufort_scale[4],
            $wind_ms < 10.8 => $beaufort_scale[5],
            $wind_ms < 13.9 => $beaufort_scale[6],
            $wind_ms < 17.2 => $beaufort_scale[7],
            $wind_ms < 20.8 => $beaufort_scale[8],
            $wind_ms < 24.5 => $beaufort_scale[9],
            $wind_ms < 28.5 => $beaufort_scale[10],
            $wind_ms < 32.7 => $beaufort_scale[11],
            $wind_ms >= 32.7 => $beaufort_scale[12],
            default => 'wi-wind-beaufort-na',
        };

    }

    private function get_cardinal_wind_icon_class($cardinal_direction): string
    {
        // Mapping von Himmelsrichtung zu Icon-Klassen
        $icon_map = [
            'N' => 'wi-from-n',
            'NNO' => 'wi-from-nne',
            'NO' => 'wi-from-ne',
            'ONO' => 'wi-from-ene',
            'O' => 'wi-from-e',
            'OSO' => 'wi-from-ese',
            'SO' => 'wi-from-se',
            'SSO' => 'wi-from-sse',
            'S' => 'wi-from-s',
            'SSW' => 'wi-from-ssw',
            'SW' => 'wi-from-sw',
            'WSW' => 'wi-from-wsw',
            'W' => 'wi-from-w',
            'WNW' => 'wi-from-wnw',
            'NW' => 'wi-from-nw',
            'NNW' => 'wi-from-nnw',
        ];

        // Rückgabe der passenden Icon-Klasse oder Fallback-Icon
        return 'wi-wind ' . $icon_map[$cardinal_direction] ?? 'wi-na'; // Default-Fallback-Icon
    }

    private function get_weather_icon_class($icon_code): string
    {
        // Extrahiere den Basis-Code (z.B. '01' aus '01d' oder '01n')
        $base_code = substr((string) $icon_code, 0, 2);

        // Hole das Icon aus dem Basis-Mapping
        return self::WEATHER_ICON_BASE_MAP[$base_code] ?? 'wi-na'; // Fallback-Icon
    }


    private function sanitize_temperature($temperature): int
    {
        return $temperature == -0 ? 0 : $temperature;
    }

    public function load_widget_scripts()
    {
        // Wetter-Icons Hauptstylesheet
        wp_enqueue_style('weather-icons-style', 'https://cdnjs.cloudflare.com/ajax/libs/weather-icons/2.0.10/css/weather-icons.min.css');
        // Wetter-Icons Wind-Stylesheet
        wp_enqueue_style('weather-icons-wind-style', 'https://cdnjs.cloudflare.com/ajax/libs/weather-icons/2.0.10/css/weather-icons-wind.min.css');
        wp_enqueue_style('weather-widget-style', plugins_url('weather-widget.css', __FILE__));

    }


}

function register_fl_weather_widget()
{
    register_widget('FL_WeatherWidget');
}
add_action('widgets_init', 'register_fl_weather_widget');

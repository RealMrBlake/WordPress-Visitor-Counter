<?php
/*
Plugin Name: Visitor Counter with GeoIP and Map - by LuxeCommerce
Plugin URI: https://luxe-commerce.com
Description: Ein Plugin, um die Anzahl der Website-Besucher zu zählen, deren Herkunft zu ermitteln und im WP-Admin anzuzeigen.
Version: 6.5
Author: Louis-Alexander Kerst
Author URI: https://louis.alexander-kerst.de
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Besucher zählen und Herkunft ermitteln
function vc_count_visitor() {
    if (!is_admin()) {
        $count = get_option('vc_visitor_count', 0);
        $count++;
        update_option('vc_visitor_count', $count);
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $geo = vc_get_geo_info($ip);
        
        $visitor_data = get_option('vc_visitor_data', []);
        $visitor_data[] = [
            'ip' => $ip,
            'geo' => $geo,
            'timestamp' => current_time('mysql')
        ];
        
        update_option('vc_visitor_data', $visitor_data);
    }
}
add_action('wp', 'vc_count_visitor');

// Geo-Informationen mittels ipinfo.io abrufen
function vc_get_geo_info($ip) {
    $response = wp_remote_get("http://ipinfo.io/{$ip}/json");
    if (is_wp_error($response)) {
        return [];
    }

    $data = wp_remote_retrieve_body($response);
    return json_decode($data, true);
}

// Menüseite im Admin-Bereich hinzufügen
function vc_add_admin_menu() {
    add_menu_page(
        'Visitor Counter',
        'Visitor Counter',
        'manage_options',
        'visitor-counter',
        'vc_admin_page',
        'dashicons-visibility',
        6
    );
}
add_action('admin_menu', 'vc_add_admin_menu');

// Admin-Seiteninhalt
function vc_admin_page() {
    $count = get_option('vc_visitor_count', 0);
    $visitor_data = get_option('vc_visitor_data', []);
    ?>
    <div class="wrap">
        <h1>Visitor Counter</h1>
        <p>Die Anzahl der Besucher auf deiner Website beträgt: <strong><?php echo $count; ?></strong></p>
        <h2>Besucherdaten</h2>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>IP-Adresse</th>
                    <th>Herkunft</th>
                    <th>Datum und Uhrzeit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visitor_data as $visitor): ?>
                    <tr>
                        <td><?php echo esc_html($visitor['ip']); ?></td>
                        <td><?php echo esc_html(implode(', ', $visitor['geo'])); ?></td>
                        <td><?php echo esc_html($visitor['timestamp']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h2>Besucherkarte</h2>
        <div id="visitor-map" style="height: 500px;"></div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var map = L.map('visitor-map').setView([0, 0], 2);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var visitorData = <?php echo json_encode($visitor_data); ?>;

            visitorData.forEach(function(visitor) {
                if (visitor.geo && visitor.geo.loc) {
                    var loc = visitor.geo.loc.split(',');
                    var marker = L.marker([parseFloat(loc[0]), parseFloat(loc[1])]).addTo(map);
                    marker.bindPopup('<b>IP:</b> ' + visitor.ip + '<br><b>Location:</b> ' + visitor.geo.city + ', ' + visitor.geo.region + ', ' + visitor.geo.country);
                }
            });
        });
    </script>
    <?php
}

// Leaflet CSS und JS laden
function vc_enqueue_admin_scripts($hook) {
    if ($hook != 'toplevel_page_visitor-counter') {
        return;
    }
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js');
}
add_action('admin_enqueue_scripts', 'vc_enqueue_admin_scripts');

// Elementor-Widget registrieren
function vc_register_elementor_widget() {
    if (did_action('elementor/loaded')) {
        require_once(__DIR__ . '/visitor-counter-elementor-widget.php');
    }
}
add_action('init', 'vc_register_elementor_widget');

// Plugin bei Aktivierung installieren
function vc_activate_plugin() {
    if (get_option('vc_visitor_count') === false) {
        add_option('vc_visitor_count', 0);
    }
    if (get_option('vc_visitor_data') === false) {
        add_option('vc_visitor_data', []);
    }
}
register_activation_hook(__FILE__, 'vc_activate_plugin');

// Plugin bei Deaktivierung deinstallieren
function vc_deactivate_plugin() {
    delete_option('vc_visitor_count');
    delete_option('vc_visitor_data');
}
register_deactivation_hook(__FILE__, 'vc_deactivate_plugin');

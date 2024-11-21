<?php
/**
 * Plugin Name:       FT Weather Info
 * Plugin URI:        https://codecanyon.net/item/ft-weather-info/
 * Description:       This plugin will show Weather Information on your WebSite.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Muhammad Abu Foysal
 * Author URI:        https://www.facebook.com/muhammadabu.foysal
 * License:           Commercial
 * License URI:       https://themeforest.net/licenses/terms/regular
 * Text Domain:       ft-weather-info
 */

/**
 * Exit if accessed directly.
 */
defined( 'ABSPATH' ) || exit;

define( 'FT_WEATHER_INFO',  __FILE__ );

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
    require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

use Inc\Admin\Admin;

/**
 * If class is not exists.
 */

if ( !class_exists( 'Ft_Weather_Info' ) ) {
    /**
     * Class of the Plugin.
     */

    final class Ft_Weather_Info {


        /**
         * Weather Information.
         *
         * @var object
         */
        public $weather_info;


        /**
         * Short Code.
         *
         * @var object
         */
        public $short_code;


        /**
         * Class construcotr
         */
        private function __construct() {
            add_action( 'plugins_loaded', [$this, 'init_plugin'] );
            add_shortcode( 'ft-weather-info', [$this, 'fetch_weather_info'] );
        }


        /**
         * Initializes a singleton instance
         *
         * @return \Ft_Weather_Info
         */
        public static function init() {
            static $instance = false;
            if ( !$instance ) {
                $instance = new self();
            }
            return $instance;
        }


        /**
         * Get Weather info.
         *
         * @param array  $attributes  Shortcode attributes.
         *
         * @return object Shortcode output.
         */
        public function fetch_weather_info( $attributes ) {
            $attributes   = shortcode_atts( ['id' => ''], $attributes );
            $meta_details = get_post_meta( $attributes['id'] );
            
            if ( array_key_exists( 'location_input', $meta_details ) ) {
                if ( empty( get_option( 'ft_wi_api_key' ) ) ) {
                    $this->short_code .= '<div class="ft_wi_set_data">Please set API Key.</div>';
                    return $this->short_code;
                } else {
                    $api_url = "http://api.openweathermap.org/data/2.5/weather?q=" . $meta_details['location_input'][0] . "&appid=" . esc_attr( get_option( 'ft_wi_api_key' ) ) . "&units=" . (  ( array_key_exists( 'temperature_unit', $meta_details ) && 'fahrenheit' == $meta_details['temperature_unit'][0] ) ? 'imperial' : 'metric' ) . "&lang=" . ( array_key_exists( 'language', $meta_details ) ? $meta_details['language'][0] : '' );
                
                    $args = [
                        'timeout'    => 15,
                        'sslverify'  => false,
                        'redirection' => 5,
                    ];                  

                    // Send the request
                    $res = wp_remote_get($api_url, $args);                
                    $response = $res['body'];
                }
            } else {
                $this->short_code .= '<div class="ft_wi_set_data">Please set Location.</div>';
                return $this->short_code;
            }
            
            // If there is an error
            if ( is_wp_error( $response ) ) {
                $this->short_code .= '<div class="ft_wi_set_data">Weather information is not accessible due to Error.</div>';
                return $this->short_code;
            } else {
                //curl_close( $ch );
                $this->weather_info = json_decode( $response );
                $this->short_code .= '<div class="ft_wi_main" ' . (  ( array_key_exists( 'background_color', $meta_details ) || ( array_key_exists( 'text_color', $meta_details ) ) ) ? 'style="' : '' ) . (  ( array_key_exists( 'background_color', $meta_details ) ) ? 'background-color:' . $meta_details['background_color'][0] . ';' : '' ) . (  ( array_key_exists( 'text_color', $meta_details ) ) ? 'color:' . $meta_details['text_color'][0] . ';"' : '' ) . '>' . (  ( array_key_exists( 'title', $meta_details ) && 'on' == $meta_details['title'][0] ) ? '<div ' . (  ( array_key_exists( 'title_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['title_font'][0] . 'px;"' : '' ) . ' class="ft_wi_top">' . get_the_title( $attributes['id'] ) . '</div>' : '' );
                $this->short_code .= (  ( array_key_exists( 'location', $meta_details ) && 'on' == $meta_details['location'][0] ) ? '<div ' . (  ( array_key_exists( 'location_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['location_font'][0] . 'px;"' : '' ) . ' class="ft_wi_top">' . $this->weather_info->name . ', ' . $this->weather_info->sys->country . '</div>' : '' );
                $this->short_code .= '<div class="ft_wi_outer">' . (  ( array_key_exists( 'temperature', $meta_details ) && 'on' == $meta_details['temperature'][0] ) ? '<div ' . (  ( array_key_exists( 'temperature_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['temperature_font'][0] . 'px;"' : '' ) . ' class="ft_wi_temp">' . (  ( array_key_exists( 'temperature_unit', $meta_details ) && 'fahrenheit' == $meta_details['temperature_unit'][0] ) ? round( $this->weather_info->main->temp ) . ' &deg;F' : round( $this->weather_info->main->temp ) . ' &deg;C' ) . '</div>' : '' );
                $this->short_code .= '<div class="ft_wi_weather_all" >' . (  ( array_key_exists( 'weather_symbol', $meta_details ) && 'on' == $meta_details['weather_symbol'][0] ) ? '<div class="ft_wi_image"><img src="http://openweathermap.org/img/w/' . $this->weather_info->weather[0]->icon . '.png"></div>' : '' );
                $this->short_code .= (  ( array_key_exists( 'weather_condition', $meta_details ) && 'on' == $meta_details['weather_condition'][0] ) ? '<div' . (  ( array_key_exists( 'weather_condition_font', $meta_details ) ) ? ' style="font-size:' . $meta_details['weather_condition_font'][0] . 'px;"' : '' ) . '>' . (  ( 'en' == $meta_details['language'][0] ) ? ucwords( $this->weather_info->weather[0]->description ) : $this->weather_info->weather[0]->description ) . '</div>' : '' ) . '</div></div>';
                $this->short_code .= '<div class="ft_wi_inner">' . (  ( array_key_exists( 'current_time', $meta_details ) && 'on' == $meta_details['current_time'][0] ) ? '<div ' . (  ( array_key_exists( 'current_time_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['current_time_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Time</div><div>' . (  ( array_key_exists( 'time', $meta_details ) ) ? gmdate( $meta_details['time'][0] . ':i A', ( time() + $this->weather_info->timezone ) ) : '' ) . '</div></div>' : '' );
                $this->short_code .= ( array_key_exists( 'date', $meta_details ) && 'on' == $meta_details['date'][0] ) ? ( '<div ' . (  ( array_key_exists( 'date_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['date_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Date</div><div>' . ( array_key_exists( 'date_current', $meta_details ) ? gmdate( $meta_details['date_current'][0] ) : '' ) . '</div></div>' ) : '';
                $this->short_code .= ( array_key_exists( 'wind_speed', $meta_details ) && 'on' == $meta_details['wind_speed'][0] ) ? ( '<div ' . (  ( array_key_exists( 'wind_speed_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['wind_speed_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Wind</div><div>' . (  ( 'fahrenheit' == $meta_details['temperature_unit'][0] ) ? round( $this->weather_info->wind->speed ) . ' mph' : round(  ( $this->weather_info->wind->speed ) * 3.6 ) . ' kph' ) . '</div></div>' ) : '';
                $this->short_code .= ( array_key_exists( 'humidity', $meta_details ) && 'on' == $meta_details['humidity'][0] ) ? ( '<div ' . (  ( array_key_exists( 'humidity_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['humidity_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Humidity</div><div>' . $this->weather_info->main->humidity . '%</div></div>' ) : '';
                $this->short_code .= ( array_key_exists( 'pressure', $meta_details ) && 'on' == $meta_details['pressure'][0] ) ? ( '<div ' . (  ( array_key_exists( 'pressure_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['pressure_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Pressure</div><div>' . $this->weather_info->main->pressure . ' hPa</div></div>' ) : '';
                $this->short_code .= ( array_key_exists( 'visibility', $meta_details ) && 'on' == $meta_details['visibility'][0] ) ? ( '<div ' . (  ( array_key_exists( 'visibility_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['visibility_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Visibility</div><div>' . $this->weather_info->visibility . ' Meter</div></div>' ) : '';
                $this->short_code .= ( array_key_exists( 'cloudiness', $meta_details ) && 'on' == $meta_details['cloudiness'][0] ) ? ( '<div ' . (  ( array_key_exists( 'cloudiness_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['cloudiness_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Cloudiness</div><div>' . $this->weather_info->clouds->all . '%</div></div>' ) : '';
                $this->short_code .= ( array_key_exists( 'sunrise', $meta_details ) && 'on' == $meta_details['sunrise'][0] ) ? ( '<div ' . (  ( array_key_exists( 'sunrise_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['sunrise_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Sunrise</div><div>' . (  ( array_key_exists( 'time', $meta_details ) ) ? gmdate( $meta_details['time'][0] . ':i A', ( $this->weather_info->sys->sunrise + $this->weather_info->timezone ) ) : '' ) . '</div></div>' ) : '';
                $this->short_code .= (  ( array_key_exists( 'sunset', $meta_details ) && 'on' == $meta_details['sunset'][0] ) ? ( '<div ' . (  ( array_key_exists( 'sunset_font', $meta_details ) ) ? 'style="font-size:' . $meta_details['sunset_font'][0] . 'px;"' : '' ) . ' class="ft_wi_row"><div>Sunset</div><div>' . (  ( array_key_exists( 'time', $meta_details ) ) ? gmdate( $meta_details['time'][0] . ':i A', ( $this->weather_info->sys->sunset + $this->weather_info->timezone ) ) : '' ) . '</div></div>' ) : '' ) . '</div></div>';
                return $this->short_code;
            }
        }


        /**
         * Initialize the plugin
         *
         * @return void
         */
        public function init_plugin() {
            function  weather_info_css() {
                wp_enqueue_style( 'ft-weather-info', plugins_url( '/assets/css/weather_info.css', __FILE__ ), false, '1.0.0', 'all' );
            }
            add_action( 'init', 'weather_info_css' );
            if ( is_admin() ) {
                new Admin();
            }
        }
    }



    /**
     * Initializes the main plugin
     *
     * @return \Ft_Weather_Info
     */
    function ft_weather_info() {
        return Ft_Weather_Info::init();
    }

    // kick-off the plugin
    ft_weather_info();
}
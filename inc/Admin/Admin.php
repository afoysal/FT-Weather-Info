<?php

namespace Inc\Admin;

/**
 * The admin class
 */
class Admin
{
    /**
     * Array of English locale codes supported by WordPress.
     */
    public $english_locales; 
    

    /**
     * Initialize the class
     */
    public function __construct()
    {        
        add_action( 'admin_menu', [ $this, 'admin_menu'] );
        add_action( 'save_post', [ $this, 'save_meta_values' ] );
        add_action( 'init', [ $this, 'register_custom_post_type' ] );
        add_action( 'add_meta_boxes', [ $this, 'weather_meta_boxes' ] );
        add_filter( 'post_row_actions', [ $this, 'remove_view' ] , 10, 2 );
        add_action( 'admin_init', [ $this, 'settings_page_registration' ] );
        add_filter( 'get_sample_permalink_html', [ $this, 'hide_permalink' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );      
        add_filter( 'post_updated_messages', [ $this, 'weather_updated_messages' ] );
        add_filter( 'manage_weather_info_posts_columns', [ $this, 'shortcode_columns' ] );
        add_action( 'manage_weather_info_posts_custom_column', [ $this, 'column_content' ], 10, 2 );
        $this->english_locales = [ 'en_US', 'en_GB', 'en_CA', 'en_AU', 'en_NZ', 'en_ZA', 'en_IN', 'en_PH' ]; 
    }


    /**
     * Create shortcode coloumn
     *
     * @param array  $columns
     *
     * @return array $columns
     */
    public function shortcode_columns( $columns )
    {
        $columns[ 'ft_weather_info_short_code' ] = __( 'Shortcode', 'ft-weather-info' );
        return $columns;
    }


    /**
     * Shortcode column content
     *
     * @param $column
     *
     * @param $post_id
     *
     */
    public function column_content( $column, $post_id )
    {
        echo '<div class="ft-weather-info-shortcode">[ft-weather-info id="' . esc_attr($post_id) . '"]</div>';
    }


    /**
     * Update messages customization
     *
     * @param array  $messages
     *
     * @return array $messages
     */
    public function weather_updated_messages( $messages )
    {
        $messages['weather_info'][1] = __( 'Weather Infomation Updated.', 'ft-weather-info' );
        $messages['weather_info'][6] = __( 'Weather Infomation Published.', 'ft-weather-info' );
        return $messages;
    }


    /**
     * Hide Permalink
     *
     * @return void
     */
    public function hide_permalink()
    {
        return '';
    }


    /**
     * Get Weather info.
     *
     * @param $post_id
     *
     */
    public function save_meta_values( $post_id )
    {
        global $post;

        // nonce check
        if ( !isset( $_POST['ft_wi_nonce'] ) || !check_admin_referer(basename(__FILE__), 'ft_wi_nonce') ) {
            return $post_id;
        }

        // Check that the nonce field was submitted and verify the nonce.
        if ( !check_admin_referer( basename(__FILE__), 'ft_wi_nonce' ) ) {
            return $post_id;
        }

        //check current user permissions
        $post_type = get_post_type_object(get_post_type($post_id));

        if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) {
            return $post_id;
        }

        // check if the post is not an auto-save or revision
        if ( wp_is_post_revision( $post_id ) || 'weather_info' !== get_post_type( $post_id ) ) {
            return $post_id;
        }

        //Filtering fields and saving meta values
        $fields = [
            'location_input'         => FILTER_SANITIZE_SPECIAL_CHARS,
            'location'               => FILTER_SANITIZE_SPECIAL_CHARS,
            'temperature'            => FILTER_SANITIZE_SPECIAL_CHARS,
            'temperature_unit'       => FILTER_SANITIZE_SPECIAL_CHARS,
            'weather_symbol'         => FILTER_SANITIZE_SPECIAL_CHARS,
            'weather_condition'      => FILTER_SANITIZE_SPECIAL_CHARS,
            'time'                   => FILTER_SANITIZE_SPECIAL_CHARS,
            'date'                   => FILTER_SANITIZE_SPECIAL_CHARS,
            'language'               => FILTER_SANITIZE_SPECIAL_CHARS,
            'title'                  => FILTER_SANITIZE_SPECIAL_CHARS,
            'temperature_input'      => FILTER_SANITIZE_SPECIAL_CHARS,
            'weather'                => FILTER_SANITIZE_SPECIAL_CHARS,
            'current_time'           => FILTER_SANITIZE_SPECIAL_CHARS,
            'date_current'           => FILTER_SANITIZE_SPECIAL_CHARS,
            'wind_speed'             => FILTER_SANITIZE_SPECIAL_CHARS,
            'humidity'               => FILTER_SANITIZE_SPECIAL_CHARS,
            'pressure'               => FILTER_SANITIZE_SPECIAL_CHARS,
            'visibility'             => FILTER_SANITIZE_SPECIAL_CHARS,
            'cloudiness'             => FILTER_SANITIZE_SPECIAL_CHARS,
            'sunrise'                => FILTER_SANITIZE_SPECIAL_CHARS,
            'sunset'                 => FILTER_SANITIZE_SPECIAL_CHARS,
            'title_font'             => FILTER_SANITIZE_NUMBER_INT,
            'location_font'          => FILTER_SANITIZE_NUMBER_INT,
            'temperature_font'       => FILTER_SANITIZE_NUMBER_INT,
            'weather_condition_font' => FILTER_SANITIZE_NUMBER_INT,
            'current_time_font'      => FILTER_SANITIZE_NUMBER_INT,
            'date_font'              => FILTER_SANITIZE_NUMBER_INT,
            'wind_speed_font'        => FILTER_SANITIZE_NUMBER_INT,
            'humidity_font'          => FILTER_SANITIZE_NUMBER_INT,
            'pressure_font'          => FILTER_SANITIZE_NUMBER_INT,
            'visibility_font'        => FILTER_SANITIZE_NUMBER_INT,
            'cloudiness_font'        => FILTER_SANITIZE_NUMBER_INT,
            'sunrise_font'           => FILTER_SANITIZE_NUMBER_INT,
            'sunset_font'            => FILTER_SANITIZE_NUMBER_INT,
            'background_color'       => FILTER_SANITIZE_SPECIAL_CHARS,
            'text_color'             => FILTER_SANITIZE_SPECIAL_CHARS,
        ];


        // Save values
        foreach ($fields as $field_name => $flag) {
            $field = filter_input(INPUT_POST, $field_name, $flag);
        
            if (isset($field) && $field !== '') { 
                update_post_meta($post_id, $field_name, $field);
            } else {
                delete_post_meta($post_id, $field_name);
            }
        }   
        
        return $post_id;
    }


    /**
     * Register Custom Post type
     *
     * @return void
     *
     */
    public function register_custom_post_type()
    {
        $supports = [ 'title' ];
        $labels = [
            'name'           => __( 'Weathers', 'ft-weather-info' ),
            'singular_name'  => __( 'Weather', 'ft-weather-info' ),
            'name_admin_bar' => __( 'Weather', 'ft-weather-info' ),
            'add_new'        => __( 'Add Weather', 'ft-weather-info' ),
            'add_new_item'   => __( 'Add Weather', 'ft-weather-info' ),
            'new_item'       => __( 'New Weather', 'ft-weather-info' ),
            'edit_item'      => __( 'Edit Weather', 'ft-weather-info' ),
            'view_item'      => __( 'View Weather', 'ft-weather-info' ),
            'all_items'      => __( 'Weathers', 'ft-weather-info' ),
            'search_items'   => __( 'Search Weathers', 'ft-weather-info' ),
            'not_found'      => __( 'No Weather Information Found.', 'ft-weather-info' ),
        ];
        $args = [
            'public'              => true,
            'show_ui'             => true,
            'supports'            => $supports,
            'labels'              => $labels,
            'public'              => true,
            'description'         => 'Weather Information',
            'has_archive'         => true,
            'exclude_from_search' => false,
            'show_in_menu'        => 'weather_info',
        ];
        register_post_type( 'weather_info', $args );
    }


    /**
     * Weather Meta Boxes
     *
     * @return void
     */
    public function weather_meta_boxes()
    {
        add_meta_box( 'ft_wi_weather_settings', __( 'Weather Settings', 'ft-weather-info' ), [ $this, 'weather_settings_html' ], 'weather_info', 'normal', 'default');
        add_meta_box( 'ft_wi_display_settings', __( 'Display Settings', 'ft-weather-info' ), [ $this, 'display_settings_html' ], 'weather_info', 'normal', 'default');
        add_meta_box( 'ft_wi_style_settings', __( 'Color Settings', 'ft-weather-info' ), [ $this, 'style_settings_html' ], 'weather_info', 'normal', 'default');
    }


    /**
     * Weather Settings HTML
     *
     * @param object $post
     *
     * @return void
     */
    public function weather_settings_html( $post )
    {
        $ft_wi_stored_meta = get_post_meta( $post->ID );

        //Language name
        $languages_array = [
            "en"    => "English",
            "af"    => "Afrikaans",
            "al"    => "Albanian",
            "ar"    => "Arabic",
            "az"    => "Azerbaijani",
            "bg"    => "Bulgarian",
            "ca"    => "Catalan",
            "cz"    => "Czech",
            "da"    => "Danish",
            "de"    => "German",
            "el"    => "Greek",
            "eu"    => "Basque",
            "fa"    => "Persian (Farsi)",
            "fi"    => "Finnish",
            "fr"    => "French",
            "gl"    => "Galician",
            "he"    => "Hebrew",
            "hi"    => "Hindi",
            "hr"    => "Croatian",
            "hu"    => "Hungarian",
            "id"    => "Indonesian",
            "it"    => "Italian",
            "ja"    => "Japanese",
            "kr"    => "Korean",
            "la"    => "Latvian",
            "lt"    => "Lithuanian",
            "mk"    => "Macedonian",
            "no"    => "Norwegian",
            "nl"    => "Dutch",
            "pl"    => "Polish",
            "pt"    => "Portuguese",
            "pt_br" => "Português Brasil",
            "ro"    => "Romanian",
            "ru"    => "Russian",
            "sv,se" => "Swedish",
            "sk"    => "Slovak",
            "sl"    => "Slovenian",
            "sp,es" => "Spanish",
            "sr"    => "Serbian",
            "th"    => "Thai",
            "tr"    => "Turkish",
            "ua,uk" => "Ukrainian",
            "vi"    => "Vietnamese",
            "zh_cn" => "Chinese Simplified",
            "zh_tw" => "Chinese Traditional",
            "zu"    => "Zulu",
        ];

        //Language option values
        $languages = '';

        //Create option values
        foreach ( $languages_array as $key => $language ) {
            $languages .= '<option value="' . esc_attr( $key ) . '"' . ( !empty( $ft_wi_stored_meta['language'] ) && $ft_wi_stored_meta['language'][0] == $key ? 'selected="selected"' : '' ) . '>' . $language . '</option>';
        }

        //Fields array
        $fields = [
            'location'   => '<input type="text" value="' . (!empty($ft_wi_stored_meta['location_input']) ? esc_attr($ft_wi_stored_meta['location_input'][0]) : '') . '"  name="location_input" />',

            'temperature'=> '<select name="temperature_unit">
                                <option ' . (!empty($ft_wi_stored_meta['temperature_unit']) && 'celcius' == $ft_wi_stored_meta['temperature_unit'][0] ? 'selected="selected"' : '') . ' value="celcius">Celcius (&deg;C)</option>
                                <option ' . (!empty($ft_wi_stored_meta['temperature_unit']) && 'fahrenheit' == $ft_wi_stored_meta['temperature_unit'][0] ? 'selected="selected"' : '') . ' value="fahrenheit">Fahrenheit (&deg;F)</option>
                            </select>',

            'time'      => '<select name="time">
                            <option ' . (!empty($ft_wi_stored_meta['time']) && "g" == $ft_wi_stored_meta['time'][0] ? 'selected="selected"' : '') . ' value="g">12 Hrs</option>
                            <option ' . (!empty($ft_wi_stored_meta['time']) && "H" == $ft_wi_stored_meta['time'][0] ? 'selected="selected"' : '') . ' value="H">24 Hrs</option>
                        </select>',

            'date'      => '<select name="date_current">
                            <option ' . (!empty($ft_wi_stored_meta['date_current']) && 'd M Y' == $ft_wi_stored_meta['date_current'][0] ? 'selected="selected"' : '') . ' value="d M Y">' . gmdate('d M Y') . '</option>
                            <option ' . (!empty($ft_wi_stored_meta['date_current']) && 'd/m/y' == $ft_wi_stored_meta['date_current'][0] ? 'selected="selected"' : '') . ' value="d/m/y">' . gmdate('d/m/y') . '</option>
                            <option ' . (!empty($ft_wi_stored_meta['date_current']) && 'Y/m/d' == $ft_wi_stored_meta['date_current'][0] ? 'selected="selected"' : '') . ' value="Y/m/d">' . gmdate('Y/m/d') . '</option>
                            <option ' . (!empty($ft_wi_stored_meta['date_current']) && 'M j, Y' == $ft_wi_stored_meta['date_current'][0] ? 'selected="selected"' : '') . ' value="M j, Y">' . gmdate('M j, Y') . '</option>
                        </select>',

            'language'  => '<select name="language">' . $languages . '</select>',
        ];

        wp_nonce_field( basename(__FILE__), 'ft_wi_nonce' );

        ?>
            <table class="form-table">
                <?php
                    foreach ( $fields as $key => $field ) {
                        ?>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( $key ); ?>">
                                        <?php 
                                            echo in_array( get_locale(), $this->english_locales ) ? esc_html(ucfirst( $key )) : esc_html($key, 'ft-weather-info' );
                                        ?>
                                    </label>
                                </th>
                                <td>
                                    <?php 
                                        echo wp_kses( $field, array(
                                            'input' => array(
                                                'type' => array(),
                                                'value' => array(),
                                                'name' => array(),
                                                'id' => array(),
                                                'class' => array(),
                                                'placeholder' => array(),
                                            ),
                                            'select' => array(
                                                'name' => array(),
                                                'id' => array(),
                                                'class' => array(),
                                            ),
                                            'option' => array(
                                                'value' => array(),
                                            ),
                                            'label' => array(
                                                'for' => array(),
                                            ),
                                            'th' => array(),
                                            'tr' => array(),
                                            'td' => array(),
                                        ) ); 
                                    ?>
                                </td>
                            </tr>
                        <?php
                    }
                ?>
            </table>
        <?php
    }


    /**
     * Display Settings HTML
     *
     * @param object $post
     *
     * @return void
     */
    public function display_settings_html( $post )
    {
        $ft_wi_stored_meta = get_post_meta( $post->ID );

        //Fields array
        $fields = [
            'title',
            'location',
            'temperature',
            'weather_symbol',
            'weather_condition',
            'current_time',
            'date',
            'wind_speed',
            'humidity',
            'pressure',
            'visibility',
            'cloudiness',
            'sunrise',
            'sunset',
        ];

        ?>
            <table class="form-table">
                <?php
                    foreach ( $fields as $field ) {
                        ?>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr($field); ?>">
                                        <?php 
                                            echo esc_html( in_array( get_locale(), $this->english_locales ) ? ucwords( str_replace( '_', ' ', $field ) ) : str_replace( '_', ' ', $field ), 'ft-weather-info' );
                                        ?>
                                    </label>
                                </th>
                                <td>
                                    <?php                                            
                                        if(!empty( $ft_wi_stored_meta )) {
                                            //for edit existing entry
                                            ?>                                
                                                <label class="ft_wi_switch">
                                                    <input type="checkbox" name="<?php echo esc_attr($field); ?>" <?php echo esc_attr($ft_wi_stored_meta[$field][0]) == 'on' ? 'checked="checked"' : '' ; ?> />
                                                    <div class="ft_wi_slider">
                                                        <span class="ft_wi_show">Show</span>
                                                        <span class="ft_wi_hide">Hide</span>
                                                    </div>
                                                </label>
                                            <?php 
                                        } else {
                                            //for new entry
                                            ?>
                                                <label class="ft_wi_switch">
                                                    <input type="checkbox" name="<?php echo esc_attr($field); ?>" checked="checked" />
                                                    <div class="ft_wi_slider">
                                                        <span class="ft_wi_show">Show</span>
                                                        <span class="ft_wi_hide">Hide</span>
                                                    </div>
                                                </label>
                                            <?php
                                        }
                                    ?>
                                </td>

                                <?php
                                    if ( 'weather_symbol' != $field ) {
                                        if ( ( $field == 'title' && !array_key_exists( 'title', $ft_wi_stored_meta )) || ( $field == 'location' && !array_key_exists( 'location', $ft_wi_stored_meta )) || ( $field == 'temperature' &&  !array_key_exists( 'temperature', $ft_wi_stored_meta ))) {
                                            ?>
                                                <td>
                                                    <label for="<?php echo esc_attr($field); ?>">
                                                        <?php  esc_html_e( 'Font Size', 'ft-weather-info' ); ?>
                                                    </label>
                                                </td>
                                                <td>
                                                    <select name="<?php echo esc_attr($field); ?>_font">
                                                        <?php
                                                            for ( $i = 10;  $i < 31;  $i++ ) {
                                                                echo '<option value="' . esc_attr($i) . '" ' . ( $i == 20 ? 'selected = "selected"' : '' ) . '>' . esc_html($i) . '</option>';
                                                            }
                                                        ?>
                                                    </select>
                                                </td>
                                            <?php
                                        } else {
                                            ?>
                                                <td>
                                                    <label for="<?php echo esc_attr($field);?>">
                                                        <?php esc_html_e( 'Font Size', 'ft-weather-info' );?>
                                                    </label>
                                                </td>
                                                <td>
                                                    <select name="<?php echo esc_attr($field);?>_font">
                                                        <?php
                                                            for ( $i = 10; $i < 26; $i++ ) {
                                                                echo '<option value="' . esc_attr($i) . '"' . ( !empty( $ft_wi_stored_meta[$field . '_font'] ) && $ft_wi_stored_meta[$field . '_font'][0] == $i ? ' selected ' : '' ) . '>' . esc_html($i) . '</option>';
                                                            }
                                                        ?>
                                                    </select>
                                                </td>
                                            <?php
                                        }
                                    }
                                ?>
                            </tr>
                        <?php
                    }
                ?>
            </table>
        <?php
    }

    
    /**
     * Style Settings HTML
     *
     * @param object $post
     *
     * @return void
     */
    public function style_settings_html( $post )
    {
        $ft_wi_stored_meta = get_post_meta( $post->ID );

        //Fields array
        $fields = [ 'background_color', 'text_color' ];
        ?>
            <table class="form-table">
                <?php
                    foreach ( $fields as $field ) {
                        ?>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr($field); ?>">
                                        <?php                                             
                                            echo esc_html( in_array( get_locale(), $this->english_locales ) ? ucwords( str_replace( '_', ' ', $field ) ) : str_replace( '_', ' ', $field ), 'ft-weather-info' );
                                        ?>
                                    </label>
                                </th>
                                <td>
                                    <?php 
                                        if(($field == 'text_color' && !array_key_exists( 'text_color', $ft_wi_stored_meta )) || ($field == 'background_color' && !array_key_exists( 'background_color', $ft_wi_stored_meta ))) {
                                            ?>
                                                <input type="color" name="<?php echo esc_attr($field); ?>" value="<?php echo $field == 'text_color' ? '#ffffff' : '#D85D5D' ?>">
                                            <?php

                                        } else {
                                            ?>
                                                <input type="color" name="<?php echo esc_attr($field); ?>" <?php echo (!empty( $ft_wi_stored_meta[$field] ) && count( $ft_wi_stored_meta[$field] ) != 0 ? 'value="' . esc_attr($ft_wi_stored_meta[$field][0]) . '"' : ''); ?>>
                                            <?php
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php
                    }
                ?>
            </table>
        <?php
    }



    /**
     * API Key field HTML
     *
     * @return void
     */
    public function weather_info_api_field()
    {
        echo '<input type="text" name="ft_wi_api_key" value="' . esc_attr( get_option( 'ft_wi_api_key' ) ) . '" placeholder="' . esc_attr__( 'API Key', 'ft-weather-info' ) . '" />
        <div id="ft_wi_collect_api">' .esc_attr__( 'Collect API Key from ', 'ft-weather-info' ) . '<a href="https://openweathermap.org" target="_blank">' . esc_attr__( 'Open Weather', 'ft-weather-info' ) . '</a></div>';
    }


    /**
     * Admin Menu 
     *
     * @return void
     */
    public function admin_menu() {
        add_menu_page( 'Weather Information', 'Weather Info', 'manage_options', 'weather_info', [$this, 'api_key_page'], 'dashicons-info-outline', 30 );
        add_submenu_page( 'weather_info', 'Weather Information | API Key', 'API Key', 'manage_options', 'weather_info_page', [ $this, 'api_key_page' ], 2 );
        add_submenu_page( 'weather_info', 'Weather Information | New Weather', 'Add Weather', 'manage_options', 'post-new.php?post_type=weather_info', '', 1 );
    }


    /**
     * API Key Form
     *
     * @return void
     */
    public function settings_page_registration()
    {
        register_setting( 
            'weather_info_api_key', 
            'ft_wi_api_key',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ) 
        );
        
        add_settings_section( 'ft-weather-info-options', '', '', 'weather_info_api_key' );
        add_settings_field( 'sidebar-name', __( 'API Key of Open Weather', 'ft-weather-info' ), [ $this, 'weather_info_api_field' ], 'weather_info_api_key', 'ft-weather-info-options' );
    }



    /**
     * API Key Page HTML
     *
     * @return void
     */
    public function api_key_page()
    { 
       ?>
            <div class="wrap">
                <h1>
                    <?php   
                        esc_html_e( 'Put your API Key', 'ft-weather-info' ); 
                    ?>
                </h1>
                <?php 
                    settings_errors(); 
                ?>
                <form id="ft_wi_weather_info_api_key" method="post" action="options.php">
                    <?php 
                        settings_fields( 'weather_info_api_key' ); 
                        do_settings_sections( 'weather_info_api_key' );                     
                        submit_button(); 
                    ?>
                </form>
            </div>
        <?php
    }



    /**
     * Enqueue Admin Style sheet
     *
     * @return void
     */
    function enqueue_admin_styles() {
        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);
        $page = sanitize_text_field(filter_input(INPUT_GET, 'page'));
        $action = sanitize_text_field(filter_input(INPUT_GET, 'action'));
        
        if (($post_type === 'weather_info') || ($page === 'weather_info_page') || ($post_type === 'weather_info' && $action === 'edit')) {
            wp_enqueue_style('weather-info-admin-styles', plugins_url('assets/css/admin_styles.css', FT_WEATHER_INFO), [], '1.0.0', 'all');
        }
    }
    


    /**
     * Remove View from list
     *
     * @param $actions $post
     *
     * @return $actions
     */
    public function remove_view( $actions, $post )
    {
        unset( $actions['view'] );
        return $actions;
    }
}
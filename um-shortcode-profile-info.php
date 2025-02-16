<?php
/**
 * Plugin Name:     Ultimate Member - Shortcode Profile Info
 * Description:     Extension to Ultimate Member for displaying User Profile info incl country flag at non UM pages and UM profile pages via a shortcode.
 * Version:         1.4.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Plugin URI:      https://github.com/MissVeronica/um-shortcode-profile-info
 * Update URI:      https://github.com/MissVeronica/um-shortcode-profile-info
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.10.0
 * Reference flags: https://github.com/lipis/flag-icons
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

class UM_Shortcode_Profile_Info {

    public $fetch_user = false;
    public $wp_users_table = array( 'user_login',
                                    'user_nicename',
                                    'user_email',
                                    'user_url',
                                    'display_name',
                                );


    function __construct() {

        define( 'Plugin_Basename_SPI', plugin_basename( __FILE__ ));

        add_shortcode( 'um_profile_info', array( $this, 'um_profile_info_shortcode' ), 10, 2 );

        add_action( 'um_after_profile_name_inline', array( $this, 'display_country_flag_profile' ), 9, 2 );
        add_action( 'um_members_after_user_name',   array( $this, 'display_country_flag_directory' ), 10, 2 );
        add_action( 'wp_enqueue_scripts',           array( $this, 'enqueue_library' ) );

        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

            add_filter( 'um_settings_structure', array( $this, 'create_setting_structures' ), 10, 1 );
            add_filter( 'plugin_action_links_' . Plugin_Basename_SPI, array( $this, 'plugin_settings_link' ), 10, 1 );
        }
    }

    public function plugin_settings_link( $links ) {

        $url = get_admin_url() . "admin.php?page=um_options&tab=appearance";
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings' ) . '</a>';

        return $links;
    }

    public function um_profile_info_shortcode( $atts, $content = '' ) {

        if ( isset( $atts['type'] ) && ! empty( $atts['type'] )) {

            $type = sanitize_text_field( $atts['type'] );
            $user_id = $this->try_to_find_the_userid( $type, $atts );

            if ( ! empty( $user_id )) {

                switch( $type ) {

                    case 'image':           return $this->display_profile_image( $user_id, $atts, $content );
                    case 'video':           return $this->display_profile_video( $user_id, $atts, $content );
                    case 'profile_link':    return $this->display_profile_link( $user_id, $atts, $content );
                    case 'url':             return $this->display_profile_url( $user_id, $atts, $content );
                    case 'meta_value':      return $this->display_profile_meta_value( $user_id, $atts );
                    case 'phone':           return $this->display_profile_phone( $user_id, $atts, $content );
                    case 'email':           return $this->display_profile_email( $user_id, $atts, $content );
                    case 'file':            return $this->display_profile_file( $user_id, $atts, $content );
                    case 'country_flag':    return $this->display_country_flag( $user_id, $atts, $content );
                    default:                return '';
                }
            }
        }

        return '';
    }

    public function display_profile_image( $user_id, $atts, $content ) {

        $image_name = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $image_name )) {
            return '';
        }

        if ( substr(  $image_name, 0, 4 ) == '<img' ) {

            if ( $this->fetch_user ) {
                $this->fetch_user = false;
                $image_name = $this->get_um_meta_value( $user_id, $atts );
                if ( empty( $image_name )) {
                    return '';
                }

            } else {
                return $image_name;
            }
        }

        $filemtime = filemtime( $this->get_um_filesystem( 'base_dir' ) . $user_id . DIRECTORY_SEPARATOR . $image_name );
        $photo_url = $this->get_um_filesystem( 'base_url' ) . $user_id . "/" . $image_name . '?=' . $filemtime;
        $width = ( isset( $atts['width'] ) && ! empty( $atts['width'] )) ? sanitize_text_field( $atts['width']  ) : '';
        $link_text = $this->get_link_text( $user_id, $atts, $content );
        $modal = '<a href="#" class="um-photo-modal" data-src="' . esc_url( $photo_url ) . '">';

        return $modal . '<img src="' . esc_url( $photo_url ) . '" width="' . esc_attr( $width ) . '" alt="Image" title="' . esc_attr( $link_text ) . '"/></a>';
    }

    public function display_profile_video( $user_id, $atts, $content ) {

        $video_name = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $video_name )) {
            return '';
        }

        $video_url = $this->get_um_filesystem( 'base_url' ) . $user_id . "/" . $video_name;
        $width = ( isset( $atts['width'] ) && ! empty( $atts['width'] )) ? sanitize_text_field( $atts['width']  ) : '';

        $output  = '<video width="' . esc_attr( $width ) . '" controls="controls">';
        $output .= '<source src="' . esc_url( $video_url ) . '" type="video/mp4">';
        $output .= esc_html__( 'No browser support', 'ultimate-member' );
        $output .= '</video>';

        return $output;
    }

    public function display_profile_link( $user_id, $atts, $content ) {

        $user_profile_url = um_user_profile_url( $user_id );
        if ( empty( $user_profile_url )) {
            return '';
        }

        $onclick_alert = $this->alert_external_url_link( $user_profile_url );
        $link_text = $this->get_link_text( $user_id, $atts, $content );

        return $this->get_field_icon( $atts ) . '<a href="' . esc_url( $user_profile_url ) . '" title="' . esc_attr( $link_text ) . '"' . $onclick_alert . '>' . esc_attr( $link_text ) . '</a>';
    }

    public function display_profile_url( $user_id, $atts, $content ) {

        $url = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $url )) {
            return '';
        }

        $onclick_alert = $this->alert_external_url_link( $url );
        $link_text = $this->get_link_text( $user_id, $atts, $content );

        return $this->get_field_icon( $atts ) . '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $link_text ) . '"' . $onclick_alert . '>' . esc_attr( $link_text ) . '</a>';
    }

    public function display_profile_meta_value( $user_id, $atts ) {

        $meta_value = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $meta_value )) {
            return '';
        }

        $meta_value = maybe_unserialize( $meta_value );

        if ( is_array( $meta_value ) ) {
            $meta_value = implode( ',', $meta_value );
        }

        return $this->get_field_icon( $atts ) . wp_kses_post( $meta_value );
    }

    public function display_profile_phone( $user_id, $atts, $content ) {

        $phone_number = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $phone_number )) {
            return '';
        }

        $link_text = $this->get_link_text( $user_id, $atts, $content );

        return $this->get_field_icon( $atts ) . '<a href="' . esc_url( 'tel:' . $phone_number ) . '" rel="nofollow" title="' . esc_attr( $link_text ) . '">' . esc_attr( $phone_number ) . '</a>';
    }

    public function display_profile_email( $user_id, $atts, $content ) {

        $email = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $email ) || ! is_email( $email )) {
            return '';
        }

        $link_text = $this->get_link_text( $user_id, $atts, $content );

        return $this->get_field_icon( $atts ) . '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_attr( $link_text ) . '</a>';
    }

    public function display_profile_file( $user_id, $atts, $content ) {

        $file_name = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $file_name )) {
            return '';
        }

        $file_url = $this->get_um_filesystem( 'base_url' ) . $user_id . "/" . $file_name;
        $onclick_alert = $this->alert_external_url_link( $file_url );
        $link_text = $this->get_link_text( $user_id, $atts, $content );

        return $this->get_field_icon( $atts ) . '<a href="' . esc_url( $file_url ) . '" target="_blank" alt="File" title="' . esc_attr( $link_text ) . '"' . $onclick_alert . '>' . esc_attr( $link_text ) . '</a>';
    }

    public function get_um_meta_value( $user_id, $atts ) {

        $meta_key = ( isset( $atts['meta_key'] ) && ! empty( $atts['meta_key'] )) ? sanitize_text_field( $atts['meta_key']  ) : '';
        if ( empty( $meta_key )) {
            return '';
        }

        if ( $this->fetch_user ) {
            $meta_value = um_user( $meta_key );

        } else {

            if ( in_array( $meta_key, $this->wp_users_table )) {
                $user_info = get_userdata( $user_id );
                $meta_value = $user_info->$meta_key;

            } else {
                $meta_value = get_user_meta( $user_id, $meta_key, true );
            }
        }

        return $meta_value;
    }

    public function get_field_icon( $atts ) {

        $icon = ( isset( $atts['icon'] ) && ! empty( $atts['icon'] )) ? '<i class="' . esc_attr( $atts['icon'] ) . '"></i> ' : '';

        return $icon;
    }

    public function try_to_find_the_userid( $type, $atts ) {

        $this->fetch_user = false;
        $user_id = apply_filters( 'um_profile_info_shortcode', false, $type, $atts );

        if ( empty( $user_id ) && isset( $atts['user_id'] )) {
            $user_id = sanitize_text_field( $atts['user_id'] );
        }

        if ( empty( $user_id )) {
            $user_id = um_get_requested_user();
            if ( ! empty( $user_id )) {
                $this->fetch_user = true;
            }
        }

        if ( empty( $user_id )) {

            if ( is_singular() ) {
                $user_id = get_post()->post_author;
            } elseif ( is_author() ) {
                $user_id = get_the_author_meta( 'ID' );
            }
        }

        return absint( $user_id );
    }

    public function get_link_text( $user_id, $atts, $content ) {

        $link_text = $content;
        if ( isset( $atts['text_meta_key'] ) && ! empty( $atts['text_meta_key'] )) {

            $atts['meta_key'] = $atts['text_meta_key'];
            $link_text_value = $this->get_um_meta_value( $user_id, $atts );

            if ( ! empty( $link_text_value )) {
                $link_text = $link_text_value;
            }
        }

        return $link_text;
    }

    public function alert_external_url_link( $url ) {

        $onclick_alert = '';

        if ( UM()->options()->get( 'allow_url_redirect_confirm' ) == 1 && $url !== wp_validate_redirect( $url ) ) {

            $onclick_alert = sprintf( ' onclick="' . esc_attr( 'return confirm( "%s" );' ) . '"',
                                        esc_js( sprintf( esc_html__( 'This link leads to a 3rd-party website. Make sure the link is safe and you really want to go to this website: \'%s\'', 'ultimate-member' ), $url ) )
                                    );
        }

        return $onclick_alert;
    }

	public function display_country_flag_directory( $user_id, $directory_data ) {

        if ( UM()->options()->get( 'shortcode_profile_info_flag_directory' ) == 1 ) {

		    echo $this->display_country_flag( $user_id );
        }
	}

	public function display_country_flag_profile( $args, $user_id ) {

        if ( UM()->options()->get( 'shortcode_profile_info_flag_profile' ) == 1 ) {

		    echo $this->display_country_flag( $user_id );
        }
	}

	public function display_country_flag( $user_id, $atts = array(), $content = '' ) {

        if ( ! empty( $user_id )) {

            $meta_key = ( isset( $atts['meta_key'] ) && ! empty( $atts['meta_key'] )) ? sanitize_text_field( $atts['meta_key']  ) : 'country';

            $user_country = get_user_meta( $user_id, $meta_key, true );
            $countries    = UM()->builtin()->get( 'countries' );
            $country_code = array_search( $user_country, $countries, true ); 

            if ( ! empty( $country_code ) ) {

                $title = ( isset( $atts['text_meta_key'] ) && $atts['text_meta_key'] != 'country' ) ? $this->get_link_text( $user_id, $atts, $content ) : $user_country;
                $font_size = ( isset( $atts['height'] ) && ! empty( $atts['height'] )) ? sanitize_text_field( $atts['height']  ) : '';

                return '<span style="font-size:' . esc_attr( $font_size ) . ';" class="fi fi-' . esc_attr( strtolower( $country_code ) ) . '" title="' . esc_attr( $title ) . '"></span>';
            }
        }

        return '';
	}

	public function enqueue_library() {

        $plugin_data = get_plugin_data( __FILE__ );
        $version = ( isset( $plugin_data['Version'] ) && ! empty( $plugin_data['Version'] )) ? $plugin_data['Version'] : '1.0.0';

		wp_register_style( 'country_flag_lib', 'https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css', array(), $version, 'all' );
		wp_enqueue_style( 'country_flag_lib' );
    }

    public function get_possible_plugin_update( $plugin ) {

        $plugin_data = get_plugin_data( __FILE__ );

        $documention = sprintf( ' <a href="%s" target="_blank" title="%s">%s</a>',
                                        esc_url( $plugin_data['PluginURI'] ),
                                        esc_html__( 'GitHub plugin documentation and download', 'ultimate-member' ),
                                        esc_html__( 'Documentation', 'ultimate-member' ));

        $description = sprintf( esc_html__( 'Plugin "Shortcode Profile Info" version %s - tested with UM 2.10.0 - %s', 'ultimate-member' ),
                                                                            $plugin_data['Version'], $documention );

        return $description;
    }

    public function get_um_filesystem( $function ) {

        if ( method_exists( UM()->common()->filesystem(), 'get_basedir' ) ) {

            switch( $function ) {
                case 'base_dir': $value = UM()->common()->filesystem()->get_basedir(); break;
                case 'base_url': $value = UM()->common()->filesystem()->get_baseurl(); break;
            }

        } else {

            switch( $function ) {
                case 'base_dir': $value = UM()->uploader()->get_upload_base_dir(); break;
                case 'base_url': $value = UM()->uploader()->get_upload_base_url(); break;
            }
        }

        return $value;
    }

    public function create_setting_structures( $settings_structure ) {

        $prefix = '&nbsp; * &nbsp;';

        $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['title'] = esc_html__( 'User Country Flag', 'ultimate-member' );
        $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['description'] = $this->get_possible_plugin_update( 'shortcode_profile_info' );

        $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['fields'][] = array(
                                'id'             => 'shortcode_profile_info_flag_profile',
                                'type'           => 'checkbox',
                                'label'          => $prefix . esc_html__( 'Display User country flag in Profile after name', 'ultimate-member' ),
                                'checkbox_label' => esc_html__( 'Country name in meta_key "country" and the Flag title from the country name', 'ultimate-member' ),
                            );

        $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['fields'][] = array(
                                'id'             => 'shortcode_profile_info_flag_directory',
                                'type'           => 'checkbox',
                                'label'          => $prefix . esc_html__( 'Display User country flag in Directory Profile card', 'ultimate-member' ),
                                'checkbox_label' => esc_html__( 'Country name in meta_key "country" and the Flag title from the country name', 'ultimate-member' ),
                            );

        return $settings_structure;
    }

}


new UM_Shortcode_Profile_Info();

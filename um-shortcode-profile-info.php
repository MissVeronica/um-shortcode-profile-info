<?php
/**
 * Plugin Name:     Ultimate Member - Shortcode Profile Info
 * Description:     Extension to Ultimate Member for displaying User Profile info at non UM pages and UM profile pages via a shortcode.
 * Version:         2.0.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Plugin URI:      https://github.com/MissVeronica/um-shortcode-profile-info
 * Update URI:      https://github.com/MissVeronica/um-shortcode-profile-info
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.10.5
 * Flags version:   7.5.0
 * Reference flags: https://github.com/lipis/flag-icons
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

class UM_Shortcode_Profile_Info {

    public $flags_version = '7.5.0';
    public $fetch_user = false;
    public $link_text  = '';
    public $title_text = '';
    public $content    = '';
    public $button     = array( '<button>', '</button>');
    public $no_button  = array( '', '');
    public $wp_users_table = array( 'user_login',
                                    'user_nicename',
                                    'user_email',
                                    'user_url',
                                    'display_name',
                                );


    function __construct() {

        define( 'Plugin_Basename_SPI', plugin_basename( __FILE__ ));

        add_shortcode( 'um_profile_info', array( $this, 'um_profile_info_shortcode' ), 10, 2 );

        add_action( 'um_after_profile_name_inline',          array( $this, 'display_country_flag_profile' ), 9, 2 );
        add_action( 'um_members_after_user_name',            array( $this, 'display_country_flag_directory' ), 10, 2 );
        add_action( 'wp_enqueue_scripts',                    array( $this, 'enqueue_flags_library' ));

        if ( version_compare( ultimatemember_version, '2.10.5' ) == -1 ) {
            add_filter( 'um_change_usermeta_for_update',         array( $this, 'profile_edit_strip_shortcodes' ), 10, 4 );
            add_filter( 'um_account_pre_updating_profile_array', array( $this, 'account_edit_strip_shortcodes' ), 10, 1 );
            add_filter( 'um_add_user_frontend_submitted',        array( $this, 'registration_strip_shortcodes' ), 10, 1 );
        }

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

        if ( true !== UM()->fields()->editing ) { 
            if ( isset( $atts['type'] ) && ! empty( $atts['type'] )) {

                $type = sanitize_text_field( $atts['type'] );

                switch( $type ) {
                
                    case 'current_user':    return $this->display_profile_current_user( $atts );

                    default:    $user_id = $this->try_to_find_the_userid( $type, $atts );

                                if ( ! empty( $user_id )) {

                                    if ( str_contains( $content, '[um_profile_info ' )) {

                                        if ( version_compare( get_bloginfo( 'version' ),'5.4', '<' ) ) {
                                            $content = do_shortcode( $content );
                                        } else {
                                            $content = apply_shortcodes( $content );
                                        }
                                    }

                                    $this->content    = $this->convert_tags( $content, $user_id );
                                    $this->link_text  = $this->get_link_text( $user_id, $atts );
                                    $this->title_text = ( ! empty( $this->link_text )) ? $this->link_text : $this->content;

                                    switch( $type ) {

                                        case 'image':           return $this->display_profile_image( $user_id, $atts );
                                        case 'video':           return $this->display_profile_video( $user_id, $atts );
                                        case 'profile_link':    return $this->display_profile_link(  $user_id, $atts );
                                        case 'profile_button':  return $this->display_profile_link(  $user_id, $atts, true );
                                        case 'url':             return $this->display_profile_url(   $user_id, $atts );
                                        case 'url_button':      return $this->display_profile_url(   $user_id, $atts, true );
                                        case 'meta_value':      return $this->display_profile_meta(  $user_id, $atts );
                                        case 'phone':           return $this->display_profile_phone( $user_id, $atts );
                                        case 'phone_button':    return $this->display_profile_phone( $user_id, $atts, true );
                                        case 'email':           return $this->display_profile_email( $user_id, $atts );
                                        case 'email_button':    return $this->display_profile_email( $user_id, $atts, true );
                                        case 'file':            return $this->display_profile_file(  $user_id, $atts );
                                        case 'file_button':     return $this->display_profile_file(  $user_id, $atts, true );
                                        case 'country_flag':    return $this->display_country_flag(  $user_id, $atts );
                                        default:                return '';
                                    }
                                }
                }
            }
        }

        return '';
    }

    public function display_profile_image( $user_id, $atts ) {

        $image_name = $this->get_um_meta_value( $user_id, $atts['meta_key'], true );
        if ( empty( $image_name )) {
            return '';
        }

        $filemtime = filemtime( $this->get_um_filesystem( 'base_dir' ) . $user_id . DIRECTORY_SEPARATOR . $image_name );
        $photo_url = $this->get_um_filesystem( 'base_url' ) . $user_id . "/" . $image_name . '?t=' . $filemtime;
        $width = ( isset( $atts['width'] ) && ! empty( $atts['width'] )) ? sanitize_text_field( $atts['width'] ) : '';

        $modal = '<a href="#" class="um-photo-modal" data-src="' . esc_url( $photo_url ) . '">';

        return $modal . '<img src="' . esc_url( $photo_url ) . '" width="' . esc_attr( $width ) . '" alt="Image" title="' . esc_attr( $this->title_text ) . '"/></a>';
    }

    public function display_profile_video( $user_id, $atts ) {

        $video_name = $this->get_um_meta_value( $user_id, $atts['meta_key'] );
        if ( empty( $video_name )) {
            return '';
        }

        $video_url = $this->get_um_filesystem( 'base_url' ) . $user_id . "/" . $video_name;
        $width = ( isset( $atts['width'] ) && ! empty( $atts['width'] )) ? sanitize_text_field( $atts['width'] ) : '';

        $output  = '<video width="' . esc_attr( $width ) . '" controls="controls" title="' . esc_attr( $this->title_text ) . '">';
        $output .= '<source src="' . esc_url( $video_url ) . '" type="video/mp4">';
        $output .= esc_html__( 'No browser support', 'ultimate-member' );
        $output .= '</video>';

        return $output;
    }

    public function display_profile_link( $user_id, $atts, $button = false ) {

        $user_profile_url = um_user_profile_url( $user_id );
        if ( empty( $user_profile_url )) {
            return '';
        }

        $onclick_alert = $this->alert_external_url_link( $user_profile_url );
        $btn = ( $button === true ) ? $this->button : $this->no_button;

        return $btn[0] . $this->get_field_icon( $atts ) . '<a href="' . esc_url( $user_profile_url ) . '" title="' . esc_attr( $this->title_text ) . '"' . $onclick_alert . '>' . esc_attr( $this->link_text ) . '</a>' . $btn[1];
    }

    public function display_profile_url( $user_id, $atts, $button = false ) {

        $url = $this->get_um_meta_value( $user_id, $atts['meta_key'] );
        if ( empty( $url )) {
            return '';
        }

        $onclick_alert = $this->alert_external_url_link( $url );
        $btn = ( $button === true ) ? $this->button : $this->no_button;

        return $btn[0] . $this->get_field_icon( $atts ) . '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $this->title_text ) . '"' . $onclick_alert . '>' . esc_attr( $this->link_text ) . '</a>' . $btn[1];
    }

    public function display_profile_meta( $user_id, $atts ) {

        $meta_value = $this->get_um_meta_value( $user_id, $atts['meta_key'] );
        if ( empty( $meta_value )) {
            return '';
        }

        $meta_value = maybe_unserialize( $meta_value );

        if ( is_array( $meta_value ) ) {
            $meta_value = implode( ',', $meta_value );
        }

        return '<span title="' . esc_attr( $this->title_text ) . '">' . $this->get_field_icon( $atts ) . wp_kses_post( $meta_value ) . '</span>';
    }

    public function display_profile_phone( $user_id, $atts, $button = false ) {

        $phone_number = $this->get_um_meta_value( $user_id, $atts['meta_key'] );
        if ( empty( $phone_number )) {
            return '';
        }

        $btn = ( $button === true ) ? $this->button : $this->no_button;

        return $btn[0] . $this->get_field_icon( $atts ) . '<a href="' . esc_url( 'tel:' . $phone_number ) . '" rel="nofollow" title="' . esc_attr( $this->title_text ) . '">' . esc_attr( $phone_number ) . '</a>' . $btn[1];
    }

    public function display_profile_email( $user_id, $atts, $button = false ) {

        $email = $this->get_um_meta_value( $user_id, $atts['meta_key'] );
        if ( empty( $email ) || ! is_email( $email )) {
            return '';
        }

        $btn = ( $button === true ) ? $this->button : $this->no_button;

        return $btn[0] . $this->get_field_icon( $atts ) . '<a href="' . esc_url( 'mailto:' . $email ) . '?subject=' . esc_attr( $this->content ) . '" title="' . esc_attr( $this->title_text ) . '">' . esc_attr( $this->link_text ) . '</a>' . $btn[1];
    }

    public function display_profile_file( $user_id, $atts, $button = false ) {

        $file_name = $this->get_um_meta_value( $user_id, $atts['meta_key'], true );
        if ( empty( $file_name )) {
            return '';
        }

        $file_url = $this->get_um_filesystem( 'base_url' ) . $user_id . "/" . $file_name;
        $onclick_alert = $this->alert_external_url_link( $file_url );
        $btn = ( $button === true ) ? $this->button : $this->no_button;

        return $btn[0] . $this->get_field_icon( $atts ) . '<a href="' . esc_url( $file_url ) . '" target="_blank" alt="File" title="' . esc_attr( $this->title_text ) . '"' . $onclick_alert . '>' . esc_attr( $this->link_text ) . '</a>' . $btn[1];
    }

    public function display_profile_current_user( $atts ) {

        global $current_user;

        $content = '';
        if ( isset( $atts['text'] ) && ! empty( $atts['text'] )) {

            $user_id = um_profile_id();

            if ( $current_user->ID != $user_id ) {
                um_fetch_user( $current_user->ID );
                $content = um_convert_tags( $atts['text'] );

                if ( ! empty( $user_id )) {
                    um_fetch_user( $user_id );
                }

            } else {
                $content = um_convert_tags( $atts['text'] );
            }
        }

        return $content;
    }

    public function get_um_meta_value( $user_id, $meta_key, $file = false ) {

        $meta_key = sanitize_key( $meta_key );
        if ( empty( $meta_key )) {
            return '';
        }

        if ( in_array( $meta_key, $this->wp_users_table )) {

            $user_info = get_userdata( $user_id );
            $meta_value = $user_info->$meta_key;

        } else {

            if ( $this->fetch_user === true ) {
                $meta_value = ( $file === false ) ? um_user( $meta_key ) : get_user_meta( $user_id, $meta_key, true );

            } else {

                $meta_value = get_user_meta( $user_id, $meta_key, true );
            }
        }

        if ( empty( $meta_value )) {
            $meta_value = '';
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
        }

        if ( empty( $user_id )) {

            if ( is_singular() ) {
                $user_id = get_post()->post_author;
            } elseif ( is_author() ) {
                $user_id = get_the_author_meta( 'ID' );
            }
        }

        $user_id = absint( $user_id );

        if ( $user_id == um_user( 'ID' )) {
            $this->fetch_user = true;
        }

        return $user_id;
    }

    public function convert_tags( $content, $user_id ) {

        if ( ! empty( $content )) {

            if ( $this->fetch_user === true ) {
                $content = um_convert_tags( $content );

            } else {

                $current_id = um_user( 'ID' );
                um_fetch_user( $user_id );

                $content = um_convert_tags( $content );
                um_fetch_user( $current_id );
            }
        }

        return $content;
    }

    public function get_link_text( $user_id, $atts ) {

        $link_text = $this->content;
        if ( isset( $atts['text_meta_key'] ) && ! empty( $atts['text_meta_key'] )) {

            $link_text_value = $this->get_um_meta_value( $user_id, $atts['text_meta_key'] );

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

	public function display_country_flag( $user_id, $atts = array() ) {

        if ( ! empty( $user_id )) {

            $country_key = sanitize_key( UM()->options()->get( 'shortcode_profile_info_flag_country_key' ));
            if ( empty( $country_key )) {
                $country_key = 'country';
            }

            $meta_key = ( isset( $atts['meta_key'] ) && ! empty( $atts['meta_key'] )) ? sanitize_key( $atts['meta_key'] ) : $country_key;
            $user_country = get_user_meta( $user_id, $meta_key, true );
            $countries    = UM()->builtin()->get( 'countries' );

            if ( ! in_array( $meta_key, array( 'billing_country', 'shipping_country' ))) {
                $country_code = array_search( $user_country, $countries, true );

            } else {
                $country_code = $user_country;
                $user_country = ( isset( $countries[$country_code] )) ? $countries[$country_code] : '';
            }

            if ( ! empty( $country_code ) ) {

                $title = ( isset( $atts['text_meta_key'] ) && $atts['text_meta_key'] != 'country' ) ? $this->link_text : $user_country;
                $font_size = ( isset( $atts['height'] ) && ! empty( $atts['height'] )) ? sanitize_text_field( $atts['height'] ) : '';

                return '<span style="font-size:' . esc_attr( $font_size ) . ';" class="fi fi-' . esc_attr( strtolower( $country_code ) ) . '" title="' . esc_attr( $title ) . '"></span>';
            }
        }

        return '';
	}

	public function enqueue_flags_library() {

        $plugin_data = get_plugin_data( __FILE__ );
        $version = ( isset( $plugin_data['Version'] ) && ! empty( $plugin_data['Version'] )) ? $plugin_data['Version'] : '1.0.0';

        $flag_version = sanitize_text_field( UM()->options()->get( 'shortcode_profile_info_flag_version' ));

        if ( empty( $flag_version )) {
            UM()->options()->update( 'shortcode_profile_info_flag_version', $this->flags_version );
            $flag_version = $this->flags_version;
        }

		wp_register_style( 'country_flags_lib', 'https://cdn.jsdelivr.net/gh/lipis/flag-icons@' . esc_attr( $flag_version ) . '/css/flag-icons.min.css', array(), $version, 'all' );
		wp_enqueue_style(  'country_flags_lib' );
    }

    public function profile_edit_strip_shortcodes( $to_update, $args, $fields, $key ) {

        if ( is_array( $fields ) && isset( $fields[$key] ) && isset( $fields[$key]['type'] ) && in_array( $fields[$key]['type'], array( 'text', 'textarea', 'url', 'tel' ))) {
            if ( is_array( $to_update ) && isset( $to_update[$key] ) && ! empty( $to_update[$key] )) {
                $to_update[$key] = strip_shortcodes( $to_update[$key] );
            }
        }

        return $to_update;
    }

    public function account_edit_strip_shortcodes( $changes ) {

        if ( is_array( $changes ) && ! empty( $changes )) {
            foreach( $changes as $key => $change ) {
                if ( is_string( $change ) && ! empty( $change )) {
                    $changes[$key] = strip_shortcodes( $change );
                }
            }
        }

        return $changes;
    }

    public function registration_strip_shortcodes( $args ) {

        if ( isset( $args['submitted'] )) {
            $args['submitted'] = $this->account_edit_strip_shortcodes( $args['submitted'] );
        }

        $args = $this->account_edit_strip_shortcodes( $args );

        return $args;
    }

    public function get_possible_plugin_update( $plugin ) {

        $plugin_data = get_plugin_data( __FILE__ );

        $documention = sprintf( ' <a href="%s" target="_blank" title="%s">%s</a>',
                                        esc_url( $plugin_data['PluginURI'] ),
                                        esc_html__( 'GitHub plugin documentation and download', 'ultimate-member' ),
                                        esc_html__( 'Documentation', 'ultimate-member' ));

        $description = sprintf( esc_html__( 'Plugin "Shortcode Profile Info" version %s - Tested with UM 2.10.5 - %s', 'ultimate-member' ),
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

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'um_options' ) {
            if ( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'appearance' ) {

                $prefix = '&nbsp; * &nbsp;';

                $flag_version = UM()->options()->get( 'shortcode_profile_info_flag_version' );
                if ( empty( $flag_version )) {
                    UM()->options()->update( 'shortcode_profile_info_flag_version', $this->flags_version );
                }

                $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['title'] = esc_html__( 'User Country Flag', 'ultimate-member' );
                $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['description'] = $this->get_possible_plugin_update( 'shortcode_profile_info' );

                $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['fields'][] = array(
                                        'id'              => 'shortcode_profile_info_flag_version',
                                        'type'            => 'text',
                                        'size'            => 'short',
                                        'label'           => $prefix . esc_html__( 'Lipis Flag Icons Library version update', 'ultimate-member' ),
                                        'description'     => esc_html__( 'Specify the version of the Lipis Flag Icons Library to be loaded by the shortcode.', 'ultimate-member' ) . '<br />' .
                                                             sprintf( esc_html__( 'Default Lipis Flag Icons Library version for this plugin release is "%s".', 'ultimate-member' ), $this->flags_version ) .
                                                             '<br />' . esc_html__( 'You can update the setting anytime when a newer library version is available by Lipis.', 'ultimate-member' ) .
                                                             '<br />Lipis Flag Icons Library: <a href="https://github.com/lipis/flag-icons/releases" title="Release history list" target="_blank">Releases</a>,' .
                                                            ' <a href="https://flagicons.lipis.dev/" title="All available Flag Icons" target="_blank">Demo</a>'
                                    );

                $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['fields'][] = array(
                                        'id'              => 'shortcode_profile_info_flag_country_key',
                                        'type'            => 'text',
                                        'size'            => 'short',
                                        'label'           => $prefix . esc_html__( 'Meta_key name for the User Country flag', 'ultimate-member' ),
                                        'description'     => esc_html__( 'Default meta_key name is "country" for country flag in Directory Profile card and Profile page header.', 'ultimate-member' ),
                                    );

                $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['fields'][] = array(
                                        'id'             => 'shortcode_profile_info_flag_profile',
                                        'type'           => 'checkbox',
                                        'label'          => $prefix . esc_html__( 'Display User Country flag in Profile page after name', 'ultimate-member' ),
                                        'checkbox_label' => esc_html__( 'Country name from meta_key value above.', 'ultimate-member' ),
                                    );

                $settings_structure['appearance']['sections']['']['form_sections']['shortcode_profile_info']['fields'][] = array(
                                        'id'             => 'shortcode_profile_info_flag_directory',
                                        'type'           => 'checkbox',
                                        'label'          => $prefix . esc_html__( 'Display User Country flag in Directory Profile card', 'ultimate-member' ),
                                        'checkbox_label' => esc_html__( 'Country name from meta_key value above.', 'ultimate-member' ),
                                    );
            }
        }

        return $settings_structure;
    }

}


new UM_Shortcode_Profile_Info();

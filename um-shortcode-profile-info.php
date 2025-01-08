<?php
/**
 * Plugin Name:     Ultimate Member - Shortcode Profile Info
 * Description:     Extension to Ultimate Member for displaying User Profile info at non UM pages via a shortcode.
 * Version:         1.0.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Plugin URI:      https://github.com/MissVeronica/um-shortcode-profile-info
 * Update URI:      https://github.com/MissVeronica/um-shortcode-profile-info
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.9.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
if ( ! class_exists( 'UM' ) ) return;

class UM_Shortcode_Profile_info {

    function __construct() {

        add_shortcode( 'um_profile_info', array( $this, 'um_profile_info_shortcode' ), 10, 2 );
    }

    public function um_profile_info_shortcode( $atts, $content = '' ) {

        if ( isset( $atts['type'] ) || ! empty( $atts['type'] )) {

            $type = sanitize_text_field( $atts['type'] );

            $user_id = apply_filters( 'um_profile_info_shortcode', false, $type );

            if ( empty( $user_id ) ) {
                $user_id = um_profile_id();
                if ( empty( $user_id ) && ! isset( $atts['user_id'] )) {
                    return '';
                } else {
                    $user_id = absint( $atts['user_id'] );
                }
            }

            switch( $type ) {

                case 'image':           return $this->display_profile_image( $user_id, $atts, $content );
                case 'video':           return $this->display_profile_video( $user_id, $atts, $content );
                case 'profile_link':    return $this->display_profile_link( $user_id, $atts, $content );
                case 'url':             return $this->display_profile_url( $user_id, $atts, $content );
                case 'meta_value':      return $this->display_profile_meta_value( $user_id, $atts );
                default:                return '';
            }
        }

        return '';
    }

    public function display_profile_image( $user_id, $atts, $content ) {

        $image_name = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $image_name )) {
            return '';
        }

        $photo_url = UM()->uploader()->get_upload_base_url() . $user_id . "/" . $image_name;
        $width = ( isset( $atts['width'] ) && ! empty( $atts['width'] )) ? sanitize_text_field( $atts['width']  ) : '';

        return '<img src="' . esc_url( $photo_url ) . '" width="' . esc_attr( $width ) . '" alt="Image" title="' . esc_attr( $content ) . '"/>';
    }

    public function display_profile_video( $user_id, $atts, $content ) {

        $video_name = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $video_name )) {
            return '';
        }

        $video_url = UM()->uploader()->get_upload_base_url() . $user_id . "/" . $video_name;
        $width = ( isset( $atts['width'] ) && ! empty( $atts['width'] )) ? sanitize_text_field( $atts['width']  ) : '';

        $output  = '<video width="' . esc_attr( $width ) . '" controls autoplay>';
        $output .= '<source src="' . esc_url( $video_url ) . '" type="video/mp4">';
        $output .= '</video>';

        return $output;
    }

    public function display_profile_link( $user_id, $atts, $content ) {

        $user_profile_url = um_user_profile_url( $user_id );
        if ( empty( $user_profile_url )) {
            return '';
        }

        return '<a href="' . esc_url( $user_profile_url ) . '" title="' . esc_attr( $content ) . '">' . esc_attr( $content ) . '</a>';
    }

    public function display_profile_url( $user_id, $atts, $content ) {

        $url = $this->get_um_meta_value( $user_id, $atts );
        if ( empty( $url )) {
            return '';
        }

        return '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $content ) . '">' . esc_attr( $content ) . '</a>';
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

        return $meta_value;
    }

    public function get_um_meta_value( $user_id, $atts ) {

        $meta_key = ( isset( $atts['meta_key'] ) && ! empty( $atts['meta_key'] )) ? sanitize_text_field( $atts['meta_key']  ) : '';
        if ( empty( $meta_key )) {
            return '';
        }

        $meta_value = get_user_meta( $user_id, $meta_key, true );

        return $meta_value;
    }
}


new UM_Shortcode_Profile_info();



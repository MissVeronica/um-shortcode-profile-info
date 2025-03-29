# UM Shortcode Profile Info - Version 1.4.0 with country flags
Extension to Ultimate Member for displaying User Profile info incl country flags at non UM pages and UM profile pages via a shortcode.

## UM Settings -> Appearance -> Profile -> "User Country Flag"
1. *  Display User country flag in Profile after name - Country name in meta_key "country" and the Flag title from the country name
2. *  Display User country flag in Directory Profile card - Country name in meta_key "country" and the Flag title from the country name

## Shortcode um_profile_info
### Main types
#### image
[um_profile_info type="image" meta_key="key_name" width="300" user_id="" text_meta_key="key_name"]title text[/um_profile_info]  Example: meta_key="profile_photo"
#### video
[um_profile_info type="video" meta_key="key_name" width="300" user_id=""]title text[/um_profile_info]
#### profile_link
[um_profile_info type="profile_link" user_id="" text_meta_key="key_name" icon=""]title text[/um_profile_info]
#### url
[um_profile_info type="url" meta_key="key_name" user_id="" text_meta_key="key_name" icon=""]title text[/um_profile_info]
#### meta_value
[um_profile_info type="meta_value" meta_key="key_name" user_id="" icon=""]
#### phone
[um_profile_info type="phone" meta_key="mobile_number" user_id="" text_meta_key="user_login" icon="fas fa-phone"]title text[/um_profile_info]
#### email
[um_profile_info type="email" meta_key="user_email" user_id="" text_meta_key="user_login" icon="fas fa-at"]
#### file
[um_profile_info type="file" meta_key="um_pdf_submitted" user_id="" text_meta_key="user_login" icon="fas fa-file-pdf"]title text[/um_profile_info]

Example: https://imgur.com/a/tBCYTjb
#### country_flag
[um_profile_info type="country_flag" meta_key="country" user_id="" height="75px"  text_meta_key="country"]title text[/um_profile_info]

### User ID
Override the user_id in the shortcode with the filter 'um_profile_info_shortcode' and remove user_id or leave empty in the shortcode. The filter code snippet is installed by adding to your active Theme's functions.php file or use the "Code Snippets" plugin.
#### User ID priority
1. the filter 'um_profile_info_shortcode'
2. shortcode user_id="1234" with a value
3. current profile page to display
4. current post author
#### code example user_id filter
<code>add_filter( 'um_profile_info_shortcode', 'um_profile_info_shortcode_userid', 10, 3 );
function um_profile_info_shortcode_userid( $user_id, $type, $atts ) {
    // find current user ID value for use by the plugin. $atts an array with the shortcode parameters.
    return $user_id;
}</code>
###  text_meta_key="key_name"
Title text for type="image". Link text for  type="profile_link", type="url", type="phone", type="email", type="file". Fallback in all cases the content text ie "title text" in the shortcode examples
### icon
icons available at Font Awesome: https://docs.fontawesome.com/web/add-icons/how-to

## References
1. "Code Snippets" Plugin: https://wordpress.org/plugins/code-snippets/
2. Flags from https://github.com/lipis/flag-icons

## Updates
1. Version 1.1.0 Addition of: text_meta_key="key_name". Fix for user_id in type="profile_link". WP Users table fields included in meta_key selections.
2. Version 1.2.0 Addition of $atts in the filter. Fix for the filter function.
3. Version 1.3.0 Addition of type="phone", type="email", type="file", icon parameter, user ID selections and priorities
4. Version 1.4.0 Addition of country flags either as shortcode or Profile meta field in header.

## Installation & Updates
Download the zip file via the green Code button and install or update as a new WP Plugin to upload, activate the plugin.

# UM Shortcode Profile Info
Extension to Ultimate Member for displaying User Profile info at non UM pages via a shortcode
## UM Settings
None
## Shortcode um_profile_info
### Main types
#### image
[um_profile_info type="image" meta_key="key_name" width="300" user_id="1234"]title text[/um_profile_info]  Example: meta_key="profile_photo"
#### video
[um_profile_info type="video" meta_key="key_name" width="300" user_id="1234"]title text[/um_profile_info]
#### profile_link
[um_profile_info type="profile_link" user_id="1234"]title text[/um_profile_info]
#### url
[um_profile_info type="url" meta_key="key_name" user_id="1234"]title text[/um_profile_info]
#### meta_value
[um_profile_info type="meta_value" meta_key="key_name" user_id="1234"]
### User ID
If you can't add the user_id in the shortcode use the filter 'um_profile_info_shortcode' and remove user_id or leave empty in the shortcode. The filter code snippet is installed by adding to your active Theme's functions.php file or use the "Code Snippets" plugin.
#### code example user_id filter
<code>add_filter( 'um_profile_info_shortcode', 'um_profile_info_shortcode_userid', 10, 2 );
function um_profile_info_shortcode_userid( $user_id, $type ) {
    // find current user ID value for use in the plugin
    return $user_id;
}</code>
## Updates
None
## Installation & Updates
Download the zip file via the green Code button and install or update as a new WP Plugin to upload, activate the plugin.

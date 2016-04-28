<?php

/*
Plugin Name: reCaptcha for bbPress Messages
Plugin URI: http://bbpress-messages.samelh.com
Description: enable reCaptcha for bbPress Messages - User Private Messages with notifications, widgets and media with no BuddyPress needed.
Author: Samuel Elh
Version: 0.1.1
Author URI: http://samelh.com
*/

// Prevent direct access
defined('ABSPATH') || exit;


class bbpm_rc
{

	public function init() {

		add_action('admin_menu', function() {
			add_options_page( 'bbPress Messages &rsaquo; reCaptcha', 'bbPM reCaptcha', 'manage_options', 'bbpm-recaptcha', array(&$this, 'bbpm_rc_admin' ) );
		});

		add_action('wp_footer', function() {
			if( ! $this->enable_for_user() )
				return;
			?>
				<script src='<?php echo apply_filters('bbpmrc_api_script_url', 'https://www.google.com/recaptcha/api.js'); ?>'></script>
			<?php
		});

		add_filter('bbpmrc_api_script_url', function($url) {
			return $url . '?hl=' . $this->settings()->lang;
		});

		add_action('bbpm_conversation_form_additional_fields', function() {

			if( ! $this->enable_for_user() )
				return; // not enabled for this user

			?> <div class="g-recaptcha" data-sitekey="<?php echo $this->settings()->pubKey; ?>"></div> <?php

		});

		add_filter('bbpm_bail_sending_message', function( $bail ) {

			if( ! $this->enable_for_user() )
				return $bail; // not enabled for this user

			// grab recaptcha library
			require_once "recaptchalib.php";

			// your secret key
			$secret = $this->settings()->secKey;
			 
			// empty response
			$response = null;
			 
			// check secret key
			$reCaptcha = new ReCaptcha($secret);

			if ( isset( $_POST["g-recaptcha-response"] ) ) {
			    $response = $reCaptcha->verifyResponse(
			        $_SERVER["REMOTE_ADDR"],
			        $_POST["g-recaptcha-response"]
			    );
			}

			if( null != $response && $response->success ) {
				// has passed the captcha
				do_action('bbpmrc_user_pass_test');
				return $bail;
			} else {
				// failed captcha
				do_action('bbpmrc_user_fail_test');
				return true;
			}

			return $bail;

		});

		add_action('bbpmrc_user_pass_test', function() {

			$count = (int) get_user_meta( wp_get_current_user()->ID, 'bbpmrc_success_count', TRUE );
			$count += 1;
			update_user_meta( wp_get_current_user()->ID, 'bbpmrc_success_count', $count );

		});

		add_filter( "plugin_action_links_".plugin_basename(__FILE__), function($links) {
		    array_push( $links, '<a href="options-general.php?page=bbpm-recaptcha">' . __( 'Settings' ) . '</a>' );
		  	return $links;
		});

	}

	public function bbpmrc_roles_list() {

		$roles = array();

		//foreach ( get_editable_roles() as $name => $role )
		//	$roles[$name] = $role['name'];
			
		foreach ( bbp_get_dynamic_roles() as $name => $role )
			$roles[$name] = $role['name'];


		return array_filter( array_unique( $roles ) );

	}


	public function bbpm_rc_admin() {


		?>

			<div style=" margin: 6px 6px 0 0; float: right; max-width: 30%;"><?php if( ! in_array( 'bbpress-messages/index.php', get_option('active_plugins') ) ):?><div style=" border: 1px solid #ddd; padding: 1px 12px; background: #F9F9F9; box-shadow: 0 1px 8px #CCCBCB; border-color: #B6C4CA;"><h3 style=" color: #555;">Get bbPress Message PRO now!</h3><p>Unlock more features on your bbPress forums..</p><p><a href="http://go.samelh.com/get/bbpress-messages/" class="button button-primary">More information</a></p></div><?php endif; ?><div style=" border: 1px solid #ddd; padding: 1px 12px; background: #F9F9F9; margin-top: 4px;"><h3 style=" color: #555;">Check out more of our WordPress plugins</h3><li><a target="_blank" href="http://go.samelh.com/get/wpchats/">WpChats</a> bringing instant live chat &amp; private messaging feature to your site..</li><li><a target="_blank" href="http://go.samelh.com/get/bbpress-ultimate/">bbPress Ultimate</a> adds more features to your forums and bbPress/BuddyPress profiles..</li><li><a target="_blank" href="http://go.samelh.com/get/bbpress-thread-prefixes/">bbPress Thread Prefixes</a> enables thread prefixes in your blog, just like any other forum board!</li><p>View more of our <a target="_blank" href="https://profiles.wordpress.org/elhardoum#content-plugins">free</a> and <a target="_blank" href="http://codecanyon.net/user/samiel/portfolio?ref=samiel">premium</a> plugins.</p></div><div style=" border: 1px solid #ddd; padding: 1px 12px; background: #F9F9F9; margin-top: 4px;"><h3 style=" color: #555;">Sign up for the newsletter</h3><p>Subscribe to our mailing list for more WordPress/bbPress/BuddyPress plugins and ideas.</p><p><a href="http://go.samelh.com/newsletter" class="button button-primary">Subscribe</a></p></div><div style=" border: 1px solid #ddd; padding: 1px 12px; background: #F9F9F9; margin-top: 4px;"><h3 style=" color: #555;">Find support</h3><p>Please refer to where you got this plugin from for support. If downloaded from Github, open a new Github issue. If you got it from WordPress.org, start a new thread in the plugin's dedicated support forum from there. Feel free to also post in our support forums at support.samelh.com :)</p></div></div>

		<?php

		$this->update();

		?>

			<h2>bbPress Messages &raquo; reCaptcha</h2>

			<form method="post">

				<p>Before you setup this plugin, make sure to go to <a href="https://www.google.com/recaptcha" target="_blank">Google reCaptcha</a> website and register your site. After that, insert both public and secret captcha keys in the following fields. A <a href="https://www.google.com/search?q=how+to+get+google+recaptcha" target="_blank">tutorial</a> might also help.</p>

				<p>
					<label>
						<strong>Enable</strong><br/>
						<label><input type="checkbox" name="enable" <?php checked($this->settings()->enable); ?>/>Check this to enable reCaptcha now</label>
					</label>
				</p>

				<p>
					<label>
						<strong>reCaptcha public key</strong><br/>
						<input type="text" name="key[pub]" size="40" value="<?php echo $this->settings()->pubKey; ?>" />
					</label>
				</p>

				<p>
					<label>
						<strong>reCaptcha secret key</strong><br/>
						<input type="text" name="key[sec]" size="40" value="<?php echo $this->settings()->secKey; ?>" />
					</label>
				</p>

				<p>
					<strong>Enable reCaptcha for the following roles:</strong><br/>
					<?php foreach( $this->bbpmrc_roles_list() as $tag => $name ) : ?>
						<label>
							<input type="checkbox" name="roles[<?php echo $tag; ?>]" <?php checked( in_array($tag, $this->settings()->roles) ); ?> />
							<?php echo $name; ?> <em>(<?php echo $tag; ?>)</em>
						</label><br/>
					<?php endforeach; ?>
				</p>

				<p>
					<strong>reCaptcha language:</strong><br/>
					<select name="lang">
						<?php foreach( $this->settings()->langs as $locale => $lang ) : ?>
							<option value="<?php echo $locale; ?>" <?php echo $locale == $this->settings()->lang ? 'selected="selected"' : ''; ?>><?php echo $lang; ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p>
					<strong>Disabling after X successful fill-outs</strong><br/>
					<label>Disable reCaptcha for a user after <input type="number" min="-1" name="dis[after]" value="<?php echo $this->settings()->disAf; ?>" /> success requests.</label>
				</p>

				<p>
					<strong>Disable for custom users</strong><br/>
					<label>If you wish to disable reCaptcha for specific user(s), enter their associative user ID, below, separated by commas:<br/><textarea name="dis[ids]" cols="50"><?php echo implode( ', ', $this->settings()->disFor ); ?></textarea>
					</label>
				</p>

				<?php wp_nonce_field('bbpmrc_nonce', 'bbpmrc_nonce'); ?>
				<?php submit_button(); ?>

			</form>

		<?php

	}

	public function update() {

		if( ! isset( $_POST['submit'] ) )
			return;

		if( ! isset( $_POST['bbpmrc_nonce'] ) || ! wp_verify_nonce( $_POST['bbpmrc_nonce'], 'bbpmrc_nonce' ) ) {
			echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
			return;
		}

		$settings = array();
		$settings['enable'] = isset( $_POST['enable'] );
		$settings['pubKey'] = isset( $_POST['key']['pub'] ) && $_POST['key']['pub'] > '' ? $_POST['key']['pub'] : false;
		$settings['secKey'] = isset( $_POST['key']['sec'] ) && $_POST['key']['sec'] > '' ? $_POST['key']['sec'] : false;
		$_roles = isset( $_POST['roles'] ) && is_array( $_POST['roles'] ) ? $_POST['roles'] : array();
		$settings['roles'] = array();
		foreach( $_roles as $tag => $role ) { $settings['roles'][] = $tag; }
		$settings['roles'] = array_filter( array_unique( $settings['roles'] ) );
		$settings['disAf'] = isset( $_POST['dis']['after'] ) && (int) $_POST['dis']['after'] > 0 ? (int) $_POST['dis']['after'] : 0;
		$_disFor = isset( $_POST['dis']['ids'] ) ? explode( ',', $_POST['dis']['ids'] ) : array();
		$settings['disFor'] = array();
		foreach( $_disFor as $uid ) { if( (int) $uid > 0 ) { $settings['disFor'][] = (int) $uid; } }
		$settings['lang'] = isset( $_POST['lang'] ) ? (string) $_POST['lang'] : false;

		update_option('bbpmrc_settings', esc_attr( json_encode( $settings ) ));

		echo '<div id="updated" class="updated notice is-dismissible"><p>Settings saved successfully.</p></div>';

	}

	public function settings() {

		$meta = get_option('bbpmrc_settings');

		$ob = $meta > '' ? json_decode( stripslashes( html_entity_decode($meta) ) ) : new stdClass();

		if( isset( $ob->enable ) && $ob->enable )
			$ob->enable = true;
		else
			$ob->enable = false;

		if( ! ( isset( $ob->pubKey ) && $ob->pubKey  > '' ) ) {
			$ob->pubKey = false;
			$ob->enable = false;
		}

		if( ! ( isset( $ob->secKey ) && $ob->secKey  > '' ) ) {
			$ob->secKey = false;
			$ob->enable = false;
		}

		if( ! ( isset( $ob->roles ) && is_array( $ob->roles ) ) )
			$ob->roles = array();

		if( ! ( isset( $ob->disAf ) && (int) $ob->disAf  > 0 ) )
			$ob->disAf = false;

		if( ! ( isset( $ob->disFor ) && is_array( $ob->disFor ) ) )
			$ob->disFor = array();

		$ob->langs = array( "ar" => "Arabic","af" => "Afrikaans","am" => "Amharic","hy" => "Armenian","az" => "Azerbaijani","eu" => "Basque","bn" => "Bengali","bg" => "Bulgarian","ca" => "Catalan","zh-HK" => "Chinese (Hong Kong)","zh-CN" => "Chinese (Simplified)","zh-TW" => "Chinese (Traditional)","hr" => "Croatian","cs" => "Czech","da" => "Danish","nl" => "Dutch","en-GB" => "English (UK)","en" => "English (US)","et" => "Estonian","fil" => "Filipino","fi" => "Finnish","fr" => "French","fr-CA" => "French (Canadian)","gl" => "Galician","ka" => "Georgian","de" => "German","de-AT" => "German (Austria)","de-CH" => "German (Switzerland)","el" => "Greek","gu" => "Gujarati","iw" => "Hebrew","hi" => "Hindi","hu" => "Hungarain","is" => "Icelandic","id" => "Indonesian","it" => "Italian","ja" => "Japanese","kn" => "Kannada","ko" => "Korean","lo" => "Laothian","lv" => "Latvian","lt" => "Lithuanian","ms" => "Malay","ml" => "Malayalam","mr" => "Marathi","mn" => "Mongolian","no" => "Norwegian","fa" => "Persian","pl" => "Polish","pt" => "Portuguese","pt-BR" => "Portuguese (Brazil)","pt-PT" => "Portuguese (Portugal)","ro" => "Romanian","ru" => "Russian","sr" => "Serbian","si" => "Sinhalese","sk" => "Slovak","sl" => "Slovenian","es" => "Spanish","es-419" => "Spanish (Latin America)","sw" => "Swahili","sv" => "Swedish","ta" => "Tamil","te" => "Telugu","th" => "Thai","tr" => "Turkish","uk" => "Ukrainian","ur" => "Urdu","vi" => "Vietnamese","zu" => "Zulu" ); // didn't write all of this long list, JS helped extract it from https://developers.google.com/recaptcha/docs/language

		if( ! ( isset( $ob->lang ) && isset( $ob->langs[$ob->lang] ) ) )
			$ob->lang = 'en';

		return $ob;

	}

	public function enable_for_user( $user_id = 0 ) {

		if( ! $user_id && is_user_logged_in() )
			$user_id = wp_get_current_user()->ID;

		$data = get_userdata( $user_id );

		if( ! $data )
			return; // null

		if( ! $this->settings()->enable || empty( $this->settings()->roles ) )
			return false;

		if( in_array( $user_id, $this->settings()->disFor ) ) { return false; }

		if( $this->settings()->disAf ) {
			$count = (int) get_user_meta( $user_id, 'bbpmrc_success_count', TRUE );
			if( $count > (int) $this->settings()->disAf ) { return false; } // user reached success limit
		}

		foreach( $data->roles as $role ) {
			if( in_array( $role, $this->settings()->roles ) ) { return true; }
		}

		return false;

	}

}

$bbpm_rc = new bbpm_rc;
$bbpm_rc->init();

/**
  * Resources:
  * - http://webdesign.tutsplus.com/tutorials/how-to-integrate-no-captcha-recaptcha-in-your-website--cms-23024
  * - https://developers.google.com/recaptcha/docs/language
  */

<?php
/*
Plugin Name: nimble Squirrel
Plugin URI: http://www.nimblesquirrel.com
Description: Get full insight into the behavior of your visitors by displaying a mini survey on your website.
Version: 1.0.0
Author: NimbleSquirrel
Contributors: NickDuncan, Jarryd Long, CodeCabin_
Author URI: http://www.nimblesquirrel.com
Text Domain: nimble-squirrel
Domain Path: /languages
*/

class NimbleSquirrel{

	function __construct(){		

		add_action('admin_menu', array( $this, 'admin_menu' ) );
		add_action('admin_head', array( $this, 'admin_head') );
		add_action('wp_enqueue_scripts', array( $this, 'load_user_script' ) );
	}	
 
	function admin_menu() {
	    add_submenu_page(
	        'options-general.php',
	        __('Nimble Squirrel Settings', 'nimble-squirrel'),
	        __('Nimble Squirrel', 'nimble-squirrel'),
	        'manage_options',
	        'nimble-squirrel',
	        array( $this, 'admin_menu_contents' ) );
	}

	function admin_menu_contents(){

		$domain = $this->domain_linked();
		
		if( $domain == 'false' ){

			?>
				<div class='wrap'>
					<h2><?php _e('Create a Nimble Squirrel Account', 'nimble-squirrel'); ?></h2>
					<p>
						<?php
							_e( "Create an account and have a survey added to your website instantly. It's FREE!", "nimble-squirrel" );
						?>
					</p>
					<p>
					<?php
						$account_2 = "<a href='http://app.nimblesquirrel.com/' target='_BLANK'>".__('My Account', 'nimblesquirrel')."</a>";
						$website_url = get_option('siteurl');
						echo sprintf ( __('If you already have an account, please ensure the domain %s is linked in the %s page', 'nimble-squirrel'), $website_url, $account_2 ); 
					?>
					<form method="POST">
						<table>
							<tr>
								<th><?php _e('Email Address', 'nimble-squirrel'); ?>:</th>
								<td><input type='email' name='email' style='width: 300px;' value="<?php echo get_option("admin_email"); ?>" /></td>
							</tr>
							<tr>
								<th><?php _e('Password', 'nimble-squirrel'); ?>:</th>
								<td><input type='password' name='password' style='width: 300px;' /></td>
							</tr>
							<tr>
								<th></th>
								<td><input type='submit' class='button button-primary' name='create_nimble_user' value='<?php _e('Create Account', 'nimble-squirrel'); ?>' </td>
							</tr>
						</table>
					</form>
				</div>
			<?php

		} else {
			$settings = get_option('NIMBLE_SQUIRREL_SETTINGS');

			?>
				<div class='wrap'>
					<h2><?php _e( 'Nimble Squirrel Settings', 'nimble-squirrel' ); ?></h2>
					<p>
						<?php
							$account = "<a href='http://app.nimblesquirrel.com/' target='_BLANK'>".__('account', 'nimblesquirrel')."</a>";
							echo sprintf(__('To make changes to your survey and view responses, please login to your %s on Nimble Squirrel', 'nimble-squirrel'), $account);
						?>
					</p>
					<form method="POST">
						<table class='form-table'>
							<tr>
								<th><?php _e('Enable Nimble Squirrel', 'nimble-squirrel'); ?>:</th>
								<td><input type='checkbox' name='nimble_enable' value='1' <?php if( isset( $settings['enabled'] ) && $settings['enabled'] == '1' ){ echo 'checked=checked'; } ?> /></td>
							</tr>
							<tr>
								<th><?php _e('Chosen Survey', 'nimble-squirrel'); ?>:</th>
								<td>
									<?php $nimble_user_id = ''; ?>							
									<select name='nimble_survey'>
										<?php 
											$surveys = $this->return_linked_surveys();
											if( $surveys ){
												$surveys = json_decode( $surveys );
												if( $surveys ){
													echo "<option value='0'>".__('Select a survey', 'nimble-squirrel')."</option>";												
													foreach( $surveys as $survey ){
														$nimble_user_id = $survey->uid;
														if( isset( $settings['survey'] ) && $settings['survey'] == $survey->id ){ $sel = 'selected'; } else { $sel = ''; }
														echo "<option value='".$survey->id."' $sel>".$survey->name."</option>";
													}
												} else {
													echo "<option value='0'>".__('No Surveys Created', 'nimble-squirrel')."</option>";	
												}
											} else {
												echo "<option value='0'>".__('No Surveys Created', 'nimble-squirrel')."</option>";
											}
										?>
									</select>	
									<p><?php _e('Please login to your Nimble Squirrel account to create, edit and delete surveys', 'nimble-squirrel'); ?></p>
									<input type='hidden' name='nimble_user_id' value='<?php echo $nimble_user_id; ?>' />							
								</td>
							</tr>
							<tr>
								<th></th>
								<td><input type='submit' class='button button-primary' name='save_nimble_settings' value='<?php _e('Save Settings', 'nimble-squirrel'); ?>' </td>
							</tr>
						</table>
					</form>
				</div>
			<?php

		}

	}

	function admin_head(){

		if( isset( $_POST['create_nimble_user'] ) ){

			$nimble_api = 'http://nimblesquirrel.com/api/wordpress.php';

			$response = wp_remote_post( $nimble_api, 
				array(
					'method' => 'POST',
					'body' => array( 
						'wordpress_plugin' => 1,
						'action' => 'create_account',
						'email' => $_POST['email'],
						'password' => $_POST['password'],
						'url' => get_option('siteurl')
					)
			    )
			);
			
			if ( is_wp_error( $response ) ) {
			   $error_message = $response->get_error_message();
			   echo $error_message;
			} else {			
			   return $response['body'];
			}

		}

		if( isset( $_POST['save_nimble_settings'] ) ){
			$settings = array();
			if(isset($_POST['nimble_enable']) && $_POST['nimble_enable'] == '1' ){
				$settings['enabled'] = 1;
			}
			if(isset($_POST['nimble_survey'])){
				$settings['survey'] = sanitize_text_field($_POST['nimble_survey']);
			}
			if(isset($_POST['nimble_user_id'])){
				$settings['user'] = sanitize_text_field($_POST['nimble_user_id']);	
			}
			update_option("NIMBLE_SQUIRREL_SETTINGS", $settings);
			echo "<div class='updated'><p>".__('Settings updated successfully', 'nimble-squirrel')."</p></div>";
		}
	}

	function load_user_script(){

		$settings = get_option('NIMBLE_SQUIRREL_SETTINGS');

		if( isset( $settings['enabled'] ) && $settings['enabled'] == 1 ){

			if( isset( $settings['user'] ) ){ $ns_id = $settings['user']; } else { $ns_id = ''; }
			if( isset( $settings['survey'] ) ){ $ns_sid = $settings['survey']; } else { $ns_sid = ''; }

			wp_enqueue_script( 'nimble-squirrel-user-script', '//nimblesquirrel.com/api/nimblesquirrel.js', array(), '1.0.0', true );
			wp_localize_script( 'nimble-squirrel-user-script', 'ns_id', $ns_id );
			wp_localize_script( 'nimble-squirrel-user-script', 'ns_sid', $ns_sid );

		}

	}

	function domain_linked(){

		$nimble_api = 'http://nimblesquirrel.com/api/wordpress.php';

		$response = wp_remote_post( $nimble_api, 
			array(
				'method' => 'POST',
				'body' => array( 
					'wordpress_plugin' => 1,
					'action' => 'validate_domain',
					'siteurl' => get_option('siteurl')
				)
		    )
		);

		if ( is_wp_error( $response ) ) {
		   $error_message = $response->get_error_message();
		   echo $error_message;
		} else {
		   return $response['body'];
		}

	}

	function return_linked_surveys(){

		$nimble_api = 'http://nimblesquirrel.com/api/wordpress.php';

		$response = wp_remote_post( $nimble_api, 
			array(
				'method' => 'POST',
				'body' => array( 
					'wordpress_plugin' => 1,
					'action' => 'return_surveys',
					'siteurl' => get_option('siteurl')
				)
		    )
		);

		if ( is_wp_error( $response ) ) {

		   $error_message = $response->get_error_message();
		   echo $error_message;
		} else {
		   return $response['body'];
		}

	}

}

new NimbleSquirrel();
<?php

class ITSEC_Settings_Admin {

	private
		$core,
		$settings;

	function run( $core ) {

		$this->settings    = true;
		$this->core        = $core;
		$this->module_path = ITSEC_Lib::get_module_path( __FILE__ );

		add_action( 'itsec_add_admin_meta_boxes', array( $this, 'itsec_add_admin_meta_boxes' ) ); //add meta boxes to admin page
		add_action( 'itsec_admin_init', array( $this, 'itsec_admin_init' ) ); //initialize admin area

	}

	/**
	 * Export all plugin settings and push to user.
	 *
	 * @since 4.5
	 *
	 * @return mixed file or false
	 */
	private function export_settings() {

		global $wpdb, $itsec_globals;

		$ignored_settings = array( //Array of settings that should not be exported
		                           'itsec_local_file_list',
		                           'itsec_jquery_version',
		                           'itsec_initials',
		                           'itsec_data',
		);

		$raw_items = $wpdb->get_results( "SELECT * FROM `" . $wpdb->options . "` WHERE `option_name` LIKE 'itsec%';", ARRAY_A );

		$clean_items = array();

		//Loop through raw options to make sure serialized data is output as a JSON array (don't want to have to unserialize anything from the user later).
		foreach ( $raw_items as $item ) {

			if ( ! in_array( $item['option_name'], $ignored_settings ) ) {

				$clean_items[] = array(
					'name'  => $item['option_name'],
					'value' => maybe_unserialize( $item['option_value'] ),
					'auto'  => ( $item['autoload'] === 'yes' ? true : false ),
				);

			}

		}

		$content = json_encode( $clean_items ); //encode the PHP array of settings

		$settings_file = '/itsec_options.json';
		$zip_file      = '/itsec_options.zip';

		if ( ! @file_put_contents( $itsec_globals['ithemes_dir'] . $settings_file, $content, LOCK_EX ) ) {

			$type    = 'error';
			$message = __( 'We could not create the backup file. If the problem persists contact support', 'it-l10n-ithemes-security-pro' );

			add_settings_error( 'itsec', esc_attr( 'settings_updated' ), $message, $type );

			return;

		}

		//Attempt to zip the saved file
		if ( ! class_exists( 'PclZip' ) ) {
			require( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
		}

		@chdir( $itsec_globals['ithemes_dir'] );
		$zip = new PclZip( './' . $zip_file );

		if ( $zip->create( './' . $settings_file ) == 0 ) {

			$type    = 'error';
			$message = __( 'We could not create the backup file. If the problem persists contact support', 'it-l10n-ithemes-security-pro' );

			add_settings_error( 'itsec', esc_attr( 'settings_updated' ), $message, $type );

		}

		@unlink( './' . $settings_file ); //Delete the original

		//Send the settings to the given user and then delete the file
		$user = trim( $_POST['email_address'] );

		if ( is_email( $user ) !== false ) {

			$attachment = array( './' . $zip_file );
			$body       = __( 'Attached is the settings file for ', 'it-l10n-ithemes-security-pro' ) . ' ' . get_option( 'siteurl' ) . __( ' created at', 'it-l10n-ithemes-security-pro' ) . ' ' . date( 'l, F jS, Y \a\\t g:i a', $itsec_globals['current_time'] );

			//Setup the remainder of the email
			$subject = __( 'Security Settings File', 'it-l10n-ithemes-security-pro' ) . ' ' . date( 'l, F jS, Y \a\\t g:i a', $itsec_globals['current_time'] );
			$subject = apply_filters( 'itsec_backup_email_subject', $subject );
			$headers = 'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>' . "\r\n";

			//Use HTML Content type
			add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

			if ( defined( 'ITSEC_DEBUG' ) && ITSEC_DEBUG === true ) {
				$body .= '<p>' . __( 'Debug info (source page): ' . esc_url( $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ) ) . '</p>';
			}

			$mail_success = wp_mail( $user, $subject, '<html>' . $body . '</html>', $headers, $attachment );

			//Remove HTML Content type
			remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

			if ( $mail_success === false ) {

				$type    = 'error';
				$message = __( 'We could not send the email. You will need to retrieve the backup file manually.', 'it-l10n-ithemes-security-pro' );

				add_settings_error( 'itsec', esc_attr( 'settings_updated' ), $message, $type );

			} else {

				@unlink( './' . $zip_file );

			}

		}

	}

	/**
	 * Import settings provided by user.
	 *
	 * @since 4.5
	 *
	 * @return void
	 */
	private function import_settings() {

		global $itsec_globals;

		check_admin_referer( 'ITSEC_admin_save', 'wp_nonce' );

		//make sure we have a file to process
		if ( ! isset( $_FILES['settings_file'] ) ) {
			die( 'error' );
		}

		add_filter( 'upload_mimes', array( $this, 'upload_mimes' ) );

		//Make sure the file name and type are correct
		if ( ! isset( $_FILES['settings_file']['type'] ) || ( $_FILES['settings_file']['type'] !== 'application/zip' && $_FILES['settings_file']['type'] !== 'application/json' ) || ! isset( $_FILES['settings_file']['name'] ) || ( $_FILES['settings_file']['name'] !== 'itsec_options.zip' && $_FILES['settings_file']['name'] !== 'itsec_options.json' ) || ! isset( $_FILES['settings_file']['error'] ) || $_FILES['settings_file']['error'] !== 0 ) {

			$type    = 'error';
			$message = __( 'The settings import file does not appear to be valid. Please try again and contact support if the problem persists.', 'it-l10n-ithemes-security-pro' );

			add_settings_error( 'itsec', esc_attr( 'settings_updated' ), $message, $type );

			return;

		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		//Make sure the file goes to the iThemes Directory
		add_filter( 'upload_dir', array( $this, 'my_upload_dir' ) );

		$file = wp_handle_upload( $_FILES['settings_file'], array( 'test_form' => false ) ); //Handle the file upload

		add_filter( 'upload_mimes', array( $this, 'remove_upload_mimes' ) );

		remove_filter( 'upload_dir', array( $this, 'my_upload_dir' ) ); //Reset the upload directory

		if ( isset( $file['type'] ) && $file['type'] === 'application/zip' ) { //process the file if it is a zip

			global $wp_filesystem;

			//Get filesystem or this will fail.
			if ( ! $wp_filesystem || ! is_object( $wp_filesystem ) ) {
				WP_Filesystem();
			}

			//Unzip the file and delete the original
			unzip_file( $file['file'], $itsec_globals['ithemes_dir'] );
			@unlink( $file['file'] );

		}

		//Get the file contents and delete the file
		$contents = @file_get_contents( $itsec_globals['ithemes_dir'] . '/itsec_options.json' );
		@unlink( $itsec_globals['ithemes_dir'] . '/itsec_options.json' );

		if ( $contents !== false ) {

			$contents_array = json_decode( $contents, true );

		} else {

			$type    = 'error';
			$message = __( 'The settings import file does not appear to be valid. Please try again and contact support if the problem persists.', 'it-l10n-ithemes-security-pro' );

			add_settings_error( 'itsec', esc_attr( 'settings_updated' ), $message, $type );

			return;

		}

		foreach ( $contents_array as $item ) {

			delete_site_option( $item['name'] );

			if ( is_multisite() ) {

				add_site_option( $item['name'], $item['value'] );

			} else {

				add_option( $item['name'], $item['value'], null, $item['auto'] );

			}

		}

	}

	/**
	 * Execute admin initializations
	 *
	 * @return void
	 */
	public function itsec_admin_init() {

		if ( $this->settings === true && isset( $_POST['itsec_import_settings'] ) && $_POST['itsec_import_settings'] === 'itsec_import_settings' ) {

			if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'ITSEC_admin_save' ) ) {

				die( __( 'Security check', 'it-l10n-ithemes-security-pro' ) );

			}

			$this->import_settings();

		}

		if ( $this->settings === true && isset( $_POST['itsec_export_settings'] ) && $_POST['itsec_export_settings'] === 'itsec_export_settings' ) {

			if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'ITSEC_admin_save' ) ) {

				die( __( 'Security check', 'it-l10n-ithemes-security-pro' ) );

			}

			$this->export_settings();

		}

	}

	/**
	 * Add meta boxes to primary options pages
	 *
	 * @return void
	 */
	public function itsec_add_admin_meta_boxes() {

		$id    = 'settings_options';
		$title = __( 'Settings Import and Export', 'it-l10n-ithemes-security-pro' );

		add_meta_box(
			$id,
			$title,
			array( $this, 'metabox_settings' ),
			'security_page_toplevel_page_itsec_advanced',
			'advanced',
			'core'
		);

	}

	/**
	 * Render the settings metabox
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function metabox_settings() {

		global $itsec_globals;

		echo '<p>' . __( 'Have more than one site? Want to just backup your settings for later?', 'it-l10n-ithemes-security-pro' ) . '</p>';
		echo '<p>' . __( 'Use the buttons below to import and export your iThemes Security settings ', 'it-l10n-ithemes-security-pro' ) . '</p>';
		echo '<p>' . __( 'Please note that if you are migrating a site to a different server you will have to update any path settings such as logs or backup files after the import.', 'it-l10n-ithemes-security-pro' ) . '</p>';

		$user = wp_get_current_user();

		?>

		<form method="post" action="?page=toplevel_page_itsec_advanced&settings-updated=true" class="itsec-form">

			<?php wp_nonce_field( 'ITSEC_admin_save', 'wp_nonce' ); ?>

			<input type="hidden" name="itsec_export_settings" value="itsec_export_settings">

			<table class="form-table">
				<tr valign="top" id="settings_import_field">
					<th scope="row" class="settinglabel">
						<label for="itsec_settings_input"><?php _e( 'Email Address', 'it-l10n-ithemes-security-pro' ); ?></label>
					</th>
					<td class="settingfield">
						<?php //username field ?>
						<input id="itsec_settings_input" name="email_address" type="text"
						       value="<?php echo $user->user_email; ?>" required/>

						<p class="description"><?php echo __( 'Enter the email address to send the file to. It will also be saved to', 'it-l10n-ithemes-security-pro' ) . '<strong>' . $itsec_globals['ithemes_dir'] . '</strong> ' . __( 'which must be accessed manually (you cannot access the file via your web browser for security reasons).', 'it-l10n-ithemes-security-pro' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Export Settings', 'it-l10n-ithemes-security-pro' ); ?>"/>
			</p>
		</form>

		<hr/>

		<form method="post" enctype="multipart/form-data"
		      action="?page=toplevel_page_itsec_advanced&settings-updated=true" class="itsec-form">

			<?php wp_nonce_field( 'ITSEC_admin_save', 'wp_nonce' ); ?>

			<input type="hidden" name="itsec_import_settings" value="itsec_import_settings">

			<table class="form-table">
				<tr valign="top" id="settings_import_field">
					<th scope="row" class="settinglabel">
						<label for="itsec_settings_input"><?php _e( 'Select Settings File', 'it-l10n-ithemes-security-pro' ); ?></label>
					</th>
					<td class="settingfield">
						<?php //username field ?>
						<input id="itsec_settings_input" name="settings_file" type="file" value="" required/>

						<p class="description"><?php _e( 'Select a settings file for import.', 'it-l10n-ithemes-security-pro' ); ?></p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Import Settings', 'it-l10n-ithemes-security-pro' ); ?>"/>
			</p>
		</form>

	<?php

	}

	/**
	 * Sets WordPress upload directory to the iThemes Directory
	 *
	 * @since 4.5
	 *
	 * @param array $upload upload directory
	 *
	 * @return array upload directory
	 */
	public function my_upload_dir( $upload ) {

		$upload['subdir'] = '/ithemes-security';
		$upload['path']   = $upload['basedir'] . $upload['subdir'];
		$upload['url']    = $upload['baseurl'] . $upload['subdir'];

		return $upload;

	}

	/**
	 * Remove support for uploading .json files.
	 *
	 * @since 1.13
	 *
	 * @param array $mimes array of mime types
	 *
	 * @return array array of mime types
	 */
	public function remove_upload_mimes( $mimes ) {

		unset( $mimes['json'] );

		return $mimes;

	}

	/**
	 * Set HTML content type for email
	 *
	 * @return string html content type
	 */
	public function set_html_content_type() {

		return 'text/html';

	}

	/**
	 * Add support for uploading .json files.
	 *
	 * @since 1.13
	 *
	 * @param array $mimes array of mime types
	 *
	 * @return array array of mime types
	 */
	public function upload_mimes( $mimes ) {

		return array_merge( $mimes, array(
			'json' => 'text/json',
			'zip' => 'application/zip',
		) );
	}

}
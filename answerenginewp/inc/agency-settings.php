<?php
/**
 * Agency Branding Settings
 *
 * Admin settings page for white-label agency branding on prospect reports.
 * Settings > Agency Branding.
 *
 * @package AnswerEngineWP
 */

/**
 * Register the settings page.
 */
function aewp_agency_settings_menu() {
	add_options_page(
		'Agency Branding',
		'Agency Branding',
		'manage_options',
		'aewp-agency',
		'aewp_agency_settings_page'
	);
}
add_action( 'admin_menu', 'aewp_agency_settings_menu' );

/**
 * Register settings.
 */
function aewp_agency_settings_init() {
	register_setting( 'aewp_agency', 'aewp_agency_name', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'aewp_agency', 'aewp_agency_logo', array( 'sanitize_callback' => 'absint' ) );
	register_setting( 'aewp_agency', 'aewp_agency_url', array( 'sanitize_callback' => 'esc_url_raw' ) );
	register_setting( 'aewp_agency', 'aewp_agency_email', array( 'sanitize_callback' => 'sanitize_email' ) );

	add_settings_section( 'aewp_agency_main', 'Agency Branding', '__return_false', 'aewp-agency' );

	add_settings_field( 'aewp_agency_name', 'Agency Name', 'aewp_field_agency_name', 'aewp-agency', 'aewp_agency_main' );
	add_settings_field( 'aewp_agency_logo', 'Agency Logo', 'aewp_field_agency_logo', 'aewp-agency', 'aewp_agency_main' );
	add_settings_field( 'aewp_agency_url', 'Agency Website', 'aewp_field_agency_url', 'aewp-agency', 'aewp_agency_main' );
	add_settings_field( 'aewp_agency_email', 'Contact Email', 'aewp_field_agency_email', 'aewp-agency', 'aewp_agency_main' );
}
add_action( 'admin_init', 'aewp_agency_settings_init' );

/* ── Field renderers ────────────────────────────────────── */

function aewp_field_agency_name() {
	$val = get_option( 'aewp_agency_name', '' );
	echo '<input type="text" name="aewp_agency_name" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="Acme Digital Agency">';
	echo '<p class="description">Shown on prospect reports: "Prepared by {Agency Name}"</p>';
}

function aewp_field_agency_logo() {
	$id  = absint( get_option( 'aewp_agency_logo', 0 ) );
	$url = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
	?>
	<div id="aewp-logo-preview" style="margin-bottom:8px;">
		<?php if ( $url ) : ?>
			<img src="<?php echo esc_url( $url ); ?>" style="max-width:200px;height:auto;">
		<?php endif; ?>
	</div>
	<input type="hidden" name="aewp_agency_logo" id="aewp_agency_logo" value="<?php echo esc_attr( $id ); ?>">
	<button type="button" class="button" id="aewp-upload-logo">Select Logo</button>
	<?php if ( $id ) : ?>
		<button type="button" class="button" id="aewp-remove-logo">Remove</button>
	<?php endif; ?>
	<p class="description">Recommended: transparent PNG, at least 400px wide.</p>
	<?php
}

function aewp_field_agency_url() {
	$val = get_option( 'aewp_agency_url', '' );
	echo '<input type="url" name="aewp_agency_url" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="https://youragency.com">';
}

function aewp_field_agency_email() {
	$val = get_option( 'aewp_agency_email', '' );
	echo '<input type="email" name="aewp_agency_email" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="hello@youragency.com">';
}

/* ── Settings page ──────────────────────────────────────── */

function aewp_agency_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Agency Branding</h1>
		<p>Configure your agency details. These appear on prospect audit reports and PDFs sent to clients.</p>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'aewp_agency' );
			do_settings_sections( 'aewp-agency' );
			submit_button( 'Save Agency Settings' );
			?>
		</form>
	</div>
	<script>
	(function($){
		$('#aewp-upload-logo').on('click', function(e){
			e.preventDefault();
			var frame = wp.media({ title: 'Select Agency Logo', multiple: false, library: { type: 'image' } });
			frame.on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				$('#aewp_agency_logo').val(attachment.id);
				$('#aewp-logo-preview').html('<img src="' + attachment.url + '" style="max-width:200px;height:auto;">');
				if (!$('#aewp-remove-logo').length) {
					$('#aewp-upload-logo').after(' <button type="button" class="button" id="aewp-remove-logo">Remove</button>');
					bindRemove();
				}
			});
			frame.open();
		});
		function bindRemove() {
			$(document).on('click', '#aewp-remove-logo', function(e){
				e.preventDefault();
				$('#aewp_agency_logo').val('0');
				$('#aewp-logo-preview').html('');
				$(this).remove();
			});
		}
		bindRemove();
	})(jQuery);
	</script>
	<?php
}

/**
 * Enqueue media uploader on the agency settings page.
 */
function aewp_agency_admin_scripts( $hook ) {
	if ( 'settings_page_aewp-agency' !== $hook ) {
		return;
	}
	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'aewp_agency_admin_scripts' );

/* ── Public getters (used by templates & PDF) ────────────── */

function aewp_get_agency_name() {
	return get_option( 'aewp_agency_name', '' );
}

function aewp_get_agency_logo_url() {
	$id = absint( get_option( 'aewp_agency_logo', 0 ) );
	return $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
}

function aewp_get_agency_url() {
	return get_option( 'aewp_agency_url', '' );
}

function aewp_get_agency_email() {
	return get_option( 'aewp_agency_email', '' );
}

function aewp_has_agency_branding() {
	return ! empty( get_option( 'aewp_agency_name', '' ) );
}

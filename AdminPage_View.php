<div class="wrap">
	<h1>Wow Search</h1>

	<?php echo $message_errors ?>
	<?php echo $message_saved ?>

	<h2>Status</h2>
	<form method="post" novalidate="novalidate">
		<input type="hidden" name="wow_search_settings_action"
			value="index_build" />
		<?php wp_nonce_field( 'wow-search' ); ?>

		<?php include __DIR__ . DIRECTORY_SEPARATOR . 'AdminPage_View_Build.php' ?>
	</form>

	<h2>Settings</h2>
	<form method="post" novalidate="novalidate">
		<input type="hidden" name="wow_search_settings_action"
			value="settings_save" />
		<?php wp_nonce_field( 'wow-search' ); ?>

		<table class="form-table">
			<?php include __DIR__ . DIRECTORY_SEPARATOR . 'AdminPage_View_Settings.php' ?>
		</table>

		<p class="submit">
			<input type="submit" name="submit"
				class="button button-primary" value="Save Changes" />
		</p>
	</form>
</div>
<p>
	Need help with your website?
	<a href="https://wowpress.host/professional-services/" target="_blank">Reach us</a>
</p>

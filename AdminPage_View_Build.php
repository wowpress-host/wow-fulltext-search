<?php

namespace WowFullTextSearch;

?>
<div id="wowfts_status">
	<table class="form-table">
		<tr>
			<th>Status:</th>
			<td style="color: <?php echo esc_attr( $status_color ) ?>">
				<?php echo htmlspecialchars( $status_text ) ?>
				<?php if ( !empty( $status_continue_url ) ): ?>
					<a href="<?php echo esc_url( $status_continue_url ) ?>">Continue</a>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<span id="wow_search_build_start_outer">
	<button	class="button button-primary wow_search_build_start">
		<?php echo $build_start_button_text ?>
	</button>
</span>
<span id="wow_search_build_restart_outer" style="display: none">
	<button	class="button button-primary wow_search_build_start">
		Restart Index Building
	</button>
</span>
<span id="wow_search_build_continue_outer" style="display: none">
	<button	class="button button-primary" id="wow_search_build_continue">
		Continue Index Building
	</button>
</span>

<div id="wow_search_build_process" <?php echo $build_style ?>>
	<table class="form-table">
		<tr>
			<th>Posts found:</th>
			<td id="wow_search_total">
				<?php echo htmlspecialchars( $build_total ) ?>
			</td>
		</tr>
		<tr>
			<th>Posts processed:</th>
			<td id="wow_search_processed">
				<?php echo htmlspecialchars( $build_processed ) ?>
			</td>
		</tr>

		<tr <?php echo $build_errors_style ?>>
			<th>Errors:</th>
			<td id="wow_search_errors">
				<?php echo htmlspecialchars( $build_errors ) ?>
			</td>
		</tr>

		<tr id="wow_search_working_now" <?php echo $build_working_now_style ?>>
			<th>Now processing:</th>
			<td id="wow_search_now"></td>
		</tr>
		<tr id="wow_search_done" style="display: none">
			<th></th>
			<td><strong>Finished</strong> <a href="?page=wow-search-page">View status</a></td>
		</tr>
		<tr id="wow_search_failed" style="display: none">
			<th></th>
			<td><strong>Failed</strong></td>
		</tr>
	</table>

	<h2>Messages</h2>
	<div id="wow_search_notices"></div>
</div>

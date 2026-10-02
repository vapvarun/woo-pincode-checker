<?php
/**
 * Import / Export tab.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Admin\DownloadHandler;
use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Import runs through REST + Action Scheduler (admin.js); export is a plain signed form.
 */
final class ImportTab {

	/**
	 * Render.
	 */
	public function render(): void {
		$importer = Plugin::importer();
		$job      = $importer->current();
		$public   = $job ? $importer->public_job( $job ) : null;

		Wbcom_Settings_Page::card_open(
			__( 'Import areas from CSV', 'woo-pincode-checker' ),
			__( 'Upload a spreadsheet saved as CSV. Large files are imported in the background, so you can leave this page while it runs.', 'woo-pincode-checker' )
		);
		?>
		<div class="wbpc-import" data-wbpc-import data-job="<?php echo esc_attr( (string) wp_json_encode( $public ) ); ?>">
			<form class="wbpc-import__upload" data-wbpc-upload>
				<p class="wbpc-field">
					<label for="wbpc-import-file"><?php esc_html_e( 'CSV file (max 10 MB)', 'woo-pincode-checker' ); ?></label>
					<input type="file" id="wbpc-import-file" name="file" accept=".csv,text/csv" required>
				</p>

				<fieldset class="wbpc-field">
					<legend><?php esc_html_e( 'Areas that already exist', 'woo-pincode-checker' ); ?></legend>
					<label><input type="radio" name="mode" value="skip" checked> <?php esc_html_e( 'Keep them as they are (add new areas only)', 'woo-pincode-checker' ); ?></label>
					<label><input type="radio" name="mode" value="update"> <?php esc_html_e( 'Update them with the values in the file (only the columns your file has)', 'woo-pincode-checker' ); ?></label>
					<label><input type="radio" name="mode" value="replace"> <?php esc_html_e( 'Delete all current areas first, then import the file', 'woo-pincode-checker' ); ?></label>
				</fieldset>

				<p class="wbpc-field">
					<label for="wbpc-import-country"><?php esc_html_e( 'Country for rows without one', 'woo-pincode-checker' ); ?></label>
					<select id="wbpc-import-country" name="country" class="wbcom-select">
						<option value=""><?php esc_html_e( 'Any country', 'woo-pincode-checker' ); ?></option>
						<?php foreach ( WC()->countries->get_countries() as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, WC()->countries->get_base_country() ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="wbpc-form-error" data-wbpc-import-error role="alert" hidden></p>
				<p><button type="submit" class="button wbcom-btn wbcom-btn--primary"><i data-lucide="upload"></i><?php esc_html_e( 'Upload and preview', 'woo-pincode-checker' ); ?></button></p>
			</form>

			<div class="wbpc-import__preview" data-wbpc-preview hidden>
				<p data-wbpc-preview-summary></p>
				<div class="wbpc-table-wrap">
					<table class="wbpc-table">
						<caption class="screen-reader-text"><?php esc_html_e( 'First rows of the file', 'woo-pincode-checker' ); ?></caption>
						<thead><tr>
							<th scope="col"><?php esc_html_e( 'Line', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Code', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'City', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php echo esc_html_x( 'Check', 'import preview column: row validation result', 'woo-pincode-checker' ); ?></th>
						</tr></thead>
						<tbody data-wbpc-preview-rows></tbody>
					</table>
				</div>
				<p class="wbpc-confirm__typed" data-wbpc-replace-confirm hidden>
					<label for="wbpc-replace-input"><?php esc_html_e( 'This deletes every current area first. Type REPLACE to confirm.', 'woo-pincode-checker' ); ?></label>
					<input type="text" id="wbpc-replace-input" class="wbcom-input" autocomplete="off">
				</p>
				<p class="wbpc-area-form__actions">
					<button type="button" class="button wbcom-btn wbcom-btn--primary" data-wbpc-start><i data-lucide="play"></i><?php esc_html_e( 'Start import', 'woo-pincode-checker' ); ?></button>
					<button type="button" class="button wbcom-btn" data-wbpc-import-cancel><?php esc_html_e( 'Cancel', 'woo-pincode-checker' ); ?></button>
				</p>
			</div>

			<div class="wbpc-import__progress" data-wbpc-progress hidden>
				<label for="wbpc-import-bar" data-wbpc-progress-label></label>
				<progress id="wbpc-import-bar" max="100" value="0"></progress>
				<p class="description"><?php esc_html_e( 'You can leave this page. The import keeps running and its result appears here and on the Overview.', 'woo-pincode-checker' ); ?></p>
				<p><button type="button" class="button wbcom-btn" data-wbpc-import-cancel><?php esc_html_e( 'Cancel import', 'woo-pincode-checker' ); ?></button></p>
			</div>

			<div class="wbpc-import__result" data-wbpc-result role="status" aria-live="polite" hidden>
				<p data-wbpc-result-text></p>
				<p>
					<a class="button wbcom-btn" data-wbpc-errors hidden href="#"><i data-lucide="file-warning"></i><?php esc_html_e( 'Download rows that failed', 'woo-pincode-checker' ); ?></a>
					<button type="button" class="button wbcom-btn" data-wbpc-import-again><?php esc_html_e( 'Import another file', 'woo-pincode-checker' ); ?></button>
				</p>
			</div>
		</div>

		<details class="wbpc-format">
			<summary><?php esc_html_e( 'File format', 'woo-pincode-checker' ); ?></summary>
			<p><?php esc_html_e( 'The first row must name the columns. Only "code" is required; leave other cells empty to use store defaults. If the same code appears twice, the last row wins in update mode.', 'woo-pincode-checker' ); ?></p>
			<ul>
				<li><code>code</code> - <?php esc_html_e( 'Postcode. End with * for a prefix (110*, SW1*). Also read as pincode, postcode or zip.', 'woo-pincode-checker' ); ?></li>
				<li><code>code_to</code> - <?php esc_html_e( 'End of a numeric range (code 110001, code_to 110099).', 'woo-pincode-checker' ); ?></li>
				<li><code>country</code> - <?php esc_html_e( 'Two-letter country code such as IN, GB or US.', 'woo-pincode-checker' ); ?></li>
				<li><code>status</code> - <?php esc_html_e( 'serviceable or blocked.', 'woo-pincode-checker' ); ?></li>
				<li><code>city</code>, <code>state</code>, <code>note</code></li>
				<li><code>days_min</code>, <code>days_max</code> - <?php esc_html_e( 'Delivery days.', 'woo-pincode-checker' ); ?></li>
				<li><code>shipping_fee</code>, <code>cod_allowed</code> (yes/no), <code>cod_fee</code></li>
			</ul>
			<p><a href="<?php echo esc_url( DownloadHandler::url( 'wbpc_sample_csv' ) ); ?>"><?php esc_html_e( 'Download a sample file', 'woo-pincode-checker' ); ?></a></p>
		</details>
		<?php
		Wbcom_Settings_Page::card_close();

		$this->export_card();
		$this->history_card( $importer->history() );
	}

	/**
	 * Export: a signed GET form, works without JavaScript.
	 */
	private function export_card(): void {
		Wbcom_Settings_Page::card_open(
			__( 'Export areas', 'woo-pincode-checker' ),
			__( 'Download your areas as CSV - for backups, or to edit in a spreadsheet and import again in "update" mode.', 'woo-pincode-checker' )
		);
		?>
		<form class="wbpc-toolbar wbpc-toolbar--export" method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wbpc_export">
			<?php wp_nonce_field( 'wbpc_export', '_wpnonce', false ); ?>
			<label class="screen-reader-text" for="wbpc-export-country"><?php esc_html_e( 'Country', 'woo-pincode-checker' ); ?></label>
			<select id="wbpc-export-country" name="country" class="wbcom-select">
				<option value=""><?php esc_html_e( 'All countries', 'woo-pincode-checker' ); ?></option>
				<option value="any"><?php esc_html_e( 'Any country', 'woo-pincode-checker' ); ?></option>
				<?php foreach ( WC()->countries->get_countries() as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<label class="screen-reader-text" for="wbpc-export-type"><?php esc_html_e( 'Type', 'woo-pincode-checker' ); ?></label>
			<select id="wbpc-export-type" name="type" class="wbcom-select">
				<option value=""><?php esc_html_e( 'All types', 'woo-pincode-checker' ); ?></option>
				<option value="exact"><?php echo esc_html_x( 'Exact', 'postcode area type', 'woo-pincode-checker' ); ?></option>
				<option value="prefix"><?php echo esc_html_x( 'Prefix', 'postcode area type', 'woo-pincode-checker' ); ?></option>
				<option value="range"><?php echo esc_html_x( 'Range', 'postcode area type', 'woo-pincode-checker' ); ?></option>
			</select>
			<label class="screen-reader-text" for="wbpc-export-status"><?php esc_html_e( 'Status', 'woo-pincode-checker' ); ?></label>
			<select id="wbpc-export-status" name="status" class="wbcom-select">
				<option value=""><?php esc_html_e( 'All statuses', 'woo-pincode-checker' ); ?></option>
				<option value="serviceable"><?php echo esc_html_x( 'Serviceable', 'area status', 'woo-pincode-checker' ); ?></option>
				<option value="blocked"><?php echo esc_html_x( 'Blocked', 'area status', 'woo-pincode-checker' ); ?></option>
			</select>
			<button type="submit" class="button wbcom-btn"><i data-lucide="download"></i><?php esc_html_e( 'Download CSV', 'woo-pincode-checker' ); ?></button>
		</form>
		<?php
		Wbcom_Settings_Page::card_close();
	}

	/**
	 * Last 10 imports.
	 *
	 * @param array $history Jobs, newest first.
	 */
	private function history_card( array $history ): void {
		if ( ! $history ) {
			return;
		}

		Wbcom_Settings_Page::card_open( __( 'Recent imports', 'woo-pincode-checker' ) );
		?>
		<div class="wbpc-table-wrap">
			<table class="wbpc-table">
				<caption class="screen-reader-text"><?php esc_html_e( 'Recent imports', 'woo-pincode-checker' ); ?></caption>
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'File', 'woo-pincode-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'When', 'woo-pincode-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Result', 'woo-pincode-checker' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Rows', 'woo-pincode-checker' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Error report', 'woo-pincode-checker' ); ?></span></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $history as $job ) : ?>
						<tr>
							<td data-label="<?php esc_attr_e( 'File', 'woo-pincode-checker' ); ?>"><?php echo esc_html( $job['name'] ); ?></td>
							<td data-label="<?php esc_attr_e( 'When', 'woo-pincode-checker' ); ?>"><?php echo esc_html( $job['started'] ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $job['started'] ) : '-' ); ?></td>
							<td data-label="<?php esc_attr_e( 'Result', 'woo-pincode-checker' ); ?>">
								<span class="wbcom-badge wbcom-badge--<?php echo esc_attr( 'done' === $job['status'] ? 'success' : ( 'failed' === $job['status'] ? 'danger' : 'muted' ) ); ?>"><?php echo esc_html( $this->status_label( $job['status'] ) ); ?></span>
							</td>
							<td data-label="<?php esc_attr_e( 'Rows', 'woo-pincode-checker' ); ?>">
								<?php
								/* translators: 1: added, 2: updated, 3: skipped, 4: failed. */
								echo esc_html( sprintf( __( '%1$s added, %2$s updated, %3$s skipped, %4$s failed', 'woo-pincode-checker' ), number_format_i18n( (int) $job['added'] ), number_format_i18n( (int) $job['updated'] ), number_format_i18n( (int) $job['skipped'] ), number_format_i18n( (int) $job['failed'] ) ) );
								?>
							</td>
							<td class="wbpc-col-actions">
								<?php if ( ! empty( $job['error_file'] ) ) : ?>
									<a href="<?php echo esc_url( DownloadHandler::url( 'wbpc_import_errors', array( 'job' => $job['id'] ) ) ); ?>"><?php esc_html_e( 'Failed rows', 'woo-pincode-checker' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		Wbcom_Settings_Page::card_close();
	}

	/**
	 * Human job status.
	 *
	 * @param string $status Status.
	 */
	private function status_label( string $status ): string {
		return array(
			'done'      => __( 'Finished', 'woo-pincode-checker' ),
			'failed'    => __( 'Failed', 'woo-pincode-checker' ),
			'cancelled' => __( 'Cancelled', 'woo-pincode-checker' ),
		)[ $status ] ?? $status;
	}
}

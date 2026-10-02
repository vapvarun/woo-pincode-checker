<?php
/**
 * Category Rules tab: extra delivery days per product category.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom\PincodeChecker\Services\SettingsService;
use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Lists only categories that have a rule, plus one "add" row. A full list of every category
 * would exceed PHP's max_input_vars (1,000 by default) on large catalogs and silently drop values.
 */
final class CategoriesTab {

	/**
	 * Render.
	 */
	public function render(): void {
		$option = SettingsService::option( 'category_days' );
		$rules  = (array) Plugin::settings()->get( 'category_days' );
		$names  = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'id=>name',
			)
		);
		$names  = is_array( $names ) ? $names : array();

		Wbcom_Settings_Page::card_open(
			__( 'Extra delivery days by category', 'woo-pincode-checker' ),
			__( 'Add days to the estimate for products that take longer, such as made-to-order furniture. A product in several categories gets the largest number; a cart gets the largest of its products.', 'woo-pincode-checker' )
		);
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="wbpc-settings-form">
			<?php settings_fields( $option ); ?>

			<?php if ( $rules ) : ?>
				<div class="wbpc-table-wrap">
					<table class="wbpc-table">
						<caption class="screen-reader-text"><?php esc_html_e( 'Category rules', 'woo-pincode-checker' ); ?></caption>
						<thead><tr>
							<th scope="col"><?php esc_html_e( 'Category', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Extra days (0 removes the rule)', 'woo-pincode-checker' ); ?></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $rules as $term_id => $days ) : ?>
								<tr>
									<td data-label="<?php esc_attr_e( 'Category', 'woo-pincode-checker' ); ?>"><label for="wbpc-cat-<?php echo esc_attr( (string) $term_id ); ?>"><?php echo esc_html( $names[ $term_id ] ?? '#' . $term_id ); ?></label></td>
									<td data-label="<?php esc_attr_e( 'Extra days', 'woo-pincode-checker' ); ?>"><input type="number" id="wbpc-cat-<?php echo esc_attr( (string) $term_id ); ?>" name="<?php echo esc_attr( $option . '[' . $term_id . ']' ); ?>" value="<?php echo esc_attr( (string) $days ); ?>" min="0" max="60" class="wbcom-input wbpc-input-days"></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No category rules yet. Every product uses the delivery days of its area.', 'woo-pincode-checker' ); ?></p>
			<?php endif; ?>

			<fieldset class="wbpc-add-rule">
				<legend><?php esc_html_e( 'Add a category', 'woo-pincode-checker' ); ?></legend>
				<label class="screen-reader-text" for="wbpc-cat-new"><?php esc_html_e( 'Category', 'woo-pincode-checker' ); ?></label>
				<select id="wbpc-cat-new" name="<?php echo esc_attr( $option ); ?>[_new_term]" class="wbcom-select">
					<option value=""><?php esc_html_e( 'Choose a category', 'woo-pincode-checker' ); ?></option>
					<?php foreach ( array_diff_key( $names, $rules ) as $term_id => $name ) : ?>
						<option value="<?php echo esc_attr( (string) $term_id ); ?>"><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<label class="screen-reader-text" for="wbpc-cat-new-days"><?php esc_html_e( 'Extra days', 'woo-pincode-checker' ); ?></label>
				<input type="number" id="wbpc-cat-new-days" name="<?php echo esc_attr( $option ); ?>[_new_days]" min="1" max="60" placeholder="<?php esc_attr_e( 'Days', 'woo-pincode-checker' ); ?>" class="wbcom-input wbpc-input-days">
			</fieldset>

			<div class="wbcom-save-bar">
				<?php submit_button( __( 'Save changes', 'woo-pincode-checker' ), 'primary wbcom-btn wbcom-btn--primary', 'submit', false ); ?>
			</div>
		</form>
		<?php
		Wbcom_Settings_Page::card_close();
	}
}

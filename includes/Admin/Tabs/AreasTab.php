<?php
/**
 * Service Areas tab: static shell; assets/admin/js/admin.js loads the data when the tab opens.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin\Tabs;

use Wbcom_Settings_Page;

defined( 'ABSPATH' ) || exit;

/**
 * The shell renders every tab on every load, so nothing here queries areas.
 */
final class AreasTab {

	/**
	 * Render.
	 */
	public function render(): void {
		Wbcom_Settings_Page::card_open(
			__( 'Service areas', 'woo-pincode-checker' ),
			__( 'Postcodes you deliver to or block. Use exact codes (110001), prefixes (SW1* or 110*) or numeric ranges (110001 to 110099).', 'woo-pincode-checker' )
		);
		?>
		<div class="wbpc-areas" data-wbpc-areas>
			<form class="wbpc-toolbar" id="wbpc-filters" role="search" data-wbpc-filters>
				<label class="screen-reader-text" for="wbpc-search"><?php esc_html_e( 'Search areas', 'woo-pincode-checker' ); ?></label>
				<input type="search" id="wbpc-search" name="search" class="wbcom-input" placeholder="<?php esc_attr_e( 'Search code or city', 'woo-pincode-checker' ); ?>">

				<?php
				$this->select(
					'type',
					__( 'Type', 'woo-pincode-checker' ),
					array(
						''       => __( 'All types', 'woo-pincode-checker' ),
						'exact'  => _x( 'Exact', 'postcode area type', 'woo-pincode-checker' ),
						'prefix' => _x( 'Prefix', 'postcode area type', 'woo-pincode-checker' ),
						'range'  => _x( 'Range', 'postcode area type', 'woo-pincode-checker' ),
					)
				);
				$this->select(
					'status',
					__( 'Status', 'woo-pincode-checker' ),
					array(
						''            => __( 'All statuses', 'woo-pincode-checker' ),
						'serviceable' => _x( 'Serviceable', 'area status', 'woo-pincode-checker' ),
						'blocked'     => _x( 'Blocked', 'area status', 'woo-pincode-checker' ),
					)
				);
				$this->select(
					'cod',
					__( 'Cash on delivery', 'woo-pincode-checker' ),
					array(
						''    => __( 'COD: any', 'woo-pincode-checker' ),
						'yes' => __( 'COD allowed', 'woo-pincode-checker' ),
						'no'  => __( 'No COD', 'woo-pincode-checker' ),
					)
				);
				$this->select(
					'country',
					__( 'Country', 'woo-pincode-checker' ),
					array(
						''    => __( 'All countries', 'woo-pincode-checker' ),
						'any' => __( 'Any country', 'woo-pincode-checker' ),
					) + WC()->countries->get_countries()
				);
				?>
			</form>

			<div class="wbpc-listbar">
				<p class="wbpc-status" data-wbpc-status role="status" aria-live="polite"></p>
				<?php
				// Belongs to the filter form through the form attribute; sits here beside the count.
				$this->select(
					'orderby',
					__( 'Sort by', 'woo-pincode-checker' ),
					array(
						'newest' => __( 'Sort: newest', 'woo-pincode-checker' ),
						'code'   => __( 'Sort: code', 'woo-pincode-checker' ),
						'city'   => __( 'Sort: city', 'woo-pincode-checker' ),
						'days'   => __( 'Sort: fastest delivery', 'woo-pincode-checker' ),
					),
					'wbpc-filters'
				);
				?>
				<button type="button" class="button wbcom-btn wbcom-btn--primary" data-wbpc-add><i data-lucide="plus"></i><?php esc_html_e( 'Add area', 'woo-pincode-checker' ); ?></button>
			</div>

			<?php $this->form(); ?>

			<div class="wbpc-confirm" data-wbpc-confirm hidden role="alertdialog" aria-labelledby="wbpc-confirm-text">
				<p id="wbpc-confirm-text" data-wbpc-confirm-text></p>
				<p class="wbpc-confirm__typed" data-wbpc-confirm-typed hidden>
					<label for="wbpc-confirm-input"><?php esc_html_e( 'Type DELETE to confirm', 'woo-pincode-checker' ); ?></label>
					<input type="text" id="wbpc-confirm-input" class="wbcom-input" autocomplete="off">
				</p>
				<button type="button" class="button wbcom-btn wbpc-btn--danger" data-wbpc-confirm-yes><?php esc_html_e( 'Delete', 'woo-pincode-checker' ); ?></button>
				<button type="button" class="button wbcom-btn" data-wbpc-confirm-no><?php esc_html_e( 'Cancel', 'woo-pincode-checker' ); ?></button>
			</div>

			<div class="wbpc-bulkbar" data-wbpc-bulkbar hidden>
				<span data-wbpc-selected></span>
				<button type="button" class="button-link" data-wbpc-select-all hidden></button>
				<button type="button" class="button wbcom-btn wbpc-btn--danger" data-wbpc-bulk-delete><i data-lucide="trash-2"></i><?php esc_html_e( 'Delete selected', 'woo-pincode-checker' ); ?></button>
			</div>

			<div class="wbpc-table-wrap">
				<table class="wbpc-table" data-wbpc-table>
					<caption class="screen-reader-text"><?php esc_html_e( 'Service areas', 'woo-pincode-checker' ); ?></caption>
					<thead>
						<tr>
							<td class="wbpc-col-check" data-label="<?php esc_attr_e( 'Select all on this page', 'woo-pincode-checker' ); ?>"><input type="checkbox" data-wbpc-check-page aria-label="<?php esc_attr_e( 'Select all areas on this page', 'woo-pincode-checker' ); ?>"></td>
							<th scope="col"><?php esc_html_e( 'Code', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Country', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'City / State', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Delivery days', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Shipping', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'COD', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'woo-pincode-checker' ); ?></th>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'woo-pincode-checker' ); ?></span></th>
						</tr>
					</thead>
					<tbody data-wbpc-rows></tbody>
				</table>
			</div>

			<div class="wbcom-empty-state" data-wbpc-empty hidden>
				<i data-lucide="map-pin-off"></i>
				<p class="wbcom-empty-state__title" data-wbpc-empty-title></p>
				<p class="wbcom-empty-state__desc" data-wbpc-empty-desc></p>
			</div>

			<nav class="wbpc-pager" data-wbpc-pager aria-label="<?php esc_attr_e( 'Service areas pages', 'woo-pincode-checker' ); ?>" hidden>
				<button type="button" class="button wbcom-btn" data-wbpc-prev><i data-lucide="chevron-left"></i><?php esc_html_e( 'Previous', 'woo-pincode-checker' ); ?></button>
				<span data-wbpc-page-label></span>
				<button type="button" class="button wbcom-btn" data-wbpc-next><?php esc_html_e( 'Next', 'woo-pincode-checker' ); ?><i data-lucide="chevron-right"></i></button>
				<label class="screen-reader-text" for="wbpc-per-page"><?php esc_html_e( 'Areas per page', 'woo-pincode-checker' ); ?></label>
				<select id="wbpc-per-page" class="wbcom-select" data-wbpc-per-page>
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="100">100</option>
				</select>
			</nav>
		</div>
		<?php
		Wbcom_Settings_Page::card_close();
	}

	/**
	 * Add/edit form. Hidden until "Add area" or "Edit".
	 */
	private function form(): void {
		?>
		<form class="wbpc-area-form" data-wbpc-form hidden novalidate>
			<h3 class="wbpc-area-form__title" data-wbpc-form-title tabindex="-1"></h3>
			<p class="wbpc-form-error" data-wbpc-form-error role="alert" hidden></p>

			<fieldset class="wbpc-field wbpc-field--wide">
				<legend><?php esc_html_e( 'Match', 'woo-pincode-checker' ); ?></legend>
				<label><input type="radio" name="type" value="exact" checked> <?php esc_html_e( 'Exact postcode', 'woo-pincode-checker' ); ?></label>
				<label><input type="radio" name="type" value="prefix"> <?php esc_html_e( 'Starts with', 'woo-pincode-checker' ); ?></label>
				<label><input type="radio" name="type" value="range"> <?php esc_html_e( 'Numeric range', 'woo-pincode-checker' ); ?></label>
			</fieldset>

			<?php
			$this->field(
				'code',
				__( 'Postcode', 'woo-pincode-checker' ),
				'text',
				array(
					'required'     => 'required',
					'maxlength'    => '21',
					'autocomplete' => 'off',
				)
			);
			$this->field(
				'code_to',
				__( 'Range end', 'woo-pincode-checker' ),
				'text',
				array(
					'maxlength'    => '20',
					'autocomplete' => 'off',
					'inputmode'    => 'numeric',
				),
				true
			);
			?>
			<p class="wbpc-field">
				<label for="wbpc-f-country"><?php esc_html_e( 'Country', 'woo-pincode-checker' ); ?></label>
				<select id="wbpc-f-country" name="country" class="wbcom-select" aria-describedby="wbpc-e-country">
					<option value=""><?php esc_html_e( 'Any country', 'woo-pincode-checker' ); ?></option>
					<?php foreach ( WC()->countries->get_countries() as $code => $name ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="wbpc-field-error" id="wbpc-e-country"></span>
			</p>
			<p class="wbpc-field">
				<label for="wbpc-f-status"><?php esc_html_e( 'Status', 'woo-pincode-checker' ); ?></label>
				<select id="wbpc-f-status" name="status" class="wbcom-select">
					<option value="serviceable"><?php esc_html_e( 'We deliver here', 'woo-pincode-checker' ); ?></option>
					<option value="blocked"><?php esc_html_e( 'Blocked (no delivery)', 'woo-pincode-checker' ); ?></option>
				</select>
			</p>
			<?php
			$this->field( 'city', __( 'City', 'woo-pincode-checker' ), 'text', array( 'maxlength' => '100' ) );
			$this->field( 'state', __( 'State / region', 'woo-pincode-checker' ), 'text', array( 'maxlength' => '100' ) );
			$this->field(
				'days_min',
				__( 'Delivery days (min)', 'woo-pincode-checker' ),
				'number',
				array(
					'min'         => '0',
					'max'         => '365',
					'step'        => '1',
					'placeholder' => __( 'Store default', 'woo-pincode-checker' ),
				)
			);
			$this->field(
				'days_max',
				__( 'Delivery days (max)', 'woo-pincode-checker' ),
				'number',
				array(
					'min'         => '0',
					'max'         => '365',
					'step'        => '1',
					'placeholder' => __( 'Same as min', 'woo-pincode-checker' ),
				)
			);
			$this->field(
				'shipping_fee',
				/* translators: %s: currency symbol. */
				sprintf( __( 'Shipping fee (%s)', 'woo-pincode-checker' ), html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ),
				'number',
				array(
					'min'         => '0',
					'step'        => 'any',
					'placeholder' => __( 'No area fee', 'woo-pincode-checker' ),
				)
			);
			?>
			<p class="wbpc-field">
				<span class="wbpc-field__label"><?php esc_html_e( 'Cash on delivery', 'woo-pincode-checker' ); ?></span>
				<label class="wbcom-toggle">
					<input type="checkbox" name="cod_allowed" value="1" checked>
					<span class="wbcom-toggle-slider"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Allow cash on delivery', 'woo-pincode-checker' ); ?></span>
				</label>
			</p>
			<?php
			$this->field(
				'cod_fee',
				/* translators: %s: currency symbol. */
				sprintf( __( 'COD fee (%s)', 'woo-pincode-checker' ), html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ),
				'number',
				array(
					'min'  => '0',
					'step' => 'any',
				)
			);
			$this->field( 'note', __( 'Internal note', 'woo-pincode-checker' ), 'text', array( 'maxlength' => '255' ), false, true );
			?>
			<div class="wbpc-area-form__actions">
				<button type="submit" class="button wbcom-btn wbcom-btn--primary" data-wbpc-save><?php esc_html_e( 'Save area', 'woo-pincode-checker' ); ?></button>
				<button type="button" class="button wbcom-btn" data-wbpc-cancel><?php esc_html_e( 'Cancel', 'woo-pincode-checker' ); ?></button>
			</div>
		</form>
		<?php
	}

	/**
	 * One labelled input with an error slot.
	 *
	 * @param string $name   Field name.
	 * @param string $label  Label.
	 * @param string $type   Input type.
	 * @param array  $attrs  Extra attributes.
	 * @param bool   $hidden Start hidden (range end).
	 * @param bool   $wide   Span the full form width.
	 */
	private function field( string $name, string $label, string $type, array $attrs = array(), bool $hidden = false, bool $wide = false ): void {
		?>
		<p class="wbpc-field<?php echo $wide ? ' wbpc-field--wide' : ''; ?>" data-wbpc-field="<?php echo esc_attr( $name ); ?>"<?php echo $hidden ? ' hidden' : ''; ?>>
			<label for="wbpc-f-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="<?php echo esc_attr( $type ); ?>" id="wbpc-f-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="wbcom-input" aria-describedby="wbpc-e-<?php echo esc_attr( $name ); ?>"
				<?php
				foreach ( $attrs as $attr => $value ) {
					printf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
				}
				?>
			>
			<span class="wbpc-field-error" id="wbpc-e-<?php echo esc_attr( $name ); ?>"></span>
		</p>
		<?php
	}

	/**
	 * A labelled filter select.
	 *
	 * @param string $name    Name.
	 * @param string $label   Visually hidden label.
	 * @param array  $options value => text.
	 * @param string $form    Id of the form it belongs to when placed outside it (form attribute).
	 */
	private function select( string $name, string $label, array $options, string $form = '' ): void {
		?>
		<label class="screen-reader-text" for="wbpc-filter-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
		<select id="wbpc-filter-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="wbcom-select"<?php echo $form ? ' form="' . esc_attr( $form ) . '"' : ''; ?>>
			<?php foreach ( $options as $value => $text ) : ?>
				<option value="<?php echo esc_attr( (string) $value ); ?>"><?php echo esc_html( $text ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
}

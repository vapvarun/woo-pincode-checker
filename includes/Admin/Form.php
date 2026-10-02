<?php
/**
 * Settings form markup in the shell's field layout.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Admin;

use Wbcom\PincodeChecker\Core\Plugin;
use Wbcom\PincodeChecker\Services\SettingsService;

defined( 'ABSPATH' ) || exit;

/**
 * One instance per settings tab; field names are wbpc_{group}[key].
 */
final class Form {

	/**
	 * Current values of the group.
	 *
	 * @var array
	 */
	private array $values;

	/**
	 * Constructor.
	 *
	 * @param string $group Settings group.
	 */
	public function __construct( private string $group ) {
		$this->values = (array) Plugin::settings()->get( $group );
	}

	/**
	 * Open the form (posts to options.php).
	 */
	public function open(): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'options.php' ) ) . '" class="wbpc-settings-form">';
		settings_fields( SettingsService::option( $this->group ) );
	}

	/**
	 * Save bar + close.
	 */
	public function close(): void {
		echo '<div class="wbcom-save-bar">';
		submit_button( __( 'Save changes', 'woo-pincode-checker' ), 'primary wbcom-btn wbcom-btn--primary', 'submit', false );
		echo '</div></form>';
	}

	/**
	 * Toggle switch.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $desc  Help.
	 */
	public function toggle( string $key, string $label, string $desc = '' ): void {
		$control = sprintf(
			'<label class="wbcom-toggle"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s><span class="wbcom-toggle-slider"></span><span class="screen-reader-text">%4$s</span></label>',
			esc_attr( $this->id( $key ) ),
			esc_attr( $this->name( $key ) ),
			checked( ! empty( $this->values[ $key ] ), true, false ),
			esc_html( $label )
		);
		$this->row( $key, $label, $desc, $control, false );
	}

	/**
	 * Select.
	 *
	 * @param string $key     Key.
	 * @param string $label   Label.
	 * @param array  $options value => text.
	 * @param string $desc    Help.
	 */
	public function select( string $key, string $label, array $options, string $desc = '' ): void {
		$html = '';
		foreach ( $options as $value => $text ) {
			$html .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( (string) $value ), selected( (string) $this->values[ $key ], (string) $value, false ), esc_html( $text ) );
		}
		$this->row( $key, $label, $desc, sprintf( '<select id="%s" name="%s" class="wbcom-select">%s</select>', esc_attr( $this->id( $key ) ), esc_attr( $this->name( $key ) ), $html ) );
	}

	/**
	 * Single-line input.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $type  text|number|time|color.
	 * @param string $desc  Help.
	 * @param array  $attrs Extra attributes.
	 */
	public function input( string $key, string $label, string $type = 'text', string $desc = '', array $attrs = array() ): void {
		$extra = '';
		foreach ( $attrs as $attr => $value ) {
			$extra .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( (string) $value ) );
		}
		$this->row(
			$key,
			$label,
			$desc,
			sprintf( '<input type="%s" id="%s" name="%s" value="%s" class="wbcom-input"%s>', esc_attr( $type ), esc_attr( $this->id( $key ) ), esc_attr( $this->name( $key ) ), esc_attr( (string) $this->values[ $key ] ), $extra )
		);
	}

	/**
	 * Textarea (one value per line for list settings).
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param string $desc  Help.
	 */
	public function textarea( string $key, string $label, string $desc = '' ): void {
		$value = is_array( $this->values[ $key ] ) ? implode( "\n", $this->values[ $key ] ) : (string) $this->values[ $key ];
		$this->row( $key, $label, $desc, sprintf( '<textarea id="%s" name="%s" class="wbcom-textarea" rows="5">%s</textarea>', esc_attr( $this->id( $key ) ), esc_attr( $this->name( $key ) ), esc_textarea( $value ) ) );
	}

	/**
	 * Checkbox group, or a multiple select when there are many options.
	 *
	 * @param string $key     Key.
	 * @param string $label   Label.
	 * @param array  $options value => text.
	 * @param string $desc    Help.
	 */
	public function choices( string $key, string $label, array $options, string $desc = '' ): void {
		$current = array_map( 'strval', (array) $this->values[ $key ] );

		if ( count( $options ) > 12 ) {
			$html = '';
			foreach ( $options as $value => $text ) {
				$html .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( (string) $value ), selected( in_array( (string) $value, $current, true ), true, false ), esc_html( $text ) );
			}
			$control = sprintf( '<select id="%s" name="%s[]" class="wbcom-select wbpc-multi" multiple size="8">%s</select>', esc_attr( $this->id( $key ) ), esc_attr( $this->name( $key ) ), $html );
		} else {
			$control = '<fieldset class="wbpc-choices"><legend class="screen-reader-text">' . esc_html( $label ) . '</legend>';
			foreach ( $options as $value => $text ) {
				$control .= sprintf( '<label><input type="checkbox" name="%s[]" value="%s" %s> %s</label>', esc_attr( $this->name( $key ) ), esc_attr( (string) $value ), checked( in_array( (string) $value, $current, true ), true, false ), esc_html( $text ) );
			}
			$control .= '</fieldset>';
		}

		$this->row( $key, $label, $desc, $control, count( $options ) > 12 );
	}

	/**
	 * Shell field row.
	 *
	 * @param string $key       Key.
	 * @param string $label     Label.
	 * @param string $desc      Help.
	 * @param string $control   Escaped control HTML.
	 * @param bool   $label_for Whether the label points at a single control.
	 */
	private function row( string $key, string $label, string $desc, string $control, bool $label_for = true ): void {
		?>
		<div class="wbcom-field wbcom-field-group">
			<div class="wbcom-field-info">
				<?php if ( $label_for ) : ?>
					<label for="<?php echo esc_attr( $this->id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
				<?php else : ?>
					<span class="wbpc-field__label"><?php echo esc_html( $label ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $desc ) : ?>
					<p class="description"><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>
			</div>
			<div class="wbcom-field-control"><?php echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped parts above. ?></div>
		</div>
		<?php
	}

	/**
	 * Field name.
	 *
	 * @param string $key Key.
	 */
	private function name( string $key ): string {
		return SettingsService::option( $this->group ) . '[' . $key . ']';
	}

	/**
	 * Field id.
	 *
	 * @param string $key Key.
	 */
	private function id( string $key ): string {
		return 'wbpc-' . $this->group . '-' . str_replace( '_', '-', $key );
	}
}

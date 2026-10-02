<?php
/**
 * Uninstall: remove plugin data unless "Keep data" is on (Pincode Checker > Tools).
 *
 * @package Wbcom\PincodeChecker
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/Core/Uninstaller.php';

\Wbcom\PincodeChecker\Core\Uninstaller::run();

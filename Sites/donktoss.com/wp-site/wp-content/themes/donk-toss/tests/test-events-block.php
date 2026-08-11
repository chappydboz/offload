<?php
/**
 * Automated Unit & Integration Tests for Donk Toss Upcoming Events Block
 */

// Mock WP Environment if running standalone PHP CLI
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
	
	// Lightweight Mock Functions for CLI Execution
	if ( ! function_exists( 'esc_html' ) ) { function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); } }
	if ( ! function_exists( 'esc_attr' ) ) { function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); } }
	if ( ! function_exists( 'esc_url' ) ) { function esc_url( $text ) { return filter_var( $text, FILTER_SANITIZE_URL ); } }
	if ( ! function_exists( 'esc_js' ) ) { function esc_js( $text ) { return addslashes( $text ); } }
	if ( ! function_exists( '__' ) ) { function __( $text, $domain = 'default' ) { return $text; } }
	if ( ! function_exists( '_e' ) ) { function _e( $text, $domain = 'default' ) { echo $text; } }
	if ( ! function_exists( 'esc_html_e' ) ) { function esc_html_e( $text, $domain = 'default' ) { echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); } }
	if ( ! function_exists( 'esc_attr_e' ) ) { function esc_attr_e( $text, $domain = 'default' ) { echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); } }
	if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $tag, $func ) {} }
	if ( ! function_exists( 'home_url' ) ) { function home_url( $path = '' ) { return 'https://donktoss.com' . $path; } }
	if ( ! function_exists( 'is_wp_error' ) ) { function is_wp_error( $thing ) { return false; } }
}

// Load Block Renderer
require_once __DIR__ . '/../block-upcoming-events.php';

class DonkTossEventsBlockTest {
	private $passed = 0;
	private $failed = 0;

	public function run_all() {
		echo "\n=======================================================\n";
		echo " 🧪 RUNNING DONK TOSS UPCOMING EVENTS BLOCK TEST SUITE \n";
		echo "=======================================================\n\n";

		$this->test_zero_events_returns_empty_string();
		$this->test_upcoming_events_query_filter();
		$this->test_block_attributes_rendering();
		$this->test_external_links_have_target_blank();

		echo "\n-------------------------------------------------------\n";
		echo sprintf( " RESULTS: %d Passed, %d Failed\n", $this->passed, $this->failed );
		echo "-------------------------------------------------------\n\n";

		if ( $this->failed > 0 ) {
			exit( 1 );
		}
	}

	private function assert_equals( $expected, $actual, $test_name ) {
		if ( $expected === $actual ) {
			echo "  ✅ PASS: {$test_name}\n";
			$this->passed++;
		} else {
			echo "  ❌ FAIL: {$test_name}\n";
			echo "     Expected: " . var_export( $expected, true ) . "\n";
			echo "     Actual:   " . var_export( $actual, true ) . "\n";
			$this->failed++;
		}
	}

	private function assert_contains( $needle, $haystack, $test_name ) {
		if ( strpos( $haystack, $needle ) !== false ) {
			echo "  ✅ PASS: {$test_name}\n";
			$this->passed++;
		} else {
			echo "  ❌ FAIL: {$test_name}\n";
			echo "     Needle '{$needle}' not found in output.\n";
			$this->failed++;
		}
	}

	/**
	 * Test 1: Zero Events Auto-Hide Guard
	 */
	public function test_zero_events_returns_empty_string() {
		$mock_events = array();
		$output = donktoss_render_upcoming_events_html( array(
			'heading' => 'Upcoming Events',
			'limit'   => 3,
		), $mock_events );

		$this->assert_equals( '', $output, 'Zero events renders exact empty string (0-byte output)' );
	}

	/**
	 * Test 2: Upcoming vs Past Date Filter
	 */
	public function test_upcoming_events_query_filter() {
		$today = date( 'Y-m-d' );
		$past_date = '2025-01-01';
		$future_date = '2026-12-31';

		$mock_all_events = array(
			array(
				'id'         => 1,
				'title'      => 'Past Event 2025',
				'start_date' => $past_date,
			),
			array(
				'id'         => 2,
				'title'      => 'Future World Championship',
				'start_date' => $future_date,
			),
		);

		$filtered = donktoss_filter_upcoming_events( $mock_all_events, $today );

		$this->assert_equals( 1, count( $filtered ), 'Only future/today events pass filter' );
		$this->assert_equals( 'Future World Championship', $filtered[0]['title'], 'Correct upcoming event title returned' );
	}

	/**
	 * Test 3: Block Attributes Parsing & Output
	 */
	public function test_block_attributes_rendering() {
		$mock_events = array(
			array(
				'id'            => 101,
				'title'         => 'The Ocho 2026 Live',
				'permalink'     => 'https://donktoss.com/events/ocho-2026/',
				'start_date'    => '2026-08-07',
				'start_time'    => '10:00 AM CT',
				'loc_name'      => 'ESPN Wide World of Sports',
				'btn_text'      => 'Watch on ESPN2',
				'btn_link'      => 'https://www.espn.com/watch/',
				'thumbnail_url' => 'https://donktoss.com/wp-content/uploads/thumb.jpg',
				'types'         => array( (object) array( 'name' => 'Live Activation' ) ),
			),
		);

		$output = donktoss_render_upcoming_events_html( array(
			'heading' => 'Upcoming Tournaments & Airings',
			'limit'   => 3,
		), $mock_events );

		$this->assert_contains( 'Upcoming Tournaments &amp; Airings', $output, 'Custom Heading rendered in HTML' );
		$this->assert_contains( 'The Ocho 2026 Live', $output, 'Event title rendered in grid' );
		$this->assert_contains( 'Live Activation', $output, 'Category tag rendered' );
	}

	/**
	 * Test 4: External CTA Links enforce target="_blank"
	 */
	public function test_external_links_have_target_blank() {
		$mock_events = array(
			array(
				'id'            => 102,
				'title'         => 'ESPN Premiere',
				'permalink'     => 'https://donktoss.com/events/premiere/',
				'start_date'    => '2026-08-07',
				'btn_text'      => 'Watch on ESPN2',
				'btn_link'      => 'https://www.espn.com/watch/',
				'thumbnail_url' => '',
				'types'         => array(),
			),
		);

		$output = donktoss_render_upcoming_events_html( array(), $mock_events );

		$this->assert_contains( 'target="_blank"', $output, 'External link enforces target="_blank"' );
		$this->assert_contains( 'rel="noopener noreferrer"', $output, 'External link enforces rel="noopener noreferrer"' );
	}
}

$test_runner = new DonkTossEventsBlockTest();
$test_runner->run_all();

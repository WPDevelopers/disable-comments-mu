<?php
use PHPUnit_Framework_TestCase;
use Brain\Monkey\Functions;


class PluginTestCase extends WP_UnitTestCase {

    function setUp() {
        parent::setUp();
		$this->plugin_instance = new Disable_Comments_MU();
    }

	function test_init_hooks_added() {
		$this->assertEquals( 10,  has_action( 'widgets_init', array( $this->plugin_instance, 'disable_rc_widget' ) ) );
		$this->assertEquals( 10,  has_action( 'wp_headers', array( $this->plugin_instance, 'filter_wp_headers' ) ) );
		$this->assertEquals( 9,  has_action( 'template_redirect', array( $this->plugin_instance, 'filter_query' ) ) );
		$this->assertEquals( 10,  has_action( 'template_redirect', array( $this->plugin_instance, 'filter_admin_bar' ) ) );
		$this->assertEquals( 10,  has_action( 'admin_init', array( $this->plugin_instance, 'filter_admin_bar' ) ) );
		$this->assertEquals( 10,  has_action( 'wp_loaded', array( $this->plugin_instance, 'setup_filters' ) ) );
	}

    function test_setup_filters() {
        $this->plugin_instance->setup_filters();
        $this->assertFalse( post_type_supports( 'post', 'comments' ) );
        $this->assertFalse( post_type_supports( 'page', 'comments' ) );
        $this->assertFalse( post_type_supports( 'article', 'comments' ) );

        $this->assertEquals( 10,  has_action( 'template_redirect', array( $this->plugin_instance, 'check_comment_template' ) ) );
        $this->assertEquals( 20,  has_action( 'comments_open', array( $this->plugin_instance, 'filter_comment_status' ) ) );
        $this->assertEquals( 20,  has_action( 'pings_open', array( $this->plugin_instance, 'filter_comment_status' ) ) );
    }

    function test_filter_headers() {
		$input = array( 'X-Pingback' => 'http://example.com' );
		$output = $this->plugin_instance->filter_wp_headers($input);
		$this->assertEmpty($output);
	}

    function test_comment_feed_403() {
		Functions::when( 'is_comment_feed' )->justReturn(true);
		Functions::expect( 'wp_die' )->once();
		$this->plugin_instance->filter_query();
	}

    function test_admin_bar_filter() {
		Functions::when( 'is_admin_bar_showing' )->justReturn(true);
		add_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		$this->plugin_instance->filter_admin_bar();
		$this->assertFalse( has_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu' ) );
	}

    function test_filter_comment_status() {
		// Supply a fake post ID - it should not matter
		$output = $this->plugin_instance->filter_comment_status( 'open', 5 );
		$this->assertFalse( $output );
	}

    function test_disable_rc_widget() {
        Functions::expect( 'unregister_widget' )->once();
        $this->plugin_instance->disable_rc_widget();
    }
}

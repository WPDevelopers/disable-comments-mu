<?php
use PHPUnit_Framework_TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;


class PluginTestCase extends PHPUnit_Framework_TestCase {

    function setUp() {
        parent::setUp();
		$this->plugin_instance = new Disable_Comments_MU();
    }

    function tearDown() {
        Monkey::tearDown();
        parent::tearDown();
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

    function test_setup_filters_for_admin() {
        Functions::when( 'is_admin' )->justReturn( true );
        $plugin = new Disable_Comments_MU();
        $plugin->setup_filters();
        $this->assertEquals( 9999,  has_action( 'admin_menu', array( $plugin, 'filter_admin_menu' ) ) );
        $this->assertEquals( 10,  has_action( 'admin_head-index.php', array( $plugin, 'dashboard_css' ) ) );
        $this->assertEquals( 10,  has_action( 'wp_dashboard_setup', array( $plugin, 'filter_dashboard' ) ) );
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

    function test_check_comment_template() {
        Functions::when( 'is_singular' )->justReturn( true );
        Functions::expect( 'wp_deregister_script' )->once()->with( 'comment-reply' );
        $this->plugin_instance->check_comment_template();
        $this->assertEquals( 20, has_action( 'comments_template', array( $this->plugin_instance, 'dummy_comments_template' ) ) );
        $this->assertFalse(has_action( 'wp_head', 'feed_links_extra' ) );
    }

    function test_dummy_template() {
        $template = $this->plugin_instance->dummy_comments_template();
        $this->assertTrue( file_exists ( $template ) );
        $this->assertEquals( substr( $template, -41 ), 'disable-comments-mu/comments-template.php' );
    }

    function test_filter_dashboard() {
        Functions::expect('remove_meta_box')->once()->with( 'dashboard_recent_comments', 'dashboard', 'normal' );
        $this->plugin_instance->filter_dashboard();
    }
}


class IntegrationTestCase extends WP_UnitTestCase {

    function setUp() {
        parent::setUp();
		$this->plugin_instance = new Disable_Comments_MU();
    }

    function test_comments_open_filter() {
        $this->assertFalse( apply_filters( 'comments_open', 'open', 5 ) );
    }

    function test_pings_open_filter() {
        $this->assertFalse( apply_filters( 'pings_open', 'open', 5 ) );
    }

    function test_post_comments_feed_link_filter() {
        $this->assertFalse( apply_filters( 'post_comments_feed_link', 'http://example.com' ) );
    }

    function test_comments_link_feed_filter() {
        $this->assertFalse( apply_filters( 'comments_link_feed', 'http://example.com' ) );
    }

    function test_comment_link_filter() {
        $this->assertFalse( apply_filters( 'comment_link', 'http://example.com' ) );
    }

    function test_get_comments_number_filter() {
        $this->assertFalse( apply_filters( 'get_comments_number', 10, 6 ) );
    }

    function test_feed_links_show_comments_feed_filter() {
        $this->assertFalse( apply_filters( 'feed_links_show_comments_feed', true ) );
    }

    function test_wp_headers_filter() {
        $this->assertEmpty( apply_filters( 'wp_headers', array( 'X-Pingback' => 'http://example.com' ) ) );
    }
}

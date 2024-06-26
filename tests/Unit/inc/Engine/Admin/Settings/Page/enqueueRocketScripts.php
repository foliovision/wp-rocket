<?php

namespace WP_Rocket\Tests\Unit\inc\Engine\Admin\Settings\Page;

use Mockery;
use Brain\Monkey\Functions;
use WP_Rocket\Engine\Admin\Database\Optimization;
use WP_Rocket\Engine\Admin\Beacon\Beacon;
use WP_Rocket\Engine\Admin\Settings\Page;
use WP_Rocket\Engine\Admin\Settings\Settings;
use WP_Rocket\Engine\License\API\UserClient;
use WP_Rocket\Engine\Optimization\DelayJS\Admin\SiteList;
use WPMedia\PHPUnit\Unit\TestCase;
use WP_Rocket\Admin\Options_Data;

/**
 * Test class covering \WP_Rocket\Engine\Admin\Settings\Page::enqueue_rocket_scripts
 * @group  Admin
 * @group  SettingsPage
 */
class Test_EnqueueRocketScripts extends TestCase {
	/**
	 * @dataProvider providerTestData
	 */
	public function testShouldMaybeEnqueueScript( $hook, $expected ) {
		$config = [
			'slug'       => 'wprocket',
			'title'      => 'WP Rocket',
			'capability' => 'rocket_manage_options',
		];

		$template_path = 'vfs://public/wp-content/plugins/wp-rocket/views';

		$page = new Page(
			$config,
			Mockery::mock( Settings::class ),
			Mockery::mock( 'WP_Rocket\Interfaces\Render_Interface'),
			Mockery::mock( Beacon::class),
			Mockery::mock( Optimization::class ),
			Mockery::mock( UserClient::class ),
			Mockery::mock( SiteList::class ),
			$template_path,
			Mockery::mock( Options_Data::class )
		);

		if ( true === $expected ) {
			Functions\expect( 'wp_enqueue_script' )
				->once()
				->with(  'wistia-e-v1', 'https://fast.wistia.com/assets/external/E-v1.js', [], null, true );
		} else {
			Functions\expect( 'wp_enqueue_script' )
				->never();
		}

		$page->enqueue_rocket_scripts( $hook );
	}

	public function providerTestData() {
		return $this->getTestData( __DIR__, 'enqueueRocketScripts' );
	}
}

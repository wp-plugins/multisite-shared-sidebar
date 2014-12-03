<?php
/**
 * @package multisite-shared-sidebar
 * @version 1.0
 */
/*
Plugin Name: Multisite Shared Sidebar
Plugin URI: 
Description: This plugin allows a sidebar to be shared between blogs on a multisite.  It's very simple to use. You can also use this plugin to add sidebars that you have added to the theme yourself.  Beware, however, that after activating the plugin, you must view the dashboard of all of subdomains in the multisite before you can use the sidebar.
Author: Mikio ISHITANI [ 2014/11/25 ]
Author URI:
Version: 1.0
Licence: GPLv2 or later
*/

/**
このプラグインはマルチサイトのブログ間でサイドバーを共有します。 
使い方はとても簡単です。

	1. 「Multisite Shared Sidebar」widgets を使って他のサイドバー内へ表示できます。
	2.　ショートコード「 [shared_sidebar blog="xx" index="xxx"] 」を使ってテキスト領域内へ表示できます。
	3.　テーマファイル内に 「 network_shared_sidebar( $blog, $index ) 」と PHPコードを直接記述して表示できます。

そして、このプラグインは貴方がテーマに追加したサイドバーも共有することができます。
ただし、プラグインを activate した後、使用する前に全ての参加サイトのダッシュボードを１回は表示しなければなりません。
**/

/**************
 * CONSTANTS
 **************/
define('MSS_REG_SIDEBARS',		'mss_registerd_sidebars' );
define('MSS_REG_WIDGETS',		'mss_registerd_widgets' );
define('MSS_SIDEBARS_WIDGETS',	'mss_sidebars_widgets' );
define('MSS_CSS_CLASS',			'multisite-shared-sidebar' );


/**
 * widgets 登録時にサイドバー関係データを [options] テーブル内へセットする
 *
 * set sidebar data in the [options] table when registering the widget
 *
 */
function set_multisite_shared_sidebar_option()
{
	global $wp_registered_sidebars, $wp_registered_widgets;

	update_option( MSS_REG_SIDEBARS, $wp_registered_sidebars ); // オプションデータがなければ新規作成される
	update_option( MSS_REG_WIDGETS,  $wp_registered_widgets  ); // if there is no option data, create a new one

	$sidebars_widgets = wp_get_sidebars_widgets();
	update_option( MSS_SIDEBARS_WIDGETS,  $sidebars_widgets );
}
add_action('init','set_multisite_shared_sidebar_option', 999);


/**
 * プラグインを activate する時にこの関数が呼び出される
 * this function is called when the plugin in activated
 *
 * シングルサイトの時はプラグインを activate しないでエラー表示する
 * when trying to activate the plugin on a single site, an error message will appear
 *
 */
function mss_plugin_activate()
{
	if( ! is_multisite()) {
//		die("[Network Shared Sidebar] プラグインはマルチサイト限定です。");
		die("The [Multisite Shared Sidebar] plugin is only for multisite use.");
	}
}
register_activation_hook( __FILE__, 'mss_plugin_activate');


/**
 * プラグインを deactivate する時に、[options]テーブル内へ保存したデータを削除する
 * delete data written by plugin from [options] table when plugin is deactivated
 */
function mss_plugin_deactivate()
{
	// サイトのブログ関係データを取得する
	// retrieve related data from blogs
	$my_blogs = wp_get_sites();

	// 各ブログの [options]テーブル内からデータを削除する
	// delete data from each subdomain's [options] table
	foreach( $my_blogs as $blog )
	{
		switch_to_blog( $blog['blog_id'] );
		{
			delete_option( MSS_SIDEBARS_WIDGETS );
			delete_option( MSS_REG_WIDGETS );
			delete_option( MSS_REG_SIDEBARS );
		}
		restore_current_blog();
	}
}
register_deactivation_hook( __FILE__, 'mss_plugin_deactivate' );


/**
 * ブログＩＤの問い合せと取得
 * query for and retrieve blog ID
 */
function mss_query_blog_id( $arg = false ) 
{
	if( ! empty($arg) )
	{
		if( ! is_numeric($arg) )
		{
			$path  = strtolower( $arg );
			$path  = '/' . trim( $path, '/' ) ;
			$path .= ( $path != '/' ) ? '/' : '' ;
			$arg   = '';
		}
		else {
			$path  = '';
		}

		// サイトの全ブログ関連データを取得
		// retrieve related data from all blogs
		$my_blogs = wp_get_sites();

		// パスによる検索 or ＩＤのチェック
		// path search or ID check
		foreach( $my_blogs as $blog )
		{
			if( $blog['path'] == $path || $blog['blog_id'] == $arg )
				return $blog['blog_id'];
		}
	}
	return false;
}


/**
 * 指定ブログのサイドバー表示
 * display sidebar from designated blog
 *
 *   ブログパス名 または ブログＩＤと、サイドバーＩＤを指定する
 *   select blog password, blog ID, or sidebar ID
 *
 *  e.g.	multisite_shared_sidebar(1,"sidebar-1");
 *			multisite_shared_sidebar("2", "sidebar-1");
 *			multisite_shared_sidebar("path", "sidebar-1");
 *			multisite_shared_sidebar("/path/", "sidebar-1");
 *
 */
function multisite_shared_sidebar( $blog, $index )
{
	$blogID = mss_query_blog_id( $blog ) ;
	
	if( empty($blogID) ) {
		return;
	}

	switch_to_blog( $blogID );
	{
		$my_registered_sidebars = get_option( MSS_REG_SIDEBARS     );
		$my_registered_widgets  = get_option( MSS_REG_WIDGETS      );
		$my_sidebars_widgets    = get_option( MSS_SIDEBARS_WIDGETS );

		// BEGIN サイドバー表示ルーチン　（ widgets.php : dynamic_sidebar() 関数をコピーし、改変した ）
		// BEGIN sidebar display routine　(this is a modified version of widgets.php : dynamic_sidebar())
		{
			$index = sanitize_title( $index );

			if ( empty( $my_registered_sidebars[ $index ] )
			  || empty( $my_sidebars_widgets[ $index ] )
			  || ! is_array( $my_sidebars_widgets[ $index ] )
			) {
				restore_current_blog();
				return;
			}

			$sidebar = $my_registered_sidebars[$index];

			foreach ( (array) $my_sidebars_widgets[$index] as $id ) {
	
				if ( !isset($my_registered_widgets[$id]) )
					continue;

				$params = array_merge(
					array( array_merge( $sidebar, array('widget_id' => $id, 'widget_name' => $my_registered_widgets[$id]['name']) ) ),
					(array) $my_registered_widgets[$id]['params']
				);

				// Substitute HTML id and class attributes into before_widget
				$classname_ = '';
				foreach ( (array) $my_registered_widgets[$id]['classname'] as $cn ) {
					if ( is_string($cn) )
						$classname_ .= '_' . $cn;
					elseif ( is_object($cn) )
						$classname_ .= '_' . get_class($cn);
				}
				$classname_ = ltrim($classname_, '_') . ' ' . MSS_CSS_CLASS;	// added
				$params[0]['before_widget'] = sprintf($params[0]['before_widget'], $id, $classname_);

				$params = apply_filters( 'multisite_shared_sidebar_params', $params );

				$callback = $my_registered_widgets[$id]['callback'];

				if ( is_callable($callback) ) {
					call_user_func_array($callback, $params);
				}
			}
		}
		// END サイドバー表示ルーチン
		// END sidebar display routine
	}
	restore_current_blog();
}


/**
 *	指定ブログのサイドバーをテキスト領域内へ表示するショートコードの定義
 *  definition of shortcode for displaying disignated sidebar in the text region
 *
 *   e.g.	[shared_sidebar blog="1" index="sidebar-1"]
 *			[shared_sidebar blog="path" index="sidebar-1"]
 *			[shared_sidebar blog="/path/" index="sidebar-1"]
 *
 */
function register_multisite_shared_sidebar_shortcode( $atts )
{
	extract(
		shortcode_atts(
			array(
				'blog' => get_current_blog_id(),
				'index' => '',
			),
			$atts
		)
	);
	multisite_shared_sidebar( $blog, $index );
}
add_shortcode(
	'shared_sidebar',	// ショートコード名 shortcode name
	'register_multisite_shared_sidebar_shortcode'	// 処理関数名 name of processing function
);


/**
 *	参加サイトのサイドバーを指定サイドバー内へ共有表示するウィジェットの定義
 *  definition of widget for displaying a subdomain's sidebar in a designated sidebar
 */
class Multisite_Shared_Sidebar_Widget extends WP_Widget
{
	function __construct() {
        parent::__construct(
            'multisite_shared_sidebar_widget', // Base ID
            'Multisite Shared Sidebar', // Name
//          array( 'description' => '指定サイトのサイドバーをウィジェット内へ表示します。', )
			array( 'description' => 'Display designated sidebar in the widget.', )
		);
	}

	public function widget( $args, $instance ) 
	{
		$blog  = $instance['blog'];
		$index = $instance['index'];
		multisite_shared_sidebar( $blog, $index );
    }

	public function form( $instance )
	{
		if ( $instance ) {
			$blog  = $instance['blog'];
			$index = $instance['index'];
		}
		else {
			$blog  = '';
			$index = '';
		}
		?>
<!--	<p>指定サイトのサイドバーをウィジェット内へ表示します。</p>-->
		<p>Display selected sidebar in the widget.</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'blog' ); ?>">Blog ID: or Blog Path:</label>
		<input  class="widefat"
        		id="<?php echo $this->get_field_id( 'blog' ); ?>" 
                name="<?php echo $this->get_field_name( 'blog' ); ?>" 
                type="text" 
                value="<?php echo esc_attr( $blog ); ?>" />
        
		<label for="<?php echo $this->get_field_id( 'index' ); ?>">Sidebar ID:</label>
		<input  class="widefat"
        		id="<?php echo $this->get_field_id( 'index' ); ?>" 
                name="<?php echo $this->get_field_name( 'index' ); ?>" 
                type="text" 
                value="<?php echo esc_attr( $index ); ?>" />
		</p>
		<?php
	}

	function update($new_instance, $old_instance) 
	{
        return $new_instance;
    }
}
add_action( 'widgets_init',
	function () {
		register_widget( 'Multisite_Shared_Sidebar_Widget' );
	}
);

?>

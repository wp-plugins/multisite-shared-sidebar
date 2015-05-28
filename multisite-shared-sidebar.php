<?php
/**
 * @package multisite-shared-sidebar
 * @version 1.1
 */
/*
Plugin Name: Multisite Shared Sidebar
Plugin URI: 
Description: This plugin allows a sidebar to be shared between blogs on a multisite.  It's very simple to use. You can also use this plugin to add sidebars that you have added to the theme yourself.  Beware, however, that after activating the plugin, you must view the dashboard of all of subdomains in the multisite before you can use the sidebar.
Author: Mikio ISHITANI [ 2015/01/15 ]
Author URI:
Version: 1.1.1
Licence: GPLv2 or later
*/

/**
このプラグインはマルチサイトのブログ間でサイドバーを共有します。 
使い方はとても簡単です。

	1. 「Multisite Shared Sidebar」widgets を使って他のサイドバー内へ表示できます。
	2.　ショートコード「 [shared_sidebar blog="xx" index="xxx"] 」を使ってテキスト領域内へ表示できます。

そして、このプラグインは貴方がテーマに追加したサイドバーも共有することができます。
ただし、プラグインを activate した後、使用する前に全ての参加サイトのダッシュボードを１回は表示しなければなりません。

== Update: 2015/01/08 ==

(1) 現在のブログサイトのサイドバー定義を使って表示できるようにしました。

(2) 以下のサイドバー定義をカスタマイズできるようにしました。
	・before_widget
	・after_widget
	・before_title
	・after_title

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
//		die("[Multisite Shared Sidebar] プラグインはマルチサイト限定です。");
		die( __('The [Multisite Shared Sidebar] plugin is only for multisite use.', 'multisite-shared-sidebar') );
	}
	$GLOBALS['mss_current_sidebar_index'] = '';
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
	
	// 外部変数の破棄
	unset( $GLOBALS['mss_current_sidebar_index'] );
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
function multisite_shared_sidebar( 
			$blog='', 
			$index='', 
			$sidebar_config='',  // '' or 'advanced_config'
			$before_widget='', 
			$after_widget='', 
			$before_title='', 
			$after_title='' 
) {
	$args = array(
				'blog' => $blog,
				'index' => $index,
				'sidebar_config' => $sidebar_config,
				'before_widget' => $before_widget,
				'after_widget' => $after_widget,
				'before_title' => $before_title,
				'after_title' => $after_title,
			);
	
	if( $args['sidebar_config'] != 'advanced_config' ) {  
		$args['sidebar_config'] = '';	// "current_config" will be ignored
	}

	multisite_shared_sidebar_1_( $args );
}


function multisite_shared_sidebar_1_( $args )
{
	global $wp_registered_sidebars, $mss_current_sidebar_index;

	$blogID = mss_query_blog_id( $args['blog'] ) ;
	
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
			$index = sanitize_title( $args['index'] );

			if ( empty( $my_registered_sidebars[ $index ] )
			  || empty( $my_sidebars_widgets[ $index ] )
			  || ! is_array( $my_sidebars_widgets[ $index ] )
			) {
				restore_current_blog();
				return;
			}
			
			if( $args['sidebar_config'] == 'current_config' && ! empty( $wp_registered_sidebars[ $mss_current_sidebar_index] ) ) {
				// 現在のテーマのサイドバー定義を使用する
				// Use of a sidebar definition of a current theme
				$sidebar = $wp_registered_sidebars[ $mss_current_sidebar_index ];
			}
			else {
				$sidebar = $my_registered_sidebars[ $index ];
			}

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
				$classname_ = ltrim($classname_, '_') . ' ' . MSS_CSS_CLASS;	// クラス名を追加

				// サイドバーのウィジェット定義を上書きする
				// Widget setting of sidebar is overwritten.
				if( $args['sidebar_config'] == 'advanced_config' )
				{
					if( ! empty($args['before_widget']) ) {
						$params[0]['before_widget'] = $args['before_widget'];
					}
					if( ! empty($args['after_widget']) ) {
						$params[0]['after_widget'] = $args['after_widget'];
					}
					if( ! empty($args['before_title']) ) {
						$params[0]['before_title'] = $args['before_title'];
					}
					if( ! empty($args['after_title']) ) {
						$params[0]['after_title'] = $args['after_title'];
					}
				}
				
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

// サイドバー表示の前処理
// ディスプレイに使用するサイドバーＩＤを外部変数へ保存する。
// An index of the sidebar used for a display is saved to a global variable.
function mss_dynamic_sidebar_before( $index, $has_widgets)
{
	if( !empty($has_widgets) ) {
		$GLOBALS['mss_current_sidebar_index'] = $index;
	}
}
add_action('dynamic_sidebar_before','mss_dynamic_sidebar_before', 10, 2 );

// サイドバー表示の後処理
function mss_dynamic_sidebar_after( $index, $has_widgets)
{
	if( !empty($has_widgets) ) {
		$GLOBALS['mss_current_sidebar_index'] = '';
	}
}
add_action('dynamic_sidebar_after','mss_dynamic_sidebar_after', 10, 2 );


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
	$args = shortcode_atts(
			array(
				'blog' => get_current_blog_id(),
				'index' => '',
				'sidebar_config' => '',  // '' or 'advanced_config'
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			),
			$atts
		);

	if( $args['sidebar_config'] != 'advanced_config' ) {
		$args['sidebar_config'] = '';	// "current_config" will be ignored
	}

	multisite_shared_sidebar_1_( $args );
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
			array( 'description' => __('Display selected sidebar in the widget.','multisite-shared-sidebar'), )
		);
	}

	public function widget( $args, $instance ) 
	{
		multisite_shared_sidebar_1_( $instance ); 
    }

	public function form( $instance )
	{
		$id_blog = $this->get_field_id('blog');
		$nm_blog = $this->get_field_name('blog');
		
		$id_index = $this->get_field_id('index');
		$nm_index = $this->get_field_name('index');
		
		$id_current_config = $this->get_field_id('current_config');
		$id_advanced_config = $this->get_field_id('advanced_config');
		$nm_sidebar_config = $this->get_field_name('sidebar_config');
			
		$id_before_widget = $this->get_field_id('before_widget');
		$nm_before_widget = $this->get_field_name('before_widget');
		
		$id_after_widget = $this->get_field_id('after_widget');
		$nm_after_widget = $this->get_field_name('after_widget');
		
		$id_before_title = $this->get_field_id('before_title');
		$nm_before_title = $this->get_field_name('before_title');
		
		$id_after_title = $this->get_field_id('after_title');
		$nm_after_title = $this->get_field_name('after_title');
		
		if ( empty($instance) )
		{
			$instance['blog'] = '';
			$instance['index'] = '';
			$instance['sidebar_config'] = 'current_config';
			$instance['before_widget'] = '';
			$instance['after_widget'] = '';
			$instance['before_title'] = '';
			$instance['after_title'] = '';
		}
		
		switch( $instance['sidebar_config'] )
		{
			case 'current_config':
				$cc = ' checked="checked"';
				$ac = '';
				$hd = ' hidden="hidden"';
				break;
			case 'advanced_config':
				$cc = '';
				$ac = ' checked="checked"';
				$hd = '';
				break;
			default:
				$cc = '';
				$ac = '';
				$hd = ' hidden="hidden"';
				break;
		}
		?>

		<p>■ <b><?php _e('Display selected sidebar in the widget.','multisite-shared-sidebar'); ?></b></p>
		<p>
		<label for="<?php echo $id_blog; ?>"><?php _e('Blog ID: or Blog Path:','multisite-shared-sidebar'); ?></label>
		<input  class="widefat"
        		id="<?php echo $id_blog; ?>" 
                name="<?php echo $nm_blog; ?>" 
                type="text" 
                placeholder="1,2,3... or /path/"
                value="<?php echo esc_attr( $instance['blog'] ); ?>" />
        <hr hidden="hidden" />
		<label for="<?php echo $id_index; ?>"><?php _e('Sidebar ID:','multisite-shared-sidebar'); ?></label>
		<input  class="widefat"
        		id="<?php echo $id_index; ?>" 
                name="<?php echo $nm_index; ?>" 
                type="text" 
                placeholder="sidebar-1"
                value="<?php echo esc_attr( $instance['index'] ); ?>" />
		</p>
		<hr />
		<p>
		<input  class="widefat"
        		<?php echo $cc; ?>
        		id="<?php echo $id_current_config; ?>" 
                name="<?php echo $nm_sidebar_config; ?>" 
                type="checkbox" 
                value="current_config" 
                onchange='mss_check_onchange(this, "<?php echo $id_current_config; ?>", "<?php echo $id_advanced_config; ?>");'
                />
        <label for="<?php echo $id_current_config; ?>" ><b><?php _e('Using current sidebar defined.','multisite-shared-sidebar'); ?></b></label>
        <p>
   		<p>
		<input  class="widefat"
        		<?php echo $ac; ?>
        		id="<?php echo $id_advanced_config; ?>" 
                name="<?php echo $nm_sidebar_config; ?>" 
                type="checkbox" 
                value="advanced_config" 
                onchange='mss_check_onchange(this, "<?php echo $id_current_config; ?>", "<?php echo $id_advanced_config; ?>");'
                />
        <label for="<?php echo $id_advanced_config; ?>" ><b><?php _e('Advanced sidebar configuration.','multisite-shared-sidebar'); ?></b></label>
        </p>
        <p <?php echo $hd; ?> id="<?php echo $id_advanced_config; ?>">
			<label>before_widget:</label>
			<textarea class="widefat" rows="2"
        		id="<?php echo $id_before_widget; ?>" 
				name="<?php echo $nm_before_widget; ?>"
            	placeholder='<li id="%1$s" class="widget %2$s">' 
            ><?php echo esc_attr( $instance['before_widget']); ?></textarea>
        
			<label>after_widget:</label>
			<textarea class="widefat" rows="2"
        		id="<?php echo $id_after_widget; ?>" 
            	name="<?php echo $nm_after_widget; ?>" 
            	placeholder='</li>'
            ><?php echo esc_attr( $instance['after_widget']); ?></textarea>

			<label>before_title:</label>
        	<textarea class="widefat" rows="2"
        		id="<?php echo $id_before_title; ?>" 
            	name="<?php echo $nm_before_title; ?>" 
            	placeholder='<h2 class="widgettitle">'
            ><?php echo esc_attr( $instance['before_title']); ?></textarea>

			<label>after_title:</label>
			<textarea  class="widefat" rows="2"
        		id="<?php echo $id_after_title; ?>" 
            	name="<?php echo $nm_after_title; ?>" 
            	placeholder='</h2>'
            ><?php echo esc_attr( $instance['after_title']); ?></textarea>
        </p>
		<?php
	}

	function update($new_instance, $old_instance) 
	{
		if( empty($new_instance['sidebar_config']) ) {
			$new_instance['sidebar_config'] = '';
		}
        return $new_instance;
    }
}
add_action( 'widgets_init',
	function () {
		register_widget( 'Multisite_Shared_Sidebar_Widget' );
	}
);


function register_multisite_shared_sidebar_javascript()
{
?>
<script type="text/javascript">
	function mss_check_onchange( chk, idc, ida )
	{
		if( chk.checked )
		{
			if( chk.value == 'advanced_config' ) {
				jQuery("input[id="+idc+"]").prop('checked', false );
				jQuery("p[id="+ida+"]").prop('hidden', false );
				return;
			}
			jQuery("input[id="+ida+"]").prop('checked', false );
		}
		jQuery("p[id="+ida+"]").prop('hidden', true );
	}
</script>
<?php
}
add_action( 'admin_footer', 'register_multisite_shared_sidebar_javascript' );

// 翻訳ファイルの読み込み
function mss_load_plugin_textdomain() {
    load_plugin_textdomain( 'multisite-shared-sidebar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
}
add_action( 'plugins_loaded', 'mss_load_plugin_textdomain' );

?>

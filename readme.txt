=== Plugin Name ===
Contributors: Mikio ISHITANI
Donate link: 
Tags: wordpress, multisite, network, sidebar, share, shared, sharing, plugins, widgets, plugin, widget, shortcode, shortcodes
Requires at least: 4.0.0
Tested up to: 4.0.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


This plugin allows a sidebar to be shared between blogs on a multisite.


== Description ==

This plugin allows a sidebar to be shared between blogs on a multisite.  
It's very simple to use.

1. Use the "Multisite Shared Sidebar" widget to display a sidebar from another subdomain.
1. Use the shorcode "[shared_sidebar blog="xx" index="xxx"]" to display a sidebar in the text region.
1. Use the PHP code "multisite_shared_sidebar($blog,$index)" to directly view the sidebar in a theme file.

You can also use this plugin to add sidebars that you have added to the theme yourself.

Beware, however, that after activating the plugin, you must view the dashboard of all of subdomains in the multisite before you can use the sidebar.

= ☆ For Japanese users ☆ =

このプラグインはマルチサイトのブログ間でサイドバーを共有します。  
使い方はとても簡単です。

1. "Multisite Shared Sidebar" widgets を使って他のサイドバー内へ表示できます.
1. ショートコード [shared_sidebar blog="xx" index="xxx"] を使ってテキスト領域内へ表示できます.
1. テーマファイル内に multisite_shared_sidebar( $blog, $index) と PHPコードを直接記述して表示できます.

そして、このプラグインは貴方がテーマに追加したサイドバーも共有することができます。

ただし、プラグインを activate した後、使用する前に全ての参加サイトのダッシュボードを１回は表示しなければなりません。


== Installation ==

1. Download the plugin
1. Uncompress it with your preferred unzip program
1. Copy the entire directory in your plugin directory of your WordPress blog (/wp-content/plugins)
1. Network-activate the plugin


== Frequently Asked Questions ==

= Can this plugin use a single site ? =

No, this can't be used.


== Screenshots ==

1. Use the "Multisite Shared Sidebar" widget

2. Use the shorcode "[shared_sidebar blog="2" index="sidebar-2"]"

== Changelog ==

= 1.0 =
* First release.

== Upgrade Notice ==

No upgrade, so far.

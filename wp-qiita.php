<?php
/*
  Plugin Name: WP Qiita
  Plugin URI: https://ka2.org/
  Description: You are able to manage the articles of <a href="https://qiita.com/" target="_blank">Qiita</a> in the WordPress by using this plugin. In this plugin is using the Qiita API v2 to the Qiita connection.
  Version: 1.0.0
  Author: ka2
  Author URI: https://ka2.org
  Copyright: 2015 monauralsound (email : ka2@ka2.org)
  License: GPL2 - http://www.gnu.org/licenses/gpl.txt
  Text Domain: wp-qiita
  Domain Path: /langs
*/
?>
<?php
/*  Copyright 2015 ka2 (https://ka2.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
define( 'WPQT_PLUGIN_VERSION', '1.0.0' );
define( 'WPQT_DB_VERSION', '1.0' );
define( 'WPQT', 'wp-qiita' ); // This plugin domain name

define( 'WPQT_DEBUG', true ); // For toggle of debug mode

require_once plugin_dir_path(__FILE__) . 'functions.php';

wpqt_factory();

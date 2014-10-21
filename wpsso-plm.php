<?php
/*
 * Plugin Name: WPSSO Place and Location Meta (WPSSO PLM)
 * Plugin URI: http://surniaulula.com/extend/plugins/wpsso-plm/
 * Author: Jean-Sebastien Morisset
 * Author URI: http://surniaulula.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: WPSSO extension to provide Open Graph / Facebook Location and Pinterest Rich Pin Place meta tags.
 * Requires At Least: 3.0
 * Tested Up To: 4.0
 * Version: 1.0
 * 
 * Copyright 2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoPlm' ) ) {

	class WpssoPlm {

		private $opt_version = 'plm1';
		private $min_version = '2.6.9.1';
		private $has_min_ver = true;

		public $p;				// class object variables

		public function __construct() {
			require_once ( dirname( __FILE__ ).'/lib/config.php' );
			WpssoPlmConfig::set_constants( __FILE__ );
			WpssoPlmConfig::require_libs( __FILE__ );

			add_filter( 'wpssoplm_installed_version', array( &$this, 'filter_installed_version' ), 10, 1 );
			add_filter( 'wpsso_get_config', array( &$this, 'filter_get_config' ), 20, 1 );

			add_action( 'wpsso_init_options', array( &$this, 'init_options' ), 20 );
			add_action( 'wpsso_init_addon', array( &$this, 'init_addon' ), 20 );
		}

		// this filter is executed at init priority -1
		public function filter_get_config( $cf ) {
			if ( version_compare( $cf['plugin']['wpsso']['version'], $this->min_version, '<' ) ) {
				$this->has_min_ver = false;
				return $cf;
			}
			$cf['opt']['version'] .= $this->opt_version;
			$cf = SucomUtil::array_merge_recursive_distinct( $cf, WpssoPlmConfig::$cf );
			return $cf;
		}

		// this action is executed when WpssoOptions::__construct() is executed (class object is created)
		public function init_options() {
			global $wpsso;
			$this->p =& $wpsso;

			if ( $this->has_min_ver === false )
				return;

			$this->p->is_avail['plm'] = true;
			$this->p->is_avail['admin']['place'] = true;
			$this->p->is_avail['head']['place'] = true;
		}

		// this action is executed once all class objects and addons have been created
		public function init_addon() {
			$shortname = WpssoPlmConfig::$cf['plugin']['wpssoplm']['short'];

			if ( $this->has_min_ver === false ) {
				$wpsso_version = $this->p->cf['plugin']['wpsso']['version'];
				$this->p->debug->log( $shortname.' requires WPSSO version '.$this->min_version.' or newer ('.$wpsso_version.' installed)' );
				if ( is_admin() )
					$this->p->notice->err( $shortname.' v'.WpssoPlmConfig::$cf['plugin']['wpssoplm']['version'].
					' requires WPSSO v'.$this->min_version.' or newer ('.$wpsso_version.' is currently installed).', true );
				return;
			}

			if ( is_admin() && 
				! empty( $this->p->options['plugin_wpssoplm_tid'] ) && 
				! $this->p->check->aop( 'wpssoplm', false ) ) {
				$this->p->notice->inf( 'An Authentication ID was entered for '.$shortname.', 
				but the Pro version is not installed yet &ndash; 
				don\'t forget to update the '.$shortname.' plugin to install the Pro version.', true );
			}

			WpssoPlmConfig::load_lib( false, 'place' );
			$this->p->place = new WpssoPlmPlace( $this->p, __FILE__ );
		}

		public function filter_installed_version( $version ) {
			if ( ! $this->p->check->aop( 'wpssoplm', false ) )
				$version = '0.'.$version;
			return $version;
		}
	}

        global $wpssoplm;
	$wpssoplm = new WpssoPlm();
}

?>

<?php

class Whv_Admin {

	public $wpdb = NULL;
	public $ns   = NULL;
	/**
	 * This method runs all of the actions necessary to install
	 * our plugin and use it with WordPress
	 *
	 * @return void
	 */
	public function runInstall() {

		// Create our accounts table
		dbDelta(str_replace(array(
			'{wpdbPrefix}',
			'{nameSpace}'
		), array(
			$this->wpdb->prefix,
			$this->ns
		), Whv_Config::Get('sqlInstallQueries', 'createAccountsTable')));
	}

	/**
	 * This method runs all of the actions necessary to uninstall
	 * our plugin from WordPress
	 *
	 * @return void
	 */
	public function runUninstall() {
		//dbdelta does not work with DROP
		// Delete our accounts table
		/*
		dbDelta(str_replace(array(
			'{wpdbPrefix}',
			'{nameSpace}'
		), array(
			$this->wpdb->prefix,
			$this->ns
		), Whv_Config::Get('sqlUninstallQueries', 'dropAccountsTable')));
		 */
		global $table_prefix, $table_suffix, $wpdb;
		$wpdb->query(
			str_replace(
				array(
					'{wpdbPrefix}',
					'{nameSpace}'
				), array(
					$this->wpdb->prefix,
					$this->ns
				), 
				Whv_Config::Get('sqlUninstallQueries', 'dropAccountsTable'))
		);
	}
}

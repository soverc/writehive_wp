<?php

/**
 * This class is a class that takes an INI file, 
 * parses it and returns variables to the caller
 * 
 * @author WriteCrowd <support@writehive.com>
 * @link   https://www.writehive.com/
 * @license GPL
 *
 **/
class Whv_Config {

	////////////////////////////////////////////////////////////////////////
	//////////      Properties      ///////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	protected static $aConfig;	// Our configurations

	////////////////////////////////////////////////////////////////////////
	//////////      Public Static      ////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method loads the configuration file
	 * and reads it into an array, separated by
	 * sections
	 * 
	 * @param string $sSection the section the desired variable is located
	 * @param string $sVariable the name of the desired variable
	 * @return mixed $mReturn
	 **/
	public static function Get($sSection, $sVariable = null) {

		// Load the configuration
		$aConfig = self::getConfig();

		// Return placeholder
		$mReturn = null;

		// Check to see if the caller wants 
		// a specific variable as well 
		if (is_null($sVariable)) {

			// If not, just return the 
			// entire section
			$mReturn = $aConfig[$sSection];
		} else {

			// Return the individual variable
			$mReturn = $aConfig[$sSection][$sVariable];
		}

		// Return the requested
		// configuration variable
		return $mReturn;
	}

	/**
	 * This method returns the entire
	 * configuration array, this method
	 * is to be primarily used for debug
	 *
	 * @return array
	 */
	public static function GetAll() {

		// Return our config array
		return self::$aConfig;
	}

	/**
	 * This method is responsible for initializing 
	 * the configuration class and setting the 
	 * base configurations from the specified 
	 * configuration file
	 * 
	 * @param string $sConfigFile is the path to the working configuration file
	 * @throws Exception
	 * @return object self for a fluid and chain-loadable interface
	 */
	public static function Init() {

		//load locally first
		$configDir = dirname(dirname(__FILE__)).'/config/';
		//I don't know ... 36000 is  10 hours?
//		if (@filemtime($configDir.'whv_config.json') > time()-36000) {
			if ($mJson = file_get_contents( $configDir. 'whv_config.json')) {
				self::$aConfig = json_decode($mJson, true);
				return true;
			}
//		}

		// Make the request
		$rContext = stream_context_create(array(
			'http' => array (
				'method'  => 'POST',
				'header'  => 'Content-type: application/json',
				'content' => json_encode(array(
					'_method' => 'load_config'
				))
			)
		));

		// Send the request
		$mResponse = file_get_contents('https://writehive.com/feeds/json', false, $rContext);

		// Check for data
		if (!empty($mResponse)) {

			// Set the response
			self::$aConfig = json_decode($mResponse, true);


			//save output to cache
			if (is_writable($configDir)) { 
				file_put_contents($configDir.'whv_config.json', $mResponse);
			}

			// Return
			return true;
		} else {
			return false;
		}
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Setters      //////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method is responsible for loading our 
	 * configurations into the system
	 * 
	 * @param array $aConfig is the array of configs
	 * @return object self for a fluid and chain-loadable interface
	 **/
	public static function setConfig(array $aConfig) {

		// Set our configuration
		self::$aConfig = (array) $aConfig;

		// Return instance
		return;
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Getters      //////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method returns the configuration array
	 * 
	 * @return array @property $aConfig
	 **/
	public static function getConfig() {

		// Return the config
		return self::$aConfig;
	}
}

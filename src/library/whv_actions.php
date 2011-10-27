<?php
/**
 * This class is responsible for setting up all
 * all the configurations and functions surrounding
 * the \syndicate and allowing them to function
 * inside of a local installation of WordPress
 *
 * @author WriteCrowd  <support@writecrowd.com>
 * @version 0.9
 * @link https://www.writecrowd.com/
 * @copyright WriteCrowd https://www.writecrowd.com/
 * @license GPL 3.0
 *
 * WriteCrowd is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * WriteCrowd is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WriteCrowd.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
class Whv_Actions {

	////////////////////////////////////////////////////////////////////////
	//////////      Properties      ///////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	protected static $oInstance = null;	// Singleton instance container
	protected $oAccount         = null;	// The current 180Create account in use
	protected $oArticle         = null;	// The current article in use
	protected $aArticles        = null; // The current article set in use
	protected $aCategories		= null; // Our categories
	protected $aComments        = null; // Our comments
	protected $oDatabase        = null;	// Our database object
	protected $sError           = null;	// Container for the current error
	protected $aGroups          = null; // Container for the current user's groups
	protected $sNamespace       = null;	// Our application namespace
	protected $sPluginPath      = null;	// Path to our Plugin
	protected $sPluginWebPath   = null; // URL to plugin
	protected $oPost            = null;	// The current WordPress Post in use
	protected $aPosts           = null; // WordPress posts
	protected $oPostData        = null;	// POST data container
	protected $mResponse        = null;	// RPC response container
	protected $oScope           = null;	// A variable scope to pass to the template
	protected $aSearchResults   = null; // Results container for when a search is run
	protected $aSubcategories   = null; // Our subcategories

	////////////////////////////////////////////////////////////////////////
	//////////      Singleton      ////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This gets the singleton instance
	 *
	 * @return object self::$oInstance
	 */
	public static function getInstance() {
		// Check to see if an instance has already
		// been created
		if (is_null(self::$oInstance)) {
			try {
				self::$oInstance = new self();
			} catch (Exception $oException) {
			}
		}
		return self::$oInstance;
	}

	/**
	 * This method resets the sinleton instance
	 *
	 * @return void
	 */
	public static function resetInstance() {
		self::$oInstance = null;
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Private Construct        //////////////////////////////
	/////////////////////////////////////////////////////////////////////

	/**
	 * This method sets up all of the WordPress features we wish to use
	 *
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	private function __construct() {

		// Check to see if an admin
		// has logged into the system
		if (is_admin()) {

			// If so, setup all of the
			// admin functions and controls

			// WordPress Init
			add_action('init', array($this, 'loadAssets'));

			// Create the sidebar menu for 180 Create
			add_action('admin_menu', array($this, 'loadAdminMenu'));

			// Meta Box
			// WordPress >= 3.0
			// add_action('add_meta_boxes', array($this, 'loadMetaBox'));

			// WordPress < 3.0
			add_action('admin_init', array($this, 'loadMetaBox'), 1);
		} else {

			// Load scripts and styles
			// for the client
			add_action('wp_head', array($this, 'loadAssets'), 1);
		}

		// Display Post Author
		add_filter('the_author', array($this, 'renderPostAuthor'));

		// Display Post Content
		add_filter('the_content', array($this, 'renderPostContent'), 1);

		// Set all of the functions and controls
		// Display Post Title
//		add_filter('the_title', array($this, 'renderPostTitle'));

		// Determine if we want to display our comments
		if (Whv_Config::Get('variables', 'enableCommentEngine')) {

			// Run when displaying Comments for a single post
//			add_filter('comments_array', array($this, 'handleComments'));

			// Use Our Comments Template
//			add_filter('comments_template', array($this, 'renderComments'));
		}

		return $this;
	}


	/**
	 * This method handles the login process
	 * for the 180Create Account
	 *
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function doLogin() {

		// Check for POST data
		if ($this->checkForData('getPostData')) {

			// POST data was found, make the
			// JSON-RPC service call
			$aPostNameSpace = $this->getPostData()->{$this->getNameSpace()};

			$this->feedJson(array(
				'_method'  => 'logon',
				'username' => $this->doSanitize($aPostNameSpace['txtDisplayName']),
				'passwd'   => $this->doSanitize($aPostNameSpace['txtPasswd'])
			));

			if (!is_object($this->getRpcResponse())) {
				$this->setError(Whv_Config::Get('errorMessages', 'noWordPressUserLoggedIn'));
				return $this;
			}
			// Check for a JSON-RPC error
			if (is_object($this->getRpcResponse()) && property_exists($this->getRpcResponse(), 'error')) {

				$this->setError($this->getRpcResponse()->error);
			} else {

				// No error was found, now
				// set the system account
				$this->setAccount($this->getRpcResponse());

				// Store the current user
				$oUser = wp_get_current_user();

				// Make sure a user is logged in
				if (empty($oUser->ID)) {

					// No user is logged in, so
					// set the system error
					$this->setError(Whv_Config::Get('errorMessages', 'noWordPressUserLoggedIn'));
				} else {

					// Store the account
					$storeSql = str_replace(array(
						'{wpdbPrefix}',
						'{nameSpace}',
						'{wpId}',
						'{returnedAccountId}',
						'{returnedAccountObject}'
					), array(
						$this->getDatabase()->prefix,
						$this->getNamespace(),
						$oUser->ID,
						$this->getRpcResponse()->user_id,
						mysql_real_escape_string(json_encode($this->getRpcResponse()))
					), Whv_Config::Get('sqlMiscQueries', 'storeOneightyAccount'));
					$x = $this->getDatabase()->query($storeSql);
				}
			}

		} else {

			// No POST data was found,
			// set the system error
			$this->setError(Whv_Config::Get('errorMessages', 'noPostData'));
		}
		return $this;
	}

	/**
	 * This method logs a user out of the syndicate
	 *
	 * @return bool
	 */
	public function doLogout() {

		// Grab the current user
		$oUser = wp_get_current_user();

		// Setup the sql
		$sSql = str_replace(array(
			'{wpdbPrefix}',
			'{nameSpace}',
			'{wpId}'
		), array(
			$this->getDatabase()->prefix,
			$this->getNamespace(),
			$oUser->ID
		), Whv_Config::Get('sqlMiscQueries', 'removeOneightyAccount'));

		// Try to run the query
		if ($this->getDatabase()->query($sSql)) {

			// The user has been
			// logged out, return
			return true;
		} else {

			// The user was not
			// logged out, return
			return false;
		}
	}

	/**
	 * This method handles the removal process
	 *
	 * @return boolean based on the success or failure of the removal
	 */
	public function doRemoveArticle() {

		// Check for POST data
		if ($this->checkForData('getPostData')) {

			// Store POST data
			$aPostData = $this->getPostData()->{$this->getNameSpace()};

			// Check for a 180Create Account
			if (!$this->checkForOneightyAccount()) {
				return false;
			}

			if ($this->checkIfOneightyArticle($aPostData['iWordPressId']) === true) {

				// Check to see if the author of
				// the article is logged into this
				// local WordPress instance
				if ($this->checkIfArticleBelongsToOneightyAccount() === true) {

					// Update Post
					wp_update_post(array(
						'ID' => $aPostData['iWordPressId'],
						'post_content' => $this->getArticle()->aSyndicationData[str_replace('{nameSpace}', $this->getNameSpace(), Whv_Config::Get('wordPress', 'postMetaKeyData'))]->content
					));

					// Remove post meta
					delete_post_meta($aPostData['iWordPressId'], str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyData')));
					delete_post_meta($aPostData['iWordPressId'], str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyId')));
					delete_post_meta($aPostData['iWordPressId'], str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyPullComments')));
					delete_post_meta($aPostData['iWordPressId'], str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicated')));
					delete_post_meta($aPostData['iWordPressId'], str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicationDate')));

					// Send the deactivation signal
					// to the RPC server
					$this->feedJson(array(
						'_method'    => 'deactivate_article',
						'_key'       => $this->getAccount()->account_key,
						'article_id' => $this->getArticle()->aSyndicationData[str_replace('{nameSpace}', $this->getNameSpace(), Whv_Config::Get('wordPress', 'postMetaKeyData'))]->article_id
					));


					// We're done, return
					return true;

				} else {

					// Try to remove the article
					// from the WordPress DB
					try {

						// Delete the post from,
						// the WordPress DB
						wp_delete_post($aPostData['iWordPressId'], true);

						// Catch all errors and exceptions
					} catch (Exception $oException) {

						// Set the system error to the
						// exception error message
						$this->setError($oException->getMessage());

						// Return false because
						// there was an error
						return false;
					}

					// Return true because the artice
					// was successfully removed
					return true;
				}
			} else {

				// Set the system error
				$this->setError(str_replace(array(
					'{wpId}', '{appName}'
				), array(
					$aPostData['iWordPressId'], Whv_Config::Get('variables', 'appName')
				), Whv_Config::Get('errorMessages', 'postNotFromSyndicate')));

				return false;
			}


			// No POST data was found
		} else {

			// Set system error
			$this->setError(Whv_Config::Get('errorMessages', 'noPostData'));

			// Return false because there
			// was an error
			return false;
		}
	}

	/**
	 * This method sanitizes and strips unwanted
	 * characters from the provided string
	 *
	 * @param string $sData is the string to sanitize
	 * @param string $sCustom is a custom expression to run
	 * @return string $sSanitized is the sanitized string
	 */
	public function doSanitize($sData, $sCustom = null) {

		// Sanitized string placeholder
		$sSanitized = null;

		// Check for a custom expression
		if (is_null($sCustom)) {

			// There is no custom expression,
			// run the default sanitization
			$sSanitized = mysql_real_escape_string(strip_tags($sData));
		} else {

			// Run the custom expression
			$sSanitized = mysql_real_escape_string(preg_replace($sCustom, '', $sData));
		}

		return $sSanitized;
	}

	/**
	 * This method handles the searching of
	 * the 180Create database
	 *
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function doSearch() {


		// POST data was found, check for
		// a user account
		if (!$this->checkForOneightyAccount()) {
			// Set the system error
			$this->setError(Whv_Config::Get('errorMessages', 'noOneightyAccount'));

			return $this;
		}

		// POST data was found, make the
		// JSON-RPC service call
		$aPostNameSpace = $this->getPostData()->{$this->getNameSpace()};
		//if the user just clicks the search page, we have no form post
		// so no 'whv' in the oPostData, we want to show
		// a few latest articles to them.
		
		if (empty($aPostNameSpace)) {
			$aPostNameSpace['limit'] = 10;
		}
		$_params = array(
			'_method'  => 'search',
			'_key'     => $this->getAccount()->account_key,
			'criteria' => $this->doSanitize($aPostNameSpace['txtCriteria'])
		);

		if (isset($aPostNameSpace['limit'])) {
			$_params['limit'] = $aPostNameSpace['limit'];
		}

		$this->feedJson($_params);

		// Check for a JSON-RPC error
		if (is_object($this->getRpcResponse()) && property_exists($this->getRpcResponse(), 'error')) {

			// Set the system error to the
			// JSON-RPC service error
			$this->setError($this->getRpcResponse()->error);
			return $this;
		}

		// No error was found, now
		// Check for actual results
		if (count($this->getRpcResponse())) {

			// Grab the current articles
			// in the local WordPress DB
			$sArticlesQuery = str_replace(array(
				'{wpdbPrefix}', '{nameSpace}'
			), array(
				$this->getDatabase()->prefix, $this->getNamespace()
			), Whv_Config::Get('sqlMiscQueries', 'retrieveOneightyArticles'));

			// Try to execute the SQL
			try {

				// Execute the SQL
				$aArticles = $this->getDatabase()->get_results($sArticlesQuery);

				// Check for results
				if (!count($aArticles)) {
					// Nothing has been syndicated yet
					$this->setSearchResults($this->getRpcResponse());
					return $this;
				}

				// We have results, create
				// the exclusion array
				$aExclude = array();

				// Loop through the local IDs
				// and append them
				foreach ($aArticles as $oArticle) {
					$aExclude[] = $oArticle->sOneightyId;
				}

				// Now loop through the search
				// results and check id the ID
				// is in the exclusion array
				$aSearchResults = $this->getRpcResponse();

				foreach ($aSearchResults as $iIndex => $oArticle) {

					// Check for ID
					if (in_array($oArticle->article_id, $aExclude)) {

						// If it is in the exclude array
						// unset the array index
					//	unset($aSearchResults[$iIndex]);
					}
				}

				// Set the system search results
				$this->setSearchResults($aSearchResults);

				return $this;

				// Catch all exceptions
			} catch (Exception $oException) {

				// Set the system error to the
				// exception message
				$this->setError($oException->getMessage());

				return $this;
			}

		} else {

			// No articles were found,
			// set the system error
			$this->setError(Whv_Config::Get('errorMessages', 'noSearchResults'));

			return $this;
		}

		return $this;
	}

	/**
	 * This method increments the syndication count of the articles
	 *
	 * @param string $sApiKey is the account holder's API key
	 * @param integer $iArticleId is the article id to increment statistics
	 * @param integer $iUserId is the account holder's account ID
	 * @return bool based on success or failure
	 */
	public function doSyndicatePlusOne($sApiKey, $iArticleId, $iUserId) {

		// Run the feed
		$this->feedJson(array(
			'_method'    => 'syndicate_plus_one',
			'_key'       => $sApiKey,
			'article_id' => $iArticleId,
			'user_id'    => $iUserId
		));

		// Check for an error
		if (! $error = $this->getRpcResponse()->error) {
			return true;
		} else {
			// We have an error, set it
			// into the system
			$this->setError($error);
			return false;
		}
	}

	/**
	 * This method is responsible syndicating
	 * content between WordPress and 180Create
	 *
	 * @param boolean $bToWordPress determines whether we are syndicating to WordPress or 180Create
	 * @return boolean based on the success or failure of the syndication
	 */
	public function doSyndicateToRpc() {

		// Check for an account
		if (!$this->checkForOneightyAccount()) {
			$this->setError(Whv_Config::Get('errorMessages', 'noOneightyAccount'));
			return false;
		}

		$aJsonRpcParams = array(
			'_method'              => 'post',
			'_key'                 => $this->getAccount()->account_key,
			'author_id'            => $this->getAccount()->user_id,
			'content'              => $this->getArticle()->sContent,
			'title'                => $this->getArticle()->sTitle,
			'description'          => $this->getArticle()->sExcerpt,
			'category_id'          => $this->getArticle()->iCategoryId,
			'secondcategory_id'    => $this->getArticle()->iSecondCategoryId,
			'subcategory_id'       => $this->getArticle()->iSubcategoryId,
			'secondsubcategory_id' => $this->getArticle()->iSecondSubcategoryId,
			'group_id'             => $this->getArticle()->iGroupId,
			'private'              => $this->getArticle()->iPrivate,
			'tag_words'            => $this->getArticle()->sTagWords,
			'cost'                 => $this->getArticle()->iCost,
			'allow_free'           => $this->getArticle()->iFree,
			'name'                 => $this->getArticle()->sName,
			'from_blog'            => get_bloginfo('name'),
			'from_url'             => get_bloginfo('url')
		);

		// Try to run the RPC request
		if (!$this->feedJson($aJsonRpcParams)) {
			return FALSE;
		}

		// Check fro RPC errors
		if (!empty($this->getRpcResponse()->error)) {

			// Set the system error
			$this->setError($this->getRpcResponse()->error);
			return false;
		}

		// No errors were found, all signs
		// point to the article being syndicated
		// now check for a WordPress Post ID
		if (empty($this->getArticle()->iWordPressId)) {
			$this->setError(Whv_Config::Get('errorMessages', 'noArticleToSyndicate'));
			return false;
		}


		// We have a WordPress Post
		// now update the post
		$this->getDatabase()->query(str_replace(array(
			'{wpdbPrefix}',
			'{iWordPressId}',
			'{sPostStatus}',
			'{sPostTitle}',
			'{sPostType}',
			'{sPostName}',
		), array(
			$this->getDatabase()->prefix,
			$this->getArticle()->iWordPressId,
			'publish',
			addslashes($this->getRpcResponse()->title),
			'post',
			addslashes($this->getRpcResponse()->name)
		), Whv_Config::Get('sqlMiscQueries', 'updateWordPressSyndicateUp')));

		// Add our post meta data
		add_post_meta($this->getArticle()->iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyData')),            mysql_real_escape_string(json_encode($this->getRpcResponse())), true);

		add_post_meta($this->getArticle()->iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyId')),              $this->doSanitize($this->getRpcResponse()->article_id),                 true);

		add_post_meta($this->getArticle()->iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyPullComments')),    true,                                                           true);
		add_post_meta($this->getArticle()->iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicationDate')), date('Y-m-d H:i:s'),                                            true);
		add_post_meta($this->getArticle()->iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicated')),      true,                                                           true);

		//syndicate comments
		//must be done after using $this->getRpcResponse() as
		// feedJson() sets a new one on good calls
		$this->doSyndicateComments($this->getArticle()->iWordPressId, $this->getRpcResponse()->article_id);


		// One up the syndication count
		return $this->doSyndicatePlusOne($this->getAccount()->account_key, $this->getRpcResponse()->article_id, $this->getAccount()->user_id);
	}


	public function doSyndicateComments($wpid, $whvid) {
		$comments = $this->getDatabase()->get_results("SELECT * FROM {$this->getDatabase()->prefix}comments WHERE comment_post_ID = ".addslashes($wpid)." AND comment_approved = 1");
		
		foreach ($comments as $cmnt) {
			$details = array(
				'_method'      => 'post_comment',
				'_key'         => $this->getAccount()->account_key,
				'article_id'   => $whvid,
				'author_id'    => $this->getAccount()->user_id,
				'author_name'  => $this->getAccount()->firstname. ' '.$this->getAccount()->firstname,
				'author_email' => $this->getAccount()->email_address,
				'author_url'   => $this->getAccount()->website,
//				'author_ip'    => $cmnt->comment_author_IP,
				'content'      => $cmnt->comment_content,
				'parent_id'    => $cmnt->comment_parent,
				'site_id'      => 0,
				'from_blog'    => get_bloginfo('name'),
				'from_url'     => get_bloginfo('url')
			);
			// Try to run the RPC request
			$posted = $this->feedJson($details);
		}
	}


	public function doSyndicateToWordPress() {

		if (empty($_POST) || empty($_POST[$this->getNameSpace()])) {
			$this->setError(Whv_Config::Get('errorMessages', 'noPostData'));
			return false;
		}
		// Set our POST data
		$aPostData = (array) $_POST[$this->getNameSpace()];

		// Check for user account
		if ($this->checkForOneightyAccount()) {

			// Make the JSON-RPC call
			$this->feedJson(array(
				'_method'    => 'fetch',
				'_key'       => $this->getAccount()->account_key,
				'article_id' => $this->doSanitize($aPostData['iArticleId'])
			));

			// Check for JSON-RPC errors
			if (property_exists($this->getRpcResponse(), 'error')) {

				// Set the system error
				// to the JSON-RPC error
				$this->setError($this->getRpcResponse()->error);

				// Return false because
				// there was an error
				return false;
			}

			// Create a new WordPress
			// post and store the ID
			$iWordPressId = wp_insert_post(array(
				'post_status'  => 'publish',
				'post_title'   => $this->doSanitize($this->getRpcResponse()->title),
				'post_type'    => 'post',
				'post_name'    => $this->doSanitize($this->getRpcResponse()->name),
				'post_content' => json_encode(array(
					'sOneightyId' => $this->doSanitize($this->getRpcResponse()->article_id)
				))
			));

			// Add the 180Create post meta data
			// into the local WordPress system
			add_post_meta($iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyData')),            mysql_real_escape_string(json_encode($this->getRpcResponse())), true);
			add_post_meta($iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyId')),              $this->doSanitize($this->getRpcResponse()->article_id),                 true);
			add_post_meta($iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyPullComments')),    true,                                                           true);
			add_post_meta($iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicationDate')), date('Y-m-d H:i:s'),                                            true);
			add_post_meta($iWordPressId, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicated')),      true,                                                           true);

			// All is well, now update
			// the syndication count
			$this->feedJson(array(
				'_method'    => 'syndicate_plus_one',
				'_key'       => $this->doSanitize($this->getAccount()->account_key),
				'article_id' => $this->doSanitize($aPostData['iArticleId']),
				'user_id'    => $this->doSanitize($this->getAccount()->id)
			));

			// Check for JSON-RPC errors
			if (property_exists($this->getRpcResponse(), 'error')) {
				$this->setError($this->getRpcResponse()->error);
				return false;
			}

			// Check for JSON-RPC success
			if (property_exists($this->getRpcResponse(), 'success') && $this->getRpcResponse()->success == true) {
				return true;
			} else {
				$this->setError(Whv_Config::Get('errorMessages', 'noSyndicatePlusOne'));
				return false;
			}
		} else {
			$this->setError(Whv_Config::Get('errorMessages', 'noOneightyAccount'));
			return false;
		}
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Checks      ///////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method run a specified getter method
	 * and check to see if any data was returned
	 *
	 * @package Checks
	 * @param string $sMethod the name of the method
	 * @return boolean
	 */
	public function checkForData($sMethod) {

		// Store the response
		$mResponse = call_user_func(array($this, $sMethod));

		// Call the method and
		// check for returned
		// dataset
		if (empty($mResponse)) {

			// If the dataset is
			// null or unset,
			// return false
			return false;
		} else {

			// If there is data,
			// return true
			return true;
		}
	}

	/**
	 * This method checks for any set errors
	 * and throws an exception if an error is
	 * set with the current error message as
	 * the exception message
	 *
	 * @package Checks
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function checkForErrors() {

		// Check for an error
		if ($this->checkForData('getError')) {

			// If there is an error,
			// throw an exception
			throw new Exception($this->getError());
		}
		return $this;
	}

	/**
	 * This method determines if there is a
	 * 180Create account stored in the local
	 * WordPress database
	 *
	 * @package Checks
	 * @return boolean
	 */
	public function checkForOneightyAccount() {

		// Grab the current user
		$oUser = wp_get_current_user();

		// Query for the account
		$aAccount = $this->getDatabase()->get_results(str_replace(array(
			'{wpdbPrefix}',
			'{nameSpace}',
			'{wpId}'
		), array(
			$this->getDatabase()->prefix,
			$this->getNamespace(),
			$oUser->ID
		), Whv_Config::Get('sqlMiscQueries', 'retrieveOneightyAccount')));

		// Check to see if the user has
		// a 180Create account
		if (count($aAccount)) {

			// This user has an account, set
			// the system account and
			// return true
			$this->setAccount(json_decode($aAccount[0]->oAccount));
			return true;
		} else {

			//this "Error" will show a huge red error box the first time anybody 
			// uses/sees this plugin.  This makes a bad UX, need to explain 
			// how to use the plugin w/o throwing an error in their face.
//			$this->setError(Whv_Config::Get('errorMessages', 'noOneightyAccount'));
			return false;
		}
	}

	/**
	 * This method checks to see if an article
	 * belongs to a currently stored account
	 *
	 * @return bool true for yes, false for no
	 */
	public function checkIfArticleBelongsToOneightyAccount() {

		// Query for the account
		$aAccount = $this->getDatabase()->get_results(str_replace(array(
			'{wpdbPrefix}',
			'{nameSpace}',
			'{sOneightyId}'
		), array(
			$this->getDatabase()->prefix,
			$this->getNamespace(),
			$this->getArticle()->aSyndicationData[str_replace('{nameSpace}', $this->getNameSpace(), Whv_Config::Get('wordPress', 'postMetaKeyData'))]->author_id
		), Whv_Config::Get('sqlMiscQueries', 'retrieveOneightyAccountByOneightyId')));

		// Check to see if the
		// account array is empty
		if (empty($aAccount)) {

			// This article does not belong to
			// a currently stored account,
			// return
			return false;
		} else {

			// This article does belong to
			// a currently stored account
			// return
			return true;
		}
	}

	/**
	 * This method determines if the
	 * provided WordPress post ID is
	 * a valid 180Create article
	 *
	 * @package Checks
	 * @return boolean
	 */
	public function checkIfOneightyArticle($iWordPressId) {

		$ns = $this->getNamespace();
		$pmks = str_replace('{nameSpace}', $ns, Whv_Config::Get('wordPress', 'postMetaKeySyndicated'));
		$pmkd = str_replace('{nameSpace}', $ns, Whv_Config::Get('wordPress', 'postMetaKeyData'));
		// Check for an article count
		if (!empty($iWordPressId) && (intval(get_post_meta($iWordPressId, $pmks, true)) == 1)) {

			// Grab the article from
			// the local database
			$oArticle = get_post($iWordPressId);


			// Create the 180Create data property
			$oArticle->aSyndicationData = array();

			// Loop through each of the
			// custom meta keys and check
			// to see if they are one of
			// our meta keys
			foreach (get_post_custom_keys($iWordPressId) as $sMetaKey) {

				// Make sure it is one of ours
				if (strpos($sMetaKey, $ns) !== false) {

					// It's one of ours, now
					// grab and set the value
					$oArticle->aSyndicationData[$sMetaKey] = get_post_meta($iWordPressId, $sMetaKey, true);
				}
			}

			// Decode the syndication data
			$oArticle->aSyndicationData[$pmkd] = json_decode($oArticle->aSyndicationData[$pmkd]);

			// Set the article into the system
			$this->setArticle($oArticle);

			// This is a 180Create
			// article, return true
			return true;
		} else {

			// This is not a 180Create
			// article, return false
			return false;
		}
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Load      /////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method is responsible for loading the
	 * menu into the WordPress admin interface
	 *
	 * @package Load
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadAdminMenu() {

		$_appName = Whv_Config::Get('variables', 'appName');
		// Setup the parent menu
		add_menu_page(
			$_appName,                                 // Page title
			$_appName,                                 // Menu title
			'administrator',                           // Permissions
			$this->getNameSpace(),                     // Page slug
			array($this, 'renderOneighty'),            // Function to render HTML
			"{$this->getPluginWebPath()}/".Whv_Config::Get('folders', 'images')."/".Whv_Config::Get('variables', 'pluginName').".png"	// Logo
		);

		// Setup Sub pages

		//Login/Accont
		add_submenu_page(
			$this->getNameSpace(),                     // Parent slug
			$_appName,   // Page title
			($this->checkForOneightyAccount() ? 'Account' : 'Login'),  // Menu title
			'administrator',                           // Permissions
			$this->getNameSpace(),                     // Slug
			array($this, 'renderOneighty')             // Function to render HTML
		);

		// Existing Content
		add_submenu_page(
			$this->getNameSpace(),                     // Parent slug
			$_appName.' Existing Content',             // Page title
			'Existing Content',                        // Menu title
			'administrator',                           // Permissions
			$this->getNameSpace().'_existing',         // Slug
			array($this, 'renderExistingContent')      // Function to render HTML
		);

		// Search
		add_submenu_page(
			$this->getNameSpace(),                     // Parent slug
			$_appName.' Article Search',               // Page title
			'Article Search',                          // Menu title
			'administrator',                           // Permissions
			$this->getNameSpace().'_search',           // Slug
			array($this, 'renderArticleSearch')        // Function to render HTML
		);

		// Syndicated Content
		add_submenu_page(
			$this->getNameSpace(),                     // Parent slug
			$_appName.' Syndicated Content',           // Page title
			'Syndicated Content',                      // Menu title
			'administrator',                           // Permissions
			$this->getNameSpace().'_syndicated',       // Slug
			array($this, 'renderSyndicatedContent')    // Function to render HTML
		);

		// Logout
		// we need this for permissions, blank menu title keeps it out of the menu
		add_submenu_page(
			$this->getNameSpace(),                     // Parent slug
			$_appName.' Logout',                       // Page title
			'',                                        // Menu title
			'administrator',                           // Permissions
			$this->getNameSpace().'_logout',           // Slug
			array($this, 'renderLogout')               // Function to render HTML
		);

		return $this;
	}

	/**
	 * This method retrieves an article from the 180Create
	 * table based on its associated WordPress ID
	 *
	 * @param integer $iWordPressId is the WordPress ID for the article
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadArticle($iWordPressId) {

		// Load the article
		$aArticles = $this->getDatabase()->get_results(str_replace(array(
			'{wpdbPrefix}',
			'{nameSpace}',
			'{wpId}'
		), array(
			$this->getDatabase()->prefix,
			$this->getNameSpace(),
			$this->doSanitize($iWordPressId)
		), Whv_Config::Get('sqlMiscQueries', 'retrieveOneightyArticle')));

		// Check to make sure we have
		// an article or article set
		if (count($aArticles)) {

			// Set the article into the system
			$this->setArticle(json_decode($aArticles[0]->oArticle));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * This method loads all of the syndicated
	 * articles into the system
	 *
	 * @package Load
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadArticles() {

		// Check for a 180Create Account
		if ($this->checkForOneightyAccount()) {

			// Try to execute the query
			try {

				// Execute the SQL and store the results
				$aArticles = get_posts(array(
					'post_type'   => 'post',
					'post_status' => 'publish',
					'numberposts' => 60,
					'meta_query'  => array(
						array(
							'key'     => str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicated')),
							'value'   => 1,
							'compare' => '='
						)
					)
				));

				// Set the articles to display
				// array placeholder
				$aDisplayArticles = array();

				// Catch all errors and exceptions
			} catch (Exception $oException) {

				// Set the system error
				$this->setError($oException->getMessage());
				return $this;
			}

			// Make sure we have articles
			if (count($aArticles)) {

				// Loop through each of the articles
				// and grab the meta data from them
				foreach ($aArticles as $oArticle) {

					// Make sure this is one
					// of our articles
					if ($this->checkIfOneightyArticle($oArticle->ID) === true) {

						// Add the article to the
						// array of articles
						$aArticles[] = $this->getArticle();
					}
				}

				// Set the system articles
				$this->setArticles($aArticles);
			} else {

				// There are no articles, so
				// set the system error
				$this->setError(Whv_Config::Get('errorMessages', 'noSyndicatedContent'));
			}
			return $this;
		}
	}

	/**
	 * This method is responsible for loadin the
	 * JavaScripts and Stylesheets into the system
	 *
	 * @package Load
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadAssets() {

		// Create our custom post type
		// register_post_type($this->getNamespace(), array(
		//  'publicly_queryable'  => true,
		//  'exclude_from_search' => false,
		//	'show_ui'             => false,
		//	'show_in_menu'        => $this->getNameSpace(),
		//	'capability_type'     => 'post',
		//  'show_in_nav_menus'   => true,
		//  'rewrite'             => true,
		//  'query_var'           => true,
		// ));

		// Register the latest jQueryUI Stylesheet from Google
		wp_register_style("{$this->getNamespace()}-jquery-ui-all-css", "{$this->getPluginWebPath()}/".Whv_Config::Get('styleSheets', 'jQueryUi'));

		// Load jQueryUI Stylesheet
		wp_enqueue_style("{$this->getNamespace()}-jquery-ui-all-css");


		if (is_admin() == false) {

			// Load jQuery libraries
			wp_enqueue_script('jquery');

			//  Register the latest jQueryUI Library
			wp_register_script("{$this->getNamespace()}-jquery-ui-all", "{$this->getPluginWebPath()}/".Whv_Config::Get('javaScripts', 'jQueryUi'));

			//  Register the latest jQueryUI Select Menu Plugin
			wp_register_script("{$this->getNamespace()}-jquery-ui-selectmenu", "{$this->getPluginWebPath()}/".Whv_Config::Get('javaScripts', 'jQueryUiSelectMenu'));

			// Register the latest jQuery Validation
			// plugin from the jQuery repository
			wp_register_script("{$this->getNamespace()}-jquery-validate", "{$this->getPluginWebPath()}/".Whv_Config::Get('javaScripts', 'jQueryValidate'));

			// Load our base functions
			wp_enqueue_script("{$this->getNamespace()}-base");

			// Load jQueryUI
			wp_enqueue_script("{$this->getNamespace()}-jquery-ui-all");

			// Load jQueryUi Select Menu
			wp_enqueue_script("{$this->getNamespace()}-jquery-ui-selectmenu");

			// Load jQuery Validate
			wp_enqueue_script("{$this->getNamespace()}-jquery-validate");

			// See if we are on post page
			if (!is_null(get_the_ID())) {
				if ($this->checkIfOneightyArticle(get_the_ID()) === true) {

					// Meta property
					$sMetaProperty = str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyData'));

					// Add in our meta tags
					echo("<meta name=\"syndication-source\" content=\"{$this->getArticle()->aSyndicationData[$sMetaProperty]->from_url}\">\n");
				}
			}
		} else {

			// Register our plugin's extra styles
			wp_register_style("{$this->getNamespace()}-extra-css", "{$this->getPluginWebPath()}/".Whv_Config::Get('styleSheets', 'base'));

			// Load our plugin's extra styles
			wp_enqueue_style("{$this->getNameSpace()}-extra-css");

			// Register our base functions
			wp_register_script("{$this->getNamespace()}-base", "{$this->getPluginWebPath()}/".Whv_Config::Get('javaScripts', 'base'));

			//  Register the latest jQueryUI Library
			wp_register_script("{$this->getNamespace()}-jquery-ui-all", "{$this->getPluginWebPath()}/".Whv_Config::Get('javaScripts', 'jQueryUi'));

			//  Register the latest jQueryUI Select Menu Plugin
//			wp_register_script("{$this->getNamespace()}-jquery-ui-selectmenu", "{$this->getPluginWebPath()}/".Whv_Config::Get('javaScripts', 'jQueryUiSelectMenu'));

			// Load our base functions
			wp_enqueue_script("{$this->getNamespace()}-base");

			// Register the latest jQuery Validation
			wp_register_script("{$this->getNamespace()}-jquery-validate", "{$this->getPluginWebPath()}/".Whv_Config::Get('javaScripts', 'jQueryValidate'));


			// Load jQueryUI
//			wp_enqueue_script("{$this->getNamespace()}-jquery-ui-all");
			wp_enqueue_script("jquery-ui-dialog");

			// Load jQueryUi Select Menu
//			wp_enqueue_script("{$this->getNamespace()}-jquery-ui-selectmenu");

			// Load jQuery Validate
			wp_enqueue_script("{$this->getNamespace()}-jquery-validate");

		}
		return $this;
	}

	/**
	 * This method load the categories from the
	 * 180Create RPC server
	 *
	 * @package Load
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadCategories() {

		// Check to see if there is a user logged in
		if (!$this->checkForOneightyAccount()) {
			return $this;
		}

		// If there is an account, make
		// the call to the RPC server
		$this->feedJson(array(
			'_method' => 'categories',
			'_key'    => $this->getAccount()->account_key
		));

		// Check for RPC errors
		if (is_object($this->getRpcResponse()) && property_exists($this->getRpcResponse(), 'error')) {

			// If there is an error,
			// set the system error
			$this->setError($this->getRpcResponse()->error);
		} else {

			// If there are no errors,
			// set the system categories
			$this->setCategories($this->getRpcResponse());
		}
		return $this;
	}

	/**
	 * This method loads the comments from 180Create
	 * into the system for the current post being viewed
	 *
	 * @package Load
	 * @param integer $sOneightyId is the article ID
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadComments() {

		// Check to see if the user is logged in
		if ($this->checkForOneightyAccount()) {

			// If there was a 180Create account
			// load the comments from rpc
			$this->feedJson(array(
				'_method'    => 'grab_comments',
				'_key'       => $this->getAccount()->account_key,
				'article_id' => $this->getArticle()->article_id
			));

			// Check for RPC errors
			if (is_object($this->getRpcResponse()) && property_exists($this->getRpcResponse(), 'error')) {

				// If there is an error,
				// set the system error
				$this->setError($this->getRpcResponse()->error);
				return $this;
			} else {

				// If there are no errors,
				// check to see if we have
				// any comments
				if (count($this->getRpcResponse())) {

					// If so, set them into the system
					$this->setComments($this->getRpcResponse());
				} else {

					// If not, return an error
					$this->setError(Whv_Config::Get('errorMessages', 'noCommentsFound'));
				}
				return $this;
			}
		} else {
			return $this;
		}
	}

	/**
	 * This method loads the current stored account's
	 * groups from 180Create into the system
	 *
	 * @package Load
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadGroups() {

		// Check for user account
		if ($this->checkForData('getAccount')) {

			// We have a user account,
			// now make the call to the
			// RPC server for the groups
			$this->feedJson(array(
				'_method' => 'groups',
				'_key'    => $this->doSanitize($this->getAccount()->account_key),
				'user_id' => $this->doSanitize($this->getAccount()->id)
			));

			// Check for JSON-RPC server errors
			if (is_object($this->getRpcResponse()) && property_exists($this->getRpcResponse(), 'error')) {

				// Check to see that we have
				// actual results
				if (is_array($this->getRpcResponse()) && count($this->getRpcResponse())) {

					// We have results, now
					// set the groups into
					// the system
					$this->setGroups($this->getRpcResponse());
					return $this;
				} else {

					// No groups were found,
					// set the system notification
					$this->setError(Whv_Config::Get('notificationMessages', 'noGroupsFound'));
					return $this;
				}

				// An error is present
			} else {

				// Set the system error
				$this->setError($this->getRpcResponse()->error);

				// Return false because there
				// was an error from the JSON
				// RPC server
				return $this;
			}

		} else {

			// Return false because
			// we have not categories
			return $this;
		}
	}

	/**
	 * This method loads our custom meta box
	 * into the WordPress interface
	 *
	 * @package Load
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadMetaBox() {

		if (function_exists('add_meta_box')) {

			// Add the meta_box to the system
			add_meta_box("{$this->getNamespace()}-post-meta-box", __(Whv_Config::Get('variables', 'appName').' Article Details', "{$this->getNameSpace()}-post-meta-box"), array($this, 'renderMetaBox'), 'post', 'side', 'high');
		}
	}

	/**
	 * This method loads all of the posts
	 * from the local WordPress DB
	 * into the system
	 *
	 * @package Load
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadPosts() {

		// Load the WordPress posts
		// into the system
		$aPosts   = get_posts(array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'numberposts' => 60
		));

		// Indices to remove array
		$aIndices = array();

		// Loop through the posts,
		// weeding out the ones
		// that are ours
		for ($iPost = 0; $iPost < count($aPosts); $iPost ++) {

			// Check to see if the post is ours
			if ($meta = get_post_meta($aPosts[$iPost]->ID, str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeySyndicated')))) {

				$x = $aPosts[$iPost];
				$x->isSyndicated = 1;
				$aPosts[$iPost] = $x;
				// Add the index to the posts to remove
				//				$aIndices[] = $iPost;
			}
		}

		// Loop through the indices
		// and unset them
		foreach ($aIndices as $iIndex) {

			// Unset the index
			//			unset($aPosts[$iIndex]);
		}

		// Set the posts into the system
		$this->setPosts($aPosts);
		return $this;
	}

	/**
	 * This method loads the subcategories
	 * from the 180Create RPC server
	 *
	 * @package Load
	 * @param integer $iCategoryId is the ID of the parent Category
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadSubcategories($iCategoryId) {

		// Check to see if there is a user logged in
		if ($this->checkForOneightyAccount()) {

			// If there is an account, make
			// the call to the RPC server
			$this->feedJson(array(
				'_method'     => 'subcategories',
				'_key'        => $this->getAccount()->account_key,
				'category_id' => $this->doSanitize($iCategoryId)
			));


			// Check for RPC errors
			if (is_object($this->getRpcResponse()) && property_exists($this->getRpcResponse(), 'error')) {

				// If there is an error,
				// set the system error
				$this->setError($this->getRpcResponse()->error);
				return $this;
			} else {

				// If there are no errors,
				// set the system categories
				$this->setSubcategories($this->getRpcResponse());
				return $this;
			}
		} else {

			// If there is no account,
			// set the system error
			$this->setError(Whv_Config::Get('errorMessages', 'noOneightyAccount'));
			return $this;
		}
	}

	/**
	 * This method loads and renders a template file.
	 * The scope of the template file is that of @var $this
	 *
	 * @package Load
	 * @param string $sTemplateFile is the name of the template file
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function loadTemplate($sTemplateFile) {

		// Make sure a template file was
		// actually specified
		if (is_null($sTemplateFile)) {

			// If not set the system error
			$this->setError(Whv_Config::Get('errorMessages', 'nullTemplateFile'));
			return $this;
		} else {

			// Set full filename
			$sTemplateFile = (string) $this->getPluginPath().'/'.Whv_Config::Get('folders', 'templates').'/'.$sTemplateFile.'.php';

			// Check to see if the file exists
			if (file_exists($sTemplateFile)) {

				// Load the template
				include_once($sTemplateFile);
				return $this;
			} else {

				// If the file doesn't exist,
				// set the system error
				$this->setError(Whv_Config::Get('errorMessages', 'templateFileNoExist'));

				// Load error template
				$this->loadTemplate("error");
				return $this;
			}
		}
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Render      ///////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method is responsible for rendering
	 * the article search form and results
	 *
	 * @package Render
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function renderArticleSearch() {

		if (!$this->checkForOneightyAccount()) {
			$this->renderOneighty();
			return $this;
		}

		$this->doSearch();
		// If so, load our template
		// for logged in users
		$this->loadTemplate("search");

		return $this;
	}

	/**
	 * This method renders a comment to the user
	 *
	 * @param object $oComment is a comment object
	 * @return Actions
	 */
	public function renderComment($oComment) {

		// Let's make sure we are loading
		// comments for a 180Create article
		if ($this->checkIfOneightyArticle(get_the_ID()) === true) {

			// Load our template
			$this->loadTemplate("comment");
		}
		return $this;
	}

	/**
	 * This method renders our comment form so that
	 * users may submit comments to 180Create
	 *
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function renderCommentForm() {

		// Let's make sure we are loading
		// comments for a 180Create article
		if ($this->checkIfOneightyArticle(get_the_ID()) === true) {

			// Load our template
			$this->loadTemplate("comment_form");
		}
		return $this;
	}

	/**
	 * This method loads our comments template
	 * instead of the default WordPress comments
	 * template file
	 *
	 * @param string $sTemplate is the comments template to load
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function renderComments($sTemplate) {

		// Check to see if there is a 180Create
		// account stored in the local DB
		if ($this->checkIfOneightyArticle(get_the_ID()) === true) {

			// If so, load our template
			// for logged in users
			//$sTemplate = "{$this->getPluginPath()}/".Whv_Config::Get('folders', 'templates')."/{$this->getNameSpace()}_comments.php";
			$sTemplate = "{$this->getPluginPath()}/".Whv_Config::Get('folders', 'templates')."/comments.php";
		}
		return $sTemplate;
	}

	/**
	 * This method is responsible for rendering
	 * the existing content page
	 *
	 * @package Render
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function renderExistingContent() {

		// Check for a 180Create Account
		if ($this->checkForOneightyAccount()) {

			// Check for POST
			if (!empty($_POST) && isset($_POST[$this->getNameSpace()])) {

				// Store OUR POST data
				$aPostData = $_POST[$this->getNameSpace()];

				// Check that the user actually
				// wishes to syndicate the article
				if ($this->checkIfOneightyArticle($aPostData['iWordPressId']) === false) {

					// Create the Article placeholder
					$oArticle = new stdClass();

					//var_dump($aPostData);
					//var_dump($oArticle);exit();

					// Load the post
					$oPost = get_post($aPostData['hdnWordPressId']);

					$oArticle->iWordPressId     = (integer) $aPostData['hdnWordPressId'];
					$oArticle->sContent         = (string) $oPost->post_content;
					$oArticle->sExcerpt         = (string) $oPost->post_excerpt;

					// See if we wish to use the
					// article prefix
					if (Whv_Config::Get('variables', 'enableArticleTitlePrefix')) {

						// If so, append the prefix to the
						// beginning of the article title
						$oArticle->sTitle = (string) Whv_Config::Get('variables', 'articleTitlePrefix').$oPost->post_title;
					} else {

						// If not, simple set the
						// article title as is
						$oArticle->sTitle = (string) $oPost->post_title;
					}

					// Add the second category
					$oArticle->iCategoryId       = (integer) $aPostData['selCategoryId'];

					// Add the second category
					$oArticle->iSecondCategoryId = (integer) (empty($aPostData['selSecondCategoryId']) ? 0 : $aPostData['selSecondCategoryId']);

					// See if we have subcategories enabled
					if (Whv_Config::Get('variables', 'enableSubCategories')) {

						// Add the category's subcategory
						$oArticle->iSubcategoryId       = (integer) (empty($aPostData['selSubcategoryId']) ? null : $aPostData['selSubcategoryId']);

						// Add the second category's subcategory
						$oArticle->iSecondSubcategoryId = (integer) (empty($aPostData['selSecondSubcategoryId']) ? null : $aPostData['selSecondSubcategoryId']);
					} else {

						// Add the category's subcategory
						$oArticle->iSubcategoryId       = null;

						// Add the second category's subcategory
						$oArticle->iSecondSubcategoryId = null;
					}

					// See if we have groups enabled
					if (Whv_Config::Get('variables', 'enableGroups')) {

						// Add the group
						$oArticle->iGroupId = (integer) (empty($aPostData['selGroupId']) ? 0 : $aPostData['selGroupId']);
					} else {

						// Add the group
						$oArticle->iGroupId = null;
					}

					// See if we have privacy settings enabled
					if (Whv_Config::Get('variables', 'enablePrivacySettings')) {

						// Add the privacy settings
						$oArticle->iPrivate = (integer) (empty($aPostData['rdoPrivate']) ? 0 : $aPostData['rdoPrivate']);

					} else {

						// Add the privacy setting
						$oArticle->iPrivate = (integer) 0;
					}

					// See if we have allow free enabled
					if (Whv_Config::Get('variables', 'enableAllowFree')) {

						// Add the privacy settings
						$oArticle->iFree = (integer) (is_null($aPostData['rdoAllowFree']) ? 0 : $aPostData['rdoAllowFree']);

					} else {

						// Add the privacy setting
						$oArticle->iFree = (integer) 1;
					}

					// See if we have cost selector enabled
					if (Whv_Config::Get('variables', 'enableCostSelect')) {

						// Add the privacy settings
						$oArticle->iCost = (integer) (is_null($aPostData['selCost']) ? 0 : $aPostData['selCost']);

					} else {

						// Add the privacy setting
						$oArticle->iCost = (integer) 0.00;
					}

					// Set the article name
					$oArticle->sName     = (string) $this->doSanitize(str_replace(' ', '-', strtolower($oPost->post_title)), '/[^a-zA-Z0-9-]+/');

					// Set article tag words
					$oArticle->sTagWords = (string) json_encode(array(
						$this->doSanitize($aPostData['txtTagWordA'], '/[^a-zA-Z0-9\s_-]+/'),	// Tag Word 1
						$this->doSanitize($aPostData['txtTagWordB'], '/[^a-zA-Z0-9\s_-]+/'), 	// Tag Word 2
						$this->doSanitize($aPostData['txtTagWordC'], '/[^a-zA-Z0-9\s_-]+/'), 	// Tag Word 3
						$this->doSanitize($aPostData['txtTagWordD'], '/[^a-zA-Z0-9\s_-]+/')	    // Tag Word 4
					));

					// Set the article
					// into the system
					$this->setArticle($oArticle);

					// We have added everything we need,
					// the doSyndicate method will do the
					// rest.  Try to see if we can run it
					// successfully
					$this->doSyndicateToRpc();
				} else {

					// Set system error
					$this->setError(Whv_Config::Get('errorMessages', 'articleAlreadyExists'));
				}
			}

			// Load posts into the system
			$this->loadPosts();

			// An account was found,
			// load our template
			$this->loadTemplate("existing");

			// No account was found
		} else {

			// Render login
			$this->renderOneighty();
		}
		return $this;
	}

	/**
	 * This method handles the views
	 * of the logout method
	 *
	 * @return Actions $this
	 */
	public function renderLogout() {

		// See if we can log
		// the user out
		if ($this->doLogout()) {

			// Load the default template
			$this->renderOneighty();
		} else {

			// Set the system error
			$this->setError(Whv_Config::Get('errorMessages', 'cannotLogout'));

			// Load the default template
			$this->renderOneighty();
		}
		return $this;
	}

	/**
	 * Update syndication statistics and redirect
	 * This should not be needed after 1.0.5
	 * @DEPRECATED
	 *
	 * @void
	 */
	public function renderStatSync() {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$userid = $_REQUEST['user-id'];
		$res = $wpdb->get_results('SELECT iWordPressId FROM `'.$prefix.'whv_accounts` WHERE `sOneightyId` = "'.addslashes($userid).'"');
		if (empty($res)) die(-1);
		$res = $wpdb->get_results('SELECT * FROM `'.$prefix.'postmeta` WHERE `meta_key` = "whvArticleData"');

		foreach ($res as $_r) {
			$obj =  json_decode( $_r->meta_value );
			$new = array();
			$new['aid1'] = $obj->article_id;
			$new['aid2'] = $obj->author_id;
			$new['date'] = $obj->date_created;
			echo json_encode($new);
			echo "\n";
		}
	}

	/**
	 * This method is responsible for rendering
	 * the post meta box
	 *
	 * @package Render
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function renderMetaBox() {

		// Add nonce field
		wp_nonce_field(plugin_basename(__FILE__), "{$this->getNameSpace()}_nonce_field");

		// Load our template
		$this->loadTemplate("metabox");
	}

	/**
	 * This method is responsible for rendering
	 * the parent admin page
	 *
	 * @package Render
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function renderOneighty() {
		// Check for POST data
		if ($this->checkForData('getPostData') && property_exists($this->getPostData(), $this->getNamespace())) {

			// Clear the error
			$this->setError(null);

			// If a form has been submitted,
			// run the login actions
			$this->doLogin();
		}

		// Check to see if there is a 180Create
		// account stored in the local DB
		if ($this->checkForOneightyAccount()) {

			// If so, load our template
			// for logged in users
			$this->loadTemplate("{$this->getNameSpace()}");
		} else {

			// No account is there, load our
			// template for non-logged in users
			$this->loadTemplate("login");
		}
		return $this;
	}

	public function renderPostAuthor($sAuthor) {

		// Make sure this is one of our articles
		if ($this->checkIfOneightyArticle(get_the_ID()) === true) {

			// Meta property
			$sMetaProperty = str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyData'));

			// Set the title
			$sAuthor = $this->getArticle()->aSyndicationData[$sMetaProperty]->author_name;
		}

		// Return the author, whether
		// or not it has been modified
		return $sAuthor;
	}

	public function renderPostContent($sContent) {

		// Make sure this is one of our articles
		if ($this->checkIfOneightyArticle(get_the_ID()) === true) {

			// Meta property
			$sMetaProperty = str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyData'));

			// Set the title
			$sContent  = $this->getArticle()->aSyndicationData[$sMetaProperty]->content;
//			$sContent .= '<p><center>Originally published at: <a href="'.$this->getArticle()->aSyndicationData[$sMetaProperty]->from_url.'">'.$this->getArticle()->aSyndicationData[$sMetaProperty]->from_blog.'</a></center></p>';
			$sContent .= '<p><center><a href="'.Whv_Config::Get('urls', 'baseUrl').'">';
			$sContent .= '<img style="vertical-align:top" src="'.Whv_Config::Get('urls', 'baseUrl').'/images/syndicated.png" alt="Syndicated at '.Whv_Config::Get('variables', 'appName').'">';
	//		$sContent .= '</a></center></p>';
			$sContent .= '</a>from:&nbsp;<a href="'.$this->getArticle()->aSyndicationData[$sMetaProperty]->from_url.'">'.$this->getArticle()->aSyndicationData[$sMetaProperty]->from_blog.'</a></center></p>';
//			$sContent .= '<script type="text/javascript">';
//			$sContent .= 'jQuery(\'.entry-meta\').html(\'<p><em>By </em><strong><a href="'.Whv_Config::Get('urls', 'baseUrl').'/user/'.$this->getArticle()->aSyndicationData[$sMetaProperty]->author_name.'">'.$this->getArticle()->aSyndicationData[$sMetaProperty]->author_name.'</a></strong><em>, Originally Published at </em><a href="'.$this->getArticle()->aSyndicationData[$sMetaProperty]->from_url.'">'.$this->getArticle()->aSyndicationData[$sMetaProperty]->from_url.'</a></p>\');';
//			$sContent .= '</script>';
		}

		// Return the content, whether
		// or not it has been modified
		return $sContent;
	}

	public function renderPostTitle($sTitle) {

		// Make sure this is one of our articles
		if ($this->checkIfOneightyArticle(get_the_ID()) === true) {

			// Meta property
			$sMetaProperty = str_replace('{nameSpace}', $this->getNamespace(), Whv_Config::Get('wordPress', 'postMetaKeyData'));

			// Set the title
			$sTitle = $this->getArticle()->aSyndicationData[$sMetaProperty]->title;
		}

		// Return the title, whether
		// or not it has been modified
		return $sTitle;
	}

	/**
	 * This method is responsible for rendering
	 * the syndicated content page
	 *
	 * @package Render
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function renderSyndicatedContent() {

		// Check for a 180Create Account
		if ($this->checkForOneightyAccount()) {

			// Load articles into memory
			$this->loadArticles();

			// An account was found,
			// load our template
			$this->loadTemplate("syndicated");

			// No account was found
		} else {

			// Render login
			$this->renderOneighty();
		}

		// Return instance
		return $this;
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Handlers      /////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method intercepts an array of comments from
	 * WordPress and rewrites it into an array of
	 * 180Create Comments
	 *
	 * @param array $aComments
	 * @return array $aComments
	 */
	public function handleComments($aComments) {

		// Make sure we are loading comments
		// for a 180Create Article
		if ($this->checkIfOneightyArticle(get_the_ID()) === true) {

			// Load the comments
			$this->loadComments();

			// Load the comments array
			// template to process them
			$this->loadTemplate("comments_array");
		}

		// Return the array of comments
		// regardless of changes made
		return $aComments;
	}

	/**
	 * This method handles the meta_box post data
	 *
	 * @package Handlers
	 * @param integer $iWordPressId
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function handlePostData($iWordPressId) {

		// Make sure that it is not simply
		// doing an auto_save
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {

			// If doing an auto_save, simply
			// return instance
			return;
		}

		// Verify permissions
		if (property_exists($this->getPostData(), 'post_type') && ('page' == $this->getPostData()->post_type)) {
			if (!current_user_can('edit_page', $iWordPressId)) {

				// Return instance
				return;
			}
		} else {
			if (!current_user_can('edit_page', $iWordPressId)) {

				// Return instance
				return;
			}
		}

		// Make sure we actually wish to syndicate
		if (empty($_POST[$this->getNamespace()]['rdoSyndicate']) || ($_POST[$this->getNameSpace()]['rdoSyndicate'] == 0)) {
			return;
		}

		// Make sure we are actually trying to publish the post
		if (isset($_POST['publish']) && ($_POST['publish'] == 'Publish') && isset($_POST['post_ID'])) {

			// Check for OUR POST data
			if (isset($_POST[$this->getNameSpace()])) {

				// Store OUR POST data
				$aPostData = $_POST[$this->getNameSpace()];

				// Check that the user actually
				// wishes to syndicate the article
				if (isset($aPostData['rdoSyndicate']) && ($aPostData['rdoSyndicate'] == 1) && ($this->checkIfOneightyArticle($iWordPressId) === false)) {

					// Double check that we actually have content
					if (!empty($_POST['content'])) {

						// Create the Article placeholder
						$oArticle = new stdClass();

						// Add the WordPress ID
						$oArticle->iWordPressId     = (integer) $iWordPressId;

						// Add the content
						$oArticle->sContent         = (string) $this->getPostData()->content;

						// Add the excerpt
						$oArticle->sExcerpt         = (string) $this->getPostData()->excerpt;

						// See if we wish to use the
						// article prefix
						if (Whv_Config::Get('variables', 'enableArticleTitlePrefix')) {

							// If so, append the prefix to the
							// beginning of the article title
							$oArticle->sTitle = (string) Whv_Config::Get('variables', 'articleTitlePrefix').$_POST['post_title'];
						} else {

							// If not, simple set the
							// article title as is
							$oArticle->sTitle = (string) $_POST['post_title'];
						}

						// Add the second category
						$oArticle->iCategoryId       = (integer) $aPostData['selCategoryId'];

						// Add the second category
						$oArticle->iSecondCategoryId = (integer) (empty($aPostData['selSecondCategoryId']) ? 0 : $aPostData['selSecondCategoryId']);

						// See if we have subcategories enabled
						if (Whv_Config::Get('variables', 'enableSubCategories')) {

							// Add the category's subcategory
							$oArticle->iSubcategoryId       = (integer) (empty($aPostData['selSubcategoryId']) ? null : $aPostData['selSubcategoryId']);

							// Add the second category's subcategory
							$oArticle->iSecondSubcategoryId = (integer) (empty($aPostData['selSecondSubcategoryId']) ? null : $aPostData['selSecondSubcategoryId']);
						} else {

							// Add the category's subcategory
							$oArticle->iSubcategoryId       = null;

							// Add the second category's subcategory
							$oArticle->iSecondSubcategoryId = null;
						}

						// See if we have groups enabled
						if (Whv_Config::Get('variables', 'enableGroups')) {

							// Add the group
							$oArticle->iGroupId = (integer) (empty($aPostData['selGroupId']) ? 0 : $aPostData['selGroupId']);
						} else {

							// Add the group
							$oArticle->iGroupId = null;
						}

						// See if we have privacy settings enabled
						if (Whv_Config::Get('variables', 'enablePrivacySettings')) {

							// Add the privacy settings
							$oArticle->iPrivate = (integer) (empty($aPostData['rdoPrivate']) ? 0 : $aPostData['rdoPrivate']);

						} else {

							// Add the privacy setting
							$oArticle->iPrivate = (integer) 0;
						}

						// See if we have allow free enabled
						if (Whv_Config::Get('variables', 'enableAllowFree')) {

							// Add the privacy settings
							$oArticle->iFree = (integer) (is_null($aPostData['rdoAllowFree']) ? 0 : $aPostData['rdoAllowFree']);

						} else {

							// Add the privacy setting
							$oArticle->iFree = (integer) 1;
						}

						// See if we have cost selector enabled
						if (Whv_Config::Get('variables', 'enableCostSelect')) {

							// Add the privacy settings
							$oArticle->iCost = (float) (is_null($aPostData['selCost']) ? 0 : $aPostData['selCost']);

						} else {

							// Add the privacy setting
							$oArticle->iCost = (float) 0.00;
						}

						// Set the article name
						$oArticle->sName     = (string) $this->doSanitize(str_replace(' ', '-', strtolower($_POST['post_title'])), '/[^a-zA-Z0-9-]+/');

						// Set article tag words
						$oArticle->sTagWords = (string) json_encode(array(
							$this->doSanitize($aPostData['txtTagWordA'], '/[^a-zA-Z0-9\s_-]+/'),	// Tag Word 1
							$this->doSanitize($aPostData['txtTagWordB'], '/[^a-zA-Z0-9\s_-]+/'), 	// Tag Word 2
							$this->doSanitize($aPostData['txtTagWordC'], '/[^a-zA-Z0-9\s_-]+/'), 	// Tag Word 3
							$this->doSanitize($aPostData['txtTagWordD'], '/[^a-zA-Z0-9\s_-]+/')		// Tag Word 4
						));

						// Set the article
						// into the system
						$this->setArticle($oArticle);

						// We have added everything we need,
						// the doSyndicate method will do the
						// rest.  Try to see if we can run it
						// successfully
						if ($this->checkIfOneightyArticle($this->getArticle()->iWordPressId) === false) {

							// Run the syndication
							if ($this->doSyndicateToRpc()) {
								return $iWordPressId;
							} else {

								// Set the system error
								$this->setError(Whv_Config::Get('errorMessages', 'unableToSyndicateFromWordPress'));
								return;
							}
						} else {

							// Set the system error
							$this->setError(Whv_Config::Get('errorMessages', 'unableToSyndicateFromWordPress'));
							return;
						}
					} else {

						// Set the system error
						$this->setError(Whv_Config::Get('errorMessages', 'noPostBody'));
					}
				}
			}
		}

		// Just in case we forgot
		return;
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Generators      ///////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method generates a category dropdown
	 * select via @method generateHtmlDropdown
	 *
	 * @param boolean $bSecondCategory tells the method whether this is the primary or secondary category select
	 * @return string is the string of HTML for the dropdown
	 */
	public function generateCategoriesDropdown($bSecondCategory = false) {

		// Load categories
		$this->loadCategories();

		// Check for categories
		if ($this->checkForData('getCategories')) {

			// We have categories, now
			// create the placeholder
			$aCategories = array(
				new stdClass()
			);

			$aCategories[0]->sValue = null;
			$aCategories[0]->sLabel = 'Please choose a category';

			// Loop through each of the categories
			// and create an object out of them
			// then append them to the array
			foreach ($this->getCategories() as $oCategory) {

				// Create the object
				$oCat = new stdClass();

				// Set the value
				$oCat->sValue = $oCategory->id;

				// Set the label
				$oCat->sLabel = $oCategory->label;

				// Push the category to the array
				array_push($aCategories, $oCat);
			}


			// Check to see whether this is the
			// primary category or the secondary
			// category dropdown select

			if ($bSecondCategory === false) {

				// Return the primary generated dropdown
				return $this->generateHtmlDropdown('selCategoryId', 'selCategoryId', $aCategories, array(
					'style' => 'width:250px;'
				));
			} else {

				// Return the secondary generated dropdown
				return $this->generateHtmlDropdown('selSecondCategoryId', 'selSecondCategoryId', $aCategories, array(
					'style' => 'width:250px;'
				));
			}

		} else {

			// There are no categories,
			// set system errors
			$this->setError(Whv_Config::Get('errorMessages', 'noCategoriesLoaded'));
			return null;
		}
	}

	/**
	 * This method generates a cost dropdown
	 * via @method generateHtmlDropdown
	 *
	 * @return string is the HTML for the dropdown
	 */
	public function generateCostDropdown() {

		// Create the Data Provider
		$aDataProvider = array(
			'Free'   => 0.00,
			'1.00'  => 1.00,
			'2.50'  => 2.50,
			'5.00'  => 5.00,
			'7.50'  => 7.50,
			'10.00' => 10.00,
			'12.50' => 12.50,
			'15.00' => 15.00,
			'17.50' => 17.50,
			'20.00' => 20.00,
			'25.00' => 25.00,
			'30.00' => 30.00,
			'35.00' => 35.00,
			'40.00' => 40.00,
			'45.00' => 45.00,
			'50.00' => 50.00
		);

		// Return the generated HTML dropdown
		return $this->generateHtmlDropdown('selCost', 'selCost', $aDataProvider, array(
			'style' => 'width:100px'
		));
	}

	/**
	 * This method generates an HTML dropdown of
	 * the stored 180Create Account's groups via
	 * @method generateHtmlDropdown
	 *
	 * @return string is the HTML dropdown
	 */
	public function generateGroupsDropdown() {

		// Load groups
		$this->loadGroups();

		if ($this->checkForData('getGroups')) {

			// We have groups, now
			// create the placeholder
			$aGroups = array(
				new stdClass()
			);

			$aGroups[0]->sValue = null;
			$aGroups[0]->sLabel = 'Please choose a group';

			// Loop through each of the groups
			// and create an object out of them
			// then append them to the array
			foreach ($this->getGroups() as $oGroup) {

				// Create the object
				$oGroup = new stdClass();

				// Set the value
				$oGroup->sValue = $oGroup->id;

				// Set the label
				$oGroup->sLabel = $oGroup->name;

				// Push the groups to the array
				array_push($aGroupss, $oGroup);
			}

			// Return the generated
			// groups dropdown HTML
			return $this->generateHtmlDropdown('selGroupId', 'selGroupId', $aGroups, array(
				'style' => 'width:250px;'
			));

		} else {

			// Set the system error
			$this->setError(Whv_Config::Get('notificationMessages', 'noGroupsFound'));

			// Return null, because we are unable
			// to build a dropdown because the
			// current account is not a member
			// of any groups
			return null;
		}

	}

	/**
	 * This method generates an HTML button
	 *
	 * @package Generators
	 * @param string $sName is the name of the button
	 * @param string $sIdentifier is the unique client side id of the button
	 * @param string $sValue is the value the append to the button
	 * @param array $aAttributes is an array of non-standard attributes to add to the button
	 * @return string $sHtml is the fully generated button
	 */
	public function generateHtmlButton($sName, $sIdentifier, $sValue, $aAttributes = array()) {
		$sHtml = "<button name=\"{$this->getNameSpace()}[{$sName}]\" id=\"{$this->getNameSpace()}-{$sIdentifier}\" ";

		foreach ($aAttributes as $sKey => $sVal) {

			// Append the attribute
			$sHtml .= "{$sKey}=\"{$sVal}\" ";
		}
		$sHtml .= ">{$sValue}</button>";
		return $sHtml;
	}

	/**
	 * This method generates an HTML dropdown from
	 * the provided data provider @param $aDataProvider.
	 *
	 * @package Generators
	 * @param string $sName is the name of the dropdown
	 * @param string $sIdentifier is the unique identifier of the dropdown
	 * @param array $aDataProvider provides the data for the options
	 * @param array $aAttributes is an array of extra attributes
	 * @return string $sHtml is the completed HTML string
	 */
	public function generateHtmlDropdown($sName, $sIdentifier,array $aDataProvider,array $aAttributes = array()) {
		$sHtml = "<select name=\"{$this->getNamespace()}[{$sName}]\" id=\"{$this->getNamespace()}-{$sIdentifier}\" ";

		foreach ($aAttributes as $sKey => $sVal) {
			$sHtml .= "{$sKey}=\"{$sVal}\" ";
		}
		$sHtml .= ">";

		// Check to see if the Data Provider
		// is an array of objects or an
		// array of arrays
		if (isset($aDataProvider[0]) && is_object($aDataProvider[0])) {

			// Parse the data provider
			foreach ($aDataProvider as $oDataEntry) {

				// Option placeholder
				$sOption = "<option value=\"{$oDataEntry->sValue}\" ";

				// Check for a selected propert
				if (property_exists($oDataEntry, 'bSselected') && $oDataEntry->bSelected === true) {

					// If this option is to be selected by default,
					// append that attribute to the option
					$sOption .= "selected=\"selected\" ";
				}

				// Finish the option tag
				$sOption .= ">{$oDataEntry->sLabel}</option>";

				// Append the option to the select
				$sHtml .= (string) $sOption;
			}
		} else {

			// Parse the data provider
			foreach ($aDataProvider as $sLabel => $sValue) {

				// Option placeholder
				$sOption = "<option value=\"{$sValue}\" ";

				// Finish the option tag
				$sOption .= ">{$sLabel}</option>";

				// Append the option to the select
				$sHtml .= (string) $sOption;
			}
		}

		// Finish the dropdown
		$sHtml .= "</select>";

		// Return the string
		return $sHtml;
	}

	/**
	 * This method generates a self closing
	 * HTML form field
	 *
	 * @package Generators
	 * @param string $sType is the type of the field (text, file, checkbox, radio, hidden)
	 * @param string $sName is the name of the field
	 * @param string $sIdentifier is the unique identifier
	 * @param string $aAttributes is an array of attributes
	 * @return string $sHtml is the generated form field
	 */
	public function generateHtmlFormField($sType, $sName, $sIdentifier,array $aAttributes = array()) {
		$sHtml = "<input type=\"{$sType}\" name=\"{$this->getNamespace()}[{$sName}]\" id=\"{$this->getNamespace()}-{$sIdentifier}\" ";
		foreach ($aAttributes as $sKey => $sVal) {
			$sHtml .= "{$sKey} =\"{$sVal}\" ";
		}
		$sHtml .= ">";
		return $sHtml;
	}

	/**
	 * This method generates a subcategory category dropdown
	 * select via @method generateHtmlDropdown
	 *
	 * @param integer $iCategoryId is the ID of the parent category
	 * @param boolean $bSecondCategory tells the method whether this is the primary or secondary category select
	 * @return string is the string of HTML for the dropdown
	 */
	public function generateSubcategoriesDropdown($iCategoryId, $bSecondCategory = false) {

		// Load categories
		$this->loadSubcategories($this->doSanitize($iCategoryId));

		// Check for categories
		if (!$this->checkForData('getSubcategories')) {
			$this->setError(Whv_Config::Get('errorMessages', 'noSubcategoriesLoaded'));
			return '';
		}

		$aCategories = array();

		// Loop through each of the categories
		// and create an object out of them
		// then append them to the array
		foreach ($this->getSubcategories() as $oCategory) {

			$oCat = new stdClass();
			$oCat->sValue = $oCategory->id;
			$oCat->sLabel = $oCategory->label;
			array_push($aCategories, $oCat);
		}


		// Check to see whether this is the
		// primary category or the secondary
		// category dropdown select
		if ($bSecondCategory == false) {

			// Return the primary generated dropdown
			return $this->generateHtmlDropdown('selSubcategoryId', 'selSubcategoryId', $aCategories, array(
				'style' => 'width:250px;'
			));
		} else {

			// Return the secondary generated dropdown
			return $this->generateHtmlDropdown('selSecondSubcategoryId', 'selSecondSubcategoryId', $aCategories, array(
				'style' => 'width:250px;'
			));
		}

	}

	////////////////////////////////////////////////////////////////////////
	//////////      Parsers      //////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method parses AJAX requests to the
	 * proper ajax handler method
	 *
	 * @return mixed method based on ajax route
	 */
	public function parseAjax() {

		$sReturnJson = null;

		// Check for POST
		if ($this->checkForData('getPostData')) {

			// Set our specific data
			$aPostData = $this->getPostData()->{$this->getNamespace()};

			// Check for a route
			if (isset($aPostData['sRoute']) && !is_null($aPostData['sRoute'])) {

				// Check for method
				if (method_exists($this, "__{$this->doSanitize($aPostData['sRoute'])}")) {

					// Method exists, call it
					$sReturnJson = call_user_func(array(
						$this,
						"__{$this->doSanitize($aPostData['sRoute'])}"
					));
				} else {

					// Method does not exist,
					// set system error
					$this->setError(str_replace('{ajaxMethod}', $aPostData['sRoute'], Whv_Config::Get('errorMessages', 'ajaxMethodNoExist')));

					// Return unsuccessful ajax
					$sReturnJson = json_encode(array(
						'bSuccess' => false,
						'sError'   => $this->getError()
					));
				}

			} else {

				// No route was specified,
				// set the system error
				$this->setError(Whv_Config::Get('errorMessages', 'noAjaxRoute'));

				// Return unsuccessful ajax
				$sReturnJson = json_encode(array(
					'bSuccess' => false,
					'sError'   => $this->getError()
				));
			}

		} else {

			// No POST data was found,
			// set system error
			$this->setError(Whv_Config::Get('errorMessages', 'noPostData'));

			// Return unsuccessful Ajax
			$sReturnJson = json_encode(array(
				'bSuccess' => false,
				'sError'   => $this->getError()
			));
		}

		// Return output
		die($sReturnJson);
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Ajaxers      //////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method is responsible for handling
	 * the subcategories dropdown
	 *
	 * @package Ajaxers
	 * @return string $sReturnJson is the json to return to the caller
	 */
	private function __ajaxGetSubcategoriesSelect() {

		// Set JSON Placeholder
		$sReturnJson = null;

		// Store POST data
		$aPostData = $this->getPostData()->{$this->getNamespace()};

		// See if we get HTML
		if ($this->generateSubcategoriesDropdown($aPostData['iCategoryId'], $aPostData['bSecondSubCategory'])) {

			// If so, return
			// successful ajax
			$sReturnJson = json_encode(array(
				'bSuccess' => true,
				'sHtml'    => $this->generateSubcategoriesDropdown($aPostData['iCategoryId'], $aPostData['bSecondSubCategory'])
			));

			// We have an error
		} else {

			// Return unsuccessful ajax
			$sReturnJson = json_encode(array(
				'bSuccess' => false,
				'sError'   => $this->getError()
			));
		}

		// Return
		return $sReturnJson;
	}

	/**
	 * This method is responsible for handling
	 * the Article Deletion ajax request
	 *
	 * @package Ajaxers
	 * @return string $sReturnJson is the json to return to the caller
	 */
	private function __ajaxRemoveArticle() {

		// Set JSON Placeholder
		$sReturnJson = null;

		// Try to remove the desired
		// article from WordPress
		if ($this->doRemoveArticle() === true) {

			// The article was removed,
			// return successful ajax
			$sReturnJson = json_encode(array(
				'bSuccess' => true,
				'sMessage' => Whv_Config::Get('successMessages', 'removedFromWordPress')
			));

		} else {

			// Return unsuccessful ajax
			$sReturnJson = json_encode(array(
				'bSuccess' => false,
				'sError'   => $this->getError()
			));
		}

		// Return our JSON response
		return $sReturnJson;
	}

	public function __ajaxSyndicateArticle() {

		// Return JSON placeholder
		$sReturnJson = null;

		// Try to syndicate the desired
		// article to WordPress
		if ($this->doSyndicateToWordPress()) {

			// The article syndicated,
			// return successful ajax
			$sReturnJson = json_encode(array(
				'bSuccess' => true,
				'sMessage' => Whv_Config::Get('successMessages', 'syndicatedToWordPress')
			));

		} else {

			$originalError = $this->getError();
			$this->setError(Whv_Config::Get('errorMessages', 'noSyndicateToWordPress'). '['.$originalError.']');

			// Return unsuccessful ajax
			$sReturnJson = json_encode(array(
				'bSuccess' => false,
				'sError'   => $this->getError()
			));
		}

		// Return our JSON response
		return $sReturnJson;
	}

	/**
	 * This method is responsible for handling
	 * the Syndicate Article ajax request
	 *
	 * @package Ajaxers
	 * @return string $sReturnJson is the json to return to the caller
	 */
	private function __ajaxSyndicateExistingPost() {

		// Return placeholder
		$aReturnJson = array();

		// Check for OUR POST data
		if (isset($_POST[$this->getNameSpace()])) {

			// Store OUR POST data
			$aPostData = $_POST[$this->getNameSpace()];

			// Check that the user actually
			// wishes to syndicate the article
			if ($this->checkIfOneightyArticle($aPostData['iWordPressId']) === false) {

				// Create the Article placeholder
				$oArticle = new stdClass();

				// Load the post
				$oPost = get_post($aPostData['iWordPressId']);

				// Add the WordPress ID
				$oArticle->iWordPressId     = (integer) $aPostData['iWordPressId'];

				// Add the content
				$oArticle->sContent         = (string) $oPost->post_content;

				// Add the excerpt
				$oArticle->sExcerpt         = (string) $oPost->post_excerpt;

				// See if we wish to use the
				// article prefix
				if (Whv_Config::Get('variables', 'enableArticleTitlePrefix')) {

					// If so, append the prefix to the
					// beginning of the article title
					$oArticle->sTitle = (string) Whv_Config::Get('variables', 'articleTitlePrefix').$oPost->post_title;
				} else {

					// If not, simple set the
					// article title as is
					$oArticle->sTitle = (string) $oPost->post_title;
				}

				// Add the second category
				$oArticle->iCategoryId       = (integer) $aPostData['iCategoryId'];

				// Add the second category
				$oArticle->iSecondCategoryId = (integer) (empty($aPostData['iSecondCategoryId']) ? 0 : $aPostData['iSecondCategoryId']);

				// See if we have subcategories enabled
				if (Whv_Config::Get('variables', 'enableSubCategories')) {

					// Add the category's subcategory
					$oArticle->iSubcategoryId       = (integer) (empty($aPostData['iSubcategoryId']) ? null : $aPostData['iSubcategoryId']);

					// Add the second category's subcategory
					$oArticle->iSecondSubcategoryId = (integer) (empty($aPostData['iSecondSubcategoryId']) ? null : $aPostData['iSecondSubcategoryId']);
				} else {

					// Add the category's subcategory
					$oArticle->iSubcategoryId       = null;

					// Add the second category's subcategory
					$oArticle->iSecondSubcategoryId = null;
				}

				// See if we have groups enabled
				if (Whv_Config::Get('variables', 'enableGroups')) {

					// Add the group
					$oArticle->iGroupId = (integer) (empty($aPostData['iGroupId']) ? 0 : $aPostData['iGroupId']);
				} else {

					// Add the group
					$oArticle->iGroupId = null;
				}

				// See if we have privacy settings enabled
				if (Whv_Config::Get('variables', 'enablePrivacySettings')) {

					// Add the privacy settings
					$oArticle->iPrivate = (integer) (empty($aPostData['bPrivate']) ? 0 : $aPostData['bPrivate']);

				} else {

					// Add the privacy setting
					$oArticle->iPrivate = (integer) 0;
				}

				// See if we have allow free enabled
				if (Whv_Config::Get('variables', 'enableAllowFree')) {

					// Add the privacy settings
					$oArticle->iFree = (integer) (is_null($aPostData['bAllowFree']) ? 0 : $aPostData['bAllowFree']);

				} else {

					// Add the privacy setting
					$oArticle->iFree = (integer) 1;
				}

				// See if we have cost selector enabled
				if (Whv_Config::Get('variables', 'enableCostSelect')) {

					// Add the privacy settings
					$oArticle->iCost = (integer) (is_null($aPostData['iCost']) ? 0 : $aPostData['iCost']);

				} else {

					// Add the privacy setting
					$oArticle->iCost = (integer) 0.00;
				}

				// Set the article name
				$oArticle->sName     = (string) $this->doSanitize(str_replace(' ', '-', strtolower($oPost->post_title)), '/[^a-zA-Z0-9-]+/');

				// Set article tag words
				$oArticle->sTagWords = (string) json_encode(array(
					$this->doSanitize($aPostData['aTagWords'][0], '/[^a-zA-Z0-9\s_-]+/'),	// Tag Word 1
					$this->doSanitize($aPostData['aTagWords'][1], '/[^a-zA-Z0-9\s_-]+/'), 	// Tag Word 2
					$this->doSanitize($aPostData['aTagWords'][2], '/[^a-zA-Z0-9\s_-]+/'), 	// Tag Word 3
					$this->doSanitize($aPostData['aTagWords'][3], '/[^a-zA-Z0-9\s_-]+/')	// Tag Word 4
				));

				// Set the article
				// into the system
				$this->setArticle($oArticle);

				// We have added everything we need,
				// the doSyndicate method will do the
				// rest.  Try to see if we can run it
				// successfully
				if ($this->checkIfOneightyArticle($this->getArticle()->iWordPressId) === false) {

					// Run the syndication
					if ($this->doSyndicateToRpc()) {

						// Return
						$aReturnJson = array(
							'bSuccess' => true,
						);
					} else {

						// Set the system error
						$this->setError(Whv_Config::Get('errorMessages', 'unableToSyndicateFromWordPress'));

						// Return
						$aReturnJson = array(
							'bSuccess' => false,
							'sError'   => $this->getError()
						);
					}
				} else {

					// Set the system error
					$this->setError(Whv_Config::Get('errorMessages', 'unableToSyndicateFromWordPress'));

					// Return
					$aReturnJson = array(
						'bSuccess' => false,
						'sError'   => $this->getError()
					);
				}
			} else {

				// Set system error
				$this->setError(Whv_Config::Get('errorMessages', 'articleAlreadyExists'));

				// Return
				$aReturnJson = array(
					'bSuccess' => false,
					'sError'   => $this->getError()
				);
			}
		} else {

			// Set the system error
			$this->setError(Whv_Config::Get('errorMessages', 'noPostData'));

			// Return
			$aReturnJson = array(
				'bSuccess' => false,
				'sError'   => $this->getError()
			);
		}

		return json_encode($aReturnJson);
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Feeds      ////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method is responsible for making
	 * a JSON-RPC request to 180Create and
	 * handling the response
	 *
	 * @param array $mData
	 */
	public function feedJson(array $aData, $bArray = false) {
		// Check for a method
		if (!isset($aData['_method']) || is_null($aData['_method'])) {

			// If no method was found, then
			// set the system error and
			// return false
			$this->setError(Whv_Config::Get('errorMessages', 'noRpcMethod'));

			// Return false because
			// we have no method to run
			return false;
		}

		// Make the request
		$rContext = stream_context_create(array(
			'http' => array (
				'method'  => 'POST',
				'header'  => 'Content-type: application/json',
				'content' => json_encode($aData)
			)));

		stream_context_set_option($rContext, 'ssl', 'allow_self_signed', true);
		stream_context_set_option($rContext, 'ssl', 'verify_peer', false);
		// Send the request
		$mResponse = file_get_contents(Whv_Config::Get('feeds', 'jsonRpc'), false, $rContext);

//		if ($aData['_method'] == 'post') {
//		var_dump($aData);
//		var_dump($mResponse);exit();
//		}
		// Check for data
		if (!empty($mResponse)) {

			// Set the response
			$this->setRpcResponse(json_decode($mResponse), $bArray);

			// Return
			return true;
		} else {

			// Return
			return false;
		}
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Debug      ////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method is responsible for showing a pretty
	 * debug output of a variable
	 *
	 * @param mixed $mVar the variable to output
	 * @param boolean $bKill determines whether to exit or not
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function showDebug($mVar, $bTerminate = false) {

		echo('<pre>');
		print_r($mVar);
		echo('</pre>');

		if ($bTerminate === true) {
			exit;
		}
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Setters      //////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method sets the current account in use
	 *
	 * @param object $oAccount is the account object
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setAccount($oAccount) {

		// Set the account
		$this->oAccount = (object) $oAccount;

		// Return instance
		return $this;
	}

	/**
	 * This method sets the current article in use
	 *
	 * @param object $oArticle is the article object
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setArticle(stdClass $oArticle) {

		// Set the article
		$this->oArticle = (object) $oArticle;

		// Return instance
		return $this;
	}

	/**
	 * This method sets the current article set in use
	 *
	 * @param array $aArticles is the article set we wish to store
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setArticles(array $aArticles) {

		// Set the article set
		$this->aArticles = (array) $aArticles;

		// Return instance
		return $this;
	}

	/**
	 * This method sets our categories
	 *
	 * @param array $aCategories is the array of category objects
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setCategories(array $aCategories) {

		// Set our categories
		$this->aCategories = (array) $aCategories;

		// Return instance
		return $this;
	}

	/**
	 * This method sets our comments into the system
	 *
	 * @param array $aComments
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setComments(array $aComments) {

		// Set comments
		$this->aComments = (array) $aComments;

		// Return instance
		return $this;
	}

	/**
	 * This method sets the current database object
	 *
	 * @param wpdb $oWordPressDatabase is $wpdb from WordPress
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setDatabase(wpdb $oWordPressDatabase) {

		// Set database object
		$this->oDatabase = (object) $oWordPressDatabase;

		// Return instance
		return $this;
	}

	/**
	 * This method sets the current system error
	 *
	 * @param string $sError is the error text
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setError($sError) {

		// Set the error
		$this->sError = (string) $sError;

		// Return instance
		return $this;
	}

	/**
	 * This method sets the groups for the current
	 * account stored in the local WordPress DB
	 *
	 * @param array $aGroups is the array of groups for the current account
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setGroups(array $aGroups) {

		// Set our groups
		$this->aGroups = (array) $aGroups;

		// Return instance
		return $this;
	}

	/**
	 * This method sets the namespace that we will be
	 * working in
	 *
	 * @param string $sNamespace is the namespace we will use
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setNamespace($sNamespace) {

		// Set our namespace
		$this->sNamespace = (string) $sNamespace;

		// Return instance
		return $this;
	}

	/**
	 * This method turns error reporting on
	 * and of for the caller
	 *
	 * @param boolean $bOnOff determines whether to turn errors on or off
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setPhpErrorReporting($bOnOff) {
		if ($bOnOff === true) {

			// Display Errors
			ini_set('display_errors', true);

			// Turn error reporting on
			error_reporting(E_ALL);

			// Turn error reporting off
		} else {

			// Stop displaying Errors
			ini_set('display_errors', false);

			// Turn error reporting on
			error_reporting(0);
		}

		// Return instance
		return $this;
	}

	/**
	 * This method sets the absolute path to our
	 * plugin folder
	 *
	 * @param string $sPluginPath is the path to the plugin
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setPluginPath($sPluginPath) {

		// Set the path to our plugin
		$this->sPluginPath = (string) $sPluginPath;

		// Return Instance
		return $this;
	}

	/**
	 * This method sets the web URL to our
	 * plugin folder
	 *
	 * @param string $sPluginWebPath is the URL to the plugin
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setPluginWebPath($sPluginWebPath) {

		// Set the URL to our plugin
		$this->sPluginWebPath = (string) $sPluginWebPath;

		// Return instance
		return $this;
	}

	/**
	 * This method sets the current WordPress
	 * post that is in use
	 *
	 * @param object $oPost the WordPress post object
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setPost(object $oPost) {

		// Set the current post
		$this->oPost = (object) $oPost;

		// Return instance
		return $this;
	}

	/**
	 * This method converts the current $_POST data to a more
	 * fluid and convenient object
	 *
	 * @param array $aPostData is the $_POST array
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setPostData(array $aPostData) {

		// Create our object placeholder
		$oPostData = new stdClass();

		// Loop through the default $_POST array
		// and convert its keys to object properties
		foreach ($aPostData as $sKey => $mValue) {

			$this->stripMagic($mValue);
			// Set the property
			$oPostData->$sKey = $mValue;
		}

		// Set the object to the system
		$this->oPostData = (object) $oPostData;

		// Return instance
		return $this;
	}

	/**
	* removes effects of Magic Quotes GPC
	*/
	public function stripMagic(&$item) {
		if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())  {
		$item = $this->stripslashes_array($item);
		}
	}

	public 	function stripslashes_array($array) {
		return is_array($array) ? array_map( array($this, 'stripslashes_array'), $array) : stripslashes($array);
	}


	/**
	 * This method sets the current posts in the
	 * WordPress database that are unrelated to
	 * 180Create
	 *
	 * @param array $aPosts is the array of WordPress post objects
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setPosts(array $aPosts) {

		// Set our posts
		$this->aPosts = (array) $aPosts;

		// Return instance
		return $this;
	}

	/**
	 * This method sets the current RPC response
	 *
	 * @param mixed $mResponse is the response from the RPC server
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setRpcResponse($mResponse) {

		// Set the response
		$this->mResponse = $mResponse;

		// Return instance
		return $this;
	}

	/**
	 * This method sets a variable scope to
	 * be used with the templates
	 *
	 * @param array $aScope is the array of variable name (key), value (val) pairs
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setScope(array $aScope) {

		// Create the scope object placeholder
		$oScope = new stdClass();

		// Take the scope array and
		// convert it to a usable
		// object
		foreach ($aScope as $sKey => $mValue) {

			// Set the property
			$oScope->{$sKey} = $mValue;
		}

		// Set the scope
		$this->oScope = (object) $oScope;

		// Return instance
		return $this;
	}

	/**
	 * This method sets our search results
	 *
	 * @param array $aResults is the returned result set
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setSearchResults(array $aResults) {

		// Set our results
		$this->aSearchResults = (array) $aResults;

		// Return instance
		return $this;
	}

	/**
	 * This method sets our subcategories
	 *
	 * @param array $aSubcategories is the array of subcategory objects
	 * @return Actions $this for a fluid and chain-loadable interface
	 */
	public function setSubcategories(array $aSubcategories) {

		// Set our subcategories
		$this->aSubcategories = (array) $aSubcategories;

		// Return instance
		return $this;
	}

	////////////////////////////////////////////////////////////////////////
	//////////      Getters      //////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////

	/**
	 * This method returns the current 180Create
	 * account that is stored in the system
	 *
	 * @return object @property $oAccount
	 */
	public function getAccount() {
		return $this->oAccount;
	}

	/**
	 * This method returns the current 180Create
	 * article that is in use
	 *
	 * @return object @property $oArticle
	 */
	public function getArticle() {
		return $this->oArticle;
	}

	/**
	 * This method retuns the current
	 * working article set
	 *
	 * @return array @property $aArticles
	 */
	public function getArticles() {
		return $this->aArticles;
	}

	/**
	 * This method returns the current categories
	 *
	 * @return array @property $aCategories
	 */
	public function getCategories() {
		return $this->aCategories;
	}

	/**
	 * This methdod returns the current comment set
	 *
	 * @return array @property $aComments
	 */
	public function getComments() {
		return $this->aComments;
	}

	/**
	 * This method returns the current active
	 * database object
	 *
	 * @return object @property $oDatabase
	 */
	public function getDatabase() {
		return $this->oDatabase;
	}

	/**
	 * This method returns the current system
	 * error message
	 *
	 * @return string @property $sError
	 */
	public function getError() {
		return $this->sError;
	}

	/**
	 * This method returns the curren stored
	 * account's groups
	 *
	 * @return array @property$aGroups
	 */
	public function getGroups() {
		return $this->aGroups;
	}

	/**
	 * This method returns the current working namespace
	 *
	 * @param integer $iCase defaults to 0 which is the original lower case string
	 * @return str @property $sNamespace
	 */
	public function getNamespace($iCase = 0) {

		// 0 = Lower Case
		// 1 = Upper Case
		// 2 = Upper Case First Letter

		// Determine the desired return
		switch ($iCase) {

		case 0 : // Lower Case

			// Return the string
			return strtolower($this->sNamespace);

			// End
			break;

		case 1 : // Upper Case

			// Return the string
			return strtoupper($this->sNamespace);

			// End
			break;

		case 2 : // Upper Case First Letter

			// Return the string
			return ucwords($this->sNamespace);

			// End
			break;
		}
	}

	/**
	 * This method returns the current plugin path
	 *
	 * @return string @property $sPluginPath
	 */
	public function getPluginPath() {
		return $this->sPluginPath;
	}

	/**
	 * This method returns the current
	 * URL to our plugin folder
	 *
	 * @return string @property $sPluginWebPath
	 */
	public function getPluginWebPath() {
		return $this->sPluginWebPath;
	}

	/**
	 * This method returns the current
	 * WordPress post in use
	 *
	 * @return object @property $oPost
	 */
	public function getPost() {
		return $this->oPost;
	}

	/**
	 * This returns the current POST object
	 *
	 * @return object @property $oPostData
	 */
	public function getPostData() {
		return $this->oPostData;
	}

	/**
	 * This method sets the current posts in the
	 * WordPress database that are unrelated to
	 * 180Create
	 *
	 * @param array $aPosts is the array of WordPress post objects
	 * @return array @property $aPosts
	 */
	public function getPosts() {
		return $this->aPosts;
	}

	/**
	 * This method returns the current RPC response
	 *
	 * @return mixed @property $mResponse
	 */
	public function getRpcResponse() {
		return $this->mResponse;
	}

	/**
	 * This method returns the current,
	 * caller-set scope
	 *
	 * @return object @property $oScope
	 */
	public function getScope() {
		return $this->oScope;
	}

	/**
	 * This method returns our search results
	 *
	 * @return array @property $aSearchResults
	 */
	public function getSearchResults() {
		return $this->aSearchResults;
	}

	/**
	 * This method returns our current
	 * subcategories
	 *
	 * @return array @property $aSubcategories
	 */
	public function getSubcategories() {
		return $this->aSubcategories;
	}
}

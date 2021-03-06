<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Webconfig class.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('ConfigurationFile.class.php');
require_once('File.class.php');
require_once('Folder.class.php');
require_once('Daemon.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webconfig class.
 *
 * Only application-level methods are in this class.  In other words, no
 * GUI components are found here.
 *
 * @package Api
 * @subpackage Daemon
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Webconfig extends Daemon
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	const FILE_CONFIG = "/etc/system/webconfig";
	const FILE_ACCESS_DATA = "/etc/system/webconfig-access";
	const FILE_SETUP_FLAG = "/etc/system/initialized/setup";
	const FILE_INSTALL_SETTINGS = '/usr/share/system/settings/install';
	const PATH_CACHE = "/htdocs/tmp";
	const TYPE_USER_DENIED = "denied";
	const TYPE_USER_REGULAR = "regular";
	const TYPE_USER_ADMIN = "admin";
	const CMD_KILLALL = "/usr/bin/killall";

	protected $is_loaded = false;
	protected $config = array();

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Webconfig constructor.
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct("webconfig-httpd");

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Clears cache files.
	 *
	 * @return void
	 */

	function ClearCache()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$folder = new Folder(COMMON_CORE_DIR . self::PATH_CACHE, true);

		try {
			if ($folder->Exists())
				$folder->Delete(true);

			$folder->Create("webconfig", "webconfig", "0755");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Returns state of admin access.
	 *
	 * @return boolean state of admin access
	 * @throws EngineException
	 */

	function GetAdminAccessState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['allow_subadmins'];
	}

	/**
	 * Returns a list of valid subadmins.
	 *
	 * @return array list of valid usernames
	 */

	function GetAdminList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$admins = array();

		try {
			$file = new File(self::FILE_ACCESS_DATA);

			if (!$file->Exists())
				return $admins;

			$lines = $file->GetContentsAsArray();

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach($lines as $line) {
			$parts = explode("=",$line);
			$admins[] = trim($parts[0]);
		}

		return $admins;
	}

	/**
	 * Returns redirect URL.
	 *
	 * @return string redirect URL
	 * @throws EngineException
	 */

	function GetRedirectUrl()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// This should probably move to a "vendor" class one day

		try {
			$configfile = new ConfigurationFile(self::FILE_INSTALL_SETTINGS);
			$configdata = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$url = isset($configdata['redirect_url']) ? $configdata['redirect_url'] : '';

		return $url;
	}

	/**
	 * Returns state of shell access for users.
	 *
	 * @return boolean state of shell access
	 * @throws EngineException
	 */

	function GetShellAccessState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['allow_shell'];
	}

	/**
	 * Returns configured template.
	 *
	 * @return string online help URL
	 * @throws EngineException
	 */

	function GetTemplate()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		// For developers -- allow environment variable to override configuration
		if (isset($_ENV['WEBCONFIG_TEMPLATE']))
			return $_ENV['WEBCONFIG_TEMPLATE'];
		
		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['template'];
	}

	/**
	 * Returns the list of available templates for webconfig.
	 *
	 * @return array list of template names
	 * @throws EngineException
	 */

	function GetTemplateList()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$folder = new Folder(COMMON_CORE_DIR  . "/htdocs/templates");

		$templatelist = array();

		try {
			$folderlist = $folder->GetListing();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		foreach($folderlist as $template) {
			if (preg_match("/(base)|(default)/", $template))
				continue;

			$templateinfo = array();

			try {
				$file = new ConfigurationFile(COMMON_CORE_DIR . "/htdocs/templates/" . $template . "/info");
				if ($file->Exists())
					$templateinfo = $file->Load();
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}

			$templatename = isset($templateinfo['name']) ? $templateinfo['name'] : $template;

			$templatelist[$templatename] = $template;
		}

		// Sort by name, but key by template directory

		$list = array();
		ksort($templatelist);

		foreach ($templatelist as $name => $folder)
			$list[$folder] = $name;

		return $list;
	}

	/**
	 * Returns state of user access.
	 *
	 * @return boolean state of user access
	 * @throws EngineException
	 */

	function GetUserAccessState()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		return $this->config['allow_user'];
	}

	/**
	 * Returns valid pages for a given user.
	 *
	 * @param string $username username
	 * @return array list of valid pages
	 * @throws EngineException
	 */

	function GetValidPages($username)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$validpages[Webconfig::TYPE_USER_REGULAR] = array();
		$validpages[Webconfig::TYPE_USER_ADMIN] = array();

		// TODO:
		// - move this list to a configuration file
		// - handle the servicestatus page a better way
		if ($this->GetUserAccessState())
			$validpages[Webconfig::TYPE_USER_REGULAR] = array('/admin/user.php', '/admin/security.php', '/admin/clearcenter-status.php');

		if ($this->GetAdminAccessState()) {
			try {
				$file = new File(self::FILE_ACCESS_DATA);
				$rawlist = $file->LookupValue("/^$username\s*=\s*/");
				$validpages[Webconfig::TYPE_USER_ADMIN] = explode("|",$rawlist);
			} catch (FileNotFoundException $e) {
				// Not fatal
			} catch (FileNoMatchException $e) {
				// Not fatal
			} catch (Exception $e) {
				throw new EngineException($e->GetMessage(), COMMON_WARNING);
			}
		}

		return $validpages;
	}

	/**
	 * Sets state of admin access.
	 *
	 * @param boolean $state state of admin access
	 * @return boolean state of admin access
	 * @throws EngineException
	 */

	function SetAdminAccessState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$stateval = $state ? 1 : 0;

		$this->_SetParameter("allow_subadmins", $stateval);
	}

	/**
	 * Sets the template for webconfig.
	 *
	 * @param string $template template for webconfig
	 * @return void
	 * @throws EngineException
	 */

	function SetTemplate($template)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$this->_SetParameter("template", $template);
	}

	/**
	 * Sets the list of pages a subadmin may access.
	 *
	 * @param string $username admin username
	 * @param array $pages string array of authorized pages
	 * @returns  void
	 */

	function SetValidPages($username, $pages)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$file = new File(self::FILE_ACCESS_DATA);

		try {
			if (! $file->Exists())
				$file->Create("root", "root", "0644");

			if ($pages) {

				$value = implode("|",$pages);
				$match = $file->ReplaceLines("/^$username\s*=\s*/", "$username = $value\n");

				if (!$match)
					$file->AddLines("$username = $value\n");
			} else {
				$file->DeleteLines("/^$username\s*=/");
			}

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	/**
	 * Sets the state of the setup/upgrade wizard.
	 *
	 * @param boolean $state state of setup/upgrade wizard
	 * @returns void
	 */

	function SetSetupState($state)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_SETUP_FLAG);

			if ($state && !$file->Exists())
				$file->Create("root", "root", "0644");
			else if (!$state && $file->Exists())
				$file->Delete();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E  M E T H O D S 
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Loads configuration files.
	 *
	 * @return void
	 * @throws EngineException
	 */

	protected function _LoadConfig()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new ConfigurationFile(self::FILE_CONFIG);

		try {
			$rawdata = $configfile->Load();

			if (isset($rawdata['allow_user']) && preg_match("/(true|1)/i", $rawdata['allow_user']))
				$this->config['allow_user'] = true;
			else
				$this->config['allow_user'] = false;

			if (isset($rawdata['allow_subadmins']) && preg_match("/(true|1)/i", $rawdata['allow_subadmins']))
				$this->config['allow_subadmins'] = true;
			else
				$this->config['allow_subadmins'] = false;

			if (isset($rawdata['allow_shell']) && preg_match("/(true|1)/i", $rawdata['allow_shell']))
				$this->config['allow_shell'] = true;
			else
				$this->config['allow_shell'] = false;

			$this->config['template'] = $rawdata['template'];

		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
	}

	/**
	 * Sets a parameter in the config file.
	 *
	 * @access private
	 * @param string $key name of the key in the config file
	 * @param string $value value for the key
	 * @return void
	 * @throws EngineException
	 */

	function _SetParameter($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		try {
			$file = new File(self::FILE_CONFIG);
			$match = $file->ReplaceLines("/^$key\s*=\s*/", "$key = $value\n");
			if (!$match)
				$file->AddLines("$key = $value\n");
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = false;
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>

<?php

/////////////////////////////////////////////////////////////////////////////
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
 * Shell execution class.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Wrapper for running shell commands.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class ShellExec extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// M E M B E R S
	///////////////////////////////////////////////////////////////////////////////

	protected $output = array();

	const CMD_SUEXEC = "/usr/bin/sudo";

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * ShellExec constructor.
	 */

	public function __construct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/**
	 * Executes the command.
	 *
	 * Excecute options are:
	 * - escape: scrub command line arguments for naught characters (default true)
	 * - log: specify a log file (default /dev/null)
	 * - env: environment variables (default null)
	 * - background: run command in background (default false)
	 * - stdin: write arguments to stdin (default false)
	 * 
	 * @param string $command command to excecute
	 * @param string $arguments command arguments
	 * @param boolean $superuser super user flag
	 * @param array $options extra execute options specified above
	 * @return int $retval command return code
	 * @throws ValidationException, EngineException
	 */

	public function Execute($command, $arguments, $superuser = false, $options = null)
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "command is: $command $arguments", __METHOD__, __LINE__);

		$this->output = array();

		if (! is_bool($superuser))
			throw new ValidationException(LOCALE_LANG_ERRMSG_INVALID_TYPE . " (superuser)");

		if (isset($options['escape']) && (!is_bool($options['escape'])))
			throw new ValidationException(LOCALE_LANG_ERRMSG_INVALID_TYPE . " (escape)");

		if (isset($options['log']) && ( preg_match("/\//", $options['log']) || preg_match("/\.\./", $options['log'])))
			throw new ValidationException(LOCALE_LANG_ERRMSG_PARAMETER_IS_INVALID . " (log: " . $options['log'] . ")");

		// Validate executable for non-superuser access.
		// If the file does not exist in superuser mode, it will get caught below... but
		// with a less "pretty" error message.

		if ((! $superuser) && (!file_exists($command)))
			throw new ValidationException(SHELLEXEC_LANG_ERRMSG_EXECUTE_FAILED, COMMON_ERROR);

		if (isset($options['escape']) && $options['escape']) {
			$command = escapeshellcmd($command);
			$arguments = escapeshellcmd($arguments);
		}

		if (strlen($arguments))
			$exe = $command . " " . $arguments;
		else
			$exe = $command;

		if ($superuser)
			$exe = self::CMD_SUEXEC . " " . $exe;

		if (isset($options['env']))
			$exe = $options['env'] . " $exe";

		// If set to background, output *must* be redirected to 
		// either a log or /dev/null

		if (isset($options['log']))
			$exe .= " >>" . COMMON_TEMP_DIR . "/" . $options['log'];
		else if (isset($options['background']) && $options['background'])
			$exe .= " >/dev/null";

		$exe .= " 2>&1";

		if (isset($options['background']) && $options['background'])
			$exe .= " &";

		$retval = null;

		if (isset($options['stdin'])) {
			$ph = popen($exe, "w");

			if (strlen($options['stdin']))
				fwrite($ph, $options['stdin']);

			$retval = pclose($ph);
		} else {
			exec($exe, $this->output, $retval);
		}

		return $retval;
	}

	/**
	 * Returns output from executed command.
	 *
	 * @return array command output as an array of strings
	 */

	public function GetOutput()
	{
		return $this->output;
	}

	/**
	 * Returns first output line.
	 *
	 * This method is useful for capturing simple command output (including errors).
	 *
	 * @return string first output line
	 */

	public function GetFirstOutputLine()
	{
		if (isset($this->output[0]))
			return $this->output[0];
		else
			return "";
	}

	/**
	 * Returns last output line.
	 *
	 * This method is useful for capturing the last line of output (including errors).
	 *
	 * @return string last output line
	 */

	public function GetLastOutputLine()
	{
		if (isset($this->output[sizeof($this->output) - 1]))
			return $this->output[sizeof($this->output) - 1];
		else
			return "";
	}

	/**
	 * @access private
	 */

	public function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		parent::__destruct();
	}
}

// vim: syntax=php ts=4
?>

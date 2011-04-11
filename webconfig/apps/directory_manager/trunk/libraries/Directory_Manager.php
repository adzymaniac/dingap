<?php

/**
 * ClearOS directory manager factory.
 *
 * @category   Apps
 * @package    Directory_Manager
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_manager/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\directory_manager;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('directory_manager');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;

clearos_load_library('base/Engine');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS directory factory.
 *
 * @category   Apps
 * @package    Directory_Manager
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/directory_manager/
 */

class Directory_Manager extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    // Modes
    //------

    const MODE_ACTIVE_DIRECTORY = 'ad';
    const MODE_MASTER = 'master';
    const MODE_SIMPLE_MASTER = 'simple_master';
    const MODE_SLAVE = 'slave';
    const MODE_STANDALONE = 'standalone';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $modes = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Directory manager constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->modes = array(
            self::MODE_ACTIVE_DIRECTORY => lang('directory_manager_active_directory'),
            self::MODE_SIMPLE_MASTER => lang('directory_manager_simple_master'),
            self::MODE_MASTER => lang('directory_manager_master'),
            self::MODE_SLAVE => lang('directory_manager_slave'),
            self::MODE_STANDALONE => lang('directory_manager_standalone')
        );
    }

    /**
     * Returns the directory driver.
     *
     * @return string directory driver
     * @throws Engine_Exception
     */

    public function get_driver()
    {
        clearos_profile(__METHOD__, __LINE__);

        $driver = 'active_directory';
        $driver = 'openldap';

        return $driver;
    }

    /**
     * Returns the available directory modes.
     *
     * @return array directory modes
     * @throws Engine_Exception
     */

    public function get_modes()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->modes;
    }

    /**
     * Returns the available plugins.
     *
     * @return array plugin list
     * @throws Engine_Exception
     */

    public function get_plugins()
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
/*
        $folder = new Folder($this->path_plugins);

        $list = $folder->get_listing();

        foreach ($list as $plugin) {
            if (! preg_match('/^\./', $plugin))
                $this->plugins[] = $plugin;
        }
*/

        $plugins = array(
            'pptp' => array(
                'name' => 'PPTP',
            ),

            'ftp' => array(
                'name' => 'FTP',
            ),
        );

        return $plugins;
    }

    /**
     * Returns the plugin map.
     *
     * @return array plugin map
     * @throws Engine_Exception
     */

    public function get_plugin_map()
    {
        clearos_profile(__METHOD__, __LINE__);

        $map = array(
            'state' => array(
                'type' => 'boolean',
                'field_type' => 'toggle',
                'required' => TRUE,
                'validator' => 'validate_state',
                'validator_class' => 'directory_manager/Directory_Manager',
                'description' => lang('directory_manager_state')
            )
        );

        return $map;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates mode.
     *
     * @param string $mode mode
     *
     * @return string error message if mode is invalid
     * @throws Engine_Exception
     */

    public function validate_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! array_key_exists($mode, $this->modes))
            return lang('directory_manager_directory_mode_is_invalid');
    }

    /**
     * Validates plugin state.
     *
     * @param string $state state
     *
     * @return string error message if state is invalid
     * @throws Engine_Exception
     */

    public function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME
    }
}
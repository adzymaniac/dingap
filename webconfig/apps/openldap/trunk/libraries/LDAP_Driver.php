<?php

/**
 * OpenLDAP driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
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

namespace clearos\apps\openldap;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('ldap');
clearos_load_language('openldap');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Factories
//----------

use \clearos\apps\ldap\LDAP_Factory as LDAP;
use \clearos\apps\mode\Mode_Factory as Mode;

clearos_load_library('ldap/LDAP_Factory');
clearos_load_library('mode/Mode_Factory');

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\ldap\LDAP_Client as LDAP_Client;
use \clearos\apps\ldap\LDAP_Engine as LDAP_Engine;
use \clearos\apps\ldap\LDAP_Utilities as LDAP_Utilities;
use \clearos\apps\mode\Mode_Engine as Mode_Engine;
use \clearos\apps\network\Hostname as Hostname;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\openldap_directory\Utilities as Utilities;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Daemon');
clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('ldap/LDAP_Client');
clearos_load_library('ldap/LDAP_Engine');
clearos_load_library('ldap/LDAP_Utilities');
clearos_load_library('mode/Mode_Engine');
clearos_load_library('network/Hostname');
clearos_load_library('network/Network_Utils');
clearos_load_library('openldap_directory/Utilities');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * OpenLDAP driver.
 *
 * @category   Apps
 * @package    OpenLDAP
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/openldap/
 */

class LDAP_Driver extends LDAP_Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const CONSTANT_BASE_DB_NUM = 3;
    const CONSTANT_BIND_DN_PREFIX = 'cn=manager,ou=Internal';

    // Policies
    const POLICY_LAN = 'lan';
    const POLICY_LOCALHOST = 'localhost';

    // Commands
    const COMMAND_LDAP_MANAGER = '/usr/sbin/ldap-manager';
    const COMMAND_LDAPSYNC = '/usr/sbin/ldapsync';
    const COMMAND_OPENSSL = '/usr/bin/openssl';
    const COMMAND_SLAPADD = '/usr/sbin/slapadd';
    const COMMAND_SLAPCAT = '/usr/sbin/slapcat';
    const COMMAND_SLAPPASSWD = '/usr/sbin/slappasswd';

    // Files and paths
    const FILE_CONFIG = '/var/clearos/openldap/config.php';
    const FILE_DATA = '/var/clearos/openldap/provision/provision.ldif';
    const FILE_DBCONFIG = '/var/lib/ldap/DB_CONFIG';
    const FILE_DBCONFIG_ACCESSLOG = '/var/lib/ldap/accesslog/DB_CONFIG';
    const FILE_INITIALIZED = '/var/clearos/openldap/initialized.php';
    const FILE_INITIALIZING = '/var/clearos/openldap/initializing';
    const FILE_LDAP_CONFIG = '/etc/openldap/ldap.conf';
    const FILE_SLAPD_CONFIG = '/etc/openldap/slapd.conf';
    const FILE_STATUS = '/var/clearos/openldap/status';
    const FILE_SYSCONFIG = '/etc/sysconfig/ldap';
    const FILE_LDIF_BACKUP = '/etc/openldap/backup.ldif';
    const FILE_LDIF_NEW_DOMAIN = '/var/clearos/openldap/provision/newdomain.ldif';
    const FILE_LDIF_OLD_DOMAIN = '/var/clearos/openldap/provision/olddomain.ldif';
    const PATH_LDAP = '/var/lib/ldap';
    const PATH_LDAP_BACKUP = '/var/clearos/openldap/backup';

    // Internal configuration
    const FILE_PROVISION_ACCESSLOG_DATA = 'deploy/provision/provision.accesslog.ldif';
    const FILE_PROVISION_DATA = 'deploy/provision/provision.ldif.template';
    const FILE_PROVISION_DBCONFIG = 'deploy/provision/DB_CONFIG.template';
    const FILE_PROVISION_LDAP_CONFIG = 'deploy/provision/ldap.conf.template';
    const FILE_PROVISION_SLAPD_CONFIG = 'deploy/provision/slapd.conf.template';
    const FILE_PROVISION_SLAPD_CONFIG_REPLICATE = 'deploy/provision/slapd-replicate.conf.template';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;
    protected $config = NULL;

    protected $file_provision_accesslog_data = NULL;
    protected $file_provision_data = NULL;
    protected $file_provision_dbconfig = NULL;
    protected $file_provision_ldap_config = NULL;
    protected $file_provision_slapd_config = NULL;
    protected $file_provision_slapd_config_replicate = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Driver constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->file_provision_accesslog_data = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_ACCESSLOG_DATA;
        $this->file_provision_data = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_DATA;
        $this->file_provision_dbconfig = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_DBCONFIG;
        $this->file_provision_ldap_config = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_LDAP_CONFIG;
        $this->file_provision_slapd_config = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_SLAPD_CONFIG;
        $this->file_provision_slapd_config_replicate = clearos_app_base('openldap') . '/' . self::FILE_PROVISION_SLAPD_CONFIG_REPLICATE;

        parent::__construct('slapd');
    }

    /**
     * Exports LDAP database to LDIF.
     *
     * @param string  $ldif  LDIF backup file
     * @param integer $dbnum database number
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function export($ldif = self::FILE_LDIF_BACKUP, $dbnum = self::CONSTANT_BASE_DB_NUM)
    {
        clearos_profile(__METHOD__, __LINE__);

        $export = new File($ldif, TRUE);

        if ($export->exists())
            $export->delete();

        $export->create('root', 'root', '0600');

        if ($this->ldaph === NULL)
            $this->ldaph = $this->get_ldap_handle();

        $was_running = $this->get_running_state();

        if ($was_running)
            $this->set_running_state(FALSE);

        $shell = new Shell();
        $shell->execute(self::COMMAND_SLAPCAT, "-n$dbnum -l " . $ldif, TRUE);

        if ($was_running)
            $this->set_running_state(TRUE);
    }

    /** 
     * Returns the base DN.
     *
     * @return string base DN
     * @throws Engine_Exception
     */

    public function get_base_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->config['base_dn'] !== NULL) 
            return $this->config['base_dn'];

        if ($this->ldaph === NULL)
            $this->ldaph = $this->get_ldap_handle();

        $base_dn = $this->ldaph->get_base_dn();

        $this->config['base_dn'] = $base_dn;

        return $base_dn;
    }

    /** 
     * Returns the bind DN.
     *
     * @return string bind DN
     * @throws Engine_Exception
     */

    public function get_bind_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = $this->get_ldap_handle();

        return $this->ldaph->get_bind_dn();
    }

    /** 
     * Returns the bind password.
     *
     * @return string bind password
     * @throws Engine_Exception
     */

    public function get_bind_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = $this->get_ldap_handle();

        return $this->ldaph->get_bind_password();
    }

    /** 
     * Returns base DN in Internet domain format.
     *
     * @return string default domain
     * @throws Engine_Exception
     */

    public function get_base_internet_domain()
    {
        clearos_profile(__METHOD__, __LINE__);

        $base_dn = $this->get_base_dn();

        $domain = preg_replace('/(,dc=)/', '.', $base_dn);
        $domain = preg_replace('/dc=/', '', $domain);

        return $domain;
    }

    /**
     * Creates an LDAP connection handle.
     *
     * Many libraries that use OpenLDAP need to:
     *
     * - grab LDAP credentials for connecting to the server
     * - connect to LDAP
     * - perform a bunch of LDAP acctions (search, read, etc)
     *
     * This method provides a common method for doing the firt two steps.
     *
     * @return LDAP handle
     * @throws Engine_Exception
     */

    public function get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_null($this->config['base_dn']))
            $this->_load_config();

        $base_dn = (empty($this->config['base_dn'])) ? '' : $this->config['base_dn'];
        $bind_dn = (empty($this->config['bind_dn'])) ? '' : $this->config['bind_dn'];
        $bind_pw = (empty($this->config['bind_pw'])) ? '' : $this->config['bind_pw'];

        $ldaph = new LDAP_Client($base_dn, $bind_dn, $bind_pw);

        return $ldaph;
    }

    /**
     * Returns the DN of the master server.
     *
     * @return string DN of the master server
     * @throws Engine_Exception
     */

    public function get_master_dn()
    {
        clearos_profile(__METHOD__, __LINE__);

        return "cn=Master,ou=Servers," . $this->get_base_dn();
    }

    /**
     * Returns the master hostname.
     *
     * @return string DN of the master server
     * @throws Engine_Exception
     */

    public function get_master_hostname()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_null($this->config))
            $this->_load_config();

        $hostname = (empty($this->config['master_hostname'])) ? '' : $this->config['master_hostname'];

        return $hostname;
    }

    /**
     * Returns the mode of directory.
     *
     * The return values are:
     * - Mode_Engine::MODE_STANDALONE
     * - Mode_Engine::MODE_MASTER
     * - Mode_Engine::MODE_SLAVE
     *
     * @return string mode of the directory
     * @throws Engine_Exception
     */

    public function get_mode()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_null($this->config))
            $this->_load_config();

        // Return the configured mode if OpenLDAP has been already initialized        
        if (! empty($this->config['mode']))
            return $this->config['mode'];

        // Otherwise, return the mode implied by the system mode
        $system_mode = Mode::create();
        $mode = $system_mode->get_mode();

        return $mode;
    }

    /**
     * Returns the mode of directory in human readable format.
     *
     * @return string mode of the directory in human readable format.
     * @throws Engine_Exception
     */

    public function get_mode_text()
    {
        clearos_profile(__METHOD__, __LINE__);

        $mode = $this->get_mode();

        return $this->modes[$mode];
    }

    /**
     * Returns a list of available modes.
     *
     * @return array list of modes
     * @throws Engine_Exception
     */

    public function get_modes()
    {
        clearos_profile(__METHOD__, __LINE__);

        $system_mode = Mode::create();
        $system_modes = $system_mode->get_modes();

        $modes = array();
        $modes[self::MODE_STANDALONE] = lang('ldap_standalone');

        foreach ($system_modes as $mode => $mode_text) {
            if (($mode === Mode_Engine::MODE_SIMPLE_MASTER) || ($mode === Mode_Engine::MODE_MASTER))
                $modes[self::MODE_MASTER] = lang('ldap_master');
            else if ( ($mode === Mode_Engine::MODE_SIMPLE_SLAVE) || ($mode === Mode_Engine::MODE_SLAVE))
                $modes[self::MODE_SLAVE] = lang('ldap_slave');
        }

        return $modes;
    }

    /** 
     * Returns security policies.
     *
     * @return array security policies
     * @throws Engine_Exception
     */

    public function get_security_policies()
    {
        clearos_profile(__METHOD__, __LINE__);

        return array(
            self::POLICY_LOCALHOST => lang('ldap_not_published'),
            self::POLICY_LAN => lang('ldap_local_network'),
        );
    }

    /** 
     * Returns security policy.
     *
     * The LDAP server can be configured to listen on:
     * -  localhost only: LDAP::POLICY_LOCALHOST
     * -  localhost and all LAN interfaces: LDAP::POLICY_LAN
     *
     * @return string security policy
     * @throws Engine_Exception
     */

    public function get_security_policy()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_SYSCONFIG);

        $policy = self::POLICY_LOCALHOST;

        try {
            if ($file->exists())
                $policy = $file->lookup_value('/^BIND_POLICY=/');
        } catch (File_No_Match_Exception $e) {
            // Use default localhost policy
        }

        return $policy;
    }

    /**
     * Returns status of account system.
     *
     * - LDAP_Engine::STATUS_INITIALIZING
     * - LDAP_Engine::STATUS_UNINITIALIZED
     * - LDAP_Engine::STATUS_OFFLINE
     * - LDAP_Engine::STATUS_ONLINE
     *
     * @return string account system status
     * @throws Engine_Exception
     */

    public function get_system_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_INITIALIZED);

        if ($this->ldaph === NULL)
            $this->ldaph = Utilities::get_ldap_handle();

        $initialization_status = $this->_get_system_status_message();

        if (! empty($initialization_status)) {
            $status = LDAP_Engine::STATUS_BUSY;
        } else if (! $this->is_initialized()) {
            $status = LDAP_Engine::STATUS_UNINITIALIZED;
        } else if ($this->ldaph->is_online()) {
            $status = LDAP_Engine::STATUS_ONLINE;
        } else {
            $status = LDAP_Engine::STATUS_OFFLINE;
        }

        return $status;
    }

    /**
     * Returns system status message.
     *
     * @return string system status message
     * @throws Engine_Exception
     */

    public function get_system_message()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_get_system_status_message();
    }

    /**
     * Imports backup LDAP database from LDIF.
     *
     * @return boolean true if import file exists
     */

    public function import()
    {
        clearos_profile(__METHOD__, __LINE__);

        $import = new File(self::FILE_LDIF_BACKUP, TRUE);

        if (! $import->exists())
            return FALSE;

        $this->_import_ldif(self::FILE_LDIF_BACKUP);

        $this->synchronize();

        // TODO: should this restart Nscd?

        return TRUE;
    }

    /**
     * Initializes the LDAP database in master mode.
     *
     * @param string $mode LDAP server mode
     * @param string $domain domain name
     * @param string $password bind DN password
     * @param boolean $start starts LDAP after initialization
     * @param boolean $force forces initialization even if LDAP server already has data
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function initialize_master($domain, $password = NULL, $force = FALSE, $start = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['force'] = $force;
        $options['start'] = $start;

        if (empty($password))
            $password =  LDAP_Utilities::generate_password();

        $this->_initialize(self::MODE_MASTER, $domain, $password, $options);
    }

    /**
     * Initializes the LDAP database in slave mode.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function initialize_slave($master, $password, $force = FALSE, $start = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['force'] = $force;
        $options['start'] = $start;
        $options['master'] = $master;

        // TODO: load domain from master node / SDN request
        $this->_initialize(self::MODE_SLAVE, $domain, $password, $options);
    }

    /**
     * Initializes the LDAP database in standalone mode.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function initialize_standalone($domain, $password = NULL, $force = FALSE, $start = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['force'] = $force;
        $options['start'] = $start;

        if (empty($password))
            $password = LDAP_Utilities::generate_password();

        $this->_initialize(self::MODE_STANDALONE, $domain, $password, $options);
    }

    /**
     * Returns state of LDAP setup.
     *
     * @return boolean TRUE if LDAP has been initialized
     */

    public function is_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_INITIALIZED);

        if ($file->exists())
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Prepares system to be initialized.
     *
     * In order to support asynchronous initialization, this method provides
     * a way to indicate that an asynchronous initialization has been
     * requested.
     *
     * @return void
     */

    public function prepare_initialize()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_initialized())
            $this->_set_initialization_status(lang('openldap_preparing_system'));
    }

    /**
     * Changes base domain used in directory
     *
     * @param string $domain domain
     *
     * @return void
     */

    public function set_base_internet_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_domain($domain));

        // Validate: method is not valid when system is in slave mode
        //-----------------------------------------------------------

        $mode = $this->get_mode();

        if ($mode === self::MODE_SLAVE)
            throw new Validation_Exception(lang('openldap_domain_cannot_be_changed_in_slave_mode'));

        // Bail if this has not been initialized
        //--------------------------------------

        if (! $this->is_initialized())
            return;

        // Bail if the domain hasn't changed
        //----------------------------------

        if ($domain === $this->get_base_internet_domain())
            return;

        // Prep file parsing
        //------------------

        $this->_set_initialization_status(lang('openldap_preparing_system'));

        if ($this->ldaph === NULL)
            $this->ldaph = $this->get_ldap_handle();

        try {
            // Dump LDAP database to export file
            //----------------------------------

            $was_running = $this->get_running_state();
            $this->export(self::FILE_LDIF_OLD_DOMAIN, self::CONSTANT_BASE_DB_NUM);

            // Grab hostname
            //--------------

            $hostnameobj = new Hostname();
            $hostname = $hostnameobj->get();

            // Load LDAP export file
            //----------------------

            $export = new File(self::FILE_LDIF_OLD_DOMAIN, TRUE);
            $exportlines = $export->get_contents_as_array();

            // Load LDAP configuration
            //------------------------

            $ldapconfig = new File(self::FILE_SLAPD_CONFIG);
            $ldaplines = $ldapconfig->get_contents_as_array();

            // Load LDAP information
            //----------------------

            $basedn = $this->ldaph->get_base_dn();

        } catch (Exception $e) {
            if ($was_running)
                $this->set_running_state(TRUE);

            throw new Engine_Exception(clearos_exception_message($e));
        }
        
        // Remove word wrap from LDIF data
        //--------------------------------

        $this->_set_initialization_status(lang('openldap_generating_configuration'));

        $cleanlines = array();

        foreach ($exportlines as $line) {
            if (preg_match('/^\s+/', $line)) {
                $previous = array_pop($cleanlines);
                $cleanlines[] = $previous . preg_replace('/^ /', '', $line);
            } else {
                $cleanlines[] = $line;
            }
        }

        // Rewrite LDAP export file
        //-------------------------

        $newbasedn = 'dc=' . preg_replace('/\./', ',dc=', $domain);
        $matches = array();

        preg_match('/^dc=([^,]*)/', $basedn, $matches);
        $olddc = $matches[1];

        preg_match('/^dc=([^,]*)/', $newbasedn, $matches);
        $newdc = $matches[1];

        $ldiflines = array();

        // TODO: Handle Kolab externally - leave it here for now.
        foreach ($cleanlines as $line) {
            if (preg_match("/$basedn/", $line))
                $ldiflines[] = preg_replace("/$basedn/", $newbasedn, $line);
            else if (preg_match("/^kolabHomeServer: /", $line))
                $ldiflines[] = "kolabHomeServer: $hostname";
            else if (preg_match("/^mail: /", $line))
                $ldiflines[] = preg_replace("/@.*/", "@$domain", $line);
            else if (preg_match("/^dc: $olddc/", $line))
                $ldiflines[] = "dc: $newdc";
            else if (preg_match("/^uid: calendar@/", $line))
                $ldiflines[] = "uid: calendar@$domain";
            else if (preg_match("/^kolabHost: /", $line))
                $ldiflines[] = "kolabHost: $hostname";
            else if (preg_match("/^postfix-mydomain: /", $line))
                $ldiflines[] = "postfix-mydomain: $domain";
            else if (preg_match("/^postfix-mydestination: /", $line))
                $ldiflines[] = "postfix-mydestination: $domain";
            else
                $ldiflines[] = $line;
        }

        // Rewrite LDAP configuration file
        //--------------------------------

        $newldaplines = array();

        foreach ($ldaplines as $line)
            $newldaplines[] = preg_replace("/$basedn/", $newbasedn, $line);

        //---------------------------------------------------------------
        // Implement file changes
        //---------------------------------------------------------------

        // LDAP export file
        //-----------------

        $newexport = new File(self::FILE_LDIF_NEW_DOMAIN);

        if ($newexport->exists())
            $newexport->delete();

        $newexport->create('root', 'root', '0600');
        $newexport->dump_contents_from_array($ldiflines);

        // LDAP configuration
        //--------------------

        $newldap = new File(self::FILE_SLAPD_CONFIG, TRUE);

        if ($newldap->exists())
            $newldap->delete();

        $newldap->create('root', 'ldap', '0640');
        $newldap->dump_contents_from_array($newldaplines);

        // Import
        //-------

        $this->_set_initialization_status(lang('openldap_importing_data'));

        $this->_import_ldif(self::FILE_LDIF_NEW_DOMAIN);

        // Set new base DN in configuration
        //---------------------------------

        $file = new File(self::FILE_CONFIG);
        $file->replace_lines('/^base_dn\s*=/', "base_dn = $newbasedn\n");
        $file->replace_lines('/^bind_dn\s*=/', "bind_dn = " . self::CONSTANT_BIND_DN_PREFIX . ",$newbasedn\n");

        $this->_set_initialization_status('');

        // Tell other LDAP dependent apps to grab latest configuration
        //------------------------------------------------------------

        try {
            if ($was_running)
                $this->synchronize(FALSE);
        } catch (Exception $e) {
            // Not fatal
        }
    }

    /** 
     * Sets security policy.
     *
     * The LDAP server can be configured to listen on:
     * -  localhost only: LDAP::POLICY_LOCALHOST
     * -  localhost and all LAN interfaces: LDAP::POLICY_LAN
     *
     * @param boolean $policy policy setting
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_security_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($errmsg = $this->validate_security_policy($policy))
            throw new Validation_Exception($errmsg);

        $file = new File(self::FILE_SYSCONFIG);

        if ($file->exists()) {
            $matches = $file->replace_lines('/^BIND_POLICY=.*/', "BIND_POLICY=$policy\n");
            if ($matches === 0)
                $file->add_lines("BIND_POLICY=$policy\n");
        } else {
            $file->create('root', 'root', '0644');
            $file->add_lines("BIND_POLICY=$policy\n");
        }
    }

    /**
     * Sends a synchronization signal to LDAP aware apps.
     *
     * @return void
     */

    public function synchronize()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_synchronize_files();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validates domain.
     *
     * @param string $domain domain
     *
     * @return string error message if domain is invalid
     */

    public function validate_domain($domain)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($domain))
            return lang('ldap_domain_is_invalid');
    }

    /**
     * Validates LDAP mode.
     *
     * @param string $mode LDAP mode
     *
     * @return string error message if LDAP mode is invalid
     */

    public function validate_mode($mode)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! (isset($mode) && array_key_exists($mode, $this->modes)))
            return lang('ldap_mode_is_invalid');
    }

    /**
     * Validates LDAP password.
     *
     * @param string $password LDAP password
     *
     * @return string error message if LDAP password is invalid
     */

    public function validate_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO
        if (empty($password))
            return lang('base_password_is_invalid');
    }

    /**
     * Validates security policy.
     *
     * @param string $policy policy
     *
     * @return string error message if security is invalid
     */

    public function validate_security_policy($policy)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (($policy !== self::POLICY_LOCALHOST) && ($policy !== self::POLICY_LAN))
            return lang('ldap_security_policy_is_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns status message.
     * 
     * @return void
     * @throws Engine_Exception
     */

    protected function _get_system_status_message()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_STATUS);

        if (! $file->exists())
            return '';

        $status = $file->lookup_value('/^status_message\s*=\s*/');

        return $status;
    }

    /**
     * Common initialization routine for the LDAP modes.
     *
     * @param string $mode LDAP server mode
     * @param string $domain domain name
     * @param string $password bind DN password
     * @param options options array depending on mode
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _initialize($mode, $domain, $password, $options)
    {
        clearos_profile(__METHOD__, __LINE__);

        $force = isset($options['force']) ? $options['force'] : FALSE;
        $start = isset($options['start']) ? $options['start'] : TRUE;
        $master_hostname = isset($options['master_hostname']) ? $options['master_hostname'] : '';

        // Bail if LDAP is already initialized (and not a re-initialize)
        //--------------------------------------------------------------

        if ($this->is_initialized() && (!$force)) {
            $this->_set_initialization_status('');
            return;
        }

        // KLUDGE: shutdown Samba or it will try to write information to LDAP
        //-------------------------------------------------------------------

        $this->_set_initialization_status(lang('openldap_preparing_system'));

        $samba_list = array('smb', 'nmb', 'winbind');

        try {
            foreach ($samba_list as $daemon) {
                $samba = new Daemon($daemon);

                if ($samba->is_installed())
                    $samba->set_running_state(FALSE);
            }
        } catch (Exception $e) {
            // not fatail
        }

        // Determine our hostname and generate an LDAP password (if required)
        //-------------------------------------------------------------------

        $hostnameinfo = new Hostname();
        $hostname = $hostnameinfo->get();

        // Generate the configuration files
        //---------------------------------

        $this->_set_initialization_status(lang('openldap_generating_configuration'));

        $this->_initialize_configuration($mode, $domain, $password, $hostname, $master_hostname);

        // Set sane security policy
        //-------------------------

        if ($mode === self::MODE_SLAVE)
            $this->set_security_policy(self::POLICY_LAN);
        else
            $this->set_security_policy(self::POLICY_LOCALHOST);
       

        // Import the base LDIF data
        //--------------------------

        $this->_set_initialization_status(lang('openldap_importing_data'));

        $this->_import_ldif(self::FILE_DATA);

        // Do some cleanup tasks
        //----------------------

        $this->_set_initialization_status(lang('openldap_preparing_startup'));

        $this->_set_initialized();
        $this->_set_startup($start);
        $this->synchronize();

        $this->_set_initialization_status('');
    }

    /**
     * Initializes LDAP configuration.
     *
     * @param string $domain   Internet domain
     * @param string $password directory administrator password 
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _initialize_configuration($mode, $domain, $password, $hostname, $master_hostname = '')
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: validate

        // Initialize some variables
        //--------------------------

        $base_dn = preg_replace('/\./', ',dc=', $domain);
        $base_dn = "dc=$base_dn";

        $base_dn_rdn = preg_replace('/,.*/', '', $base_dn);
        $base_dn_rdn = preg_replace('/dc=/', '', $base_dn_rdn);

        $bind_pw = $password;
        $bind_dn = self::CONSTANT_BIND_DN_PREFIX . ',' . $base_dn;

        $shell = new Shell();

        $shell->execute(self::COMMAND_SLAPPASSWD, "-s $bind_pw");
        $bind_pw_hash = $shell->get_first_output_line();

        // Create internal configuration file
        //-----------------------------------

        $config = "mode = " . $mode . "\n";
        $config .= "base_dn = $base_dn\n";
        $config .= "bind_dn = $bind_dn\n";
        $config .= "bind_pw = $bind_pw\n";
        $config .= "bind_pw_hash = $bind_pw_hash\n";

        if ($mode === self::MODE_SLAVE)
            $config .= "master_hostname = $master_hostname\n";

        $file = new File(self::FILE_CONFIG);

        if ($file->exists())
            $file->delete();

        $file->create('root', 'webconfig', '0640');
        $file->add_lines($config);

        $this->config = NULL;

        // Create slapd.conf configuration
        //--------------------------------

        if ($mode === self::MODE_SLAVE)
            $slapd = $this->file_provision_slapd_config_replicate;
        else
            $slapd = $this->file_provision_slapd_config;

        $file = new File($slapd);

        $contents = $file->get_contents();
        $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
        $contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);
        $contents = preg_replace("/\@\@\@bind_pw\@\@\@/", $bind_pw, $contents);
        $contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);
        $contents = preg_replace("/\@\@\@domain\@\@\@/", $domain, $contents);
        $contents = preg_replace("/\@\@\@master_hostname\@\@\@/", $master_hostname, $contents);

        $newfile = new File(self::FILE_SLAPD_CONFIG);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('root', 'ldap', '0640');
        $newfile->add_lines("$contents\n");

        // Create ldap.conf configuration
        //-------------------------------

        $file = new File($this->file_provision_ldap_config);

        $contents = $file->get_contents();
        $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);

        $newfile = new File(self::FILE_LDAP_CONFIG);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('root', 'root', '0644');
        $newfile->add_lines("$contents\n");

        // Slave mode... bug out, we're done
        //----------------------------------

        if ($mode === self::MODE_SLAVE)
            return;

        // Create DB_CONFIG configuration
        //-------------------------------

        $file = new File($this->file_provision_dbconfig);

        $contents = $file->get_contents();

        $newfile = new File(self::FILE_DBCONFIG, TRUE);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('ldap', 'ldap', '0644');
        $newfile->add_lines("$contents\n");

        // DB_CONFIG configuration for accesslog
        //--------------------------------------

        $file = new File($this->file_provision_dbconfig);

        $contents = $file->get_contents();

        $newfile = new File(self::FILE_DBCONFIG_ACCESSLOG, TRUE);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('ldap', 'ldap', '0644');
        $newfile->add_lines("$contents\n");

        // LDAP provision data file
        //-------------------------

        $file = new File($this->file_provision_data);

        $contents = $file->get_contents();
        $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
        $contents = preg_replace("/\@\@\@base_dn_rdn\@\@\@/", $base_dn_rdn, $contents);
        $contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);

        $newfile = new File(self::FILE_DATA);

        if ($newfile->exists())
            $newfile->delete();

        $newfile->create('root', 'ldap', '0640');
        $newfile->add_lines("$contents\n");
    }

    /**
     * Imports an LDIF file.
     *
     * @param string $ldif LDIF file
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _import_ldif($ldif)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = $this->get_ldap_handle();

        // Shutdown LDAP if running
        //-------------------------

        $was_running = $this->get_running_state();

        if ($was_running)
            $this->set_running_state(FALSE);

        // Backup old LDAP
        //----------------

        $filename = self::PATH_LDAP_BACKUP . '/' . "backup-" . strftime("%m-%d-%Y-%H-%M-%S", time()) . ".ldif";

        try {
            if ($this->is_initialized())
                $this->export($filename);
        } catch (Exception $e) {
            // Exports can fail if LDAP is busted
        }

        // Clear out old database
        //-----------------------

        $folder = new Folder(self::PATH_LDAP);

        $filelist = $folder->get_recursive_listing();

        foreach ($filelist as $filename) {
            if (!preg_match('/DB_CONFIG$/', $filename)) {
                $file = new File(self::PATH_LDAP . '/' . $filename, TRUE);
                $file->delete();
            }
        }

        // Import new database
        //--------------------

        $shell = new Shell();
        $shell->execute(self::COMMAND_SLAPADD, '-n2 -l ' . $this->file_provision_accesslog_data, TRUE);
        $shell->execute(self::COMMAND_SLAPADD, '-n3 -l ' . $ldif, TRUE);

        // Fix file permissions
        //---------------------

        $folder->chown('ldap', 'ldap', TRUE);

        if ($was_running)
            $this->set_running_state(TRUE);
    }

    /**
     * Loads configuration file.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new Configuration_File(self::FILE_CONFIG);
            $this->config = $file->load();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        } catch (Exception $e) {
            throw new Engine_Exception(clearos_exception_message($e));
        }
    }

    /**
     * Sets initialized flag.
     * 
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_INITIALIZED);

        if (! $file->exists())
            $file->create("root", "root", "0644");
    }

    /**
     * Sets status message.
     * 
     * @param string $message status message
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_initialization_status($message)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_STATUS);

        if (! $file->exists())
            $file->create('root', 'root', '0644');

        $matches = $file->replace_lines('/^status_message =.*/', "status_message = $message\n");

        if ($matches === 0)
            $file->add_lines("status_message = $message\n");
    }

    /**
     * Sets startup policy
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_startup($start)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($this->ldaph === NULL)
            $this->ldaph = $this->get_ldap_handle();

        $this->set_boot_state(TRUE);

        if ($start)
            $this->restart();
    }

    /**
     * Synchronizes template files.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */
    
    protected function _synchronize_files()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load directory configuration settings
        //--------------------------------------

        if (is_null($this->config))
            $this->_load_config();

        $base_dn = (empty($this->config['base_dn'])) ? '' : $this->config['base_dn'];
        $bind_dn = (empty($this->config['bind_dn'])) ? '' : $this->config['bind_dn'];
        $bind_pw = (empty($this->config['bind_pw'])) ? '' : $this->config['bind_pw'];
        $bind_pw_hash = (empty($this->config['bind_pw_hash'])) ? '' : $this->config['bind_pw_hash'];
        $master_hostname = (empty($this->config['master_hostname'])) ? '' : $this->config['master_hostname'];

        // Synchronize all the configs 
        //----------------------------

        $folder = new Folder(self::PATH_SYNCHRONIZE);

        $sync_files = $folder->get_listing();

        foreach ($sync_files as $sync_file) {

            // Pull out metadata from sync files
            //----------------------------------

            $contents = '';
            $target = '';
            $owner = '';
            $group = '';
            $permissions = '';
            $warning = "Please do not edit - this file is automatically generated.\n\n";

            $file = new File(self::PATH_SYNCHRONIZE . '/' . $sync_file);
            $sync_contents = $file->get_contents_as_array();

            foreach ($sync_contents as $line) {
                if (preg_match('/CLEAROS_DIRECTORY_TARGET=/', $line))
                    $target = preg_replace('/.*CLEAROS_DIRECTORY_TARGET=/', '', $line);
                else if (preg_match('/CLEAROS_DIRECTORY_PERMISSIONS=/', $line))
                    $permissions = preg_replace('/.*CLEAROS_DIRECTORY_PERMISSIONS=/', '', $line);
                else if (preg_match('/CLEAROS_DIRECTORY_OWNER=/', $line))
                    $owner = preg_replace('/.*CLEAROS_DIRECTORY_OWNER=/', '', $line);
                else if (preg_match('/CLEAROS_DIRECTORY_GROUP=/', $line))
                    $group = preg_replace('/.*CLEAROS_DIRECTORY_GROUP=/', '', $line);
                else if (preg_match('/CLEAROS_DIRECTORY_WARNING_MESSAGE/', $line))
                    $contents .= preg_replace('/CLEAROS_DIRECTORY_WARNING_MESSAGE/', $warning, $line);
                else
                    $contents .= $line . "\n";
            }

            // Perform search replace on variables
            //------------------------------------

            $contents = preg_replace("/\@\@\@base_dn\@\@\@/", $base_dn, $contents);
            $contents = preg_replace("/\@\@\@bind_dn\@\@\@/", $bind_dn, $contents);
            $contents = preg_replace("/\@\@\@bind_pw\@\@\@/", $bind_pw, $contents);
            $contents = preg_replace("/\@\@\@bind_pw_hash\@\@\@/", $bind_pw_hash, $contents);

            // Write out file
            //---------------

            $target = new File($target);

            if ($target->exists())
                $target->delete();

            $target->create($owner, $group, $permissions);
            $target->add_lines($contents);
        }
    }
}

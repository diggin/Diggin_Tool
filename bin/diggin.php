<?php

/**
 * diggin
 *
 * borrowed from zf.php
 */
class diggin
{

    // ZF_HOME
    const SH_HOME = '%s_HOME';
    const SH_STORAGE_DIR = '%s_STORAGE_DIR';
    const SH_CONFIG_FILE = '%s_CONFIG_FILE';
    const SH_INCLUDE_PATH = '%s_CONFIG_FILE';
    const SH_STORAGE_DIRECTORY = '%s_STORAGE_DIRECTORY';
    const SH_INCLUDE_PATH_PREPEND = '%s_CONFIG_FILE';

    /**
     * @var string
     * shell name
     */
    private $_shName;

    /**
     * @var string
     * shell name
     */
    private $_shNameUpper;

    /**
     * @var bool
     */
    protected $_clientLoaded = false;
    
    /**
     * @var string
     */
    protected $_mode = 'runTool';
    
    /**
     * @var array of messages
     */
    protected $_messages = array();
    
    /**
     * @var string
     */
    protected $_homeDirectory = null;
    
    /**
     * @var string
     */
    protected $_storageDirectory = null;
    
    /**
     * @var string
     */
    protected $_configFile = null;
    
    /**
     * main()
     * 
     * @return void
     */
    public static function main($shName, $shNameUpper)
    {
        $diggin = new self();
        $diggin->_shName = $shName;
        $diggin->_shNameUpper = $shNameUpper;
        $diggin->bootstrap();
        $diggin->run();
    }
    
    /**
     * bootstrap()
     * 
     * @return diggin
     */
    public function bootstrap()
    {
        // detect settings
        $this->_mode             = $this->_detectMode();
        $this->_homeDirectory    = $this->_detectHomeDirectory();
        $this->_storageDirectory = $this->_detectStorageDirectory();
        $this->_configFile       = $this->_detectConfigFile();
        
        // setup
        $this->_setupPHPRuntime();
        $this->_setupToolRuntime();
    }
    
    /**
     * run()
     * 
     * @return ZF
     */
    public function run()
    {
        switch ($this->_mode) {
            case 'runError':
                $this->_runError();
                $this->_runInfo();
                break;
            case 'runSetup':
                if ($this->_runSetup() === false) {
                    $this->_runInfo();
                }
                break;
            case 'runInfo':
                $this->_runInfo();
                break;
            case 'runTool':
            default:
                $this->_runTool();
                break;
        }
        
        return $this;
    }
    
    /**
     * _detectMode()
     * 
     * @return ZF
     */
    protected function _detectMode()
    {
        $arguments = $_SERVER['argv'];

        $mode = 'runTool';
        
        if (!isset($arguments[0])) {
            return $mode;
        }
        
        if ($arguments[0] == $_SERVER['PHP_SELF']) {
            $this->_executable = array_shift($arguments);
        }
        
        if (!isset($arguments[0])) {
            return $mode;
        }
        
        if ($arguments[0] == '--setup') {
            $mode = 'runSetup';
        } elseif ($arguments[0] == '--info') {
            $mode = 'runInfo';
    	}
    	
    	return $mode;
    }
    

    /**
     * _detectHomeDirectory() - detect the home directory in a variety of different places
     * 
     * @param $mustExist Should the returned value already exist in the file system
     * @param $returnMessages Should it log messages for output later
     * @return string
     */
    protected function _detectHomeDirectory($mustExist = true, $returnMessages = true)
    {
        $homeDirectory = null;
        
        $homeDirectory = getenv(sprintf(self::SH_HOME, $this->_shNameUpper)); // check env var %s_HOME
        if ($homeDirectory) {
            $this->_logMessage('Home directory found in environment variable ZF_HOME with value ' . $homeDirectory, $returnMessages);
            if (!$mustExist || ($mustExist && file_exists($homeDirectory))) {
                return $homeDirectory;
            } else {
                $this->_logMessage('Home directory does not exist at ' . $homeDirectory, $returnMessages);
            }
        }
        
        $homeDirectory = getenv('HOME'); // HOME environment variable
        
        if ($homeDirectory) {
            $this->_logMessage('Home directory found in environment variable HOME with value ' . $homeDirectory, $returnMessages);
            if (!$mustExist || ($mustExist && file_exists($homeDirectory))) {
                return $homeDirectory;
            } else {
                $this->_logMessage('Home directory does not exist at ' . $homeDirectory, $returnMessages);
            }
            
        }

        $homeDirectory = getenv('HOMEPATH');
            
        if ($homeDirectory) {
            $this->_logMessage('Home directory found in environment variable HOMEPATH with value ' . $homeDirectory, $returnMessages);
            if (!$mustExist || ($mustExist && file_exists($homeDirectory))) {
                return $homeDirectory;
            } else {
                $this->_logMessage('Home directory does not exist at ' . $homeDirectory, $returnMessages);
            }
        }
        
        return false;
    }
    
    /**
     * _detectStorageDirectory() - Detect where the storage directory is from a variaty of possiblities
     * 
     * @param $mustExist Should the returned value already exist in the file system
     * @param $returnMessages Should it log messages for output later
     * @return string
     */
    protected function _detectStorageDirectory($mustExist = true, $returnMessages = true)
    {
        $storageDirectory = false;
        
        //$storageDirectory = getenv('ZF_STORAGE_DIR');
        $storageDirectory = getenv(sprintf(self::SH_HOME, $this->_shNameUpper));
        if ($storageDirectory) {
            $this->_logMessage('Storage directory path found in environment variable ZF_STORAGE_DIR with value ' . $storageDirectory, $returnMessages);
            if (!$mustExist || ($mustExist && file_exists($storageDirectory))) {
                return $storageDirectory;
            } else {
                $this->_logMessage('Storage directory does not exist at ' . $storageDirectory, $returnMessages);
            }
        }
        
        $homeDirectory = ($this->_homeDirectory) ? $this->_homeDirectory : $this->_detectHomeDirectory(true, false); 
        
        if ($homeDirectory) {
            //$storageDirectory = $homeDirectory . '/.zf/';
            $storageDirectory = $homeDirectory . '/.'.$this->_shName.'/';
            $this->_logMessage('Storage directory assumed in home directory at location ' . $storageDirectory, $returnMessages);
            if (!$mustExist || ($mustExist && file_exists($storageDirectory))) {
                return $storageDirectory;
            } else {
                $this->_logMessage('Storage directory does not exist at ' . $storageDirectory, $returnMessages);
            }
        }
        
        return false;
    }
    
    /**
     * _detectConfigFile() - Detect config file location from a variety of possibilities
     * 
     * @param $mustExist Should the returned value already exist in the file system
     * @param $returnMessages Should it log messages for output later
     * @return string
     */
    protected function _detectConfigFile($mustExist = true, $returnMessages = true)
    {
        $configFile = null;
        
        //$configFile = getenv('ZF_CONFIG_FILE');
        $configFile = getenv($c = sprintf(self::SH_CONFIG_FILE, $u = $this->_shNameUpper));
        if ($configFile) {
            //$this->_logMessage("Config file found environment variable ZF_CONFIG_FILE at " . $configFile, $returnMessages);
            $this->_logMessage("Config file found environment variable $c at " . $configFile, $returnMessages);
            unset($c);
            if (!$mustExist || ($mustExist && file_exists($configFile))) {
                return $configFile;
            } else {
                $this->_logMessage('Config file does not exist at ' . $configFile, $returnMessages);
            }
        }
        
        $homeDirectory = ($this->_homeDirectory) ? $this->_homeDirectory : $this->_detectHomeDirectory(true, false);
        if ($homeDirectory) {
            $configFile = $homeDirectory . '/.'.$this->_shName.'.ini';
            $this->_logMessage('Config file assumed in home directory at location ' . $configFile, $returnMessages);
            if (!$mustExist || ($mustExist && file_exists($configFile))) {
                return $configFile;
            } else {
                $this->_logMessage('Config file does not exist at ' . $configFile, $returnMessages);
            }
        }
        
        $storageDirectory = ($this->_storageDirectory) ? $this->_storageDirectory : $this->_detectStorageDirectory(true, false);
        if ($storageDirectory) {
            $configFile = $storageDirectory . '/'.$this->_shName.'.ini';
            $this->_logMessage('Config file assumed in storage directory at location ' . $configFile, $returnMessages);
            if (!$mustExist || ($mustExist && file_exists($configFile))) {
                return $configFile;
            } else {
                $this->_logMessage('Config file does not exist at ' . $configFile, $returnMessages);
            }
        }
        
        return false;
    }
    

    /**
     * _setupPHPRuntime() - parse the config file if it exists for php ini values to set
     * 
     * @return void
     */
    protected function _setupPHPRuntime()
    {
    	// set php runtime settings
    	ini_set('display_errors', true);
    	
        // support the changing of the current working directory, necessary for some providers
        if (isset($_ENV['ZEND_TOOL_CURRENT_WORKING_DIRECTORY'])) {
            chdir($_ENV['ZEND_TOOL_CURRENT_WORKING_DIRECTORY']);
        }
    	
        if (!$this->_configFile) {
            return;
        }
        $zfINISettings = parse_ini_file($this->_configFile);
        $phpINISettings = ini_get_all();
        foreach ($zfINISettings as $zfINIKey => $zfINIValue) {
            if (substr($zfINIKey, 0, 4) === 'php.') {
                $phpINIKey = substr($zfINIKey, 4); 
                if (array_key_exists($phpINIKey, $phpINISettings)) {
                    ini_set($phpINIKey, $zfINIValue);
                }
            }
        }

        return null;
    }
    
    /**
     * _setupToolRuntime() - setup the tools include_path and load the proper framwork parts that
     * enable Zend_Tool to work.
     * 
     * @return void
     */
    protected function _setupToolRuntime()
    {

        // last ditch efforts
        if ($this->_tryClientLoad()) {
            return;
        }
        
        // if ZF is not in the include_path, but relative to this file, put it in the include_path
        if (($includePathPrepend = getenv('ZEND_TOOL_INCLUDE_PATH_PREPEND')) || ($includePathFull = getenv('ZEND_TOOL_INCLUDE_PATH'))) {
            if (isset($includePathPrepend) && ($includePathPrepend !== false)) {
                set_include_path($includePathPrepend . PATH_SEPARATOR . get_include_path());
            } elseif (isset($includePathFull) && ($includePathFull !== false)) {
                set_include_path($includePathFull);
            }
        }
        
        if ($this->_tryClientLoad()) {
            return;
        }
        
        $zfIncludePath['relativePath'] = dirname(__FILE__) . '/../library/';
        if (file_exists($zfIncludePath['relativePath'] . 'Zend/Tool/Framework/Client/Console.php')) {
            set_include_path(realpath($zfIncludePath['relativePath']) . PATH_SEPARATOR . get_include_path());
        }
    
        if (!$this->_tryClientLoad()) {
            $this->_mode = 'runError';
            return;
        }
        
        return null;
    }
    
    /**
     * _tryClientLoad() - Attempt to load the Zend_Tool_Framework_Client_Console to enable the tool to run.
     * 
     * This method will return false if its not loaded to allow the consumer to alter the environment in such
     * a way that it can be called again to try loading the proper file/class.
     * 
     * @return bool if the client is actuall loaded or not
     */
    protected function _tryClientLoad()
    {
        $this->_clientLoaded = false;
        //$fh = @fopen('Zend/Tool/Framework/Client/Console.php', 'r', true);
        $fh = @fopen('Diggin/Tool/Framework/Client/Console.php', 'r', true);
        if (!$fh) {
            return $this->_clientLoaded; // false
        } else {
            fclose($fh);
            unset($fh);
            //include 'Zend/Tool/Framework/Client/Console.php';
            //$this->_clientLoaded = class_exists('Zend_Tool_Framework_Client_Console');
            include 'Diggin/Tool/Framework/Client/Console.php';
            $this->_clientLoaded = class_exists('Diggin_Tool_Framework_Client_Console');
        }
        
        return $this->_clientLoaded;
    }
    
    /**
     * _runError() - Output the error screen that tells the user that the tool was not setup
     * in a sane way
     * 
     * @return void
     */
    protected function _runError()
    {
        $shNameUpper = $this->_shNameUpper;
        $shName = $this->_shName;
        echo <<<EOS
    
***************************** $shNameUpper ERROR ********************************
In order to run the $shName command, you need to ensure that Zend Framework
is inside your include_path.  There are a variety of ways that you can
ensure that this $shName command line tool knows where the Zend Framework
library is on your system, but not all of them can be described here.

The easiest way to get the $shName command running is to allow is to give it
the include path via an environment variable ZEND_TOOL_INCLUDE_PATH or
ZEND_TOOL_INCLUDE_PATH_PREPEND with the proper include path to use,
then run the command "$shName --setup".  This command is designed to create
a storage location for your user, as well as create the $shName.ini file
that the $shName command will consult in order to run properly on your
system.  

Example you would run:

$ ZEND_TOOL_INCLUDE_PATH=/path/to/library $shName --setup

Your are encourged to read more in the link that follows.

EOS;

        return null;
    }
    
    /**
     * _runInfo() - this command will produce information about the setup of this script and
     * Zend_Tool
     * 
     * @return void
     */
    protected function _runInfo()
    {
        $shName = $this->_shName;
        echo 'Zend_Tool & CLI Setup Information' . PHP_EOL
           . '(available via the command line "zf --info")' 
           . PHP_EOL;
        
        echo '   * ' . implode(PHP_EOL . '   * ', $this->_messages) . PHP_EOL;
        
        echo PHP_EOL;
        
        echo 'To change the setup of this tool, run: "zf --setup"';
           
        echo PHP_EOL;

    }
    
    /**
     * _runSetup() - parse the request to see which setup command to run
     * 
     * @return void
     */
    protected function _runSetup()
    {
        $setupCommand = (isset($_SERVER['argv'][2])) ? $_SERVER['argv'][2] : null;
        
        switch ($setupCommand) {
            case 'storage-directory':
                $this->_runSetupStorageDirectory();
                break;
            case 'config-file':
                $this->_runSetupConfigFile();
                break;
            default:
                $this->_runSetupMoreInfo();
                break;
        }
        
        return null;
    }

    /**
     * _runSetupStorageDirectory() - if the storage directory does not exist, create it
     * 
     * @return void
     */
    protected function _runSetupStorageDirectory()
    {
        $storageDirectory = $this->_detectStorageDirectory(false, false);
        
        if (file_exists($storageDirectory)) {
            echo 'Directory already exists at ' . $storageDirectory . PHP_EOL
               . 'Cannot create storage directory.';
            return;
        }
        
        mkdir($storageDirectory);
        
        echo 'Storage directory created at ' . $storageDirectory . PHP_EOL;
    }

    /**
     * _runSetupConfigFile()
     * 
     * @return void
     */
    protected function _runSetupConfigFile()
    {
        $configFile = $this->_detectConfigFile(false, false);
        
        if (file_exists($configFile)) {
            echo 'File already exists at ' . $configFile . PHP_EOL
               . 'Cannot write new config file.';
            return;
        }
        
        $includePath = get_include_path();
        
        $contents = 'php.include_path = "' . $includePath . '"';
        
        file_put_contents($configFile, $contents);
        
        $iniValues = ini_get_all();
        if ($iniValues['include_path']['global_value'] != $iniValues['include_path']['local_value']) {
            echo 'NOTE: the php include_path to be used with the tool has been written' . PHP_EOL
               . 'to the config file, using ZF_INCLUDE_PATH (or other include_path setters)' . PHP_EOL
               . 'is no longer necessary.' . PHP_EOL . PHP_EOL;
        }
        
        echo 'Config file written to ' . $configFile . PHP_EOL;
        
        return null;
    }

    /**
     * _runSetupMoreInfo() - return more information about what can be setup, and what is setup
     * 
     * @return void
     */
    protected function _runSetupMoreInfo()
    {
        $homeDirectory    = $this->_detectHomeDirectory(false, false);
        $storageDirectory = $this->_detectStorageDirectory(false, false);
        $configFile       = $this->_detectConfigFile(false, false);
     
        $shName = $this->_shName;
        $shNameUpper = $this->_shNameUpper;
        $SH_HOME = sprintf(self::SH_HOME, $shNameUpper);
        //self::SH_STORAGE_DIR
        $SH_STORAGE_DIR = sprintf(self::SH_STORAGE_DIR, $shNameUpper);
        $SH_CONFIG_FILE  = sprintf(self::SH_CONFIG_FILE, $shNameUpper);
        $SH_STORAGE_DIRECTORY  = sprintf(self::SH_STORAGE_DIRECTORY, $shNameUpper);
        $SH_INCLUDE_PATH  = sprintf(self::SH_INCLUDE_PATH, $shNameUpper);
        $SH_INCLUDE_PATH_PREPEND  = sprintf(self::SH_INCLUDE_PATH_PREPEND, $shNameUpper);

        echo <<<EOS

$shNameUpper Command Line Tool - Setup
----------------------------
        
Current Paths (Existing or not):
    Home Directory: {$homeDirectory}
    Storage Directory: {$storageDirectory}
    Config File: {$configFile}

Important Environment Variables:
    $SH_HOME 
        - the directory this tool will look for a home directory
        - directory must exist
    $SH_STORAGE_DIRECTORY 
        - where this tool will look for a storage directory
        - directory must exist
    $SH_CONFIG_FILE 
        - where this tool will look for a configuration file
    $SH_INCLUDE_PATH
        - set the include_path for this tool to use this value
    $SH_INCLUDE_PATH_PREPEND
        - prepend the current php.ini include_path with this value
    
Search Order:
    Home Directory:
        - $SH_HOME, then HOME (*nix), then HOMEPATH (windows)
    Storage Directory:
        - $SH_STORAGE_DIR, then {home}/.$shName/
    Config File:
        - $SH_CONFIG_FILE, then {home}/.$shName.ini, then {home}/$shName.ini,
          then {storage}/$shName.ini

Commands:
    $shName --setup storage-directory
        - setup the storage directory, directory will be created
    $shName --setup config-file
        - create the config file with some default values


EOS;
    }
    
    /**
     * _runTool() - This is where the magic happens, dispatch Zend_Tool
     * 
     * @return void
     */
    protected function _runTool()
    {
	    
	    $configOptions = array();
	    if (isset($this->_configFile) && $this->_configFile) {
	        $configOptions['configOptions']['configFilepath'] = $this->_configFile;
	    }
	    if (isset($this->_storageDirectory) && $this->_storageDirectory) {
	        $configOptions['storageOptions']['directory'] = $this->_storageDirectory;
	    }
	    
	    // ensure that zf.php loads the Zend_Tool_Project features
	    //$configOptions['classesToLoad'] = 'Zend_Tool_Project_Provider_Manifest';
	    
	    //$console = new Zend_Tool_Framework_Client_Console($configOptions);
	    $console = new Diggin_Tool_Framework_Client_Console($configOptions);
        $console->setShName($this->_shName);
	    $console->dispatch();
		return null;
    }

    /**
     * _logMessage() - Internal method used to log setup and information messages.
     * 
     * @param $message
     * @param $storeMessage
     * @return void
     */
    protected function _logMessage($message, $storeMessage = true)
    {
        if (!$storeMessage) {
            return;
        }
        
        $this->_messages[] = $message;
    }
    
    
}

//for debug
set_include_path(dirname(__FILE__).'/../library' . PATH_SEPARATOR . get_include_path());

$shName = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME);
$shNameUpper = strtoupper($shName);
if (!getenv($shNameUpper."_NO_MAIN")) {
    diggin::main($shName, $shNameUpper);
}

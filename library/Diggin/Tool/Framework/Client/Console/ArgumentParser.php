<?php

/**
 * @see Zend_Tool_Framework_Client_Console_ArgumentParser
 */
require_once 'Zend/Tool/Framework/Client/Console/ArgumentParser.php';

/**
 * @category   Diggin
 * @package    Diggin_Tool
 */
class Diggin_Tool_Framework_Client_Console_ArgumentParser extends Zend_Tool_Framework_Client_Console_ArgumentParser
{
    private $_shName;

    public function setShName($shName)
    {
        $this->_shName = $shName;
    }

    /**
     * _createHelpResponse
     *
     * @param unknown_type $options
     */
    protected function _createHelpResponse($options = array())
    {
        require_once 'Diggin/Tool/Framework/Client/Console/HelpSystem.php';
        $helpSystem = new Diggin_Tool_Framework_Client_Console_HelpSystem();
        $helpSystem->setShName($this->_shName);
        $helpSystem->setRegistry($this->_registry);

        if (isset($options['error'])) {
            $helpSystem->respondWithErrorMessage($options['error']);
        }

        if (isset($options['actionName']) && isset($options['providerName'])) {
            $helpSystem->respondWithSpecialtyAndParamHelp($options['providerName'], $options['actionName']);
        } elseif (isset($options['actionName'])) {
            $helpSystem->respondWithActionHelp($options['actionName']);
        } elseif (isset($options['providerName'])) {
            $helpSystem->respondWithProviderHelp($options['providerName']);
        } else {
            $helpSystem->respondWithGeneralHelp();
        }

    }

}

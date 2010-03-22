<?php
require_once 'Diggin/Tool/Framework/Client/Console.php';


class DigginX_Tool_Framework_Client_Console extends Diggin_Tool_Framework_Client_Console
{

    protected function _preInit()
    {
        $config = $this->_registry->getConfig();
        if ($this->_configOptions != null) {
            $config->setOptions($this->_configOptions);
        } 

        if ($config->storage->adapter == 'couchdb') {
            require_once 'DigginX/Tool/Framework/Client/Storage/CouchDbAttachment.php';
            $storage = new Zend_Tool_Framework_Client_Storage(array('adapter' => new DigginX_Tool_Framework_Client_Storage_CouchDbAttachment()));

            $this->_registry->setStorage($storage);
        }

        return parent::_preInit();
    }


    /**
    public function getMissingParameterPromptString(Zend_Tool_Framework_Provider_Interface $provider, Zend_Tool_Framework_Action_Interface $actionInterface, $missingParameterName)
    {  
        //var_dump($provider instanceof Zend_Tool_Framework_Provider_Abstract);
        //$r = new ReflectionClass($provider);
        //var_dump($r->getProperties());
        //var_dump($provider, $actionInterface, $missingParameterName);
        //var_dump($actionInterface, $missingParameterName);
        
        $request = new DigginX_Tool_Framework_Interactive_InputRequest('please');
        $request->setProvider($provider);
        $request->setAction($actionInterface);
        $request->setMissingParameterName($missingParameterName);

        readline_completion_function(array($this, 'completion'));

        //return new Zend_Tool_Framework_Client_Interactive_InputRequest('pleaaasssss');
        return 'Please provide a value for $' . $missingParameterName;
    }

    public function completion($line, $pos, $cursor)
    {
        return array('fuga', 'hoge', 'hogee','hogege', 'hsjs');
    }

    public function handleInteractiveInputRequest(Zend_Tool_Framework_Client_Interactive_InputRequest $inputRequest)
    {
        //fwrite(STDOUT, $inputRequest->getContent() . PHP_EOL . $this->_shName . '> ');
        $line = readline($inputRequest->getContent() . PHP_EOL . $this->getShName() . '> ');

        readline_add_history($line);
        $inputContent = $line;
        return rtrim($inputContent); // remove the return from the end of the string
    }
    */
}

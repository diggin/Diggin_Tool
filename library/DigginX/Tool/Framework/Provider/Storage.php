<?php
require_once 'Zend/Tool/Framework/Provider/Abstract.php';
class DigginX_Tool_Framework_Provider_Storage 
    extends Zend_Tool_Framework_Provider_Abstract
{

    public function put($name, $value)
    {
        $storage = $this->_registry->getStorage();

        $ret = $this->_registry->getStorage()->put($name, $value);
        
        $this->_registry->getResponse()->appendContent('put storage');
    }

    public function has($name)
    {
        $ret = $this->_registry->getStorage()->has($name);


    }

    public function get($name)
    {
        $ret = $this->_registry->getStorage()->get($name);
    
        $this->_registry->getResponse()->appendContent($ret);
    }

    public function getStreamUri($name)
    {
        $ret = $this->_registry->getStorage()->getStreamUri($name);

        $this->_registry->getResponse()->appendContent($ret);
    }

    public function remove($name)
    {
        $ret = $this->_registry->getStorage()->remove($name);

        $this->_registry->getResponse()->appendContent('remove');
    }


}


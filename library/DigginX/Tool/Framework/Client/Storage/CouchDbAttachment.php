<?php
require_once 'Zend/Tool/Framework/Client/Storage/AdapterInterface.php';

class DigginX_Tool_Framework_Client_Storage_CouchDbAttachment
    implements Zend_Tool_Framework_Client_Storage_AdapterInterface
{
    /** Sopha_Db */
    private $_db;
    protected $_dbname = 'digginx';
    public $_docname = 'zf';

    private $_lastDocument = array();

    public function put($name, $value)
    {

        $doc = $this->_getDoc();

        if (substr($name, -5) == '.html') {
            $type = 'text/html';
        }

        $doc->setAttachment($name, $type, $value);

        return $this->getDb()->update($doc);
    }

    public function get($name)
    {
        $attachment = $this->_getDoc()->getAttachment($name);

        return $attachment->getData();
    }

    public function has($name)
    {
        return ($this->_getDoc()->getAttachment($name)) ? true : false ;
    }

    protected function _getDoc()
    {
        return $this->_retrieve($this->_docname);
    }

    protected function _retrieve($name)
    {
        if (!isset($this->_lastDocument[$name])) {
            $this->_lastDocument[$name] = $this->getDb()->retrieve($name);
        }

        return $this->_lastDocument[$name];
    }

    public function remove($name) 
    {
        $db = $this->getDb();

        $doc = $this->_getDoc();
        $ret = $db->delete(array($this->_docname, $name), $doc->getRevision());
        
        return $ret;
    }

    public function getStreamUri($name)
    {
        return implode('/', array($this->_getDoc()->getUrl(), $name));
    }

    public function getDb()
    {
        if (!$this->_db) {
            require_once 'Sopha/Db.php';
            $this->_db = new Sopha_Db($this->_dbname);
        }

        return $this->_db;
    }
}


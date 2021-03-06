<?php
class Enlight_Template_BlockArgs extends Enlight_Collection_ArgsCollection
{
    protected $_processed;
    protected $_name;
	protected $_return;
	
    public function __construct($name, array $args=null)
	{
		$this->name = $name;
        parent::__construct($args);
	}
    public function stop()
	{
		$this->_processed = true;
	}
	public function setProcessed($processed)
	{
		$this->_processed = (bool) $processed;
	}
	public function isProcessed()
	{
		return $this->_processed;
	}
	public function setName($name)
	{
		$this->_name = $name;
	}
    public function getName()
	{
		return $this->_name;
	}
	public function setReturn($return)
	{
		$this->_return = $return;
	}
	public function getReturn()
	{
		return $this->_return;
	}
}
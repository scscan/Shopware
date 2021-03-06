<?php
/**
 * Enlight Mail Component
 */
class Enlight_Components_Mail extends Zend_Mail
{
	protected $_isHtml = false;
	protected $_fromName = null;
	protected $_plainBody = null;
	protected $_plainBodyText = null;
	
	/**
	 * Set mail html mode
	 *
	 * @deprecated 
	 * @param bool $isHtml
	 */
	public function IsHTML($isHtml = true)
	{
		$this->_isHtml = (bool) $isHtml;
	}
	
	/**
	 * Add a recipient to mail
	 *
	 * @deprecated 
	 * @param string $email
	 * @param string $name
	 * @return Zend_Mail
	 */
	public function AddAddress($email, $name = '')
	{
		return $this->addTo($email, $name);
	}
	
	/**
     * Clears list of recipient email addresses
     * 
     * @deprecated 
     */
	public function ClearAddresses()
	{
		return $this->clearRecipients();
	}
	
	/**
     * Adds an existing attachment to the mail message
     *
     * @param  Zend_Mime_Part $attachment
     * @return Zend_Mail Provides fluent interface
     */
	public function addAttachment($attachment)
    {
    	if(!$attachment instanceof Zend_Mime_Part) {
    		if(func_num_args() > 1) {
    			$filename = func_get_arg(1);
    		} else {
    			$filename = basename($attachment);
    		}
    		$this->createAttachment(
    			file_get_contents($attachment),
    			Zend_Mime::TYPE_OCTETSTREAM,
                Zend_Mime::DISPOSITION_ATTACHMENT,
                Zend_Mime::ENCODING_BASE64,
                $filename
            );
            return $this;
    	}
        return parent::addAttachment($attachment);
    }
	
    /**
     * Sets From-header and sender of the message
     *
     * @param  string    $email
     * @param  string    $name
     * @return Zend_Mail Provides fluent interface
     * @throws Zend_Mail_Exception if called subsequent times
     */
	public function setFrom($email, $name = null)
    {
    	$this->_fromName = $name;
    	return parent::setFrom($email, $name);
    }
    
    /**
     * Clears the sender from the mail
     *
     * @return Zend_Mail Provides fluent interface
     */
    public function clearFrom()
    {
    	$this->_fromName = null;
    	return parent::clearFrom();
    }
    
    /**
     * Returns from name
     *
     * @return unknown
     */
    public function getFromName()
    {
    	return $this->_fromName;
    }
    
    /**
     * Returns a list of recipient email addresses
     *
     * @return array
     */
    public function getTo()
    {
        return $this->_to;
    }
    
    /**
     * Sets the text body for the message.
     *
     * @param  string $txt
     * @param  string $charset
     * @param  string $encoding
     * @return Zend_Mail Provides fluent interface
    */
    public function setBodyText($txt, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
    	$this->_plainBodyText = $txt;
    	return parent::setBodyText($txt, $charset, $encoding);
    }
    
    /**
     * Sets the HTML body for the message
     *
     * @param  string    $html
     * @param  string    $charset
     * @param  string    $encoding
     * @return Zend_Mail Provides fluent interface
     */
    public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
    	$this->_plainBody = $html;
    	return parent::setBodyHtml($html, $charset, $encoding);
    }
    
	/**
     * Returns plain body html
     *
     * @return string|null
     */
    public function getPlainBody()
    {
    	return $this->_plainBody;
    }
    
    /**
     * Returns plain body text
     *
     * @return string|null
     */
    public function getPlainBodyText()
    {
    	return $this->_plainBodyText;
    }
    
    /**
	 * Magic setter method
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'From':
				$fromName = $this->getFromName();
				$this->clearFrom();
				$this->setFrom($value, $fromName);
				break;
			case 'FromName':
				$from = $this->getFrom();
				$this->clearFrom();
				$this->setFrom($from, $value);
				break;
			case 'Subject':
				$this->clearSubject();
				$this->setSubject($value);
				break;
			case 'Body':
				if($this->_isHtml) {
					$this->setBodyHtml($value);
				} else {
					$this->setBodyText($value);
				}
				break;
			case 'AltBody':
				if($this->_isHtml) {
					$this->setBodyText($value);
				}
				break;
		}
	}
	
	/**
	 * Magic getter method
	 *
	 * @param string $name
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'From':
				return $this->getFrom();
				break;
			case 'FromName':
				return $this->getFromName();
				break;
			case 'Subject':
				return $this->getSubject();
				break;
			case 'Body':
				if($this->_isHtml) {
					return $this->_plainBody;
				} else {
					return $this->_plainBodyText;
				}
				break;
			case 'AltBody':
				return $this->_plainBodyText;
				break;
		}
	}
}
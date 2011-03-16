<?php
/**
 * Shopware test listener
 *
 * @link http://www.shopware.de
 * @copyright Copyright (c) 2011, shopware AG
 * @author Heiner Lohaus
 * @package Shopware
 * @subpackage Components
 */
class Shopware_Components_Test_Listener extends PHPUnit_Extensions_TicketListener
{
	protected $serverAddress;
	protected $printTicketStateChanges;
	protected $notifyTicketStateChanges;
	
	/**
	 * Constructor method
	 * 
	 * @param string|array $options
	 */
	public function __construct($serverAddress, $printTicketStateChanges=false , $notifyTicketStateChanges=false)
    {
    	$this->serverAddress = $serverAddress;
    	$this->printTicketStateChanges = $printTicketStateChanges;
    	$this->notifyTicketStateChanges = $notifyTicketStateChanges;
    }
        
    /**
     * Get the status of a ticket message
     *
     * @param  integer $ticketId The ticket ID
     * @return array('status' => $status) ($status = new|closed|unknown_ticket)
     */
    public function getTicketInfo($ticketId = null)
    {
    	if (!is_numeric($ticketId)) {
    		return array('status' => 'invalid_ticket_id');
    	}
    	try {
    		$info = $this->getClient()->call('ticket.get', (int) $ticketId);
    		switch ($info[3]['jenkins']) {
    			case 'Test erfolgreich':
    				return array('status' => 'closed');
    			case '':
    			case 'Kein Test':
    			case 'Test fehlgeschlagen':
    				return array('status' => 'new');
    			default:
    				return array('status' => 'unknown_ticket');
    		}
    	}
    	catch (Exception $e) {
    		return array('status' => 'unknown_ticket');
    	}
    }

    /**
     * Update a ticket with a new status
     *
     * @param string $ticketId   The ticket number of the ticket under test (TUT).
     * @param string $statusToBe The status of the TUT after running the associated test.
     * @param string $message    The additional message for the TUT.
     * @param string $resolution The resolution for the TUT.
     */
    protected function updateTicket($ticketId, $statusToBe, $message, $resolution)
    {
        $this->getClient()->call('ticket.update', array(
        	(int) $ticketId,
        	$message,
        	null,
        	null,
        	array(
        		'jenkins_date' => Zend_Date::now()->toString('YYYY-MM-dd HH:mm:ss'),
        		'jenkins' => $statusToBe=='closed' ? 'Test erfolgreich' : 'Test fehlgeschlagen',
        		'resolution' => $resolution
        	),
        	$this->notifyTicketStateChanges
        ));

        if ($this->printTicketStateChanges) {
            printf(
              "\nUpdating Trac issue #%d, status: %s\n",
              $ticketId,
               $statusToBe=='closed' ? 'Test erfolgreich' : 'Test fehlgeschlagen'
            );
        }
    }

    /**
     * Returns xml rpc client
     *
     * @return Zend_XmlRpc_Client
     */
    protected function getClient()
    {
        return new Zend_XmlRpc_Client($this->serverAddress);
    }

    /**
     * Test ended method
     *
     * @param PHPUnit_Framework_Test $test
     * @param float $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        if (!$test instanceof PHPUnit_Framework_Warning) {
            if ($test->getStatus() == PHPUnit_Runner_BaseTestRunner::STATUS_PASSED) {
                $ifStatus   = array('assigned', 'new', 'reopened');
                $newStatus  = 'closed';
                $message    = 'Automatically closed by PHPUnit (test passed).';
                $resolution = 'fixed';
                $cumulative = TRUE;
            }

            else if ($test->getStatus() == PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE) {
                $ifStatus   = array('closed');
                $newStatus  = 'reopened';
                $message    = 'Automatically reopened by PHPUnit (test failed).';
                $resolution = '';
                $cumulative = FALSE;
            }

            else {
                return;
            }

            $name = $test->getName();
            $pos = strpos($name, ' with data set');
            if ($pos !== FALSE) {
            	$name = substr($name, 0, $pos);
            }
            $tickets = PHPUnit_Util_Test::getTickets(get_class($test), $name);

            foreach ($tickets as $ticket) {
                // Remove this test from the totals (if it passed).
                if ($test->getStatus() == PHPUnit_Runner_BaseTestRunner::STATUS_PASSED) {
                    unset($this->ticketCounts[$ticket][$name]);
                }

                // Only close tickets if ALL referenced cases pass
                // but reopen tickets if a single test fails.
                if ($cumulative) {
                    // Determine number of to-pass tests:
                    if (count($this->ticketCounts[$ticket]) > 0) {
                        // There exist remaining test cases with this reference.
                        $adjustTicket = FALSE;
                    } else {
                        // No remaining tickets, go ahead and adjust.
                        $adjustTicket = TRUE;
                    }
                } else {
                    $adjustTicket = TRUE;
                }

                $ticketInfo = $this->getTicketInfo($ticket);

                if ($adjustTicket && in_array($ticketInfo['status'], $ifStatus)) {
                    $this->updateTicket($ticket, $newStatus, $message, $resolution);
                }
            }
        }
    }
}
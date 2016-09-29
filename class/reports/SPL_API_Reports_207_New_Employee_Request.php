<?php

class SPL_API_Reports_207_New_Employee_Request extends SPL_API_Reports {

    var $path = '/var/web/---/php/swiftmailer/lib/swift_required.php';
    var $smtp = 'mail.spokanelibrary.org';
    var $port = '25';

	public function getReportData() {
		//return $this->params['vals']['branch'];
		
		$report = new stdClass();

		$report->sorted = $this->params['vals'];

        require_once $this->path;
        $transport = Swift_SmtpTransport::newInstance($this->smtp, $this->port);
        $mailer = Swift_Mailer::newInstance($transport);

        $message = Swift_Message::newInstance('New Employee Request')
        ->setFrom(array('itsupport@spokanelibrary.org' => 'IT Support'))
        //->setTo(array('sgirard@spokanelibrary.org' => 'IT Support'))
        ->setTo(array('itsupport@spokanelibrary.org' => 'IT Support'))
        ->setBody($this->getMessage(), 'text/html')
        ;

        if ( $this->params['vals']['cc'] ) {
            // allow comma-separated addresses
            if ( stristr($this->params['vals']['cc'], ',') ) {
                $cc = explode(',', $this->params['vals']['cc']);
                $message->setCc($cc);
            
                $message->setFrom($cc[0]);
            } else {
                $message->setFrom($this->params['vals']['cc']);
                //$message->setCc($this->params['vals']['cc']);
            }
        }

        // Send the message
        $result = $mailer->send($message);
		


        return $report;
	}

    protected function getMessage() {
        $width = '600px';
        
        $vals = $this->params['vals'];


        $msg .= '<table style="width:'.$width.';">'.PHP_EOL;

        $msg .= $this->getMessageRow('Start Date', $vals['datebegin']);

        $msg .= $this->getMessageRow('First Name', $vals['firstname']);
        $msg .= $this->getMessageRow('Last Name', $vals['lastname']);
        if ( $vals['displayname'] ) {
            $msg .= $this->getMessageRow('Display Name', $vals['displayname']);
        }
        $msg .= $this->getMessageRow('Job Title', $vals['jobtitle']);
        $msg .= $this->getMessageRow('Department', $vals['department']);
        $msg .= $this->getMessageRow('Primary Location', $vals['location']);
        

        $msg .= $this->getMessageRow('Keycard', $this->getCheckboxValue($vals['keycard']));
        $msg .= $this->getMessageRow('Phone', $this->getCheckboxValue($vals['phone']));
        $msg .= $this->getMessageRow('Email', $this->getCheckboxValue($vals['email']));
        $msg .= $this->getMessageRow('Horizon', $this->getCheckboxValue($vals['horizon']));
        $msg .= $this->getMessageRow('The Lens', $this->getCheckboxValue($vals['lens']));

        if ( $vals['notes'] ) {
            $msg .= $this->getMessageRow('Notes', nl2br($vals['notes']));
        }

        $msg .= '</table>'.PHP_EOL;

        $msg .= '<hr>'.PHP_EOL;

        $msg .= '<table style="width:'.$width.';">'.PHP_EOL;

        $msg .= $this->getMessageRow('IT Staff', 'Please reply with updates as you complete tasks.');

        $msg .= '</table>'.PHP_EOL;

        //$msg .= '<hr>'.PHP_EOL;
        //$msg .= print_r($vals, true);
        
        return $msg;
    }

    protected function getCheckboxValue($value) {
        return isset($value) ? ucfirst($value) : 'No';
    }

    protected function getMessageRow($label, $value) {
        $msg = '';
        
        $msg .= '<tr>'.PHP_EOL;
        $msg .= '<td style="width:100px; text-align:right; vertical-align:top;">'.PHP_EOL;
        $msg .= '<b>'.$label.'</b>'.PHP_EOL;
        $msg .= '</td>'.PHP_EOL;
        $msg .= '<td>'.PHP_EOL;
        $msg .= stripslashes($value).PHP_EOL;
        $msg .= '</td>'.PHP_EOL;
        $msg .= '</tr>'.PHP_EOL;

        return $msg;
    }

}

?>
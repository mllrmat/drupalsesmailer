<?php

use Pelago\Emogrifier;


class SESMailSystem implements MailSystemInterface {
	
	public function format(array $message){
		
		$line_endings = variable_get('mail_line_endings', MAIL_LINE_ENDINGS);
		$message['body'] = implode($line_endings, $message['body']);
		
		
		$message['text'] = drupal_html_to_text($message['body']);
		$message['text'] = drupal_wrap_mail($message['text']);

		// we allow modules to set a special themehook for each message
		$html = '';
		$themehook = 'sesmail';
		
		if(isset($message['themehook'])){
			$themehook = $message['themehook'];
		} 
		
		$html = theme($themehook, $message);
		
		$theme_key = mailsystem_get_mail_theme();
		$theme_path = drupal_get_path('theme', $theme_key);
		
		$emogrifier = new Emogrifier();

		// put properties such as height, width, background color and font color from your CSS into the available HTML tags
		$emogrifier->enableCssToHtmlMapping();
		//$emogrifier->addExcludedSelector('.preview'); //does not work, see https://github.com/jjriv/emogrifier/issues/347

		
		$emogrifier->setHtml($html);
		
		//we load the css corresponding to the themehook set
		$css_file = $theme_path . "/css/$themehook.css";
		
		if(!file_destination($css_file, FILE_EXISTS_ERROR)){
			$handle = fopen($css_file, "r");
			$contents = fread($handle, filesize($css_file));
			fclose($handle);
			$emogrifier->setCss($contents);
		}
			
		$html = $emogrifier->emogrify();
		
		$message['html'] = $html; 
		
		return $message;
	}
	
	public function mail(array $message) {
		
		$recipients = array();
		//check if we have several recipients, then we have to send multiple mails
		if(strpos($message['to'], ',') !== FALSE){
			$recipients = explode(',', $message['to']);
		} else {
			$recipients = array($message['to']);
		}
		
		
		$success = false;
		
		foreach ($recipients as $recipient){
			$recipient = trim($recipient);
			
			if(valid_email_address($recipient) && /*&& valid_email_address($message['from'])*/ $message['subject'] != ''){
				
				$source = $message['from'];
				
				if(!valid_email_address($source)){
					$pos = strpos($source, '<');
					$name = trim(substr($source, 0, $pos));
					$email = trim(strrchr($source, '<'));
					$source = '=?utf-8?q?' . str_replace(' ', '=20', quoted_printable_encode($name)) . '?= ' . $email;
				}
				
				$data = array();
				$data['module'] = $message['module'];
				$data['mailkey'] = $message['key'];
				$data['destination'] = $recipient;
				$data['source'] = $source;
				$data['subject'] = $message['subject'];
				$data['html'] = $message['html'];
				$data['text'] = $message['text'];
				
				$data['timestamp'] = time();
				
				try{
					
					$query = db_insert('ses_mails')
					->fields($data);
					
					$message['mid'] = $query->execute();
					
				} catch (Exception $e){
			        watchdog('SES mail', 'Failed to insert message: ' . $e->getMessage());
		    		$success = false; 
		    	}
	
		    	
		    	if(isset($message['send_direct']) && $message['send_direct'] == true){
		    		//send message directly and bypass queue
		    		//nevertheless we load it from the database to have the same format as in all other sends.
		    		$result = db_select('ses_mails', 'm')
		    		->fields('m')
		    		->condition('mid', $message['mid'])
		    		->isNull('messageid')
		    		->execute();
		    		
		    		if($result->rowCount() > 0){
		    			ses_mail_send($result->fetchAllAssoc('mid'));
		    		}
		    	} 
		    	$success = true; 
			} else {
				$success = false; 
			}
		}
		
		return $success;
	}
}

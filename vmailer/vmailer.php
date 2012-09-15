<?php
/**
 * @author:  Vihren 'vcrazy' Ganev <vcrazy@abv.bg>, <http://ganev.bg>, <https://github.com/vcrazy>
 * @package All
 * @category Standalone Multiple Mail Sender
 * @version 20110218
 * 
 * Generates and sends mail.
 * Has some interesting features:
 *  sending the message in html and plain text format
 *	attach multiple files
 *	send several messages at once
 *	..others
 */
class vMailer
{
    protected $_from = 'no-reply@ganev.bg';
    protected $_reply_to = 'no-reply@ganev.bg';
    protected $_return_path = 'no-reply@ganev.bg';

    protected $_subject = 'ganev.bg';
    protected $_to = array();

    protected $_message = '';
    protected $_plain_message = '';

    protected $_headers = '';
    protected $_message_to_send = '';
    protected $_attachments = array(); // container for all attached files

    protected $_x_priority = '3';
    
    protected $_boundary = '';
    protected $_attachments_boundary = '';
    protected $_flag = '';

    public function __construct($params = array())
    {
        /**
         * @params
         * from
         * reply_to
         * return_path
         * x_priority
         * subject
         * message
		 * attachments
		 * to
        */

        foreach($params as $param => $value) // set all parameters you have received
        {
            if(is_array($value))
            {
                foreach($value as $attached_file) // works for attached files and recipients
                {
                    $this->{'_' . $param}[] = $attached_file;
                }
            }
            else
            {
                if(in_array($param, array('attachments', 'to')))
                {
                    $this->{'_' . $param}[] = $value;
                }
                else
                {
                    $this->{'_' . $param} = $value;
                }
            }
        }
    }

    public function send($emails = '')
    {
        $this->_encode_cyrillic(); // encodes the cyrillic in all cyrillic headers
        $this->_generate_boundary(); // generates unique boundary

        $this->_generate_headers(); // generates the message headers
        $this->_generate_message(); // generates the message text

        if($emails == '') // emails are added earlier, just send them
        {
            if(count($this->_to) > 0)
            {
                foreach($this->_to as $email)
                {
                    // send the emails if any
                    $this->_send_mail($email);
                }
            }
        }
        else // emails come like function argument
        {
            if(is_array($emails)) // if emails come in an array
            {
                foreach($emails as $email)
                {
                    // send the emails if any
                    $this->_send_mail($email);
                }
            }
            else // just one email
            {
                // send it
                return $this->_send_mail($emails);
            }
        }

        return;
    }

    protected function _send_mail($email)
    {
		if($this->_flag != '') // if -f set the -f
		{
			$sended = @mail($email, $this->_subject, $this->_message_to_send, $this->_headers, $this->_flag);
		}
		else
		{
			$sended = @mail($email, $this->_subject, $this->_message_to_send, $this->_headers);
		}

		return $sended;
    }

    protected function _encode_cyrillic()
    {
        foreach($cyrillic_in_headers = array('from', array('to'), 'reply_to', 'return_path', 'subject') as $header_part => $value)
        {
            $header_part_orig = $header_part;
            $header_part = $value;

            if(!is_array($header_part))
            {
                $value = $this->{'_' . $value};

                if(mb_strlen($value, 'utf-8') > 0)
                {
                    // now we have to encode the cyrillic characters and not only..
                    if((mb_strpos($value, '@') != FALSE) && (mb_strpos($value, ' ') != FALSE)) // if we have email address AND name
                    {
                        $last_white_space = mb_strrpos($value, ' ', 'utf-8');
                        $from_email = mb_substr($value, $last_white_space + 1, mb_strlen($value, 'utf-8') - $last_white_space - 1, 'utf-8');
                        $from_name = mb_substr($value, 0, $last_white_space, 'utf-8');

                        $this->{'_' . $header_part} = '=?UTF-8?B?' . base64_encode($from_name) . '?= ' . $from_email;
                    }
					elseif(mb_strpos($value, '@') != FALSE) // we have email only
					{
						$this->{'_' . $header_part} = $value;
					}
                    else // we have only text ( is subject )
                    {
                        // encode the name
                        $this->{'_' . $header_part} = '=?UTF-8?B?' . base64_encode($value) . '?=';
                    }
                }
            }
            else
            {
				$action = $header_part[0];
				$emails = $this->{'_' . $action};
				if(count($emails) > 0)
				{
					for($i=0; $i<count($emails); $i++)
					{
						$str = $emails[$i];
						if((mb_strpos($str, '@') != FALSE) && (mb_strpos($str, ' ') != FALSE)) // if we have email address AND name
						{
							$last_white_space = mb_strrpos($str, ' ', 'utf-8');
							$from_email = mb_substr($str, $last_white_space + 1, mb_strlen($str, 'utf-8') - $last_white_space - 1, 'utf-8');
							$from_name = mb_substr($str, 1, $last_white_space - 2, 'utf-8');
							$this->{'_' . $action}[$i] = '=?UTF-8?B?' . base64_encode($from_name) . '?= ' . $from_email;
						}
						elseif(mb_strpos($str, ' ') != FALSE) // we have only name
						{
							// encode the name
							$this->{'_' . $action}[$i] = '=?UTF-8?B?' . chunk_split(base64_encode($str)) . '?=';
						}
					}
				}
            }
        }
    }

    protected function _generate_boundary($attachment = FALSE)
    {
		$site = Helper::get_domain();
        if($attachment)
        {
            $this->_attachments_boundary = $site . '.att.' . mt_rand() . time() . mt_rand();
        }
        else
        {
            $this->_boundary = $site . '.' . mt_rand() . time() . mt_rand();
        }
    }

    protected function _generate_headers()
    {
        $headers = 'MIME-Version: 1.0' . "\n";
        $headers .= 'From: ' . $this->_from . "\n";
        $headers .= 'Reply-To: ' . $this->_reply_to . "\n"; // can be commented
        $headers .= 'Return-Path: ' . $this->_return_path . "\n";

        if(count($this->_attachments) > 0) // attachments
        {
            $this->_generate_boundary(TRUE);
            $headers .= 'Content-Type: multipart/mixed;boundary=' . $this->_attachments_boundary . "\n";
        }
        else // no attachments
        {
            $headers .= 'Content-type: multipart/alternative;boundary=' . $this->_boundary . "\n";
        }

        $headers .= 'X-Priority: ' . $this->_x_priority . "\n"; // can be commented
        $headers .= 'X-Mailer: vMailer'; // can be commented

        $this->_headers = $headers;
    }

    protected function _generate_message()
    {
        if(count($this->_attachments) > 0) // attachment(s)!
        {
            $message = 'This is a multi-part message in MIME format.' . "\n";
            $message .= "\n\n--" . $this->_attachments_boundary . "\n";
            $message .= 'Content-type: multipart/alternative;boundary=' . $this->_boundary . "\n";
        }
        else
        {
            $message = 'This is a MIME encoded message.' . "\n";
        }

        $message .= "\n\n--" . $this->_boundary . "\n";

        // plain text
        $message .= 'Content-Type: text/plain;charset=utf-8' . "\n";
		$message .= 'Content-Transfer-Encoding: quoted-printable' . "\n\n";
		$message .= quoted_printable_encode($this->_plain_message) . "\n";

        // html
        if($this->_message != '')
        {
            $message .= "\n\n--" . $this->_boundary . "\n";

            $message .= 'Content-Type: text/html;charset=utf-8' . "\n";
			$message .= 'Content-Transfer-Encoding: quoted-printable' . "\n\n";
			$message .= quoted_printable_encode($this->_message) . "\n";

        }

        $message .= "\n\n--" . $this->_boundary . '--' . "\n\n";

        // attached file(s)
        if(count($this->_attachments) > 0) // yes
        {
            foreach($this->_attachments as $attached_file)
            {
                $attachment_name = explode('/', $attached_file);
                $file_extension = explode('.', $attachment_name[count($attachment_name) - 1]);
                $filename = $attachment_name[count($attachment_name) - 1];

                $message .= "--" . $this->_attachments_boundary;

                $message .= "\n" . 'Content-type: ' . $this->_get_mime_type($file_extension[count($file_extension) - 1]) . ';name="' . $filename . '"' . "\n";
                $message .= 'Content-Disposition: attachment;' . "\n";
                $message .= 'filename: "' . $filename . '"' . "\n";
                $message .= 'Content-Transfer-Encoding: base64' . "\n\n";
                $message .= chunk_split(base64_encode(file_get_contents($attached_file))) . "\n\n";
            }

            $message .= "\n\n--" . $this->_attachments_boundary . "--\n";
        }

        $this->_message_to_send = $message;
    }

    protected function _get_mime_type($ext = '')
    {
        $mimes = array(
        'hqx'   =>  'application/mac-binhex40',
        'cpt'   =>  'application/mac-compactpro',
        'doc'   =>  'application/msword',
        'bin'   =>  'application/macbinary',
        'dms'   =>  'application/octet-stream',
        'lha'   =>  'application/octet-stream',
        'lzh'   =>  'application/octet-stream',
        'exe'   =>  'application/octet-stream',
        'class' =>  'application/octet-stream',
        'psd'   =>  'application/octet-stream',
        'so'    =>  'application/octet-stream',
        'sea'   =>  'application/octet-stream',
        'dll'   =>  'application/octet-stream',
        'oda'   =>  'application/oda',
        'pdf'   =>  'application/pdf',
        'ai'    =>  'application/postscript',
        'eps'   =>  'application/postscript',
        'ps'    =>  'application/postscript',
        'smi'   =>  'application/smil',
        'smil'  =>  'application/smil',
        'mif'   =>  'application/vnd.mif',
        'xls'   =>  'application/vnd.ms-excel',
        'ppt'   =>  'application/vnd.ms-powerpoint',
        'wbxml' =>  'application/vnd.wap.wbxml',
        'wmlc'  =>  'application/vnd.wap.wmlc',
        'dcr'   =>  'application/x-director',
        'dir'   =>  'application/x-director',
        'dxr'   =>  'application/x-director',
        'dvi'   =>  'application/x-dvi',
        'gtar'  =>  'application/x-gtar',
        'php'   =>  'application/x-httpd-php',
        'php4'  =>  'application/x-httpd-php',
        'php3'  =>  'application/x-httpd-php',
        'phtml' =>  'application/x-httpd-php',
        'phps'  =>  'application/x-httpd-php-source',
        'js'    =>  'application/x-javascript',
        'swf'   =>  'application/x-shockwave-flash',
        'sit'   =>  'application/x-stuffit',
        'tar'   =>  'application/x-tar',
        'tgz'   =>  'application/x-tar',
        'xhtml' =>  'application/xhtml+xml',
        'xht'   =>  'application/xhtml+xml',
        'zip'   =>  'application/zip',
        'mid'   =>  'audio/midi',
        'midi'  =>  'audio/midi',
        'mpga'  =>  'audio/mpeg',
        'mp2'   =>  'audio/mpeg',
        'mp3'   =>  'audio/mpeg',
        'aif'   =>  'audio/x-aiff',
        'aiff'  =>  'audio/x-aiff',
        'aifc'  =>  'audio/x-aiff',
        'ram'   =>  'audio/x-pn-realaudio',
        'rm'    =>  'audio/x-pn-realaudio',
        'rpm'   =>  'audio/x-pn-realaudio-plugin',
        'ra'    =>  'audio/x-realaudio',
        'rv'    =>  'video/vnd.rn-realvideo',
        'wav'   =>  'audio/x-wav',
        'bmp'   =>  'image/bmp',
        'gif'   =>  'image/gif',
        'jpeg'  =>  'image/jpeg',
        'jpg'   =>  'image/jpeg',
        'jpe'   =>  'image/jpeg',
        'png'   =>  'image/png',
        'tiff'  =>  'image/tiff',
        'tif'   =>  'image/tiff',
        'css'   =>  'text/css',
        'html'  =>  'text/html',
        'htm'   =>  'text/html',
        'shtml' =>  'text/html',
        'txt'   =>  'text/plain',
        'text'  =>  'text/plain',
        'log'   =>  'text/plain',
        'rtx'   =>  'text/richtext',
        'rtf'   =>  'text/rtf',
        'xml'   =>  'text/xml',
        'xsl'   =>  'text/xml',
        'mpeg'  =>  'video/mpeg',
        'mpg'   =>  'video/mpeg',
        'mpe'   =>  'video/mpeg',
        'qt'    =>  'video/quicktime',
        'mov'   =>  'video/quicktime',
        'avi'   =>  'video/x-msvideo',
        'movie' =>  'video/x-sgi-movie',
        'doc'   =>  'application/msword',
        'word'  =>  'application/msword',
        'xl'    =>  'application/excel',
        'eml'   =>  'message/rfc822'
        );

        return (!isset($mimes[strtolower($ext)])) ? 'application/octet-stream' : $mimes[strtolower($ext)];
    }

    public function attach($element)
    {
        foreach($this->_attachments as $attachment)
        {
            if($attachment == $element) // if we have attached this file earlier
            {
                return;
            }
        }

        $this->_attachments[] = $element;
    }

    public function detach($element)
    {
        if(is_int($element)) // we want to remove by index
        {
            if(count($this->_attachments) > 0 && ($element >= 0 && $element < count($this->_attachments)))
            {
                // if we have elements and have pointed valid index
                $this->_detach($element);
            }
        }
        else // remove by value
        {
            foreach($this->_attachments as $key => $value)
            {
                if($value == $element) // if such element exists
                {
                    $this->_detach($key);
                }
            }
        }
    }

    protected function _detach($index)
    {
        if($index != count($this->_attachments) - 1)
        {
            // we don't want to remove the last one;
            // move the last element on $index place
            $this->_attachments[$index] = $this->_attachments[count($this->_attachments) - 1];
        }

        unset($this->_attachments[count($this->_attachments) - 1]); // remove the last
    }

    public function detach_all()
    {
        for($i=count($this->_attachments)-1; $i>=0; $i--)
        {
            $this->_detach($i);
        }
    }

    public function add_recipient($email)
    {
        $new_recipient = $email;
        $e_1 = (int)mb_strrpos($email, '<');
        $e_2 = (int)mb_strrpos($email, '>');

        if($e_1 >= 0 && $e_2 != FALSE)
        {
            $email = substr($email, $e_1 + 1, $e_2 - $e_1 - 1);
        }

        for($i=0; $i<count($this->_to); $i++)
        {
            // the email we are just adding - get the exact email
            $e_1 = (int)mb_strrpos($this->_to[$i], '<');
            $e_2 = (int)mb_strrpos($this->_to[$i], '>');

            if($e_2 != FALSE)
            {
                $recipient = substr($this->_to[$i], $e_1 + 1, $e_2 - $e_1 - 1);
            }
            else
            {
                $recipient = $this->_to[$i];
            }

            if($recipient == $email) // if we already have this recipient in the list
            {
                $this->_to[$i] = $new_recipient;
                return;
            }
        }

        $this->_to[] = $new_recipient;
    }

    public function remove_recipient($element)
    {
        if(is_int($element)) // we want to remove by index
        {
            if(count($this->_to) > 0 && ($element >= 0 && $element < count($this->_to)))
            {
                // we have recipients and have pointed valid index
                $this->_remove_recipient($element);
            }
        }
        else // remove by value
        {
            $recipient_to_be_removed = $element;
            $e_1 = (int)mb_strrpos($element, '<');
            $e_2 = (int)mb_strrpos($element, '>');

            if($e_1)
            { // get the email address of the recipient we want to remove
                $recipient_to_be_removed = substr($element, $e_1 + 1, $e_2 - $e_1 - 1);
            }

            for($i=0; $i<count($this->_to); $i++)
            { // get the email address of every recipient in the list
                $e_1 = (int)mb_strrpos($this->_to[$i], '<');
                $e_2 = (int)mb_strrpos($this->_to[$i], '>');

                if($e_1)
                {
                    $recipient = mb_substr($this->_to[$i], $e_1 + 1, $e_2 - $e_1 - 1);
                }
                else
                {
                    $recipient = $this->_to[$i];
                }

                if($recipient == $recipient_to_be_removed)
                {
                    $this->_remove_recipient($i);
                }
            }
        }
    }

    protected function _remove_recipient($index)
    {
        if($index != count($this->_to) - 1)
        {
            // if we don't want to remove the last one;
            // move the last element on $index place (the element's index we want to remove)
            $this->_to[$index] = $this->_to[count($this->_to) - 1];
        }

        unset($this->_to[count($this->_to) - 1]); // remove the last recipient
    }

    public function remove_all_recipients()
    {
        for($i=count($this->_to)-1; $i>=0; $i--)
        {
            $this->_remove_recipient($i);
        }
    }

    public function set($variable, $value) // global SET
    {
        if(isset($this->{'_' . $variable}))
        {
            $variable_to_set = $this->{'_' . $variable};

            if((is_array($value) && is_array($variable_to_set)) || (!is_array($value) && !is_array($variable_to_set)))
            {
                // if normal ... just set it
                $this->{'_' . $variable} = $value;
            }
            elseif(!is_array($value) && is_array($variable_to_set))
            {
                // but if want to set one value on place of array
                // remove all the array values and set the value on array's first position

                if($variable == 'to')
                {
                    $this->remove_all_recipients();
                }
                elseif($variable == 'attachments')
                {
                    $this->detach_all();
                }

                $this->{'_' . $variable}[] = $value;
            }
        }
    }

    public function get($variable) // global GET
    {
        if(isset($this->{'_' . $variable}))
        {
            return $this->{'_' . $variable}; // returns arrays as arrays and variables as variables
        }
        else
        {
            return FALSE;
        }
    }

    public function set_from($from)
    {
        $this->_from = $from;
    }

    public function set_reply_to($reply_to)
    {
        $this->_reply_to = $reply_to;
    }

    public function set_return_path($return_path)
    {
        $this->_return_path = $return_path;
    }

    public function set_subject($subject)
    {
        $this->_subject = $subject;
    }

    public function set_message($message)
    {
        $this->_message = $message;
    }

    public function set_plain_message($plain_message)
    {
        $this->_plain_message = $plain_message;
    }

    public function set_x_priority($x_priority)
    {
        $this->_x_priority = $x_priority;
    }

    public function set_flag($flag)
    {
        $this->_flag = $flag;
    }

	public function set_headers_with_email($email)
	{
		$this->_from = $this->_reply_to = $this->_return_path = $email;
	}

	public function get_from()
    {
        return $this->_from;
    }

    public function get_reply_to()
    {
        return $this->_reply_to;
    }

    public function get_return_path()
    {
        return $this->_return_path;
    }

    public function get_subject()
    {
        return $this->_subject;
    }

    public function get_message()
    {
        return $this->_message;
    }

    public function get_plain_message()
    {
        return $this->_plain_message;
    }

    public function get_headers()
    {
        return $this->_headers;
    }

    public function get_new_headers()
    {
        $this->_generate_headers();
        return $this->get_headers();
    }

    public function get_message_to_send()
    {
        return $this->_message_to_send;
    }

    public function get_new_message_to_send()
    {
        $this->_generate_message();
        return $this->get_message_to_send();
    }

    public function get_x_priority()
    {
        return $this->_x_priority;
    }

    public function get_flag()
    {
        return $this->_flag;
    }

    public function get_recipients_as_string()
    {
        return htmlspecialchars(implode(', ', $this->_to));
    }

    public function get_recipients_as_array()
    {
        return $this->_to;
    }

    public function get_attachments_as_string()
    {
        return implode(', ', $this->_attachments);
    }

    public function get_attachments_as_array()
    {
        return $this->_attachments;
    }

    public function print_test_email()
    {
        $this->_encode_cyrillic(); // encodes the cyrillic in all cyrillic headers
        $this->_generate_boundary(); // generates unique boundary

        $this->_generate_headers(); // generates the message headers
        $this->_generate_message(); // generates the message text

        echo '<br />Test email sending:<br />';
        echo 'Subject: ' . $this->_subject . '<br />';
        echo 'Message: ' . $this->_message_to_send . '<br />';
        echo 'Headers: ' . $this->_headers . '<br />';
    }

    public function get_number_of_attachments()
    {
        return count($this->_attachments);
    }

    public function get_number_of_recipients()
    {
        return count($this->_to);
    }
}
?>
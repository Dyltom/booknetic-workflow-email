<?php

namespace BookneticAddon\EmailWorkflow\Helpers;

use BookneticVendor\Google\Service\Gmail\Message;

class GmailMessageHelper
{
    private static $instance;
    private $senderName;
    private $senderEmail;
    private $sendTo;
    private $subject;
    private $body;
    private $attachments;

    public static function getInstance()
    {
        if( is_null(self::$instance ))
        {
            self::$instance = new GmailMessageHelper();
        }
        return self::$instance;
    }

    public function setSenderName($senderName)
    {
        $this->senderName = $senderName;
        return $this;
    }

    public function setSenderEmail($senderEmail)
    {
        $this->senderEmail = $senderEmail;
        return $this;
    }

    public function setSendTo($sendTo)
    {
        $this->sendTo = $sendTo;
        return $this;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function getMessage()
    {
        $strRawMessage = "";
        $boundary = uniqid(rand(), true);
        $subjectCharset = $charset = 'utf-8';

        $strRawMessage .= "To:  <" . $this->sendTo . ">" . "\r\n";
        $strRawMessage .= 'From: =?'. $charset .'?B?'. base64_encode( $this->senderName ) .'?=' . " <" . $this->senderEmail . ">" . "\r\n";
        $strRawMessage .= 'Subject: =?' . $subjectCharset . '?B?' . base64_encode($this->subject) . "?=\r\n";

        if( ! empty( $this->attachments ) )
        {
            $strRawMessage .= 'MIME-Version: 1.0' . "\r\n";
            $strRawMessage .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";
        }

        foreach ($this->attachments as  $attachment) {
            if( empty( $attachment ) )
                continue;

            $array = explode('/', $attachment);
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            $mimeType = finfo_file($finfo, $attachment);
            $fileName = $array[sizeof($array)-1];

            $strRawMessage .= "\r\n--{$boundary}\r\n";
            $strRawMessage .= 'Content-Type: '. $mimeType .'; name="'. $fileName .'";' . "\r\n";
            $strRawMessage .= 'Content-ID: <' . 'test'. '>' . "\r\n";
            $strRawMessage .= 'Content-Description: ' . $fileName . ';' . "\r\n";
            $strRawMessage .= 'Content-Disposition: attachment; filename="' . $fileName . '"; size=' . filesize($attachment). ';' . "\r\n";
            $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
            $strRawMessage .= chunk_split(base64_encode(file_get_contents($attachment)), 76, "\n") . "\r\n";
            $strRawMessage .= "--{$boundary}\r\n";
        }
        $strRawMessage .= 'Content-Type: text/html; charset=' . $charset . "\r\n";
        $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
        $strRawMessage .= $this->body . "\r\n";

        $mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
        $msg = new Message();
        $msg->setRaw($mime);

        return $msg;
    }
}
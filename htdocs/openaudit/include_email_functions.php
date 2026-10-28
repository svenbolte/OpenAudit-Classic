<?php
/* Email delivery disabled: legacy MIME/SMTP dependency removed. */
function GetEmailObject() { return null; }
function SendHtmlEmail($subject,$html,$to,&$email,$attachments=array(),$logo=null) { return is_array($to) ? $to : array($to); }
function ParseEmailTemplate($variables,$template) { return ''; }

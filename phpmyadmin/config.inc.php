<?php
/*
 * This is needed for cookie based authentication to encrypt password in
 * cookie
 */
$cfg['blowfish_secret'] = 's"@W]_!.u/].{PT/z)m-v}q>y7sdB8+0';
$cfg['DefaultLang'] = 'de';
$cfg['SendErrorReports'] = 'never';

/*
 * Servers configuration
 */
$i = 0;

/*
 * First server
 */
$i++;

/* Authentication type and info */
// $cfg['Servers'][$i]['auth_type'] = 'config';
// $cfg['Servers'][$i]['user'] = 'benutzer';
// $cfg['Servers'][$i]['password'] = 'passwort';
$cfg['Servers'][$i]['extension'] = 'mysqli';
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['Lang'] = '';

/* Bind to the localhost ipv4 address and tcp */
$cfg['Servers'][$i]['host'] = '127.0.0.1';
$cfg['Servers'][$i]['connect_type'] = 'tcp';


/*
 * End of servers configuration
 */

?>

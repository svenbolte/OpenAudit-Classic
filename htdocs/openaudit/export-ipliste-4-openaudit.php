<?php
// importiert softwareversionen CSV Datei in Mysql Tabelle softwareversionen

$JQUERY_UI = array('core','dialog','tooltip');
include_once("include.php");

$outfile = dirname(__DIR__, 1).'\openaudit\scripts\pc_list_file.txt';

    $ip = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ip = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ip = $_SERVER['REMOTE_ADDR'];
    else
        $ip = 'UNKNOWN';
	
echo "<td style=\"vertical-align:top;width:100%\">\n";
echo "<div class=\"main_each\">";
echo "<table ><tr><td class=\"contenthead\">\n";
echo 'Openaudit IP-Liste erzeugen</td></tr></table>';
echo "<table ><tr><td>";

// pc_list_file_text auslesen und Netzwerke extrahieren in /24
$filearr = file($outfile, FILE_IGNORE_NEW_LINES);
// $filearr = array('10.10.10.1','10.10.10.2','10.10.10.3','10.10.10.4','10.10.23.5','10.10.20.2','10.10.20.3','10.10.10.4',);
$networks=array();
$networklist='';

foreach ($filearr as $ip) {
	$nums = explode('.', $ip);
	$cnet = $nums[0]. '.' .$nums[1]. '.' .$nums[2];
	if (!in_array($cnet, $networks)) { $networks[] = $cnet; }
}
foreach ($networks as $network) {
	$networklist .= $network . '.0/24,';
}
$networklist = substr($networklist, 0, -1);
echo '<b>Netzwerke aus der Datei pc_list_file_txt eingelesen:</b><br><input type="text" style="width:70%" value="'.$networklist.'"><br>';

$trimmed = implode(".", array_slice(explode(".", $ip), 0, 3)).'.0/24';
echo '<p>Ihre IP-Adresse ist: <code>'.$ip.'</code> im Netzwerk: <code>' .$trimmed.'</code></p>';

function ipListFromRange($range){
    $parts = explode('/',$range);
    $exponent = 32-$parts[1];
	$count = pow(2,$exponent) - 1;
    $start = ip2long($parts[0]);
    $end = $start+$count;
    return array_map('long2ip', range($start, $end) );
}

if (isset($_GET['ipliste'])) {
	echo '<div style="font-family:monospace;width:500px;max-width:80%">';
	$outputliste='';
	$inputnetworks = $_GET['ipliste'];
	$inputarray = explode(",", $inputnetworks);
	// csv-liste aus Netzwerken erzeugen
	foreach ($inputarray as $inputnet) {
		$iparray = array_slice(ipListFromRange($inputnet), 1, -1); 
		echo $inputnet . ' &nbsp; | &nbsp; ';
		foreach ($iparray as $ipliste) {
			$outputliste .= $ipliste . "\r\n";
		}
	}	
	echo '</div>';
	// write to file
	file_put_contents($outfile, $outputliste, LOCK_EX);
	echo '<br><br>Datei '.$outfile.' erzeugt und alte Datei überschrieben.';
} else {	
	echo '<p style="color:red"> Die Datei: <b>' . $outfile . '</b> wird dabei überschrieben!</p>';
	// input networks like 192.168.2.0/24 in list, comma separated
	echo '<form method="get"><b>Liste der IP-Netze, kommagetrennt (Beispiel: 172.26.18.0/24,192.168.1.0/24 ):</b><br>';
	echo '<input style="width:60%;max-width:80%" id="ipliste" name="ipliste" type="text" size="5000" value="'.$trimmed.'">';
	echo '<br><input type="submit" value="Liste erzeugen"></form>';
}	

echo '</td></tr><tr><td colspan=2 style="padding-left:50px"> <h2>IP-Rechner</h2>';

?>
<script>
function IPv4_Address( addressDotQuad, netmaskBits ) {
	var split = addressDotQuad.split( '.', 4 );
	var byte1 = Math.max( 0, Math.min( 255, parseInt( split[0] ))); /* sanity check: valid values: = 0-255 */
	var byte2 = Math.max( 0, Math.min( 255, parseInt( split[1] )));
	var byte3 = Math.max( 0, Math.min( 255, parseInt( split[2] )));
	var byte4 = Math.max( 0, Math.min( 255, parseInt( split[3] )));
	if( isNaN( byte1 )) {	byte1 = 0;	}	/* fix NaN situations */
	if( isNaN( byte2 )) {	byte2 = 0;	}
	if( isNaN( byte3 )) {	byte3 = 0;	}
	if( isNaN( byte4 )) {	byte4 = 0;	}
	addressDotQuad = ( byte1 +'.'+ byte2 +'.'+ byte3 +'.'+ byte4 );

	this.addressDotQuad = addressDotQuad.toString();
	this.netmaskBits = Math.max( 0, Math.min( 32, parseInt( netmaskBits ))); /* sanity check: valid values: = 0-32 */
	
	this.addressInteger = IPv4_dotquadA_to_intA( this.addressDotQuad );
	this.addressDotQuad  = IPv4_intA_to_dotquadA( this.addressInteger );
	this.addressBinStr  = IPv4_intA_to_binstrA( this.addressInteger );
	
	this.netmaskBinStr  = IPv4_bitsNM_to_binstrNM( this.netmaskBits );
	this.netmaskInteger = IPv4_binstrA_to_intA( this.netmaskBinStr );
	this.netmaskDotQuad  = IPv4_intA_to_dotquadA( this.netmaskInteger );
	
	this.netaddressBinStr = IPv4_Calc_netaddrBinStr( this.addressBinStr, this.netmaskBinStr );
	this.netaddressInteger = IPv4_binstrA_to_intA( this.netaddressBinStr );
	this.netaddressDotQuad  = IPv4_intA_to_dotquadA( this.netaddressInteger );
	
	this.netbcastBinStr = IPv4_Calc_netbcastBinStr( this.addressBinStr, this.netmaskBinStr );
	this.netbcastInteger = IPv4_binstrA_to_intA( this.netbcastBinStr );
	this.netbcastDotQuad  = IPv4_intA_to_dotquadA( this.netbcastInteger );
}

/* In some versions of JavaScript subnet calculators they use bitwise operations to shift the values left. Unfortunately JavaScript converts to a 32-bit signed integer when you mess with bits, which leaves you with the sign + 31 bits. For the first byte this means converting back to an integer results in a negative value for values 128 and higher since the leftmost bit, the sign, becomes 1. Using the 64-bit float allows us to display the integer value to the user. */
/* dotted-quad IP to integer */
function IPv4_dotquadA_to_intA( strbits ) {
	var split = strbits.split( '.', 4 );
	var myInt = (
		parseFloat( split[0] * 16777216 )	/* 2^24 */
	  + parseFloat( split[1] * 65536 )		/* 2^16 */
	  + parseFloat( split[2] * 256 )		/* 2^8  */
	  + parseFloat( split[3] )
	);
	return myInt;
}

/* integer IP to dotted-quad */
function IPv4_intA_to_dotquadA( strnum ) {
	var byte1 = ( strnum >>> 24 );
	var byte2 = ( strnum >>> 16 ) & 255;
	var byte3 = ( strnum >>>  8 ) & 255;
	var byte4 = strnum & 255;
	return ( byte1 + '.' + byte2 + '.' + byte3 + '.' + byte4 );
}

/* integer IP to binary string representation */
function IPv4_intA_to_binstrA( strnum ) {
	var numStr = strnum.toString( 2 ); /* Initialize return value as string */
	var numZeros = 32 - numStr.length; /* Calculate no. of zeros */
	if (numZeros > 0) {	for (var i = 1; i <= numZeros; i++) { numStr = "0" + numStr }	} 
	return numStr;
}

/* binary string IP to integer representation */
function IPv4_binstrA_to_intA( binstr ) {
	return parseInt( binstr, 2 );
}

/* convert # of bits to a string representation of the binary value */
function IPv4_bitsNM_to_binstrNM( bitsNM ) {
	var bitString = '';
	var numberOfOnes = bitsNM;
	while( numberOfOnes-- ) bitString += '1'; /* fill in ones */
	numberOfZeros = 32 - bitsNM;
	while( numberOfZeros-- ) bitString += '0'; /* pad remaining with zeros */
	return bitString;
}

/* The IPv4_Calc_* functions operate on string representations of the binary value because I don't trust JavaScript's sign + 31-bit bitwise functions. */
/* logical AND between address & netmask */
function IPv4_Calc_netaddrBinStr( addressBinStr, netmaskBinStr ) {
	var netaddressBinStr = '';
	var aBit = 0; var nmBit = 0;
	for( pos = 0; pos < 32; pos ++ ) {
		aBit = addressBinStr.substr( pos, 1 );
		nmBit = netmaskBinStr.substr( pos, 1 );
		if( aBit == nmBit ) {	netaddressBinStr += aBit.toString();	}
		else{	netaddressBinStr += '0';	}
	}
	return netaddressBinStr;
}

/* logical OR between address & NOT netmask */
function IPv4_Calc_netbcastBinStr( addressBinStr, netmaskBinStr ) {
	var netbcastBinStr = '';
	var aBit = 0; var nmBit = 0;
	for( pos = 0; pos < 32; pos ++ ) {
		aBit = parseInt( addressBinStr.substr( pos, 1 ));
		nmBit = parseInt( netmaskBinStr.substr( pos, 1 ));
		
		if( nmBit ) {	nmBit = 0;	}	/* flip netmask bits */
		else{	nmBit = 1;	}
		
		if( aBit || nmBit ) {	netbcastBinStr += '1'	}
		else{	netbcastBinStr += '0';	}
	}
	return netbcastBinStr;
}

/* included as an example alternative for converting 8-bit bytes to an integer in IPv4_dotquadA_to_intA */
function IPv4_BitShiftLeft( mask, bits ) {
	return ( mask * Math.pow( 2, bits ) );
}

/* used for display purposes */
function IPv4_BinaryDotQuad( binaryString ) {
	return ( binaryString.substr( 0, 8 ) +'.'+ binaryString.substr( 8, 8 ) +'.'+ binaryString.substr( 16, 8 ) +'.'+ binaryString.substr( 24, 8 ) );
}


function update_ip(){
	for(s=0;s<33;s++){if(document.ip_subnet.in_ip_netmask[s].selected){use_subnet_bits=document.ip_subnet.in_ip_netmask[s].value}}
	var a=new IPv4_Address(document.ip_subnet.in_ip_address.value,use_subnet_bits);
	document.getElementById("ip_dotquad").firstChild.data=a.addressDotQuad;
	document.getElementById("ip_integer").firstChild.data=a.addressInteger;
	document.getElementById("ip_binary").firstChild.data=IPv4_BinaryDotQuad(a.addressBinStr);
	document.getElementById("netmask_dotquad").firstChild.data=a.netmaskDotQuad;
	document.getElementById("netmask_integer").firstChild.data=a.netmaskInteger;
	document.getElementById("netmask_binary").firstChild.data=IPv4_BinaryDotQuad(a.netmaskBinStr);
	document.getElementById("netmask_bits").firstChild.data=a.netmaskBits+" bits";
	document.getElementById("netaddress_binary").firstChild.data=IPv4_BinaryDotQuad(a.netaddressBinStr);
	document.getElementById("netaddress_integer").firstChild.data=a.netaddressInteger;
	document.getElementById("netaddress_dotquad").firstChild.data=a.netaddressDotQuad;
	document.getElementById("netbcast_binary").firstChild.data=IPv4_BinaryDotQuad(a.netbcastBinStr);
	document.getElementById("netbcast_integer").firstChild.data=a.netbcastInteger;
	document.getElementById("netbcast_dotquad").firstChild.data=a.netbcastDotQuad;
	document.getElementById("netpossible_quantity").firstChild.data=(parseFloat(a.netbcastInteger)-parseFloat(a.netaddressInteger)+1)+" IPs"};

</script><form method="POST" id="ip_subnet" name="ip_subnet">
IP-Adresse: <input type="text" size="14" name="in_ip_address" value="<?php echo $ip; ?>" onkeyup="update_ip();"><br /> <br />
Netzwerkmaske:	<select name="in_ip_netmask" id="in_ip_netmask" onchange="update_ip();"><option value="32">255.255.255.255 (32 bits)</option><option value="31">255.255.255.254 (31 bits)</option><option value="30">255.255.255.252 (30 bits)</option><option value="29">255.255.255.248 (29 bits)</option><option value="28">255.255.255.240 (28 bits)</option><option value="27">255.255.255.224 (27 bits)</option><option value="26">255.255.255.192 (26 bits)</option><option value="25">255.255.255.128 (25 bits)</option><option value="24">255.255.255.0   (24 bits)</option><option value="23">255.255.254.0   (23 bits)</option><option value="22">255.255.252.0   (22 bits)</option><option value="21">255.255.248.0   (21 bits)</option><option value="20">255.255.240.0   (20 bits)</option><option value="19">255.255.224.0   (19 bits)</option><option value="18">255.255.192.0   (18 bits)</option><option value="17">255.255.128.0   (17 bits)</option><option value="16">255.255.0.0     (16 bits)</option><option value="15">255.254.0.0     (15 bits)</option><option value="14">255.252.0.0     (14 bits)</option><option value="13">255.248.0.0     (13 bits)</option><option value="12">255.240.0.0     (12 bits)</option><option value="11">255.224.0.0     (11 bits)</option><option value="10">255.192.0.0     (10 bits)</option><option value="9">255.128.0.0     (9 bits)</option><option value="8">255.0.0.0       (8 bits)</option><option value="7">254.0.0.0       (7 bits)</option><option value="6">252.0.0.0       (6 bits)</option><option value="5">248.0.0.0       (5 bits)</option><option value="4">240.0.0.0       (4 bits)</option><option value="3">224.0.0.0       (3 bits)</option><option value="2">192.0.0.0       (2 bits)</option><option value="1">128.0.0.0       (1 bits)</option><option value="0">0.0.0.0         (0 bits)</option></select><br />
</form>
<figure class="wp-block-table table table-responsive table-striped table-condensed">
<style>.tftable td {padding:8px}</style>
<table class="tftable" style="padding:8px;font-size:1.2em">
<tbody>
<tr>
<th></th>
<th>Notation</th>
<th>Zähler</th>
<th>Range</th>
<th>Binär</th>
</tr>
<tr>
<th>Adresse:</th>
<td><span id="ip_dotquad">unset</span></td>
<td><span id="ip_integer">unset</span></td>
<td></td>
<td><span id="ip_binary">unset</span></td>
</tr>
<tr>
<th>Netzmaske:</th>
<td><span id="netmask_dotquad">unset</span></td>
<td><span id="netmask_integer">unset</span></td>
<td><nobr><span id="netmask_bits">unset</span></nobr></td>
<td><span id="netmask_binary">unset</span></td>
</tr>
<tr>
<th>Netzwerk:</th>
<td><span id="netaddress_dotquad">unset</span></td>
<td><span id="netaddress_integer">unset</span></td>
<td></td>
<td><span id="netaddress_binary">unset</span></td>
</tr>
<tr>
<th>Broadcast:</th>
<td><span id="netbcast_dotquad">unset</span></td>
<td><span id="netbcast_integer">unset</span></td>
<td><nobr><span id="netpossible_quantity">unset</span></nobr></td>
<td><span id="netbcast_binary">unset</span></td>
</tr>
</tbody>
</table>
</figure>
<p><script type="text/javascript">
update_ip();
</script></p>
<?php

echo '</td></tr></table></body></html>';
?>

'''''''''''''''''''''''''''''''''''
' Open Audit                      '
' Software and Hardware Inventory '
' Outputs into MySQL              '
' (c) Mark Unwin 2003             '
'''''''''''''''''''''''''''''''''''


''''''''''''''''''''''''''''''''''''
' User defined settings below here '
''''''''''''''''''''''''''''''''''''

' Below calls the file audit_include.vbs to setup the variables.
ExecuteGlobal CreateObject("Scripting.FileSystemObject").OpenTextFile("audit.config").ReadAll 

'nmap_tmp_cleanup = false           ' Set this false if you want to leave the tmp files for analysis in your tmp folder
'nmap_subnet = "10.10.10."            ' The subnet you wish to scan
'nmap_subnet_formatted = "010.010.010."    ' The subnet padded with 0's
nmap_ie_form_page = "http://localhost:888/openaudit/admin_nmap_input.php"
non_nmap_page = "http://localhost:888/openaudit/admin_nmap_input2.php"
nmap_ie_visible = "y"
nmap_ie_auto_close = "n"
'nmap_ip_start = 2
'nmap_ip_end = 254

''''''''''''''''''''''''''''''''''''''''
' Don't change the settings below here '
''''''''''''''''''''''''''''''''''''''''
Const HKEY_CLASSES_ROOT  = &H80000000
Const HKEY_CURRENT_USER  = &H80000001
Const HKEY_LOCAL_MACHINE = &H80000002
Const HKEY_USERS         = &H80000003
Const ForAppending = 8

Dim oAdd
Dim ie
Dim oDoc
Dim strcomputer

Set oShell = CreateObject("Wscript.Shell")
Set oFS = CreateObject("Scripting.FileSystemObject")

' If any command line args given - use the first one as strComputer
If Wscript.Arguments.Unnamed.Count > 0 Then
  strComputer = wscript.arguments.unnamed(0)
  nmap_ip_start=cint(strcomputer)
  nmap_ip_end=cint(strcomputer)
end if

'''''''''''''''''''''''''''''''''''
' Script loop starts here         '
'''''''''''''''''''''''''''''''''''
for ip = nmap_ip_start to nmap_ip_end
  if ip = 1000 then 
    wscript.echo "bypassing 1000"
  else
    '
    ' Create a valid tmp file.
    dim dt : dt = Now()
    timestamp = Year(dt) & Right("0" & Month(dt),2) & Right("0" & Day(dt),2) & Right("0" & Hour(dt),2) & Right("0" & Minute(dt),2) & Right("0" & Second(dt),2)
    sTemp = oShell.ExpandEnvironmentStrings("%TEMP%")
    sTempFile = sTemp & "\" & "nmap_" & nmap_subnet  & ip & "_" & timestamp & ".tmp"
    '
    'Create a valid nmap.exe string 
    nmap = Chr(34) & "c:\Program Files (x86)\xampplite\nmap\nmap.exe" & Chr(34) & " --system-dns "
    if nmap_syn_scan = "y" then
    nmap = nmap & "-sS "
    end if
    if nmap_udp_scan = "y" then
    nmap = nmap & "-sU "
    end if
    if nmap_srv_ver_scan = "y" then
    nmap = nmap & "-sV --version-intensity " & nmap_srv_ver_int & " "
    end if
    nmap = nmap & "-O -T4 	-v -oN " & sTempFile & " " & nmap_subnet
    '
    '
    scan = nmap & ip
    wscript.echo scan
    Set sh=WScript.CreateObject("WScript.Shell")
    sh.Run scan, 6, True
    set sh = nothing
    set form_input = nothing
    set file_read = nothing
    Set objFSO = CreateObject("Scripting.FileSystemObject")
    Set objTextFile = objFSO.OpenTextFile(sTempFile, 1)
    Do Until objTextFile.AtEndOfStream
      strText = objTextFile.ReadAll
    Loop
    objTextFile.Close
    

   '''''''''''''''''''''''''''''''''''''''''
  ' Create an IE HTTPXML instance for output into '
  '''''''''''''''''''''''''''''''''''''''''

   url = non_nmap_page
   form_total = strText
   Err.clear
   XmlObj = "ServerXMLHTTP"
   Set objHTTP = WScript.CreateObject("MSXML2.ServerXMLHTTP.3.0")
   objHTTP.SetOption 2, 13056  ' Ignore all SSL errors
   objHTTP.Open "POST", url, False
   objHTTP.setRequestHeader "Content-Type","application/x-www-form-urlencoded"
   if utf8 = "y" then
     objHTTP.Send "add=" + urlEncode(form_total + vbcrlf)
   else
     objHTTP.Send "add=" + escape(Deconstruct(form_total + vbcrlf))
   end if
   if (Err.Number <> 0 or objHTTP.status <> 200) then
     Err.clear
     XmlObj = "XMLHTTP"
     Set objHTTP = WScript.CreateObject("MSXML2.XMLHTTP")
     objHTTP.Open "POST", url, False
     objHTTP.setRequestHeader "Content-Type","application/x-www-form-urlencoded"
     if utf8 = "y" then
       objHTTP.Send "add=" + urlEncode(form_total + vbcrlf)
     else
       objHTTP.Send "add=" + escape(Deconstruct(form_total + vbcrlf))
     end if
   end if
	 Echo( "*** NMAP-Ziel-URL : " & url)
	 if (Err.Number <> 0 or objHTTP.status <> 200) then
		 Echo("Unable to send XML to server using " & XmlObj & " - HTTP Response: " & objHTTP.status & " (" & objHTTP.statusText & ") - Error " & Err.Number & " " & Err.Description)
	 else
		 Echo("XML sent to server using " & XmlObj & ": " & objHTTP.status & " (" & objHTTP.statusText & ")")
	 end if
     Err.clear


    ' Cleanup the text file if requested 
    if nmap_tmp_cleanup = true then
      objFSO.DeleteFile(sTempFile)
    end if
  end if ' excluded ip number
next

' ***************************** Functions *************************************************************

Function Echo(sText)
	If verbose = "y" then 
    wscript.echo sText
    End If
    ' Also add to Audit Log 
    if use_audit_log = "y" then 
    Set objFSO = CreateObject("Scripting.FileSystemObject")
        If objFSO.FileExists(this_audit_log) Then
        Set objFile = objFSO.OpenTextFile(this_audit_log, ForAppending)
'        objFile.WriteLine
        objFile.WriteLine "" & Now & "," & strComputer & ",'Audit Result - "  & sText & " - Completed OK.'"
        objFile.Close
        End If
    End if
    
 End Function

Function Deconstruct(strIn)
  strOut = ""
  For x = 1 to Len(strIn)
    If Asc(Mid(strIn,x,1)) > 128 Then
      strOut = strOut & "&#" & Asc(Mid(strIn,x,1))
    Else
      strOut = strOut & Mid(strIn,x,1)
    End If
  Next

  Deconstruct = strOut
End Function


wscript.quit

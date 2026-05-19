' Entfällt mit Umstellung auf Powershell 2026 - diese Datei kann dann weg!!!

' wscript.echo "Customer specific audits (Windows Update Version)"
' *** Example Reg-Keys: Add Windows >7 build to the Software Table
' strKeyPath = "SOFTWARE\Microsoft\Windows NT\CurrentVersion"
' strValueName = "BuildLabEx"
' str3ValueName = "UBR"
' str2ValueName = "ProductName"
' display_name = ""
' oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,strValueName,windo_Version
' oReg.GetDWORDValue HKEY_LOCAL_MACHINE,strKeyPath,str3ValueName,win_subbuild
' oReg.GetStringValue HKEY_LOCAL_MACHINE,strKeyPath,str2ValueName,display_name
' if xwindo_Version <> "" then
'	 form_input = "software^^^Microsoft " & display_name       & " (version)^^^" _
'                   & windo_Version & "/" & win_subbuild       & "^^^" _
'                   & ""                 & "^^^" _
'                   & ""                 & "^^^" _
'                   & OSInstall          & "^^^" _
'                   & "Microsoft^^^^^^^^^" _
'                   & "http://www.microsoft.com/windows^^^" _
'                   & "Lab Build/Subbuild zur installierten Windows Version"    & "^^^" _
'                   & ""                 & "^^^" _
'                   & ""   & "^^^ "
'	 entry form_input,comment,objTextFile,oAdd,oComment
'	 form_input = ""
'end if	 

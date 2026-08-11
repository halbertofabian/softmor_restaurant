#define MyAppName "Softmor Print Agent"
#define MyAppVersion "1.2.0"
#define MyAppPublisher "Softmor"
#define MyAppExeName "SoftmorPrintAgent.exe"

[Setup]
AppId={{A607B3F4-C9C5-4B37-8B30-E5386597A5A4}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\Softmor\PrintAgent
DefaultGroupName=Softmor Print Agent
OutputDir=.
OutputBaseFilename=SoftmorPrintAgentSetup
Compression=lzma
SolidCompression=yes
WizardStyle=modern
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
PrivilegesRequired=admin
DisableProgramGroupPage=yes

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Files]
Source: "..\publish\win-x64\SoftmorPrintAgent.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "start-agent.bat"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\Softmor Print Agent"; Filename: "{app}\{#MyAppExeName}"
Name: "{commonstartup}\Softmor Print Agent"; Filename: "{app}\start-agent.bat"

[Run]
Filename: "{app}\start-agent.bat"; Description: "Iniciar Softmor Print Agent ahora"; Flags: nowait postinstall skipifsilent

[UninstallRun]
Filename: "taskkill"; Parameters: "/F /IM SoftmorPrintAgent.exe"; Flags: runhidden skipifdoesntexist

unit Utils;

interface

uses
  Registry,
  Windows,
  SysUtils,
  Classes;

function CurrentUserName: string;
function CurrentComputerName: string;

implementation

function CurrentUserName: string;
var
  Reg: TRegistry;
begin
  Reg := TRegistry.Create;
  try
    Reg.RootKey := HKEY_CURRENT_USER;
    Reg.OpenKey('\Software\Microsoft\Windows\CurrentVersion\Explorer', False);
    Result := Reg.ReadString('Logon User Name');
  finally
    Reg.Free;
  end;
end;

function CurrentComputerName: string;
var
  Reg: TRegistry;
begin
  Reg := TRegistry.Create;
  try
    Reg.RootKey := HKEY_LOCAL_MACHINE;
    Reg.OpenKey('\SYSTEM\ControlSet001\Control\ComputerName\ActiveComputerName', False);
    Result := Reg.ReadString('ComputerName');
  finally
    Reg.Free;
  end;
end;

end.
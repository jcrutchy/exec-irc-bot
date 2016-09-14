unit JMC_FileUtils;

interface

uses
  Classes,
  ShellAPI,
  SysUtils,
  Windows,
  FileCtrl;

function ExpandPath(const MasterFileName, SlaveFileName: string): string;
function ExpandPathFromExe(const SlaveFileName: string): string;
function RelatePathFromExe(const SlaveFileName: string): string;
function SettingsFileName: string;
function FileToStr(const FileName: string; var S: string): Boolean;
function StrToFile(const FileName: string; var S: string): Boolean;
function CreatePath(const Path: string): Boolean;
function OpenFile(const FileName: string; const OverrideValidation: Boolean = False): Boolean;

implementation

{ ExpandPath doesn't work when:
  MasterFileName = 'C:\My Documents\Delphi Projects\ParserFAR23\Source\Data\'
  SlaveFileName = '\Data\2007-11-22 FAR 23.htm' }
function ExpandPath(const MasterFileName, SlaveFileName: string): string; // If SlaveFileName is a path only, encompass a call to ExpandPath with a call to IncludeTrailingPathDelimiter.
var
  S: string;
begin
  S := SysUtils.GetCurrentDir;
  if not SysUtils.SetCurrentDir(ExtractFilePath(MasterFileName)) then
  begin
    Result := '';
    Exit;
  end;
  Result := SysUtils.ExpandFileName(SlaveFileName);
  SysUtils.SetCurrentDir(S);
end;

function ExpandPathFromExe(const SlaveFileName: string): string;
begin
  Result := ExpandPath(SysUtils.ExtractFilePath(ParamStr(0)), SlaveFileName);
end;

function RelatePathFromExe(const SlaveFileName: string): string;
begin
  Result := SysUtils.ExtractRelativePath(SysUtils.ExtractFilePath(ParamStr(0)), SlaveFileName);
end;

function SettingsFileName: string;
begin
  Result := SysUtils.ChangeFileExt(ParamStr(0), '.ini');
end;

function FileToStr(const FileName: string; var S: string): Boolean; // FileName must be fully qualified
var
  F: TFileStream;
  Buffer: array[1..1024] of Char;
  Temp: string;
  i: Integer;
begin
  Result := False;
  S := '';
  if SysUtils.FileExists(FileName) then
    try
      F := Classes.TFileStream.Create(FileName, fmOpenRead + fmShareDenyNone);
      F.Seek(0, soFromBeginning);
      repeat
        i := F.Read(Buffer, SizeOf(Buffer));
        Temp := Copy(Buffer, 1, i);
        S := S + Temp;
      until Length(Temp) = 0;
      F.Free;
      Result := True;
    except
      Result := False;
    end;
end;

function StrToFile(const FileName: string; var S: string): Boolean; // FileName must be fully qualified
var
  F: TextFile;
  Path: string;
begin
  Path := ExtractFilePath(FileName);
  if not FileCtrl.DirectoryExists(Path) then
    FileCtrl.ForceDirectories(Path);
  AssignFile(F, FileName);
  try
    Rewrite(F);
  except
    Result := False;
    Exit;
  end;
  Write(F, S);
  CloseFile(F);
  Result := True;
end;

function CreatePath(const Path: string): Boolean;
begin
  try
    Result := FileCtrl.ForceDirectories(Path);
  except
    Result := False;
  end;
end;

function OpenFile(const FileName: string; const OverrideValidation: Boolean = False): Boolean;
begin
  Result := SysUtils.FileExists(FileName) or OverrideValidation;
  if Result then
    Result := ShellAPI.ShellExecute(0, 'open', PChar(FileName), '', nil, SW_SHOWNORMAL) <= 32;
end;

end.
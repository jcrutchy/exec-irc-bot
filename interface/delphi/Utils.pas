unit Utils;

interface

uses
  Windows,
  SysUtils,
  Classes,
  Graphics,
  Controls,
  Forms,
  Dialogs,
  ExtCtrls,
  StrUtils;

function ExtractSerialzedString(const S: string; var Ret: string): Boolean;
function FileToStr(const FileName: string; var S: string): Boolean; // FileName must be fully qualified
function StrToFile(const FileName: string; const S: string): Boolean; // FileName must be fully qualified & path must exist

implementation

// Fmsg_time_dt := DateUtils.UnixToDateTime(Round(msg_time));
// Fmsg_time_str := SysUtils.FormatDateTime('yyyy-mm-dd hh:nn:ss', msg_time_dt);
// Tmp := SysUtils.StringReplace(Tmp, #10, ' ', [rfReplaceAll]);

function ExtractSerialzedString(const S: string; var Ret: string): Boolean;
var
  i: Integer;
  L: Integer;
begin
  Result := False;
  Ret := '';
  i := Pos(':', S);
  if i <= 0 then
    Exit;
  try
    L := SysUtils.StrToInt(Copy(S, 1, i - 1));
  except
    Exit;
  end;
  if Length(S) < (i + 2) then
    Exit;
  if (Copy(S, i + 1, 1) <> '"') or (Copy(S, i + 2 + L, 1) <> '"') then
  begin     
    //ShowMessage('Error: ' + Copy(S, i + 2 + L, 1));
    Exit;
  end;
  Ret := Copy(S, i + 2, L);
  Result := True;
end;

function FileToStr(const FileName: string; var S: string): Boolean; // FileName must be fully qualified
var
  F: Classes.TFileStream;
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

function StrToFile(const FileName: string; const S: string): Boolean; // FileName must be fully qualified & path must exist
var
  Path: string;
  F: Classes.TStrings;
begin
  Path := ExtractFilePath(FileName);
  if not SysUtils.DirectoryExists(Path) then
  begin
    Result := False;
    Exit;
  end;
  F := Classes.TStringList.Create;
  F.Text := S;
  try
    F.SaveToFile(FileName);
    Result := True;
  except
    Result := False;
  end;
end;

end.
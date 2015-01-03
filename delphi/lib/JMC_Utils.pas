unit JMC_Utils;

interface

uses
  SysUtils,
  JMC_Strings,
  Windows,
  JMC_Parts,
  Graphics,
  Registry,
  ExtCtrls;

function StrToCurrencyDefault(const S: string; const Default: Currency = 0): Currency;
function RemoveDollars(const Value: string): string;
function RemoveCommas(const Value: string): string;
function RemoveCharacter(const Value: string; const Character: Char): string;
function GetLoggedInUserName: string;
function PosStart(var S: string; const Sub: string; const Start: Integer): Integer;
function HandledStrToDateTime(const S: string; var Value: TDateTime): Boolean;
function StrToIntTest(const S: string; var Value: Integer): Boolean;
function StrToIntDefault(const S: string; const Default: Integer = 0): Integer;
function StrToFloatTest(const S: string; var Value: Double): Boolean;
function StrToFloatDefault(const S: string; const Default: Double = 0): Double;
function MessyStrToInt(const S: string; const Default: Integer = 0): Integer;
procedure ReadColor(const Settings: TCustomIniFile; const Section, Key: string; const Default: TColor; var Value: TColor);
procedure WriteColor(const Settings: TCustomIniFile; const Section, Key: string; const Value: TColor);
function ReadDouble(const Settings: TCustomIniFile; const Section, Key: string; const Default: Double; var Value: Double): Boolean;
procedure WriteDouble(const Settings: TCustomIniFile; const Section, Key: string; const Value: Double);
function ReadInteger(const Settings: TCustomIniFile; const Section, Key: string; const Default: Integer; var Value: Integer): Boolean;
procedure WriteInteger(const Settings: TCustomIniFile; const Section, Key: string; const Value: Integer);
//function ReadString(const Settings: TCustomIniFile; const Section, Default: string; const Value: TLabeledEdit): Boolean; overload;
function ReadString(const Settings: TCustomIniFile; const Section, Key, Default: string; var Value: string): Boolean; overload;
procedure WriteString(const Settings: TCustomIniFile; const Section, Key, Value: string);
function FloatToString(const Value: Double; const Commas: Boolean = True; const DecimalPrecision: Integer = -1; const TrimRightZeros: Boolean = True; const Width: Integer = -1): string;
function RemoveCtrlChars(const S: string): string;
function IsInteger(const Value: Double): Boolean;

implementation

function StrToCurrencyDefault(const S: string; const Default: Currency = 0): Currency;
var
  SS: string;
begin
  SS := RemoveDollars(RemoveCommas(Trim(S)));
  if SS = '' then
  begin
    Result := Default;
    Exit;
  end;
  try
    Result := StrToCurr(SS);
  except
    Result := Default;
  end;
end;

function RemoveDollars(const Value: string): string;
begin
  Result := RemoveCharacter(Value, '$');
end;

function RemoveCommas(const Value: string): string;
begin
  Result := RemoveCharacter(Value, ',');
end;

function RemoveCharacter(const Value: string; const Character: Char): string;
var
  i: Integer;
begin
  Result := '';
  for i := 1 to Length(Value) do
    if Value[i] <> Character then
      Result := Result + Value[i];
end;

function GetLoggedInUserName: string;
var
  Reg: TRegistry;
begin
  Result := '';
  Reg := TRegistry.Create;
  try
    Reg.RootKey := HKEY_CURRENT_USER;
    Reg.OpenKey('\Software\Microsoft\Windows\CurrentVersion\Explorer', False);
    Result := Reg.ReadString('Logon User Name');
  finally
    Reg.Free;
  end;
end;

function PosStart(var S: string; const Sub: string; const Start: Integer): Integer;
begin
  Result := Pos(Sub, Copy(S, Start, Length(S) - Start + 1));
  if Result > 0 then
    Result := Result + Start - 1;
end;

function HandledStrToDateTime(const S: string; var Value: TDateTime): Boolean;
var
  SS: string;
begin
  Result := False;
  SS := RemoveCommas(Trim(S));
  if SS = '' then
    Exit;
  try
    Value := StrToFloat(SS);
  except
    Exit;
  end;
  Result := True;
end;

function StrToIntTest(const S: string; var Value: Integer): Boolean;
var
  SS: string;
begin
  Result := False;
  SS := RemoveCommas(Trim(S));
  if SS = '' then
    Exit;
  try
    Value := StrToInt(SS);
  except
    Exit;
  end;
  Result := True;
end;

function StrToIntDefault(const S: string; const Default: Integer = 0): Integer;
var
  SS: string;
begin
  SS := RemoveCommas(Trim(S));
  if SS = '' then
  begin
    Result := Default;
    Exit;
  end;
  try
    Result := StrToInt(SS);
  except
    Result := Default;
  end;
end;

function StrToFloatTest(const S: string; var Value: Double): Boolean;
var
  SS: string;
begin
  Result := False;
  SS := RemoveCommas(Trim(S));
  if SS = '' then
    Exit;
  try
    Value := StrToFloat(SS);
  except
    Exit;
  end;
  Result := True;
end;

function StrToFloatDefault(const S: string; const Default: Double = 0): Double;
var
  SS: string;
begin
  SS := RemoveCommas(Trim(S));
  if SS = '' then
  begin
    Result := Default;
    Exit;
  end;
  try
    Result := StrToFloat(SS);
  except
    Result := Default;
  end;
end;

function MessyStrToInt(const S: string; const Default: Integer = 0): Integer;
var
  Numbers: string;
  b: Boolean;
  i: Integer;
begin
  Numbers := '';
  b := False;
  for i := 1 to Length(S) do
    case Ord(S[i]) of
      48..57:
        begin
          Numbers := Numbers + S[i];
          b := True;
        end;
    else
      if b then
        Break;
    end;
  if Numbers <> '' then
    try
      Result := StrToInt(Numbers);
      Exit;
    except
    end;
  Result := Default;
end;

procedure ReadColor(const Settings: TCustomIniFile; const Section, Key: string; const Default: TColor; var Value: TColor);
var
  S: string;
  R, G, B: Byte;
begin
  Value := Default;
  S := Settings.ReadValue(Section, Key);
  try
    R := StrToInt(ReadPart(S, 1, ','));
    G := StrToInt(ReadPart(S, 2, ','));
    B := StrToInt(ReadPart(S, 3, ','));
    Value := RGB(R, G, B);
  except
  end;
end;

procedure WriteColor(const Settings: TCustomIniFile; const Section, Key: string; const Value: TColor);
begin
  Settings.WriteValue(Section, Key, Format('%d,%d,%d', [GetRValue(Value), GetGValue(Value), GetBValue(Value)]));
end;

function ReadDouble(const Settings: TCustomIniFile; const Section, Key: string; const Default: Double; var Value: Double): Boolean;
begin
  Value := Default;
  Result := StrToFloatTest(Settings.ReadValue(Section, Key), Value);
end;

procedure WriteDouble(const Settings: TCustomIniFile; const Section, Key: string; const Value: Double);
begin
  Settings.WriteValue(Section, Key, FloatToString(Value));
end;

function ReadInteger(const Settings: TCustomIniFile; const Section, Key: string; const Default: Integer; var Value: Integer): Boolean;
begin
  Result := StrToIntTest(Settings.ReadValue(Section, Key), Value);
  if not Result then
    Value := Default;
end;

procedure WriteInteger(const Settings: TCustomIniFile; const Section, Key: string; const Value: Integer);
begin
  Settings.WriteValue(Section, Key, IntToStr(Value));
end;

{function ReadString(const Settings: TCustomIniFile; const Section, Default: string; const Value: TLabeledEdit): Boolean;
var
  S: string;
begin
  S := Settings.ReadValue(Section, Value.EditLabel.Caption);
  Result := S <> '';
  if Result then
    Value.Text := S
  else
    Value.Text := Default;
end;}

function ReadString(const Settings: TCustomIniFile; const Section, Key, Default: string; var Value: string): Boolean;
begin
  Value := Settings.ReadValue(Section, Key);
  Result := Value <> '';
  if not Result then
    Value := Default;
end;

procedure WriteString(const Settings: TCustomIniFile; const Section, Key, Value: string);
begin
  Settings.WriteValue(Section, Key, Value);
end;

function FloatToString(const Value: Double; const Commas: Boolean = True; const DecimalPrecision: Integer = -1; const TrimRightZeros: Boolean = True; const Width: Integer = -1): string;
var
  i: Integer;
  j: Integer;
  C: Char;
  W: string;
const
  MAXDECIMALPRECISION = 10;
begin
  if Commas then
    C := 'n'
  else
    C := 'f';
  if (DecimalPrecision >= 0) and (DecimalPrecision <= MAXDECIMALPRECISION) then
  begin
    if Width >= DecimalPrecision then
      W := SysUtils.IntToStr(Width)
    else
      W := '';
    Result := Format('%' + W + '.' + IntToStr(DecimalPrecision) + C, [Value]);
  end
  else
    Result := Format('%.' + IntToStr(MAXDECIMALPRECISION) + C, [Value]);
  i := Length(Result);
  if TrimRightZeros and (i > 0) then
  begin
    j := Pos('.', Result);
    if j > 0 then
      repeat
        if Result[i] = '0' then
          Result := Copy(Result, 1, i - 1)
        else
        begin
          if Result[i] = '.' then
            Result := Copy(Result, 1, i - 1);
          Break;
        end;
        Dec(i);
      until i < j;
  end;
  if Length(Result) < Width then
    Result := StringOfChar(' ', Width - Length(Result)) + Result;
end;

function RemoveCtrlChars(const S: string): string;
var
  i: Integer;
begin
  Result := '';
  for i := 1 to Length(S) do
    case Ord(S[i]) of
      32..126: Result := Result + S[i];
    end;
end;

function IsInteger(const Value: Double): Boolean;
var
  S: string;
  i: Integer;
  j: Integer;
begin
  try
    S := SysUtils.Format('%.6f', [Value]);
  except
    Result := False;
    Exit;
  end;
  S := Trim(S);
  if S = '' then
  begin
    Result := False;
    Exit;
  end;
  for i := 1 to Length(S) do
    case S[i] of
      '0'..'9', '.', '-': Continue;
    else
      Result := False;
      Exit;
    end;
  Result := True;
  j := Pos('.', S);
  for i := j + 1 to Length(S) do
    case S[i] of
      '0': Continue;
    else
      Result := False;
      Exit;
    end;
end;

end.
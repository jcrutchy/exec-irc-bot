unit JMC_SysUtils;

interface

uses
  Windows,
  SysUtils,
  Classes,
  Graphics;

function DoubleToByteString(const Value: Double): string;
function ByteStringToDouble(const Value: string): Double;

implementation

function DoubleToByteString(const Value: Double): string;
var
  Buffer: array[0..7] of Char;
  i: Integer;
begin
  FillChar(Buffer, 8, 0);
  Move(Value, Buffer, 8);
  Result := '';
  for i := 0 to 7 do
    Result := Result + Buffer[i];
end;

function ByteStringToDouble(const Value: string): Double;
var
  Buffer: array[0..7] of Char;
  m: Double;
  i: Integer;
begin
  if Length(Value) = 8 then
  begin
    for i := 0 to 7 do
      Buffer[i] := Value[i + 1];
    Move(Buffer, m, 8);
    Result := m;
  end
  else
    Result := 0;
end;

end.
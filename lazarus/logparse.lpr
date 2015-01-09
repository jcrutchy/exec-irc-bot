program logparse;

uses
  Classes,
  sysutils,
  strutils;

var
  Data: Classes.TStrings;

begin
  Data := Classes.TStringList.Create;
  try
    strutils.ExtractDelimited(1, Line, [Delim]);
  finally
    Data.Free;
  end;
end.

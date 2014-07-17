#!/usr/bin/env instantfpc
{$mode objfpc}{$H+}
uses
  Classes, SysUtils;

var
  i: Integer;
begin
  for i:=0 to ParamCount do writeln(ParamStr(i));
end.


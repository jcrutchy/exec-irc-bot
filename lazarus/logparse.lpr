program logparse;

// http://wiki.lazarus.freepascal.org/fphttpclient
// http://wiki.lazarus.freepascal.org/Console_Mode_Pascal

{$mode objfpc}{$H+}

uses
  {$IFDEF UNIX}{$IFDEF UseCThreads}
  cthreads,
  {$ENDIF}{$ENDIF}
  Classes;
//  Classes,
//  sysutils,
//  strutils,
//  fphttpclient;

//var
//  Data: Classes.TStrings;

begin
//  Writeln(TFPCustomHTTPClient.SimpleGet('http://parse.my.to/'));
//  Data := Classes.TStringList.Create;
//  try
//    strutils.ExtractDelimited(1, Line, [Delim]);
//  finally
//    Data.Free;
//  end;
  WriteLn('Hello World!');
  ReadLn;
end.

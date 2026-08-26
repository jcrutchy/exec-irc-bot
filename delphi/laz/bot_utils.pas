unit bot_utils;

{$mode delphi}

interface

uses
  Classes,
  SysUtils,
  bot_classes;

function ParseMessage(const Data: string): TBotMessage;

implementation

function ParseMessage(const Data: string): TBotMessage;
var
  S: string;
  sub: string;
  i: Integer;
begin
  Result.Valid := False;
  Result.TimeStamp := Now;
  Result.Data := Data;
  S := Data;
  // :<prefix> <command> <params> :<trailing>
  // the only required part of the message is the command
  // if there is no prefix, then the source of the message is the server for the current connection (such as for PING)
  if Copy(Data, 1, 1) = ':' then
  begin
    i := Pos(' ', S);
    if i > 0 then
    begin
      Result.Prefix := Copy(S, 2, i - 2);
      S := Copy(S, i + 1, Length(S) - i);
    end;
  end;
  i := Pos(' :', S);
  if i > 0 then
  begin
    Result.Trailing := Copy(S, i + 2, Length(S) - i - 1);
    S := Copy(S, 1, i - 1);
  end;
  i := Pos(' ', S);
  if i > 0 then
  begin
    // params found
    Result.Params := Copy(S, i + 1, Length(S) - i);
    S := Copy(S, 1, i - 1);
  end;
  Result.Command := S;
  if Result.Command = '' then
    Exit;
  Result.Valid := True;
  if Result.Prefix <> '' then
  begin
    // prefix format: nick!user@hostname
    i := Pos('!', Result.Prefix);
    if i > 0 then
    begin
      Result.Nick := Copy(Result.Prefix, 1, i - 1);
      sub := Copy(Result.Prefix, i + 1, Length(Result.Prefix) - i);
      i := Pos('@', sub);
      if i > 0 then
      begin
        Result.User := Copy(sub, 1, i - 1);
        Result.Hostname := Copy(sub, i + 1, Length(sub) - i);
      end;
    end
    else
      Result.Nick := Result.Prefix;
  end;
  i := Pos(' ', Result.Params);
  if i <= 0 then
    Result.Destination := Result.Params;
end;

end.

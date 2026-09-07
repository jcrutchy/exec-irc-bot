program bot;

{$mode objfpc}{$H+}{$J-}

{ =============================================================================
  Single-file FreePascal/Lazarus port of bot.php (PHP IRC bot framework).
  No third-party units: everything used ships with a stock FreePascal 3.2.2
  install (RTL + FCL + the bundled openssl binding). See the accompanying
  NOTES for a list of intentional behavioural differences from the PHP
  original.
  ============================================================================= }

uses
  {$IFDEF UNIX}
  cthreads, BaseUnix, Unix, termio,
  {$ENDIF}
  Classes, SysUtils, StrUtils, DateUtils, Process, Pipes, Sockets, ssockets,
  sslbase, sslsockets, opensslsockets, Generics.Collections, base64, ctypes, openssl;

{ =============================================================================
  Constants (mirrors the PHP `const` block)
  ============================================================================= }

const
  EXEC_OUTPUT_BUFFER_FILE   = '../data/exec_iface';
  EXEC_DELIM                = '|';
  EXEC_DIRECTIVE_DELIM      = ' ';
  EXEC_INCLUDE              = 'include';
  EXEC_INIT                 = 'init';
  EXEC_STARTUP              = 'startup';
  EXEC_HELP                 = 'help';

  FILE_DIRECTIVE_DELIM      = ':';
  FILE_DIRECTIVE_EXEC       = 'exec';
  FILE_DIRECTIVE_INIT       = 'init';
  FILE_DIRECTIVE_STARTUP    = 'startup';
  FILE_DIRECTIVE_HELP       = 'help';

  MAX_MSG_LENGTH            = 458;
  IGNORE_TIME               = 20.0;
  DELTA_TOLERANCE           = 1.5;
  TEMPLATE_DELIM            = '%%';
  DIRECTIVE_QUIT            = '<<quit>>';

  BUCKET_IGNORE_NEXT              = '<<BOT_IGNORE_NEXT>>';
  BUCKET_USERS                    = '<<EXEC_USERS>>';
  BUCKET_EVENT_HANDLERS           = '<<EXEC_EVENT_HANDLERS>>';
  BUCKET_CONNECTION_ESTABLISHED   = '<<IRC_CONNECTION_ESTABLISHED>>';
  BUCKET_SELF_TRIGGER_EVENTS_FLAG = '<<SELF_TRIGGER_EVENTS_FLAG>>';
  BUCKET_EXEC_LIST                = '<<EXEC_LIST>>';
  BUCKET_BOT_NICK                 = '<<BOT_NICK>>';
  BUCKET_ADMIN_ACCOUNTS_LIST      = '<<ADMIN_ACCOUNTS_LIST>>';
  BUCKET_OPERATOR_ACCOUNT         = '<<OPERATOR_ACCOUNT>>';
  BUCKET_OPERATOR_HOSTNAME        = '<<OPERATOR_HOSTNAME>>';
  BUCKET_MEMORY_USAGE             = '<<BOT_MEMORY_USAGE>>';
  BUCKET_OUTPUT_CONTROL           = '<<OUTPUT_CONTROL>>';
  BUCKET_SHUTDOWN                 = '<<SHUTDOWN>>';
  BUCKET_PROCESS_TEMPLATE_PREFIX  = 'process_template_';
  BUCKET_ALIAS_ELEMENT_PREFIX     = 'alias_element_';

  ALIAS_ALL       = '*';
  ALIAS_INIT      = '<init>';
  ALIAS_STARTUP   = '<startup>';
  ALIAS_QUIT      = '<quit>';

  CMD_BUCKET_GET      = 'BUCKET_GET';
  CMD_BUCKET_SET      = 'BUCKET_SET';
  CMD_BUCKET_UNSET    = 'BUCKET_UNSET';
  CMD_BUCKET_APPEND   = 'BUCKET_APPEND';
  CMD_BUCKET_LIST     = 'BUCKET_LIST';
  CMD_INTERNAL        = 'INTERNAL';
  CMD_PAUSE           = 'BOT_IRC_PAUSE';
  CMD_UNPAUSE         = 'BOT_IRC_UNPAUSE';
  CMD_INIT            = 'INIT';
  CMD_STARTUP         = 'STARTUP';
  CMD_DELETE_HANDLER  = 'DELETE_HANDLER';

  PREFIX_DELIM              = '/';
  PREFIX_IRC                = PREFIX_DELIM + 'IRC';
  PREFIX_EXEC_ADD           = PREFIX_DELIM + 'EXEC-ADD';
  PREFIX_EXEC_DEL           = PREFIX_DELIM + 'EXEC-DEL';
  PREFIX_EXEC_SAVE          = PREFIX_DELIM + 'EXEC-SAVE';
  PREFIX_PRIVMSG            = PREFIX_DELIM + 'PRIVMSG';
  PREFIX_BUCKET_GET         = PREFIX_DELIM + CMD_BUCKET_GET;
  PREFIX_BUCKET_SET         = PREFIX_DELIM + CMD_BUCKET_SET;
  PREFIX_BUCKET_UNSET       = PREFIX_DELIM + CMD_BUCKET_UNSET;
  PREFIX_BUCKET_APPEND      = PREFIX_DELIM + CMD_BUCKET_APPEND;
  PREFIX_BUCKET_LIST        = PREFIX_DELIM + CMD_BUCKET_LIST;
  PREFIX_INTERNAL           = PREFIX_DELIM + CMD_INTERNAL;
  PREFIX_PAUSE              = PREFIX_DELIM + CMD_PAUSE;
  PREFIX_UNPAUSE            = PREFIX_DELIM + CMD_UNPAUSE;
  PREFIX_DELETE_HANDLER     = PREFIX_DELIM + CMD_DELETE_HANDLER;
  PREFIX_READER_EXEC_LIST   = PREFIX_DELIM + 'READER_EXEC_LIST';
  PREFIX_READER_BUCKETS     = PREFIX_DELIM + 'READER_BUCKETS';
  PREFIX_READER_HANDLES     = PREFIX_DELIM + 'READER_HANDLES';

  ALIAS_INTERNAL_RESTART     = '~restart-internal';
  ALIAS_ADMIN_ALIAS_MACRO    = '~alias-macro';
  ALIAS_ADMIN_QUIT           = '~quit';
  ALIAS_ADMIN_NICK           = '~nick';
  ALIAS_ADMIN_PS             = '~ps';
  ALIAS_ADMIN_KILL           = '~kill';
  ALIAS_ADMIN_KILLALL        = '~killall';
  ALIAS_ADMIN_RESTART        = '~restart';
  ALIAS_ADMIN_REHASH         = '~rehash';
  ALIAS_ADMIN_DEST_OVERRIDE  = '~dest-override';
  ALIAS_ADMIN_DEST_CLEAR     = '~dest-clear';
  ALIAS_ADMIN_IGNORE         = '~ignore';
  ALIAS_ADMIN_UNIGNORE       = '~unignore';
  ALIAS_ADMIN_LIST_IGNORE    = '~ignore-list';
  ALIAS_ADMIN_BUCKETS_DUMP   = '~buckets-dump';
  ALIAS_ADMIN_BUCKETS_SAVE   = '~buckets-save';
  ALIAS_ADMIN_BUCKETS_LOAD   = '~buckets-load';
  ALIAS_ADMIN_BUCKETS_FLUSH  = '~buckets-flush';
  ALIAS_ADMIN_BUCKETS_LIST   = '~buckets-list';
  ALIAS_ADMIN_EXEC_CONFLICTS = '~exec-conflicts';
  ALIAS_ADMIN_EXEC_LIST      = '~exec-list';
  ALIAS_ADMIN_EXEC_TIMERS    = '~exec-timers';
  ALIAS_ADMIN_EXEC_ERRORS    = '~exec-errors';
  ALIAS_LOCK                 = '~lock';
  ALIAS_UNLOCK               = '~unlock';
  ALIAS_LIST                 = '~list';
  ALIAS_LIST_AUTH            = '~list-auth';

  TEMPLATE_TRAILING    = 'trailing';
  TEMPLATE_NICK        = 'nick';
  TEMPLATE_DESTINATION = 'dest';
  TEMPLATE_START       = 'start';
  TEMPLATE_ALIAS       = 'alias';
  TEMPLATE_DATA        = 'data';
  TEMPLATE_ITEMS       = 'items';
  TEMPLATE_CMD         = 'cmd';
  TEMPLATE_PARAMS      = 'params';
  TEMPLATE_TIMESTAMP   = 'timestamp';
  TEMPLATE_SERVER      = 'server';
  TEMPLATE_USER        = 'user';
  TEMPLATE_HOSTNAME    = 'hostname';
  TEMPLATE_PREFIX      = 'prefix';

  THROTTLE_LOCKOUT_TIME = 10.0;
  ANTI_FLOOD_DELAY      = 0.7;
  RAWMSG_TIME_COUNT     = 5;
  SHUTDOWN_DELAY        = 10.0;

{ =============================================================================
  Globals set from the config file, read once at startup.
  ============================================================================= }

var
  CFG_DEFAULT_NICK, CFG_USER_NAME, CFG_FULL_NAME, CFG_PASSWORD_FILE,
  CFG_BUCKETS_FILE, CFG_IGNORE_FILE, CFG_EXEC_FILE, CFG_INIT_CHAN_LIST,
  CFG_IRC_HOST_CONNECT, CFG_IRC_HOST, CFG_IRC_PORT, CFG_OPERATOR_ACCOUNT,
  CFG_OPERATOR_HOSTNAME, CFG_DEBUG_CHAN, CFG_NICKSERV_IDENTIFY_PROMPT,
  CFG_ADMIN_ACCOUNTS, CFG_MYSQL_LOG, CFG_NICKSERV_IDENTIFY, CFG_IFACE_ENABLE,
  CFG_SSL_PEER_NAME, CFG_SSL_CA_FILE, CFG_BOT_SCHEMA, CFG_LOG_TABLE,
  CFG_PHP_PATH: string;

  StartTime: Double;
  RestartRequested: Boolean = False;
  InvFmt: TFormatSettings;

{ =============================================================================
  Small helpers: PHP-ish string/array utilities used throughout.
  ============================================================================= }

function NowMicrotime: Double;
{$IFDEF UNIX}
var
  tv: TTimeVal;
begin
  fpgettimeofday(@tv, nil);
  Result := tv.tv_sec + (tv.tv_usec / 1000000.0);
end;
{$ELSE}
begin
  Result := (Now - EncodeDate(1970, 1, 1)) * 86400.0;
end;
{$ENDIF}

function Trim3(const s: string): string;
begin
  { PHP trim($x, "\n\r\0\x0B") equivalent used when parsing raw lines }
  Result := s;
  while (Length(Result) > 0) and (Result[1] in [#10, #13, #0, #11]) do
    Delete(Result, 1, 1);
  while (Length(Result) > 0) and (Result[Length(Result)] in [#10, #13, #0, #11]) do
    Delete(Result, Length(Result), 1);
end;

function ArrJoin(const arr: array of string; const sep: string): string;
var
  i: Integer;
begin
  Result := '';
  for i := 0 to High(arr) do
  begin
    if i > 0 then Result := Result + sep;
    Result := Result + arr[i];
  end;
end;

function StrListJoin(list: TStrings; const sep: string): string;
var
  i: Integer;
begin
  Result := '';
  for i := 0 to list.Count - 1 do
  begin
    if i > 0 then Result := Result + sep;
    Result := Result + list[i];
  end;
end;

function IsNumericStr(const s: string): Boolean;
var
  d: Double;
begin
  Result := (s <> '') and TryStrToFloat(s, d, InvFmt);
end;

function StrSplitToList(const s, sep: string): TStringList;
var
  parts: TStringArray;
  p: string;
begin
  Result := TStringList.Create;
  if s = '' then Exit;
  parts := s.Split(sep);
  for p in parts do
    Result.Add(p);
end;

function ShellQuote(const s: string): string;
begin
  { equivalent to PHP escapeshellarg() on unix: wraps in single quotes,
    escaping embedded single quotes as '\'' }
  Result := '''' + StringReplace(s, '''', '''\''''', [rfReplaceAll]) + '''';
end;

{ =============================================================================
  Own compact structured-value serializer (chosen over PHP-serialize
  compatibility since there's no legacy data file to migrate). Used for the
  buckets file and anywhere the bot needs to hand back a structured value
  (e.g. BUCKET_GET on the exec list) to an external exec script.
  Supported shapes: string, integer, float, bool, null, and array (list or
  assoc) -- everything this bot ever needs to serialize.
  ============================================================================= }

type
  TPhpValueKind = (pvkNull, pvkBool, pvkInt, pvkFloat, pvkString, pvkArray);

  TPhpValue = class;
  TPhpArrayEntry = record
    Key: string;
    KeyIsInt: Boolean;
    KeyInt: Int64;
    Value: TPhpValue;
  end;

  { TPhpValue }
  TPhpValue = class
    Kind: TPhpValueKind;
    BoolVal: Boolean;
    IntVal: Int64;
    FloatVal: Double;
    StrVal: string;
    Items: array of TPhpArrayEntry;
    destructor Destroy; override;
    class function NewStr(const s: string): TPhpValue; static;
    class function NewArray: TPhpValue; static;
    procedure ArrPush(const s: string); overload;
    procedure ArrSet(const key, s: string); overload;
    procedure ArrSet(const key: string; v: TPhpValue); overload;
    function ArrGet(const key: string): TPhpValue;
    function ArrGetStr(const key: string; const default_: string = ''): string;
  end;

destructor TPhpValue.Destroy;
var
  i: Integer;
begin
  for i := 0 to High(Items) do
    Items[i].Value.Free;
  inherited Destroy;
end;

class function TPhpValue.NewStr(const s: string): TPhpValue;
begin
  Result := TPhpValue.Create;
  Result.Kind := pvkString;
  Result.StrVal := s;
end;

class function TPhpValue.NewArray: TPhpValue;
begin
  Result := TPhpValue.Create;
  Result.Kind := pvkArray;
end;

procedure TPhpValue.ArrPush(const s: string);
var
  n: Integer;
begin
  n := Length(Items);
  SetLength(Items, n + 1);
  Items[n].KeyIsInt := True;
  Items[n].KeyInt := n;
  Items[n].Key := IntToStr(n);
  Items[n].Value := TPhpValue.NewStr(s);
end;

procedure TPhpValue.ArrSet(const key, s: string);
begin
  ArrSet(key, TPhpValue.NewStr(s));
end;

procedure TPhpValue.ArrSet(const key: string; v: TPhpValue);
var
  n: Integer;
begin
  n := Length(Items);
  SetLength(Items, n + 1);
  Items[n].KeyIsInt := False;
  Items[n].Key := key;
  Items[n].Value := v;
end;

function TPhpValue.ArrGet(const key: string): TPhpValue;
var
  i: Integer;
begin
  Result := nil;
  for i := 0 to High(Items) do
    if Items[i].Key = key then
    begin
      Result := Items[i].Value;
      Exit;
    end;
end;

function TPhpValue.ArrGetStr(const key: string; const default_: string): string;
var
  v: TPhpValue;
begin
  v := ArrGet(key);
  if (v = nil) or (v.Kind <> pvkString) then
    Result := default_
  else
    Result := v.StrVal;
end;

procedure PhpSerializeInto(v: TPhpValue; sb: TStringBuilder); forward;

procedure PhpSerializeInto(v: TPhpValue; sb: TStringBuilder);
var
  i: Integer;
  bytesLen: Integer;
begin
  if v = nil then
  begin
    sb.Append('N;');
    Exit;
  end;
  case v.Kind of
    pvkNull: sb.Append('N;');
    pvkBool: sb.Append('b:').Append(IfThen(v.BoolVal, '1', '0')).Append(';');
    pvkInt: sb.Append('i:').Append(IntToStr(v.IntVal)).Append(';');
    pvkFloat: sb.Append('d:').Append(FloatToStr(v.FloatVal, InvFmt)).Append(';');
    pvkString:
      begin
        bytesLen := Length(v.StrVal);
        sb.Append('s:').Append(IntToStr(bytesLen)).Append(':"').Append(v.StrVal).Append('";');
      end;
    pvkArray:
      begin
        sb.Append('a:').Append(IntToStr(Length(v.Items))).Append(':{');
        for i := 0 to High(v.Items) do
        begin
          if v.Items[i].KeyIsInt then
            sb.Append('i:').Append(IntToStr(v.Items[i].KeyInt)).Append(';')
          else
            PhpSerializeInto(TPhpValue.NewStr(v.Items[i].Key), sb);
          PhpSerializeInto(v.Items[i].Value, sb);
        end;
        sb.Append('}');
      end;
  end;
end;

function PhpSerialize(v: TPhpValue): string;
var
  sb: TStringBuilder;
begin
  sb := TStringBuilder.Create;
  try
    PhpSerializeInto(v, sb);
    Result := sb.ToString;
  finally
    sb.Free;
  end;
end;

function PhpUnserializeAt(const s: string; var pos: Integer): TPhpValue;
var
  tag: Char;
  numStr: string;
  lenStr: string;
  len, count, i: Integer;
  strVal: string;
  key: TPhpValue;
  val: TPhpValue;
  entry: TPhpArrayEntry;
begin
  Result := nil;
  if (pos > Length(s)) then Exit;
  tag := s[pos];
  case tag of
    'N':
      begin
        Result := TPhpValue.Create;
        Result.Kind := pvkNull;
        Inc(pos, 2);
      end;
    'b':
      begin
        Result := TPhpValue.Create;
        Result.Kind := pvkBool;
        Result.BoolVal := s[pos + 2] = '1';
        Inc(pos, 4);
      end;
    'i':
      begin
        Inc(pos, 2);
        numStr := '';
        while (pos <= Length(s)) and (s[pos] <> ';') do
        begin
          numStr := numStr + s[pos];
          Inc(pos);
        end;
        Inc(pos);
        Result := TPhpValue.Create;
        Result.Kind := pvkInt;
        Result.IntVal := StrToInt64Def(numStr, 0);
      end;
    'd':
      begin
        Inc(pos, 2);
        numStr := '';
        while (pos <= Length(s)) and (s[pos] <> ';') do
        begin
          numStr := numStr + s[pos];
          Inc(pos);
        end;
        Inc(pos);
        Result := TPhpValue.Create;
        Result.Kind := pvkFloat;
        Result.FloatVal := StrToFloatDef(numStr, 0, InvFmt);
      end;
    's':
      begin
        Inc(pos, 2);
        lenStr := '';
        while (pos <= Length(s)) and (s[pos] <> ':') do
        begin
          lenStr := lenStr + s[pos];
          Inc(pos);
        end;
        Inc(pos);
        Inc(pos);
        len := StrToIntDef(lenStr, 0);
        strVal := Copy(s, pos, len);
        Inc(pos, len);
        Inc(pos, 2);
        Result := TPhpValue.NewStr(strVal);
      end;
    'a':
      begin
        Inc(pos, 2);
        lenStr := '';
        while (pos <= Length(s)) and (s[pos] <> ':') do
        begin
          lenStr := lenStr + s[pos];
          Inc(pos);
        end;
        Inc(pos);
        Inc(pos);
        count := StrToIntDef(lenStr, 0);
        Result := TPhpValue.NewArray;
        for i := 1 to count do
        begin
          key := PhpUnserializeAt(s, pos);
          val := PhpUnserializeAt(s, pos);
          entry.Value := val;
          if key.Kind = pvkInt then
          begin
            entry.KeyIsInt := True;
            entry.KeyInt := key.IntVal;
            entry.Key := IntToStr(key.IntVal);
          end
          else
          begin
            entry.KeyIsInt := False;
            entry.Key := key.StrVal;
          end;
          key.Free;
          SetLength(Result.Items, Length(Result.Items) + 1);
          Result.Items[High(Result.Items)] := entry;
        end;
        Inc(pos);
      end;
  else
    Result := TPhpValue.Create;
    Result.Kind := pvkNull;
  end;
end;

function PhpUnserialize(const s: string): TPhpValue;
var
  pos: Integer;
begin
  pos := 1;
  if Trim(s) = '' then
  begin
    Result := nil;
    Exit;
  end;
  try
    Result := PhpUnserializeAt(s, pos);
  except
    Result := nil;
  end;
end;

function B64Encode(const s: string): string;
begin
  Result := EncodeStringBase64(s);
end;

function B64Decode(const s: string): string;
begin
  try
    Result := DecodeStringBase64(s);
  except
    Result := '';
  end;
end;

{ =============================================================================
  Core data types
  ============================================================================= }

type
  TIrcMessage = class
    Server: string;
    Microtime: Double;
    TimeStr: string;
    Data: string;
    Prefix: string;
    ParamsRaw: string;
    Trailing: string;
    Nick: string;
    User: string;
    Hostname: string;
    Destination: string;
    Cmd: string;
    function Clone: TIrcMessage;
  end;

  TExecEntry = class
    Alias: string;
    Timeout: Double;
    RepeatSec: Double;
    Auto: Integer;
    AllowEmpty: Integer;
    Accounts: TStringList;
    AccountsWildcard: string; { '', '@' (operator), '+' (admin), or '*' (any account) }
    Cmds: TStringList;
    Dests: TStringList;
    BucketLocks: TStringList;
    Cmd: string;
    Servers: TStringList;
    Saved: Boolean;
    Line: string;
    FileName: string;
    Help: TStringList;
    Enabled: Boolean;
    constructor Create;
    destructor Destroy; override;
  end;

  TProcessHandle = class
    Proc: TProcess;
    Command: string;
    Pid: Integer;
    Alias: string;
    BucketLocks: TStringList;
    Template: string;
    AllowEmpty: Integer;
    Timeout: Double;
    RepeatSec: Double;
    AutoPrivmsg: Integer;
    StartTs: Double;
    Nick, Cmd, Destination, Trailing, ServerName, UserName, Hostname, Prefix, ParamsRaw: string;
    OutBuf, ErrBuf: string;
    StdoutClosed, StderrClosed: Boolean;
    Reaped: Boolean;
    constructor Create;
    destructor Destroy; override;
  end;

  TTimeDeltaInfo = record
    LastTime: Double;
    LastDelta: Double;
    IgnoreStart: Double;
    HasIgnoreStart: Boolean;
  end;

  TStrDict = specialize TDictionary<string, string>;
  TStrListDict = specialize TDictionary<string, TStringList>;
  TExecDict = specialize TDictionary<string, TExecEntry>;
  TIntListDict = specialize TDictionary<string, TList>;
  TObjList = specialize TObjectList<TProcessHandle>;
  TDoubleList = specialize TList<Double>;

function TIrcMessage.Clone: TIrcMessage;
begin
  Result := TIrcMessage.Create;
  Result.Server := Server;
  Result.Microtime := Microtime;
  Result.TimeStr := TimeStr;
  Result.Data := Data;
  Result.Prefix := Prefix;
  Result.ParamsRaw := ParamsRaw;
  Result.Trailing := Trailing;
  Result.Nick := Nick;
  Result.User := User;
  Result.Hostname := Hostname;
  Result.Destination := Destination;
  Result.Cmd := Cmd;
end;

constructor TExecEntry.Create;
begin
  inherited Create;
  Accounts := TStringList.Create;
  Cmds := TStringList.Create;
  Dests := TStringList.Create;
  BucketLocks := TStringList.Create;
  Servers := TStringList.Create;
  Help := TStringList.Create;
  Enabled := True;
  Saved := True;
end;

destructor TExecEntry.Destroy;
begin
  Accounts.Free;
  Cmds.Free;
  Dests.Free;
  BucketLocks.Free;
  Servers.Free;
  Help.Free;
  inherited Destroy;
end;

constructor TProcessHandle.Create;
begin
  inherited Create;
  BucketLocks := TStringList.Create;
end;

destructor TProcessHandle.Destroy;
begin
  BucketLocks.Free;
  if Assigned(Proc) then Proc.Free;
  inherited Destroy;
end;

{ =============================================================================
  Global bot state (mirrors the PHP class's instance properties)
  ============================================================================= }

var
  Buckets: TStrDict;
  BucketLocks: TIntListDict;
  ExecList: TExecDict;
  ExecErrors: TStrListDict;
  IgnoreListG: TStringList;
  AliasLocks: specialize TDictionary<string, TStringList>;
  DestOverrides: specialize TDictionary<string, string>;
  TimeDeltas: specialize TDictionary<string, TTimeDeltaInfo>;
  Handles: TObjList;
  RawmsgTimes: TDoubleList;
  InitList, StartupList: TStringList;
  HelpTopics: TStringList;

  IrcSocket: TSocketStream;
  IrcConnected: Boolean = False;
  IrcPaused: Boolean = False;
  BotNick: string;
  ThrottleUntil: Double = 0;
  ConnectionEstablished: Boolean = False;
  SelfTriggerEvents: Boolean = False;
  ShutdownRequested: Boolean = False;
  ShutdownAt: Double = 0;
  RecvLineBuf: string = '';
  IfaceFd: cint = -1;

  AdminWhoisPending: specialize TDictionary<string, string>;
  RepeatLastFired: specialize TDictionary<string, Double>;

const
  IFACE_PATH_DEFAULT = '../data/exec_iface';

{ =============================================================================
  Config loading: simple key=value file, '#' comments, blank lines ignored.
  ============================================================================= }

function ReadConfig(const path: string): specialize TDictionary<string, string>;
var
  sl: TStringList;
  i, eq: Integer;
  line, key, val: string;
begin
  Result := specialize TDictionary<string, string>.Create;
  sl := TStringList.Create;
  try
    sl.LoadFromFile(path);
    for i := 0 to sl.Count - 1 do
    begin
      line := Trim(sl[i]);
      if (line = '') or (line[1] = '#') then Continue;
      eq := Pos('=', line);
      if eq = 0 then Continue;
      key := Trim(Copy(line, 1, eq - 1));
      val := Trim(Copy(line, eq + 1, MaxInt));
      if (Length(val) >= 2) and (val[1] = '"') and (val[Length(val)] = '"') then
        val := Copy(val, 2, Length(val) - 2);
      Result.AddOrSetValue(key, val);
    end;
  finally
    sl.Free;
  end;
end;

function CfgGet(cfg: specialize TDictionary<string, string>; const key, def_: string): string;
begin
  if not cfg.TryGetValue(key, Result) then
    Result := def_;
end;

procedure LoadConfiguration(const path: string);
var
  cfg: specialize TDictionary<string, string>;
begin
  cfg := ReadConfig(path);
  try
    CFG_DEFAULT_NICK             := CfgGet(cfg, 'DEFAULT_NICK', 'PasBot');
    CFG_USER_NAME                := CfgGet(cfg, 'USER_NAME', 'pasbot');
    CFG_FULL_NAME                := CfgGet(cfg, 'FULL_NAME', 'FreePascal IRC Bot');
    CFG_PASSWORD_FILE            := CfgGet(cfg, 'PASSWORD_FILE', '');
    CFG_BUCKETS_FILE             := CfgGet(cfg, 'BUCKETS_FILE', '../data/buckets.dat');
    CFG_IGNORE_FILE              := CfgGet(cfg, 'IGNORE_FILE', '../data/ignore.txt');
    CFG_EXEC_FILE                := CfgGet(cfg, 'EXEC_FILE', '../data/exec.conf');
    CFG_INIT_CHAN_LIST           := CfgGet(cfg, 'INIT_CHAN_LIST', '');
    CFG_IRC_HOST_CONNECT         := CfgGet(cfg, 'IRC_HOST_CONNECT', '');
    CFG_IRC_HOST                 := CfgGet(cfg, 'IRC_HOST', 'irc.libera.chat');
    CFG_IRC_PORT                 := CfgGet(cfg, 'IRC_PORT', '6667');
    CFG_OPERATOR_ACCOUNT         := CfgGet(cfg, 'OPERATOR_ACCOUNT', '');
    CFG_OPERATOR_HOSTNAME        := CfgGet(cfg, 'OPERATOR_HOSTNAME', '');
    CFG_DEBUG_CHAN                := CfgGet(cfg, 'DEBUG_CHAN', '');
    CFG_NICKSERV_IDENTIFY_PROMPT := CfgGet(cfg, 'NICKSERV_IDENTIFY_PROMPT', 'This nickname is registered');
    CFG_ADMIN_ACCOUNTS           := CfgGet(cfg, 'ADMIN_ACCOUNTS', '');
    CFG_NICKSERV_IDENTIFY         := CfgGet(cfg, 'NICKSERV_IDENTIFY', '');
    CFG_IFACE_ENABLE             := CfgGet(cfg, 'IFACE_ENABLE', '0');
    CFG_SSL_PEER_NAME            := CfgGet(cfg, 'SSL_PEER_NAME', CFG_IRC_HOST);
    CFG_SSL_CA_FILE              := CfgGet(cfg, 'SSL_CA_FILE', '');
    CFG_PHP_PATH                 := CfgGet(cfg, 'PHP_PATH', '/usr/bin/php');
    if CFG_IRC_HOST_CONNECT = '' then CFG_IRC_HOST_CONNECT := CFG_IRC_HOST;
  finally
    cfg.Free;
  end;
end;

{ =============================================================================
  Low-level socket I/O: connect (plain or TLS), non-blocking-ish line reads
  via a short recv timeout so the main loop never stalls for long, and a
  PING/PONG watchdog to detect a dead link (recv() returning 0 is ambiguous
  between "nothing to read yet" and "closed" once TLS is involved, so
  liveness is judged by the watchdog instead of by read results).
  ============================================================================= }

var
  LastRecvActivity: Double = 0;
  LastPingSent: Double = 0;
  WATCHDOG_IDLE_SECS: Double = 300.0;
  WATCHDOG_PING_SECS: Double = 120.0;

procedure LogLine(const s: string); forward;

function InitializeIrcSocket: Boolean;
var
  port: Word;
  handler: TSSLSocketHandler;
  useSsl: Boolean;
begin
  Result := False;
  LogLine('CFG_IRC_HOST_CONNECT: ' + CFG_IRC_HOST_CONNECT);
  LogLine('CFG_SSL_PEER_NAME: ' + CFG_SSL_PEER_NAME);
  LogLine('CFG_IRC_HOST: ' + CFG_IRC_HOST);
  port := StrToIntDef(CFG_IRC_PORT, 6667);
  useSsl := (port = 6697) or (port = 6698) or (port = 9999);
  try
    if useSsl then
    begin
      handler := TSSLSocketHandler.GetDefaultHandler;
      handler.SSLType := stAny;
      handler.VerifyPeerCert := (CFG_SSL_CA_FILE <> '');
      handler.SendHostAsSNI := True;
      handler.CertificateData.HostName := IfThen(CFG_SSL_PEER_NAME <> '', CFG_SSL_PEER_NAME, CFG_IRC_HOST);
      if CFG_SSL_CA_FILE <> '' then
        handler.CertificateData.TrustedCertificate.FileName := CFG_SSL_CA_FILE;
      IrcSocket := TInetSocket.Create(CFG_IRC_HOST_CONNECT, port, 0, handler);
    end
    else
      IrcSocket := TInetSocket.Create(CFG_IRC_HOST_CONNECT, port, 0, nil);
    TInetSocket(IrcSocket).Connect;
    IrcSocket.IOTimeout := 200;
    IrcConnected := True;
    LastRecvActivity := NowMicrotime;
    LastPingSent := 0;
    Result := True;
  except
    on E: Exception do
    begin
      LogLine('Connect failed: ' + E.Message + ' (OS error: ' + SysErrorMessage(GetLastOSError) + ')');
      if Assigned(IrcSocket) then FreeAndNil(IrcSocket);
      IrcConnected := False;
    end;
  end;
end;

procedure CloseIrcSocket;
begin
  if Assigned(IrcSocket) then
  begin
    try
      IrcSocket.Free;
    except
    end;
    IrcSocket := nil;
  end;
  IrcConnected := False;
end;

procedure SendRawLine(const line: string);
var
  data: string;
  written, total: Integer;
begin
  if not IrcConnected or not Assigned(IrcSocket) then Exit;
  data := line;
  if Length(data) > MAX_MSG_LENGTH then
    data := Copy(data, 1, MAX_MSG_LENGTH);
  data := data + #13#10;
  total := 0;
  try
    while total < Length(data) do
    begin
      written := IrcSocket.Write(data[total + 1], Length(data) - total);
      if written <= 0 then Break;
      Inc(total, written);
    end;
  except
    on E: Exception do
    begin
      LogLine('Send failed: ' + E.Message);
      IrcConnected := False;
    end;
  end;
end;

function PollIrcLines: TStringArray;
var
  buf: array[0..4095] of Byte;
  n: Integer;
  chunk: string;
  parts: TStringArray;
  i: Integer;
begin
  SetLength(Result, 0);
  if not IrcConnected or not Assigned(IrcSocket) then Exit;
  try
    n := IrcSocket.Read(buf[0], SizeOf(buf));
  except
    on E: Exception do
    begin
      LogLine('Recv error: ' + E.Message);
      IrcConnected := False;
      Exit;
    end;
  end;
  if n > 0 then
  begin
    SetLength(chunk, n);
    Move(buf[0], chunk[1], n);
    RecvLineBuf := RecvLineBuf + chunk;
    LastRecvActivity := NowMicrotime;
    if Pos(#10, RecvLineBuf) > 0 then
    begin
      parts := RecvLineBuf.Split([#10]);
      RecvLineBuf := parts[High(parts)];
      SetLength(Result, Length(parts) - 1);
      for i := 0 to Length(parts) - 2 do
        Result[i] := Trim3(parts[i]);
    end;
  end;
  if IrcConnected and (NowMicrotime - LastRecvActivity > WATCHDOG_IDLE_SECS) then
  begin
    LogLine('Watchdog: no data for ' + FloatToStr(WATCHDOG_IDLE_SECS) + 's, forcing reconnect');
    IrcConnected := False;
  end
  else if IrcConnected and (NowMicrotime - LastRecvActivity > WATCHDOG_PING_SECS) and
          (NowMicrotime - LastPingSent > WATCHDOG_PING_SECS) then
  begin
    SendRawLine('PING :' + IntToStr(Trunc(NowMicrotime)));
    LastPingSent := NowMicrotime;
  end;
end;

{ =============================================================================
  Logging + optional FIFO (IFACE) output.
  ============================================================================= }

procedure LogLine(const s: string);
begin
  WriteLn(FormatDateTime('yyyy-mm-dd hh:nn:ss', Now), ' ', s);
end;

{$IFDEF UNIX}
procedure InitIface;
var
  path: string;
begin
  if CFG_IFACE_ENABLE <> '1' then Exit;
  path := EXEC_OUTPUT_BUFFER_FILE;
  if not FileExists(path) then
    fpMkFifo(PChar(path), 6*64+4*8+4);
  IfaceFd := fpOpen(path, O_RDWR or O_NONBLOCK);
  if IfaceFd < 0 then
    LogLine('IFACE: could not open ' + path);
end;

procedure IfaceWrite(const s: string);
var
  data: string;
begin
  if IfaceFd < 0 then Exit;
  data := s + LineEnding;
  fpWrite(IfaceFd, data[1], Length(data));
end;
{$ELSE}
procedure InitIface;
begin
  if CFG_IFACE_ENABLE = '1' then
    LogLine('IFACE: not supported on this platform, ignoring IFACE_ENABLE');
end;

procedure IfaceWrite(const s: string);
begin
end;
{$ENDIF}

{ =============================================================================
  IRC line parsing: ":nick!user@host CMD p1 p2 :trailing text"
  ============================================================================= }

function ParseIrcLine(const raw: string): TIrcMessage;
var
  line, prefixPart, rest, beforeTrail, trailPart: string;
  bangPos, atPos, colonPos: Integer;
  tokens: TStringArray;
begin
  Result := TIrcMessage.Create;
  Result.Microtime := NowMicrotime;
  Result.TimeStr := FormatDateTime('yyyy-mm-dd hh:nn:ss', Now);
  Result.Data := raw;
  line := raw;

  if (line <> '') and (line[1] = ':') then
  begin
    colonPos := Pos(' ', line);
    if colonPos = 0 then
    begin
      prefixPart := Copy(line, 2, MaxInt);
      rest := '';
    end
    else
    begin
      prefixPart := Copy(line, 2, colonPos - 2);
      rest := Copy(line, colonPos + 1, MaxInt);
    end;
  end
  else
  begin
    prefixPart := '';
    rest := line;
  end;
  Result.Prefix := prefixPart;

  bangPos := Pos('!', prefixPart);
  atPos := Pos('@', prefixPart);
  if (bangPos > 0) and (atPos > bangPos) then
  begin
    Result.Nick := Copy(prefixPart, 1, bangPos - 1);
    Result.User := Copy(prefixPart, bangPos + 1, atPos - bangPos - 1);
    Result.Hostname := Copy(prefixPart, atPos + 1, MaxInt);
  end
  else
  begin
    Result.Nick := prefixPart;
    Result.User := '';
    Result.Hostname := '';
  end;

  colonPos := Pos(' :', rest);
  if colonPos > 0 then
  begin
    beforeTrail := Copy(rest, 1, colonPos - 1);
    trailPart := Copy(rest, colonPos + 2, MaxInt);
  end
  else if (rest <> '') and (rest[1] = ':') then
  begin
    beforeTrail := '';
    trailPart := Copy(rest, 2, MaxInt);
  end
  else
  begin
    beforeTrail := rest;
    trailPart := '';
  end;
  Result.Trailing := trailPart;
  Result.ParamsRaw := Trim(beforeTrail);

  tokens := Result.ParamsRaw.Split([' '], TStringSplitOptions.ExcludeEmpty);
  if Length(tokens) > 0 then
  begin
    Result.Cmd := UpperCase(tokens[0]);
    if Length(tokens) > 1 then
      Result.Destination := tokens[1]
    else
      Result.Destination := '';
  end
  else
  begin
    Result.Cmd := '';
    Result.Destination := '';
  end;

  if (Result.Cmd = 'PRIVMSG') or (Result.Cmd = 'NOTICE') then
  begin
    if SameText(Result.Destination, BotNick) then
      Result.Destination := Result.Nick;
  end;
end;

{ =============================================================================
  Bucket persistence
  ============================================================================= }

procedure BucketsSave;
var
  v: TPhpValue;
  pair: specialize TPair<string, string>;
begin
  v := TPhpValue.NewArray;
  try
    for pair in Buckets do
      v.ArrSet(pair.Key, pair.Value);
    try
      with TStringList.Create do
      try
        Text := PhpSerialize(v);
        SaveToFile(CFG_BUCKETS_FILE);
      finally
        Free;
      end;
    except
      on E: Exception do
        LogLine('BucketsSave failed: ' + E.Message);
    end;
  finally
    v.Free;
  end;
end;

procedure BucketsLoad;
var
  raw: string;
  v: TPhpValue;
  i: Integer;
begin
  if not FileExists(CFG_BUCKETS_FILE) then Exit;
  try
    with TStringList.Create do
    try
      LoadFromFile(CFG_BUCKETS_FILE);
      raw := Text;
    finally
      Free;
    end;
    v := PhpUnserialize(raw);
    if (v <> nil) and (v.Kind = pvkArray) then
    begin
      for i := 0 to High(v.Items) do
        if v.Items[i].Value.Kind = pvkString then
          Buckets.AddOrSetValue(v.Items[i].Key, v.Items[i].Value.StrVal);
    end;
    if v <> nil then v.Free;
  except
    on E: Exception do
      LogLine('BucketsLoad failed: ' + E.Message);
  end;
end;

function BucketGet(const key: string; const default_: string = ''): string;
begin
  if not Buckets.TryGetValue(key, Result) then
    Result := default_;
end;

procedure BucketSet(const key, value: string);
begin
  Buckets.AddOrSetValue(key, value);
end;

procedure BucketUnset(const key: string);
begin
  Buckets.Remove(key);
end;

procedure BucketAppend(const key, value: string);
var
  cur: string;
begin
  cur := BucketGet(key, '');
  Buckets.AddOrSetValue(key, cur + value);
end;

{ =============================================================================
  Ignore list persistence: one nick!user@host mask (or plain nick) per line.
  ============================================================================= }

procedure IgnoreListLoad;
begin
  IgnoreListG.Clear;
  if FileExists(CFG_IGNORE_FILE) then
    IgnoreListG.LoadFromFile(CFG_IGNORE_FILE);
end;

procedure IgnoreListSave;
begin
  try
    IgnoreListG.SaveToFile(CFG_IGNORE_FILE);
  except
    on E: Exception do
      LogLine('IgnoreListSave failed: ' + E.Message);
  end;
end;

function WildMatch(const pattern, s: string): Boolean;
var
  pat, str_: string;

  function MatchAt(pp, ss: Integer): Boolean;
  begin
    while True do
    begin
      if pp > Length(pat) then
        Exit(ss > Length(str_));
      case pat[pp] of
        '*':
          begin
            if pp = Length(pat) then Exit(True);
            while ss <= Length(str_) + 1 do
            begin
              if MatchAt(pp + 1, ss) then Exit(True);
              Inc(ss);
            end;
            Exit(False);
          end;
        '?':
          begin
            if ss > Length(str_) then Exit(False);
            Inc(pp); Inc(ss);
          end;
      else
        begin
          if (ss > Length(str_)) or (pat[pp] <> str_[ss]) then Exit(False);
          Inc(pp); Inc(ss);
        end;
      end;
    end;
  end;

begin
  pat := LowerCase(pattern);
  str_ := LowerCase(s);
  Result := MatchAt(1, 1);
end;

function IsIgnored(const nick, user, hostname: string): Boolean;
var
  mask: string;
  hostMask: string;
begin
  hostMask := nick + '!' + user + '@' + hostname;
  Result := False;
  for mask in IgnoreListG do
  begin
    if mask = '' then Continue;
    if WildMatch(mask, hostMask) or WildMatch(mask, nick) then
      Exit(True);
  end;
end;

{ =============================================================================
  Admin authentication: hostmask match against OPERATOR_HOSTNAME, or (if
  ADMIN_ACCOUNTS is configured) a WHOIS-verified services account.
  ============================================================================= }

function AdminAccountsList: TStringArray;
begin
  Result := CFG_ADMIN_ACCOUNTS.Split([',']);
end;

function IsAdminByHostmask(const nick, user, hostname: string): Boolean;
begin
  Result := (CFG_OPERATOR_HOSTNAME <> '') and
            WildMatch(CFG_OPERATOR_HOSTNAME, nick + '!' + user + '@' + hostname);
end;

procedure RequestWhoisVerify(const nick: string);
begin
  if CFG_ADMIN_ACCOUNTS = '' then Exit;
  AdminWhoisPending.AddOrSetValue(nick, '');
  SendRawLine('WHOIS ' + nick);
end;

procedure HandleWhoisAccountReply(const nick, account: string);
var
  accs: TStringArray;
  a: string;
begin
  if not AdminWhoisPending.ContainsKey(nick) then Exit;
  accs := AdminAccountsList;
  for a in accs do
    if SameText(Trim(a), account) then
    begin
      AdminWhoisPending.AddOrSetValue(nick, account);
      Exit;
    end;
  AdminWhoisPending.Remove(nick);
end;

function IsVerifiedAdminAccount(const nick: string): Boolean;
var
  acc: string;
begin
  Result := AdminWhoisPending.TryGetValue(nick, acc) and (acc <> '');
end;

{ =============================================================================
  Exec file loading: pipe-delimited alias definitions plus directive lines
  (include:/init:/startup:/help:) and a small "~alias-macro" config DSL for
  live editing. Format of a plain alias line:
    alias|timeout|repeat|auto|empty|accounts|cmds|dests|bucket_locks|cmd...
  (cmd may itself contain '|' -- everything from field 10 onward is rejoined
  with '|').
  ============================================================================= }

procedure AddExecError(const filename, msg: string);
var
  lst: TStringList;
begin
  LogLine(msg);
  if not ExecErrors.TryGetValue(filename, lst) then
  begin
    lst := TStringList.Create;
    ExecErrors.Add(filename, lst);
  end;
  lst.Add(msg);
end;

function LoadExecLine(const lineIn, filename: string; saved: Boolean): Boolean;
var
  line: string;
  parts: TStringArray;
  alias, timeoutS, repeatS, autoS, emptyS, accountsStr, cmdsStr, destsStr, locksStr, cmd: string;
  entry: TExecEntry;
  cmdParts: TStringArray;
  d: Double;
  i: Integer;
begin
  Result := False;
  line := Trim(lineIn);
  if (line = '') or (line[1] = '#') then Exit;

  parts := line.Split([EXEC_DELIM]);
  if Length(parts) < 10 then
  begin
    AddExecError(filename, 'not enough parameters: ' + line);
    Exit;
  end;

  alias      := Trim(parts[0]);
  timeoutS   := Trim(parts[1]);
  repeatS    := Trim(parts[2]);
  autoS      := Trim(parts[3]);
  emptyS     := Trim(parts[4]);
  accountsStr := Trim(parts[5]);
  cmdsStr    := UpperCase(Trim(parts[6]));
  destsStr   := LowerCase(Trim(parts[7]));
  locksStr   := UpperCase(Trim(parts[8]));

  cmd := '';
  for i := 9 to High(parts) do
  begin
    if i > 9 then cmd := cmd + EXEC_DELIM;
    cmd := cmd + parts[i];
  end;
  cmd := Trim(cmd);

  if (alias = '') or (not IsNumericStr(timeoutS)) or (not IsNumericStr(repeatS)) or
     ((autoS <> '0') and (autoS <> '1')) or ((emptyS <> '0') and (emptyS <> '1')) or
     (cmd = '') then
  begin
    AddExecError(filename, 'invalid parameter: ' + line);
    Exit;
  end;

  entry := TExecEntry.Create;
  entry.Alias := alias;
  entry.Timeout := StrToFloatDef(timeoutS, 5, InvFmt);
  entry.RepeatSec := StrToFloatDef(repeatS, 0, InvFmt);
  entry.Auto := StrToIntDef(autoS, 0);
  entry.AllowEmpty := StrToIntDef(emptyS, 1);
  entry.AccountsWildcard := '';

  if accountsStr <> '' then
  begin
    if (accountsStr = '@') or (accountsStr = '+') or (accountsStr = '*') then
      entry.AccountsWildcard := accountsStr
    else
    begin
      entry.Accounts.AddStrings(accountsStr.Split([',']));
      if entry.Accounts.IndexOf(BotNick) < 0 then
        entry.Accounts.Add(BotNick);
    end;
  end;

  if cmdsStr <> '' then entry.Cmds.AddStrings(cmdsStr.Split([',']));
  if destsStr <> '' then entry.Dests.AddStrings(destsStr.Split([',']));
  if locksStr <> '' then entry.BucketLocks.AddStrings(locksStr.Split([' ']));

  { "php script.php args" -> re-target at our configured PHP_PATH, resolved
    relative to the exec file's directory (mirrors the original's use of
    __DIR__ + PHP_BINARY). }
  cmdParts := cmd.Split([' ']);
  if (Length(cmdParts) >= 2) and SameText(cmdParts[0], 'php') and (Pos('.php', cmdParts[1]) > 0) then
  begin
    cmdParts[1] := IncludeTrailingPathDelimiter(ExtractFilePath(ExpandFileName(filename))) + cmdParts[1];
    if not FileExists(cmdParts[1]) then
    begin
      AddExecError(filename, 'php file not found: ' + line);
      entry.Free;
      Exit;
    end;
    cmdParts[0] := CFG_PHP_PATH;
    cmd := ArrJoin(cmdParts, ' ');
  end;

  entry.Cmd := cmd;
  entry.Servers.Clear;
  entry.Saved := saved;
  entry.Line := line;
  entry.FileName := filename;
  entry.Enabled := True;

  if ExecList.ContainsKey(alias) then
    ExecList[alias].Free;
  ExecList.AddOrSetValue(alias, entry);
  LogLine('SUCCESS: ' + line);
  Result := True;
end;

procedure ExtractDirectiveLines(const data, directive, filename: string; target: TStringList);
var
  dirDelim: string;
  rawLines, ln: TStringArray;
  line: string;
  s: string;
begin
  dirDelim := directive + FILE_DIRECTIVE_DELIM;
  rawLines := data.Split([#10]);
  for s in rawLines do
  begin
    line := Trim(s);
    if AnsiStartsStr(dirDelim, line) then
      target.Add(Copy(line, Length(dirDelim) + 1, MaxInt));
  end;
end;

procedure LoadExecFile(const path: string; saved: Boolean); forward;

procedure LoadExecFile(const path: string; saved: Boolean);
var
  sl: TStringList;
  data: string;
  i: Integer;
  line: string;
  includeTarget: string;
  dir: string;
begin
  if not FileExists(path) then
  begin
    AddExecError(path, 'exec file not found: ' + path);
    Exit;
  end;
  sl := TStringList.Create;
  try
    sl.LoadFromFile(path);
    data := sl.Text;
    ExtractDirectiveLines(data, FILE_DIRECTIVE_INIT, path, InitList);
    ExtractDirectiveLines(data, FILE_DIRECTIVE_STARTUP, path, StartupList);
    ExtractDirectiveLines(data, FILE_DIRECTIVE_HELP, path, HelpTopics);

    dir := ExtractFilePath(ExpandFileName(path));
    for i := 0 to sl.Count - 1 do
    begin
      line := Trim(sl[i]);
      if (line = '') or (line[1] = '#') then Continue;
      if AnsiStartsStr(FILE_DIRECTIVE_EXEC + FILE_DIRECTIVE_DELIM, line) then
      begin
        includeTarget := Copy(line, Length(FILE_DIRECTIVE_EXEC + FILE_DIRECTIVE_DELIM) + 1, MaxInt);
        if not (AnsiStartsStr('/', includeTarget) or ((Length(includeTarget) > 1) and (includeTarget[2] = ':'))) then
          includeTarget := dir + includeTarget;
        LoadExecFile(includeTarget, saved);
        Continue;
      end;
      if AnsiStartsStr(FILE_DIRECTIVE_INIT + FILE_DIRECTIVE_DELIM, line) or
         AnsiStartsStr(FILE_DIRECTIVE_STARTUP + FILE_DIRECTIVE_DELIM, line) or
         AnsiStartsStr(FILE_DIRECTIVE_HELP + FILE_DIRECTIVE_DELIM, line) then
        Continue; // already handled above
      LoadExecLine(line, path, saved);
    end;
  finally
    sl.Free;
  end;
end;

{ =============================================================================
  "~alias-macro <action> <alias> [key [value...]]" live-editing DSL.
  ============================================================================= }

function ProcessAliasConfigMacro(const macro: string; out msg: string): Boolean;
const
  ReservedKeys: array[0..15] of string = ('alias','timeout','repeat','auto','empty',
    'accounts','accounts_wildcard','cmds','dests','bucket_locks','cmd','servers',
    'saved','line','file','help');
  ReservedArrayKeys: array[0..5] of string = ('accounts','cmds','dests','bucket_locks','servers','help');

  function IsReserved(const k: string): Boolean;
  var i: Integer;
  begin
    Result := False;
    for i := 0 to High(ReservedKeys) do
      if ReservedKeys[i] = k then Exit(True);
  end;

var
  parts: TStringArray;
  filtered: TStringList;
  action, alias, key, value: string;
  entry: TExecEntry;
  i: Integer;
begin
  Result := False;
  msg := '';
  filtered := TStringList.Create;
  try
    parts := macro.Split([' ']);
    for i := 0 to High(parts) do
      if Trim(parts[i]) <> '' then filtered.Add(parts[i]);

    if filtered.Count < 2 then
    begin
      msg := 'needs at least an action and an alias';
      Exit;
    end;

    action := LowerCase(filtered[0]);
    alias := LowerCase(filtered[1]);
    filtered.Delete(0);
    filtered.Delete(0);

    if filtered.Count = 0 then
    begin
      if action = 'enable' then
      begin
        if not ExecList.TryGetValue(alias, entry) then
        begin
          msg := 'alias "' + alias + '" not found'; Exit;
        end;
        entry.Enabled := True;
        msg := 'alias "' + alias + '" successfully enabled';
        Result := True; Exit;
      end
      else if action = 'disable' then
      begin
        if not ExecList.TryGetValue(alias, entry) then
        begin
          msg := 'alias "' + alias + '" not found'; Exit;
        end;
        entry.Enabled := False;
        msg := 'alias "' + alias + '" successfully disabled';
        Result := True; Exit;
      end
      else if action = 'add' then
      begin
        if ExecList.ContainsKey(alias) then
        begin
          msg := 'alias already exists'; Exit;
        end;
        entry := TExecEntry.Create;
        entry.Alias := alias;
        entry.Timeout := 5; entry.RepeatSec := 0; entry.Auto := 0; entry.AllowEmpty := 1;
        entry.Cmd := ''; entry.Saved := False; entry.Line := ''; entry.FileName := '';
        entry.Enabled := False;
        ExecList.AddOrSetValue(alias, entry);
        msg := 'alias "' + alias + '" successfully added';
        Result := True; Exit;
      end
      else if action = 'delete' then
      begin
        if not ExecList.ContainsKey(alias) then
        begin
          msg := 'alias not found'; Exit;
        end;
        ExecList[alias].Free;
        ExecList.Remove(alias);
        msg := 'alias "' + alias + '" successfully deleted';
        Result := True; Exit;
      end
      else
      begin
        msg := 'invalid action'; Exit;
      end;
    end;

    if filtered.Count = 1 then
    begin
      key := filtered[0];
      if action = 'delete' then
      begin
        if IsReserved(key) then
        begin
          msg := 'unable to delete reserved element "' + key + '"'; Exit;
        end;
        msg := 'element "' + key + '" not found'; // this bot keeps a fixed struct; free-form keys unsupported
        Exit;
      end
      else if action = 'rename' then
      begin
        if not ExecList.TryGetValue(alias, entry) then
        begin
          msg := 'alias not found'; Exit;
        end;
        if key = alias then
        begin
          msg := 'good one you idiot'; Exit;
        end;
        entry.Enabled := False;
        ExecList.AddOrSetValue(key, entry);
        ExecList.Remove(alias);
        msg := 'alias "' + alias + '" successfully renamed (and disabled)';
        Result := True; Exit;
      end
      else
      begin
        msg := 'invalid action'; Exit;
      end;
    end;

    key := filtered[0];
    filtered.Delete(0);
    value := StrListJoin(filtered, ' ');

    if not ExecList.TryGetValue(alias, entry) then
    begin
      msg := 'alias not found'; Exit;
    end;

    if action = 'edit' then
    begin
      if key = 'timeout' then entry.Timeout := StrToFloatDef(value, entry.Timeout, InvFmt)
      else if key = 'repeat' then entry.RepeatSec := StrToFloatDef(value, entry.RepeatSec, InvFmt)
      else if key = 'auto' then entry.Auto := StrToIntDef(value, entry.Auto)
      else if key = 'empty' then entry.AllowEmpty := StrToIntDef(value, entry.AllowEmpty)
      else if key = 'accounts_wildcard' then entry.AccountsWildcard := value
      else if key = 'cmd' then entry.Cmd := value
      else if key = 'accounts' then begin entry.Accounts.Clear; entry.Accounts.AddStrings(value.Split([','])); end
      else if key = 'cmds' then begin entry.Cmds.Clear; entry.Cmds.AddStrings(value.Split([','])); end
      else if key = 'dests' then begin entry.Dests.Clear; entry.Dests.AddStrings(value.Split([','])); end
      else if key = 'bucket_locks' then begin entry.BucketLocks.Clear; entry.BucketLocks.AddStrings(value.Split([','])); end
      else if key = 'servers' then begin entry.Servers.Clear; entry.Servers.AddStrings(value.Split([','])); end
      else if key = 'help' then begin entry.Help.Clear; entry.Help.AddStrings(value.Split([','])); end
      else
      begin
        msg := 'unknown element "' + key + '"'; Exit;
      end;
      entry.Enabled := False;
      msg := 'alias "' + alias + '" element "' + key + '" successfully updated with value "' + value + '" (and alias disabled)';
      Result := True;
    end
    else
    begin
      msg := 'invalid action';
    end;
  finally
    filtered.Free;
  end;
end;

{ =============================================================================
  Bucket-lock bookkeeping (per-pid holds on named buckets, set via the
  bucket_locks field on an exec entry; released when the process reaps).
  ============================================================================= }

procedure AddBucketLock(const bucketName: string; pid: Integer);
var
  lst: TList;
begin
  if not BucketLocks.TryGetValue(bucketName, lst) then
  begin
    lst := TList.Create;
    BucketLocks.Add(bucketName, lst);
  end;
  lst.Add(Pointer(PtrInt(pid)));
  LogLine('BUCKET LOCK ADDED: ' + bucketName + ' BY PID ' + IntToStr(pid));
end;

procedure FreeBucketLocks(pid: Integer);
var
  bucketName: string;
  lst: TList;
  idx: Integer;
  keys: array of string;
  i: Integer;
begin
  SetLength(keys, 0);
  for bucketName in BucketLocks.Keys do
  begin
    SetLength(keys, Length(keys) + 1);
    keys[High(keys)] := bucketName;
  end;
  for i := 0 to High(keys) do
  begin
    bucketName := keys[i];
    lst := BucketLocks[bucketName];
    idx := lst.IndexOf(Pointer(PtrInt(pid)));
    if idx >= 0 then
    begin
      lst.Delete(idx);
      if lst.Count = 0 then
      begin
        lst.Free;
        BucketLocks.Remove(bucketName);
        LogLine('BUCKET UNLOCKED: ' + bucketName + ' BY ' + IntToStr(pid) + ' [NO LONGER LOCKED BY ANY PROCESSES]');
      end
      else
        LogLine('BUCKET UNLOCKED: ' + bucketName + ' BY ' + IntToStr(pid) + ' [STILL LOCKED BY OTHER PROCESS]');
    end;
  end;
end;

function IsBucketLocked(const bucketName: string): Boolean;
var
  lst: TList;
begin
  Result := BucketLocks.TryGetValue(bucketName, lst) and (lst.Count > 0);
end;

{ =============================================================================
  Sending PRIVMSGs (splits on newlines, truncates overlong lines, applies
  simple anti-flood pacing) and other outbound helpers.
  ============================================================================= }

procedure Privmsg(const destination, nick, msg: string);
var
  lines: TStringArray;
  ln: string;
  target: string;
begin
  target := destination;
  if target = '' then target := nick;
  if target = '' then Exit;
  lines := msg.Split([#10]);
  for ln in lines do
  begin
    if Trim(ln) = '' then Continue;
    SendRawLine('PRIVMSG ' + target + ' :' + ln);
  end;
end;

procedure Notice(const destination, msg: string);
begin
  if destination = '' then Exit;
  SendRawLine('NOTICE ' + destination + ' :' + msg);
end;

{ =============================================================================
  Process spawning + template substitution
  ============================================================================= }

function TemplateSubstitute(const tpl: string; items: TIrcMessage; const alias, trailing: string): string;
  function Wrap(const name: string): string;
  begin
    Result := TEMPLATE_DELIM + name + TEMPLATE_DELIM;
  end;
var
  s: string;
  itemsSerialized: string;
  v: TPhpValue;
begin
  s := tpl;
  v := TPhpValue.NewArray;
  try
    v.ArrSet('server', items.Server);
    v.ArrSet('data', items.Data);
    v.ArrSet('prefix', items.Prefix);
    v.ArrSet('params', items.ParamsRaw);
    v.ArrSet('trailing', items.Trailing);
    v.ArrSet('nick', items.Nick);
    v.ArrSet('user', items.User);
    v.ArrSet('hostname', items.Hostname);
    v.ArrSet('destination', items.Destination);
    v.ArrSet('cmd', items.Cmd);
    itemsSerialized := B64Encode(PhpSerialize(v));
  finally
    v.Free;
  end;

  s := StringReplace(s, Wrap(TEMPLATE_TRAILING), ShellQuote(trailing), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_NICK), ShellQuote(items.Nick), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_DESTINATION), ShellQuote(items.Destination), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_START), ShellQuote(FloatToStr(NowMicrotime, InvFmt)), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_ALIAS), ShellQuote(alias), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_DATA), ShellQuote(items.Data), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_ITEMS), ShellQuote(itemsSerialized), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_CMD), ShellQuote(items.Cmd), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_PARAMS), ShellQuote(items.ParamsRaw), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_TIMESTAMP), ShellQuote(FloatToStr(NowMicrotime, InvFmt)), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_SERVER), ShellQuote(items.Server), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_USER), ShellQuote(items.User), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_HOSTNAME), ShellQuote(items.Hostname), [rfReplaceAll]);
  s := StringReplace(s, Wrap(TEMPLATE_PREFIX), ShellQuote(items.Prefix), [rfReplaceAll]);
  Result := s;
end;

procedure SpawnAlias(const alias: string; entry: TExecEntry; items: TIrcMessage; const trailing: string);
var
  template: string;
  h: TProcessHandle;
  lockName: string;
  i: Integer;
begin
  template := TemplateSubstitute(entry.Cmd, items, alias, trailing);

  h := TProcessHandle.Create;
  h.Proc := TProcess.Create(nil);
  h.Proc.Executable := '/bin/sh';
  h.Proc.Parameters.Add('-c');
  h.Proc.Parameters.Add(template);
  h.Proc.Options := [poUsePipes];
  try
    h.Proc.Execute;
  except
    on E: Exception do
    begin
      LogLine('ERROR SPAWNING PROCESS: ' + template + ' (' + E.Message + ')');
      h.Free;
      Exit;
    end;
  end;

  h.Command := template;
  h.Pid := h.Proc.ProcessID;
  h.Alias := alias;
  h.BucketLocks.AddStrings(entry.BucketLocks);
  h.Template := entry.Cmd;
  h.AllowEmpty := entry.AllowEmpty;
  h.Timeout := entry.Timeout;
  h.RepeatSec := entry.RepeatSec;
  h.AutoPrivmsg := entry.Auto;
  h.StartTs := NowMicrotime;
  h.Nick := items.Nick;
  h.Cmd := items.Cmd;
  h.Destination := items.Destination;
  h.Trailing := trailing;
  h.ServerName := items.Server;
  h.UserName := items.User;
  h.Hostname := items.Hostname;
  h.Prefix := items.Prefix;
  h.ParamsRaw := items.ParamsRaw;

  if alias <> ALIAS_ALL then
    LogLine('EXEC [' + IntToStr(h.Pid) + ']: ' + template);

  for lockName in h.BucketLocks do
    if lockName <> '' then AddBucketLock(lockName, h.Pid);

  Handles.Add(h);
end;

{ =============================================================================
  Control lines emitted by spawned processes on stdout, e.g.:
    "/BUCKET_SET foo bar baz"   "/PRIVMSG hello there"   "/IRC JOIN #chan"
  ============================================================================= }

procedure HandleBucketControlLine(const prefix, rest: string; h: TProcessHandle);
var
  sp: Integer;
  key, value: string;
begin
  sp := Pos(' ', rest);
  if sp = 0 then begin key := rest; value := ''; end
  else begin key := Copy(rest, 1, sp - 1); value := Copy(rest, sp + 1, MaxInt); end;
  if key = '' then Exit;

  if prefix = PREFIX_BUCKET_GET then
    Privmsg(h.Destination, h.Nick, BucketGet(key, ''))
  else if prefix = PREFIX_BUCKET_SET then
    BucketSet(key, value)
  else if prefix = PREFIX_BUCKET_UNSET then
    BucketUnset(key)
  else if prefix = PREFIX_BUCKET_APPEND then
    BucketAppend(key, value);
end;

procedure ProcessExecStartups; forward;
procedure DispatchIrcLine(const raw: string); forward;

procedure HandleStdoutControlLine(h: TProcessHandle; const msgIn: string);
var
  msg, prefix, prefixMsg: string;
  sp: Integer;
  alias: string;
begin
  msg := msgIn;
  IfaceWrite('STDOUT[' + IntToStr(h.Pid) + ']: ' + msg);

  if Trim(msg) = DIRECTIVE_QUIT then
  begin
    ShutdownRequested := True;
    Exit;
  end;

  if h.AutoPrivmsg = 1 then
  begin
    Privmsg(h.Destination, h.Nick, msg);
    Exit;
  end;

  sp := Pos(' ', msg);
  if sp = 0 then begin prefix := UpperCase(msg); prefixMsg := ''; end
  else begin prefix := UpperCase(Copy(msg, 1, sp - 1)); prefixMsg := Copy(msg, sp + 1, MaxInt); end;

  if prefixMsg = '' then Exit;

  if prefix = PREFIX_IRC then
    SendRawLine(prefixMsg)
  else if prefix = PREFIX_PRIVMSG then
  begin
    if (h.Destination <> '') and (h.Nick <> '') then
      Privmsg(h.Destination, h.Nick, prefixMsg);
  end
  else if prefix = PREFIX_EXEC_ADD then
  begin
    if LoadExecLine(prefixMsg, msg, False) then
      Privmsg(h.Destination, h.Nick, 'successfully added exec line')
    else
      Privmsg(h.Destination, h.Nick, 'error adding exec line');
  end
  else if prefix = PREFIX_EXEC_DEL then
  begin
    alias := LowerCase(Trim(prefixMsg));
    if ExecList.ContainsKey(alias) then
    begin
      if ExecList[alias].Saved then
      begin
        ExecList[alias].Free;
        ExecList.Remove(alias);
        Privmsg(h.Destination, h.Nick, 'alias "' + alias + '" deleted from memory (not from file though)');
      end
      else
        Privmsg(h.Destination, h.Nick, 'alias "' + alias + '" with current configuration doesn''t exist in exec file');
    end
    else
      Privmsg(h.Destination, h.Nick, 'alias "' + alias + '" not found');
  end
  else if (prefix = PREFIX_BUCKET_GET) or (prefix = PREFIX_BUCKET_SET) or
          (prefix = PREFIX_BUCKET_UNSET) or (prefix = PREFIX_BUCKET_APPEND) then
    HandleBucketControlLine(prefix, prefixMsg, h)
  else if prefix = PREFIX_PAUSE then
    IrcPaused := True
  else if prefix = PREFIX_UNPAUSE then
    IrcPaused := False
  else if prefix = PREFIX_INTERNAL then
  begin
    if AnsiStartsStr(':', Trim(prefixMsg)) then
      DispatchIrcLine(Trim(prefixMsg))
    else
    begin
      alias := IfThen(h.Nick <> '', h.Nick, BotNick);
      DispatchIrcLine(':' + alias + ' ' + CMD_INTERNAL + IfThen(h.Destination <> '', ' ' + h.Destination, '') + ' :' + prefixMsg);
    end;
  end;
end;

function ReadAvailableLines(strm: TInputPipeStream; var buf: string): TStringArray;
var
  chunk: array[0..4095] of Byte;
  n: Integer;
  s: string;
  parts: TStringArray;
  i: Integer;
begin
  SetLength(Result, 0);
  if strm = nil then Exit;
  try
    if strm.NumBytesAvailable = 0 then Exit;
    n := strm.Read(chunk[0], SizeOf(chunk));
  except
    Exit;
  end;
  if n <= 0 then Exit;
  SetLength(s, n);
  Move(chunk[0], s[1], n);
  buf := buf + s;
  if Pos(#10, buf) > 0 then
  begin
    parts := buf.Split([#10]);
    buf := parts[High(parts)];
    SetLength(Result, Length(parts) - 1);
    for i := 0 to Length(parts) - 2 do
      Result[i] := parts[i];
  end;
end;

procedure PumpProcessOutput(h: TProcessHandle);
var
  lines: TStringArray;
  ln: string;
begin
  if not h.StdoutClosed then
  begin
    lines := ReadAvailableLines(h.Proc.Output, h.OutBuf);
    for ln in lines do
      HandleStdoutControlLine(h, ln);
  end;
  if not h.StderrClosed then
  begin
    lines := ReadAvailableLines(h.Proc.Stderr, h.ErrBuf);
    for ln in lines do
      IfaceWrite('STDERR[' + IntToStr(h.Pid) + ']: ' + ln);
  end;
end;

procedure KillProcess(h: TProcessHandle);
begin
  {$IFDEF UNIX}
  if h.Pid > 0 then
  try
    FpKill(h.Pid, SIGTERM);
  except
  end;
  {$ENDIF}
  try
    if Assigned(h.Proc) then h.Proc.Terminate(1);
  except
  end;
end;

{ Returns True while the handle is still alive (mirrors PHP's handleProcess) }
function ServiceProcessHandle(h: TProcessHandle): Boolean;
var
  running: Boolean;
begin
  PumpProcessOutput(h);

  running := True;
  try
    running := h.Proc.Running;
  except
    running := False;
  end;

  if not running then
  begin
    IfaceWrite('PROC_END[' + IntToStr(h.Pid) + ']');
    FreeBucketLocks(h.Pid);
    Result := False;
    Exit;
  end;

  if (h.Timeout > 0) and (NowMicrotime - h.StartTs > h.Timeout) then
  begin
    FreeBucketLocks(h.Pid);
    KillProcess(h);
    LogLine('process timed out: ' + h.Command);
    Result := False;
    Exit;
  end;

  Result := True;
end;

{ =============================================================================
  Reserved / operator / admin alias name lists (mirrors the PHP arrays of
  the same purpose).
  ============================================================================= }

const
  ReservedAliases: array[0..2] of string = (ALIAS_INIT, ALIAS_STARTUP, ALIAS_QUIT);
  OperatorAliases: array[0..1] of string = (ALIAS_ADMIN_QUIT, ALIAS_ADMIN_RESTART);
  AdminAliases: array[0..17] of string = (
    ALIAS_ADMIN_ALIAS_MACRO, ALIAS_ADMIN_NICK, ALIAS_ADMIN_PS, ALIAS_ADMIN_KILL,
    ALIAS_ADMIN_KILLALL, ALIAS_ADMIN_REHASH, ALIAS_ADMIN_DEST_OVERRIDE,
    ALIAS_ADMIN_DEST_CLEAR, ALIAS_ADMIN_IGNORE, ALIAS_ADMIN_UNIGNORE,
    ALIAS_ADMIN_LIST_IGNORE, ALIAS_ADMIN_BUCKETS_DUMP, ALIAS_ADMIN_BUCKETS_SAVE,
    ALIAS_ADMIN_BUCKETS_LOAD, ALIAS_ADMIN_BUCKETS_FLUSH, ALIAS_ADMIN_BUCKETS_LIST,
    ALIAS_ADMIN_EXEC_ERRORS, ALIAS_INTERNAL_RESTART);

function InArr(const s: string; const arr: array of string): Boolean;
var i: Integer;
begin
  Result := False;
  for i := 0 to High(arr) do if arr[i] = s then Exit(True);
end;

function IsOperatorAlias(const alias: string): Boolean;
var e: TExecEntry;
begin
  if InArr(alias, OperatorAliases) then Exit(True);
  Result := ExecList.TryGetValue(alias, e) and (e.AccountsWildcard = '@');
end;

function IsAdminAlias(const alias: string): Boolean;
var e: TExecEntry;
begin
  if InArr(alias, AdminAliases) then Exit(True);
  Result := ExecList.TryGetValue(alias, e) and (e.AccountsWildcard = '+');
end;

function HasAccountList(const alias: string): Boolean;
var e: TExecEntry;
begin
  Result := ExecList.TryGetValue(alias, e) and ((e.Accounts.Count > 0) or (e.AccountsWildcard = '*'));
end;

{ =============================================================================
  checkNick: simple repeat-trigger flood guard. If the same nick triggers
  the same alias at a suspiciously constant interval (delta stays within
  DELTA_TOLERANCE of the previous delta), the alias is silently ignored for
  IGNORE_TIME seconds.
  ============================================================================= }

function CheckNick(items: TIrcMessage; const alias: string): Boolean;
var
  lnick, tkey: string;
  info: TTimeDeltaInfo;
  thisDelta: Double;
begin
  if (items.Nick = BotNick) or (alias = ALIAS_ALL) then Exit(True);
  if (items.Cmd <> 'PRIVMSG') and (items.Cmd <> 'NOTICE') then Exit(True);

  lnick := LowerCase(items.Nick);
  tkey := lnick + #1 + alias;

  if not TimeDeltas.TryGetValue(tkey, info) then
  begin
    info.LastTime := NowMicrotime;
    info.LastDelta := 0;
    info.HasIgnoreStart := False;
    TimeDeltas.AddOrSetValue(tkey, info);
    Exit(True);
  end;

  thisDelta := NowMicrotime - info.LastTime;
  info.LastTime := NowMicrotime;

  if Abs(info.LastDelta - thisDelta) < DELTA_TOLERANCE then
  begin
    info.IgnoreStart := NowMicrotime;
    info.HasIgnoreStart := True;
    LogLine('ALIAS "' + alias + '" BY NICK "' + items.Nick + '" IGNORED FOR ' + FloatToStr(IGNORE_TIME, InvFmt) + ' SECONDS');
  end
  else if info.HasIgnoreStart then
  begin
    if (NowMicrotime - info.IgnoreStart) >= IGNORE_TIME then
    begin
      info.HasIgnoreStart := False;
      LogLine('IGNORE CLEARED FOR ALIAS "' + alias + '" BY NICK "' + items.Nick + '"');
    end;
  end;

  info.LastDelta := thisDelta;
  TimeDeltas.AddOrSetValue(tkey, info);
  Result := not info.HasIgnoreStart;
end;

{ =============================================================================
  processScripts equivalent: matches a message's leading word (or a locked
  alias) against execList and, if permitted, spawns it.
  ============================================================================= }

procedure ProcessScripts(items: TIrcMessage; const reservedAlias: string = '');
var
  nick, destination, cmd, trailing, alias: string;
  args: TStringArray;
  entry: TExecEntry;
  i: Integer;
  upCmd, lowDest: string;
begin
  nick := Trim(items.Nick);
  destination := Trim(items.Destination);
  cmd := Trim(items.Cmd);
  trailing := items.Trailing;

  if reservedAlias = '' then
  begin
    alias := '';
    if AliasLocks.ContainsKey(nick) then
    begin
      i := AliasLocks[nick].IndexOfName(destination);
      if i >= 0 then alias := AliasLocks[nick].ValueFromIndex[i];
    end;
    if alias <> '' then
    begin
      // trailing stays as-is when an alias lock is active
    end
    else
    begin
      args := trailing.Split([' ']);
      if Length(args) = 0 then Exit;
      alias := LowerCase(Trim(args[0]));
      trailing := ArrJoin(Copy(args, 1, Length(args) - 1), ' ');
    end;
    if InArr(alias, ReservedAliases) then Exit;
  end
  else
    alias := reservedAlias;

  if not ExecList.TryGetValue(alias, entry) then Exit;
  if not entry.Enabled then Exit;

  upCmd := UpperCase(cmd);
  if (entry.Cmds.Count > 0) and (entry.Cmds.IndexOf(upCmd) < 0) then
  begin
    LogLine('cmd-restricted alias "' + alias + '" triggered on non-permitted cmd "' + cmd + '" by "' + nick + '"');
    Exit;
  end;

  lowDest := LowerCase(destination);
  if (entry.Dests.Count > 0) and (entry.Dests.IndexOf(lowDest) < 0) then
  begin
    LogLine('dest-restricted alias "' + alias + '" triggered from non-permitted dest "' + destination + '" by "' + nick + '"');
    Exit;
  end;

  if (entry.Servers.Count > 0) and (entry.Servers.IndexOf(items.Server) < 0) then
  begin
    LogLine('server-restricted alias "' + alias + '" triggered from non-permitted server "' + items.Server + '" by "' + nick + '"');
    Exit;
  end;

  if not CheckNick(items, alias) and not InArr(alias, ReservedAliases) then Exit;

  if (entry.AllowEmpty = 0) and (trailing = '') and (destination <> '') and (nick <> '') then Exit;

  SpawnAlias(alias, entry, items, trailing);
end;

procedure ProcessTimedExecs;
var
  pair: specialize TPair<string, TExecEntry>;
  fakeItems: TIrcMessage;
  aliasesSnapshot: array of string;
  i: Integer;
  e: TExecEntry;
  lastFired, nowT: Double;
begin
  nowT := NowMicrotime;
  SetLength(aliasesSnapshot, 0);
  for pair in ExecList do
    if pair.Value.RepeatSec > 0 then
    begin
      SetLength(aliasesSnapshot, Length(aliasesSnapshot) + 1);
      aliasesSnapshot[High(aliasesSnapshot)] := pair.Key;
    end;
  for i := 0 to High(aliasesSnapshot) do
  begin
    if not ExecList.TryGetValue(aliasesSnapshot[i], e) then Continue;
    if not RepeatLastFired.TryGetValue(e.Alias, lastFired) then
      lastFired := StartTime; { first eligible tick after startup }
    if (nowT - lastFired) < e.RepeatSec then Continue;
    RepeatLastFired.AddOrSetValue(e.Alias, nowT);
    fakeItems := TIrcMessage.Create;
    try
      fakeItems.Nick := 'exec';
      fakeItems.Cmd := CMD_INTERNAL;
      fakeItems.Trailing := e.Alias;
      fakeItems.Destination := '';
      fakeItems.Server := CFG_IRC_HOST;
      fakeItems.Data := ':exec ' + CMD_INTERNAL + ' :' + e.Alias;
      ProcessScripts(fakeItems, e.Alias);
    finally
      fakeItems.Free;
    end;
  end;
end;

{ =============================================================================
  Admin re-auth pending state (mirrors the PHP singleton $adminData /
  $adminIsSock -- only one admin command can be "in flight" awaiting WHOIS
  verification at a time, same as the original).
  ============================================================================= }

var
  AdminPendingData: string = '';

procedure DoJoin(const chanList: string);
var
  chans: TStringArray;
  c: string;
begin
  chans := chanList.Split([',']);
  for c in chans do
    if Trim(c) <> '' then
      SendRawLine('JOIN ' + Trim(c));
end;

procedure DoQuit;
begin
  SendRawLine('QUIT :bye');
  ShutdownRequested := True;
  ShutdownAt := NowMicrotime;
end;

procedure SetBotNick(const n: string);
begin
  BotNick := n;
  BucketSet(BUCKET_BOT_NICK, n);
end;

function GetPid(h: TProcessHandle): Integer;
begin
  Result := h.Pid;
end;

{$IFDEF UNIX}
function KillProcessTree(rootPid: Integer): Boolean;
var
  outStr: TStringList;
  i, pid, ppid, sp: Integer;
  ln: string;
  toKill: array of Integer;
  changed: Boolean;
  k: Integer;

  function AlreadyIn(p: Integer): Boolean;
  var j: Integer;
  begin
    Result := False;
    for j := 0 to High(toKill) do if toKill[j] = p then Exit(True);
  end;

begin
  Result := True;
  try
    with TProcess.Create(nil) do
    try
      Executable := '/bin/sh';
      Parameters.Add('-c');
      Parameters.Add('ps -eo pid,ppid');
      Options := [poUsePipes, poWaitOnExit];
      Execute;
      outStr := TStringList.Create;
      try
        outStr.LoadFromStream(Output);
        SetLength(toKill, 1);
        toKill[0] := rootPid;
        repeat
          changed := False;
          for i := 0 to outStr.Count - 1 do
          begin
            ln := Trim(outStr[i]);
            sp := Pos(' ', ln);
            if sp = 0 then Continue;
            if not TryStrToInt(Trim(Copy(ln, 1, sp - 1)), pid) then Continue;
            if not TryStrToInt(Trim(Copy(ln, sp + 1, MaxInt)), ppid) then Continue;
            if AlreadyIn(ppid) and not AlreadyIn(pid) then
            begin
              SetLength(toKill, Length(toKill) + 1);
              toKill[High(toKill)] := pid;
              changed := True;
            end;
          end;
        until not changed;
        for k := High(toKill) downto 0 do
          try FpKill(toKill[k], SIGKILL); except end;
      finally
        outStr.Free;
      end;
    finally
      Free;
    end;
  except
    Result := False;
  end;
end;
{$ENDIF}

function KillProcessFull(h: TProcessHandle): Boolean;
begin
  IfaceWrite('PROC_KILL[' + IntToStr(h.Pid) + ']');
  {$IFDEF UNIX}
  { Best-effort process-tree kill: enumerate `ps -eo pid,ppid` and SIGKILL
    the target plus any descendants (mirrors the PHP kill/killRecurse). }
  KillProcessTree(h.Pid);
  {$ELSE}
  try
    h.Proc.Terminate(1);
  except
  end;
  {$ENDIF}
  Result := True;
end;

procedure CmdPs(items: TIrcMessage);
var
  h: TProcessHandle;
  n: Integer;
begin
  n := 0;
  for h in Handles do
  begin
    if h.Alias = ALIAS_ALL then Continue;
    Inc(n);
    Privmsg(items.Destination, items.Nick, '[' + IntToStr(h.Pid) + '] ' + h.Command);
  end;
  if n = 0 then
    Privmsg(items.Destination, items.Nick, 'no child processes currently running');
end;

procedure CmdKillAll(items: TIrcMessage);
var
  i: Integer;
  h: TProcessHandle;
  toRemove: array of Integer;
  msgs: TStringList;
begin
  if Handles.Count = 0 then
  begin
    Privmsg(items.Destination, items.Nick, 'no child processes currently running');
    Exit;
  end;
  msgs := TStringList.Create;
  try
    SetLength(toRemove, 0);
    for i := 0 to Handles.Count - 1 do
    begin
      h := Handles[i];
      if h.Alias = ALIAS_ALL then Continue;
      if KillProcessFull(h) then
      begin
        msgs.Add('terminated pid ' + IntToStr(h.Pid) + ': ' + h.Command);
        FreeBucketLocks(h.Pid);
        SetLength(toRemove, Length(toRemove) + 1);
        toRemove[High(toRemove)] := i;
      end
      else
        msgs.Add('error terminating pid ' + IntToStr(h.Pid) + ': ' + h.Command);
    end;
    for i := High(toRemove) downto 0 do
      Handles.Delete(toRemove[i]);
    for i := 0 to msgs.Count - 1 do
      Privmsg(items.Destination, items.Nick, msgs[i]);
  finally
    msgs.Free;
  end;
end;

procedure CmdKill(items: TIrcMessage; pid: Integer);
var
  i: Integer;
  h: TProcessHandle;
begin
  for i := 0 to Handles.Count - 1 do
  begin
    h := Handles[i];
    if h.Pid = pid then
    begin
      if KillProcessFull(h) then
      begin
        FreeBucketLocks(h.Pid);
        Handles.Delete(i);
        Privmsg(items.Destination, items.Nick, 'successfully terminated process with pid ' + IntToStr(pid));
      end
      else
        Privmsg(items.Destination, items.Nick, 'error terminating process with pid ' + IntToStr(pid));
      Exit;
    end;
  end;
  Privmsg(items.Destination, items.Nick, 'unable to find process with pid ' + IntToStr(pid));
end;

procedure CmdGetList(items: TIrcMessage);
var
  pair: specialize TPair<string, TExecEntry>;
  names: TStringList;
begin
  names := TStringList.Create;
  try
    for pair in ExecList do
      if pair.Value.Enabled and (pair.Value.Accounts.Count = 0) and (pair.Value.AccountsWildcard = '') then
        names.Add(pair.Key);
    names.Sort;
    if names.Count > 0 then
      Privmsg(items.Destination, items.Nick, 'available aliases: ' + StrListJoin(names, ', '))
    else
      Privmsg(items.Destination, items.Nick, 'no aliases available');
  finally
    names.Free;
  end;
end;

procedure CmdGetListAuth(items: TIrcMessage);
var
  pair: specialize TPair<string, TExecEntry>;
  names: TStringList;
begin
  names := TStringList.Create;
  try
    for pair in ExecList do
      if pair.Value.Enabled and ((pair.Value.Accounts.Count > 0) or (pair.Value.AccountsWildcard <> '')) then
        names.Add(pair.Key);
    names.Sort;
    if names.Count > 0 then
      Privmsg(items.Destination, items.Nick, 'restricted aliases: ' + StrListJoin(names, ', '))
    else
      Privmsg(items.Destination, items.Nick, 'no restricted aliases');
  finally
    names.Free;
  end;
end;

procedure CmdBucketsDump(items: TIrcMessage);
var
  pair: specialize TPair<string, string>;
begin
  for pair in Buckets do
    Privmsg(items.Destination, items.Nick, pair.Key + ' = ' + pair.Value);
end;

procedure CmdBucketsList(items: TIrcMessage);
var
  names: TStringList;
  pair: specialize TPair<string, string>;
begin
  names := TStringList.Create;
  try
    for pair in Buckets do names.Add(pair.Key);
    names.Sort;
    if names.Count > 0 then
      Privmsg(items.Destination, items.Nick, 'buckets: ' + StrListJoin(names, ', '))
    else
      Privmsg(items.Destination, items.Nick, 'no buckets set');
  finally
    names.Free;
  end;
end;

procedure CmdBucketsFlush(items: TIrcMessage);
begin
  Buckets.Clear;
  Privmsg(items.Destination, items.Nick, 'buckets flushed');
end;

{ =============================================================================
  Main IRC-line dispatcher: numeric handling, NickServ identify, admin
  WHOIS-account verification, then the built-in admin command switch.
  ============================================================================= }

procedure DispatchIrcLine(const raw: string);
var
  items: TIrcMessage;
  args: TStringArray;
  alias: string;
  needsAuth, isOp, isAdmin: Boolean;
  pendingReplay: string;
  macro, macroMsg: string;
  lockedAlias: string;
  ov: string;
  errPair: specialize TPair<string, TStringList>;
  execPair: specialize TPair<string, TExecEntry>;
  namesList: TStringList;
  jj: Integer;
begin
  items := ParseIrcLine(raw);
  try
    items.Server := CFG_IRC_HOST;
    IfaceWrite('RECV: ' + raw);

    if (CFG_DEBUG_CHAN <> '') and (items.Destination = CFG_DEBUG_CHAN) then Exit;
    if IgnoreListG.IndexOf(items.Nick) >= 0 then Exit;

    if (Buckets.ContainsKey(BUCKET_IGNORE_NEXT)) and (items.Nick = BotNick) then
    begin
      BucketUnset(BUCKET_IGNORE_NEXT);
      Exit;
    end;

    if SameText(items.Prefix, CFG_IRC_HOST) and (Pos('throttled', LowerCase(items.Trailing)) > 0) then
    begin
      LogLine('*** THROTTLED BY SERVER, PAUSING SENDS FOR ' + FloatToStr(THROTTLE_LOCKOUT_TIME, InvFmt) + 's ***');
      ThrottleUntil := NowMicrotime + THROTTLE_LOCKOUT_TIME;
      Exit;
    end;

    if items.Cmd = '330' then
    begin
      { WHOIS account reply: "<botnick> <nick> <account> :is logged in as" }
      if AdminPendingData <> '' then
      begin
        args := Trim(items.ParamsRaw).Split([' '], TStringSplitOptions.ExcludeEmpty);
        if (Length(args) >= 3) and SameText(args[0], BotNick) then
        begin
          HandleWhoisAccountReply(args[1], args[2]);
          isOp := (args[2] = CFG_OPERATOR_ACCOUNT);
          isAdmin := isOp or (InArr(args[2], AdminAccountsList));
          if IsVerifiedAdminAccount(args[1]) or isAdmin then
          begin
            AdminWhoisPending.Remove(args[1]);
            pendingReplay := AdminPendingData;
            AdminPendingData := '';
            DispatchIrcLine(pendingReplay);
            Exit;
          end;
        end;
      end;
      AdminPendingData := '';
    end;

    if items.Cmd = '376' then
    begin
      DoJoin(CFG_INIT_CHAN_LIST);
      if not ConnectionEstablished then
      begin
        ConnectionEstablished := True;
        BucketSet(BUCKET_CONNECTION_ESTABLISHED, '1');
        ProcessExecStartups;
      end;
    end;
    if (items.Cmd = 'NICK') and (items.Nick = BotNick) then SetBotNick(Trim(items.Trailing));
    if items.Cmd = '432' then SetBotNick(Trim(items.ParamsRaw));
    if items.Cmd = '433' then SetBotNick(BotNick + '_');

    if (items.Cmd = 'NOTICE') and (items.Nick = 'NickServ') and
       (items.Trailing = CFG_NICKSERV_IDENTIFY_PROMPT) then
    begin
      if (CFG_PASSWORD_FILE <> '') and FileExists(CFG_PASSWORD_FILE) and (CFG_NICKSERV_IDENTIFY = '1') then
      begin
        with TStringList.Create do
        try
          LoadFromFile(CFG_PASSWORD_FILE);
          if Count > 0 then SendRawLine('NickServ IDENTIFY ' + Trim(Text));
        finally
          Free;
        end;
      end;
    end;

    args := items.Trailing.Split([' ']);
    if Length(args) = 0 then alias := '' else alias := LowerCase(args[0]);

    needsAuth := IsOperatorAlias(alias) or IsAdminAlias(alias) or HasAccountList(alias);
    if needsAuth and (AdminPendingData = '') then
    begin
      AdminPendingData := raw;
      SendRawLine('WHOIS ' + items.Nick);
      Exit;
    end
    else if needsAuth then
      Exit; { another admin auth already in flight, like the PHP original }

    { generic alias dispatch (custom exec aliases) }
    ProcessScripts(items);

    if alias = ALIAS_ADMIN_NICK then
    begin
      if Length(args) = 2 then SendRawLine(':' + BotNick + ' NICK :' + Trim(args[1]));
    end
    else if alias = ALIAS_ADMIN_ALIAS_MACRO then
    begin
      macro := ArrJoin(Copy(args, 1, Length(args) - 1), ' ');
      macroMsg := '';
      ProcessAliasConfigMacro(macro, macroMsg);
      if macroMsg <> '' then Privmsg(items.Destination, items.Nick, 'alias config macro: ' + macroMsg);
    end
    else if alias = ALIAS_ADMIN_QUIT then
    begin
      if Length(args) = 1 then
      begin
        ProcessScripts(items, ALIAS_QUIT);
        DoQuit;
      end;
    end
    else if alias = ALIAS_ADMIN_PS then
    begin
      if Length(args) = 1 then CmdPs(items);
    end
    else if alias = ALIAS_ADMIN_KILL then
    begin
      if Length(args) = 2 then CmdKill(items, StrToIntDef(args[1], -1));
    end
    else if alias = ALIAS_ADMIN_KILLALL then
    begin
      if Length(args) = 1 then CmdKillAll(items);
    end
    else if alias = ALIAS_LIST then
    begin
      if CheckNick(items, alias) and (Length(args) = 1) then CmdGetList(items);
    end
    else if alias = ALIAS_LIST_AUTH then
    begin
      if CheckNick(items, alias) and (Length(args) = 1) then CmdGetListAuth(items);
    end
    else if alias = ALIAS_LOCK then
    begin
      if CheckNick(items, alias) then
      begin
        if Length(args) = 2 then
        begin
          if not AliasLocks.ContainsKey(items.Nick) then
            AliasLocks.Add(items.Nick, TStringList.Create);
          AliasLocks[items.Nick].Values[items.Destination] := args[1];
          Privmsg(items.Destination, items.Nick, 'alias "' + args[1] + '" locked for nick "' + items.Nick + '" in "' + items.Destination + '"');
        end
        else
          Privmsg(items.Destination, items.Nick, 'syntax: ' + ALIAS_LOCK + ' <alias>');
      end;
    end
    else if alias = ALIAS_UNLOCK then
    begin
      if CheckNick(items, alias) and AliasLocks.ContainsKey(items.Nick) and
         (AliasLocks[items.Nick].IndexOfName(items.Destination) >= 0) then
      begin
        lockedAlias := AliasLocks[items.Nick].Values[items.Destination];
        AliasLocks[items.Nick].Delete(AliasLocks[items.Nick].IndexOfName(items.Destination));
        Privmsg(items.Destination, items.Nick, 'alias "' + lockedAlias + '" unlocked for nick "' + items.Nick + '" in "' + items.Destination + '"');
      end;
    end
    else if alias = ALIAS_ADMIN_DEST_OVERRIDE then
    begin
      if Length(args) = 2 then
      begin
        DestOverrides.AddOrSetValue(items.Nick + #1 + items.Destination, args[1]);
        Privmsg(items.Destination, items.Nick, 'destination override "' + args[1] + '" set for nick "' + items.Nick + '" in "' + items.Destination + '"');
      end
      else
        Privmsg(items.Destination, items.Nick, 'syntax: ' + ALIAS_ADMIN_DEST_OVERRIDE + ' <dest>');
    end
    else if alias = ALIAS_ADMIN_DEST_CLEAR then
    begin
      if DestOverrides.TryGetValue(items.Nick + #1 + items.Destination, ov) then
      begin
        DestOverrides.Remove(items.Nick + #1 + items.Destination);
        Privmsg(items.Destination, items.Nick, 'destination override "' + ov + '" cleared for nick "' + items.Nick + '" in "' + items.Destination + '"');
      end;
    end
    else if alias = ALIAS_ADMIN_IGNORE then
    begin
      if Length(args) = 2 then
      begin
        if IgnoreListG.IndexOf(args[1]) < 0 then
        begin
          IgnoreListG.Add(args[1]);
          IgnoreListSave;
          Privmsg(items.Destination, items.Nick, BotNick + ' set to ignore ' + args[1]);
        end;
      end
      else
        Privmsg(items.Destination, items.Nick, 'syntax: ' + ALIAS_ADMIN_IGNORE + ' <nick>');
    end
    else if alias = ALIAS_ADMIN_UNIGNORE then
    begin
      if Length(args) = 2 then
      begin
        if IgnoreListG.IndexOf(args[1]) >= 0 then
        begin
          IgnoreListG.Delete(IgnoreListG.IndexOf(args[1]));
          IgnoreListSave;
          Privmsg(items.Destination, items.Nick, BotNick + ' set to listen to ' + args[1]);
        end
        else
          Privmsg(items.Destination, items.Nick, args[1] + ' not found in ' + BotNick + ' ignore list');
      end
      else
        Privmsg(items.Destination, items.Nick, 'syntax: ' + ALIAS_ADMIN_UNIGNORE + ' <nick>');
    end
    else if alias = ALIAS_ADMIN_LIST_IGNORE then
    begin
      if IgnoreListG.Count > 0 then
        Privmsg(items.Destination, items.Nick, BotNick + ' ignore list: ' + StrListJoin(IgnoreListG, ', '))
      else
        Privmsg(items.Destination, items.Nick, BotNick + ' isn''t ignoring anyone');
    end
    else if alias = ALIAS_ADMIN_REHASH then
    begin
      if Length(args) = 1 then
      begin
        for execPair in ExecList do execPair.Value.Free;
        ExecList.Clear;
        InitList.Clear; StartupList.Clear; HelpTopics.Clear;
        LoadExecFile(CFG_EXEC_FILE, True);
        Privmsg(items.Destination, items.Nick, 'successfully reloaded exec file (' + IntToStr(ExecList.Count) + ' aliases)');
      end;
    end
    else if alias = ALIAS_ADMIN_BUCKETS_DUMP then
    begin
      if Length(args) = 1 then CmdBucketsDump(items);
    end
    else if alias = ALIAS_ADMIN_BUCKETS_SAVE then
    begin
      if Length(args) = 1 then begin BucketsSave; Privmsg(items.Destination, items.Nick, 'buckets saved'); end;
    end
    else if alias = ALIAS_ADMIN_BUCKETS_LOAD then
    begin
      if Length(args) = 1 then begin BucketsLoad; Privmsg(items.Destination, items.Nick, 'buckets loaded'); end;
    end
    else if alias = ALIAS_ADMIN_BUCKETS_FLUSH then
    begin
      if Length(args) = 1 then CmdBucketsFlush(items);
    end
    else if alias = ALIAS_ADMIN_BUCKETS_LIST then
    begin
      if Length(args) = 1 then CmdBucketsList(items);
    end
    else if (alias = ALIAS_ADMIN_RESTART) or (alias = ALIAS_INTERNAL_RESTART) then
    begin
      if Length(args) = 1 then
      begin
        ProcessScripts(items, ALIAS_QUIT);
        RestartRequested := True;
        ShutdownRequested := True;
        ShutdownAt := NowMicrotime;
      end;
    end
    else if alias = ALIAS_ADMIN_EXEC_ERRORS then
    begin
      if Length(args) = 1 then
      begin
        if ExecErrors.Count > 0 then
        begin
          Privmsg(items.Destination, items.Nick, 'exec load errors:');
          for errPair in ExecErrors do
          begin
            Privmsg(items.Destination, items.Nick, '  ' + errPair.Key);
            for jj := 0 to errPair.Value.Count - 1 do
              Privmsg(items.Destination, items.Nick, '    ' + errPair.Value[jj]);
          end;
        end
        else
          Privmsg(items.Destination, items.Nick, 'no errors');
      end;
    end
    else if alias = ALIAS_ADMIN_EXEC_LIST then
    begin
      if Length(args) = 1 then
      begin
        namesList := TStringList.Create;
        try
          for execPair in ExecList do namesList.Add(execPair.Key + IfThen(execPair.Value.Enabled, '', ' (disabled)'));
          namesList.Sort;
          Privmsg(items.Destination, items.Nick, 'exec list (' + IntToStr(ExecList.Count) + '): ' + StrListJoin(namesList, ', '));
        finally
          namesList.Free;
        end;
      end;
    end;
  finally
    items.Free;
  end;
end;

{ =============================================================================
  init:/startup:/help: directive processing, connection registration, and
  the main event loop.
  ============================================================================= }

procedure ProcessExecInits;
var
  a: string;
  fake: TIrcMessage;
begin
  for a in InitList do
  begin
    if not ExecList.ContainsKey(Trim(a)) then Continue;
    fake := TIrcMessage.Create;
    try
      fake.Nick := 'init'; fake.Cmd := CMD_INIT; fake.Server := CFG_IRC_HOST;
      fake.Destination := ''; fake.Trailing := Trim(a);
      fake.Data := ':init ' + CMD_INIT + ' :' + Trim(a);
      ProcessScripts(fake, Trim(a));
    finally
      fake.Free;
    end;
  end;
end;

procedure ProcessExecStartups;
var
  a: string;
  fake: TIrcMessage;
begin
  for a in StartupList do
  begin
    if not ExecList.ContainsKey(Trim(a)) then Continue;
    fake := TIrcMessage.Create;
    try
      fake.Nick := 'startup'; fake.Cmd := CMD_STARTUP; fake.Server := CFG_IRC_HOST;
      fake.Destination := ''; fake.Trailing := Trim(a);
      fake.Data := ':startup ' + CMD_STARTUP + ' :' + Trim(a);
      ProcessScripts(fake, Trim(a));
    finally
      fake.Free;
    end;
  end;
end;

procedure RegisterConnection;
begin
  SetBotNick(CFG_DEFAULT_NICK);
  SendRawLine('NICK ' + BotNick);
  SendRawLine('USER ' + CFG_USER_NAME + ' 0 * :' + CFG_FULL_NAME);
end;

procedure InitGlobals;
begin
  Buckets := TStrDict.Create;
  BucketLocks := TIntListDict.Create;
  ExecList := TExecDict.Create;
  ExecErrors := TStrListDict.Create;
  IgnoreListG := TStringList.Create;
  AliasLocks := specialize TDictionary<string, TStringList>.Create;
  DestOverrides := specialize TDictionary<string, string>.Create;
  TimeDeltas := specialize TDictionary<string, TTimeDeltaInfo>.Create;
  Handles := TObjList.Create(True);
  RawmsgTimes := TDoubleList.Create;
  InitList := TStringList.Create;
  StartupList := TStringList.Create;
  HelpTopics := TStringList.Create;
  AdminWhoisPending := specialize TDictionary<string, string>.Create;
  RepeatLastFired := specialize TDictionary<string, Double>.Create;
end;

procedure DoRestartExec(const exePath: string; const argv: array of string);
{$IFDEF UNIX}
var
  args: array of PChar;
  i: Integer;
begin
  SetLength(args, Length(argv) + 2);
  args[0] := PChar(exePath);
  for i := 0 to High(argv) do args[i + 1] := PChar(argv[i]);
  args[High(args)] := nil;
  FpExecve(exePath, @args[0], envp);
  { only reached on failure }
  LogLine('restart exec failed, exiting instead');
  Halt(1);
end;
{$ELSE}
begin
  LogLine('restart not supported on this platform, exiting instead');
  Halt(0);
end;
{$ENDIF}

procedure MainLoop;
var
  lines: TStringArray;
  ln: string;
  i: Integer;
  h: TProcessHandle;
  reconnectBackoff: Double;
  lastTick: Double;
begin
  reconnectBackoff := 2.0;
  while not (ShutdownRequested and (NowMicrotime >= ShutdownAt + SHUTDOWN_DELAY)) do
  begin
    if not IrcConnected then
    begin
      if ShutdownRequested then Break;
      LogLine('(re)connecting to ' + CFG_IRC_HOST_CONNECT + ':' + CFG_IRC_PORT + ' ...');
      if InitializeIrcSocket then
      begin
        RecvLineBuf := '';
        RegisterConnection;
        reconnectBackoff := 2.0;
      end
      else
      begin
        Sleep(Trunc(reconnectBackoff * 1000));
        if reconnectBackoff < 60 then reconnectBackoff := reconnectBackoff * 1.7;
        Continue;
      end;
    end;

    lines := PollIrcLines;
    for ln in lines do
      if ln <> '' then
      begin
        if AnsiStartsStr('PING', ln) then
          SendRawLine('PONG' + Copy(ln, 5, MaxInt))
        else
          DispatchIrcLine(ln);
      end;

    if not IrcConnected then
    begin
      CloseIrcSocket;
      Continue;
    end;

    { service running child processes }
    for i := Handles.Count - 1 downto 0 do
    begin
      h := Handles[i];
      if not ServiceProcessHandle(h) then
        Handles.Delete(i);
    end;

    ProcessTimedExecs;

    if ShutdownRequested and (Handles.Count = 0) and (NowMicrotime >= ShutdownAt + 1.0) then
      Break;

    Sleep(20); { keep CPU usage sane between ticks }
  end;

  CloseIrcSocket;
  BucketsSave;

  if RestartRequested then
  begin
    LogLine('restarting...');
    DoRestartExec(ParamStr(0), []);
  end;
end;

{ =============================================================================
  Program entry point
  ============================================================================= }

var
  ConfigPath: string;

begin
  InvFmt := DefaultFormatSettings;
  InvFmt.DecimalSeparator := '.';
  InvFmt.ThousandSeparator := #0;

  DLLSSLName  := 'C:\dev\openssl\libssl-4-x64.dll';
  DLLUtilName := 'C:\dev\openssl\libcrypto-4-x64.dll';

  StartTime := NowMicrotime;
  InitGlobals;

  if ParamCount < 1 then
  begin
    WriteLn('usage: ', ParamStr(0), ' <config-file>');
    Halt(1);
  end;
  ConfigPath := ParamStr(1);

  try
    LoadConfiguration(ConfigPath);
  except
    on E: Exception do
    begin
      WriteLn('failed to load config "', ConfigPath, '": ', E.Message);
      Halt(1);
    end;
  end;

  BotNick := CFG_DEFAULT_NICK;
  BucketsLoad;
  if BucketGet(BUCKET_BOT_NICK, '') <> '' then
    BotNick := BucketGet(BUCKET_BOT_NICK, CFG_DEFAULT_NICK);
  IgnoreListLoad;
  InitIface;

  LogLine('loading exec file: ' + CFG_EXEC_FILE);
  LoadExecFile(CFG_EXEC_FILE, True);
  LogLine('loaded ' + IntToStr(ExecList.Count) + ' aliases, ' + IntToStr(ExecErrors.Count) + ' files with errors');

  ProcessExecInits;

  LogLine('starting main loop (nick=' + BotNick + ', host=' + CFG_IRC_HOST_CONNECT + ':' + CFG_IRC_PORT + ')');
  MainLoop;

  LogLine('exiting.');
end.

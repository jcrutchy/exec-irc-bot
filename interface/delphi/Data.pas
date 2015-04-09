unit Data;

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
  uLkJSON;

type

  TExecMessage = class(TObject)
  private
    Fmsg_buf: string;
    Fmsg_type: string;
    Fmsg_time: Double;
    Fmsg_command: string;
    Fmsg_pid: Integer;
    Fmsg_alias: string;
    Fmsg_template: string;
    Fmsg_allow_empty: string;
    Fmsg_timeout: string;
    Fmsg_repeat: string;
    Fmsg_auto_privmsg: string;
    Fmsg_start: Double;
    Fmsg_nick: string;
    Fmsg_cmd: string;
    Fmsg_destination: string;
    Fmsg_trailing: string;
    Fmsg_server: string;
    Fmsg_data: string;
    Fmsg_prefix: string;
    Fmsg_params: string;
    Fmsg_user: string;
    Fmsg_hostname: string;
    Fmsg_accounts_wildcard: string;
    Fmsg_exec_line: string;
    Fmsg_file: string;
    Fmsg_bucket_locks: Classes.TStrings;
    Fmsg_accounts: Classes.TStrings;
    Fmsg_cmds: Classes.TStrings;
    Fmsg_dests: Classes.TStrings;
  public
    constructor Create;
    destructor Destroy; override;
  public
    function Load(const Data: string): Boolean;
  public
    property msg_buf: string read Fmsg_buf;
    property msg_type: string read Fmsg_type;
    property msg_time: Double read Fmsg_time;
    property msg_command: string read Fmsg_command;
    property msg_pid: Integer read Fmsg_pid;
    property msg_alias: string read Fmsg_alias;
    property msg_template: string read Fmsg_template;
    property msg_allow_empty: string read Fmsg_allow_empty;
    property msg_timeout: string read Fmsg_timeout;
    property msg_repeat: string read Fmsg_repeat;
    property msg_auto_privmsg: string read Fmsg_auto_privmsg;
    property msg_start: Double read Fmsg_start;
    property msg_nick: string read Fmsg_nick;
    property msg_cmd: string read Fmsg_cmd;
    property msg_destination: string read Fmsg_destination;
    property msg_trailing: string read Fmsg_trailing;
    property msg_server: string read Fmsg_server;
    property msg_data: string read Fmsg_data;
    property msg_prefix: string read Fmsg_prefix;
    property msg_params: string read Fmsg_params;
    property msg_user: string read Fmsg_user;
    property msg_hostname: string read Fmsg_hostname;
    property msg_accounts_wildcard: string read Fmsg_accounts_wildcard;
    property msg_exec_line: string read Fmsg_exec_line;
    property msg_file: string read Fmsg_file;
    property msg_bucket_locks: Classes.TStrings read Fmsg_bucket_locks;
    property msg_accounts: Classes.TStrings read Fmsg_accounts;
    property msg_cmds: Classes.TStrings read Fmsg_cmds;
    property msg_dests: Classes.TStrings read Fmsg_dests;
  end;

  TExecMessages = class(TObject)
  private
    FItems: Classes.TList;
  private
    function GetCount: Integer;
    function GetItem(const Index: Integer): TExecMessage;
  public
    constructor Create;
    destructor Destroy; override;
  public
    function Add(const S: string): TExecMessage;
    procedure Clear;
  public
    property Count: Integer read GetCount;
    property Items[const Index: Integer]: TExecMessage read GetItem; default;
  end;

  TProcessPanel = class(TCustomPanel)
  private
    
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
  end;

  TMonitorPanel = class(TCustomPanel)
  private

  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
  end;

implementation

{ TExecMessage }

constructor TExecMessage.Create;
begin
  Fmsg_bucket_locks := Classes.TStringList.Create;
  Fmsg_accounts := Classes.TStringList.Create;
  Fmsg_cmds := Classes.TStringList.Create;
  Fmsg_dests := Classes.TStringList.Create;
end;

destructor TExecMessage.Destroy;
begin
  Fmsg_bucket_locks.Free;
  Fmsg_accounts.Free;
  Fmsg_cmds.Free;
  Fmsg_dests.Free;
  inherited;
end;

function TExecMessage.Load(const Data: string): Boolean;
var
  i: Integer;
  item: uLkJSON.TlkJSONobject;
  json: uLkJSON.TlkJSONbase;
  json_obj: uLkJSON.TlkJSONobject;
begin
  Result := True;
  try
    json := uLkJSON.TlkJSON.ParseText(Data);
    json_obj := uLkJSON.TlkJSONobject(json);
    Fmsg_buf := json_obj.getString('buf');
    Fmsg_type := json_obj.getString('type');
    Fmsg_time := json_obj.getDouble('time');
    if (Fmsg_type = 'stdout') or (Fmsg_type = 'stderr') then
    begin
      Fmsg_command := json_obj.getString('command');
      Fmsg_pid := json_obj.getInt('pid');
      Fmsg_alias := json_obj.getString('alias');
      Fmsg_template := json_obj.getString('template');
      Fmsg_allow_empty := json_obj.getString('allow_empty');
      Fmsg_timeout := json_obj.getString('timeout');
      Fmsg_repeat := json_obj.getString('repeat');
      Fmsg_auto_privmsg := json_obj.getString('auto_privmsg');
      Fmsg_start := json_obj.getDouble('start');
      Fmsg_nick := json_obj.getString('nick');
      Fmsg_cmd := json_obj.getString('cmd');
      Fmsg_destination := json_obj.getString('destination');
      Fmsg_trailing := json_obj.getString('trailing');
      Fmsg_server := json.Field['items'].Field['server'].Value;
      Fmsg_data := json.Field['items'].Field['data'].Value;
      Fmsg_prefix := json.Field['items'].Field['prefix'].Value;
      Fmsg_params := json.Field['items'].Field['params'].Value;
      Fmsg_user := json.Field['items'].Field['user'].Value;
      Fmsg_hostname := json.Field['items'].Field['hostname'].Value;
      Fmsg_accounts_wildcard := json.Field['exec'].Field['accounts_wildcard'].Value;
      Fmsg_exec_line := json.Field['exec'].Field['line'].Value;
      Fmsg_file := json.Field['exec'].Field['file'].Value;
      item := uLkJSON.TlkJSONobject(json.Field['exec'].Field['accounts']);
      for i := 0 to item.Count - 1 do
        Fmsg_accounts.Add(item.Child[i].Value);
      item := uLkJSON.TlkJSONobject(json.Field['exec'].Field['cmds']);
      for i := 0 to item.Count - 1 do
        Fmsg_cmds.Add(item.Child[i].Value);
      item := uLkJSON.TlkJSONobject(json.Field['exec'].Field['dests']);
      for i := 0 to item.Count - 1 do
        Fmsg_dests.Add(item.Child[i].Value);
      item := uLkJSON.TlkJSONobject(json.Field['exec'].Field['bucket_locks']);
      for i := 0 to item.Count - 1 do
        Fmsg_bucket_locks.Add(item.Child[i].Value);
    end;
    json.Free;
  except
    Result := False;
  end;
end;

{ TExecMessages }

function TExecMessages.Add(const S: string): TExecMessage;
var
  Msg: TExecMessage;
begin
  Msg := TExecMessage.Create;
  if Msg.Load(S) then
    Result := Msg
  else
  begin
    Msg.Free;
    Result := nil;
  end;
end;

procedure TExecMessages.Clear;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Items[i].Free;
  FItems.Clear;
end;

constructor TExecMessages.Create;
begin
  FItems := Classes.TList.Create;
end;

destructor TExecMessages.Destroy;
begin
  Clear;
  FItems.Free;
  inherited;
end;

function TExecMessages.GetCount: Integer;
begin
  Result := FItems.Count;
end;

function TExecMessages.GetItem(const Index: Integer): TExecMessage;
begin
  Result := FItems[Index];
end;

{ TProcessPanel }

constructor TProcessPanel.Create(AOwner: TComponent);
begin
  inherited;

end;

destructor TProcessPanel.Destroy;
begin

  inherited;
end;

{ TMonitorPanel }

constructor TMonitorPanel.Create(AOwner: TComponent);
begin
  inherited;

end;

destructor TMonitorPanel.Destroy;
begin

  inherited;
end;

end.
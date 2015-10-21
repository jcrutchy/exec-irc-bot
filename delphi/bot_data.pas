unit bot_data;

interface

uses
  Windows,
  SysUtils,
  Classes,
  Graphics,
  Controls,
  Forms,
  Sockets,
  ScktComp,
  Dialogs,
  ExtCtrls,
  StrUtils;

type

  TBotClientThread = class;
  TBotServer = class;
  TBotServerArray = class;
  TBotChannel = class;
  TBotChannelArray = class;
  TBotUser = class;
  TBotUserArray = class;

  { TBotMessage }

  TBotMessage = record
    Command: string;
    Data: string;
    Destination: string;
    Hostname: string;
    Nick: string;
    Params: string;
    Prefix: string;
    Server: string;
    TimeStamp: TDateTime;    Trailing: string;
    User: string;
    Valid: Boolean;
  end;

  TBotReceiveEvent = procedure(const Server: TBotServer; const Message: TBotMessage; const Data: string) of object;

  { TBotClientThread }

  TBotClientThread = class(TThread)
  private
    FClient: Sockets.TTcpClient;
    FServer: TBotServer;
    FBuffer: string;
  private
    procedure ClientError(Sender: TObject; SocketError: Integer);
    procedure ClientSend(Sender: TObject; Buf: PAnsiChar; var DataLen: Integer);
  public
    constructor Create(CreateSuspended: Boolean);
    procedure Update;
    procedure Send(const Msg: string);
    procedure Execute; override;
  public
    property Server: TBotServer read FServer write FServer;
  end;

  { TBotServer }

  TBotServer = class(TObject)
  private
    FRemoteHost: string;
    FRemotePort: string;
    FNickName: string;
    FUserName: string;
    FFullName: string;
    FHostName: string;
    FServerName: string;
    FNickServPasswordFileName: string;
    FHandler: TBotReceiveEvent;
    FThread: TBotClientThread;
  public
    constructor Create(const Handler: TBotReceiveEvent);
    destructor Destroy; override;
  public
    procedure Connect(const RemoteHost, RemotePort, NickName, UserName, FullName, HostName, ServerName: string);
    procedure Send(const Msg: string; const Obfuscate: Boolean = False);
  public
    property RemoteHost: string read FRemoteHost;
    property RemotePort: string read FRemotePort;
    property NickName: string read FNickName;
    property UserName: string read FUserName;
    property FullName: string read FFullName;
    property HostName: string read FHostName;
    property ServerName: string read FServerName;
    property NickServPasswordFileName: string read FNickServPasswordFileName;
  public
    property Handler: TBotReceiveEvent read FHandler write FHandler;
  end;

  { TBotServerArray }

  TBotServerArray = class(TObject)
  private
    FGlobalHandler: TBotReceiveEvent;
    FServers: Classes.TList;
  private
    function GetCount: Integer;
    function GetServer(const Index: Integer): TBotServer;
  public
    constructor Create(const GlobalHandler: TBotReceiveEvent);
    destructor Destroy; override;
  public
    function Add: TBotServer;
  public
    property Count: Integer read GetCount;
    property Servers[const Index: Integer]: TBotServer read GetServer; default;
  end;

  { TBotChannel }

  TBotChannel = class(TObject)
  private

  public
    constructor Create;
    destructor Destroy; override;
  public

  end;

  { TBotChannelArray }

  TBotChannelArray = class(TObject)
  private

  public
    constructor Create;
    destructor Destroy; override;
  public

  end;

  { TBotUser }

  TBotUser = class(TObject)
  private

  public
    constructor Create;
  public

  end;

  { TBotUserArray }

  TBotUserArray = class(TObject)
  private

  public
    constructor Create;
    destructor Destroy; override;
  public

  end;

procedure ProcessSleep(const Milliseconds: Cardinal);
function ParseMessage(const Data: string): TBotMessage;

implementation

procedure ProcessSleep(const Milliseconds: Cardinal);
var
  n: Cardinal;
begin
  n := Windows.GetTickCount;
  while (Windows.GetTickCount - n) < Milliseconds do
    Application.ProcessMessages;
end;

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

{ TBotClientThread }

constructor TBotClientThread.Create(CreateSuspended: Boolean);
begin
  inherited;
  FreeOnTerminate := True;
end;

procedure TBotClientThread.Execute;
var
  Buf: Char;
const
  TERMINATOR: string = #13#10;
begin
  try
    FClient := TTcpClient.Create(nil);
    FClient.OnError := ClientError;
    FClient.OnSend := ClientSend;
    try
      FClient.RemoteHost := FClient.LookupHostAddr(FServer.RemoteHost);
      FClient.RemotePort := FServer.RemotePort;
      if FClient.Connect = False then
      begin
        FBuffer := '<< CONNECTION ERROR >>';
        Synchronize(Update);
        Exit;
      end;
      FBuffer := '<< CONNECTED >>';
      Synchronize(Update);
      Send('NICK ' + FServer.NickName);
      Send('USER ' + FServer.UserName + ' ' + FServer.HostName + ' ' + FServer.ServerName + ' :' + FServer.FullName);
      FBuffer := '';
      while (Application.Terminated = False) and (Self.Terminated = False) and (FClient.Connected = True) do
      begin
        Buf := #0;
        FClient.ReceiveBuf(Buf, 1);
        if Buf <> #0 then
        begin
          FBuffer := FBuffer + Buf;
          if Copy(FBuffer, Length(FBuffer) - Length(TERMINATOR) + 1, Length(TERMINATOR)) = TERMINATOR then
          begin
            FBuffer := Copy(FBuffer, 1, Length(FBuffer) - Length(TERMINATOR));
            Synchronize(Update);
            FBuffer := '';
          end;
        end
        else
        begin
          if FBuffer <> '' then
          begin
            Synchronize(Update);
            FBuffer := '';
          end;
        end;
      end;
      FBuffer := '<< DISCONNECTED >>';
      Synchronize(Update);
    finally
      FClient.Free;
    end;
  except
    FBuffer := '<< EXCEPTION ERROR >>';
    Synchronize(Update);
  end;
end;

procedure TBotClientThread.Send(const Msg: string);
begin
  if Assigned(FClient) then
    if FClient.Connected then
      FClient.Sendln(Msg);
end;

procedure TBotClientThread.Update;
begin
  if Assigned(FServer.Handler) then
    FServer.Handler(FServer, ParseMessage(FBuffer), FBuffer);
end;

procedure TBotClientThread.ClientError(Sender: TObject; SocketError: Integer);
begin
  //
end;

procedure TBotClientThread.ClientSend(Sender: TObject; Buf: PAnsiChar; var DataLen: Integer);
begin
  //
end;

{ TBotServer }

procedure TBotServer.Connect(const RemoteHost, RemotePort, NickName, UserName, FullName, HostName, ServerName: string);
begin
  FRemoteHost := RemoteHost;
  FRemotePort := RemotePort;
  FNickName := NickName;
  FUserName := UserName;
  FFullName := FullName;
  FHostName := HostName;
  FServerName := ServerName;
  FThread.Resume;
end;

constructor TBotServer.Create(const Handler: TBotReceiveEvent);
begin
  FHandler := Handler;
  FThread := TBotClientThread.Create(True);
  FThread.Server := Self;
end;

destructor TBotServer.Destroy;
begin
  //
  inherited;
end;

procedure TBotServer.Send(const Msg: string; const Obfuscate: Boolean = False);
begin
  FThread.Send(Msg);
  if Obfuscate = False then
    if Assigned(FHandler) then
      FHandler(Self, ParseMessage(Msg), Msg);
end;

{ TBotServerArray }

function TBotServerArray.Add: TBotServer;
begin
  Result := TBotServer.Create(FGlobalHandler);
  FServers.Add(Result);
end;

constructor TBotServerArray.Create(const GlobalHandler: TBotReceiveEvent);
begin
  FGlobalHandler := GlobalHandler;
  FServers := Classes.TList.Create;
end;

destructor TBotServerArray.Destroy;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Servers[i].Free;
  FServers.Free;
  inherited;
end;

function TBotServerArray.GetCount: Integer;
begin
  Result := FServers.Count;
end;

function TBotServerArray.GetServer(const Index: Integer): TBotServer;
begin
  if (Index >= 0) and (Index < Count) then
    Result := FServers[Index]
  else
    Result := nil;
end;

{ TBotChannel }

constructor TBotChannel.Create;
begin

end;

destructor TBotChannel.Destroy;
begin

  inherited;
end;

{ TBotChannelArray }

constructor TBotChannelArray.Create;
begin

end;

destructor TBotChannelArray.Destroy;
begin

  inherited;
end;

{ TBotUser }

constructor TBotUser.Create;
begin

end;

{ TBotUserArray }

constructor TBotUserArray.Create;
begin

end;

destructor TBotUserArray.Destroy;
begin

  inherited;
end;

end.
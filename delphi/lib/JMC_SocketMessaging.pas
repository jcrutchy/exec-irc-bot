unit JMC_SocketMessaging;

interface

uses
  Classes,
  SysUtils,
  Sockets,
  JMC_Strings,
  Dialogs,
  Windows,
  Controls,
  StdCtrls,
  ExtCtrls,
  Forms;

type

  TSM_ClientThread = class;
  TSM_Message = class;
  TSM_MessageArray = class;
  TSM_Messenger = class;

  TSM_MessageReceivedEvent = procedure (const Sender: TSM_Messenger; const Message: TSM_Message; var Remove: Boolean) of object;

  TSM_ClientThread = class(TThread)
  private
    FBuffer: TSM_Message;
    FMessenger: TSM_Messenger;
  public
    constructor Create(CreateSuspended: Boolean);
    procedure Update;
    procedure Execute; override;
  public
    property Buffer: TSM_Message read FBuffer;
    property Messenger: TSM_Messenger read FMessenger write FMessenger;
  end;

  TSM_Message = class(TObject)
  private
    FMessage: TStringArray;
    FRemoteAddress: string;
  public
    constructor Create;
    destructor Destroy; override;
  public
    property Message: TStringArray read FMessage;
    property RemoteAddress: string read FRemoteAddress write FRemoteAddress;
  end;

  TSM_MessageArray = class(TObject)
  private
    FMessages: TList;
  private
    function GetMessage(const Index: Integer): TSM_Message;
    function GetCount: Integer;
  public
    constructor Create;
    destructor Destroy; override;
  public
    procedure Add(const Message: TSM_Message);
    procedure Delete(const Message: TSM_Message);
  public
    property Messages[const Index: Integer]: TSM_Message read GetMessage; default;
    property Count: Integer read GetCount;
  end;

  TSM_Messenger = class(TObject)
  private
    FClient: TTcpClient;
    FMessages: TSM_MessageArray;
    FServer: TTcpServer;
  private
    FOnClientConnect: TNotifyEvent;
    FOnClientDisconnect: TNotifyEvent;
    FOnClientError: TNotifyEvent;
    FOnMessageReceived: TSM_MessageReceivedEvent;
  private
    function GetListening: Boolean;
    function GetListeningPort: string;
    function GetLocalAddress: string;
    function GetLocalName: string;
    function GetSendingPort: string;
    procedure SetListeningPort(const Value: string);
    procedure SetSendingPort(const Value: string);
  private
    procedure ServerAccept(Sender: TObject; ClientSocket: TCustomIpClient);
  private
    procedure ClientConnect(Sender: TObject);
    procedure ClientDisconnect(Sender: TObject);
    procedure ClientError(Sender: TObject; SocketError: Integer);
    procedure MessageReceived(const Message: TSM_Message; var Remove: Boolean);
  public
    constructor Create;
    destructor Destroy; override;
  public
    function AddressToName(const Address: string): string;
    procedure CloseServer;
    function NameToAddress(const Name: string): string;
    procedure OpenServer;
    function SendMessage(const Message, Address: string): Boolean;
  public
    property Client: TTcpClient read FClient;
    property Listening: Boolean read GetListening;
    property ListeningPort: string read GetListeningPort write SetListeningPort;
    property LocalAddress: string read GetLocalAddress;
    property LocalName: string read GetLocalName;
    property Messages: TSM_MessageArray read FMessages;
    property SendingPort: string read GetSendingPort write SetSendingPort;
    property Server: TTcpServer read FServer;
  public
    property OnClientConnect: TNotifyEvent read FOnClientConnect write FOnClientConnect;
    property OnClientDisconnect: TNotifyEvent read FOnClientDisconnect write FOnClientDisconnect;
    property OnClientError: TNotifyEvent read FOnClientError write FOnClientError;
    property OnMessageReceived: TSM_MessageReceivedEvent read FOnMessageReceived write FOnMessageReceived;
  end;

implementation

{ TAR_ClientThread }

constructor TSM_ClientThread.Create(CreateSuspended: Boolean);
begin
  inherited;
  FreeOnTerminate := True;
  FBuffer := TSM_Message.Create;
end;

procedure TSM_ClientThread.Execute;
begin
  Synchronize(Update);
end;

procedure TSM_ClientThread.Update;
var
  Remove: Boolean;
begin
  Remove := True;
  FMessenger.Messages.Add(FBuffer);
  FMessenger.MessageReceived(FBuffer, Remove);
  if Remove then
    FMessenger.Messages.Delete(FBuffer);
end;

{ TSM_Message }

constructor TSM_Message.Create;
begin
  FMessage := TStringArray.Create;
end;

destructor TSM_Message.Destroy;
begin
  FMessage.Free;
  inherited;
end;

{ TSM_MessageArray }

procedure TSM_MessageArray.Add(const Message: TSM_Message);
begin
  FMessages.Add(Message);
end;

constructor TSM_MessageArray.Create;
begin
  FMessages := TList.Create;
end;

procedure TSM_MessageArray.Delete(const Message: TSM_Message);
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    if Messages[i] = Message then
    begin
      FMessages.Delete(i);
      Message.Free;
    end;
end;

destructor TSM_MessageArray.Destroy;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Messages[i].Free;
  FMessages.Free;
  inherited;
end;

function TSM_MessageArray.GetCount: Integer;
begin
  Result := FMessages.Count;
end;

function TSM_MessageArray.GetMessage(const Index: Integer): TSM_Message;
begin
  Result := FMessages[Index];
end;

{ TSM_Messenger }

function TSM_Messenger.AddressToName(const Address: string): string;
begin
  Result := FClient.LookupHostName(Address);
end;

procedure TSM_Messenger.ClientConnect(Sender: TObject);
begin
  if Assigned(FOnClientConnect) then
  begin
    FOnClientConnect(Self);
    Application.ProcessMessages;
  end;
end;

procedure TSM_Messenger.ClientDisconnect(Sender: TObject);
begin
  if Assigned(FOnClientDisconnect) then
  begin
    FOnClientDisconnect(Self);
    Application.ProcessMessages;
  end;
end;

procedure TSM_Messenger.ClientError(Sender: TObject; SocketError: Integer);
begin
  if Assigned(FOnClientError) then
  begin
    FOnClientError(Self);
    Application.ProcessMessages;
  end;
end;

procedure TSM_Messenger.CloseServer;
begin
  if FServer.Active then
    FServer.Close;
end;

constructor TSM_Messenger.Create;
begin
  FMessages := TSM_MessageArray.Create;
  FServer := TTcpServer.Create(nil);
  FServer.OnAccept := ServerAccept;
  FClient := TTcpClient.Create(nil);
  FClient.OnConnect := ClientConnect;
  FClient.OnDisconnect := ClientDisconnect;
  FClient.OnError := ClientError;
end;

destructor TSM_Messenger.Destroy;
begin
  FMessages.Free;
  FClient.Free;
  FServer.Free;
  inherited;
end;

function TSM_Messenger.GetListening: Boolean;
begin
  Result := FServer.Active;
end;

function TSM_Messenger.GetListeningPort: string;
begin
  Result := FServer.LocalPort;
end;

function TSM_Messenger.GetLocalAddress: string;
begin
  Result := FClient.LocalHostAddr;
end;

function TSM_Messenger.GetLocalName: string;
begin
  Result := FClient.LocalHostName;
end;

function TSM_Messenger.GetSendingPort: string;
begin
  Result := FClient.RemotePort;
end;

procedure TSM_Messenger.MessageReceived(const Message: TSM_Message; var Remove: Boolean);
begin
  if Assigned(FOnMessageReceived) then
  begin
    FOnMessageReceived(Self, Message, Remove);
    Application.ProcessMessages;
  end;
end;

function TSM_Messenger.NameToAddress(const Name: string): string;
begin
  Result := FClient.LookupHostAddr(Name);
end;

procedure TSM_Messenger.OpenServer;
begin
  FServer.Open;
end;

function TSM_Messenger.SendMessage(const Message, Address: string): Boolean;
begin
  FClient.RemoteHost := Address;
  try
    Result := FClient.Connect;
    if Result then
      FClient.Sendln(Message, #0);
  finally
    FClient.Close;
  end;
end;

procedure TSM_Messenger.ServerAccept(Sender: TObject; ClientSocket: TCustomIpClient);
var
  ClientThread: TSM_ClientThread;
begin
  ClientThread := TSM_ClientThread.Create(True);
  ClientThread.Buffer.RemoteAddress := ClientSocket.RemoteHost;
  ClientThread.Buffer.Message.Text := ClientSocket.Receiveln(#0);
  ClientThread.Messenger := Self;
  ClientThread.Resume;
end;

procedure TSM_Messenger.SetListeningPort(const Value: string);
begin
  FServer.LocalPort := Value;
end;

procedure TSM_Messenger.SetSendingPort(const Value: string);
begin
  FClient.RemotePort := Value;
end;

end.
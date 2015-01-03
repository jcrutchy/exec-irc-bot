unit JMC_Messaging;

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

  TJMC_Message = class;
  TJMC_MessageArray = class;
  TJMC_Messenger = class;

  TJMC_MessageReceivedEvent = procedure (const Sender: TJMC_Messenger; const Message: TJMC_Message; var Remove: Boolean) of object;

  TJMC_Message = class(TObject)
  private
    FMessage: TStringArray;
  private
    FRecipientAddress: string;
    FRecipientListeningPort: string;
    FSenderAddress: string;
    FSenderListeningPort: string;
  public
    constructor Create;
    destructor Destroy; override;
  public
    property Message: TStringArray read FMessage;
  public
    property RecipientAddress: string read FRecipientAddress write FRecipientAddress;
    property RecipientListeningPort: string read FRecipientListeningPort write FRecipientListeningPort;
    property SenderAddress: string read FSenderAddress write FSenderAddress;
    property SenderListeningPort: string read FSenderListeningPort write FSenderListeningPort;
  end;

  TJMC_MessageArray = class(TObject)
  private
    FMessages: TList;
  private
    function GetMessage(const Index: Integer): TJMC_Message;
    function GetCount: Integer;
  public
    constructor Create;
    destructor Destroy; override;
  public
    function Add: TJMC_Message;
    procedure Delete(const Message: TJMC_Message);
  public
    property Messages[const Index: Integer]: TJMC_Message read GetMessage; default;
    property Count: Integer read GetCount;
  end;

  TJMC_Messenger = class(TObject)
  private
    FClient: TTcpClient;
    FMessages: TJMC_MessageArray;
    FServer: TTcpServer;
  private
    FOnClientConnect: TNotifyEvent;
    FOnClientDisconnect: TNotifyEvent;
    FOnClientError: TNotifyEvent;
    FOnMessageReceived: TJMC_MessageReceivedEvent;
  private
    function GetDefaultSendingPort: string;
    function GetListening: Boolean;
    function GetListeningPort: string;
    function GetLocalAddress: string;
    function GetLocalName: string;
    procedure SetDefaultSendingPort(const Value: string);
    procedure SetListeningPort(const Value: string);
  private
    procedure ServerAccept(Sender: TObject; ClientSocket: TCustomIpClient);
  private
    procedure ClientConnect(Sender: TObject);
    procedure ClientDisconnect(Sender: TObject);
    procedure ClientError(Sender: TObject; SocketError: Integer);
    procedure MessageReceived(const Message: TJMC_Message; var Remove: Boolean);
  public
    constructor Create;
    destructor Destroy; override;
  public
    function AddressToName(const Address: string): string;
    procedure CloseServer;
    function NameToAddress(const Name: string): string;
    procedure OpenServer;
    function SendMessage(const Message, Address: string; const Port: string = ''): Boolean;
  public
    property Client: TTcpClient read FClient;
    property DefaultSendingPort: string read GetDefaultSendingPort write SetDefaultSendingPort;
    property Listening: Boolean read GetListening;
    property ListeningPort: string read GetListeningPort write SetListeningPort;
    property LocalAddress: string read GetLocalAddress;
    property LocalName: string read GetLocalName;
    property Messages: TJMC_MessageArray read FMessages;
    property Server: TTcpServer read FServer;
  public
    property OnClientConnect: TNotifyEvent read FOnClientConnect write FOnClientConnect;
    property OnClientDisconnect: TNotifyEvent read FOnClientDisconnect write FOnClientDisconnect;
    property OnClientError: TNotifyEvent read FOnClientError write FOnClientError;
    property OnMessageReceived: TJMC_MessageReceivedEvent read FOnMessageReceived write FOnMessageReceived;
  end;

implementation

{ dynamic/private ports are 49152 through 65535 }

{ TJMC_Message }

constructor TJMC_Message.Create;
begin
  FMessage := TStringArray.Create;
end;

destructor TJMC_Message.Destroy;
begin
  FMessage.Free;
  inherited;
end;

{ TJMC_MessageArray }

function TJMC_MessageArray.Add: TJMC_Message;
begin
  Result := TJMC_Message.Create;
  FMessages.Add(Result);
end;

constructor TJMC_MessageArray.Create;
begin
  FMessages := TList.Create;
end;

procedure TJMC_MessageArray.Delete(const Message: TJMC_Message);
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

destructor TJMC_MessageArray.Destroy;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Messages[i].Free;
  FMessages.Free;
  inherited;
end;

function TJMC_MessageArray.GetCount: Integer;
begin
  Result := FMessages.Count;
end;

function TJMC_MessageArray.GetMessage(const Index: Integer): TJMC_Message;
begin
  Result := FMessages[Index];
end;

{ TJMC_Messenger }

function TJMC_Messenger.AddressToName(const Address: string): string;
begin
  Result := FClient.LookupHostName(Address);
end;

procedure TJMC_Messenger.ClientConnect(Sender: TObject);
begin
  if Assigned(FOnClientConnect) then
  begin
    FOnClientConnect(Self);
    Application.ProcessMessages;
  end;
end;

procedure TJMC_Messenger.ClientDisconnect(Sender: TObject);
begin
  if Assigned(FOnClientDisconnect) then
  begin
    FOnClientDisconnect(Self);
    Application.ProcessMessages;
  end;
end;

procedure TJMC_Messenger.ClientError(Sender: TObject; SocketError: Integer);
begin
  if Assigned(FOnClientError) then
  begin
    FOnClientError(Self);
    Application.ProcessMessages;
  end;
end;

procedure TJMC_Messenger.CloseServer;
begin
  if FServer.Active then
    FServer.Close;
end;

constructor TJMC_Messenger.Create;
begin
  FMessages := TJMC_MessageArray.Create;
  FServer := TTcpServer.Create(nil);
  FServer.OnAccept := ServerAccept;
  FClient := TTcpClient.Create(nil);
  FClient.OnConnect := ClientConnect;
  FClient.OnDisconnect := ClientDisconnect;
  FClient.OnError := ClientError;
end;

destructor TJMC_Messenger.Destroy;
begin
  FMessages.Free;
  FClient.Free;
  FServer.Free;
  inherited;
end;

function TJMC_Messenger.GetDefaultSendingPort: string;
begin
  Result := FClient.RemotePort;
end;

function TJMC_Messenger.GetListening: Boolean;
begin
  Result := FServer.Active;
end;

function TJMC_Messenger.GetListeningPort: string;
begin
  Result := FServer.LocalPort;
end;

function TJMC_Messenger.GetLocalAddress: string;
begin
  Result := FClient.LocalHostAddr;
end;

function TJMC_Messenger.GetLocalName: string;
begin
  Result := FClient.LocalHostName;
end;

procedure TJMC_Messenger.MessageReceived(const Message: TJMC_Message; var Remove: Boolean);
begin
  if Assigned(FOnMessageReceived) then
  begin
    FOnMessageReceived(Self, Message, Remove);
    Application.ProcessMessages;
  end;
end;

function TJMC_Messenger.NameToAddress(const Name: string): string;
begin
  Result := FClient.LookupHostAddr(Name);
end;

procedure TJMC_Messenger.OpenServer;
begin
  FServer.Open;
end;

function TJMC_Messenger.SendMessage(const Message, Address: string; const Port: string = ''): Boolean;
var
  DefaultPort: string;
begin
  FClient.RemoteHost := Address;
  DefaultPort := FClient.RemotePort;
  if Port <> '' then
    FClient.RemotePort := Port;
  try
    Result := FClient.Connect;
    if Result then
      FClient.Sendln(FServer.LocalPort + #13#10 + Message, #0#0#0);
  finally
    FClient.Close;
  end;
  FClient.RemotePort := DefaultPort;
end;

procedure TJMC_Messenger.ServerAccept(Sender: TObject; ClientSocket: TCustomIpClient);
var
  Remove: Boolean;
  Buffer: TJMC_Message;
begin
  Remove := True;
  Buffer := FMessages.Add;
  Buffer.SenderAddress := ClientSocket.RemoteHost;
  Buffer.RecipientAddress := ClientSocket.LocalHostAddr;
  Buffer.RecipientListeningPort := FServer.LocalPort;
  Buffer.Message.Text := ClientSocket.Receiveln(#0#0#0);
  Buffer.SenderListeningPort := Buffer.Message[0];
  Buffer.Message.Delete(0);
  MessageReceived(Buffer, Remove);
  if Remove then
    FMessages.Delete(Buffer);
end;

procedure TJMC_Messenger.SetDefaultSendingPort(const Value: string);
begin
  FClient.RemotePort := Value;
end;

procedure TJMC_Messenger.SetListeningPort(const Value: string);
begin
  FServer.LocalPort := Value;
end;

end.
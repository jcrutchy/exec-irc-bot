unit JMC_HTTP_NonBlocking;

interface

uses
  Classes,
  Controls,
  StdCtrls,
  SysUtils,
  ExtCtrls,
  Forms,
  Dialogs,
  Sockets,
  WinInet,
  Windows,
  ScktComp,
  ComCtrls,
  JMC_Parts;

type

  TJMC_HTTP_Request = class;

  TJMC_HTTP_RequestType = (JMC_HTTP_rtGet, JMC_HTTP_rtPost, JMC_HTTP_rtMultipart);

  TJMC_HTTP_Parameter = class(TObject)
  private
    FKey: string;
    FValue: string;
    FRequest: TJMC_HTTP_Request;
  public
    constructor Create(const Request: TJMC_HTTP_Request);
  public
    function AsFormatted: string;
    procedure SetParameter(const Key: string; const Value: string);
  public
    property Key: string read FKey write FKey;
    property Value: string read FValue write FValue;
  end;

  TJMC_HTTP_ParameterArray = class(TObject)
  private
    FParameters: TList;
    FRequest: TJMC_HTTP_Request;
  private
    function GetCount: Integer;
    function GetParameter(const Index: Integer): TJMC_HTTP_Parameter;
  public
    constructor Create(const Request: TJMC_HTTP_Request);
    destructor Destroy; override;
  public
    function Add: TJMC_HTTP_Parameter;
    procedure Clear;
  public
    property Count: Integer read GetCount;
    property Parameters[const Index: Integer]: TJMC_HTTP_Parameter read GetParameter; default;
  end;

  TJMC_HTTP_Request = class(TObject)
  private
    FHost: string;
    FPort: Integer;
    FRequest: string;
    FUserAgent: string;
  private
    FReadCount: Integer;
    FReadStatus: string;
  private
    FConnected: Boolean;
  private
    FRequestType: TJMC_HTTP_RequestType;
    FMultipartBoundary: string;
  private
    FResponseContent: string;
    FResponseHeaders: string;
  private
    FPingStart: Cardinal;
    FPingEnd: Cardinal;
  private
    FOnConnect: TNotifyEvent;
    FOnConnecting: TNotifyEvent;
    FOnDisconnect: TNotifyEvent;
    FOnError: TNotifyEvent;
  private
    FLabelDownloadSize: TLabel;
    FLabelPingTime: TLabel;
    FLabelPingTimerInterval: TLabel;
    FLabelStatus: TLabel;
    FMemoReadStatus: TMemo;
    FMemoRequest: TMemo;
    FMemoResponseContent: TMemo;
    FMemoResponseHeaders: TMemo;
    FPanelProgressParent: TPanel;
    FShapeProgressBar: TShape;
    FTimerPing: TTimer;
  private
    FParameters: TJMC_HTTP_ParameterArray;
  private
    FClientSocket: TClientSocket;
  private
    procedure DoError;
  private
    procedure ClientSocketRead(Sender: TObject; Socket: TCustomWinSocket);
    procedure ClientSocketError(Sender: TObject; Socket: TCustomWinSocket; ErrorEvent: TErrorEvent; var ErrorCode: Integer);
    procedure ClientSocketConnect(Sender: TObject; Socket: TCustomWinSocket);
    procedure ClientSocketConnecting(Sender: TObject; Socket: TCustomWinSocket);
    procedure ClientSocketDisconnect(Sender: TObject; Socket: TCustomWinSocket);
  public
    constructor Create;
    destructor Destroy; override;
  public
    procedure AddParameter(const Key: string; const Value: string);
    procedure Clear;
  public
    procedure SendRequest(const ServerAddress: string);
  public
    property OnConnect: TNotifyEvent read FOnConnect write FOnConnect;
    property OnConnecting: TNotifyEvent read FOnConnecting write FOnConnecting;
    property OnDisconnect: TNotifyEvent read FOnDisconnect write FOnDisconnect;
    property OnError: TNotifyEvent read FOnError write FOnError;
  public
    property ClientSocket: TClientSocket read FClientSocket;
  public
    property Connected: Boolean read FConnected;
  public
    property RequestType: TJMC_HTTP_RequestType read FRequestType write FRequestType;
    property MultipartBoundary: string read FMultipartBoundary write FMultipartBoundary;
    property UserAgent: string read FUserAgent write FUserAgent;
  public
    property LabelDownloadSize: TLabel read FLabelDownloadSize write FLabelDownloadSize;
    property LabelPingTime: TLabel read FLabelPingTime write FLabelPingTime;
    property LabelPingTimerInterval: TLabel read FLabelPingTimerInterval write FLabelPingTimerInterval;
    property LabelStatus: TLabel read FLabelStatus write FLabelStatus;
    property MemoReadStatus: TMemo read FMemoReadStatus write FMemoReadStatus;
    property MemoRequest: TMemo read FMemoRequest write FMemoRequest;
    property MemoResponseContent: TMemo read FMemoResponseContent write FMemoResponseContent;
    property MemoResponseHeaders: TMemo read FMemoResponseHeaders write FMemoResponseHeaders;
    property PanelProgressParent: TPanel read FPanelProgressParent write FPanelProgressParent;
    property ShapeProgressBar: TShape read FShapeProgressBar write FShapeProgressBar;
    property TimerPing: TTimer read FTimerPing write FTimerPing;
  end;

procedure ParseAddress(const Address: string; var Port: Integer; var Host: string; var Request: string);

implementation

function IsNumbersOnly(const Value: string): Boolean;
var
  i: Integer;
begin
  if Length(Value) = 0 then
  begin
    Result := False;
    Exit;
  end;
  Result := True;
  for i := 1 to Length(Value) do
    case Ord(Value[i]) of
      48..57: Continue;
    else
      Result := False;
      Exit;
    end;
end;

procedure ParseAddress(const Address: string; var Port: Integer; var Host: string; var Request: string);
var
  i: Integer;
  j: Integer;
  S: string;
  p1: string;
  p2: string;
begin
  S := Address;
  i := Pos('//', S);
  p1 := '';
  if i > 0 then
  begin
    p1 := LowerCase(Copy(S, 1, i - 1));
    p2 := '';
    for j := 1 to Length(p1) do
      case p1[j] of
        'a'..'z': p2 := p2 + p1[j];
      else
        Break;
      end;
    if p2 = 'http' then
      p1 := '80'
    else
      if p2 = 'https' then
        p1 := '443'
      else
        p1 := '';
    S := Copy(S, i + 2, Length(S) - i - 1);
  end;
  i := Pos('/', S);
  if i <= 0 then
  begin
    S := S + '/';
    i := Length(S);
  end;
  Host := Copy(S, 1, i - 1);
  Request := Copy(S, i, Length(S) - i + 1);
  i := Pos(':', Request);
  if i > 0 then
  begin
    p1 := Copy(Request, i + 1, Length(Request) - i);
    if IsNumbersOnly(p1) then
      Request := Copy(Request, 1, i - 1)
    else
      p1 := '';
  end;
  if Length(p1) = 0 then
    p1 := '80';
  try
    Port := StrToInt(p1);
  except
    Port := 80;
  end;
end;

function HexToInt(const HexNum: string): Integer;
begin
  Result := StrToInt('$' + HexNum);
end;

function EncodeParameter(const Key: string; const Value: string; const Request: TJMC_HTTP_Request): string;
var
  i: Integer;
  S: Classes.TStrings;
begin
  if Request.RequestType = JMC_HTTP_rtMultipart then
  begin
    Result := '--' + Request.MultipartBoundary + #13#10;
    if SysUtils.FileExists(Value) = False then
      Result := Result + 'Content-Disposition: form-data; name="nonfile_field"' + #13#10#13#10 + Value
    else
    begin
      S := Classes.TStringList.Create;
      try
        S.LoadFromFile(Value);
      except
      end;
      Result := Result + 'Content-Disposition: form-data; name="' + Key + '"; filename="' + SysUtils.ExtractFileName(Value) + '"' + #13#10 + 'Content-Type: text/plain' + #13#10#13#10 + S.Text;
      S.Free;
    end;
  end
  else
  begin
    Result := Key + '=';
    for i := 1 to Length(Value) do
      Result := Result + '%' + IntToHex(Ord(Value[i]), 2);
  end;
end;

function DecodeParameter(const Key: string; const Value: string; const Request: TJMC_HTTP_Request): string;
var
  i: Integer;
  S: string;
begin
  if Request.RequestType = JMC_HTTP_rtMultipart then
  begin
    Result := '';
  end
  else
  begin
    i := Length(Key + '=') + 1;
    Result := '';
    repeat
      S := Copy(Value, i + 1, 2);
      Result := Result + Chr(HexToInt(S));
      Inc(i, 3);
    until i >= Length(Value);
  end;
end;

{ TJMC_HTTP_Parameter }

function TJMC_HTTP_Parameter.AsFormatted: string;
begin
  Result := EncodeParameter(FKey, FValue, FRequest);
end;

constructor TJMC_HTTP_Parameter.Create(const Request: TJMC_HTTP_Request);
begin
  FRequest := Request;
end;

procedure TJMC_HTTP_Parameter.SetParameter(const Key, Value: string);
begin
  FKey := Key;
  FValue := Value;
end;

{ TJMC_HTTP_ParameterArray }

function TJMC_HTTP_ParameterArray.Add: TJMC_HTTP_Parameter;
begin
  Result := TJMC_HTTP_Parameter.Create(FRequest);
  FParameters.Add(Result);
end;

procedure TJMC_HTTP_ParameterArray.Clear;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Parameters[i].Free;
  FParameters.Clear;
end;

constructor TJMC_HTTP_ParameterArray.Create(const Request: TJMC_HTTP_Request);
begin
  FRequest := Request;
  FParameters := TList.Create;
end;

destructor TJMC_HTTP_ParameterArray.Destroy;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Parameters[i].Free;
  FParameters.Free;
  inherited;
end;

function TJMC_HTTP_ParameterArray.GetCount: Integer;
begin
  Result := FParameters.Count;
end;

function TJMC_HTTP_ParameterArray.GetParameter(const Index: Integer): TJMC_HTTP_Parameter;
begin
  Result := FParameters[Index];
end;

{ TJMC_HTTP_Request }

procedure TJMC_HTTP_Request.AddParameter(const Key, Value: string);
begin
  FParameters.Add.SetParameter(Key, Value);
end;

procedure TJMC_HTTP_Request.Clear;
begin
  FHost := '';
  FPort := 0;
  FRequest := '';
  FReadCount := 0;
  FReadStatus := '';
  FResponseContent := '';
  FResponseHeaders := '';
  FPingStart := 0;
  FPingEnd := 0;
  FParameters.Clear;
end;

procedure TJMC_HTTP_Request.ClientSocketConnect(Sender: TObject; Socket: TCustomWinSocket);
var
  RequestContent: string;
  UserAgentHeader: string;
  S: string;
  i: Integer;
begin
  FConnected := True;
  if Assigned(FMemoResponseContent) then
    FMemoResponseContent.Clear;
  if Assigned(FMemoResponseHeaders) then
    FMemoResponseHeaders.Clear;
  FReadCount := 0;
  FResponseContent := '';
  FReadStatus := '';
  if Assigned(FShapeProgressBar) and Assigned(FPanelProgressParent) then
  begin
    FShapeProgressBar.Align := alNone;
    FShapeProgressBar.Left := -FShapeProgressBar.Width;
  end;
  if Assigned(FLabelPingTime) then
    FLabelPingTime.Caption := 'Ping:';
  if Assigned(FLabelStatus) then
    FLabelStatus.Caption := 'SENDING REQUEST...';
  RequestContent := '';
  for i := 0 to FParameters.Count - 1 do
    if Length(RequestContent) > 0 then
    begin
      if FRequestType = JMC_HTTP_rtMultipart then
        RequestContent := RequestContent + #13#10 + FParameters[i].AsFormatted
      else
        RequestContent := RequestContent + '&' + FParameters[i].AsFormatted;
    end
    else
      RequestContent := FParameters[i].AsFormatted;
  // Use web-sniffer.net to develop request
  FUserAgent := Trim(FUserAgent);
  if Length(FUserAgent) > 0 then
    UserAgentHeader := 'User-Agent: ' + FUserAgent + #13#10
  else
    UserAgentHeader := '';
  S := '';
  case FRequestType of
    JMC_HTTP_rtGet:
      if Length(RequestContent) > 0 then
        S := 'GET ' + FRequest + '?' + RequestContent + ' HTTP/1.1' + #13#10 + 'Host: ' + FHost + #13#10 + UserAgentHeader + 'Connection: close' + #13#10#13#10
      else
        S := 'GET ' + FRequest + ' HTTP/1.1' + #13#10 + 'Host: ' + FHost + #13#10 + UserAgentHeader + 'Connection: close' + #13#10#13#10;
    JMC_HTTP_rtPost:
      S := 'POST ' + FRequest + ' HTTP/1.1' + #13#10 + 'Host: ' + FHost + #13#10 + UserAgentHeader + 'Content-Type: application/x-www-form-urlencoded' + #13#10 + 'Content-Length: ' + IntToStr(Length(RequestContent)) + #13#10 + 'Connection: close' + #13#10 + 'Accept: text/*' + #13#10#13#10 + RequestContent;
    JMC_HTTP_rtMultipart:
      S := 'POST ' + FRequest + ' HTTP/1.1' + #13#10 + 'Host: ' + FHost + #13#10 + UserAgentHeader + 'Content-Type: multipart/form-data; boundary=' + FMultipartBoundary + #13#10 + 'Content-Length: ' + IntToStr(Length(RequestContent)) + #13#10 + 'Connection: close' + #13#10 + 'Accept: text/*' + #13#10#13#10 + RequestContent;
  end;
  if Assigned(FMemoRequest) then
    FMemoRequest.Lines.Text := S;
  FParameters.Clear;
  try
    Socket.SendText(S);
  except
    DoError;
  end;
  if Assigned(FOnConnect) then
    FOnConnect(Self);
end;

procedure TJMC_HTTP_Request.ClientSocketConnecting(Sender: TObject; Socket: TCustomWinSocket);
begin
  if Assigned(FLabelStatus) then
    FLabelStatus.Caption := 'SOCKET CONNECTING...';
  if Assigned(FOnConnecting) then
    FOnConnecting(Self);
end;

procedure TJMC_HTTP_Request.ClientSocketDisconnect(Sender: TObject; Socket: TCustomWinSocket);
var
  PingDelta: Cardinal;
begin
  if not Application.Terminated then
    ClientSocketRead(Sender, Socket);
  if Assigned(FMemoResponseContent) then
    FMemoResponseContent.Lines.Text := FResponseContent;
  if Assigned(FMemoReadStatus) then
    FMemoReadStatus.Lines.Text := FReadStatus;
  if Assigned(FMemoResponseHeaders) then
    FMemoResponseHeaders.Lines.Text := FResponseHeaders;
  FPingEnd := GetTickCount;
  PingDelta := FPingEnd - FPingStart;
  if Assigned(FLabelPingTime) then
    FLabelPingTime.Caption := 'Ping: ' + IntToStr(PingDelta) + ' milliseconds';
  if Assigned(FTimerPing) then
    if FTimerPing.Interval < (PingDelta + 500) then
    begin
      FTimerPing.Enabled := False;
      FTimerPing.Interval := PingDelta + 500;
      FTimerPing.Enabled := True;
    end;
  if Assigned(FLabelPingTimerInterval) and Assigned(FTimerPing) then
    FLabelPingTimerInterval.Caption := 'Timer Interval: ' + IntToStr(FTimerPing.Interval) + ' milliseconds';
  if Assigned(FShapeProgressBar) and Assigned(FPanelProgressParent) then
    FShapeProgressBar.Left := -FShapeProgressBar.Width;
  if Assigned(FLabelStatus) then
    if Length(FResponseHeaders) > 0 then
      FLabelStatus.Caption := 'SOCKET DISCONNECTED: RESPONSE RECEIVED'
    else
      FLabelStatus.Caption := 'SOCKET DISCONNECTED: NO RESPONSE';
  Application.ProcessMessages;
  FConnected := False;
  if Assigned(FOnDisconnect) then
    FOnDisconnect(Self);
end;

procedure TJMC_HTTP_Request.ClientSocketError(Sender: TObject; Socket: TCustomWinSocket; ErrorEvent: TErrorEvent; var ErrorCode: Integer);
begin
  ErrorCode := 0;
  DoError;
end;

procedure TJMC_HTTP_Request.ClientSocketRead(Sender: TObject; Socket: TCustomWinSocket);
var
  L: Integer;
  //Buf: array[0..1023] of Char;
  Buf: array[0..4096] of Char; // REQUIRED FOR CHUNKED ENCODING POST RESPONSES (OTHERWISE RESPONSE IS INCOMPLETE)
  n: Integer;
  i: Integer;
begin
  Application.ProcessMessages;
  if Application.Terminated then
  begin
    Socket.Close;
    Exit;
  end;
  FillChar(Buf, SizeOf(Buf), #0);
  L := SizeOf(Buf);
  n := Socket.ReceiveBuf(Buf, L);
  FResponseContent := SysUtils.TrimRight(FResponseContent + Buf);
  if Assigned(FLabelDownloadSize) then
    FLabelDownloadSize.Caption := 'Downloaded: ' + IntToStr(Length(FResponseContent)) + ' bytes (' + Format('%.1f', [Length(FResponseContent) / 1024]) + ' kb)';
  i := Pos(#13#10#13#10, FResponseContent);
  if (i > 0) and (Length(FResponseHeaders) = 0) then
  begin
    FResponseHeaders := Copy(FResponseContent, 1, i - 1);
    FResponseContent := Copy(FResponseContent, i + Length(#13#10#13#10), Length(FResponseContent) - i - Length(#13#10#13#10) + 1);
  end;
  FReadCount := FReadCount + 1;
  if Assigned(FLabelStatus) then
    FLabelStatus.Caption := 'SOCKET READING (COUNT: ' + SysUtils.IntToStr(FReadCount) + ')';
  if FReadStatus <> '' then
    FReadStatus := FReadStatus + #13#10;
  FReadStatus := FReadStatus + '#' + IntToStr(FReadCount) + ': ' + IntToStr(L) + '~' + IntToStr(n);
  if Assigned(FShapeProgressBar) and Assigned(FPanelProgressParent) then
  begin
    if FShapeProgressBar.Left >= FPanelProgressParent.ClientWidth then
      FShapeProgressBar.Left := -FShapeProgressBar.Width;
    FShapeProgressBar.Left := FShapeProgressBar.Left + 1;
  end;
end;

constructor TJMC_HTTP_Request.Create;
begin
  FConnected := False;
  FRequestType := JMC_HTTP_rtGet;
  FParameters := TJMC_HTTP_ParameterArray.Create(Self);
  FMultipartBoundary := '-------------------------------18788734234';
  FClientSocket := TClientSocket.Create(nil);
  with FClientSocket do
  begin
    ClientType := ctNonBlocking;
    OnConnect := ClientSocketConnect;
    OnConnecting := ClientSocketConnecting;
    OnDisconnect := ClientSocketDisconnect;
    OnRead := ClientSocketRead;
    OnError := ClientSocketError;
  end;
end;

destructor TJMC_HTTP_Request.Destroy;
begin
  FClientSocket.Free;
  FParameters.Free;
  inherited;
end;

procedure TJMC_HTTP_Request.DoError;
begin
  if Assigned(FTimerPing) then
    FTimerPing.Enabled := False;
  if Assigned(FLabelStatus) then
    FLabelStatus.Caption := 'SOCKET ERROR';
  Application.ProcessMessages;
  if Assigned(FOnError) then
    FOnError(Self);
  FConnected := False;
end;

procedure TJMC_HTTP_Request.SendRequest(const ServerAddress: string);
begin
  FPingStart := GetTickCount;
  if Assigned(FMemoReadStatus) then
    FMemoReadStatus.Clear;
  if Assigned(FMemoRequest) then
    FMemoRequest.Clear;
  if Assigned(FMemoResponseContent) then
    FMemoResponseContent.Clear;
  if Assigned(FMemoResponseHeaders) then
    FMemoResponseHeaders.Clear;
  FResponseContent := '';
  FResponseHeaders := '';
  FReadStatus := '';
  ParseAddress(ServerAddress, FPort, FHost, FRequest);
  FClientSocket.Port := FPort;
  FClientSocket.Host := FHost;
  FClientSocket.Open;
end;

end.
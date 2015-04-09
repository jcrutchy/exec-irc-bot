unit Main;

interface

uses
  Windows,
  SysUtils,
  Classes,
  Graphics,
  Controls,
  Forms,
  Dialogs,
  Sockets,
  StdCtrls,
  DateUtils,
  Data,
  ComCtrls,
  Grids,
  ExtCtrls,
  ScktComp;

type

  TClientThread = class;

  TFormMain = class(TForm)
    PageControl1: TPageControl;
    TabSheet1: TTabSheet;
    TabSheet2: TTabSheet;
    Panel1: TPanel;
    Label1: TLabel;
    Label2: TLabel;
    Label3: TLabel;
    Label4: TLabel;
    Label5: TLabel;
    Button1: TButton;
    ListBox1: TListBox;
    TreeView1: TTreeView;
    Splitter1: TSplitter;
    TabSheet3: TTabSheet;
    TabSheet4: TTabSheet;
    TabSheet5: TTabSheet;
    ListBox2: TListBox;
    Splitter2: TSplitter;
    Panel2: TPanel;
    Button2: TButton;
    LabeledEditAliasesTrailing: TLabeledEdit;
    LabeledEditAliasesDest: TLabeledEdit;
    TabSheet6: TTabSheet;
    StatusBar1: TStatusBar;
    Timer1: TTimer;
    ProgressBar1: TProgressBar;
    Memo1: TMemo;
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
    procedure Timer1Timer(Sender: TObject);
    procedure Button2Click(Sender: TObject);
  private
    FThread: TClientThread;
    FMessages: TExecMessages;
    FMaxTraffic: Integer;
    FTraffic: Integer;
    FTrafficPercent: Integer;
    FTrafficCount: Integer;
    procedure ThreadHandler(const S: string);
  end;

  TClientThread = class(TThread)
  private
    FClient: TTcpClient;
    FBuffer: string;
    FHandler: TGetStrProc;
  private
    procedure ClientError(Sender: TObject; SocketError: Integer);
    procedure ClientSend(Sender: TObject; Buf: PAnsiChar; var DataLen: Integer);
  public
    constructor Create(CreateSuspended: Boolean);
    procedure Update;
    procedure Send(const Msg: string);
    procedure Execute; override;
  public
    property Handler: TGetStrProc read FHandler write FHandler;
  end;

var
  FormMain: TFormMain;

implementation

{$R *.dfm}

{ TClientThread }

constructor TClientThread.Create(CreateSuspended: Boolean);
begin
  inherited;
  FreeOnTerminate := True;
end;

procedure TClientThread.Execute;
begin
  try
    FClient := TTcpClient.Create(nil);
    FClient.OnError := ClientError;
    FClient.OnSend := ClientSend;
    try
      FClient.RemoteHost := '192.168.1.58'; // exception raised and program hangs if address is inaccessible  >:-|
      FClient.RemotePort := '50000';
      if FClient.Connect = False then
      begin
        ShowMessage('Unable to connect to remote host.');
        Exit;
      end;
      while (Application.Terminated = False) and (Self.Terminated = False) and (FClient.Connected = True) do
      begin
        FBuffer := FClient.Receiveln(#10);
        Synchronize(Update);
      end;
    finally
      FClient.Free;
    end;
  except
    on E: Exception do
      ShowMessage('Exception' + ^M + E.ClassName + ^M + E.Message);
  end;
end;

procedure TClientThread.Send(const Msg: string);
var
  SendClient: TClientSocket;
begin
  ShowMessage(Msg);
  SendClient := TClientSocket.Create(nil);
  SendClient.Address := '192.168.1.58';
  SendClient.Port := 50000;
  SendClient.Open;
  if SendClient.Active then
    SendClient.Socket.SendText(Msg + CRLF);
  SendClient.Close;
  SendClient.Free;
end;

procedure TClientThread.Update;
begin
  if Assigned(FHandler) then
    FHandler(FBuffer);
end;

procedure TClientThread.ClientError(Sender: TObject; SocketError: Integer);
begin
  FBuffer := SysUtils.IntToStr(SocketError);
end;

procedure TClientThread.ClientSend(Sender: TObject; Buf: PAnsiChar; var DataLen: Integer);
begin
  FBuffer := 'MESSAGE SENT: ' + Buf;
end;

{ TFormMain }

procedure TFormMain.FormCreate(Sender: TObject);
begin
  FMessages := TExecMessages.Create;
  FThread := TClientThread.Create(True);
  FThread.Handler := ThreadHandler;
  FThread.Resume;
  Timer1.Enabled := True;
end;

procedure TFormMain.FormDestroy(Sender: TObject);
begin
  FMessages.Free;
end;

procedure TFormMain.ThreadHandler(const S: string);
var
  Msg: TExecMessage;
begin
  FMessages.Clear;
  Msg := FMessages.Add(S);
  if Msg <> nil then
  begin
    Inc(FTraffic);
    StatusBar1.Panels[3].Text := SysUtils.Trim(Msg.msg_buf) + ' [' + Msg.msg_server + '] ' + SysUtils.IntToStr(Msg.msg_accounts.Count);
    while Memo1.Lines.Count > 100 do
      Memo1.Lines.Delete(0);
    Memo1.Lines.Add(StatusBar1.Panels[3].Text);
  end;
  // Fmsg_time_dt := DateUtils.UnixToDateTime(Round(msg_time));
  // Fmsg_time_str := SysUtils.FormatDateTime('yyyy-mm-dd hh:nn:ss', msg_time_dt);
end;

procedure TFormMain.Timer1Timer(Sender: TObject);
var
  F: Integer;
begin
  F := Round(1000 / Timer1.Interval);
  if FTraffic > FMaxTraffic then
    FMaxTraffic := FTraffic;
  StatusBar1.Panels[0].Text := IntToStr(FTraffic) + '/sec';
  StatusBar1.Panels[1].Text := IntToStr(FMaxTraffic) + '/sec max';
  if FMaxTraffic = 0 then
    FTrafficPercent := 0
  else
    FTrafficPercent := Round(FTraffic / FMaxTraffic * 100);
  StatusBar1.Panels[2].Text := IntToStr(FTrafficPercent) + '%';
  if FTrafficPercent < ProgressBar1.Position then
    ProgressBar1.Position := ProgressBar1.Position - 5
  else
    ProgressBar1.Position := FTrafficPercent;
  Inc(FTrafficCount);
  if FTrafficCount >= F then
    FTraffic := 0;
end;

procedure TFormMain.Button2Click(Sender: TObject);
var
  msg: string;
begin
  if LabeledEditAliasesDest.Text <> '' then
    msg := ':exec INTERNAL ' + LabeledEditAliasesDest.Text + ' :' + LabeledEditAliasesTrailing.Text
  else
    msg := ':exec INTERNAL :' + LabeledEditAliasesTrailing.Text;
  FThread.Send(msg);
end;

end.

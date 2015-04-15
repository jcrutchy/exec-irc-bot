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
  Unserializer,
  ComCtrls,
  Grids,
  ExtCtrls,
  ScktComp,
  Utils, Menus;

type

  TClientThread = class;

  TFormMain = class(TForm)
    StatusBar1: TStatusBar;
    Timer1: TTimer;
    ProgressBar1: TProgressBar;
    LabelMessage: TLabel;
    MemoTraffic: TMemo;
    Panel1: TPanel;
    MainMenu: TMainMenu;
    MenuFile: TMenuItem;
    MenuItemExit: TMenuItem;
    ListBoxBuckets: TListBox;
    ListBoxAliases: TListBox;
    ListBoxHandles: TListBox;
    Panel2: TPanel;
    LabeledEditAliasesDest: TLabeledEdit;
    LabeledEditAliasesTrailing: TLabeledEdit;
    ButtonSend: TButton;
    Button3: TButton;
    ButtonRunTests: TButton;
    Button1: TButton;
    Splitter1: TSplitter;
    Splitter2: TSplitter;
    Splitter3: TSplitter;
    procedure FormCreate(Sender: TObject);
    procedure Timer1Timer(Sender: TObject);
    procedure ButtonSendClick(Sender: TObject);
    procedure Button3Click(Sender: TObject);
    procedure ButtonRunTestsClick(Sender: TObject);
    procedure Button1Click(Sender: TObject);
  private
    FThread: TClientThread;
    FMaxTraffic: Integer;
    FTraffic: Integer;
    FTrafficPercent: Integer;
    FTrafficCount: Integer;
    FErrorCount: Integer;
    FMessageCount: Integer;
    FByteCount: Integer;
    FStartTime: Cardinal;
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
      FClient.RemoteHost := '192.168.1.22';
      FClient.RemotePort := '50000';
      if FClient.Connect = False then
      begin
        ShowMessage('Unable to connect to remote host.');
        Exit;
      end;
      FBuffer := '';
      while (Application.Terminated = False) and (Self.Terminated = False) and (FClient.Connected = True) do
      begin
        FClient.ReceiveBuf(Buf, 1);
        FBuffer := FBuffer + Buf;
        if Copy(FBuffer, Length(FBuffer) - Length(TERMINATOR) + 1, Length(TERMINATOR)) = TERMINATOR then
        begin
          Synchronize(Update);
          FBuffer := '';
        end;
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
begin
  if Assigned(FClient) then
    if FClient.Connected then
      FClient.Sendln(Msg);
end;

procedure TClientThread.Update;
begin
  if Assigned(FHandler) then
    FHandler(FBuffer);
end;

procedure TClientThread.ClientError(Sender: TObject; SocketError: Integer);
begin

end;

procedure TClientThread.ClientSend(Sender: TObject; Buf: PAnsiChar; var DataLen: Integer);
begin

end;

{ TFormMain }

procedure TFormMain.FormCreate(Sender: TObject);
begin
  FStartTime := GetTickCount;
  FThread := TClientThread.Create(True);
  FThread.Handler := ThreadHandler;
  FThread.Resume;
  Timer1.Enabled := True;
end;

procedure TFormMain.ThreadHandler(const S: string);
var
  Msg: TSerialized;
  i: Integer;
begin
  Msg := TSerialized.Create;
  try
    {while Memo1.Lines.Count > 100 do
      Memo1.Lines.Delete(0);}
    Inc(FTraffic, Length(S));
    Inc(FByteCount, Length(S));
    Inc(FMessageCount);
    StatusBar1.Panels[4].Text := IntToStr(FMessageCount) + ' messages';
    StatusBar1.Panels[5].Text := Format('%.1f', [FByteCount / 1024]) + ' kb';
    StatusBar1.Panels[6].Text := Format('%.1f', [(GetTickCount - FStartTime) / 1000]) + ' sec';
    if Msg.Parse(S) then
    begin
      try
        LabelMessage.Caption := Msg.ArrayData['buf'].StringData;
        if Msg['type'].StringData = 'reader_handles' then
        begin
          // triggers in response to /READER_HANDLES command
          MemoTraffic.Lines.Add('READER_HANDLES: [' + Msg['buf']['alias'].StringData + '] '+ Msg['buf']['command'].StringData);
          i := ListBoxHandles.Items.IndexOf(Msg['buf']['alias'].StringData + ' [' + IntToStr(Msg['buf']['pid'].IntegerData) + ']');
          if i < 0 then
            ListBoxHandles.Items.Add(Msg['buf']['alias'].StringData + ' [' + IntToStr(Msg['buf']['pid'].IntegerData) + ']');
        end;
        if Msg['type'].StringData = 'reader_exec_list' then
        begin
          // triggers in response to /READER_EXEC_LIST command
          MemoTraffic.Lines.Add('READER_EXEC_LIST: [' + Msg['buf']['alias'].StringData + '] '+ Msg['handle']['command'].StringData);
          i := ListBoxAliases.Items.IndexOf(Msg['buf']['alias'].StringData);
          if i < 0 then
            ListBoxHandles.Items.Add(Msg['buf']['alias'].StringData);
        end;
        if Msg['type'].StringData = 'reader_buckets' then
        begin
          // triggers in response to /READER_BUCKETS command
          MemoTraffic.Lines.Add('READER_BUCKETS: ' + Msg['index'].StringData + ' => '+ Msg['buf'].StringData);
          i := ListBoxBuckets.Items.IndexOf(Msg['index'].StringData);
          if i < 0 then
            ListBoxBuckets.Items.Add(Msg['index'].StringData);
        end;
        if Msg['type'].StringData = 'socket' then
        begin
          // triggers when a socket message is received
          MemoTraffic.Lines.Add('SOCKET: ' + Msg['buf'].StringData);
        end;
        if Msg['type'].StringData = 'proc_timeout' then
        begin
          // triggers when a process times out
          MemoTraffic.Lines.Add('PROC_TIMEOUT: [' + Msg['handle']['alias'].StringData + '] '+ Msg['handle']['command'].StringData);
          i := ListBoxHandles.Items.IndexOf(Msg['handle']['alias'].StringData + ' [' + IntToStr(Msg['handle']['pid'].IntegerData) + ']');
          if i >= 0 then
            ListBoxHandles.Items.Delete(i);
        end;
        if Msg['type'].StringData = 'data' then
        begin
          // triggers after message is parsed into items
          MemoTraffic.Lines.Add('DATA: ' + Msg['items']['nick'].StringData + ' [' + Msg['items']['destination'].StringData + '] ' + Msg['items']['trailing'].StringData);
        end;
        if Msg['type'].StringData = 'command' then
        begin
          // triggers on internal commands, such as quit, rehash, etc
        end;
        if Msg['type'].StringData = 'proc_start' then
        begin
          // triggers when a process is started
          MemoTraffic.Lines.Add('PROC_START: [' + Msg['handle']['alias'].StringData + '] '+ Msg['handle']['command'].StringData);
          ListBoxHandles.Items.Add(Msg['handle']['alias'].StringData + ' [' + IntToStr(Msg['handle']['pid'].IntegerData) + ']');
        end;
        if Msg['type'].StringData = 'proc_end' then
        begin
          // triggers when process terminates normally
          MemoTraffic.Lines.Add('PROC_END: [' + Msg['handle']['alias'].StringData + '] '+ Msg['handle']['command'].StringData);
          i := ListBoxHandles.Items.IndexOf(Msg['handle']['alias'].StringData + ' [' + IntToStr(Msg['handle']['pid'].IntegerData) + ']');
          if i >= 0 then
            ListBoxHandles.Items.Delete(i);
        end;
        if Msg['type'].StringData = 'proc_kill' then
        begin
          // triggers when process is killed
          MemoTraffic.Lines.Add('PROC_KILL: [' + Msg['handle']['alias'].StringData + '] '+ Msg['handle']['command'].StringData);
          i := ListBoxHandles.Items.IndexOf(Msg['handle']['alias'].StringData + ' [' + IntToStr(Msg['handle']['pid'].IntegerData) + ']');
          if i >= 0 then
            ListBoxHandles.Items.Delete(i);
        end;
      except
        MemoTraffic.Lines.Add('******* MESSAGE STRUCTRURE ACCESS ERROR - START *******');
        MemoTraffic.Lines.Add(S);
        MemoTraffic.Lines.Add('******* MESSAGE STRUCTRURE ACCESS ERROR - FINISH *******');
      end;
    end
    else
    begin
      MemoTraffic.Lines.Add(S);
      LabelMessage.Caption := Msg.Serialized;
      FErrorCount := FErrorCount + 1;
      StatusBar1.Panels[3].Text := IntToStr(FErrorCount) + ' errors';
    end;
  finally
    Msg.Free;
  end;
end;

procedure TFormMain.Timer1Timer(Sender: TObject);
var
  F: Integer;
begin
  F := Round(1000 / Timer1.Interval);
  if FTraffic > FMaxTraffic then
    FMaxTraffic := FTraffic;
  StatusBar1.Panels[0].Text := Format('%.1f', [FTraffic / 1024]) + ' kb/s';
  StatusBar1.Panels[1].Text := Format('%.1f', [FMaxTraffic / 1024]) + ' kb/s max';
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

procedure TFormMain.ButtonSendClick(Sender: TObject);
var
  msg: string;
begin
  if LabeledEditAliasesDest.Text <> '' then
    msg := ':exec INTERNAL ' + LabeledEditAliasesDest.Text + ' :' + LabeledEditAliasesTrailing.Text
  else
    msg := ':exec INTERNAL :' + LabeledEditAliasesTrailing.Text;
  FThread.Send(msg);
end;

procedure TFormMain.Button3Click(Sender: TObject);
begin
  FThread.Terminate;
end;

procedure TFormMain.ButtonRunTestsClick(Sender: TObject);
begin
  RunUnserializeTests;
end;

procedure TFormMain.Button1Click(Sender: TObject);
begin
  if Assigned(FThread) then
  begin
    FThread.Send('/READER_HANDLES');
    FThread.Send('/READER_EXEC_LIST');
    FThread.Send('/READER_BUCKETS');
  end;
end;

end.

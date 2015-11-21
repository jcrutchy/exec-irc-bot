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
  Utils,
  Menus;

type

  TClientThread = class;

  TFormMain = class(TForm)
    StatusBar1: TStatusBar;
    TimerStatus: TTimer;
    ProgressBar1: TProgressBar;
    LabelMessage: TLabel;
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
    ButtonAliasesBuckets: TButton;
    Splitter1: TSplitter;
    Splitter2: TSplitter;
    Splitter3: TSplitter;
    TimerUpdateHandles: TTimer;
    Splitter4: TSplitter;
    TreeViewData: TTreeView;
    Panel3: TPanel;
    MemoTraffic: TMemo;
    Splitter5: TSplitter;
    Panel4: TPanel;
    MemoAliasTraffic: TMemo;
    LabeledEditAlias: TLabeledEdit;
    ButtonAliasTrafficClear: TButton;
    procedure FormCreate(Sender: TObject);
    procedure TimerStatusTimer(Sender: TObject);
    procedure ButtonSendClick(Sender: TObject);
    procedure Button3Click(Sender: TObject);
    procedure ButtonRunTestsClick(Sender: TObject);
    procedure ButtonAliasesBucketsClick(Sender: TObject);
    procedure TimerUpdateHandlesTimer(Sender: TObject);
    procedure ListBoxHandlesClick(Sender: TObject);
    procedure ListBoxBucketsClick(Sender: TObject);
    procedure ListBoxAliasesClick(Sender: TObject);
    procedure ButtonAliasTrafficClearClick(Sender: TObject);
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
    FSelectedHandle: string;
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
      FClient.RemoteHost := '10.0.2.15';
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
  TimerStatus.Enabled := True;
  TimerUpdateHandles.Enabled := True;
end;

procedure TFormMain.ThreadHandler(const S: string);
var
  Msg: TSerialized;
  i: Integer;
  Tmp: string;
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
          LabelMessage.Caption := 'READER_HANDLES: [' + Msg['buf']['alias'].StringData + '] '+ Msg['buf']['command'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          Tmp := Msg['buf']['alias'].StringData + ' [' + IntToStr(Msg['buf']['pid'].IntegerData) + ']';
          i := ListBoxHandles.Items.IndexOf(Tmp);
          if i < 0 then
          begin
            ListBoxHandles.Items.AddObject(Tmp, Msg);
            if FSelectedHandle = Tmp then
            begin
              ListBoxHandles.Selected[ListBoxHandles.Items.Count - 1] := True;
              ListBoxHandlesClick(nil);
            end;
            Exit;
          end;
        end;
        if Msg['type'].StringData = 'reader_exec_list' then
        begin
          // triggers in response to /READER_EXEC_LIST command
          LabelMessage.Caption := 'READER_EXEC_LIST: [' + Msg['buf']['alias'].StringData + '] '+ Msg['buf']['cmd'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          i := ListBoxAliases.Items.IndexOf(Msg['buf']['alias'].StringData);
          if i < 0 then
          begin
            ListBoxAliases.Items.AddObject(Msg['buf']['alias'].StringData, Msg);
            Exit;
          end;
        end;
        if Msg['type'].StringData = 'reader_buckets' then
        begin
          // triggers in response to /READER_BUCKETS command
          LabelMessage.Caption := 'READER_BUCKETS: ' + Msg['index'].StringData + ' => '+ Msg['buf'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          i := ListBoxBuckets.Items.IndexOf(Msg['index'].StringData);
          if i < 0 then
          begin
            ListBoxBuckets.Items.AddObject(Msg['index'].StringData, Msg);
            Exit;
          end;
        end;
        if Msg['type'].StringData = 'socket' then
        begin
          // triggers when a socket message is received
          LabelMessage.Caption := 'SOCKET: ' + Msg['buf'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
        end;
        if Msg['type'].StringData = 'data' then
        begin
          // triggers after message is parsed into items
          LabelMessage.Caption := 'DATA: ' + Msg['items']['nick'].StringData + ' [' + Msg['items']['destination'].StringData + '] ' + Msg['items']['trailing'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
        end;
        if Msg['type'].StringData = 'command' then
        begin
          // triggers on internal commands, such as quit, rehash, etc
        end;
        if Msg['type'].StringData = 'proc_start' then
        begin
          // triggers when a process is started
          LabelMessage.Caption := 'PROC_START: [' + Msg['handle']['alias'].StringData + '] '+ Msg['handle']['command'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          Tmp := Msg['handle']['alias'].StringData + ' [' + IntToStr(Msg['handle']['pid'].IntegerData) + ']';
          ListBoxHandles.Items.AddObject(Tmp, Msg);
          if FSelectedHandle = Tmp then
          begin
            ListBoxHandles.Selected[ListBoxHandles.Items.Count - 1] := True;
            ListBoxHandlesClick(nil);
          end;
          Exit;
        end;
        if Msg['type'].StringData = 'proc_end' then
        begin
          // triggers when process terminates normally
          LabelMessage.Caption := 'PROC_END: [' + Msg['handle']['alias'].StringData + '] '+ Msg['handle']['command'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          i := ListBoxHandles.Items.IndexOf(Msg['handle']['alias'].StringData + ' [' + IntToStr(Msg['handle']['pid'].IntegerData) + ']');
          if i >= 0 then
          begin
            ListBoxHandles.Items.Objects[i].Free;
            ListBoxHandles.Items.Delete(i);
          end;
        end;
        if Msg['type'].StringData = 'proc_timeout' then
        begin
          // triggers when a process times out
          LabelMessage.Caption := 'PROC_TIMEOUT: [' + Msg['handle']['alias'].StringData + '] '+ Msg['handle']['command'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          i := ListBoxHandles.Items.IndexOf(Msg['handle']['alias'].StringData + ' [' + IntToStr(Msg['handle']['pid'].IntegerData) + ']');
          if i >= 0 then
          begin
            ListBoxHandles.Items.Objects[i].Free;
            ListBoxHandles.Items.Delete(i);
          end;
        end;
        if Msg['type'].StringData = 'proc_kill' then
        begin
          // triggers when process is killed
          LabelMessage.Caption := 'PROC_KILL: [' + Msg['handle']['alias'].StringData + '] '+ Msg['handle']['command'].StringData;
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          i := ListBoxHandles.Items.IndexOf(Msg['handle']['alias'].StringData + ' [' + IntToStr(Msg['handle']['pid'].IntegerData) + ']');
          if i >= 0 then
          begin
            ListBoxHandles.Items.Objects[i].Free;
            ListBoxHandles.Items.Delete(i);
          end;
        end;
        if Msg['type'].StringData = 'stdout' then
        begin
          LabelMessage.Caption := 'STDOUT: [' + Msg['handle']['alias'].StringData + '] '+ Trim(Msg['buf'].StringData);
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          if Msg['handle']['alias'].StringData = LabeledEditAlias.Text then
            MemoAliasTraffic.Lines.Add(Trim(Msg['buf'].StringData));
        end;
        if Msg['type'].StringData = 'stderr' then
        begin
          LabelMessage.Caption := 'STDERR: [' + Msg['handle']['alias'].StringData + '] '+ Trim(Msg['buf'].StringData);
          MemoTraffic.Lines.Add(LabelMessage.Caption);
          if Msg['handle']['alias'].StringData = LabeledEditAlias.Text then
            MemoAliasTraffic.Lines.Add(Trim(Msg['buf'].StringData));
        end;
      except
        MemoTraffic.Lines.Add('******* MESSAGE STRUCTURE ACCESS EXCEPTION - START *******');
        MemoTraffic.Lines.Add(S);
        MemoTraffic.Lines.Add('******* MESSAGE STRUCTURE ACCESS EXCEPTION - FINISH *******');
        LabelMessage.Caption := Msg.Serialized;
        FErrorCount := FErrorCount + 1;
        StatusBar1.Panels[3].Text := IntToStr(FErrorCount) + ' errors';
      end;
    end
    else
    begin
      MemoTraffic.Lines.Add('******* MESSAGE STRUCTURE PARSE ERROR - START *******');
      MemoTraffic.Lines.Add(S);
      MemoTraffic.Lines.Add('******* MESSAGE STRUCTURE PARSE ERROR - FINISH *******');
      LabelMessage.Caption := Msg.Serialized;
      FErrorCount := FErrorCount + 1;
      StatusBar1.Panels[3].Text := IntToStr(FErrorCount) + ' errors';
    end;
  except
    MemoTraffic.Lines.Add('******* MESSAGE STRUCTURE PARSE EXCEPTION - START *******');
    MemoTraffic.Lines.Add(S);
    MemoTraffic.Lines.Add('******* MESSAGE STRUCTURE PARSE EXCEPTION - FINISH *******');
    LabelMessage.Caption := Msg.Serialized;
    FErrorCount := FErrorCount + 1;
    StatusBar1.Panels[3].Text := IntToStr(FErrorCount) + ' errors';
  end;
  Msg.Free;
end;

procedure TFormMain.TimerStatusTimer(Sender: TObject);
var
  F: Integer;
begin
  F := Round(1000 / TimerStatus.Interval);
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

procedure TFormMain.ButtonAliasesBucketsClick(Sender: TObject);
var
  t: Cardinal;
begin
  if Assigned(FThread) then
  begin
    FThread.Send('/READER_EXEC_LIST');
    t := GetTickCount;
    while (GetTickCount - t) >= 1000 do
      Application.ProcessMessages;
    FThread.Send('/READER_BUCKETS');
  end;
end;

procedure TFormMain.TimerUpdateHandlesTimer(Sender: TObject);
begin
  if ListBoxHandles.Items.IndexOf(FSelectedHandle) = -1 then
    FSelectedHandle := '';
  ListBoxHandles.Clear;
  if Assigned(FThread) then
  begin
    FThread.Send('/READER_HANDLES');
    if ListBoxBuckets.Count = 0 then
      FThread.Send('/READER_BUCKETS');
    if ListBoxAliases.Count = 0 then
      FThread.Send('/READER_EXEC_LIST');
  end;
end;

procedure TFormMain.ListBoxHandlesClick(Sender: TObject);
begin
  if ListBoxHandles.ItemIndex >= 0 then
  begin
    if ListBoxHandles.Items[ListBoxHandles.ItemIndex] <> FSelectedHandle then
    begin
      FSelectedHandle := ListBoxHandles.Items[ListBoxHandles.ItemIndex];
      TreeViewData.Items.Clear;
      TSerialized(ListBoxHandles.Items.Objects[ListBoxHandles.ItemIndex]).FillTreeView(TreeViewData);
      TreeViewData.FullExpand;
      // deselect alias and bucket
    end;
  end
  else
  begin
    FSelectedHandle := '';
    TreeViewData.Items.Clear;
  end;
end;

procedure TFormMain.ListBoxBucketsClick(Sender: TObject);
begin
  if ListBoxBuckets.ItemIndex >= 0 then
  begin
    TreeViewData.Items.Clear;
    TSerialized(ListBoxBuckets.Items.Objects[ListBoxBuckets.ItemIndex]).FillTreeView(TreeViewData);
    TreeViewData.FullExpand;
    // deselect handle and alias
  end
  else
    TreeViewData.Items.Clear;
end;

procedure TFormMain.ListBoxAliasesClick(Sender: TObject);
begin
  if ListBoxAliases.ItemIndex >= 0 then
  begin
    TreeViewData.Items.Clear;
    TSerialized(ListBoxAliases.Items.Objects[ListBoxAliases.ItemIndex]).FillTreeView(TreeViewData);
    TreeViewData.FullExpand;
    // deselect handle and bucket
  end
  else
    TreeViewData.Items.Clear;
end;

procedure TFormMain.ButtonAliasTrafficClearClick(Sender: TObject);
begin
  MemoAliasTraffic.Clear;
end;

end.

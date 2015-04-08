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
  Data;

type

  TClientThread = class;

  TFormMain = class(TForm)
    MemoTraffic: TMemo;
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
  private
    FThread: TClientThread;
    FMessages: TExecMessages;
    procedure ThreadHandler(const S: string);
  end;

  TClientThread = class(TThread)
  private
    FBuffer: string;
    FHandler: TGetStrProc;
  public
    constructor Create(CreateSuspended: Boolean);
    procedure Update;
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
  Client: TTcpClient;
begin
  try
    Client := TTcpClient.Create(nil);
    try
      Client.RemoteHost := '192.168.1.58'; // exception raised and program hangs if address is inaccessible  >:-|
      Client.RemotePort := '50000';
      if Client.Connect = False then
      begin
        ShowMessage('Unable to connect to remote host.');
        Exit;
      end;
      while (Application.Terminated = False) and (Self.Terminated = False) and (Client.Connected = True) do
      begin
        FBuffer := Client.Receiveln(#10);
        Synchronize(Update);
      end;
    finally
      Client.Free;
    end;
  except
    on E: Exception do
      ShowMessage('Exception' + ^M + E.ClassName + ^M + E.Message);
  end;
end;

procedure TClientThread.Update;
begin
  if Assigned(FHandler) then
    FHandler(FBuffer);
end;

{ TFormMain }

procedure TFormMain.FormCreate(Sender: TObject);
begin
  FMessages := TExecMessages.Create;
  FThread := TClientThread.Create(True);
  FThread.Handler := ThreadHandler;
  FThread.Resume;
end;

procedure TFormMain.FormDestroy(Sender: TObject);
begin
  FMessages.Free;
end;

procedure TFormMain.ThreadHandler(const S: string);
var
  Msg: TExecMessage;
begin
  Msg := FMessages.Add(S);
  if Msg <> nil then
    MemoTraffic.Lines.Text := SysUtils.Trim(Msg.msg_buf) + ' [' + Msg.msg_server + '] ' + SysUtils.IntToStr(Msg.msg_accounts.Count);
  // Fmsg_time_dt := DateUtils.UnixToDateTime(Round(msg_time));
  // Fmsg_time_str := SysUtils.FormatDateTime('yyyy-mm-dd hh:nn:ss', msg_time_dt);
end;

end.

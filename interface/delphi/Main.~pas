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
  StdCtrls;

type

  TFormMain = class(TForm)
    MemoTraffic: TMemo;
    TcpClient1: TTcpClient;
    procedure FormCreate(Sender: TObject);
  end;

  TClientThread = class(TThread)
  private
    FBuffer: TStrings;
    FTarget: TStrings;
  public
    constructor Create(CreateSuspended: Boolean);
    procedure Update;
    procedure Execute; override;
    procedure Terminate;
  public
    property Buffer: TStrings read FBuffer;
    property Target: TStrings read FTarget write FTarget;
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
  FBuffer := TStringList.Create;
end;

procedure TClientThread.Execute;
var
  Client: TTcpClient;
begin
  try
    Client := TTcpClient.Create(nil);
    try
      Client.RemoteHost := '192.168.1.57';
      Client.RemotePort := '50000';
      if Client.Connect = False then
      begin
        ShowMessage('Unable to connect to remote host.');
        Exit;
      end;
      while (Application.Terminated = False) and (Self.Terminated = False) and (Client.Connected = True) do
      begin
        FBuffer.Clear;
        FBuffer.Text := Client.Receiveln(#10);
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

procedure TClientThread.Terminate;
begin
  FBuffer.Free;
  inherited;
end;

procedure TClientThread.Update;
begin
  FTarget.Clear;
  FTarget.Text := FBuffer.Text;
end;

{ TFormMain }

procedure TFormMain.FormCreate(Sender: TObject);
var
  Thread: TClientThread;
begin
  Thread := TClientThread.Create(True);
  Thread.FreeOnTerminate := True;
  Thread.Target := MemoTraffic.Lines;
  Thread.Resume;
end;

end.

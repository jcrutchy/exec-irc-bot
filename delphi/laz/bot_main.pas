unit bot_main;

{$mode delphi}{$H+}

interface

uses
  Classes,
  SysUtils,
  process,
  FileUtil,
  Forms,
  Controls,
  Graphics,
  Dialogs,
  StdCtrls,
  bot_classes;

type

  { TForm1 }

  TForm1 = class(TForm)
    Button1: TButton;
    Edit1: TEdit;
    MemoData1: TMemo;
    MemoData2: TMemo;
    Process1: TProcess;
    procedure Button1Click(Sender: TObject);
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
  private
    FServers: TBotServerArray;
  private
    procedure ReceiveHandler(const Server: TBotServer; const Message: TBotMessage; const Data: string);
  end;

var
  Form1: TForm1;

implementation

uses
  bot_utils;

{$R *.lfm}

{ TForm1 }

procedure TForm1.FormCreate(Sender: TObject);
begin
  FServers := TBotServerArray.Create(ReceiveHandler);
  FServers.Add.Connect('irc.sylnt.us', 'z', 'z', 'z.bot', 'hostname', 'servername', 6667);
  FServers.Add.Connect('banks.freenode.net', 'z_exec', 'z', 'z.bot', 'hostname', 'servername', 6667);
end;

procedure TForm1.Button1Click(Sender: TObject);
begin
  if Edit1.Text = '' then
    Exit;
  FServers.Servers[0].Send(Edit1.Text);
  Edit1.Text := '';
  while MemoData1.Lines.Count > 500 do
    MemoData1.Lines.Delete(0);
end;

procedure TForm1.FormDestroy(Sender: TObject);
begin
  FServers.Free;
end;

procedure TForm1.ReceiveHandler(const Server: TBotServer; const Message: TBotMessage; const Data: string);
var
  S: string;
begin
  S := FormatDateTime('yyyy-mm-dd hh:nn:ss.zzz', Now) + ' > ' + Trim(Data);
  if Server.RemoteHost = 'irc.sylnt.us' then
    MemoData1.Lines.Add(S)
  else
    MemoData2.Lines.Add(S);
  while MemoData1.Lines.Count > 500 do
    MemoData1.Lines.Delete(0);
end;

end.

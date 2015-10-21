unit bot_main;

interface

uses
  Windows,
  SysUtils,
  Classes,
  Graphics,
  Controls,
  Forms,
  Dialogs,
  StdCtrls,
  DateUtils,
  ComCtrls,
  Messages,
  Grids,
  ExtCtrls,
  Menus,
  bot_data;

type

  TFormMain = class(TForm)
    MemoData: TMemo;
    Panel1: TPanel;
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
  private
    FServers: bot_data.TBotServerArray;
    FNickServPasswordFileName: string;
  private
    procedure ReceiveHandler(const Server: TBotServer; const Message: TBotMessage; const Data: string);
    procedure Startup(const Server: TBotServer);
  end;

var
  FormMain: TFormMain;

implementation

{$R *.dfm}

{ TFormMain }

procedure TFormMain.FormCreate(Sender: TObject);
begin
  FServers := bot_data.TBotServerArray.Create(ReceiveHandler);
  FNickServPasswordFileName := SysUtils.ExtractRelativePath(SysUtils.ExtractFilePath(ParamStr(0)), '..\..\pwd\exec');
  FServers.Add.Connect('irc.sylnt.us', '6667', 'exec', 'exec', 'exec.bot', 'hostname', 'servername');
end;

procedure TFormMain.FormDestroy(Sender: TObject);
begin
  FServers.Free;
end;

procedure TFormMain.ReceiveHandler(const Server: TBotServer; const Message: TBotMessage; const Data: string);
var
  S: Classes.TStrings;
begin
  MemoData.Lines.Add(Data);
  if Message.Trailing = 'You have 60 seconds to identify to your nickname before it is changed.' then
  begin
    S := Classes.TStringList.Create;
    try
      if SysUtils.FileExists(FNickServPasswordFileName) then
      begin
        S.LoadFromFile(FNickServPasswordFileName);
        Server.Send('NickServ IDENTIFY ' + SysUtils.Trim(S.Text), True);
      end;
    finally
      S.Free;
    end;
    Startup(Server);
  end;
end;

procedure TFormMain.Startup(const Server: TBotServer);
begin
  Server.Send('JOIN #test');
end;

end.

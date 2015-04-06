unit main;

{$mode objfpc}{$H+}

interface

uses
  Classes,
  SysUtils,
  FileUtil,
  Forms,
  Controls,
  Graphics,
  Dialogs,
  StdCtrls,
  ExtCtrls;

type

  { TForm1 }

  TForm1 = class(TForm)
    Button1: TButton;
    Memo1: TMemo;
    procedure Button1Click(Sender: TObject);
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
  private
    FData: TFileStream;
  end;

var
  Form1: TForm1;

implementation

{$R *.lfm}

{ TForm1 }

procedure TForm1.FormCreate(Sender: TObject);
begin
  FData := TFileStream.Create('/home/jared/git/data/exec_iface', fmOpenRead);
end;

procedure TForm1.Button1Click(Sender: TObject);
begin
  while not Application.Terminated do
  begin
    Application.ProcessMessages;
  end;
end;

procedure TForm1.FormDestroy(Sender: TObject);
begin
  FData.Free;
end;

end.

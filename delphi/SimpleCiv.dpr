program SimpleCiv;

uses
  Forms,
  Unit02 in 'Unit02.pas' {FormMain},
  DataClasses in 'DataClasses.pas';

{$R *.res}

begin
  Application.Initialize;
  Application.HelpFile := 'SimpleCiv';
  Application.CreateForm(TFormMain, FormMain);
  Application.Run;
end.
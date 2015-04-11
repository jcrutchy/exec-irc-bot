program execstat;

uses
  Forms,
  Main in 'Main.pas' {FormMain},
  Unserializer in 'Unserializer.pas',
  Utils in 'Utils.pas';

{$R *.res}

begin
  Application.Initialize;
  Application.CreateForm(TFormMain, FormMain);
  Application.Run;
end.

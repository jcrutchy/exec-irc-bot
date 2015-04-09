program execstat;

uses
  Forms,
  Main in 'Main.pas' {FormMain},
  Data in 'Data.pas',
  uLkJSON in '..\..\..\..\Delphi Library 2011\from_the_internet\uLkJSON.pas';

{$R *.res}

begin
  Application.Initialize;
  Application.CreateForm(TFormMain, FormMain);
  Application.Run;
end.

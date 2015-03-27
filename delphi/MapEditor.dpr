program MapEditor;

uses
  Forms,
  Unit01 in 'Unit01.pas' {FormMain},
  DataClasses in 'DataClasses.pas';

{$R *.res}

begin
  Application.Initialize;
  Application.Title := 'Map Editor';
  Application.CreateForm(TFormMain, FormMain);
  Application.Run;
end.
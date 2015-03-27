program Matrix;

uses
  Forms,
  SysUtils,
  Main in 'Main.pas' {FormMain};

{$R *.res}
{$R WindowsXP.res}

var
  S: string;

begin
  Application.Initialize;
  S := UpperCase(ParamStr(1));
  if S <> '' then
  begin
    while (Ord(S[1]) < 65) or (Ord(S[1]) > 90) do
      Delete(S, 1, 1);
    if S <> '' then
      case S[1] of
        'S': Application.CreateForm(TFormMain, FormMain);
        'A', 'P': { Set Password & Preview } ;
      else
        {Application.CreateForm(TFormConfig, FormConfig);}
      end;
  end
  else
    {Application.CreateForm(TFormConfig, FormConfig)};
  Application.Run;
end.
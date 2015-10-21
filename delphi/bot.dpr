program bot;

uses
  Forms,
  bot_main in 'bot_main.pas' {FormMain},
  bot_data in 'bot_data.pas';

begin
  Application.Initialize;
  Application.CreateForm(TFormMain, FormMain);
  Application.Run;
end.
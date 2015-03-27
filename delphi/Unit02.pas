unit Unit02;

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
  ExtCtrls,
  ComCtrls,
  Menus,
  JMC_FileUtils,
  JMC_Parts,
  ToolWin,
  DataClasses,
  ImgList;

type

  TFormMain = class(TForm)
    StatusBar1: TStatusBar;
    MainMenu1: TMainMenu;
    MenuFile: TMenuItem;
    MenuItemExit: TMenuItem;
    SaveDialog1: TSaveDialog;
    MenuItemLoadMap: TMenuItem;
    OpenDialog1: TOpenDialog;
    ImageList1: TImageList;
    N1: TMenuItem;
    ScrollBox1: TScrollBox;
    Image1: TImage;
    Timer1: TTimer;
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
    procedure Image1MouseMove(Sender: TObject; Shift: TShiftState; X, Y: Integer);
    procedure MenuItemLoadMapClick(Sender: TObject);
    procedure MenuItemExitClick(Sender: TObject);
    procedure Timer1Timer(Sender: TObject);
  private
    FMap: TJMC_Map;
    FOldX: Integer;
    FOldY: Integer;
    FIgnoreEvents: Boolean;
  end;

var
  FormMain: TFormMain;

implementation

{$R *.dfm}

{ TFormMain }

procedure TFormMain.FormCreate(Sender: TObject);
var
  Player: TJMC_Player;
begin
  FIgnoreEvents := True;
  FOldX := -1;
  FOldY := -1;
  FMap := TJMC_Map.Create;
  FMap.ScaleFactor := 2;
  FMap.Image := Image1;
  FMap.SetMapSize(50, 35);
  FMap.SetTileSize(10, 10);
  FMap.LoadTiles(ExtractFilePath(ParamStr(0)) + 'tiles.ini');
  FIgnoreEvents := False;
  FMap.LoadMap(ExtractFilePath(ParamStr(0)) + 'test0.map');
  Player := TJMC_Player.Create;
  Player.PlayerName := 'Jared';
  FMap.Players.Add(Player);
end;

procedure TFormMain.FormDestroy(Sender: TObject);
begin
  FMap.Free;
end;

procedure TFormMain.Image1MouseMove(Sender: TObject; Shift: TShiftState; X, Y: Integer);
var
  Tile: TJMC_Tile;
  i: Integer;
  j: Integer;
begin
  if FIgnoreEvents = True then
    Exit;
  i := X div FMap.TileWidth;
  j := Y div FMap.TileHeight;
  if (i = FOldX) and (j = FOldY) then
    Exit;
  FOldX := i;
  FOldY := j;
  Tile := FMap.Coords[i, j];
  if Tile <> nil then
    StatusBar1.Panels[0].Text := Format('Tile @ (%d,%d): "%s" [ID %d]', [i, j, Tile.TileName, Tile.TileID])
  else
    StatusBar1.Panels[0].Text := Format('Tile @ (%d,%d): NOT ASSIGNED', [i, j]);
  FMap.DrawCursor(i, j);
end;

procedure TFormMain.MenuItemLoadMapClick(Sender: TObject);
begin
  if not DirectoryExists(OpenDialog1.InitialDir) then
    OpenDialog1.InitialDir := ExtractFilePath(ParamStr(0));
  if OpenDialog1.Execute = False then
    Exit;
  FMap.LoadMap(OpenDialog1.FileName);
end;

procedure TFormMain.MenuItemExitClick(Sender: TObject);
begin
  Close;
end;

procedure TFormMain.Timer1Timer(Sender: TObject);
begin
  FMap.MoveIncrement;
end;

end.

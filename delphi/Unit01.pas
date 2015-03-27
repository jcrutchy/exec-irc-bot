unit Unit01;

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
    ScrollBox1: TScrollBox;
    Image1: TImage;
    MainMenu1: TMainMenu;
    MenuFile: TMenuItem;
    MenuItemExit: TMenuItem;
    MenuItemGenerateRandomMap: TMenuItem;
    MenuItemSaveMap: TMenuItem;
    SaveDialog1: TSaveDialog;
    MenuItemLoadMap: TMenuItem;
    OpenDialog1: TOpenDialog;
    ToolBar1: TToolBar;
    ComboBoxTiles: TComboBox;
    ImageList1: TImageList;
    N1: TMenuItem;
    N2: TMenuItem;
    Label1: TLabel;
    EditMapWidth: TEdit;
    EditMapHeight: TEdit;
    Label2: TLabel;
    Label3: TLabel;
    ToolButton1: TToolButton;
    ToolButton2: TToolButton;
    ToolButton3: TToolButton;
    ToolButton4: TToolButton;
    ToolButton5: TToolButton;
    ToolButton6: TToolButton;
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
    procedure Image1MouseMove(Sender: TObject; Shift: TShiftState; X, Y: Integer);
    procedure MenuItemGenerateRandomMapClick(Sender: TObject);
    procedure MenuItemSaveMapClick(Sender: TObject);
    procedure MenuItemLoadMapClick(Sender: TObject);
    procedure Image1MouseDown(Sender: TObject; Button: TMouseButton; Shift: TShiftState; X, Y: Integer);
    procedure EditMapWidthChange(Sender: TObject);
    procedure MenuItemExitClick(Sender: TObject);
  private
    FMap: TJMC_Map;
    FOldX: Integer;
    FOldY: Integer;
    FIgnoreEvents: Boolean;
    procedure FillTilesComboBox;
  end;

var
  FormMain: TFormMain;

implementation

{$R *.dfm}

{ TFormMain }

procedure TFormMain.FillTilesComboBox;
var
  i: Integer;
begin
  ComboBoxTiles.Clear;
  for i := 0 to FMap.Tiles.Count - 1 do
    ComboBoxTiles.Items.AddObject(FMap.Tiles.Tiles[i].TileName, FMap.Tiles.Tiles[i]);
  ComboBoxTiles.ItemIndex := -1;
  if ComboBoxTiles.Items.Count > 0 then
    ComboBoxTiles.ItemIndex := 0;
  for i := 0 to FMap.Tiles.Count - 1 do
    if ComboBoxTiles.Items[i] = 'land' then
    begin
      ComboBoxTiles.ItemIndex := i;
      Break;
    end;
end;

procedure TFormMain.FormCreate(Sender: TObject);
begin
  FIgnoreEvents := True;
  FOldX := -1;
  FOldY := -1;
  FMap := TJMC_Map.Create;
  FMap.Image := Image1;
  FMap.SetMapSize(50, 35);
  EditMapWidth.Text := SysUtils.IntToStr(FMap.Columns);
  EditMapHeight.Text := SysUtils.IntToStr(FMap.Rows);
  FMap.SetTileSize(10, 10);
  FMap.LoadTiles(ExtractFilePath(ParamStr(0)) + 'tiles.ini');
  FillTilesComboBox;
  MenuItemGenerateRandomMapClick(nil);
  FIgnoreEvents := False;
end;

procedure TFormMain.FormDestroy(Sender: TObject);
begin
  FMap.SaveTiles(ExtractFilePath(ParamStr(0)) + 'tiles.ini');
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
  if ssLeft in Shift then
  begin
    FIgnoreEvents := True;
    Image1MouseDown(Self, mbLeft, Shift, X, Y);
    FIgnoreEvents := False;
  end;
  Tile := FMap.Coords[i, j];
  if Tile <> nil then
    StatusBar1.Panels[0].Text := Format('Tile @ (%d,%d): "%s" [ID %d]', [i, j, Tile.TileName, Tile.TileID])
  else
    StatusBar1.Panels[0].Text := Format('Tile @ (%d,%d): NOT ASSIGNED', [i, j]);
  FMap.DrawCursor(i, j);
end;

procedure TFormMain.Image1MouseDown(Sender: TObject; Button: TMouseButton; Shift: TShiftState; X, Y: Integer);
var
  Tile: TJMC_Tile;
  i: Integer;
  j: Integer;
begin
  if ComboBoxTiles.ItemIndex < 0 then
    Exit;
  Tile := TJMC_Tile(ComboBoxTiles.Items.Objects[ComboBoxTiles.ItemIndex]);
  if Tile <> nil then
  begin
    i := X div FMap.TileWidth;
    j := Y div FMap.TileHeight;
    FMap.Coords[i, j] := Tile;
    FMap.PaintMap;
    FOldX := -1;
    FOldY := -1;
    if FIgnoreEvents = False then
      Image1MouseMove(Self, Shift, X, Y);
  end;
end;

procedure TFormMain.MenuItemGenerateRandomMapClick(Sender: TObject);
begin
  FMap.FillMap('water');
  FMap.GenerateRandomMap;
  FMap.PaintMap;
end;

procedure TFormMain.MenuItemSaveMapClick(Sender: TObject);
begin
  if not DirectoryExists(SaveDialog1.InitialDir) then
    SaveDialog1.InitialDir := ExtractFilePath(ParamStr(0));
  if SaveDialog1.Execute = False then
    Exit;
  FMap.SaveMap(SaveDialog1.FileName);
end;

procedure TFormMain.MenuItemLoadMapClick(Sender: TObject);
begin
  if not DirectoryExists(OpenDialog1.InitialDir) then
    OpenDialog1.InitialDir := ExtractFilePath(ParamStr(0));
  if OpenDialog1.Execute = False then
    Exit;
  FMap.LoadMap(OpenDialog1.FileName);
end;

procedure TFormMain.EditMapWidthChange(Sender: TObject);
var
  i: Integer;
  j: Integer;
begin
  if FIgnoreEvents = True then
    Exit;
  try
    i := SysUtils.StrToInt(EditMapWidth.Text);
    j := SysUtils.StrToInt(EditMapHeight.Text);
  except
    FIgnoreEvents := True;
    EditMapWidth.Text := SysUtils.IntToStr(FMap.Columns);
    EditMapHeight.Text := SysUtils.IntToStr(FMap.Rows);
    FIgnoreEvents := False;
    Exit;
  end;
  FMap.SetMapSize(i, j);
  MenuItemGenerateRandomMapClick(nil);
end;

procedure TFormMain.MenuItemExitClick(Sender: TObject);
begin
  Close;
end;

end.

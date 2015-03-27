unit DataClasses;

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
  JMC_FileUtils,
  JMC_Parts;

type

  TJMC_Map = class;
  TJMC_ResourceArray = class;

  TJMC_Tile = class(TObject)
  private
    FBitmap: Graphics.TBitmap;
    FTileName: string;
    FFileName: string;
    FTileID: Integer;
  public
    constructor Create;
    destructor Destroy; override;
    property Bitmap: Graphics.TBitmap read FBitmap;
    property TileName: string read FTileName write FTileName;
    property FileName: string read FFileName write FFileName;
    property TileID: Integer read FTileID write FTileID;
  end;

  TJMC_TileArray = class(TObject)
  private
    FTiles: Classes.TList;
    function GetCount: Integer;
    function GetTile(const Index: Integer): TJMC_Tile;
    function GetTileByName(const TileName: string): TJMC_Tile;
  public
    constructor Create;
    destructor Destroy; override;
    procedure Add(const Value: TJMC_Tile);
    procedure Clear;
    function GetTileByID(const TileID: Integer): TJMC_Tile;
    property Count: Integer read GetCount;
    property Tiles[const Index: Integer]: TJMC_Tile read GetTile;
    property TilesByName[const TileName: string]: TJMC_Tile read GetTileByName; default;
  end;

  TJMC_TileList = class(TObject)
  private
    FTiles: Classes.TList;
    function GetCount: Integer;
    function GetTile(const Index: Integer): TJMC_Tile;
    procedure SetCount(const Value: Integer);
    procedure SetTile(const Index: Integer; const Value: TJMC_Tile);
  public
    constructor Create;
    destructor Destroy; override;
    procedure Clear;
    property Count: Integer read GetCount write SetCount;
    property Tiles[const Index: Integer]: TJMC_Tile read GetTile write SetTile; default;
  end;

  TJMC_Resource = class(TObject)
  private
    FLocationX: Integer;
    FLocationY: Integer;
    FResourceID: Integer;
    FResourceName: string;
    FBitmap: Graphics.TBitmap;
    FMask: Graphics.TBitmap;
    FFileName: string;
    FOwner: TJMC_ResourceArray;
  public
    constructor Create(const Owner: TJMC_ResourceArray); virtual;
    destructor Destroy; override;
  public
    procedure LoadBitmap(const FileName: string);
  public
    property LocationX: Integer read FLocationX;
    property LocationY: Integer read FLocationY;
    property ResourceID: Integer read FResourceID;
    property ResourceName: string read FResourceName;
    property Bitmap: Graphics.TBitmap read FBitmap;
    property Mask: Graphics.TBitmap read FMask;
    property FileName: string read FFileName write FFileName;
    property Owner: TJMC_ResourceArray read FOwner;
  end;

  TJMC_ResourceArray = class(TObject)
  private
    FOwner: TJMC_Map;
    FResources: Classes.TList;
    function GetCount: Integer;
    function GetResource(const Index: Integer): TJMC_Resource;
    function GetResourceByName(const ResourceName: string): TJMC_Resource;
  public
    constructor Create(const Owner: TJMC_Map);
    destructor Destroy; override;
    procedure Add(const Value: TJMC_Resource);
    procedure Clear;
    function GetResourceByID(const ResourceID: Integer): TJMC_Resource;
    property Count: Integer read GetCount;
    property Owner: TJMC_Map read FOwner;
    property Resources[const Index: Integer]: TJMC_Resource read GetResource;
    property ResourcesByName[const ResourceName: string]: TJMC_Resource read GetResourceByName; default;
  end;

  TJMC_ResourceList = class(TObject)
  private
    FResources: Classes.TList;
    function GetCount: Integer;
    function GetResource(const Index: Integer): TJMC_Resource;
    procedure SetCount(const Value: Integer);
    procedure SetResource(const Index: Integer; const Value: TJMC_Resource);
  public
    constructor Create;
    destructor Destroy; override;
    procedure Clear;
    property Count: Integer read GetCount write SetCount;
    property Resources[const Index: Integer]: TJMC_Resource read GetResource write SetResource; default;
  end;

  TJMC_Player = class(TObject)
  private
    FLocationX: Integer;
    FLocationY: Integer;
    FPlayerID: Integer;
    FPlayerName: string;
  public
    constructor Create; virtual;
  public
    property LocationX: Integer read FLocationX;
    property LocationY: Integer read FLocationY;
    property PlayerID: Integer read FPlayerID;
    property PlayerName: string read FPlayerName write FPlayerName;
  end;

  TJMC_PlayerArray = class(TObject)
  private
    FPlayers: Classes.TList;
    function GetCount: Integer;
    function GetPlayer(const Index: Integer): TJMC_Player;
    function GetPlayerByName(const PlayerName: string): TJMC_Player;
  public
    constructor Create;
    destructor Destroy; override;
    procedure Add(const Value: TJMC_Player);
    procedure Clear;
    function GetPlayerByID(const PlayerID: Integer): TJMC_Player;
    property Count: Integer read GetCount;
    property Players[const Index: Integer]: TJMC_Player read GetPlayer;
    property PlayersByName[const PlayerName: string]: TJMC_Player read GetPlayerByName; default;
  end;

  TJMC_Map = class(TObject)
  private
    FBuffer: Graphics.TBitmap;
    FTiles: TJMC_TileArray;
    FGrid: TJMC_TileList;
    FColumns: Integer;
    FRows: Integer;
    FTileWidth: Integer;
    FTileHeight: Integer;
    FScaleFactor: Integer;
    FImage: ExtCtrls.TImage; // flip buffer to this
    FResources: TJMC_ResourceArray;
    FPlayers: TJMC_PlayerArray;
  private
    function GetCoord(const X, Y: Integer): TJMC_Tile;
    procedure SetCoord(const X, Y: Integer; const Value: TJMC_Tile);
  public
    constructor Create;
    destructor Destroy; override;
    procedure LoadTile(const TileID: Integer; const TileName: string; const FileName: string);
    procedure LoadTiles(const FileName: string);
    procedure SaveTiles(const FileName: string);
    procedure PaintMap;
    procedure FlipBuffer(const Buffer: Graphics.TBitmap);
    procedure DrawCursor(const i, j: Integer);
    procedure HighlightCoastline;
    procedure FillMap(const TileName: string);
    procedure SaveMap(const FileName: string);
    procedure LoadMap(const FileName: string);
    procedure GenerateRandomMap;
    procedure MoveIncrement;
    procedure SetImageSize;
    procedure SetMapSize(const Columns, Rows: Integer);
    procedure SetTileSize(const TileWidth, TileHeight: Integer);
  public
    property Buffer: Graphics.TBitmap read FBuffer;
    property Columns: Integer read FColumns write FColumns;
    property Rows: Integer read FRows write FRows;
    property TileWidth: Integer read FTileWidth write FTileWidth;
    property TileHeight: Integer read FTileHeight write FTileHeight;
    property ScaleFactor: Integer read FScaleFactor write FScaleFactor;
    property Image: ExtCtrls.TImage read FImage write FImage;
    property Coords[const X, Y: Integer]: TJMC_Tile read GetCoord write SetCoord;
    property Tiles: TJMC_TileArray read FTiles;
    property Resources: TJMC_ResourceArray read FResources;
    property Players: TJMC_PlayerArray read FPlayers;
  end;

  TJMC_Resource_Unit = class(TJMC_Resource)
  private
    FPlayer: TJMC_Player;
  private
    Dest: TPoint;
    Pos: TPoint;
  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  public
    function MoveIncrement: Boolean;
    procedure MovePosRect(const dx, dy: Integer);
  public
    property Player: TJMC_Player read FPlayer write FPlayer;
  end;

  TJMC_Resource_Unit_Settler = class(TJMC_Resource_Unit)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Unit_Warrior = class(TJMC_Resource_Unit)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement = class(TJMC_Resource)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_Zone = class(TJMC_Resource_Improvement)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_Zone_Residential = class(TJMC_Resource_Improvement_Zone)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_Zone_Industrial = class(TJMC_Resource_Improvement_Zone)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_Zone_Commercial = class(TJMC_Resource_Improvement_Zone)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_Zone_Farmland = class(TJMC_Resource_Improvement_Zone)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_Road = class(TJMC_Resource_Improvement)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_City = class(TJMC_Resource_Improvement)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_CityItem = class(TJMC_Resource_Improvement)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_Library = class(TJMC_Resource_Improvement_CityItem)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_MarketPlace = class(TJMC_Resource_Improvement_CityItem)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

  TJMC_Resource_Improvement_Granary = class(TJMC_Resource_Improvement_CityItem)
  private

  public
    constructor Create(const Owner: TJMC_ResourceArray); override;
  end;

const
  DIRECTIONS : array[0..1, 0..3] of Integer = ((0, 1, 0, -1), (-1, 0, 1, 0));
  { Row 0 = X Directions
    Row 1 = Y Directions
    Columns - Directions:
      0 = Up
      1 = Right
      2 = Down
      3 = Left}

function IntToBinStr(const Value: Integer): string;
function BinStrToInt(const Value: string): Integer;

implementation

function IntToBinStr(const Value: Integer): string;
begin
  Result := Chr(Lo(Value)) + Chr(Hi(Value));
end;

function BinStrToInt(const Value: string): Integer;
begin
  if Length(Value) <> 2 then
    Result := -1
  else
  begin
    Result := Ord(Value[2]) shl 8;
    Result := Result + Ord(Value[1]);
  end;
end;

{ TJMC_Tile }

constructor TJMC_Tile.Create;
begin
  FBitmap := Graphics.TBitmap.Create;
end;

destructor TJMC_Tile.Destroy;
begin
  FBitmap.Free;
  inherited;
end;

{ TJMC_TileArray }

procedure TJMC_TileArray.Add(const Value: TJMC_Tile);
begin
  FTiles.Add(Value);
end;

procedure TJMC_TileArray.Clear;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Tiles[i].Free;
  FTiles.Clear;
end;

constructor TJMC_TileArray.Create;
begin
  FTiles := Classes.TList.Create;
end;

destructor TJMC_TileArray.Destroy;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Tiles[i].Free;
  FTiles.Free;
  inherited;
end;

function TJMC_TileArray.GetCount: Integer;
begin
  Result := FTiles.Count;
end;

function TJMC_TileArray.GetTile(const Index: Integer): TJMC_Tile;
begin
  if (Index >= 0) and (Index < Count) then
    Result := FTiles[Index]
  else
    Result := nil;
end;

function TJMC_TileArray.GetTileByID(const TileID: Integer): TJMC_Tile;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    if Tiles[i].TileID = TileID then
    begin
      Result := Tiles[i];
      Exit;
    end;
  Result := nil;
end;

function TJMC_TileArray.GetTileByName(const TileName: string): TJMC_Tile;
var
  i: Integer;
  S: string;
begin
  S := UpperCase(TileName);
  for i := 0 to Count - 1 do
    if UpperCase(Tiles[i].TileName) = S then
    begin
      Result := Tiles[i];
      Exit;
    end;
  Result := nil;
end;

{ TJMC_TileList }

procedure TJMC_TileList.Clear;
begin
  FTiles.Clear;
end;

constructor TJMC_TileList.Create;
begin
  FTiles := Classes.TList.Create;
end;

destructor TJMC_TileList.Destroy;
begin
  FTiles.Free;
  inherited;
end;

function TJMC_TileList.GetCount: Integer;
begin
  Result := FTiles.Count;
end;

function TJMC_TileList.GetTile(const Index: Integer): TJMC_Tile;
begin
  if (Index >= 0) and (Index < Count) then
    Result := FTiles[Index]
  else
    Result := nil;
end;

procedure TJMC_TileList.SetCount(const Value: Integer);
begin
  if Value < 0 then
    Exit;
  FTiles.Count := Value;
end;

procedure TJMC_TileList.SetTile(const Index: Integer; const Value: TJMC_Tile);
begin
  if (Index >= 0) and (Index < Count) then
    FTiles[Index] := Value;
end;

{ TJMC_Resource }

constructor TJMC_Resource.Create(const Owner: TJMC_ResourceArray);
begin
  FOwner := Owner;
  FLocationX := -1;
  FLocationY := -1;
  FBitmap := Graphics.TBitmap.Create;
  FMask := Graphics.TBitmap.Create;
end;

destructor TJMC_Resource.Destroy;
begin
  FBitmap.Free;
  FMask.Free;
  inherited;
end;

procedure TJMC_Resource.LoadBitmap(const FileName: string);
var
  x: Integer;
  y: Integer;
begin
  if SysUtils.FileExists(FileName) = False then
  begin
    Dialogs.ShowMessage('Bitmap image file not found.');
    Exit;
  end;
  FFileName := FileName;
  FBitmap.LoadFromFile(FFileName);
  FMask.Assign(FBitmap);
  FMask.Mask(clFuchsia);
  FMask.Monochrome := True;
  for y := 0 to FBitmap.Height - 1 do
    for x := 0 to FBitmap.Width - 1 do
      if FBitmap.Canvas.Pixels[x, y] = clWhite then
        FBitmap.Canvas.Pixels[x, y] := clBlack;
end;

{ TJMC_ResourceArray }

procedure TJMC_ResourceArray.Add(const Value: TJMC_Resource);
begin
  FResources.Add(Value);
end;

procedure TJMC_ResourceArray.Clear;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Resources[i].Free;
  FResources.Clear;
end;

constructor TJMC_ResourceArray.Create;
begin
  FResources := Classes.TList.Create;
end;

destructor TJMC_ResourceArray.Destroy;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Resources[i].Free;
  FResources.Free;
  inherited;
end;

function TJMC_ResourceArray.GetCount: Integer;
begin
  Result := FResources.Count;
end;

function TJMC_ResourceArray.GetResource(const Index: Integer): TJMC_Resource;
begin
  if (Index >= 0) and (Index < Count) then
    Result := FResources[Index]
  else
    Result := nil;
end;

function TJMC_ResourceArray.GetResourceByID(const ResourceID: Integer): TJMC_Resource;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    if Resources[i].ResourceID = ResourceID then
    begin
      Result := Resources[i];
      Exit;
    end;
  Result := nil;
end;

function TJMC_ResourceArray.GetResourceByName(const ResourceName: string): TJMC_Resource;
var
  i: Integer;
  S: string;
begin
  S := UpperCase(ResourceName);
  for i := 0 to Count - 1 do
    if UpperCase(Resources[i].ResourceName) = S then
    begin
      Result := Resources[i];
      Exit;
    end;
  Result := nil;
end;

{ TJMC_ResourceList }

procedure TJMC_ResourceList.Clear;
begin
  FResources.Clear;
end;

constructor TJMC_ResourceList.Create;
begin
  FResources := Classes.TList.Create;
end;

destructor TJMC_ResourceList.Destroy;
begin
  FResources.Free;
  inherited;
end;

function TJMC_ResourceList.GetCount: Integer;
begin
  Result := FResources.Count;
end;

function TJMC_ResourceList.GetResource(const Index: Integer): TJMC_Resource;
begin
  if (Index >= 0) and (Index < Count) then
    Result := FResources[Index]
  else
    Result := nil;
end;

procedure TJMC_ResourceList.SetCount(const Value: Integer);
begin
  if Value < 0 then
    Exit;
  FResources.Count := Value;
end;

procedure TJMC_ResourceList.SetResource(const Index: Integer; const Value: TJMC_Resource);
begin
  if (Index >= 0) and (Index < Count) then
    FResources[Index] := Value;
end;

{ TJMC_Player }

constructor TJMC_Player.Create;
begin

end;

{ TJMC_PlayerArray }

procedure TJMC_PlayerArray.Add(const Value: TJMC_Player);
begin
  FPlayers.Add(Value);
end;

procedure TJMC_PlayerArray.Clear;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Players[i].Free;
  FPlayers.Clear;
end;

constructor TJMC_PlayerArray.Create;
begin
  FPlayers := Classes.TList.Create;
end;

destructor TJMC_PlayerArray.Destroy;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Players[i].Free;
  FPlayers.Free;
  inherited;
end;

function TJMC_PlayerArray.GetCount: Integer;
begin
  Result := FPlayers.Count;
end;

function TJMC_PlayerArray.GetPlayer(const Index: Integer): TJMC_Player;
begin
  if (Index >= 0) and (Index < Count) then
    Result := FPlayers[Index]
  else
    Result := nil;
end;

function TJMC_PlayerArray.GetPlayerByID(const PlayerID: Integer): TJMC_Player;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    if Players[i].PlayerID = PlayerID then
    begin
      Result := Players[i];
      Exit;
    end;
  Result := nil;
end;

function TJMC_PlayerArray.GetPlayerByName(const PlayerName: string): TJMC_Player;
var
  i: Integer;
  S: string;
begin
  S := UpperCase(PlayerName);
  for i := 0 to Count - 1 do
    if UpperCase(Players[i].PlayerName) = S then
    begin
      Result := Players[i];
      Exit;
    end;
  Result := nil;
end;

{ TJMC_Map }

constructor TJMC_Map.Create;
begin
  FScaleFactor := 1;
  FBuffer := Graphics.TBitmap.Create;
  FTiles := TJMC_TileArray.Create;
  FGrid := TJMC_TileList.Create;
  FResources := TJMC_ResourceArray.Create(Self);
  FPlayers := TJMC_PlayerArray.Create;
end;

destructor TJMC_Map.Destroy;
begin
  FResources.Free;
  FPlayers.Free;
  FGrid.Free;
  FTiles.Free;
  FBuffer.Free;
  inherited;
end;

procedure TJMC_Map.DrawCursor(const i, j: Integer);
var
  R: Windows.TRect;
  Buf: Graphics.TBitmap;
begin
  Buf := Graphics.TBitmap.Create;
  try
    R := Classes.Rect(i * FTileWidth, j * FTileHeight, (i + 1) * FTileWidth, (j + 1) * FTileHeight);
    Buf.Canvas.Brush.Style := bsClear;
    Buf.Canvas.Pen.Color := clRed;
    Buf.Width := FBuffer.Width;
    Buf.Height := FBuffer.Height;
    Buf.Canvas.Draw(0, 0, FBuffer);
    Buf.Canvas.Rectangle(R);
    FlipBuffer(Buf);
  finally
    Buf.Free;
  end;
end;

procedure TJMC_Map.FillMap(const TileName: string);
var
  x: Integer;
  y: Integer;
  Tile: TJMC_Tile;
begin
  Tile := FTiles[TileName];
  for y := 0 to FRows - 1 do
    for x := 0 to FColumns - 1 do
      Coords[x, y] := Tile;
end;

procedure TJMC_Map.FlipBuffer(const Buffer: Graphics.TBitmap);
begin
  FImage.Canvas.Draw(0, 0, Buffer);
end;

procedure TJMC_Map.GenerateRandomMap;
var
  LandmassCount: Integer;
  LandmassSize: Integer;
  i: Integer;
  x: Integer;
  y: Integer;
  x1: Integer;
  y1: Integer;
  x2: Integer;
  y2: Integer;
  d: Integer;
  d1: Integer;
  n: Integer;
  LandTile: TJMC_Tile;
begin
  if FTiles.Count = 0 then
  begin
    ShowMessage('Unable to generate random map because there are no tiles available.');
    Exit;
  end;
  if (FColumns <= 0) or (FRows <= 0) then
  begin
    ShowMessage('Unable to generate random map because the map size is invalid.');
    Exit;
  end;
  LandTile := FTiles['land'];
  if Assigned(LandTile) = False then
  begin
    ShowMessage('Unable to generate random map because the "land" tile could not be found.');
    Exit;
  end;
  Randomize;
  LandmassCount := 6;
  LandmassSize := 70;
  for i := 1 to LandmassCount do
  begin
    Randomize;
    n := 0;
    x := Random(FColumns);
    y := Random(FRows);
    Coords[x, y] := LandTile;
    Inc(n);
    x1 := x;
    y1 := y;
    d := Random(4);
    while n < LandmassSize do
    begin
      repeat
        repeat
          d1 := Random(4);
        until d1 <> d;
        d := d1;
        x2 := x1 + DIRECTIONS[0, d];
        y2 := y1 + DIRECTIONS[1, d];
      until (x2 >= 0) and (y2 >= 0) and (x2 < FColumns) and (y2 < FRows);
      x1 := x2;
      y1 := y2;
      if Coords[x1, y1] <> LandTile then
      begin
        Coords[x1, y1] := LandTile;
        Inc(n);
      end;
      if Random(200) = 0 then // higher number in brackets makes landmass more spread out
      begin
        x1 := x;
        y1 := y;
      end;
    end;
  end;
  // fill in any isolated inland 1x1 lakes
  for y := 0 to FRows - 1 do
    for x := 0 to FColumns - 1 do
      if Coords[x, y].TileName = 'water' then
      begin
        n := 0;
        for i := 0 to 3 do
          if Coords[x + DIRECTIONS[0, i], y + DIRECTIONS[1, i]] = LandTile then
            Inc(n);
        if n = 4 then
          Coords[x, y] := LandTile;
      end;
end;

function TJMC_Map.GetCoord(const X, Y: Integer): TJMC_Tile;
var
  i: Integer;
begin
  if (FTiles.Count = 0) or (X < 0) or (Y < 0) or (X >= FColumns) or (Y >= FRows) then
  begin
    Result := nil;
    Exit;
  end;
  i := X + Y * FColumns;
  if (i >= 0) and (i < FGrid.Count) then
    Result := FGrid[i]
  else
    Result := nil;
end;

procedure TJMC_Map.HighlightCoastline;
var
  x: Integer;
  y: Integer;
  n: Integer;
  i: Integer;
  j: Integer;
  Tile: TJMC_Tile;
  Test: TJMC_Tile;
begin
  FBuffer.Canvas.Pen.Color := clYellow;
  for y := 0 to FRows - 1 do
    for x := 0 to FColumns - 1 do
    begin
      Tile := Coords[x, y];
      if Assigned(Tile) = False then
        Continue;
      if Tile.TileName <> 'land' then
        Continue;
      // tile is land
      for n := 0 to 3 do
      begin
        Test := Coords[x + DIRECTIONS[0, n], y + DIRECTIONS[1, n]];
        if Assigned(Test) = False then
          Continue;
        if Test.TileName = 'land' then
          Continue;
        i := x * FTileWidth;
        j := y * FTileHeight;
        case n of
          0: // UP
            begin
              FBuffer.Canvas.MoveTo(i, j - 1);
              FBuffer.Canvas.LineTo(i + FTileWidth, j - 1);
            end;
          1: // RIGHT
            begin
              FBuffer.Canvas.MoveTo(i + FTileWidth, j);
              FBuffer.Canvas.LineTo(i + FTileWidth, j + FTileHeight);
            end;
          2: // DOWN
            begin
              FBuffer.Canvas.MoveTo(i, j + FTileHeight);
              FBuffer.Canvas.LineTo(i + FTileWidth, j + FTileHeight);
            end;
          3: // LEFT
            begin
              FBuffer.Canvas.MoveTo(i - 1, j);
              FBuffer.Canvas.LineTo(i - 1, j + FTileHeight);
            end;
        end;
      end;
    end;
end;

procedure TJMC_Map.LoadMap(const FileName: string);
var
  S: string;
  i: Integer;
begin
  if JMC_FileUtils.FileToStr(FileName, S) = False then
    ShowMessage(Format('Error loading map file "%s".', [FileName]));
  SetMapSize(BinStrToInt(Copy(S, 1, 2)), BinStrToInt(Copy(S, 3, 2)));
  for i := 0 to FGrid.Count - 1 do
    FGrid[i] := FTiles.GetTileByID(BinStrToInt(Copy(S, (i + 2) * 2 + 1, 2)));
  PaintMap;
end;

procedure TJMC_Map.LoadTile(const TileID: Integer; const TileName: string; const FileName: string);
var
  Tile: TJMC_Tile;
  Img: ExtCtrls.TImage;
begin
  if FileExists(FileName) = False then
  begin
    ShowMessage(Format('File "%s" not found.', [FileName]));
    Exit;
  end;
  Tile := TJMC_Tile.Create;
  try
    Tile.Bitmap.LoadFromFile(FileName);
    if (Tile.Bitmap.Width <> FTileWidth) or (Tile.Bitmap.Height <> FTileHeight) then
    begin
      Img := ExtCtrls.TImage.Create(nil);
      try
        Img.Picture.Assign(Tile.Bitmap);
        Img.Width := Tile.Bitmap.Width;
        Img.Height := Tile.Bitmap.Height;
        Img.Proportional := True;
        Img.Stretch := True;
        Img.Width := FTileWidth;
        Img.Height := FTileHeight;
        Tile.Bitmap.Width := FTileWidth;
        Tile.Bitmap.Height := FTileHeight;
        Tile.Bitmap.Canvas.StretchDraw(Img.ClientRect, Img.Picture.Bitmap);
      finally
        Img.Free;
      end;
      if (Tile.Bitmap.Width <> FTileWidth) or (Tile.Bitmap.Height <> FTileHeight) then
      begin
        ShowMessage(Format('Bitmap image in file "%s" is not the required size.' + ^M + ^M + 'Tile images are required to be %d pixels wide by %d pixels high.', [FileName, FTileWidth, FTileHeight]));
        Tile.Free;
        Exit;
      end;
    end;
    Tile.TileName := TileName;
    Tile.TileID := TileID;
    Tile.FileName := FileName;
    FTiles.Add(Tile);
  except
    ShowMessage(Format('File "%s" is not a valid bitmap image.', [FileName]));
    Tile.Free;
    Exit;
  end;
end;

procedure TJMC_Map.LoadTiles(const FileName: string);
var
  S: string;
  Line: string;
  i: Integer;
  procedure Add;
  var
    sTileID: string;
    iTileID: Integer;
    TileName: string;
    FileName: string;
  begin
    // 1|water|water.bmp
    iTileID := -1;
    sTileID := JMC_Parts.ReadPart(Line, 1, '|');
    TileName := JMC_Parts.ReadPart(Line, 2, '|');
    FileName := JMC_Parts.ReadPart(Line, 3, '|');
    if SysUtils.FileExists(FileName) = False then
      FileName := ExtractFilePath(ParamStr(0)) + FileName;
    if SysUtils.FileExists(FileName) = False then
      ShowMessage(SysUtils.Format('Tile bitmap file "%s" not found.', [FileName]))
    else
    begin
      try
        iTileID := SysUtils.StrToInt(sTileID);
      except
        ShowMessage('Tile ID StrToInt conversion error.');
      end;
      LoadTile(iTileID, TileName, FileName);
    end;
  end;
begin
  if JMC_FileUtils.FileToStr(FileName, S) = False then
    ShowMessage(SysUtils.Format('Error loading tiles file "%s".', [FileName]));
  i := 1;
  Line := '';
  while i <= Length(S) do
  begin
    case Ord(S[i]) of
      10, 13:
        begin
          Add;
          Line := '';
          if (Ord(S[i]) = 13) and (i < Length(S)) then
            if Ord(S[i + 1]) = 10 then
              Inc(i);
        end;
    else
      Line := Line + S[i];
    end;
    Inc(i);
  end;
  Add;
  PaintMap;
end;

procedure TJMC_Map.MoveIncrement;
var
  i: Integer;
begin
   
end;

procedure TJMC_Map.PaintMap;
var
  x: Integer;
  y: Integer;
  Tile: TJMC_Tile;
begin
  SetImageSize;
  FBuffer.Width := FImage.Width;
  FBuffer.Height := FImage.Height;
  FBuffer.Canvas.Brush.Color := clBlack;
  FBuffer.Canvas.FillRect(Rect(0, 0, FBuffer.Width, FBuffer.Height));
  for y := 0 to FRows - 1 do
    for x := 0 to FColumns - 1 do
    begin
      Tile := Coords[x, y];
      if Assigned(Tile) then
        FBuffer.Canvas.Draw(x * FTileWidth, y * FTileHeight, Tile.Bitmap);
    end;
  HighlightCoastline;
  FlipBuffer(FBuffer);
end;

procedure TJMC_Map.SaveMap(const FileName: string);
var
  S: string;
  i: Integer;
begin
  S := IntToBinStr(FColumns) + IntToBinStr(FRows);
  for i := 0 to FGrid.Count - 1 do
    if FGrid[i] <> nil then
      S := S + IntToBinStr(FGrid[i].TileID)
    else
      S := S + IntToBinStr(255);
  if JMC_FileUtils.StrToFile(FileName, S) = False then
    ShowMessage(Format('Error saving map file "%s".', [FileName]));
end;

procedure TJMC_Map.SaveTiles(const FileName: string);
var
  S: string;
  i: Integer;
begin
  if FTiles.Count > 0 then
  begin
    S := IntToStr(FTiles.Tiles[0].TileID) + '|' + FTiles.Tiles[0].TileName + '|' + FTiles.Tiles[0].FileName;
    for i := 1 to FTiles.Count - 1 do
      S := S + #13#10 + IntToStr(FTiles.Tiles[i].TileID) + '|' + FTiles.Tiles[i].TileName + '|' + FTiles.Tiles[i].FileName;
  end;
  if JMC_FileUtils.StrToFile(FileName, S) = False then
    ShowMessage(Format('Error saving tiles file "%s".', [FileName]));
end;

procedure TJMC_Map.SetCoord(const X, Y: Integer; const Value: TJMC_Tile);
var
  i: Integer;
begin
  i := X + Y * FColumns;
  if (i >= 0) and (i < FGrid.Count) then
    FGrid[i] := Value;
end;

procedure TJMC_Map.SetImageSize;
begin
  if Assigned(FImage) then
  begin
    FImage.Width := FColumns * FTileWidth;
    FImage.Height := FRows * FTileHeight;
    FImage.Picture.Bitmap.Width := FImage.Width;
    FImage.Picture.Bitmap.Height := FImage.Height;
  end;
end;

procedure TJMC_Map.SetMapSize(const Columns, Rows: Integer);
begin
  if (Columns < 0) or (Rows < 0) then
    Exit;
  FGrid.Clear;
  FColumns := Columns;
  FRows := Rows;
  FGrid.Count := Columns * Rows;
  SetImageSize;
end;

procedure TJMC_Map.SetTileSize(const TileWidth, TileHeight: Integer);
begin
  if (TileWidth < 0) or (TileHeight < 0) then
    Exit;
  FTiles.Clear;
  FTileWidth := TileWidth * FScaleFactor;
  FTileHeight := TileHeight * FScaleFactor;
  SetImageSize;
end;

{ TJMC_Resource_Unit }

constructor TJMC_Resource_Unit.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

function TJMC_Resource_Unit.MoveIncrement: Boolean;
var
  L: Real;
  bx: Integer;
  by: Integer;
  dx: Integer;
  dy: Integer;
  Fill: TRect;
begin
  Result := False;
  bx := Abs(Pos.x - Dest.x);
  by := Abs(Pos.y - Dest.y);
  L := Sqrt(Sqr(bx) + Sqr(by));
  Fill := Rect(Pos.x, Pos.y, Pos.x + 10, Pos.y + 10);
  if L < 3 then
  begin
    with FOwner.Owner.Image.Canvas do
    begin
      FillRect(Fill);
      CopyMode := cmSrcAnd;
      Draw(Pos.x, Pos.y, FMask);
      CopyMode := cmSrcPaint;
      Draw(Pos.x, Pos.y, FBitmap);
    end;
    Result := True;
    Exit;
  end;
  dx := Round((5 * bx) / L);
  dy := Round((5 * by) / L);
  if Dest.x < Pos.x then
    dx := -dx;
  if Dest.y < Pos.y then
    dy := -dy;
  MovePosRect(dx, dy);
  with FOwner.Owner.Image.Canvas do
  begin
    FillRect(Fill);
    CopyMode := cmSrcAnd;
    Draw(Pos.x, Pos.y, FMask);
    CopyMode := cmSrcPaint;
    Draw(Pos.x, Pos.y, FBitmap);
  end;
end;

procedure TJMC_Resource_Unit.MovePosRect(const dx, dy: Integer);
begin
  Pos := Point(Pos.x + dx, Pos.y + dy);
end;

{ TJMC_Resource_Unit_Settler }

constructor TJMC_Resource_Unit_Settler.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Unit_Warrior }

constructor TJMC_Resource_Unit_Warrior.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement }

constructor TJMC_Resource_Improvement.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_Zone }

constructor TJMC_Resource_Improvement_Zone.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_Zone_Residential }

constructor TJMC_Resource_Improvement_Zone_Residential.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_Zone_Industrial }

constructor TJMC_Resource_Improvement_Zone_Industrial.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_Zone_Commercial }

constructor TJMC_Resource_Improvement_Zone_Commercial.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_Zone_Farmland }

constructor TJMC_Resource_Improvement_Zone_Farmland.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_Road }

constructor TJMC_Resource_Improvement_Road.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_City }

constructor TJMC_Resource_Improvement_City.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_CityItem }

constructor TJMC_Resource_Improvement_CityItem.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_Library }

constructor TJMC_Resource_Improvement_Library.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_MarketPlace }

constructor TJMC_Resource_Improvement_MarketPlace.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

{ TJMC_Resource_Improvement_Granary }

constructor TJMC_Resource_Improvement_Granary.Create(const Owner: TJMC_ResourceArray);
begin
  inherited;

end;

end.
unit Main;

interface

uses
  Windows,
  SysUtils,
  Classes,
  Graphics,
  Controls,
  Forms,
  Dialogs,
  ExtCtrls,
  JMC_PathFinding;

type

  TMainForm = class(TForm)
    MapImage: TImage;
    procedure FormCreate(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
    procedure MapImageMouseDown(Sender: TObject; Button: TMouseButton; Shift: TShiftState; X, Y: Integer);
  private
    FMap: Graphics.TBitmap;
    FObstacleMap: TObstacleMap;
    FPath: TPath;
    FLocation: TPoint;
    FPathIndex: Integer;
    procedure PaintDude;
    procedure ClearDude;
    procedure PaintPath;
  end;

var
  MainForm: TMainForm;

implementation

{$R *.DFM}

procedure TMainForm.ClearDude;
var
  R: TRect;
  i, j: Integer;
begin
  i := FLocation.x;
  j := FLocation.y;
  R := Rect(i - 3, j - 3, i + 4, j + 4);
  MapImage.Canvas.CopyRect(R, FMap.Canvas, R);
end;

procedure TMainForm.FormCreate(Sender: TObject);
var
  i, j: Integer;
begin
  MapImage.Picture.LoadFromFile('ObstacleMap.bmp'); // The image painted to the screen isn't usually the monochrome obstacle map (usually nice colourful terrain eye candy).
  Position := poScreenCenter;
  SetLength(FObstacleMap, MapImage.Height, MapImage.Width);
  for j := 0 to MapImage.Height - 1 do
    for i := 0 to MapImage.Width - 1 do
      FObstacleMap[j, i] := MapImage.Canvas.Pixels[i, j] = clBlack;
  Randomize;
  i := Random(MapImage.Width);
  j := Random(MapImage.Height);
  while FObstacleMap[j, i] = True do
  begin
    i := Random(MapImage.Width);
    j := Random(MapImage.Height);
  end;
  FLocation := Point(i, j);
  FMap := Graphics.TBitmap.Create;
  FMap.Assign(MapImage.Picture.Bitmap);
  MapWidth := MapImage.Width;
  MapHeight := MapImage.Height;
  PaintDude;
end;

procedure TMainForm.FormDestroy(Sender: TObject);
begin
  SetLength(FObstacleMap, 0, 0);
  SetLength(FPath, 0);
  FMap.Free;
end;

procedure TMainForm.MapImageMouseDown(Sender: TObject; Button: TMouseButton; Shift: TShiftState; X, Y: Integer);
begin
  if FindPath(@FPath, FLocation, Point(X, Y), @FObstacleMap) then
  begin
    MapImage.Picture.Bitmap.Assign(FMap);
    PaintPath;
    FPathIndex := 0;
    while FPathIndex < Length(FPath) - 1 do
    begin
      ClearDude;
      Inc(FPathIndex);
      FLocation := FPath[FPathIndex].Location;
      PaintPath;
      PaintDude;
      Application.ProcessMessages;
      if Application.Terminated then
        Exit;
    end;
    MapImage.Picture.Bitmap.Assign(FMap);
    PaintDude;
  end;
end;

procedure TMainForm.PaintDude;
var
  i, j: Integer;
begin
  i := FLocation.x;
  j := FLocation.y;
  with MapImage.Canvas do
  begin
    Pixels[i, j] := clBlue;
    Pen.Color := clBlue;
    MoveTo(i - 3, j - 3);
    LineTo(i + 3, j - 3);
    LineTo(i + 3, j + 3);
    LineTo(i - 3, j + 3);
    LineTo(i - 3, j - 3);
  end;
end;

procedure TMainForm.PaintPath;
var
  i: Integer;
begin
  for i := 0 to Length(FPath) - 1 do
    MapImage.Canvas.Pixels[FPath[i].Location.x, FPath[i].Location.y] := clRed;
end;

end.
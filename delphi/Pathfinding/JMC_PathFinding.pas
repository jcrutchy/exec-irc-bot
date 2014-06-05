{ While I wrote this unit myself, a pathfinding algorithm contained in a unit
  called 'DXPath' developed by John Christian Lonningdal in 1996 was referred
  to for initial understanding. After reading through and understanding this
  person's algorithm, I thought there was some parts that were unneccessary,
  so I went about designing this algorithm from scratch. It uses the same
  general principle as 'DXPath', but is shorter and more understandable. It
  still requires work though.

  POSSIBLE IMPROVEMENTS:

  1. Make it so that the legs of the path can be any angle (instead of just
     the 45 degrees currently used). }

unit JMC_PathFinding;

interface

uses
  Windows,
  SysUtils,
  Classes,
  Dialogs;

type

  TObstacleMap = array of array of Boolean; // True => Obstacle

  PObstacleMap = ^TObstacleMap;

  TPathPoint = record
    Location : TPoint;
    Direction : Integer;
  end;

  TPath = array of TPathPoint;

  PPath = ^TPath;

var
  MapWidth, MapHeight : Integer; // These are the dimensions of the map that units can move on, which doesn't have to be the same as the dimensions of the terrain map (the FindPath function doesn't have to rely on the terrain map in any way).

function FindPath(const Path : PPath; const Start, Finish : TPoint; const ObstacleMap : PObstacleMap) : Boolean;

implementation

const
  Directions : array[0..1, 0..7] of Integer = ((0, 1, 0, -1, -1, 1, -1, 1), (-1, 0, 1, 0, -1, -1, 1, 1));
  { Row 0 = X Directions
    Row 1 = Y Directions
    Columns - Directions:
      0 = Up
      1 = Right
      2 = Down
      3 = Left
      4 = Up & Left
      5 = Up & Right
      6 = Down & Left
      7 = Down & Right
      255 = None }

function FindPath(const Path : PPath; const Start, Finish : TPoint; const ObstacleMap : PObstacleMap) : Boolean;
// This algorithm will find the most direct path between the finish and start locations, if one exists.
// Returns true if a path is found, otherwise returns false.
var
  Direction, LocationsCount, PathLength, LocationIndex, x, y : Integer;
  DirectionMap : array of array of Integer;
  Locations : array of TPoint;
  InversePath : TPath;
  CurrentLocation : TPoint;
begin
  if (Start.x < 0) or (Start.x >= MapWidth) or (Finish.x < 0) or (Finish.x >= MapWidth) or (Start.y < 0) or (Start.y >= MapHeight) or (Finish.y < 0) or (Finish.y >= MapHeight) then
  begin
    Result := False;
    ShowMessage('(Start.x < 0) or (Start.x >= MapWidth) or (Finish.x < 0) or (Finish.x >= MapWidth) or (Start.y < 0) or (Start.y >= MapHeight) or (Finish.y < 0) or (Finish.y >= MapHeight)');
    Exit;
  end;
  if Length(ObstacleMap^) <> MapHeight then
  begin
    Result := False;
    ShowMessage('Length(ObstacleMap) <> MapHeight');
    Exit;
  end;
  if Length(ObstacleMap^[0]) <> MapWidth then
  begin
    Result := False;
    ShowMessage('Length(ObstacleMap[0]) <> MapWidth');
    Exit;
  end;
  if ObstacleMap^[Start.y, Start.x] or ObstacleMap^[Finish.y, Finish.x] then
  begin
    Result := False;
    ShowMessage('ObstacleMap^[Start.y, Start.x] or ObstacleMap^[Finish.y, Finish.x]');
    Exit;
  end;
  // Initialize the direction map with 255 (no direction).
  SetLength(DirectionMap, MapHeight, MapWidth);
  for y := 0 to MapHeight - 1 do
    for x := 0 to MapWidth - 1 do
      DirectionMap[y, x] := 255;
  LocationsCount := 0;
  LocationIndex := -1;
  CurrentLocation := Start;
  repeat
    // Test for traversable locations in all directions around the current location.
    for Direction := 0 to 7 do
    begin
      x := CurrentLocation.x + Directions[0, Direction];
      y := CurrentLocation.y + Directions[1, Direction];
      // If the point at (x, y) is traversable, add it to the locations array if it hasn't already been added, and add the direction relative to the current location to the direction map.
      if (x >= 0) and (y >= 0) and (x < MapWidth) and (y < MapHeight) then
        if (not ObstacleMap^[y, x]) and (DirectionMap[y, x] = 255) then
        begin
          Inc(LocationsCount);
          SetLength(Locations, LocationsCount);
          Locations[LocationsCount - 1] := Point(x, y);
          DirectionMap[y, x] := Direction;
        end;
    end;
    // The current location has been fully tested. Move on to the next traversable location stored in the locations array.
    Inc(LocationIndex);
    if LocationIndex >= Length(Locations) then
    begin
      SetLength(DirectionMap, 0);
      SetLength(Locations, 0);
      SetLength(InversePath, 0);
      Result := False;
      ShowMessage('LocationIndex >= Length(Locations)');
      Exit;
    end;
    CurrentLocation := Locations[LocationIndex];
    // If the current location is the same as the finish location, a path has been found (break from the searching loop).
  until (CurrentLocation.x = Finish.x) and (CurrentLocation.y = Finish.y);
  PathLength := 1;
  SetLength(InversePath, PathLength);
  InversePath[0].Location := CurrentLocation;
  InversePath[0].Direction := DirectionMap[CurrentLocation.y, CurrentLocation.x];
  // Start from the finish and work back to the start, following the inverted directions and adding locations as you go.
  repeat
    Direction := DirectionMap[CurrentLocation.y, CurrentLocation.x];
    // To invert the direction, subtract the ordinal in the directions array instead of adding it.
    CurrentLocation.x := CurrentLocation.x - Directions[0, Direction];
    CurrentLocation.y := CurrentLocation.y - Directions[1, Direction];
    Inc(PathLength);
    SetLength(InversePath, PathLength);
    InversePath[PathLength - 1].Location := CurrentLocation;
    InversePath[PathLength - 1].Direction := Direction;
    // When the start location is reached, break from the loop.
  until (CurrentLocation.x = Start.x) and (CurrentLocation.y = Start.y);
  SetLength(Path^, PathLength);
  // Copy the points from InversePath into Path in the opposite order.
  y := PathLength - 1;
  for x := 0 to PathLength - 1 do
  begin
    Path^[x] := InversePath[y];
    Dec(y);
  end;
  // Free memory from temporary arrays.
  SetLength(DirectionMap, 0);
  SetLength(Locations, 0);
  SetLength(InversePath, 0);
  // Path has been successfully found (and is stored in the array that Path points to).
  Result := True;
end;

end.
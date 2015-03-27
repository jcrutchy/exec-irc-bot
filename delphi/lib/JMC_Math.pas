unit JMC_Math;

interface

uses
  Math;

function JMC_ArcTan2(const Opposite: Double; const Adjacent: Double): Double;

implementation

function JMC_ArcTan2(const Opposite: Double; const Adjacent: Double): Double;
begin
  Result := 0;
  if (Opposite = 0) or (Adjacent = 0) then
  begin
    if (Opposite = 0) and (Adjacent = 0) then
      Result := 0
    else
      if Opposite = 0 then
        if Adjacent < 0 then
          Result := Pi
        else
          Result := 0;
      if Adjacent = 0 then
        if Opposite < 0 then
          Result := -Pi / 2
        else
          Result := Pi / 2;
  end
  else
    Result := ArcTan2(Opposite, Adjacent);
end;

end.
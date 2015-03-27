unit JMC_Graphics;

interface

uses
  Windows,
  Graphics,
  Types,
  Controls,
  Messages,
  Classes,
  Printers,
  Math,
  Dialogs,
  SysUtils;

const
  MAXPIXELCOUNT = 32768;

type

  pRGBArray = ^TRGBArray;
  TRGBArray = array[0..MAXPIXELCOUNT - 1] of TRGBTriple;

procedure GradientFill_Vert(const Buffer: Graphics.TBitmap; const TopColor, BottomColor: TColor);
procedure GradientFill_Horz(const Buffer: Graphics.TBitmap; const LeftColor, RightColor: TColor);
function NegateColor(const Value: TColor): TColor;
procedure NegateBitmap(const Value: Graphics.TBitmap);
procedure PointBox(const Canvas: TCanvas; const X, Y: Integer; const BoxSize: Integer = 5);
procedure AngleTextOut(const Canvas: TCanvas; const Text: string; X, Y, Angle: Integer);
procedure RotateBitmap(const Bitmap: Graphics.TBitmap; const AngleDegrees: Double);
procedure PrintAngleTextOut(const Text: string; X, Y, Angle: Integer);
procedure Rotate(var X, Y: Double; const Angle: Double);
procedure DrawArrowLine(const Canvas: TCanvas; const Count, X1, Y1, X2, Y2, ArrowLength, HeadLength, HeadWidth: Integer);
procedure DrawArrow(const Canvas: TCanvas; const X1, Y1, X2, Y2, ArrowLength, HeadLength, HeadWidth: Integer);

implementation

// Color := Windows.RGB(0, 87, 36);

procedure GradientFill_Vert(const Buffer: Graphics.TBitmap; const TopColor, BottomColor: TColor);
var
  y: Integer;
  dR, dG, dB: Double;
  R, G, B: Byte;
begin
  with Buffer do
  begin
    dR := (GetRValue(BottomColor) - GetRValue(TopColor)) / Height;
    dG := (GetGValue(BottomColor) - GetGValue(TopColor)) / Height;
    dB := (GetBValue(BottomColor) - GetBValue(TopColor)) / Height;
    with Canvas do
    begin
      Pen.Color := TopColor;
      for y := 0 to Height do
      begin
        R := Round(GetRValue(TopColor) + y * dR);
        G := Round(GetGValue(TopColor) + y * dG);
        B := Round(GetBValue(TopColor) + y * dB);
        Pen.Color := RGB(R, G, B);
        MoveTo(0, y);
        LineTo(Width, y);
      end;
    end;
  end;
end;

procedure GradientFill_Horz(const Buffer: Graphics.TBitmap; const LeftColor, RightColor: TColor);
var
  x: Integer;
  dR, dG, dB: Double;
  R, G, B: Byte;
begin
  with Buffer do
  begin
    dR := (GetRValue(RightColor) - GetRValue(LeftColor)) / Width;
    dG := (GetGValue(RightColor) - GetGValue(LeftColor)) / Width;
    dB := (GetBValue(RightColor) - GetBValue(LeftColor)) / Width;
    with Canvas do
    begin
      Pen.Color := LeftColor;
      for x := 0 to Width do
      begin
        R := Round(GetRValue(LeftColor) + x * dR);
        G := Round(GetGValue(LeftColor) + x * dG);
        B := Round(GetBValue(LeftColor) + x * dB);
        Pen.Color := RGB(R, G, B);
        MoveTo(x, 0);
        LineTo(x, Height);
      end;
    end;
  end;
end;

function NegateColor(const Value: TColor): TColor;
begin
  Result := RGB(255 - GetRValue(Value), 255 - GetGValue(Value), 255 - GetBValue(Value));
end;

procedure NegateBitmap(const Value: Graphics.TBitmap);
begin
  //Value.PixelFormat := pf24bit;
  InvertRect(Value.Canvas.Handle, Types.Rect(0, 0, Value.Width, Value.Height));
end;

procedure PointBox(const Canvas: TCanvas; const X, Y: Integer; const BoxSize: Integer = 5);
var
  i: Integer;
  OriginalPenWidth: Integer;
begin
  if BoxSize < 3 then
    Exit;
  i := (BoxSize - 1) div 2;
  with Canvas do
  begin
    OriginalPenWidth := Pen.Width;
    Pen.Width := 1;
    MoveTo(X - i, Y - i);
    LineTo(X + i, Y - i);
    MoveTo(X + i, Y - i);
    LineTo(X + i, Y + i);
    MoveTo(X + i, Y + i);
    LineTo(X - i, Y + i);
    MoveTo(X - i, Y + i);
    LineTo(X - i, Y - i);
    Pixels[X, Y] := Pen.Color;
    Pen.Width := OriginalPenWidth;
    MoveTo(X, Y);
  end;
end;

procedure RotateBitmap(const Bitmap: Graphics.TBitmap; const AngleDegrees: Double);
// http://www.efg2.com/Lab/ImageProcessing/RotateScanline.htm
var
  Buffer: Graphics.TBitmap;
  CosTheta: Double;
  i:  Integer;
  iRotationAxis: Integer;
  iOriginal: Integer;
  iPrime: Integer;
  iPrimeRotated: Integer;
  j: Integer;
  jRotationAxis: Integer;
  jOriginal: Integer;
  jPrime: Integer;
  jPrimeRotated: Integer;
  RowOriginal: pRGBArray;
  RowRotated: pRGBArray;
  SinTheta: Double;
  Theta: Double;
begin
  Buffer := Graphics.TBitmap.Create;
  try
    Buffer.Width := Bitmap.Width;
    Buffer.Height := Bitmap.Height;
    Buffer.PixelFormat := pf24bit;
    Theta := AngleDegrees * Pi / 180;
    SinTheta := Sin(Theta);
    CosTheta := Cos(Theta);
    iRotationAxis := Buffer.Width div 2;
    jRotationAxis := Buffer.Height div 2;
    for j := Buffer.Height - 1 DownTo 0 Do
    begin
      RowRotated := Buffer.ScanLine[j];
      jPrime := 2 * (j - jRotationAxis) + 1;
      for i := Buffer.Width - 1 DownTo 0 Do
      begin
        iPrime := 2 * (i - iRotationAxis) + 1;
        iPrimeRotated := Round(iPrime * CosTheta - jPrime * SinTheta);
        jPrimeRotated := Round(iPrime * SinTheta + jPrime * CosTheta);
        iOriginal := (iPrimeRotated - 1) div 2 + iRotationAxis;
        jOriginal := (jPrimeRotated - 1) div 2 + jRotationAxis;
        if (iOriginal >= 0) and (iOriginal <= Bitmap.Width - 1) and (jOriginal >= 0) and (jOriginal <= Bitmap.Height - 1) then
        begin
          RowOriginal := Bitmap.ScanLine[jOriginal];
          RowRotated[i] := RowOriginal[iOriginal];
        end
        else
        begin
          RowRotated[i].rgbtBlue := 255;
          RowRotated[i].rgbtGreen := 0;
          RowRotated[i].rgbtRed := 0;
        end;
      end;
    end;
    Bitmap.Assign(Buffer);
  finally
    Buffer.Free;
  end;
end;

procedure PrintAngleTextOut(const Text: string; X, Y, Angle: Integer);
// http://www.efg2.com/Lab/Library/Delphi/Printing/JoeHechtRotatedFontOnPrinter.TXT (26 September 2008)
var
  LogFont: Windows.TLogFont;
  OldFont: Windows.HFONT;
  NewFont: Windows.HFONT;
begin
  Printer.Canvas.Font.PixelsPerInch := GetDeviceCaps(Printer.Canvas.Handle, LOGPIXELSY);
  GetObject(Printer.Canvas.Font.Handle, SizeOf(LogFont), @LogFont);
  LogFont.lfEscapement := Angle * 10;
  LogFont.lfOrientation := Angle * 10;
  NewFont := CreateFontIndirect(LogFont);
  OldFont := SelectObject(Printer.Canvas.Handle, NewFont);
  Windows.TextOut(Printer.Canvas.Handle, X, Y, PChar(Text), Length(Text));
  SelectObject(Printer.Canvas.Handle, OldFont);
  DeleteObject(NewFont);
end;

procedure AngleTextOut(const Canvas: TCanvas; const Text: string; X, Y, Angle: Integer);
// http://www.efg2.com/Lab/Library/Delphi/Printing/JoeHechtRotatedFontOnPrinter.TXT (26 September 2008)
var
  LogFont: Windows.TLogFont;
  OldFont: Windows.HFONT;
  NewFont: Windows.HFONT;
begin
  Canvas.Font.PixelsPerInch := GetDeviceCaps(Canvas.Handle, LOGPIXELSY);
  GetObject(Canvas.Font.Handle, SizeOf(LogFont), @LogFont);
  LogFont.lfEscapement := Angle * 10;
  LogFont.lfOrientation := Angle * 10;
  NewFont := CreateFontIndirect(LogFont);
  OldFont := SelectObject(Canvas.Handle, NewFont);
  Windows.TextOut(Canvas.Handle, X, Y, PChar(Text), Length(Text));
  SelectObject(Canvas.Handle, OldFont);
  DeleteObject(NewFont);
end;

procedure Rotate(var X, Y: Double; const Angle: Double);
var
  T: Double;
  x1: Double;
  y1: Double;
begin
  T := Angle * Pi / 180;
  x1 := X * Cos(T) - Y * Sin(T);
  y1 := X * Sin(T) + Y * Cos(T);
  X := x1;
  Y := y1;
end;

procedure DrawArrowLine(const Canvas: TCanvas; const Count, X1, Y1, X2, Y2, ArrowLength, HeadLength, HeadWidth: Integer);
var
  L: Double;
  Gap: Double;
  i: Integer;
  X: Double;
  Y: Double;
  Angle: Double;
  dx, dy: Double;
  xx, yy: Double;
  xx1, yy1: Double;
begin
  // Point 1 to Point 2 (Head of Arrow @ Point 2)
  L := Sqrt(Sqr(X2 - X1) + Sqr(Y2 - Y1));
  Gap := (L - Count * ArrowLength) / (Count - 1);
  if X1 <> X2 then
  begin
    if X1 > X2 then
    begin
      if Y1 <> Y2 then
      begin
        if Y1 > Y2 then
        begin
          // X1 > X2, Y1 > Y2 (Points TOP LEFT)
          dx := X1 - X2;
          dy := Y1 - Y2;
          Angle := ArcTan(dy / dx);
          X := X1;
          Y := Y1;
          for i := 1 to Count do
          begin
            Canvas.MoveTo(Round(X), Round(Y));
            X := X - ArrowLength * Cos(Angle);
            Y := Y - ArrowLength * Sin(Angle);
            Canvas.LineTo(Round(X), Round(Y));
            xx1 := HeadLength;
            yy1 := -HeadWidth div 2;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            xx1 := HeadLength;
            yy1 := HeadWidth div 2;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.MoveTo(Round(X), Round(Y));
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            X := X - Gap * Cos(Angle);
            Y := Y - Gap * Sin(Angle);
          end;
        end
        else
        begin
          // X1 > X2, Y1 < Y2 (Points BOTTOM LEFT)
          dx := X1 - X2;
          dy := Y2 - Y1;
          Angle := ArcTan(dx / dy);
          X := X1;
          Y := Y1;
          for i := 1 to Count do
          begin
            Canvas.MoveTo(Round(X), Round(Y));
            X := X - ArrowLength * Sin(Angle);
            Y := Y + ArrowLength * Cos(Angle);
            Canvas.LineTo(Round(X), Round(Y));
            xx1 := -HeadWidth div 2;
            yy1 := HeadLength;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            xx1 := HeadWidth div 2;
            yy1 := HeadLength;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.MoveTo(Round(X), Round(Y));
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            X := X - Gap * Sin(Angle);
            Y := Y + Gap * Cos(Angle);
          end;
        end;
      end
      else
      begin
        // X1 > X2, Y1 = Y2 (Points LEFT)
        X := X1;
        for i := 1 to Count do
        begin
          Canvas.MoveTo(Round(X), Y1);
          X := X - ArrowLength;
          Canvas.LineTo(Round(X), Y1);
          Canvas.MoveTo(Round(X), Y1);
          Canvas.LineTo(Round(X) + HeadLength, Y1 + HeadWidth div 2);
          Canvas.MoveTo(Round(X), Y1);
          Canvas.LineTo(Round(X) + HeadLength, Y1 - HeadWidth div 2);
          X := X - Gap;
        end;
      end;
    end
    else
    begin
      // X1 < X2
      if Y1 <> Y2 then
      begin
        if Y1 > Y2 then
        begin
          // X1 < X2, Y1 > Y2 (Points TOP RIGHT)
          dx := X2 - X1;
          dy := Y1 - Y2;
          Angle := ArcTan(dx / dy);
          X := X1;
          Y := Y1;
          for i := 1 to Count do
          begin
            Canvas.MoveTo(Round(X), Round(Y));
            X := X + ArrowLength * Sin(Angle);
            Y := Y - ArrowLength * Cos(Angle);
            Canvas.LineTo(Round(X), Round(Y));
            xx1 := -HeadWidth div 2;
            yy1 := -HeadLength;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            xx1 := HeadWidth div 2;
            yy1 := -HeadLength;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.MoveTo(Round(X), Round(Y));
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            X := X + Gap * Sin(Angle);
            Y := Y - Gap * Cos(Angle);
          end;
        end
        else
        begin
          // X1 < X2, Y1 < Y2 (Points BOTTOM RIGHT)
          dx := X2 - X1;
          dy := Y2 - Y1;
          Angle := ArcTan(dy / dx);
          X := X1;
          Y := Y1;
          for i := 1 to Count do
          begin
            Canvas.MoveTo(Round(X), Round(Y));
            X := X + ArrowLength * Cos(Angle);
            Y := Y + ArrowLength * Sin(Angle);
            Canvas.LineTo(Round(X), Round(Y));
            xx1 := -HeadLength;
            yy1 := -HeadWidth div 2;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            xx1 := -HeadLength;
            yy1 := HeadWidth div 2;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.MoveTo(Round(X), Round(Y));
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            X := X + Gap * Cos(Angle);
            Y := Y + Gap * Sin(Angle);
          end;
        end;
      end
      else
      begin
        // X1 < X2, Y1 = Y2 (Points RIGHT)
        X := X1;
        for i := 1 to Count do
        begin
          Canvas.MoveTo(Round(X), Y1);
          X := X + ArrowLength;
          Canvas.LineTo(Round(X), Y1);
          Canvas.MoveTo(Round(X), Y1);
          Canvas.LineTo(Round(X) - HeadLength, Y1 + HeadWidth div 2);
          Canvas.MoveTo(Round(X), Y1);
          Canvas.LineTo(Round(X) - HeadLength, Y1 - HeadWidth div 2);
          X := X + Gap;
        end;
      end;
    end;
  end
  else
  begin
    // X1 = X2
    if Y1 <> Y2 then
    begin
      if Y1 > Y2 then
      begin
        // X1 = X2, Y1 > Y2 (Points UP)
        Y := Y1;
        for i := 1 to Count do
        begin
          Canvas.MoveTo(X1, Round(Y));
          Y := Y - ArrowLength;
          Canvas.LineTo(X1, Round(Y));
          Canvas.MoveTo(X1, Round(Y));
          Canvas.LineTo(X1 + HeadWidth div 2, Round(Y) + HeadLength);
          Canvas.MoveTo(X1, Round(Y));
          Canvas.LineTo(X1 - HeadWidth div 2, Round(Y) + HeadLength);
          Y := Y - Gap;
        end;
      end
      else
      begin
        // X1 = X2, Y1 < Y2 (Points DOWN)
        Y := Y1;
        for i := 1 to Count do
        begin
          Canvas.MoveTo(X1, Round(Y));
          Y := Y + ArrowLength;
          Canvas.LineTo(X1, Round(Y));
          Canvas.MoveTo(X1, Round(Y));
          Canvas.LineTo(X1 + HeadWidth div 2, Round(Y) - HeadLength);
          Canvas.MoveTo(X1, Round(Y));
          Canvas.LineTo(X1 - HeadWidth div 2, Round(Y) - HeadLength);
          Y := Y + Gap;
        end;
      end;
    end
    else
    begin
      // X1 = X2, Y1 = Y2
      Exit;
    end;
  end;
end;

procedure DrawArrow(const Canvas: TCanvas; const X1, Y1, X2, Y2, ArrowLength, HeadLength, HeadWidth: Integer);
var
  X: Double;
  Y: Double;
  Angle: Double;
  dx, dy: Double;
  xx, yy: Double;
  xx1, yy1: Double;
begin
  // Point 1 to Point 2 (Head of Arrow @ Point 2)
  if X1 <> X2 then
  begin
    if X1 > X2 then
    begin
      if Y1 <> Y2 then
      begin
        if Y1 > Y2 then
        begin
          // X1 > X2, Y1 > Y2 (Points TOP LEFT)
          dx := X1 - X2;
          dy := Y1 - Y2;
          Angle := ArcTan(dy / dx);
          X := X1;
          Y := Y1;
            Canvas.MoveTo(Round(X), Round(Y));
            X := X - ArrowLength * Cos(Angle);
            Y := Y - ArrowLength * Sin(Angle);
            Canvas.LineTo(Round(X), Round(Y));
            xx1 := HeadLength;
            yy1 := -HeadWidth div 2;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            xx1 := HeadLength;
            yy1 := HeadWidth div 2;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.MoveTo(Round(X), Round(Y));
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
        end
        else
        begin
          // X1 > X2, Y1 < Y2 (Points BOTTOM LEFT)
          dx := X1 - X2;
          dy := Y2 - Y1;
          Angle := ArcTan(dx / dy);
          X := X1;
          Y := Y1;
            Canvas.MoveTo(Round(X), Round(Y));
            X := X - ArrowLength * Sin(Angle);
            Y := Y + ArrowLength * Cos(Angle);
            Canvas.LineTo(Round(X), Round(Y));
            xx1 := -HeadWidth div 2;
            yy1 := HeadLength;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            xx1 := HeadWidth div 2;
            yy1 := HeadLength;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.MoveTo(Round(X), Round(Y));
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
        end;
      end
      else
      begin
        // X1 > X2, Y1 = Y2 (Points LEFT)
        X := X1;
          Canvas.MoveTo(Round(X), Y1);
          X := X - ArrowLength;
          Canvas.LineTo(Round(X), Y1);
          Canvas.MoveTo(Round(X), Y1);
          Canvas.LineTo(Round(X) + HeadLength, Y1 + HeadWidth div 2);
          Canvas.MoveTo(Round(X), Y1);
          Canvas.LineTo(Round(X) + HeadLength, Y1 - HeadWidth div 2);
      end;
    end
    else
    begin
      // X1 < X2
      if Y1 <> Y2 then
      begin
        if Y1 > Y2 then
        begin
          // X1 < X2, Y1 > Y2 (Points TOP RIGHT)
          dx := X2 - X1;
          dy := Y1 - Y2;
          Angle := ArcTan(dx / dy);
          X := X1;
          Y := Y1;
            Canvas.MoveTo(Round(X), Round(Y));
            X := X + ArrowLength * Sin(Angle);
            Y := Y - ArrowLength * Cos(Angle);
            Canvas.LineTo(Round(X), Round(Y));
            xx1 := -HeadWidth div 2;
            yy1 := -HeadLength;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            xx1 := HeadWidth div 2;
            yy1 := -HeadLength;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.MoveTo(Round(X), Round(Y));
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
        end
        else
        begin
          // X1 < X2, Y1 < Y2 (Points BOTTOM RIGHT)
          dx := X2 - X1;
          dy := Y2 - Y1;
          Angle := ArcTan(dy / dx);
          X := X1;
          Y := Y1;
            Canvas.MoveTo(Round(X), Round(Y));
            X := X + ArrowLength * Cos(Angle);
            Y := Y + ArrowLength * Sin(Angle);
            Canvas.LineTo(Round(X), Round(Y));
            xx1 := -HeadLength;
            yy1 := -HeadWidth div 2;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
            xx1 := -HeadLength;
            yy1 := HeadWidth div 2;
            xx := xx1 * Cos(Angle) + yy1 * Sin(Angle);
            yy := -xx1 * Sin(Angle) + yy1 * Cos(Angle);
            xx1 := xx;
            yy1 := -yy;
            Canvas.MoveTo(Round(X), Round(Y));
            Canvas.LineTo(Round(X + xx1), Round(Y + yy1));
        end;
      end
      else
      begin
        // X1 < X2, Y1 = Y2 (Points RIGHT)
        X := X1;
          Canvas.MoveTo(Round(X), Y1);
          X := X + ArrowLength;
          Canvas.LineTo(Round(X), Y1);
          Canvas.MoveTo(Round(X), Y1);
          Canvas.LineTo(Round(X) - HeadLength, Y1 + HeadWidth div 2);
          Canvas.MoveTo(Round(X), Y1);
          Canvas.LineTo(Round(X) - HeadLength, Y1 - HeadWidth div 2);
      end;
    end;
  end
  else
  begin
    // X1 = X2
    if Y1 <> Y2 then
    begin
      if Y1 > Y2 then
      begin
        // X1 = X2, Y1 > Y2 (Points UP)
        Y := Y1;
          Canvas.MoveTo(X1, Round(Y));
          Y := Y - ArrowLength;
          Canvas.LineTo(X1, Round(Y));
          Canvas.MoveTo(X1, Round(Y));
          Canvas.LineTo(X1 + HeadWidth div 2, Round(Y) + HeadLength);
          Canvas.MoveTo(X1, Round(Y));
          Canvas.LineTo(X1 - HeadWidth div 2, Round(Y) + HeadLength);
      end
      else
      begin
        // X1 = X2, Y1 < Y2 (Points DOWN)
        Y := Y1;
          Canvas.MoveTo(X1, Round(Y));
          Y := Y + ArrowLength;
          Canvas.LineTo(X1, Round(Y));
          Canvas.MoveTo(X1, Round(Y));
          Canvas.LineTo(X1 + HeadWidth div 2, Round(Y) - HeadLength);
          Canvas.MoveTo(X1, Round(Y));
          Canvas.LineTo(X1 - HeadWidth div 2, Round(Y) - HeadLength);
      end;
    end
    else
    begin
      // X1 = X2, Y1 = Y2
      Exit;
    end;
  end;
end;

end.
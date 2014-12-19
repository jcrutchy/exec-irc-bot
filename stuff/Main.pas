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
  Math;

type

  TMatrixStreamArray = class;
  TMatrixColorTree = class;

  TMatrixColorArray = class(TObject)
  private
    FColors: TList;
  private
    function GetColor(const Index: Integer): TColor;
  public
    constructor Create(const Owner: TMatrixColorTree; const Count: Integer);
    destructor Destroy; override;
  public
    property Colors[const Index: Integer]: TColor read GetColor; default;
  end;

  TMatrixColorTree = class(TObject)
  private
    FColorArrays: TList;
    FOwner: TMatrixStreamArray;
  private
    function GetColorArray(const Index: Integer): TMatrixColorArray;
    function GetCount: Integer;
  public
    constructor Create(const Owner: TMatrixStreamArray);
    destructor Destroy; override;
  public
    property ColorArrays[const Index: Integer]: TMatrixColorArray read GetColorArray; default;
    property Count: Integer read GetCount;
    property Owner: TMatrixStreamArray read FOwner;
  end;

  TMatrixStream = class(TObject)
  private
    FCharacters: array of Char;
    FCharCount: Integer;
    FDisplayCount: Integer;
    FFontSize: Integer;
    FLeft: Integer;
    FOwner: TMatrixStreamArray;
    FProgress: Integer;
    FSpeed: Integer;
    FSpeedIncrementer: Integer;
    FTextHeight: Integer;
  public
    constructor Create(const Owner: TMatrixStreamArray);
    destructor Destroy; override;
  public
    function Finished: Boolean;
    procedure Iterate;
  end;

  TMatrixStreamArray = class(TObject)
  private
    FASCIIMax: Integer;
    FASCIIMin: Integer;
    FCountMax: Integer;
    FCountMin: Integer;
    FBackgroundColor: TColor;
    FBuffer: Graphics.TBitmap;
    FBufferRect: TRect;
    FCharacterSet: Integer;
    FColorTree: TMatrixColorTree;
    FFontSizeMax: Integer;
    FFontSizeMin: Integer;
    FHeadColor: TColor;
    FMaxStreams: Integer;
    FOutput: Graphics.TBitmap;
    FStreams: TList;
    FTailColor: TColor;
  public
    constructor Create(const Output: Graphics.TBitmap);
    destructor Destroy; override;
  public
    procedure Iterate;
    procedure Resize;
  public
    property Buffer: Graphics.TBitmap read FBuffer;
    property ColorTree: TMatrixColorTree read FColorTree;
    property HeadColor: TColor read FHeadColor;
    property TailColor: TColor read FTailColor;
  end;

  TFormMain = class(TForm)
    Timer: TTimer;
    ImageStreams: TImage;
    procedure TimerTimer(Sender: TObject);
    procedure FormCreate(Sender: TObject);
    procedure FormResize(Sender: TObject);
    procedure FormDestroy(Sender: TObject);
    procedure FormKeyPress(Sender: TObject; var Key: Char);
  private
    FStreams: TMatrixStreamArray;
  end;

var
  FormMain: TFormMain;

const
  MATRIX_DEFAULT_CHARSVISIBLE_MIN = 2;
  MATRIX_DEFAULT_CHARSVISIBLE_MAX = 200;
  MATRIX_DEFAULT_FONTSIZE_MIN = 2;
  MATRIX_DEFAULT_FONTSIZE_MAX = 16;
  MATRIX_DEFAULT_STREAMCOUNT_MAX = 600;
  MATRIX_DEFAULT_ASCII_MIN = 165;
  MATRIX_DEFAULT_ASCII_MAX = 200;
  MATRIX_DEFAULT_FONTNAME = 'Courier New';
  MATRIX_DEFAULT_CHARACTERSET = DEFAULT_CHARSET;
  MATRIX_DEFAULT_COLOR_HEAD_R = 0;
  MATRIX_DEFAULT_COLOR_HEAD_G = 255;
  MATRIX_DEFAULT_COLOR_HEAD_B = 0;
  MATRIX_DEFAULT_COLOR_TAIL_R = 0;
  MATRIX_DEFAULT_COLOR_TAIL_G = 130;
  MATRIX_DEFAULT_COLOR_TAIL_B = 0;
  MATRIX_DEFAULT_COLOR_BACKGROUND = clBlack;

implementation

{$R *.dfm}

{ TMatrixColorArray }

constructor TMatrixColorArray.Create(const Owner: TMatrixColorTree; const Count: Integer);
var
  i: Integer;
  dR, dG, dB: Double;
  R1, G1, B1: Byte;
  R2, G2, B2: Byte;
  c1, c2: TColor;
begin
  FColors := TList.Create;
  c1 := Owner.Owner.TailColor;
  c2 := Owner.Owner.Buffer.Canvas.Brush.Color;
  R1 := GetRValue(c1);
  G1 := GetGValue(c1);
  B1 := GetBValue(c1);
  R2 := GetRValue(c2);
  G2 := GetGValue(c2);
  B2 := GetBValue(c2);
  dR := (R1 - R2) / Count;
  dG := (G1 - G2) / Count;
  dB := (B1 - B2) / Count;
  for i := 0 to Count - 1 do
    FColors.Add(Pointer(RGB(Round(R1 - dR * i), Round(G1 - dG * i), Round(B1 - dB * i))));
end;

destructor TMatrixColorArray.Destroy;
begin
  FColors.Free;
  inherited;
end;

function TMatrixColorArray.GetColor(const Index: Integer): TColor;
begin
  Result := TColor(FColors[Index]);
end;

{ TMatrixColorTree }

constructor TMatrixColorTree.Create(const Owner: TMatrixStreamArray);
var
  i: Integer;
begin
  FOwner := Owner;
  FColorArrays := TList.Create;
  for i := 0 to MATRIX_DEFAULT_CHARSVISIBLE_MAX - MATRIX_DEFAULT_CHARSVISIBLE_MIN - 1 do
    FColorArrays.Add(TMatrixColorArray.Create(Self, i + MATRIX_DEFAULT_CHARSVISIBLE_MIN));
end;

destructor TMatrixColorTree.Destroy;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    ColorArrays[i].Free;
  FColorArrays.Free;
  inherited;
end;

function TMatrixColorTree.GetColorArray(const Index: Integer): TMatrixColorArray;
begin
  Result := FColorArrays[Index];
end;

function TMatrixColorTree.GetCount: Integer;
begin
  Result := FColorArrays.Count;
end;

{ TMatrixStream }

constructor TMatrixStream.Create(const Owner: TMatrixStreamArray);
var
  i: Integer;
begin
  FOwner := Owner;
  FFontSize := MATRIX_DEFAULT_FONTSIZE_MIN + Random(MATRIX_DEFAULT_FONTSIZE_MAX - MATRIX_DEFAULT_FONTSIZE_MIN);
  FOwner.Buffer.Canvas.Font.Size := FFontSize;
  FTextHeight := FOwner.Buffer.Canvas.TextHeight('A');
  FCharCount := FOwner.Buffer.Height div FTextHeight;
  FDisplayCount := Min(FCharCount, MATRIX_DEFAULT_CHARSVISIBLE_MIN + Random(MATRIX_DEFAULT_CHARSVISIBLE_MAX - MATRIX_DEFAULT_CHARSVISIBLE_MIN));
  FLeft := Random(FOwner.Buffer.Width);
  SetLength(FCharacters, FCharCount);
  for i := 0 to FCharCount - 1 do
    FCharacters[i] := Char(MATRIX_DEFAULT_ASCII_MIN + Random(MATRIX_DEFAULT_ASCII_MAX - MATRIX_DEFAULT_ASCII_MIN));
  FSpeed := Random(FFontSize div 4);
  FSpeedIncrementer := 0;
  FProgress := 0;
end;

destructor TMatrixStream.Destroy;
begin
  SetLength(FCharacters, 0);
  inherited;
end;

function TMatrixStream.Finished: Boolean;
begin
  Result := FProgress > FCharCount + FDisplayCount + 1;
end;

procedure TMatrixStream.Iterate;
var
  i: Integer;
begin
  FOwner.Buffer.Canvas.Font.Size := FFontSize;
  for i := 0 to FCharCount do
    if (i > FProgress - FDisplayCount) and (i <= FProgress) then
    begin
      if i = FProgress then
        FOwner.Buffer.Canvas.Font.Color := FOwner.HeadColor
      else
        try
          // There is a bug in either the color tree generation or in accessing a color in the color tree.
          FOwner.Buffer.Canvas.Font.Color := FOwner.ColorTree[FDisplayCount - 1][FProgress - i - 1];
        except
          FOwner.Buffer.Canvas.Font.Color := FOwner.Buffer.Canvas.Brush.Color;
        end;
      FOwner.Buffer.Canvas.TextOut(FLeft, FTextHeight * i, FCharacters[i])
    end;
  if FSpeedIncrementer = 0 then
    Inc(FProgress);
  if FSpeedIncrementer > FSpeed then
    FSpeedIncrementer := 0
  else
    Inc(FSpeedIncrementer);
end;

{ TMatrixStreamArray }

constructor TMatrixStreamArray.Create(const Output: Graphics.TBitmap);
begin
  FOutput := Output;
  FBuffer := Graphics.TBitmap.Create;
  FBuffer.Canvas.Font.Style := [fsBold];
  FBuffer.Canvas.Brush.Color := MATRIX_DEFAULT_COLOR_BACKGROUND;
  FBuffer.Canvas.Font.Charset := MATRIX_DEFAULT_CHARACTERSET;
  FHeadColor := RGB(MATRIX_DEFAULT_COLOR_HEAD_R, MATRIX_DEFAULT_COLOR_HEAD_G, MATRIX_DEFAULT_COLOR_HEAD_B);
  FTailColor := RGB(MATRIX_DEFAULT_COLOR_TAIL_R, MATRIX_DEFAULT_COLOR_TAIL_G, MATRIX_DEFAULT_COLOR_TAIL_B);
  FStreams := TList.Create;
  FColorTree := TMatrixColorTree.Create(Self);
end;

destructor TMatrixStreamArray.Destroy;
var
  i: Integer;
begin
  FColorTree.Free;
  for i := 0 to FStreams.Count - 1 do
    TMatrixStream(FStreams[i]).Free;
  FStreams.Free;
  FBuffer.Free;
  inherited;
end;

procedure TMatrixStreamArray.Iterate;
var
  i: Integer;
begin
  FBuffer.Canvas.FillRect(FBufferRect);
  if (Random(3) = 0) and (FStreams.Count < MATRIX_DEFAULT_STREAMCOUNT_MAX) then
    FStreams.Add(TMatrixStream.Create(Self));
  i := 0;
  while i < FStreams.Count do
  begin
    TMatrixStream(FStreams[i]).Iterate;
    if TMatrixStream(FStreams[i]).Finished then
    begin
      TMatrixStream(FStreams[i]).Free;
      FStreams.Delete(i);
    end
    else
      Inc(i);
  end;
  FOutput.Canvas.CopyRect(FBufferRect, FBuffer.Canvas, FBufferRect);
end;

procedure TMatrixStreamArray.Resize;
begin
  FBuffer.Width := FOutput.Width;
  FBuffer.Height := FOutput.Height;
  FBufferRect := Rect(0, 0, FBuffer.Width, FBuffer.Height);
end;

{ TFormMain }

procedure TFormMain.TimerTimer(Sender: TObject);
begin
  FStreams.Iterate;
end;

procedure TFormMain.FormCreate(Sender: TObject);
begin
  Randomize;
  FStreams := TMatrixStreamArray.Create(ImageStreams.Picture.Bitmap);
  WindowState := wsMaximized;
  Cursor := crNone;
  Timer.Enabled := True;
end;

procedure TFormMain.FormResize(Sender: TObject);
begin
  ImageStreams.Picture.Bitmap.Width := ImageStreams.Width;
  ImageStreams.Picture.Bitmap.Height := ImageStreams.Height;
  FStreams.Resize;
end;

procedure TFormMain.FormDestroy(Sender: TObject);
begin
  FStreams.Free;
end;

procedure TFormMain.FormKeyPress(Sender: TObject; var Key: Char);
begin
  Timer.Enabled := False;
  Cursor := crDefault;
  Application.Terminate;
end;

end.
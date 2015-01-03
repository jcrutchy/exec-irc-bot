// Modified April 2010
// TCustomStrings.SetText method modified to account for either #10, #13 or #13#10 as line breaks

unit JMC_Strings;

interface

uses
  Classes,
  JMC_Parts,
  Graphics,
  Windows,
  SysUtils,
  Clipbrd,
  JMC_FileUtils;

type

  { TCustomStrings }

  TCustomStrings = class(TObject)
  protected
    function IndexInRange(const Index: Integer): Boolean;
    function GetCount: Integer; virtual; abstract;
    function GetLine(const Index: Integer): string; virtual; abstract;
    function GetText: string;
    procedure SetLine(const Index: Integer; const Value: string); virtual; abstract;
    procedure SetText(const Value: string);
    procedure Ins(const Index: Integer; const Line: string); virtual; abstract;
    procedure Del(const Index: Integer); virtual; abstract;
  public
    constructor Create; virtual;
    function LoadFromFile(const FileName: string): Boolean; virtual; abstract;
    function SaveToFile(const FileName: string): Boolean; virtual; abstract;
    procedure Add; overload; virtual;
    procedure Add(const Line: string); overload; virtual; abstract;
    procedure Append(const Index: Integer; const Value: string);
    procedure AppendFrom(const Lines: TCustomStrings); overload;
    procedure AppendFrom(const Lines: TStrings); overload;
    procedure AppendTo(const Lines: TCustomStrings); overload;
    procedure AppendTo(const Lines: TStrings); overload;
    function Insert(const Index: Integer; const Line: string): Boolean;
    function Delete(const Index: Integer): Boolean;
    procedure Clear; virtual; abstract;
    procedure Assign(const Strings: TStrings); overload;
    procedure Assign(const Strings: TCustomStrings); overload;
    procedure CopyToClipboard;
    procedure CopyToStrings(const Strings: TStrings);
    function Equals(const Strings: TCustomStrings): Boolean;
    function Empty: Boolean;
    function Find(const Line: string): Integer;
    function MaxWidth(const Canvas: TCanvas): Integer;
    property Text: string read GetText write SetText;
    property Count: Integer read GetCount;
    property Lines[const Index: Integer]: string read GetLine write SetLine; default;
  end;

  { TStringArray }

  TStringArray = class(TCustomStrings)
  protected
    FCount: Integer;
    FLines: array of string;
    function GetCount: Integer; override;
    function GetLine(const Index: Integer): string; override;
    procedure SetLine(const Index: Integer; const Value: string); override;
    procedure Ins(const Index: Integer; const Line: string); override;
    procedure Del(const Index: Integer); override;
  public
    destructor Destroy; override;
    function LoadFromFile(const FileName: string): Boolean; override;
    function SaveToFile(const FileName: string): Boolean; override;
    procedure Clear; override;
    procedure Add(const Line: string); override;
  end;

  { TCustomIniFile }

  TCustomIniFile = class(TObject)
  private
    FStrings: TCustomStrings;
    function SectionFound(const LineIndex: Integer): Boolean;
  protected
    procedure SetStrings(const Value: TCustomStrings);
  public
    constructor Create; virtual;
    function LoadFromFile(const FileName: string): Boolean; virtual;
    function SaveToFile(const FileName: string): Boolean; virtual;
    { Parts }
    function ReadPart(const LineIndex, PartIndex: Integer): string;
    function WritePart(const Part: string; const LineIndex, PartIndex: Integer): Boolean;
    { Sections }
    function WriteSection(const Section: string): Boolean;
    function ReadSections(const Lines: TCustomStrings): Boolean;
    function SectionIndex(const Section: string): Integer;
    function SectionExists(const Section: string): Boolean;
    function CountSections: Integer;
    function RenameSection(const OldSection, NewSection: string): Boolean;
    function DeleteSection(const Section: string): Boolean;
    function ClearSection(const Section: string): Boolean;
    { Keys }
    function ReadKeys(const Section: string; const Lines: TCustomStrings): Boolean;
    function KeyIndex(const Section, Key: string): Integer;
    function KeyExists(const Section, Key: string): Boolean;
    function CountKeys(const Section: string): Integer;
    function RenameKey(const Section, OldKey, NewKey: string): Boolean;
    function DeleteKey(const Section, Key: string): Boolean;
    { Values }
    function ReadValue(const Section, Key: string): string;
    function ReadValues(const Section: string; const Lines: TCustomStrings): Boolean;
    function WriteValue(const Section, Key, Value: string): Boolean;
    { Lines }
    function ReadLine(const Section: string; const Index: Integer): string;
    function ReadLines(const Section: string; const Lines: TCustomStrings; const SkipBlanks, TrimSpaces, AllowEmpty: Boolean): Boolean;
    procedure WriteLine(const Section, NewLine: string);
    procedure WriteLines(const Section: string; const Lines: TCustomStrings);
    function LineIndex(const Section, Line: string): Integer;
    function LineExists(const Section, Line: string): Boolean;
    function CountLines(const Section: string): Integer;
    function NextEmptyLineIndex(const StartIndex: Integer): Integer; overload;
    function NextEmptyLineIndex(const Section: string): Integer; overload;
    function ChangeLine(const Section, OldLine, NewLine: string): Boolean;
    function DeleteLine(const Section, Line: string): Boolean;
    { Properties }
    property Strings: TCustomStrings read FStrings;
  end;

  { TLinkedIniFile }

  TLinkedIniFile = class(TCustomIniFile)
  public
    property Strings write SetStrings;
  end;

  { TStoredIniFile }

  TStoredIniFile = class(TCustomIniFile)
  public
    constructor Create; override;
    destructor Destroy; override;
  end;

implementation

{ TCustomStrings }

procedure TCustomStrings.Add;
begin
  Add('');
end;

procedure TCustomStrings.Append(const Index: Integer; const Value: string);
begin
  Lines[Index] := Lines[Index] + Value;
end;

procedure TCustomStrings.AppendFrom(const Lines: TCustomStrings);
var
  i: Integer;
begin
  for i := 0 to Lines.Count - 1 do
    Add(Lines[i]);
end;

procedure TCustomStrings.AppendFrom(const Lines: TStrings);
var
  i: Integer;
begin
  for i := 0 to Lines.Count - 1 do
    Add(Lines[i]);
end;

procedure TCustomStrings.AppendTo(const Lines: TCustomStrings);
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Lines.Add(GetLine(i));
end;

procedure TCustomStrings.AppendTo(const Lines: TStrings);
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Lines.Add(GetLine(i));
end;

procedure TCustomStrings.Assign(const Strings: TCustomStrings);
var
  i: Integer;
begin
  Clear;
  for i := 0 to Strings.Count - 1 do
    Add(Strings[i]);
end;

procedure TCustomStrings.Assign(const Strings: TStrings);
begin
  SetText(Strings.Text);
end;

procedure TCustomStrings.CopyToClipboard;
begin
  Clipboard.SetTextBuf(PChar(Text));
end;

procedure TCustomStrings.CopyToStrings(const Strings: TStrings);
Begin
  Strings.Text := GetText;
end;

constructor TCustomStrings.Create;
begin
  inherited;
end;

function TCustomStrings.Delete(const Index: Integer): Boolean;
begin
  Result := IndexInRange(Index);
  if Result then
    Del(Index);
end;

function TCustomStrings.Empty: Boolean;
begin
  Result := Count = 0;
end;

function TCustomStrings.Equals(const Strings: TCustomStrings): Boolean;
var
  i: Integer;
begin
  Result := Count = Strings.Count;
  if Result then
    for i := 0 to Count - 1 do
      if CompareStr(Lines[i], Strings[i]) <> 0 then
      begin
        Result := False;
        Exit;
      end;
end;

function TCustomStrings.Find(const Line: string): Integer;
var
  i: Integer;
  S: string;
begin
  S := UpperCase(Line);
  for i := 0 to Count - 1 do
    if UpperCase(Lines[i]) = S then
    begin
      Result := i;
      Exit;
    end;
  Result := -1;
end;

function TCustomStrings.GetText: string;
var
  i: Integer;
begin
  Result := '';
  for i := 0 to Count - 1 do
  begin
    Result := Result + Lines[i];
    if i < Count - 1 then
      Result := Result + #13#10;
  end;
end;

function TCustomStrings.IndexInRange(const Index: Integer): Boolean;
begin
  Result := (Index >= 0) and (Index < Count);
end;

function TCustomStrings.Insert(const Index: Integer; const Line: string): Boolean;
begin
  Result := IndexInRange(Index);
  if Result then
    Ins(Index, Line);
end;

function TCustomStrings.MaxWidth(const Canvas: TCanvas): Integer;
var
  i: Integer;
begin
  Result := 0;
  for i := 0 to Count - 1 do
    if Canvas.TextWidth(Lines[i]) > Result then
      Result := Canvas.TextWidth(Lines[i]);
end;

procedure TCustomStrings.SetText(const Value: string);
var
  i: Integer;
  S: string;
begin
  Clear;
  if Value = '' then
    Exit;
  i := 1;
  S := '';
  while i <= Length(Value) do
  begin
    case Ord(Value[i]) of
      10, 13:
        begin
          Add(S);
          S := '';
          if (Ord(Value[i]) = 13) and (i < Length(Value)) then
            if Ord(Value[i + 1]) = 10 then
              Inc(i);
        end;
    else
      S := S + Value[i];
    end;
    Inc(i);
  end;
  Add(S);
end;

{ TStringArray }

procedure TStringArray.Add(const Line: string);
begin
  Inc(FCount);
  SetLength(FLines, FCount);
  FLines[FCount - 1] := Line;
end;

procedure TStringArray.Clear;
begin
  SetLength(FLines, 0);
  FCount := 0;
end;

procedure TStringArray.Del(const Index: Integer);
var
  i: Integer;
begin
  Dec(FCount);
  for i := Index to FCount - 1 do
    FLines[i] := FLines[i + 1];
  SetLength(FLines, FCount);
end;

destructor TStringArray.Destroy;
begin
  SetLength(FLines, 0);
  inherited;
end;

function TStringArray.GetCount: Integer;
begin
  Result := FCount;
end;

function TStringArray.GetLine(const Index: Integer): string;
begin
  if IndexInRange(Index) then
    Result := FLines[Index]
  else
    Result := '';
end;

procedure TStringArray.Ins(const Index: Integer; const Line: string);
var
  i: Integer;
begin
  Inc(FCount);
  SetLength(FLines, FCount);
  for i := FCount - 1 downto Index + 1 do
    FLines[i] := FLines[i - 1];
  FLines[Index] := Line;
end;

function TStringArray.LoadFromFile(const FileName: string): Boolean;
var
  S: string;
begin
  Result := JMC_FileUtils.FileToStr(FileName, S);
  SetText(S);
end;

function TStringArray.SaveToFile(const FileName: string): Boolean; // FileName must be fully qualified
var
  F: TFileStream;
  i: Integer;
  S: string;
begin
  try
    if Count > 0 then
    begin
      F := TFileStream.Create(FileName, fmCreate or fmOpenWrite or fmShareDenyNone);
      for i := 0 to Count - 2 do
      begin
        S := FLines[i] + #13#10;
        F.Write(S[1], Length(S));
      end;
      F.Write(FLines[Count - 1][1], Length(FLines[Count - 1]));
      F.Free;
      Result := True;
    end
    else
      Result := False;
  except
    Result := False;
  end;
end;

procedure TStringArray.SetLine(const Index: Integer; const Value: string);
begin
  if IndexInRange(Index) then
    FLines[Index] := Value;
end;

{ TCustomIniFile }

function TCustomIniFile.ChangeLine(const Section, OldLine, NewLine: string): Boolean;
var
  i: Integer;
begin
  i := LineIndex(Section, OldLine);
  Result := i > -1;
  if Result then
    FStrings[i] := NewLine;
end;

function TCustomIniFile.ClearSection(const Section: string): Boolean;
var
  i: Integer;
begin
  i := SectionIndex(Section);
  Result := i > -1;
  if Result then
  begin
    Inc(i);
    while i < FStrings.Count do
    begin
      if SectionFound(i) then
        Break;
      FStrings.Del(i);
      Inc(i);
    end;
  end;
end;

function TCustomIniFile.CountKeys(const Section: string): Integer;
var
  i, n: Integer;
begin
  Result := 0;
  i := SectionIndex(Section);
  if i > -1 then
    for n := i to FStrings.Count - 1 do
    begin
      if SectionFound(n) then
        Break;
      if Pos('=', FStrings[n]) > 0 then
        Inc(Result);
    end;
end;

function TCustomIniFile.CountLines(const Section: string): Integer;
var
  n, i: Integer;
begin
  Result := 0;
  i := SectionIndex(Section) + 1;
  if i > -1 then
    for n := i to FStrings.Count - 1 do
    begin
      if SectionFound(n) then
        Break;
      Inc(Result);
    end;
end;

function TCustomIniFile.CountSections: Integer;
var
  i: Integer;
begin
  Result := 0;
  for i := 0 to FStrings.Count - 1 do
    if SectionFound(i) then
      Inc(Result);
end;

constructor TCustomIniFile.Create;
begin
  inherited;
end;

function TCustomIniFile.DeleteKey(const Section, Key: string): Boolean;
var
  i: Integer;
begin
  i := KeyIndex(Section, Key);
  Result := i > -1;
  if Result then
    FStrings.Delete(i);
end;

function TCustomIniFile.DeleteLine(const Section, Line: string): Boolean;
var
  i: Integer;
begin
  i := LineIndex(Section, Line);
  Result := i <> -1;
  if Result then
    FStrings.Delete(i);
end;

function TCustomIniFile.DeleteSection(const Section: string): Boolean;
var
  i: Integer;
begin
  i := SectionIndex(Section);
  Result := i > -1;
  if Result then
  begin
    FStrings.Del(i);
    while i < FStrings.Count do
    begin
      if SectionFound(i) then
        Break;
      FStrings.Del(i);
      Inc(i);
    end;
  end;
end;

function TCustomIniFile.KeyExists(const Section, Key: string): Boolean;
begin
  Result := KeyIndex(Section, Key) > -1;
end;

function TCustomIniFile.KeyIndex(const Section, Key: string): Integer;
var
  i, n: Integer;
begin
  i := SectionIndex(Section);
  if i > -1 then
    for n := i + 1 to FStrings.Count - 1 do
    begin
      if SectionFound(n) then
        Break;
      if Pos('=', FStrings[n]) <= 0 then
        Break;
      if UpperCase(JMC_Parts.ReadPart(FStrings[n], 1, '=')) = UpperCase(Key) then
      begin
        Result := n;
        Exit;
      end;
    end;
  Result := -1;
end;

function TCustomIniFile.LineExists(const Section, Line: string): Boolean;
begin
  Result := LineIndex(Section, Line) > -1;
end;

function TCustomIniFile.LineIndex(const Section, Line: string): Integer;
var
  n, i: Integer;
begin
  i := SectionIndex(Section);
  if i > -1 then
    for n := i + 1 to FStrings.Count - 1 do
    begin
      if SectionFound(n) then
        Break;
      if FStrings[n] = Line then
      begin
        Result := n;
        Exit;
      end;
    end;
  Result := -1;
end;

function TCustomIniFile.LoadFromFile(const FileName: string): Boolean;
begin
  Result := FStrings.LoadFromFile(FileName);
end;

function TCustomIniFile.NextEmptyLineIndex(const Section: string): Integer;
begin
  Result := SectionIndex(Section);
  if Result > -1 then
    Result := NextEmptyLineIndex(Result + 1);
end;

function TCustomIniFile.NextEmptyLineIndex(const StartIndex: Integer): Integer;
var
  i: Integer;
begin
  for i := StartIndex to FStrings.Count - 1 do
  begin
    if Trim(FStrings[i]) = '' then
    begin
      FStrings[i] := '';
      Result := i;
      if SectionFound(i + 1) then
        FStrings.Insert(i + 1, '');
      Exit;
    end;
    if SectionFound(i) then
    begin
      FStrings.Insert(i, '');
      FStrings.Insert(i, '');
      Result := i;
      Exit;
    end;
  end;
  FStrings.Add('');
  Result := FStrings.Count - 1;
end;

function TCustomIniFile.ReadKeys(const Section: string; const Lines: TCustomStrings): Boolean;
var
  i, n: Integer;
begin
  Lines.Clear;
  i := SectionIndex(Section);
  if i > -1 then
    for n := i + 1 to FStrings.Count - 1 do
    begin
      if SectionFound(n) then
        Break;
      if Pos('=', FStrings[n]) > 0 then
        Lines.Add(ReadPart(n, 1));
    end;
  Result := Lines.Count > 0;
end;

function TCustomIniFile.ReadLine(const Section: string; const Index: Integer): string;
var
  i: Integer;
begin
  i := SectionIndex(Section);
  if i > -1 then
    Result := FStrings[i + Index + 1]
  else
    Result := '';
end;

function TCustomIniFile.ReadPart(const LineIndex, PartIndex: Integer): string;
begin
  Result := JMC_Parts.ReadPart(FStrings[LineIndex], PartIndex, '=');
end;

function TCustomIniFile.ReadLines(const Section: string; const Lines: TCustomStrings; const SkipBlanks, TrimSpaces, AllowEmpty: Boolean): Boolean;
var
  i, n: Integer;
  function NextLineIsSection: Boolean;
  begin
    Result := False;
    if n < FStrings.Count - 1 then
      if Length(Trim(FStrings[n + 1])) > 1 then
        if Trim(FStrings[n + 1])[1] = '[' then
          Result := True;
  end;
begin
  Result := False;
  Lines.Clear;
  i := SectionIndex(Section);
  if i < 0 then
    Exit;
  for n := i + 1 to FStrings.Count - 1 do
  begin
    if SectionFound(n) then
      Break;
    if (SkipBlanks and (Trim(FStrings[n]) <> '')) or ((not SkipBlanks) and (not NextLineIsSection)) then
      if TrimSpaces then
        Lines.Add(Trim(FStrings[n]))
      else
        Lines.Add(FStrings[n]);
  end;
  Result := (Lines.Count > 0) or AllowEmpty;
end;

function TCustomIniFile.ReadSections(const Lines: TCustomStrings): Boolean;
var
  i: Integer;
begin
  Lines.Clear;
  for i := 0 to FStrings.Count - 1 do
    if SectionFound(i) then
      Lines.Add(JMC_Parts.ReadPart(JMC_Parts.ReadPart(FStrings[i], 2, '['), 1, ']'));
  Result := Lines.Count > 0;
end;

function TCustomIniFile.ReadValue(const Section, Key: string): string;
var
  i: Integer;
begin
  i := KeyIndex(Section, Key);
  if i > -1 then
    Result := ReadPart(i, 2)
  else
    Result := '';
end;

function TCustomIniFile.ReadValues(const Section: string; const Lines: TCustomStrings): Boolean;
var
  i, n: Integer;
begin
  Lines.Clear;
  i := SectionIndex(Section);
  if i > -1 then
    for n := i to FStrings.Count - 1 do
    begin
      if SectionFound(n) then
        Break;
      if Pos('=', FStrings[n]) > 0 then
        Lines.Add(ReadPart(n, 2));
    end;
  Result := Lines.Count > 0;
end;

function TCustomIniFile.RenameKey(const Section, OldKey, NewKey: string): Boolean;
var
  i: Integer;
begin
  i := KeyIndex(Section, OldKey);
  Result := i > -1;
  if Result then
    Result := WritePart(NewKey, i, 1);
end;

function TCustomIniFile.RenameSection(const OldSection, NewSection: string): Boolean;
var
  i: Integer;
begin
  i := SectionIndex(OldSection);
  Result := i > -1;
  if Result then
    FStrings[i] := '[' + NewSection + ']';
end;

function TCustomIniFile.SaveToFile(const FileName: string): Boolean;
begin
  Result := FStrings.SaveToFile(FileName);
end;

function TCustomIniFile.SectionExists(const Section: string): Boolean;
begin
  Result := SectionIndex(Section) <> -1;
end;

function TCustomIniFile.SectionIndex(const Section: string): Integer;
var
  i: Integer;
begin
  for i := 0 to FStrings.Count - 1 do
    if UpperCase(Trim(FStrings[i])) = '[' + UpperCase(Section) + ']' then
    begin
      Result := i;
      Exit;
    end;
  Result := -1;
end;

function TCustomIniFile.SectionFound(const LineIndex: Integer): Boolean;
begin
  Result := TrimLeft(Copy(FStrings[LineIndex], 1, 1)) = '[';
end;

procedure TCustomIniFile.SetStrings(const Value: TCustomStrings);
begin
  FStrings := Value;
end;

procedure TCustomIniFile.WriteLine(const Section, NewLine: string);
var
  i: Integer;
begin
  i := SectionIndex(Section);
  if i > -1 then
    FStrings[NextEmptyLineIndex(i + 1)] := NewLine
  else
    if Section <> '' then
    begin
      if Trim(FStrings[FStrings.Count - 1]) <> '' then
        FStrings.Add('');
      FStrings.Add('[' + Section + ']');
      FStrings.Add(NewLine);
    end;
end;

procedure TCustomIniFile.WriteLines(const Section: string; const Lines: TCustomStrings);
var
  n, i: Integer;
begin
  i := SectionIndex(Section);
  if i > -1 then
  begin
    n := NextEmptyLineIndex(i + 1);
    for i := 0 to Lines.Count - 1 do
      FStrings.Insert(n + i, Lines[i]);
  end
  else
    if Section <> '' then
    begin
      if Trim(FStrings[FStrings.Count - 1]) <> '' then
        FStrings.Add('');
      FStrings.Add('[' + Section + ']');
      for i := 0 to Lines.Count - 1 do
        FStrings.Add(Lines[i]);
    end;
end;

function TCustomIniFile.WritePart(const Part: string; const LineIndex, PartIndex: Integer): Boolean;
var
  S: string;
begin
  S := FStrings[LineIndex];
  Result := JMC_Parts.WritePart(S, Part, PartIndex, '=');
  if Result then
    FStrings[LineIndex] := S;
end;

function TCustomIniFile.WriteSection(const Section: string): Boolean;
var
  i: Integer;
begin
  Result := False;
  if Section = '' then
    Exit;
  i := SectionIndex(Section);
  if i > -1 then
    Exit;
  if Trim(FStrings[FStrings.Count - 1]) <> '' then
    FStrings.Add('');
  FStrings.Add('[' + Section + ']');
  Result := True;
end;

function TCustomIniFile.WriteValue(const Section, Key, Value: string): Boolean;
var
  i: Integer;
begin
  i := KeyIndex(Section, Key);
  if i > -1 then
  begin
    Result := WritePart(Value, i, 2);
    Exit;
  end;
  if Key <> '' then
  begin
    i := NextEmptyLineIndex(Section);
    if i > -1 then
    begin
      FStrings[i] := Key + '=' + Value;
      Result := True;
      Exit;
    end;
    if Section <> '' then
    begin
      if Trim(FStrings[FStrings.Count - 1]) <> '' then
        FStrings.Add('');
      FStrings.Add('[' + Section + ']');
      FStrings.Add(Key + '=' + Value);
      Result := True;
      Exit;
    end;
  end;
  Result := False;
end;

{ TStoredIniFile }

constructor TStoredIniFile.Create;
begin
  inherited;
  FStrings := TStringArray.Create;
end;

destructor TStoredIniFile.Destroy;
begin
  FStrings.Free;
  inherited;
end;

end.

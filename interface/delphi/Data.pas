unit Data;

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
  Utils;

type

  TSerializedArray = class;

  TSerialized = class(TObject)
  private
    FSerialized: string;
    FDataType: Char;
    FIntegerData: Integer;
    FDoubleData: Double;
    FStringData: string;
    FBooleanData: Boolean;
    FArrayData: TSerializedArray;
  private
    function ParseIntegerData(const Data: string): Boolean;
    function ParseDoubleData(const Data: string): Boolean;
    function ParseStringData(const Data: string): Boolean;
    function ParseBooleanData(const Data: string): Boolean;
    function ParseArrayData(const Data: string): Boolean;
  public
    constructor Create;
    destructor Destroy; override;
  public
    function Parse(const Serialized: string): Boolean;
  public
    property Serialized: string read FSerialized;
    property DataType: Char read FDataType;
  public
    property IntegerData: Integer read FIntegerData;
    property DoubleData: Double read FDoubleData;
    property StringData: string read FStringData;
    property BooleanData: Boolean read FBooleanData;
    property ArrayData: TSerializedArray read FArrayData;
  end;

  TSerializedArray = class(TObject)
  private
    FSerialized: string;
    FItems: Classes.TStrings;
  private
    function GetCount: Integer;
    function GetValue(const Key: string): TSerialized;
  public
    constructor Create;
    destructor Destroy; override;
  public
    procedure Clear;
    function IndexOf(const Key: string): Integer;
    function Parse(const Serialized: string): Boolean;
  public
    property Serialized: string read FSerialized;
    property Count: Integer read GetCount;
    property Items: Classes.TStrings read FItems;
    property Values[const Key: string]: TSerialized read GetValue;
  end;

implementation

{ TSerialized }

constructor TSerialized.Create;
begin
  FArrayData := TSerializedArray.Create;
end;

destructor TSerialized.Destroy;
begin
  FArrayData.Free;
  inherited;
end;

function TSerialized.Parse(const Serialized: string): Boolean;
var
  S: string;
begin
  Result := False;
  FIntegerData := 0;
  FDoubleData := 0.0;
  FStringData := '';
  FBooleanData := False;
  FArrayData.Clear;
  if Length(Serialized) < 3 then
    Exit;
  if Serialized[2] <> ':' then
    Exit;
  S := Copy(Serialized, 3, Length(Serialized) - 2);
  case Serialized[1] of
    'a':
      if ParseArrayData(Serialized) = False then
        Exit;
    'b':
      if ParseBooleanData(S) = False then
        Exit;
    'd':
      if ParseDoubleData(S) = False then
        Exit;
    'i':
      if ParseIntegerData(S) = False then
        Exit;
    's':
      if ParseStringData(S) = False then
        Exit;
  else
    Exit;
  end;
  FSerialized := Serialized;
  FDataType := Serialized[1];
  Result := True;
end;

function TSerialized.ParseArrayData(const Data: string): Boolean;
begin
  Result := FArrayData.Parse(Data);
end;

function TSerialized.ParseBooleanData(const Data: string): Boolean;
begin
  Result := False;
  if Data = '1' then
  begin
    FBooleanData := True;
    Result := True;
  end
  else
    if Data = '0' then
    begin
      FBooleanData := False;
      Result := True;
    end;
end;

function TSerialized.ParseDoubleData(const Data: string): Boolean;
begin
  try
    FDoubleData := SysUtils.StrToFloat(Data);
    Result := True;
  except
    FDoubleData := 0.0;
    Result := False;
  end;
end;

function TSerialized.ParseIntegerData(const Data: string): Boolean;
begin
  try
    FIntegerData := SysUtils.StrToInt(Data);
    Result := True;
  except
    FIntegerData := 0;
    Result := False;
  end;
end;

function TSerialized.ParseStringData(const Data: string): Boolean;
begin
  Result := ExtractSerialzedString(Data, FStringData);
end;

{ TSerializedArray }

procedure TSerializedArray.Clear;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    FItems.Objects[i].Free;
  FItems.Clear;
end;

constructor TSerializedArray.Create;
begin
  FItems := Classes.TStringList.Create;
end;

destructor TSerializedArray.Destroy;
begin
  Clear;
  FItems.Free;
  inherited;
end;

function TSerializedArray.GetCount: Integer;
begin
  Result := FItems.Count;
end;

function TSerializedArray.GetValue(const Key: string): TSerialized;
var
  i: Integer;
begin
  i := IndexOf(Key);
  if i >= 0 then
    Result := TSerialized(FItems.Objects[i])
  else
    Result := nil;
end;

function TSerializedArray.IndexOf(const Key: string): Integer;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    if FItems[i] = Key then
    begin
      Result := i;
      Exit;
    end;
  Result := -1;
end;

function TSerializedArray.Parse(const Serialized: string): Boolean;
var
  S: string;
begin
  FSerialized := Serialized;
  Result := False;
  // Serialized = 'a:1:{s:6:"server";s:12:"irc.sylnt.us";}'
  S := Serialized;
  Delete(S, 1, 2);
  // Serialized = '1:{s:6:"server";s:12:"irc.sylnt.us";}'
end;

end.
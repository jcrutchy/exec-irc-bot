unit Unserializer;

interface

uses
  Windows,
  SysUtils,
  Classes,
  Graphics,
  Controls,
  Forms,
  ComCtrls,
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
    function ParseIntegerData(const Data: string; var Len: Integer): Boolean;
    function ParseDoubleData(const Data: string; var Len: Integer): Boolean;
    function ParseStringData(const Data: string; var Len: Integer): Boolean;
    function ParseBooleanData(const Data: string; var Len: Integer): Boolean;
    function ParseArrayData(const Data: string; var Len: Integer): Boolean;
    function GetArrayValue(const Key: string): TSerialized;
  public
    constructor Create;
    destructor Destroy; override;
  public
    function ArrayParse(var Serialized: string): Boolean;
    function Parse(const Serialized: string): Boolean;
    function FillTreeView(const TreeView: TTreeView; const Parent: TTreeNode = nil): Boolean;
    function DataAsString: string;
  public
    property Serialized: string read FSerialized;
    property DataType: Char read FDataType;
  public
    property IntegerData: Integer read FIntegerData;
    property DoubleData: Double read FDoubleData;
    property StringData: string read FStringData;
    property BooleanData: Boolean read FBooleanData;
    property ArrayData: TSerializedArray read FArrayData;
    property ArrayValues[const Key: string]: TSerialized read GetArrayValue; default;
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
    function ParseLength(const Serialized: string): Integer;
  public
    property Serialized: string read FSerialized;
    property Count: Integer read GetCount;
    property Items: Classes.TStrings read FItems;
    property Values[const Key: string]: TSerialized read GetValue; default;
  end;

procedure RunUnserializeTests;

implementation

const
  ERROR_MESSAGE: string = 'ERROR: ClassName=%s Method=%s Serialized=%s';

procedure RunUnserializeTests;
var
  Msg: TSerialized;
  S: string;
  Passed: Boolean;
  FileName: string;
begin
  Passed := True;
  Msg := TSerialized.Create;
  (*S := 'a:3:{s:6:"server";s:12:"irc.sylnt.us";s:9:"microtime";d:1428721291.088635;s:4:"nick";s:4:"exec";}';
  if Msg.Parse(S) then
  begin
    if Msg.ArrayData.Count <> 3 then
      Passed := False;
    if Msg.ArrayData['server'] = nil then
      Passed := False
    else
      if Msg.ArrayData['server'].StringData <> 'irc.sylnt.us' then
        Passed := False;
  end
  else
    Passed := False;
  S := 'a:0:{}';
  if Msg.Parse(S) then
  begin
    if Msg.ArrayData.Count <> 0 then
      Passed := False;
  end
  else
    Passed := False;
  S := 'a:1:{s:4:"type";a:1:{s:7:"process";i:10;}}';
  if Msg.Parse(S) then
  begin
    if Msg.ArrayData.Count <> 1 then
      Passed := False;
  end
  else
  begin
    ShowMessage('Test failed: ' + Msg.Serialized);
    Passed := False;
  end;
  S := 'a:2:{s:3:"foo";a:0:{}s:3:"bar";i:3;}';
  if Msg.Parse(S) then
  begin
    if Msg.ArrayData.Count <> 2 then
      Passed := False;
  end
  else
  begin
    ShowMessage('Test failed: ' + Msg.Serialized);
    Passed := False;
  end;
  S := 'a:1:{s:3:"foo";b:1;}';
  if Msg.Parse(S) then
  begin
    if Msg.ArrayData.Count <> 1 then
      Passed := False;
  end
  else
  begin
    ShowMessage('Test failed: ' + Msg.Serialized);
    Passed := False;
  end;
  S := 'a:1:{s:10:"0123456789";b:1;}';
  if Msg.Parse(S) then
  begin
    if Msg.ArrayData.Count <> 1 then
      Passed := False;
  end
  else
  begin
    ShowMessage('Test failed: ' + Msg.Serialized);
    Passed := False;
  end;
  S := 'a:1:{s:28:"/BUCKET_GET MINION_CMD_sylnt";s:6:"handle";}';
  if Msg.Parse(S) then
  begin
    if Msg.ArrayData.Count <> 1 then
      Passed := False;
  end
  else
  begin
    ShowMessage('Test failed: ' + Msg.Serialized);
    Passed := False;
  end;*)

  FileName := ExtractFilePath(ParamStr(0)) + 'tests\test001.txt';
  if FileToStr(FileName, S) = False then
  begin
    ShowMessage('Test failed: ' + S);
    Passed := False;
  end
  else
    if Msg.Parse(S) then             // TODO: TRYING TO GET THIS TEST TO WORK - MESSAGES GETTING MIXED TOGETHER. MAYBE DUE TO LENGTH OF EXEC_LIST MESSAGES. TRY HAVE BOT BREAK UP INTO SUBARRAYS & SEE IF IT HELPS (THOUGH SEEMS HACKISH) ????
    begin

    end
    else
    begin
      ShowMessage('Test failed: ' + S);
      Passed := False;
    end;
    
  Msg.Free;
  if Passed then
    ShowMessage('Tests passed!');
end;

{ TSerialized }

function TSerialized.ArrayParse(var Serialized: string): Boolean;
var
  S: string;
  L: Integer;
begin
  Result := False;
  FSerialized := '';
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
  L := -1;
  case Serialized[1] of
    'a':
      if ParseArrayData(Serialized, L) = False then
        Exit;
    'b':
      if ParseBooleanData(S, L) = False then
        Exit;
    'd':
      if ParseDoubleData(S, L) = False then
        Exit;
    'i':
      if ParseIntegerData(S, L) = False then
        Exit;
    's':
      if ParseStringData(S, L) = False then
        Exit;
  else
    Exit;
  end;
  FSerialized := Serialized;
  FDataType := Serialized[1];
  if L >= 0 then
    Delete(Serialized, 1, L + 2);
  Result := True;
end;

constructor TSerialized.Create;
begin
  FArrayData := TSerializedArray.Create;
end;

function TSerialized.DataAsString: string;
begin
  Result := '';
  case FDataType of
    'b':
      if FBooleanData then
        Result := 'True'
      else
        Result := 'False';
    'd': Result := FloatToStr(FDoubleData);
    'i': Result := IntToStr(FIntegerData);
    's': Result := FStringData;
  end;
end;

destructor TSerialized.Destroy;
begin
  FArrayData.Free;
  inherited;
end;

function TSerialized.FillTreeView(const TreeView: TTreeView; const Parent: TTreeNode = nil): Boolean;
var
  N: TTreeNode;
  i: Integer;
begin
  N := TreeView.Items.AddChildObject(Parent, DataAsString, Self);
  for i := 0 to FArrayData.Count - 1 do
    TSerialized(FArrayData.Items.Objects[i]).FillTreeView(TreeView, N);
  Result := True;
end;

function TSerialized.GetArrayValue(const Key: string): TSerialized;
begin
  Result := FArrayData[Key];
end;

function TSerialized.Parse(const Serialized: string): Boolean;
var
  S: string;
begin
  S := Serialized;
  Result := ArrayParse(S);
end;

function TSerialized.ParseArrayData(const Data: string; var Len: Integer): Boolean;
begin
  Len := FArrayData.ParseLength(Data);
  Result := Len <> -1;
  if Result = False then
    FSerialized := SysUtils.Format(ERROR_MESSAGE, [Self.ClassName, 'ParseArrayData', FSerialized]);
end;

function TSerialized.ParseBooleanData(const Data: string; var Len: Integer): Boolean;
begin
  Len := 1;
  Result := False;
  if Data <> '' then
    if Data[1] = '1' then
    begin
      FBooleanData := True;
      Result := True;
    end
    else
      if Data[1] = '0' then
      begin
        FBooleanData := False;
        Result := True;
      end;
  if Result = False then
    FSerialized := SysUtils.Format(ERROR_MESSAGE, [Self.ClassName, 'ParseBooleanData', FSerialized]);
end;

function TSerialized.ParseDoubleData(const Data: string; var Len: Integer): Boolean;
var
  S: string;
  i: Integer;
begin
  S := Data;
  i := Pos(';', S);
  if i > 0 then
    S := Copy(S, 1, i - 1);
  Len := Length(S);
  try
    FDoubleData := SysUtils.StrToFloat(S);
    Result := True;
  except
    FDoubleData := 0.0;
    Result := False;
  end;
  if Result = False then
    FSerialized := SysUtils.Format(ERROR_MESSAGE, [Self.ClassName, 'ParseDoubleData', FSerialized]);
end;

function TSerialized.ParseIntegerData(const Data: string; var Len: Integer): Boolean;
var
  S: string;
  i: Integer;
begin
  Result := True;
  S := Data;
  i := Pos(';', S);
  if i > 0 then
    S := Copy(S, 1, i - 1);
  Len := Length(S);
  for i := 1 to Len do
    case S[i] of
      '0'..'9': ;
    else
      Result := False;
      Break;
    end;
  if Result then
    try
      FIntegerData := SysUtils.StrToInt(S);
    except
      Result := False;
      FIntegerData := 0;
    end;
  if Result = False then
    FSerialized := SysUtils.Format(ERROR_MESSAGE, [Self.ClassName, 'ParseIntegerData', FSerialized]);
end;

function TSerialized.ParseStringData(const Data: string; var Len: Integer): Boolean;
begin
  Result := Utils.ExtractSerialzedString(Data, FStringData);
  Len := Length(SysUtils.IntToStr(Length(FStringData))) + 3 + Length(FStringData);
  if Result = False then
    FSerialized := SysUtils.Format(ERROR_MESSAGE, [Self.ClassName, 'ParseStringData', FSerialized]);
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
begin
  Result := ParseLength(Serialized) <> -1;
end;

function TSerializedArray.ParseLength(const Serialized: string): Integer;
var
  S: string;
  n: Integer;
  i: Integer;
  Children: TList;
  Child: TSerialized;
begin
  FSerialized := Serialized;
  Result := -1;
  S := Serialized;
  Delete(S, 1, 2);
  i := Pos(':', S);
  if i <= 0 then
    Exit;
  try
    n := SysUtils.StrToInt(Copy(S, 1, i - 1)); // number of elements in the array
  except
    StrToFile(ExtractFilePath(ParamStr(0)) + 'debug.txt', S);
    Exit;
  end;
  Delete(S, 1, i);
  if Length(S) < 2 then
    Exit;
  if S[1] <> '{' then
    Exit;
  Delete(S, 1, 1);
  Children := TList.Create;
  try
    for i := 1 to n * 2 do
    begin
      Child := TSerialized.Create;
      if Child.ArrayParse(S) = False then
      begin
        Child.Free;
        Exit;
      end
      else
      begin
        Children.Add(Child);
        if (Child.DataType <> 'a') and (S <> '') then
          if S[1] = ';' then
            Delete(S, 1, 1)
          else
            Exit;
      end;
    end;
    for i := 0 to n - 1 do
    begin
      case TSerialized(Children[i * 2]).DataType of
        's': FItems.AddObject(TSerialized(Children[i * 2]).StringData, TSerialized(Children[i * 2 + 1]));
        'i': FItems.AddObject(SysUtils.IntToStr(TSerialized(Children[i * 2]).IntegerData), TSerialized(Children[i * 2 + 1]));
      else
        Exit;
      end;
    end;
    if Length(S) < 1 then
      Exit;
    if S[1] <> '}' then
      Exit;
    Delete(S, 1, 1);
    Result := Length(Serialized) - Length(S) - 2;
  finally
    if Result = -1 then
    begin
      for i := 0 to Children.Count - 1 do
        TSerialized(Children[i]).Free;
      FItems.Clear;
    end
    else
      for i := 0 to Children.Count - 1 do
        if i mod 2 = 0 then
          TSerialized(Children[i]).Free;
    Children.Free;
  end;
end;

end.
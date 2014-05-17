unit CDB_Design;

// gpl2
// by crutchy
// 17-may-2014

{$I DelphiVersions.inc}

interface

uses
{$IFDEF DELPHI6UP}
  DesignIntf,
  DesignEditors,
{$ENDIF}
{$IFDEF DELPHI3UP}
  DsgnIntf,
{$ENDIF}
  CDB,
  CDB_Reports,
  Classes,
  Dialogs,
  JMC_Strings;

type

  TCDB_FieldNameProperty = class(TStringProperty)
  public
    function GetAttributes: TPropertyAttributes; override;
    procedure GetValues(Proc: TGetStrProc); override;
  end;

  TCDB_OpenFileNameProperty = class(TStringProperty)
  private
    FOpenDialog: TOpenDialog;
  public
{$IFDEF DELPHI6UP}
    constructor Create(const ADesigner: IDesigner; APropCount: Integer); override;
{$ENDIF}
{$IFDEF DELPHI3UP}
    constructor Create(const ADesigner: IFormDesigner; APropCount: Integer); override;
{$ENDIF}
    destructor Destroy; override;
    procedure Edit; override;
    function GetAttributes: TPropertyAttributes; override;
  public
    property OpenDialog: TOpenDialog read FOpenDialog;
  end;

  TCDB_SaveFileNameProperty = class(TStringProperty)
  private
    FSaveDialog: TSaveDialog;
    FOpenDialog: TOpenDialog;
  public
{$IFDEF DELPHI6UP}
    constructor Create(const ADesigner: IDesigner; APropCount: Integer); override;
{$ENDIF}
{$IFDEF DELPHI3UP}
    constructor Create(const ADesigner: IFormDesigner; APropCount: Integer); override;
{$ENDIF}
    destructor Destroy; override;
    procedure Edit; override;
    function GetAttributes: TPropertyAttributes; override;
  public
    property OpenDialog: TOpenDialog read FOpenDialog;
  end;

  TCDB_DataLinkProperty = class(TClassProperty)
  public
    function GetAttributes: TPropertyAttributes; override;
  end;

  TCDB_GridPanelProperty = class(TClassProperty)
  public
    function GetAttributes: TPropertyAttributes; override;
  end;

procedure Register;

implementation

procedure Register;
begin
  RegisterComponents('CDB', [TCDB_Database, TCDB_Table, TCDB_Query, TCDB_Label, TCDB_Edit, TCDB_ComboBox, TCDB_CheckBox, TCDB_Memo, TCDB_Image, TCDB_Panel, TCDB_ControlGrid]);
  RegisterPropertyEditor(TypeInfo(TCDB_FieldName), nil, '', TCDB_FieldNameProperty);
  RegisterPropertyEditor(TypeInfo(TCDB_OpenFileName), nil, '', TCDB_OpenFileNameProperty);
  RegisterPropertyEditor(TypeInfo(TCDB_SaveFileName), nil, '', TCDB_SaveFileNameProperty);
  RegisterPropertyEditor(TypeInfo(TCDB_DataLink), nil, '', TCDB_DataLinkProperty);
  RegisterPropertyEditor(TypeInfo(TCDB_GridPanel), nil, '', TCDB_GridPanelProperty);
end;

{ TCDB_FieldNameProperty }

function TCDB_FieldNameProperty.GetAttributes: TPropertyAttributes;
begin
  Result := [paValueList, paSortList];
end;

procedure TCDB_FieldNameProperty.GetValues(Proc: TGetStrProc);
var
  i: Integer;
  DataLink: TCDB_FieldDataLink;
  FieldNames: TStringArray;
begin
  inherited;
  DataLink := TCDB_FieldDataLink(GetComponent(0));
  if DataLink <> nil then
    if DataLink is TCDB_FieldDataLink then
      if DataLink.RecordSet <> nil then
      begin
        FieldNames := TStringArray.Create;
        if DataLink.RecordSet.LoadFieldNames(FieldNames, DataLink.LinkDataType) then
          for i := 0 to FieldNames.Count - 1 do
            Proc(FieldNames[i]);
        FieldNames.Free;
      end;
end;

{ TCDB_OpenFileNameProperty }

{$IFDEF DELPHI6UP}
constructor TCDB_OpenFileNameProperty.Create(const ADesigner: IDesigner; APropCount: Integer);
begin
  inherited;
  FOpenDialog := TOpenDialog.Create(nil);
end;
{$ENDIF}
{$IFDEF DELPHI3UP}
constructor TCDB_OpenFileNameProperty.Create(const ADesigner: IFormDesigner; APropCount: Integer);
begin
  inherited;
  FOpenDialog := TOpenDialog.Create(nil);
end;
{$ENDIF}

destructor TCDB_OpenFileNameProperty.Destroy;
begin
  FOpenDialog.Free;
  inherited;
end;

procedure TCDB_OpenFileNameProperty.Edit;
var
  DefaultExt, Filter, Title, FileName: string;
  Component: TObject;
begin
  Component := GetComponent(0);
  if Component.InheritsFrom(TCDB_RecordSet) then
    TCDB_RecordSet(Component).GetOpenDialogProperties(GetName, DefaultExt, Filter, Title, FileName);
  if Component.InheritsFrom(TCDB_Report) then
    TCDB_Report(Component).GetOpenDialogProperties(GetName, DefaultExt, Filter, Title, FileName);
  FOpenDialog.DefaultExt := DefaultExt;
  FOpenDialog.Filter := Filter;
  FOpenDialog.Title := Title;
  FOpenDialog.FileName := FileName;
  FOpenDialog.Options := FOpenDialog.Options + [ofFileMustExist];
  if FOpenDialog.Execute then
    SetValue(FOpenDialog.FileName);
end;

function TCDB_OpenFileNameProperty.GetAttributes: TPropertyAttributes;
begin
  Result := [paDialog];
end;

{ TCDB_DataLinkProperty }

function TCDB_DataLinkProperty.GetAttributes: TPropertyAttributes;
begin
  Result := [paSubProperties, paReadOnly];
end;

{ TCDB_GridPanelProperty }

function TCDB_GridPanelProperty.GetAttributes: TPropertyAttributes;
begin
  Result := [paSubProperties, paReadOnly];
end;

{ TCDB_SaveFileNameProperty }

{$IFDEF DELPHI6UP}
constructor TCDB_SaveFileNameProperty.Create(const ADesigner: IDesigner; APropCount: Integer);
begin
  inherited;
  FSaveDialog := TSaveDialog.Create(nil);
end;
{$ENDIF}
{$IFDEF DELPHI3UP}
constructor TCDB_SaveFileNameProperty.Create(const ADesigner: IFormDesigner; APropCount: Integer);
begin
  inherited;
  FSaveDialog := TSaveDialog.Create(nil);
end;
{$ENDIF}

destructor TCDB_SaveFileNameProperty.Destroy;
begin
  FSaveDialog.Free;
  inherited;
end;

procedure TCDB_SaveFileNameProperty.Edit;
var
  DefaultExt, Filter, Title, FileName: string;
  Component: TObject;
begin
  Component := GetComponent(0);
  if Component.InheritsFrom(TCDB_Report) then
    TCDB_Report(Component).GetSaveDialogProperties(GetName, DefaultExt, Filter, Title, FileName);
  FSaveDialog.DefaultExt := DefaultExt;
  FSaveDialog.Filter := Filter;
  FSaveDialog.Title := Title;
  FSaveDialog.FileName := FileName;
  if FSaveDialog.Execute then
    SetValue(FSaveDialog.FileName);
end;

function TCDB_SaveFileNameProperty.GetAttributes: TPropertyAttributes;
begin
  Result := [paDialog];
end;

end.

unit CDB;

// gpl2
// by crutchy
// 17-may-2014

interface

uses
  Classes,
  Controls,
  Dialogs,
  ExtCtrls,
  StdCtrls,
  SysUtils,
  Math,
  Graphics,
  Forms,
  Menus,
  Messages,
  Clipbrd,
  Windows,
  JMC_Strings,
  JMC_FileUtils,
  JMC_Parts;

const
  CDBM_GETDATALINK = WM_USER + 1;

type

  TCDB_Field = class;
  TCDB_DataLink = class;
  TCDB_Record = class;
  TCDB_RecordSet = class;
  TCDB_Database = class;
  TCDB_Image = class;

  TCDB_DataType = (dtError, dtString, dtInteger, dtCurrency, dtFloat, dtDateTime, dtBoolean, dtStrings, dtImage);
  TCDB_LinkDataType = (ldtNone, ldtString, ldtBoolean, ldtStrings, ldtImage);
  TCDB_LinkReference = (lrActive, lrIndex);

  TCDB_RecordSetNotifyEvent = procedure(const RecordSet: TCDB_RecordSet) of object;
  TCDB_ProcedureEvent = procedure of object;
  TCDB_ChildDataLinkChangedEvent = procedure(const Child: TCDB_DataLink) of object;

  TCDB_OpenFileName = type string;
  TCDB_SaveFileName = type string;
  TCDB_FieldName = type string;

  TCDB_FieldDef = class(TObject)
  private
    FDataType: TCDB_DataType;
    FDuplicates: Boolean;
    FFieldIndex: Integer;
    FFieldName: TCDB_FieldName;
    FRecordSet: TCDB_RecordSet;
    FRequired: Boolean;
  protected
    function Empty(const Field: TCDB_Field): Boolean;
    function Equal(const Field1, Field2: TCDB_Field): Boolean; virtual; abstract;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); virtual;
    procedure Assign(const Source: TCDB_FieldDef); virtual;
    procedure Update;
    function ValidateRecord(const RecordIndex: Integer): Boolean; virtual;
  public
    property DataType: TCDB_DataType read FDataType;
    property Duplicates: Boolean read FDuplicates write FDuplicates;
    property FieldIndex: Integer read FFieldIndex;
    property FieldName: TCDB_FieldName read FFieldName write FFieldName;
    property RecordSet: TCDB_RecordSet read FRecordSet;
    property Required: Boolean read FRequired write FRequired;
  end;

  TCDB_FieldDefs = class(TObject)
  private
    FFieldCount: Integer;
    FFieldDefs: array of TCDB_FieldDef;
    FRecordSet: TCDB_RecordSet;
    function GetFieldDef(const Index: Integer): TCDB_FieldDef;
  public
    constructor Create(const RecordSet: TCDB_RecordSet);
    destructor Destroy; override;
    procedure AddFieldDef(const DataType: TCDB_DataType);
    procedure Clear;
    function MoveFieldDef(const FromIndex, ToIndex: Integer): Boolean;
  public
    property FieldCount: Integer read FFieldCount;
    property FieldDefs[const Index: Integer]: TCDB_FieldDef read GetFieldDef; default;
  end;

  TCDB_StringFieldDef = class(TCDB_FieldDef)
  private
    FDefaultValue: string;
    FMaximumLength: Integer;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
    procedure Assign(const Source: TCDB_FieldDef); override;
  public
    property DefaultValue: string read FDefaultValue write FDefaultValue;
    property MaximumLength: Integer read FMaximumLength write FMaximumLength;
  end;

  TCDB_IntegerFieldDef = class(TCDB_FieldDef)
  private
    FDefaultValue: Integer;
    FLowerRange: Integer;
    FLowerRangeEnabled: Boolean;
    FUpperRange: Integer;
    FUpperRangeEnabled: Boolean;
    FIncrement: Boolean;
    FIncrementValue: Integer;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
    procedure Assign(const Source: TCDB_FieldDef); override;
    function ValidateRecord(const RecordIndex: Integer): Boolean; override;
  public
    property DefaultValue: Integer read FDefaultValue write FDefaultValue;
    property LowerRange: Integer read FLowerRange write FLowerRange;
    property LowerRangeEnabled: Boolean read FLowerRangeEnabled write FLowerRangeEnabled;
    property UpperRange: Integer read FUpperRange write FUpperRange;
    property UpperRangeEnabled: Boolean read FUpperRangeEnabled write FUpperRangeEnabled;
    property Increment: Boolean read FIncrement write FIncrement;
    property IncrementValue: Integer read FIncrementValue write FIncrementValue;
  end;

  TCDB_CurrencyFieldDef = class(TCDB_FieldDef)
  private
    FDefaultValue: Currency;
    FLowerRange: Currency;
    FLowerRangeEnabled: Boolean;
    FUpperRange: Currency;
    FUpperRangeEnabled: Boolean;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
    procedure Assign(const Source: TCDB_FieldDef); override;
    function ValidateRecord(const RecordIndex: Integer): Boolean; override;
  public
    property DefaultValue: Currency read FDefaultValue write FDefaultValue;
    property LowerRange: Currency read FLowerRange write FLowerRange;
    property LowerRangeEnabled: Boolean read FLowerRangeEnabled write FLowerRangeEnabled;
    property UpperRange: Currency read FUpperRange write FUpperRange;
    property UpperRangeEnabled: Boolean read FUpperRangeEnabled write FUpperRangeEnabled;
  end;

  TCDB_FloatFieldDef = class(TCDB_FieldDef)
  private
    FDefaultValue: Double;
    FLowerRange: Double;
    FLowerRangeEnabled: Boolean;
    FUpperRange: Double;
    FUpperRangeEnabled: Boolean;
    FDecimalPlaces: Integer;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
    procedure Assign(const Source: TCDB_FieldDef); override;
    function ValidateRecord(const RecordIndex: Integer): Boolean; override;
  public
    property DefaultValue: Double read FDefaultValue write FDefaultValue;
    property LowerRange: Double read FLowerRange write FLowerRange;
    property LowerRangeEnabled: Boolean read FLowerRangeEnabled write FLowerRangeEnabled;
    property UpperRange: Double read FUpperRange write FUpperRange;
    property UpperRangeEnabled: Boolean read FUpperRangeEnabled write FUpperRangeEnabled;
    property DecimalPlaces: Integer read FDecimalPlaces write FDecimalPlaces;
  end;

  TCDB_DateTimeFieldDef = class(TCDB_FieldDef)
  private
    FDefaultEmpty: Boolean;
    FDefaultNow: Boolean;
    FDefaultValue: TDateTime;
    FFormat: string;
    FLowerRange: TDateTime;
    FLowerRangeEnabled: Boolean;
    FUpperRange: TDateTime;
    FUpperRangeEnabled: Boolean;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
    procedure Assign(const Source: TCDB_FieldDef); override;
    function ValidateRecord(const RecordIndex: Integer): Boolean; override;
  public
    property DefaultEmpty: Boolean read FDefaultEmpty write FDefaultEmpty;
    property DefaultNow: Boolean read FDefaultNow write FDefaultNow;
    property DefaultValue: TDateTime read FDefaultValue write FDefaultValue;
    property Format: string read FFormat write FFormat;
    property LowerRange: TDateTime read FLowerRange write FLowerRange;
    property LowerRangeEnabled: Boolean read FLowerRangeEnabled write FLowerRangeEnabled;
    property UpperRange: TDateTime read FUpperRange write FUpperRange;
    property UpperRangeEnabled: Boolean read FUpperRangeEnabled write FUpperRangeEnabled;
  end;

  TCDB_BooleanFieldDef = class(TCDB_FieldDef)
  private
    FDefaultValue: Boolean;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
    procedure Assign(const Source: TCDB_FieldDef); override;
  public
    property DefaultValue: Boolean read FDefaultValue write FDefaultValue;
  end;

  TCDB_BlobFieldDef = class(TCDB_FieldDef)
  private
    FDefaultFileName: string;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    procedure Assign(const Source: TCDB_FieldDef); override;
    function LoadDefaultValue(const FileName: string): Boolean; virtual;
    function ValidateRecord(const RecordIndex: Integer): Boolean; override;
  public
    property DefaultFileName: string read FDefaultFileName;
  end;

  TCDB_StringsFieldDef = class(TCDB_BlobFieldDef)
  private
    FDefaultValue: string;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
    procedure Assign(const Source: TCDB_FieldDef); override;
    function LoadDefaultValue(const FileName: string): Boolean; override;
  public
    property DefaultValue: string read FDefaultValue;
  end;

  TCDB_ImageFieldDef = class(TCDB_BlobFieldDef)
  private
    FDefaultValue: TImage;
  protected
    function Equal(const Field1, Field2: TCDB_Field): Boolean; override;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
    destructor Destroy; override;
    procedure Assign(const Source: TCDB_FieldDef); override;
    function LoadDefaultValue(const FileName: string): Boolean; override;
  public
    property DefaultValue: TImage read FDefaultValue;
  end;

  TCDB_Field = class(TObject)
  private
    FFieldDef: TCDB_FieldDef;
    FCDB_Record: TCDB_Record;
  protected
    function GetValueAsString: string; virtual;
    procedure SetValueAsString(const Value: string); virtual;
  public
    constructor Create(const FieldDef: TCDB_FieldDef; const CDB_Record: TCDB_Record); virtual;
    function Empty: Boolean; virtual;
    procedure InitializeValue; virtual; abstract;
  public
    property ValueAsString: string read GetValueAsString write SetValueAsString;
  public
    property FieldDef: TCDB_FieldDef read FFieldDef;
    property CDB_Record: TCDB_Record read FCDB_Record;
  end;

  TCDB_StringField = class(TCDB_Field)
  private
    FValue: string;
  protected
    function GetValueAsString: string; override;
    procedure SetValueAsString(const Value: string); override;
  public
    function Empty: Boolean; override;
    procedure InitializeValue; override;
  public
    property Value: string read GetValueAsString write SetValueAsString;
  end;

  TCDB_IntegerField = class(TCDB_Field)
  private
    FValue: Integer;
    procedure SetValue(const Value: Integer);
  protected
    function GetValueAsString: string; override;
    procedure SetValueAsString(const Value: string); override;
  public
    procedure InitializeValue; override;
  public
    property Value: Integer read FValue write SetValue;
  end;

  TCDB_CurrencyField = class(TCDB_Field)
  private
    FValue: Currency;
    procedure SetValue(const Value: Currency);
  protected
    function GetValueAsString: string; override;
    procedure SetValueAsString(const Value: string); override;
  public
    procedure InitializeValue; override;
  public
    property Value: Currency read FValue write SetValue;
  end;

  TCDB_FloatField = class(TCDB_Field)
  private
    FValue: Double;
    procedure SetValue(const Value: Double);
  protected
    function GetValueAsString: string; override;
    procedure SetValueAsString(const Value: string); override;
  public
    procedure InitializeValue; override;
  public
    property Value: Double read FValue write SetValue;
  end;

  TCDB_DateTimeField = class(TCDB_Field)
  private
    FValue: TDateTime;
    FValueEmpty: Boolean;
    procedure SetValue(const Value: TDateTime);
    procedure SetValueEmpty(const Value: Boolean);
  protected
    function GetValueAsString: string; override;
    procedure SetValueAsString(const Value: string); override;
  public
    function Empty: Boolean; override;
    procedure InitializeValue; override;
  public
    property Value: TDateTime read FValue write SetValue;
    property ValueEmpty: Boolean read FValueEmpty write SetValueEmpty;
  end;

  TCDB_BooleanField = class(TCDB_Field)
  private
    FValue: Boolean;
    procedure SetValue(const Value: Boolean);
  protected
    function GetValueAsString: string; override;
    procedure SetValueAsString(const Value: string); override;
  public
    procedure InitializeValue; override;
  public
    property Value: Boolean read FValue write SetValue;
  end;

  TCDB_BlobField = class(TCDB_Field)
  private
    FFileName: string;
  protected
    function GetValueAsString: string; override;
    procedure SetValueAsString(const Value: string); override;
  public
    procedure ClearValue; virtual; abstract;
    function Empty: Boolean; override;
    procedure InitializeValue; override;
    function LoadValue(const FileName: string): Boolean; overload; virtual;
    function LoadValue: Boolean; overload;
    procedure SaveValue(const FileName: string); overload; virtual;
    procedure SaveValue; overload;
  public
    property FileName: string read FFileName write SetValueAsString;
  end;

  TCDB_StringsField = class(TCDB_BlobField)
  private
    FValue: string;
    procedure SetValue(const Value: string);
  public
    procedure ClearValue; override;
    function Empty: Boolean; override;
    procedure InitializeValue; override;
    function LoadValue(const FileName: string): Boolean; override;
    procedure SaveValue(const FileName: string); override;
  public
    property Value: string read FValue write SetValue;
  end;

  TCDB_ImageField = class(TCDB_BlobField)
  private
    FValue: TImage;
    procedure SetValue(const Value: TImage);
  public
    constructor Create(const FieldDef: TCDB_FieldDef; const CDB_Record: TCDB_Record); override;
    destructor Destroy; override;
    procedure ClearValue; override;
    function Empty: Boolean; override;
    procedure InitializeValue; override;
    function LoadValue(const FileName: string): Boolean; override;
    procedure SaveValue(const FileName: string); override;
  public
    property Value: TImage read FValue write SetValue;
  end;

  TCDB_DataLink = class(TPersistent)
  private
    FChildCount: Integer;
    FChildren: array of TCDB_DataLink;
    FParent: TCDB_DataLink;
    FReference: TCDB_LinkReference;
    FRecordIndex: Integer;
    FOnUpdateOutput: TCDB_ProcedureEvent;
    FOnChildChanged: TCDB_ChildDataLinkChangedEvent;
    FRecordSet: TCDB_RecordSet;
    FRecordSetLocked: Boolean;
    function GetActiveRecordIndex: Integer;
    procedure SetRecordIndex(const Value: Integer);
    procedure SetReference(const Value: TCDB_LinkReference);
    function GetChild(const Index: Integer): TCDB_DataLink;
  protected
    function GetActive: Boolean; virtual;
    procedure SetRecordSet(const Value: TCDB_RecordSet); virtual;
  public
    constructor Create; virtual;
    destructor Destroy; override;
    procedure AddChild(const Child: TCDB_DataLink); virtual;
    procedure Assign(Source: TPersistent); override;
    procedure DeleteChild(const Child: TCDB_DataLink);
    procedure Associate; virtual;
    procedure UpdateOutput; virtual;
    procedure ChildChanged(const Child: TCDB_DataLink); virtual;
    procedure FirstRecord;
    procedure PreviousRecord;
    procedure NextRecord;
    procedure LastRecord;
    procedure LockRecordSet;
    procedure UnlockRecordSet;
  public
    property Active: Boolean read GetActive;
    property ActiveRecordIndex: Integer read GetActiveRecordIndex;
    property RecordIndex: Integer read FRecordIndex write SetRecordIndex;
    property OnUpdateOutput: TCDB_ProcedureEvent read FOnUpdateOutput write FOnUpdateOutput;
    property OnChildChanged: TCDB_ChildDataLinkChangedEvent read FOnChildChanged write FOnChildChanged;
    property RecordSetLocked: Boolean read FRecordSetLocked;
    property Parent: TCDB_DataLink read FParent write FParent;
    property ChildCount: Integer read FChildCount;
    property Children[const Index: Integer]: TCDB_DataLink read GetChild;
  public
    property RecordSet: TCDB_RecordSet read FRecordSet write SetRecordSet;
    property Reference: TCDB_LinkReference read FReference write SetReference;
  end;

  TCDB_ParentRecordSetDataLink = class(TCDB_DataLink)
  protected
    procedure SetRecordSet(const Value: TCDB_RecordSet); override;
  public
    procedure AddChild(const Child: TCDB_DataLink); override;
  published
    property RecordSet;
  end;

  TCDB_WindowDataLink = class(TCDB_ParentRecordSetDataLink)
  public
    constructor Create; override;
    procedure ControlListChange(const Control: TControl; const Inserting: Boolean);
    procedure UpdateOutput; override;
    procedure ChildChanged(const Child: TCDB_DataLink); override;
  end;

  TCDB_FieldDataLink = class(TCDB_DataLink)
  private
    FFieldDef: TCDB_FieldDef;
    FLinkDataType: TCDB_LinkDataType;
    FFieldName: TCDB_FieldName;
    procedure SetFieldName(const Value: TCDB_FieldName);
    function GetCurrentGenericField: TCDB_Field;
    function GetGenericField(const RecordIndex: Integer): TCDB_Field;
  protected
    function GetActive: Boolean; override;
    procedure SetRecordSet(const Value: TCDB_RecordSet); override;
  public
    procedure Assign(Source: TPersistent); override;
    procedure Associate; override;
  public
    property CurrentGenericField: TCDB_Field read GetCurrentGenericField;
    property FieldDef: TCDB_FieldDef read FFieldDef;
    property LinkDataType: TCDB_LinkDataType read FLinkDataType;
    property GenericFields[const RecordIndex: Integer]: TCDB_Field read GetGenericField;
  public
    property FieldName: TCDB_FieldName read FFieldName write SetFieldName;
  end;

  TCDB_StringDataLink = class(TCDB_FieldDataLink)
  private
    function GetCurrentValue: string;
    procedure SetCurrentValue(const Value: string);
    function GetValue(const RecordIndex: Integer): string;
    procedure SetValue(const RecordIndex: Integer; const Value: string);
  public
    constructor Create; override;
  public
    property CurrentValue: string read GetCurrentValue write SetCurrentValue;
    property Values[const RecordIndex: Integer]: string read GetValue write SetValue; default;
  published
    property FieldName;
    property RecordSet;
  end;

  TCDB_ComboBoxLookupDataLink = class(TCDB_ParentRecordSetDataLink)
  private
    FBound: TCDB_StringDataLink;
    FDisplayed: TCDB_StringDataLink;
  private
    procedure SetBound(const Value: TCDB_StringDataLink);
    procedure SetDisplayed(const Value: TCDB_StringDataLink);
  public
    constructor Create; override;
    destructor Destroy; override;
  public
    procedure Assign(Source: TPersistent); override;
    procedure Associate; override;
  published
    property Bound: TCDB_StringDataLink read FBound write SetBound;
    property Displayed: TCDB_StringDataLink read FDisplayed write SetDisplayed;
  end;

  TCDB_BooleanDataLink = class(TCDB_FieldDataLink)
  private
    function GetCurrentValue: Boolean;
    procedure SetCurrentValue(const Value: Boolean);
  public
    constructor Create; override;
  public
    property CurrentValue: Boolean read GetCurrentValue write SetCurrentValue;
  published
    property FieldName;
    property RecordSet;
  end;

  TCDB_StringsDataLink = class(TCDB_FieldDataLink)
  private
    function GetCurrentValue: string;
    procedure SetCurrentValue(const Value: string);
    function GetCurrentField: TCDB_StringsField;
  public
    constructor Create; override;
  public
    property CurrentField: TCDB_StringsField read GetCurrentField;
    property CurrentValue: string read GetCurrentValue write SetCurrentValue;
  published
    property FieldName;
    property RecordSet;
  end;

  TCDB_ImageDataLink = class(TCDB_FieldDataLink)
  private
    function GetCurrentField: TCDB_ImageField;
    function GetCurrentValue: TImage;
    procedure SetCurrentValue(const Value: TImage);
  public
    constructor Create; override;
  public
    property CurrentField: TCDB_ImageField read GetCurrentField;
    property CurrentValue: TImage read GetCurrentValue write SetCurrentValue;
  published
    property FieldName;
    property RecordSet;
  end;

  TCDB_Record = class(TObject)
  private
    FFields: array of TCDB_Field;
    FRecordIndex: Integer;
    function GetField(const Index: Integer): TCDB_Field;
  public
    constructor Create(const RecordSet: TCDB_RecordSet); virtual;
    destructor Destroy; override;
  public
    property Fields[const Index: Integer]: TCDB_Field read GetField; default;
    property RecordIndex: Integer read FRecordIndex;
  end;

  TCDB_StoredRecord = class(TCDB_Record)
  public
    constructor Create(const RecordSet: TCDB_RecordSet); override;
  end;

  TCDB_RecordSet = class(TComponent)
  private
    FActive: Boolean;
    FActiveRecordIndex: Integer;
    FLoading: Boolean;
    FUpdatingDatabase: Boolean;
    FDatabase: TCDB_Database;
    FDataLinkCount: Integer;
    FDataLinks: array of TCDB_DataLink;
    FFieldDefs: TCDB_FieldDefs;
    FFileName: TCDB_OpenFileName;
    FRecordCount: Integer;
    FRecords: array of TCDB_Record;
    procedure AssociateDataLinks;
    function GetDataLink(const Index: Integer): TCDB_DataLink;
    procedure SetActiveRecordIndex(const Value: Integer);
    function GetLast: TCDB_Record;
    procedure SetFileName(const Value: TCDB_OpenFileName);
    procedure SetDatabase(const Value: TCDB_Database);
    function GetFieldCount: Integer;
  private
    FAfterAddRecord: TCDB_RecordSetNotifyEvent;
    FAfterClose: TCDB_RecordSetNotifyEvent;
    FAfterDeleteRecord: TCDB_RecordSetNotifyEvent;
    FAfterEdit: TCDB_RecordSetNotifyEvent;
    FAfterOpen: TCDB_RecordSetNotifyEvent;
    FAfterScrollRecords: TCDB_RecordSetNotifyEvent;
    FOnRecordInvalid: TCDB_RecordSetNotifyEvent;
  protected
    function ChangeActiveRecord(const RecordIndex: Integer): Boolean;
    function GetActive: Boolean; virtual;
    function GetFieldDef(const Index: Integer): TCDB_FieldDef; virtual;
    function GetRecord(const Index: Integer): TCDB_Record; virtual;
    procedure Loaded; override;
    procedure RecordAdded;
    procedure SetActive(const Value: Boolean); virtual;
    function ValidateActiveRecord: Boolean;
    function ValidateAllRecords: Boolean;
    function ValidateRecord(const Index: Integer): Boolean;
  protected
    procedure Before_AfterAddRecord_Event; virtual;
    procedure Before_AfterClose_Event; virtual;
    procedure Before_AfterDeleteRecord_Event; virtual;
    procedure Before_AfterEdit_Event; virtual;
    procedure Before_AfterOpen_Event; virtual;
    procedure Before_AfterScrollRecords_Event; virtual;
    procedure Before_OnRecordInvalid_Event; virtual;
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
    procedure Activate;
    function ActiveRecordIndexValid: Boolean;
    procedure AddDataLink(const DataLink: TCDB_DataLink);
    procedure AddFieldDef(const DataType: TCDB_DataType);
    function AddRecord: Boolean; virtual;
    procedure DatabaseChanged; virtual;
    procedure Deactivate;
    procedure DeleteActiveRecord;
    procedure DeleteDataLink(const DataLink: TCDB_DataLink);
    procedure DeleteRecord(const Index: Integer);
    procedure FirstRecord;
    procedure GetOpenDialogProperties(const PropertyName: string; var DefaultExt, Filter, Title, FileName: string); virtual;
    procedure LastRecord;
    function LoadFieldNames(const Strings: TStringArray; const DataLinkType: TCDB_LinkDataType): Boolean; virtual; abstract;
    function LoadFromFile(const FileName: string): Boolean; overload; virtual;
    function LoadFromFile: Boolean; overload;
    function NextRecord: Boolean;
    function PreviousRecord: Boolean;
    procedure Reset;
    function SaveToFile(const FileName: string): Boolean; overload; virtual;
    function SaveToFile: Boolean; overload;
    procedure UpdateDatabase;
    procedure UpdateLinks;
    function ValidateRecordIndex(const Index: Integer): Boolean;
  public
    property ActiveRecordIndex: Integer read FActiveRecordIndex write SetActiveRecordIndex;
    property DataLinkCount: Integer read FDataLinkCount;
    property DataLinks[const Index: Integer]: TCDB_DataLink read GetDataLink;
    property FieldCount: Integer read GetFieldCount;
    property FieldDefs: TCDB_FieldDefs read FFieldDefs;
    property Last: TCDB_Record read GetLast;
    property RecordCount: Integer read FRecordCount;
    property Records[const Index: Integer]: TCDB_Record read GetRecord; default;
  published
    property Active: Boolean read GetActive write SetActive;
    property Database: TCDB_Database read FDatabase write SetDatabase;
    property FileName: TCDB_OpenFileName read FFileName write SetFileName;
  published
    property AfterAddRecord: TCDB_RecordSetNotifyEvent read FAfterAddRecord write FAfterAddRecord;
    property AfterClose: TCDB_RecordSetNotifyEvent read FAfterClose write FAfterClose;
    property AfterDeleteRecord: TCDB_RecordSetNotifyEvent read FAfterDeleteRecord write FAfterDeleteRecord;
    property AfterEdit: TCDB_RecordSetNotifyEvent read FAfterEdit write FAfterEdit;
    property AfterOpen: TCDB_RecordSetNotifyEvent read FAfterOpen write FAfterOpen;
    property AfterScrollRecords: TCDB_RecordSetNotifyEvent read FAfterScrollRecords write FAfterScrollRecords;
    property OnRecordInvalid: TCDB_RecordSetNotifyEvent read FOnRecordInvalid write FOnRecordInvalid;
  end;

  TCDB_Database = class(TComponent)
  private
    FRecordSetCount: Integer;
    FRecordSets: array of TCDB_RecordSet;
    function GetRecordSet(const Index: Integer): TCDB_RecordSet;
  public
    destructor Destroy; override;
    procedure Changed(const Sender: TCDB_RecordSet);
    procedure AddRecordSet(const RecordSet: TCDB_RecordSet);
    procedure BecomeOwner(const RecordSet: TCDB_RecordSet);
    function DeleteRecordSet(const RecordSet: TCDB_RecordSet): Boolean;
    function LoadRecordSet(const FileName: string): Boolean;
    function RecordSetIndex(const FileName: string): Integer;
  public
    property RecordSetCount: Integer read FRecordSetCount;
    property RecordSets[const Index: Integer]: TCDB_RecordSet read GetRecordSet;
  end;

  TCDB_TableFieldDefs = class(TCDB_FieldDefs)
  public
    procedure LoadFieldDefs(const TableFile: TCustomIniFile; const FileName: string);
    procedure SaveFieldDefs(const TableFile: TCustomIniFile);
  end;

  TCDB_Table = class(TCDB_RecordSet)
  protected
    procedure SetActive(const Value: Boolean); override;
  public
    function AddRecord: Boolean; override;
    procedure GetOpenDialogProperties(const PropertyName: string; var DefaultExt, Filter, Title, FileName: string); override;
    function LoadFieldNames(const Strings: TStringArray; const DataLinkType: TCDB_LinkDataType): Boolean; override;
    function LoadFromFile(const FileName: string): Boolean; override;
    function SaveToFile(const FileName: string): Boolean; override;
  end;

  TCDB_Query = class(TCDB_RecordSet)
  private
    FAfterExecute: TCDB_RecordSetNotifyEvent;
  private
    FFieldRecordSets: array of TCDB_RecordSet;
    FFieldIndexes: array of Integer;
    FFieldNames : array of string;
    FRecordSetCount: Integer;
    FRecordSets: array of TCDB_RecordSet;
    FRecordSetAliases: array of string;
    function GetFieldDef2(const RecordSetAlias, FieldName: string): TCDB_FieldDef;
    function GetRecordSet(const Index: Integer): TCDB_RecordSet;
    function GetRecordSetAlias(const Index: Integer): string;
  protected
    procedure SetActive(const Value: Boolean); override;
  protected
    procedure Before_AfterExecute_Event; virtual;
  public
    destructor Destroy; override;
    function AddRecord: Boolean; override;
    procedure AddRecordSet(const RecordSet: TCDB_RecordSet; const Alias: string);
    procedure DatabaseChanged; override;
    procedure Execute;
    procedure GetOpenDialogProperties(const PropertyName: string; var DefaultExt, Filter, Title, FileName: string); override;
    function LoadFieldNames(const Strings: TStringArray; const DataLinkType: TCDB_LinkDataType): Boolean; override;
    function LoadFromFile(const FileName: string): Boolean; override;
  public
    property RecordSetCount: Integer read FRecordSetCount;
    property RecordSetAliases[const Index: Integer]: string read GetRecordSetAlias;
    property RecordSets[const Index: Integer]: TCDB_RecordSet read GetRecordSet;
  published
    property AfterExecute: TCDB_RecordSetNotifyEvent read FAfterExecute write FAfterExecute;
  end;

  TCDB_BlobPopupMenu = class(TPopupMenu)
  private
    FOpenDialog: TOpenDialog;
    FSaveDialog: TSaveDialog;
  protected
    procedure ChangeFileNameMenuItemClick(Sender: TObject); virtual; abstract;
    procedure ClearMenuItemClick(Sender: TObject); virtual; abstract;
    procedure CopyMenuItemClick(Sender: TObject); virtual; abstract;
    procedure CutMenuItemClick(Sender: TObject); virtual; abstract;
    procedure OpenMenuItemClick(Sender: TObject); virtual; abstract;
    procedure PasteMenuItemClick(Sender: TObject); virtual; abstract;
    procedure ReopenMenuItemClick(Sender: TObject); virtual; abstract;
    procedure SaveAsMenuItemClick(Sender: TObject); virtual; abstract;
    procedure SaveMenuItemClick(Sender: TObject); virtual; abstract;
  public
    constructor Create(AOwner: TComponent); override;
    procedure Refresh; virtual;
  public
    property OpenDialog: TOpenDialog read FOpenDialog;
    property SaveDialog: TSaveDialog read FSaveDialog;
  end;

  TCDB_ImagePopupMenu = class(TCDB_BlobPopupMenu)
  private
    FImage: TCDB_Image;
  protected
    procedure ChangeFileNameMenuItemClick(Sender: TObject); override;
    procedure ClearMenuItemClick(Sender: TObject); override;
    procedure CopyMenuItemClick(Sender: TObject); override;
    procedure CutMenuItemClick(Sender: TObject); override;
    procedure OpenMenuItemClick(Sender: TObject); override;
    procedure PasteMenuItemClick(Sender: TObject); override;
    procedure ReopenMenuItemClick(Sender: TObject); override;
    procedure SaveAsMenuItemClick(Sender: TObject); override;
    procedure SaveMenuItemClick(Sender: TObject); override;
  public
    constructor Create(AOwner: TComponent); override;
    procedure Refresh; override;
  public
    property Image: TCDB_Image read FImage;
  end;

  TCDB_Label = class(TLabel)
  private
    FDataLink: TCDB_StringDataLink;
    procedure UpdateOutput;
    procedure SetDataLink(const Value: TCDB_StringDataLink);
  protected
    procedure Loaded; override;
    procedure WndProc(var Message: TMessage); override;
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
  published
    property DataLink: TCDB_StringDataLink read FDataLink write SetDataLink;
  published
    property Caption stored False;
  end;

  TCDB_Edit = class(TEdit)
  private
    FDataLink: TCDB_StringDataLink;
    procedure UpdateOutput;
    procedure SetDataLink(const Value: TCDB_StringDataLink);
  protected
    procedure Change; override;
    procedure DoExit; override;
    procedure Loaded; override;
    procedure WndProc(var Message: TMessage); override;
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
  published
    property DataLink: TCDB_StringDataLink read FDataLink write SetDataLink;
  published
    property Text stored False;
  end;

  TCDB_ComboBox = class(TComboBox)
  private
    FDataLink: TCDB_StringDataLink;
    FLookup: TCDB_ComboBoxLookupDataLink;
    procedure UpdateLookup;
    procedure SetDataLink(const Value: TCDB_StringDataLink);
    procedure SetLookup(const Value: TCDB_ComboBoxLookupDataLink);
    procedure UpdateOutput;
  protected
    procedure Change; override;
    procedure DoExit; override;
    procedure Loaded; override;
    procedure WndProc(var Message: TMessage); override;
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
  published
    property DataLink: TCDB_StringDataLink read FDataLink write SetDataLink;
    property Lookup: TCDB_ComboBoxLookupDataLink read FLookup write SetLookup;
  published
    property Items stored False;
    property Text stored False;
  end;

  TCDB_CheckBox = class(TCheckBox)
  private
    FDataLink: TCDB_BooleanDataLink;
    procedure UpdateOutput;
    procedure SetDataLink(const Value: TCDB_BooleanDataLink);
  protected
    procedure Click; override;
    procedure Loaded; override;
    procedure WndProc(var Message: TMessage); override;
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
  published
    property DataLink: TCDB_BooleanDataLink read FDataLink write SetDataLink;
  published
    property Checked stored False;
  end;

  TCDB_Memo = class(TMemo)
  private
    FDataLink: TCDB_StringsDataLink;
    procedure UpdateOutput;
    procedure SetDataLink(const Value: TCDB_StringsDataLink);
  protected
    procedure Change; override;
    procedure Loaded; override;
    procedure WndProc(var Message: TMessage); override;
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
    function LoadFromFile(const FileName: string): Boolean;
  published
    property DataLink: TCDB_StringsDataLink read FDataLink write SetDataLink;
  published
    property Lines stored False;
  end;

  TCDB_Image = class(TScrollBox) // Images aren't centering at design time
  private
    FDataLink: TCDB_ImageDataLink;
    FImage: TImage;
    FPopupMenu: TCDB_ImagePopupMenu;
    procedure Center;
    procedure Change;
    procedure UpdateOutput;
    procedure SetDataLink(const Value: TCDB_ImageDataLink);
  protected
    procedure Loaded; override;
    procedure WndProc(var Message: TMessage); override;
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
    procedure Clear;
    function CopyToClipboard: Boolean;
    function CutToClipboard: Boolean;
    function LoadFromFile(const FileName: string): Boolean;
    function PasteFromClipboard: Boolean;
  public
    property Image: TImage read FImage;
  published
    property DataLink: TCDB_ImageDataLink read FDataLink write SetDataLink;
  end;

  TCDB_Panel = class(TPanel) // When inserting controls after panel recordset is already assigned, the recordset for the inserted control recordset isn't being updated.
  private
    FDataLink: TCDB_WindowDataLink;
    procedure CMControlListChange(var Message: TCMControlListChange); message CM_CONTROLLISTCHANGE;
    procedure SetDataLink(const Value: TCDB_WindowDataLink);
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
  published
    property DataLink: TCDB_WindowDataLink read FDataLink write SetDataLink;
  end;

  TCDB_GridPanel = class(TCustomControl)
  private
    procedure CMControlListChange(var Message: TCMControlListChange); message CM_CONTROLLISTCHANGE;
  protected
    procedure Paint; override;
  public
    constructor Create(AOwner: TComponent); override;
  end;

  TCDB_ControlGrid = class(TCustomControl)
  private
    FChanging: Boolean;
    FDataLink: TCDB_WindowDataLink;
    FPanel: TCDB_GridPanel;
    FPanelIndex: Integer;
    FRowCount: Integer;
  private
    function GetPanelBounds(const Index: Integer): TRect;
    procedure SetDataLink(const Value: TCDB_WindowDataLink);
    procedure SetRowCount(const Value: Integer);
    procedure UpdateOutput;
    procedure WMLButtonDown(var Message: TWMLButtonDown); message WM_LBUTTONDOWN;
    procedure WMSize(var Message: TWMSize); message WM_SIZE;
    procedure WMVScroll(var Message: TWMVScroll); message WM_VSCROLL;
  protected
    function GetChildParent: TComponent; override;
    procedure GetChildren(Proc: TGetChildProc; Root: TComponent); override;
    procedure Loaded; override;
    procedure Paint; override;
  public
    constructor Create(AOwner: TComponent); override;
    destructor Destroy; override;
  public
    procedure PanelControlListChange(const Control: TControl; const Inserting: Boolean);
  published
    property DataLink: TCDB_WindowDataLink read FDataLink write SetDataLink;
    property Panel: TCDB_GridPanel read FPanel;
  published
    property Align;
    property RowCount: Integer read FRowCount write SetRowCount;
  end;

function StringToDataType(const Value: string): TCDB_DataType;
function DataTypeToString(const DataType: TCDB_DataType): string;
function StringToInteger(const Value: string): Integer; overload;
function StringToInteger(const Value: string; var Successful: Boolean): Integer; overload;
function IntegerToString(const Value: Integer): string;
function StringToCurrency(const Value: string): Currency; overload;
function StringToCurrency(const Value: string; var Successful: Boolean): Currency; overload;
function CurrencyToString(const Value: Currency): string;
function StringToFloat(const Value: string): Double; overload;
function StringToFloat(const Value: string; var Successful: Boolean): Double; overload;
function FloatToString(const Value: Double): string;
function StringToDateTime(const Value, Format: string; var DateTime: TDateTime): Boolean;
function DateTimeToString(const Value: TDateTime; const Format: string): string;
function StringToBoolean(const Value: string): Boolean;
function BooleanToString(const Value: Boolean): string;
function BooleanToCurrency(const Value: Boolean): Currency;
function CurrencyToBoolean(const Value: Currency): Boolean;
function BooleanToInteger(const Value: Boolean): Integer;
function IntegerToBoolean(const Value: Integer): Boolean;
function BooleanToFloat(const Value: Boolean): Double;
function FloatToBoolean(const Value: Double): Boolean;
function BitmapsEqual(const Bitmap1, Bitmap2: Graphics.TBitmap): Boolean;

implementation

function StringToDataType(const Value: string): TCDB_DataType;
var
  S: string;
begin
  S := UpperCase(Value);
  if S = 'STRING' then
    Result := dtString
  else
    if S = 'INTEGER' then
      Result := dtInteger
    else
      if S = 'CURRENCY' then
        Result := dtCurrency
      else
        if S = 'FLOAT' then
          Result := dtFloat
        else
          if S = 'DATETIME' then
            Result := dtDateTime
          else
            if S = 'BOOLEAN' then
              Result := dtBoolean
            else
              if S = 'STRINGS' then
                Result := dtStrings
              else
                if S = 'IMAGE' then
                  Result := dtImage
                else
                  Result := dtError;
end;

function DataTypeToString(const DataType: TCDB_DataType): string;
begin
  case DataType of
    dtString: Result := 'String';
    dtInteger: Result := 'Integer';
    dtCurrency: Result := 'Currency';
    dtFloat: Result := 'Float';
    dtDateTime: Result := 'DateTime';
    dtBoolean: Result := 'Boolean';
    dtStrings: Result := 'Strings';
    dtImage: Result := 'Image';
  else
    Result := '';
  end;
end;

function StringToInteger(const Value: string): Integer;
begin
  try
    Result := StrToInt(Value);
  except
    Result := 0;
  end;
end;

function StringToInteger(const Value: string; var Successful: Boolean): Integer;
begin
  Successful := True;
  try
    Result := StrToInt(Value);
  except
    Result := 0;
    Successful := False;
  end;
end;

function IntegerToString(const Value: Integer): string;
begin
  Result := IntToStr(Value);
end;

function StringToCurrency(const Value: string): Currency;
begin
  try
    Result := StrToCurr(Value);
  except
    Result := 0;
  end;
end;

function StringToCurrency(const Value: string; var Successful: Boolean): Currency;
begin
  Successful := True;
  try
    Result := StrToCurr(Value);
  except
    Result := 0;
    Successful := False;
  end;
end;

function CurrencyToString(const Value: Currency): string;
begin
  Result := CurrToStr(Value);
end;

function StringToFloat(const Value: string): Double;
begin
  try
    Result := StrToFloat(Value);
  except
    Result := 0;
  end;
end;

function StringToFloat(const Value: string; var Successful: Boolean): Double;
begin
  Successful := True;
  try
    Result := StrToFloat(Value);
  except
    Result := 0;
    Successful := False;
  end;
end;

function FloatToString(const Value: Double): string;
begin
  Result := FloatToStr(Value);
end;

function StringToDateTime(const Value, Format: string; var DateTime: TDateTime): Boolean;
var
  i, j, k, m: Integer;
  Day, Month, Year: Integer;
  S: string;
begin
  Result := False;
  if (Value = '') or (Format = '') then
    Exit;
  i := 1;
  j := 1;
  Day := 0;
  Month := 0;
  Year := -1;
  repeat
    case UpCase(Format[i]) of
      'D':
        begin
          if UpperCase(Copy(Format, i, 4)) = 'DDDD' then
          begin
            Inc(i, 4);
            for k := 1 to Length(LongDayNames) do
            begin
              m := Pos(LongDayNames[k], Value);
              if m > 0 then
              begin
                Inc(j, Length(LongDayNames[k]));
                Break;
              end;
            end;
            if m = 0 then
              Exit;
          end;
          if UpperCase(Copy(Format, i, 3)) = 'DDD' then
          begin
            Inc(i, 3);
            for k := 1 to Length(ShortDayNames) do
            begin
              m := Pos(ShortDayNames[k], Value);
              if m > 0 then
              begin
                Inc(j, Length(ShortDayNames[k]));
                Break;
              end;
            end;
            if m = 0 then
              Exit;
          end;
          case Value[j] of
            '0'..'9':
              begin
                S := Value[j];
                Inc(j);
                Inc(i);
                if Length(Value) >= j then
                begin
                  if Length(Format) >= i then
                    if UpCase(Format[i]) ='D' then
                      Inc(i)
                    else
                      if Value[j - 1] = '0' then
                        Exit;
                  case Value[j] of
                    '0'..'9':
                      begin
                        S := S + Value[j];
                        Inc(j);
                      end;
                  end;
                end;
                if (Day <> 0) and (Day <> StrToInt(S)) then
                  Exit;
                Day := StrToInt(S);
                if (Day = 0) or (Day > 31) then
                  Exit;
                Continue;
              end;
          end;
        end;
      'M':
        begin
          if UpperCase(Copy(Format, i, 4)) = 'MMMM' then
          begin
            Inc(i, 4);
            for k := 1 to Length(LongMonthNames) do
            begin
              m := Pos(LongMonthNames[k], Value);
              if m > 0 then
              begin
                if (Month <> 0) and (Month <> k) then
                  Exit;
                Month := k;
                Inc(j, Length(LongMonthNames[k]));
                Break;
              end;
            end;
            if Month = 0 then
              Exit
            else
              Continue;
          end;
          if UpperCase(Copy(Format, i, 3)) = 'MMM' then
          begin
            Inc(i, 3);
            for k := 1 to Length(ShortMonthNames) do
            begin
              m := Pos(ShortMonthNames[k], Value);
              if m > 0 then
              begin
                if (Month <> 0) and (Month <> k) then
                  Exit;
                Month := k;
                Inc(j, Length(ShortMonthNames[k]));
                Break;
              end;
            end;
            if Month = 0 then
              Exit
            else
              Continue;
          end;
          case Value[j] of
            '0'..'9':
              begin
                S := Value[j];
                Inc(j);
                Inc(i);
                if Length(Value) >= j then
                begin
                  if Length(Format) >= i then
                    if UpCase(Format[i]) ='M' then
                      Inc(i)
                    else
                      if Value[j - 1] = '0' then
                        Exit;
                  case Value[j] of
                    '0'..'9':
                      begin
                        S := S + Value[j];
                        Inc(j);
                      end;
                  end;
                end;
                if (Month <> 0) and (Month <> StrToInt(S)) then
                  Exit;
                Month := StrToInt(S);
                if (Month = 0) or (Month > 12) then
                  Exit;
                Continue;
              end;
          end;
        end;
      'Y':
        begin
          if UpperCase(Copy(Format, i, 4)) = 'YYYY' then
          begin
            Inc(i, 4);
            S := Copy(Value, j, 4);
            Inc(j, 4);
            for k := 1 to 4 do
              case S[k] of
                '0'..'9':;
              else
                Exit;
              end;
            if (Year <> -1) and (Year <> StrToInt(S)) then
              Exit;
            Year := StrToInt(S);
            if Year < 0 then
              Exit
            else
              Continue;
          end;
          if UpperCase(Copy(Format, i, 2)) = 'YY' then
          begin
            Inc(i, 2);
            S := Copy(Value, j, 2);
            Inc(j, 2);
            for k := 1 to 2 do
              case S[k] of
                '0'..'9':;
              else
                Exit;
              end;
            if (Year <> -1) and (Year <> StrToInt(S)) then
              Exit;
            Year := StrToInt(S);
            if Year < 0 then
              Exit
            else
              Continue;
          end;
        end;
    end;
    Inc(i);
    Inc(j);
  until (i > Length(Format)) or (j > Length(Value));
  if (Day = 0) or (Month = 0) or (Year < 0) then
    Exit;
  DateTime := EncodeDate(Year, Month, Day);
  Result := True;
end;

function DateTimeToString(const Value: TDateTime; const Format: string): string;
begin
  Result := FormatDateTime(Format, Value);
end;

function StringToBoolean(const Value: string): Boolean;
begin
  Result := UpperCase(Value) = 'YES';
end;

function BooleanToString(const Value: Boolean): string;
begin
  if Value then
    Result := 'Yes'
  else
    Result := 'No'
end;

function BooleanToCurrency(const Value: Boolean): Currency;
begin
  if Value then
    Result := 1
  else
    Result := 0;
end;

function CurrencyToBoolean(const Value: Currency): Boolean;
begin
  Result := Value = 1;
end;

function BooleanToInteger(const Value: Boolean): Integer;
begin
  Result := Integer(Value);
end;

function IntegerToBoolean(const Value: Integer): Boolean;
begin
  Result := Value = 1;
end;

function BooleanToFloat(const Value: Boolean): Double;
begin
  if Value then
    Result := 1
  else
    Result := 0;
end;

function FloatToBoolean(const Value: Double): Boolean;
begin
  Result := Value = 1;
end;

function BitmapsEqual(const Bitmap1, Bitmap2: Graphics.TBitmap): Boolean;
var
  x, y: Integer;
begin
  Result := (Bitmap1 <> nil) and (Bitmap2 <> nil);
  if Result then
  begin
    Result := (Bitmap1.Width = Bitmap2.Width) and (Bitmap1.Height = Bitmap2.Height);
    if Result then
      for y := 0 to Bitmap1.Height - 1 do
        for x := 0 to Bitmap1.Width - 1 do
          if Bitmap1.Canvas.Pixels[x, y] <> Bitmap2.Canvas.Pixels[x, y] then
          begin
            Result := False;
            Exit;
          end;
  end;
end;

{ TCDB_FieldDef }

procedure TCDB_FieldDef.Assign(const Source: TCDB_FieldDef);
begin
  if Source.ClassType <> ClassType then
    Exit;
  FDuplicates := Source.Duplicates;
  FFieldName := Source.FieldName;
  FRequired := Source.Required;
end;

constructor TCDB_FieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  FRecordSet := RecordSet;
end;

function TCDB_FieldDef.Empty(const Field: TCDB_Field): Boolean;
begin
  Result := Field.Empty;
end;

procedure TCDB_FieldDef.Update;
var
  i: Integer;
  b: Boolean;
begin
  b := False;
  with FRecordSet do
  begin
    for i := 0 to DataLinkCount - 1 do
    begin
      if DataLinks[i] is TCDB_FieldDataLink then
        if TCDB_FieldDataLink(DataLinks[i]).FieldDef = Self then
        begin
          DataLinks[i].UpdateOutput;
          FRecordSet.UpdateDatabase;
          b := True;
        end;
    end;
    if b then
    begin
      Before_AfterEdit_Event;
      if Assigned(AfterEdit) then
        AfterEdit(FRecordSet);
    end;
  end;
end;

function TCDB_FieldDef.ValidateRecord(const RecordIndex: Integer): Boolean;
var
  i: Integer;
begin
  Result := FRecordSet.ActiveRecordIndexValid;
  if not Result then
    Exit;
  if FRequired and Empty(FRecordSet[RecordIndex][FFieldIndex]) then
  begin
    Result := False;
    ShowMessage(FFieldName + ' field cannot be empty.');
    Exit;
  end;
  if not FDuplicates then
    for i := 0 to FRecordSet.RecordCount - 1 do
      if (i <> RecordIndex) and Equal(FRecordSet[i][FFieldIndex], FRecordSet[RecordIndex][FFieldIndex]) then
      begin
        Result := False;
        ShowMessage('Duplicates not allowed in ' + FFieldName + ' field.');
        Exit;
      end;
  Result := True;
end;

{ TCDB_FieldDefs }

procedure TCDB_FieldDefs.AddFieldDef(const DataType: TCDB_DataType);
begin
  Inc(FFieldCount);
  SetLength(FFieldDefs, FFieldCount);
  case DataType of
    dtString: FFieldDefs[FFieldCount - 1] := TCDB_StringFieldDef.Create(FRecordSet);
    dtInteger: FFieldDefs[FFieldCount - 1] := TCDB_IntegerFieldDef.Create(FRecordSet);
    dtCurrency: FFieldDefs[FFieldCount - 1] := TCDB_CurrencyFieldDef.Create(FRecordSet);
    dtFloat: FFieldDefs[FFieldCount - 1] := TCDB_FloatFieldDef.Create(FRecordSet);
    dtDateTime: FFieldDefs[FFieldCount - 1] := TCDB_DateTimeFieldDef.Create(FRecordSet);
    dtBoolean: FFieldDefs[FFieldCount - 1] := TCDB_BooleanFieldDef.Create(FRecordSet);
    dtStrings: FFieldDefs[FFieldCount - 1] := TCDB_StringsFieldDef.Create(FRecordSet);
    dtImage: FFieldDefs[FFieldCount - 1] := TCDB_ImageFieldDef.Create(FRecordSet);
  else
    Dec(FFieldCount);
    SetLength(FFieldDefs, FFieldCount);
    Exit;
  end;
  FFieldDefs[FFieldCount - 1].FFieldIndex := FFieldCount - 1;
end;

procedure TCDB_FieldDefs.Clear;
var
  i: Integer;
begin
  for i := 0 to FFieldCount - 1 do
    FFieldDefs[i].Free;
  SetLength(FFieldDefs, 0);
  FFieldCount := 0;
end;

constructor TCDB_FieldDefs.Create(const RecordSet: TCDB_RecordSet);
begin
  FRecordSet := RecordSet;
end;

destructor TCDB_FieldDefs.Destroy;
begin
  Clear;
  inherited;
end;

function TCDB_FieldDefs.GetFieldDef(const Index: Integer): TCDB_FieldDef;
begin
  Result := FFieldDefs[Index];
end;

function TCDB_FieldDefs.MoveFieldDef(const FromIndex, ToIndex: Integer): Boolean;
var
  i: Integer;
  f: TCDB_FieldDef;
begin
  Result := not (FromIndex = ToIndex);
  if not Result then
    Exit;
  f := FFieldDefs[ToIndex];
  FFieldDefs[ToIndex] := FFieldDefs[FromIndex];
  if FromIndex > ToIndex then
  begin
    for i := FromIndex downto ToIndex + 2 do
      FFieldDefs[i] := FFieldDefs[i - 1];
    FFieldDefs[ToIndex + 1] := f;
  end
  else
  begin
    for i := FromIndex to ToIndex - 2 do
      FFieldDefs[i] := FFieldDefs[i + 1];
    FFieldDefs[ToIndex - 1] := f;
  end;
end;

{ TCDB_StringFieldDef }

procedure TCDB_StringFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if not (Source is TCDB_StringFieldDef) then
    Exit;
  FDefaultValue := TCDB_StringFieldDef(Source).DefaultValue;
  FMaximumLength := TCDB_StringFieldDef(Source).MaximumLength;
end;

constructor TCDB_StringFieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  inherited;
  FDataType := dtString;
end;

function TCDB_StringFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := TCDB_StringField(Field1).Value = TCDB_StringField(Field2).Value;
end;

{ TCDB_IntegerFieldDef }

procedure TCDB_IntegerFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if not (Source is TCDB_IntegerFieldDef) then
    Exit;
  FDefaultValue := TCDB_IntegerFieldDef(Source).DefaultValue;
  FLowerRange := TCDB_IntegerFieldDef(Source).LowerRange;
  FUpperRange := TCDB_IntegerFieldDef(Source).UpperRange;
  FIncrement := TCDB_IntegerFieldDef(Source).Increment;
  FIncrementValue := TCDB_IntegerFieldDef(Source).IncrementValue;
end;

constructor TCDB_IntegerFieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  inherited;
  FDataType := dtInteger;
end;

function TCDB_IntegerFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := TCDB_IntegerField(Field1).Value = TCDB_IntegerField(Field2).Value;
end;

function TCDB_IntegerFieldDef.ValidateRecord(const RecordIndex: Integer): Boolean;
begin
  Result := inherited ValidateRecord(RecordIndex);
  if Result then
    with TCDB_IntegerField(RecordSet[RecordIndex][FieldIndex]) do
    begin
      if FUpperRange = FLowerRange then
        Exit;
      if (FUpperRange > FLowerRange) and FUpperRangeEnabled and FLowerRangeEnabled then
      begin
        Result := (Value <= FUpperRange) and (Value >= FLowerRange);
        if not Result then
          ShowMessage('Value in ' + FieldName + ' field must be between ' + IntegerToString(FUpperRange) + ' and ' + IntegerToString(FLowerRange) + ' (Value = ' + IntegerToString(Value) + ').');
      end
      else
      begin
        if FUpperRangeEnabled and not FLowerRangeEnabled then
        begin
          Result := Value <= FUpperRange;
          if not Result then
            ShowMessage('Value in ' + FieldName + ' field must be less than ' + IntegerToString(FUpperRange) + ' (Value = ' + IntegerToString(Value) + ').');
        end;
        if FLowerRangeEnabled and not FUpperRangeEnabled then
        begin
          Result := Value >= FLowerRange;
          if not Result then
            ShowMessage('Value in ' + FieldName + ' field must be greater than ' + IntegerToString(FLowerRange) + ' (Value = ' + IntegerToString(Value) + ').');
        end;
      end;
    end;
end;

{ TCDB_CurrencyFieldDef }

procedure TCDB_CurrencyFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if not (Source is TCDB_CurrencyFieldDef) then
    Exit;
  FDefaultValue := TCDB_CurrencyFieldDef(Source).DefaultValue;
  FLowerRange := TCDB_CurrencyFieldDef(Source).LowerRange;
  FUpperRange := TCDB_CurrencyFieldDef(Source).UpperRange;
end;

constructor TCDB_CurrencyFieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  inherited;
  FDataType := dtCurrency;
end;

function TCDB_CurrencyFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := TCDB_CurrencyField(Field1).Value = TCDB_CurrencyField(Field2).Value;
end;

function TCDB_CurrencyFieldDef.ValidateRecord(const RecordIndex: Integer): Boolean;
begin
  Result := inherited ValidateRecord(RecordIndex);
  if Result then
    with TCDB_CurrencyField(RecordSet[RecordIndex][FieldIndex]) do
    begin
      if FUpperRange = FLowerRange then
        Exit;
      if (FUpperRange > FLowerRange) and FUpperRangeEnabled and FLowerRangeEnabled then
      begin
        Result := (Value <= FUpperRange) and (Value >= FLowerRange);
        if not Result then
          ShowMessage('Value in ' + FieldName + ' field must be between ' + CurrencyToString(FUpperRange) + ' and ' + CurrencyToString(FLowerRange) + ' (Value = ' + CurrencyToString(Value) + ').');
      end
      else
      begin
        if FUpperRangeEnabled and not FLowerRangeEnabled then
        begin
          Result := Value <= FUpperRange;
          if not Result then
            ShowMessage('Value in ' + FieldName + ' field must be less than ' + CurrencyToString(FUpperRange) + ' (Value = ' + CurrencyToString(Value) + ').');
        end;
        if FLowerRangeEnabled and not FUpperRangeEnabled then
        begin
          Result := Value >= FLowerRange;
          if not Result then
            ShowMessage('Value in ' + FieldName + ' field must be greater than ' + CurrencyToString(FLowerRange) + ' (Value = ' + CurrencyToString(Value) + ').');
        end;
      end;
    end;
end;

{ TCDB_FloatFieldDef }

procedure TCDB_FloatFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if not (Source is TCDB_FloatFieldDef) then
    Exit;
  FDefaultValue := TCDB_FloatFieldDef(Source).DefaultValue;
  FLowerRange := TCDB_FloatFieldDef(Source).LowerRange;
  FUpperRange := TCDB_FloatFieldDef(Source).UpperRange;
  FDecimalPlaces := TCDB_FloatFieldDef(Source).DecimalPlaces;
end;

constructor TCDB_FloatFieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  inherited;
  FDataType := dtFloat;
end;

function TCDB_FloatFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := TCDB_FloatField(Field1).Value = TCDB_FloatField(Field2).Value;
end;

function TCDB_FloatFieldDef.ValidateRecord(const RecordIndex: Integer): Boolean;
begin
  Result := inherited ValidateRecord(RecordIndex);
  if Result then
    with TCDB_FloatField(RecordSet[RecordIndex][FieldIndex]) do
    begin
      if FUpperRange = FLowerRange then
        Exit;
      if (FUpperRange > FLowerRange) and FUpperRangeEnabled and FLowerRangeEnabled then
      begin
        Result := (Value <= FUpperRange) and (Value >= FLowerRange);
        if not Result then
          ShowMessage('Value in ' + FieldName + ' field must be between ' + FloatToString(FUpperRange) + ' and ' + FloatToString(FLowerRange) + ' (Value = ' + FloatToString(Value) + ').');
      end
      else
      begin
        if FUpperRangeEnabled and not FLowerRangeEnabled then
        begin
          Result := Value <= FUpperRange;
          if not Result then
            ShowMessage('Value in ' + FieldName + ' field must be less than ' + FloatToString(FUpperRange) + ' (Value = ' + FloatToString(Value) + ').');
        end;
        if FLowerRangeEnabled and not FUpperRangeEnabled then
        begin
          Result := Value >= FLowerRange;
          if not Result then
            ShowMessage('Value in ' + FieldName + ' field must be greater than ' + FloatToString(FLowerRange) + ' (Value = ' + FloatToString(Value) + ').');
        end;
      end;
    end;
end;

{ TCDB_DateTimeFieldDef }

procedure TCDB_DateTimeFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if not (Source is TCDB_DateTimeFieldDef) then
    Exit;
  FDefaultEmpty := TCDB_DateTimeFieldDef(Source).DefaultEmpty;
  FDefaultNow := TCDB_DateTimeFieldDef(Source).DefaultNow;
  FDefaultValue := TCDB_DateTimeFieldDef(Source).DefaultValue;
  FFormat := TCDB_DateTimeFieldDef(Source).Format;
  FLowerRange := TCDB_DateTimeFieldDef(Source).LowerRange;
  FUpperRange := TCDB_DateTimeFieldDef(Source).UpperRange;
end;

constructor TCDB_DateTimeFieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  inherited;
  FDataType := dtDateTime;
  FFormat := 'c'; // Displays the date using the format given by the ShortDateFormat global variable.
end;

function TCDB_DateTimeFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := (TCDB_DateTimeField(Field1).Value = TCDB_DateTimeField(Field2).Value) or (TCDB_DateTimeField(Field1).ValueEmpty = TCDB_DateTimeField(Field2).ValueEmpty);
end;

function TCDB_DateTimeFieldDef.ValidateRecord(const RecordIndex: Integer): Boolean;
begin
  Result := inherited ValidateRecord(RecordIndex);
  if Result then
    with TCDB_DateTimeField(RecordSet[RecordIndex][FieldIndex]) do
    begin
      if FUpperRange = FLowerRange then
        Exit;
      if (FUpperRange > FLowerRange) and FUpperRangeEnabled and FLowerRangeEnabled then
      begin
        Result := (Value <= FUpperRange) and (Value >= FLowerRange);
        if not Result then
          ShowMessage('Value in ' + FieldName + ' field must be between ' + DateTimeToString(FUpperRange, FFormat) + ' and ' + DateTimeToString(FLowerRange, FFormat) + ' (Value = ' + DateTimeToString(Value, FFormat) + ').');
      end
      else
      begin
        if FUpperRangeEnabled and not FLowerRangeEnabled then
        begin
          Result := Value <= FUpperRange;
          if not Result then
            ShowMessage('Value in ' + FieldName + ' field must be less than ' + DateTimeToString(FUpperRange, FFormat) + ' (Value = ' + DateTimeToString(Value, FFormat) + ').');
        end;
        if FLowerRangeEnabled and not FUpperRangeEnabled then
        begin
          Result := Value >= FLowerRange;
          if not Result then
            ShowMessage('Value in ' + FieldName + ' field must be greater than ' + DateTimeToString(FLowerRange, FFormat) + ' (Value = ' + DateTimeToString(Value, FFormat) + ').');
        end;
      end;
    end;
end;

{ TCDB_BooleanFieldDef }

procedure TCDB_BooleanFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if Source is TCDB_BooleanFieldDef then
    FDefaultValue := TCDB_BooleanFieldDef(Source).DefaultValue;
end;

constructor TCDB_BooleanFieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  inherited;
  FDataType := dtBoolean;
end;

function TCDB_BooleanFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := TCDB_BooleanField(Field1).Value = TCDB_BooleanField(Field2).Value;
end;

{ TCDB_BlobFieldDef }

procedure TCDB_BlobFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if Source.InheritsFrom(TCDB_BlobFieldDef) then
    FDefaultFileName := TCDB_BlobFieldDef(Source).DefaultFileName;
end;

function TCDB_BlobFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := TCDB_BlobField(Field1).FileName = TCDB_BlobField(Field2).FileName;
end;

function TCDB_BlobFieldDef.LoadDefaultValue(const FileName: string): Boolean;
begin
  Result := FileExists(FileName);
  FDefaultFileName := FileName;
end;

function TCDB_BlobFieldDef.ValidateRecord(const RecordIndex: Integer): Boolean;
begin
  Result := inherited ValidateRecord(RecordIndex);
  if Result then
    with TCDB_BlobField(RecordSet[RecordIndex][FieldIndex]) do
      if Required and (Length(FileName) = 0) then
      begin
        SaveValue(Format('%s%' + IntegerToString(Round(Log10(RecordSet.RecordCount)) + 1) + 'd', [FieldName, FieldIndex]));
        ShowMessage('Text file "' + FileName + '" created.');
      end;
end;

{ TCDB_StringsFieldDef }

procedure TCDB_StringsFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if Source is TCDB_StringsFieldDef then
    FDefaultValue := TCDB_StringsFieldDef(Source).DefaultValue;
end;

constructor TCDB_StringsFieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  inherited;
  FDataType := dtStrings;
end;

function TCDB_StringsFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := TCDB_StringsField(Field1).Value = TCDB_StringsField(Field2).Value;
end;

function TCDB_StringsFieldDef.LoadDefaultValue(const FileName: string): Boolean;
var
  S: TStringArray;
begin
  Result := inherited LoadDefaultValue(FileName);
  if Result then
  begin
    S := TStringArray.Create;
    S.LoadFromFile(FileName);
    FDefaultValue := S.Text;
    S.Free;
  end;
end;

{ TCDB_ImageFieldDef }

procedure TCDB_ImageFieldDef.Assign(const Source: TCDB_FieldDef);
begin
  inherited;
  if Source is TCDB_ImageFieldDef then
    FDefaultValue.Assign(TCDB_ImageFieldDef(Source).DefaultValue);
end;

constructor TCDB_ImageFieldDef.Create(const RecordSet: TCDB_RecordSet);
begin
  inherited;
  FDataType := dtImage;
  FDefaultValue := TImage.Create(nil);
end;

destructor TCDB_ImageFieldDef.Destroy;
begin
  FDefaultValue.Free;
  inherited;
end;

function TCDB_ImageFieldDef.Equal(const Field1, Field2: TCDB_Field): Boolean;
begin
  Result := BitmapsEqual(TCDB_ImageField(Field1).Value.Picture.Bitmap, TCDB_ImageField(Field2).Value.Picture.Bitmap);
end;

function TCDB_ImageFieldDef.LoadDefaultValue(const FileName: string): Boolean;
begin
  Result := inherited LoadDefaultValue(FileName);
  if Result then
    FDefaultValue.Picture.LoadFromFile(FileName);
end;

{ TCDB_Field }

constructor TCDB_Field.Create(const FieldDef: TCDB_FieldDef; const CDB_Record: TCDB_Record);
begin
  FFieldDef := FieldDef;
  FCDB_Record := CDB_Record;
end;

function TCDB_Field.Empty: Boolean;
begin
  Result := False;
end;

function TCDB_Field.GetValueAsString: string;
begin
  Result := '';
end;

procedure TCDB_Field.SetValueAsString(const Value: string);
begin
end;

{ TCDB_StringField }

function TCDB_StringField.Empty: Boolean;
begin
  Result := FValue = '';
end;

function TCDB_StringField.GetValueAsString: string;
begin
  Result := FValue;
end;

procedure TCDB_StringField.InitializeValue;
begin
  FValue := TCDB_StringFieldDef(FieldDef).DefaultValue;
end;

procedure TCDB_StringField.SetValueAsString(const Value: string);
var
  S: string;
  i: Integer;
begin
  i := TCDB_StringFieldDef(FieldDef).MaximumLength;
  if i > 0 then
    S := Copy(Value, 1, i)
  else
    S := Value;
  if S <> FValue then
  begin
    FValue := S;
    FieldDef.Update;
  end;
end;

{ TCDB_IntegerField }

function TCDB_IntegerField.GetValueAsString: string;
begin
  Result := IntegerToString(FValue);
end;

procedure TCDB_IntegerField.InitializeValue;
begin
  with TCDB_IntegerFieldDef(FieldDef) do
  begin
    if IncrementValue <> 0 then
      if RecordSet.RecordCount > 1 then
      begin
        FValue := TCDB_IntegerField(RecordSet[RecordSet.RecordCount - 2][FieldIndex]).Value + IncrementValue;
        Exit;
      end;
    FValue := DefaultValue;
  end;
end;

procedure TCDB_IntegerField.SetValue(const Value: Integer);
begin
  if FValue <> Value then
  begin
    FValue := Value;
    FieldDef.Update;
  end;
end;

procedure TCDB_IntegerField.SetValueAsString(const Value: string);
begin
  SetValue(StringToInteger(Value));
end;

{ TCDB_CurrencyField }

function TCDB_CurrencyField.GetValueAsString: string;
begin
  Result := CurrencyToString(FValue);
end;

procedure TCDB_CurrencyField.InitializeValue;
begin
  FValue := TCDB_CurrencyFieldDef(FieldDef).DefaultValue;
end;

procedure TCDB_CurrencyField.SetValue(const Value: Currency);
begin
  if FValue <> Value then
  begin
    FValue := Value;
    FieldDef.Update;
  end;
end;

procedure TCDB_CurrencyField.SetValueAsString(const Value: string);
begin
  SetValue(StringToCurrency(Value));
end;

{ TCDB_FloatField }

function TCDB_FloatField.GetValueAsString: string;
begin
  Result := FloatToString(FValue);
end;

procedure TCDB_FloatField.InitializeValue;
begin
  FValue := TCDB_FloatFieldDef(FieldDef).DefaultValue;
end;

procedure TCDB_FloatField.SetValue(const Value: Double);
begin
  if FValue <> Value then
  begin
    FValue := Value;
    FieldDef.Update;
  end;
end;

procedure TCDB_FloatField.SetValueAsString(const Value: string);
begin
  SetValue(StringToFloat(Value));
end;

{ TCDB_DateTimeField }

function TCDB_DateTimeField.Empty: Boolean;
begin
  Result := FValueEmpty;
end;

function TCDB_DateTimeField.GetValueAsString: string;
begin
  if FValueEmpty then
    Result := ''
  else
    Result := DateTimeToString(FValue, TCDB_DateTimeFieldDef(FieldDef).Format);
end;

procedure TCDB_DateTimeField.InitializeValue;
begin
  with TCDB_DateTimeFieldDef(FieldDef) do
    if DefaultEmpty then
    begin
      FValueEmpty := True;
      FValue := 0;
    end
    else
      if DefaultNow then
      begin
        FDefaultEmpty := False;
        FValue := Now;
      end
      else
        FValue := DefaultValue;
end;

procedure TCDB_DateTimeField.SetValue(const Value: TDateTime);
begin
  if FValue <> Value then
  begin
    FValue := Value;
    FieldDef.Update;
  end;
end;

procedure TCDB_DateTimeField.SetValueAsString(const Value: string);
var
  d: TDateTime;
begin
  if StringToDateTime(Value, TCDB_DateTimeFieldDef(FieldDef).Format, d) then
  begin
    SetValue(d);
    SetValueEmpty(False);
  end
  else
  begin
    SetValue(0);
    SetValueEmpty(True);
  end;
end;

procedure TCDB_DateTimeField.SetValueEmpty(const Value: Boolean);
begin
  if FValueEmpty <> Value then
  begin
    FValueEmpty := Value;
    FieldDef.Update;
  end;
end;

{ TCDB_BooleanField }

function TCDB_BooleanField.GetValueAsString: string;
begin
  Result := BooleanToString(FValue);
end;

procedure TCDB_BooleanField.InitializeValue;
begin
  FValue := TCDB_BooleanFieldDef(FieldDef).DefaultValue;
end;

procedure TCDB_BooleanField.SetValue(const Value: Boolean);
begin
  if FValue <> Value then
  begin
    FValue := Value;
    FieldDef.Update;
  end;
end;

procedure TCDB_BooleanField.SetValueAsString(const Value: string);
begin
  SetValue(StringToBoolean(Value));
end;

{ TCDB_BlobField }

function TCDB_BlobField.Empty: Boolean;
begin
  Result := FFileName = '';
end;

function TCDB_BlobField.GetValueAsString: string;
begin
  Result := ExtractRelativePath(FieldDef.RecordSet.FileName, FFileName);
end;

procedure TCDB_BlobField.InitializeValue;
begin
  LoadValue(TCDB_BlobFieldDef(FieldDef).DefaultFileName);
end;

function TCDB_BlobField.LoadValue(const FileName: string): Boolean;
var
  S: string;
begin
  S := FileName;
  Result := FileExists(S);
  if not Result then
  begin
    S := ExpandPath(FieldDef.RecordSet.FileName, S);
    Result := FileExists(S);
    if not Result then
      S := FileName;
  end;
  FFileName := S;
end;

function TCDB_BlobField.LoadValue: Boolean;
begin
  Result := LoadValue(FFileName);
end;

procedure TCDB_BlobField.SaveValue;
begin
  SaveValue(FFileName);
end;

procedure TCDB_BlobField.SaveValue(const FileName: string);
var
  S: string;
begin
  if FileExists(FileName) then
    if MessageDlg('Are you sure you want to overwrite the file "' + FileName + '"?', mtConfirmation, [mbYes, mbNo], 0) <> mrYes then
      Exit;
  S := ExtractFilePath(FileName);
  if Length(S) > 0 then
    ForceDirectories(S);
  FFileName := FileName;
  FieldDef.Update;
end;

procedure TCDB_BlobField.SetValueAsString(const Value: string);
begin
  LoadValue(ExpandPath(FieldDef.RecordSet.FileName, Value));
end;

{ TCDB_StringsField }

procedure TCDB_StringsField.ClearValue;
begin
  FValue := '';
  FieldDef.Update;
end;

function TCDB_StringsField.Empty: Boolean;
begin
  Result := inherited Empty;
  if Result then
    Result := FValue = '';
end;

procedure TCDB_StringsField.InitializeValue;
begin
  inherited;
  FValue := TCDB_StringsFieldDef(FieldDef).DefaultValue;
end;

function TCDB_StringsField.LoadValue(const FileName: string): Boolean;
var
  S: TStringArray;
begin
  Result := inherited LoadValue(FileName);
  if Result then
  begin
    S := TStringArray.Create;
    S.LoadFromFile(FileName);
    FValue := S.Text;
    S.Free;
    FieldDef.Update;
  end
  else
    ClearValue;
end;

procedure TCDB_StringsField.SaveValue(const FileName: string);
var
  S: TStringArray;
begin
  inherited;
  S := TStringArray.Create;
  S.Text := FValue;
  S.SaveToFile(FileName);
  S.Free;
end;

procedure TCDB_StringsField.SetValue(const Value: string);
begin
  if FValue = Value then
    Exit;
  FValue := Value;
  FieldDef.Update;
end;

{ TCDB_ImageField }

procedure TCDB_ImageField.ClearValue;
begin
  FValue.Free;
  FValue := TImage.Create(nil);
  FieldDef.Update;
end;

constructor TCDB_ImageField.Create(const FieldDef: TCDB_FieldDef; const CDB_Record: TCDB_Record);
begin
  inherited;
  FValue := TImage.Create(nil);
end;

destructor TCDB_ImageField.Destroy;
begin
  FValue.Free;
  inherited;
end;

function TCDB_ImageField.Empty: Boolean;
begin
  Result := inherited Empty;
  if Result then
    Result := FValue.Picture.Bitmap.Empty;
end;

procedure TCDB_ImageField.InitializeValue;
begin
  inherited;
  FValue.Picture.Assign(TCDB_ImageFieldDef(FieldDef).DefaultValue.Picture);
end;

function TCDB_ImageField.LoadValue(const FileName: string): Boolean;
begin
  Result := inherited LoadValue(FileName);
  if Result then
  begin
    FValue.Picture.LoadFromFile(FileName);
    FieldDef.Update;
  end
  else
    ClearValue;
end;

procedure TCDB_ImageField.SaveValue(const FileName: string);
begin
  inherited;
  FValue.Picture.SaveToFile(FileName);
end;

procedure TCDB_ImageField.SetValue(const Value: TImage);
begin
  FValue.Picture.Assign(Value.Picture);
  FieldDef.Update;
end;

{ TCDB_DataLink }

procedure TCDB_DataLink.AddChild(const Child: TCDB_DataLink);
begin
  Child.Parent := Self;
  Inc(FChildCount);
  SetLength(FChildren, FChildCount);
  FChildren[FChildCount - 1] := Child;
  ChildChanged(Child);
end;

procedure TCDB_DataLink.Assign(Source: TPersistent);
var
  i: Integer;
  SourceDataLink: TCDB_DataLink;
begin
  if not Source.InheritsFrom(TCDB_DataLink) then
    Exit;
  inherited;
  if FRecordSet <> nil then
    FRecordSet.DeleteDataLink(Self);
  for i := 0 to FChildCount - 1 do
    FChildren[i].Parent := nil;
  SetLength(FChildren, 0);
  FChildCount := 0;
  SourceDataLink := TCDB_DataLink(Source);
  RecordSet := SourceDataLink.RecordSet;
  FParent := SourceDataLink.Parent;
  Reference := SourceDataLink.Reference;
  RecordIndex := SourceDataLink.RecordIndex;
  FOnUpdateOutput := SourceDataLink.OnUpdateOutput;
  FOnChildChanged := SourceDataLink.OnChildChanged;
  FRecordSetLocked := SourceDataLink.RecordSetLocked;
  for i := 0 to SourceDataLink.ChildCount - 1 do
    AddChild(SourceDataLink.Children[i]);
  UpdateOutput;
end;

procedure TCDB_DataLink.Associate;
begin
  Reference := FReference;
end;

procedure TCDB_DataLink.UpdateOutput;
var
  i: Integer;
begin
  if FParent <> nil then
    FParent.ChildChanged(Self);
  for i := 0 to FChildCount - 1 do
    Children[i].UpdateOutput;
  if Assigned(FOnUpdateOutput) then
    FOnUpdateOutput;
end;

procedure TCDB_DataLink.ChildChanged(const Child: TCDB_DataLink);
begin
  if FParent <> nil then
    FParent.ChildChanged(Self);
  if Assigned(FOnChildChanged) then
    FOnChildChanged(Child);
end;

constructor TCDB_DataLink.Create;
begin
  FReference := lrActive;
  FRecordIndex := -1;
  FRecordSetLocked := False;
end;

procedure TCDB_DataLink.DeleteChild(const Child: TCDB_DataLink);
var
  i, j: Integer;
begin
  for i := 0 to FChildCount - 1 do
    if FChildren[i] = Child then
    begin
      Child.Parent := nil;
      Dec(FChildCount);
      for j := i to FChildCount - 1 do
        FChildren[j] := FChildren[j + 1];
      SetLength(FChildren, FChildCount);
      ChildChanged(Child);
      Exit;
    end;
end;

destructor TCDB_DataLink.Destroy;
var
  i: Integer;
begin
  if FRecordSet <> nil then
    FRecordSet.DeleteDataLink(Self);
  for i := 0 to FChildCount - 1 do
    FChildren[i].Parent := nil;
  SetLength(FChildren, 0);
  inherited;
end;

procedure TCDB_DataLink.FirstRecord;
begin
  RecordIndex := 0;
end;

function TCDB_DataLink.GetActive: Boolean;
var
  i: Integer;
begin
  Result := False;
  if FRecordSet = nil then
    Exit;
  if not FRecordSet.Active then
    Exit;
  for i := 0 to FChildCount - 1 do
    if not FChildren[i].Active then
      Exit;
  Result := True;
end;

function TCDB_DataLink.GetActiveRecordIndex: Integer;
begin
  if Active then
    case Reference of
      lrActive: Result := FRecordSet.ActiveRecordIndex;
      lrIndex: Result := FRecordIndex;
    else
      Result := -1;
    end
  else
    Result := -1;
end;

function TCDB_DataLink.GetChild(const Index: Integer): TCDB_DataLink;
begin
  Result := FChildren[Index];
end;

procedure TCDB_DataLink.LastRecord;
begin
  if Active then
    RecordIndex := FRecordSet.RecordCount - 1;
end;

procedure TCDB_DataLink.NextRecord;
begin
  RecordIndex := FRecordIndex + 1;
end;

procedure TCDB_DataLink.PreviousRecord;
begin
  RecordIndex := FRecordIndex - 1;
end;

procedure TCDB_DataLink.SetRecordIndex(const Value: Integer);
begin
  if (FRecordIndex <> Value) and (FReference = lrIndex) and Active then
    if RecordSet.ValidateRecordIndex(Value) then
    begin
      FRecordIndex := Value;
      UpdateOutput;
    end;
end;

procedure TCDB_DataLink.SetRecordSet(const Value: TCDB_RecordSet);
begin
  if FRecordSetLocked then
    Exit;
  if FRecordSet <> Value then
  begin
    if FRecordSet <> nil then
      FRecordSet.DeleteDataLink(Self);
    FRecordSet := Value;
    if FRecordSet <> nil then
      FRecordSet.AddDataLink(Self);
  end;
  UpdateOutput;
end;

procedure TCDB_DataLink.SetReference(const Value: TCDB_LinkReference);
begin
  FReference := Value;
  case FReference of
    lrActive: FRecordIndex := -1;
    lrIndex:
      if Active then
        RecordIndex := FRecordSet.ActiveRecordIndex;
  end;
  UpdateOutput;
end;

procedure TCDB_DataLink.LockRecordSet;
begin
  FRecordSetLocked := True;
end;

procedure TCDB_DataLink.UnlockRecordSet;
begin
  FRecordSetLocked := False;
end;

{ TCDB_ParentRecordSetDataLink }

procedure TCDB_ParentRecordSetDataLink.AddChild(const Child: TCDB_DataLink);
begin
  inherited;
  Child.LockRecordSet;
end;

procedure TCDB_ParentRecordSetDataLink.SetRecordSet(const Value: TCDB_RecordSet);
var
  i: Integer;
begin
  inherited;
  for i := 0 to FChildCount - 1 do
  begin
    FChildren[i].UnlockRecordSet;
    FChildren[i].RecordSet := RecordSet;
    FChildren[i].LockRecordSet;
  end;
end;

{ TCDB_WindowDataLink }

procedure TCDB_WindowDataLink.UpdateOutput;
begin
  if Reference <> lrIndex then
    Reference := lrIndex;
  inherited;
end;

procedure TCDB_WindowDataLink.ChildChanged(const Child: TCDB_DataLink);
begin
  try
    if Child.Reference <> lrIndex then
    begin
      Child.Reference := lrIndex;
      Exit;
    end;
    if Child.RecordIndex <> RecordIndex then
      Child.RecordIndex := RecordIndex;
  finally
    inherited;
  end;
end;

constructor TCDB_WindowDataLink.Create;
begin
  inherited;
  Reference := lrIndex;
end;

procedure TCDB_WindowDataLink.ControlListChange(const Control: TControl; const Inserting: Boolean);
var
  Child: TCDB_DataLink;
begin
  Child := TCDB_DataLink(Control.Perform(CDBM_GETDATALINK, 0, Integer(Control)));
  if Child is TCDB_DataLink then
    if Inserting then
      AddChild(Child)
    else
      DeleteChild(Child);
end;

{ TCDB_FieldDataLink }

procedure TCDB_FieldDataLink.Assign(Source: TPersistent);
begin
  if not Source.InheritsFrom(TCDB_FieldDataLink) then
    Exit;
  inherited;
  FieldName := TCDB_FieldDataLink(Source).FieldName;
end;

procedure TCDB_FieldDataLink.Associate;
begin
  FieldName := FFieldName;
  inherited;
end;

function TCDB_FieldDataLink.GetActive: Boolean;
begin
  Result := inherited GetActive;
  if Result then
    Result := FFieldDef <> nil;
end;

function TCDB_FieldDataLink.GetCurrentGenericField: TCDB_Field;
begin
  case Reference of
    lrActive: Result := GetGenericField(ActiveRecordIndex);
    lrIndex: Result := GetGenericField(RecordIndex);
  else
    Result := nil;
  end;
end;

function TCDB_FieldDataLink.GetGenericField(const RecordIndex: Integer): TCDB_Field;
begin
  if Active then
    with FieldDef, RecordSet do
      if ValidateRecordIndex(RecordIndex) then
      begin
        Result := Records[RecordIndex][FieldIndex];
        Exit;
      end;
  Result := nil;
end;

procedure TCDB_FieldDataLink.SetFieldName(const Value: TCDB_FieldName);
var
  i: Integer;
  S: string;
  b: Boolean;
begin
  if inherited GetActive then
  begin
    S := UpperCase(Value);
    b := False;
    for i := 0 to RecordSet.FieldCount - 1 do
      if UpperCase(RecordSet.FieldDefs[i].FieldName) = S then
      begin
        FFieldName := Value;
        FFieldDef := RecordSet.FieldDefs[i];
        inherited Associate;
        b := True;
        Break;
      end;
    if not b then
    begin
      FFieldName := '';
      FFieldDef := nil;
    end;
    UpdateOutput;
    Exit;
  end;
  if RecordSet <> nil then
  begin
    if not FileExists(RecordSet.FileName) then
      FFieldName := ''
    else
      FFieldName := Value;
  end
  else
    FFieldName := Value;
  FFieldDef := nil;
  UpdateOutput;
end;

procedure TCDB_FieldDataLink.SetRecordSet(const Value: TCDB_RecordSet);
begin
  inherited;
  Associate;
end;

{ TCDB_StringDataLink }

constructor TCDB_StringDataLink.Create;
begin
  inherited;
  FLinkDataType := ldtString;
end;

function TCDB_StringDataLink.GetCurrentValue: string;
begin
  Result := GetValue(ActiveRecordIndex);
end;

function TCDB_StringDataLink.GetValue(const RecordIndex: Integer): string;
var
  f: TCDB_Field;
begin
  f := GenericFields[RecordIndex];
  if f <> nil then
    Result := f.ValueAsString
  else
    Result := '';
end;

procedure TCDB_StringDataLink.SetCurrentValue(const Value: string);
begin
  SetValue(ActiveRecordIndex, Value);
end;

procedure TCDB_StringDataLink.SetValue(const RecordIndex: Integer; const Value: string);
var
  f: TCDB_Field;
begin
  f := GenericFields[RecordIndex];
  if f <> nil then
    f.ValueAsString := Value;
end;

{ TCDB_ComboBoxLookupDataLink }

procedure TCDB_ComboBoxLookupDataLink.Assign(Source: TPersistent);
begin
  if not Source.InheritsFrom(TCDB_ComboBoxLookupDataLink) then
    Exit;
  inherited;
  FBound.Assign(TCDB_ComboBoxLookupDataLink(Source).Bound);
  FDisplayed.Assign(TCDB_ComboBoxLookupDataLink(Source).Displayed);
end;

procedure TCDB_ComboBoxLookupDataLink.Associate;
begin
  inherited;
  FBound.Associate;
  FDisplayed.Associate;
end;

constructor TCDB_ComboBoxLookupDataLink.Create;
begin
  inherited;
  FBound := TCDB_StringDataLink.Create;
  FDisplayed := TCDB_StringDataLink.Create;
  AddChild(FBound);
  AddChild(FDisplayed);
end;

destructor TCDB_ComboBoxLookupDataLink.Destroy;
begin
  FBound.Free;
  FDisplayed.Free;
  inherited;
end;

procedure TCDB_ComboBoxLookupDataLink.SetBound(const Value: TCDB_StringDataLink);
begin
  FBound.Assign(Value);
end;

procedure TCDB_ComboBoxLookupDataLink.SetDisplayed(const Value: TCDB_StringDataLink);
begin
  FDisplayed.Assign(Value);
end;

{ TCDB_BooleanDataLink }

constructor TCDB_BooleanDataLink.Create;
begin
  inherited;
  FLinkDataType := ldtBoolean;
end;

function TCDB_BooleanDataLink.GetCurrentValue: Boolean;
var
  f: TCDB_Field;
begin
  f := CurrentGenericField;
  if f <> nil then
    case f.FieldDef.DataType of
      dtString: Result := StringToBoolean(TCDB_StringField(f).Value);
      dtInteger: Result := IntegerToBoolean(TCDB_IntegerField(f).Value);
      dtCurrency: Result := CurrencyToBoolean(TCDB_CurrencyField(f).Value);
      dtFloat: Result := FloatToBoolean(TCDB_FloatField(f).Value);
      dtBoolean: Result := TCDB_BooleanField(f).Value;
    else
      Result := False;
    end
  else
    Result := False;
end;

procedure TCDB_BooleanDataLink.SetCurrentValue(const Value: Boolean);
var
  f: TCDB_Field;
begin
  f := CurrentGenericField;
  if f <> nil then
    case f.FieldDef.DataType of
      dtString: TCDB_StringField(f).Value := BooleanToString(Value);
      dtInteger: TCDB_IntegerField(f).Value := BooleanToInteger(Value);
      dtCurrency: TCDB_CurrencyField(f).Value := BooleanToCurrency(Value);
      dtFloat: TCDB_FloatField(f).Value := BooleanToFloat(Value);
      dtBoolean: TCDB_BooleanField(f).Value := Value;
    end;
end;

{ TCDB_StringsDataLink }

constructor TCDB_StringsDataLink.Create;
begin
  inherited;
  FLinkDataType := ldtStrings;
end;

function TCDB_StringsDataLink.GetCurrentField: TCDB_StringsField;
var
  f: TCDB_Field;
begin
  f := CurrentGenericField;
  if f <> nil then
    if f.FieldDef.DataType = dtStrings then
    begin
      Result := TCDB_StringsField(f);
      Exit;
    end;
  Result := nil;
end;

function TCDB_StringsDataLink.GetCurrentValue: string;
var
  f: TCDB_StringsField;
begin
  f := GetCurrentField;
  if f <> nil then
    Result := f.Value
  else
    Result := '';
end;

procedure TCDB_StringsDataLink.SetCurrentValue(const Value: string);
var
  f: TCDB_StringsField;
begin
  f := GetCurrentField;
  if f <> nil then
    f.Value := Value;
end;

{ TCDB_ImageDataLink }

constructor TCDB_ImageDataLink.Create;
begin
  inherited;
  FLinkDataType := ldtImage;
end;

function TCDB_ImageDataLink.GetCurrentField: TCDB_ImageField;
var
  f: TCDB_Field;
begin
  f := CurrentGenericField;
  if f <> nil then
    if f.FieldDef.DataType = dtImage then
    begin
      Result := TCDB_ImageField(f);
      Exit;
    end;
  Result := nil;
end;

function TCDB_ImageDataLink.GetCurrentValue: TImage;
var
  f: TCDB_ImageField;
begin
  f := GetCurrentField;
  if f <> nil then
    Result := f.Value
  else
    Result := nil;
end;

procedure TCDB_ImageDataLink.SetCurrentValue(const Value: TImage);
var
  f: TCDB_ImageField;
begin
  f := GetCurrentField;
  if f <> nil then
    f.Value := Value;
end;

{ TCDB_Record }

constructor TCDB_Record.Create(const RecordSet: TCDB_RecordSet);
begin
  SetLength(FFields, RecordSet.FieldCount);
  FRecordIndex := RecordSet.RecordCount - 1;
end;

destructor TCDB_Record.Destroy;
var
  i: Integer;
begin
  for i := 0 to Length(FFields) - 1 do
    FFields[i].Free;
  SetLength(FFields, 0);
  inherited;
end;

function TCDB_Record.GetField(const Index: Integer): TCDB_Field;
begin
  Result := FFields[Index];
end;

{ TCDB_StoredRecord }

constructor TCDB_StoredRecord.Create(const RecordSet: TCDB_RecordSet);
var
  i: Integer;
begin
  inherited;
  for i := 0 to Length(FFields) - 1 do
  begin
    case RecordSet.FieldDefs[i].DataType of
      dtString: FFields[i] := TCDB_StringField.Create(RecordSet.FieldDefs[i], Self);
      dtInteger: FFields[i] := TCDB_IntegerField.Create(RecordSet.FieldDefs[i], Self);
      dtCurrency: FFields[i] := TCDB_CurrencyField.Create(RecordSet.FieldDefs[i], Self);
      dtFloat: FFields[i] := TCDB_FloatField.Create(RecordSet.FieldDefs[i], Self);
      dtDateTime: FFields[i] := TCDB_DateTimeField.Create(RecordSet.FieldDefs[i], Self);
      dtBoolean: FFields[i] := TCDB_BooleanField.Create(RecordSet.FieldDefs[i], Self);
      dtStrings: FFields[i] := TCDB_StringsField.Create(RecordSet.FieldDefs[i], Self);
      dtImage: FFields[i] := TCDB_ImageField.Create(RecordSet.FieldDefs[i], Self);
    end;
    Fields[i].InitializeValue;
  end;
end;

{ TCDB_RecordSet }

procedure TCDB_RecordSet.Activate;
begin
  Active := True;
end;

function TCDB_RecordSet.ActiveRecordIndexValid: Boolean;
begin
  Result := ValidateRecordIndex(FActiveRecordIndex);
end;

procedure TCDB_RecordSet.AddDataLink(const DataLink: TCDB_DataLink);
begin
  Inc(FDataLinkCount);
  SetLength(FDataLinks, FDataLinkCount);
  FDataLinks[FDataLinkCount - 1] := DataLink;
end;

procedure TCDB_RecordSet.AddFieldDef(const DataType: TCDB_DataType);
begin
  FFieldDefs.AddFieldDef(DataType);
end;

function TCDB_RecordSet.AddRecord: Boolean;
begin
  Result := False;
  if FieldCount = 0 then
    Exit;
  if FActive and (FRecordCount > 0) then
    if not ValidateActiveRecord then
      Exit;
  Inc(FRecordCount);
  SetLength(FRecords, FRecordCount);
  Result := True;
end;

procedure TCDB_RecordSet.AssociateDataLinks;
var
  i: Integer;
begin
  if csLoading in ComponentState then
    Exit;
  for i := 0 to FDataLinkCount - 1 do
    FDataLinks[i].Associate;
end;

procedure TCDB_RecordSet.Before_AfterAddRecord_Event;
begin
end;

procedure TCDB_RecordSet.Before_AfterClose_Event;
begin
end;

procedure TCDB_RecordSet.Before_AfterDeleteRecord_Event;
begin
end;

procedure TCDB_RecordSet.Before_AfterEdit_Event;
begin
end;

procedure TCDB_RecordSet.Before_AfterOpen_Event;
begin
end;

procedure TCDB_RecordSet.Before_AfterScrollRecords_Event;
begin
end;

procedure TCDB_RecordSet.Before_OnRecordInvalid_Event;
begin
end;

function TCDB_RecordSet.ChangeActiveRecord(const RecordIndex: Integer): Boolean;
begin
  Result := False;
  if csLoading in ComponentState then
    Exit;
  if FActive then
  begin
    if RecordIndex = -2 then
      FActiveRecordIndex := -1
    else
      if RecordIndex <= -1 then
        Exit;
    if RecordIndex >= 0 then
      if RecordIndex < FRecordCount then
        FActiveRecordIndex := RecordIndex
      else
        Exit;
    UpdateLinks;
    Result := True;
    Before_AfterScrollRecords_Event;
    if Assigned(FAfterScrollRecords) then
      FAfterScrollRecords(Self);
  end;
end;

constructor TCDB_RecordSet.Create(AOwner: TComponent);
begin
  inherited;
  FActiveRecordIndex := -1;
  FFieldDefs := TCDB_FieldDefs.Create(Self);
end;

procedure TCDB_RecordSet.DatabaseChanged;
begin
  UpdateLinks;
end;

procedure TCDB_RecordSet.Deactivate;
begin
  Active := False;
end;

procedure TCDB_RecordSet.DeleteActiveRecord;
begin
  DeleteRecord(FActiveRecordIndex);
end;

procedure TCDB_RecordSet.DeleteDataLink(const DataLink: TCDB_DataLink);
var
  i, j: Integer;
begin
  for i := 0 to FDataLinkCount - 1 do
    if FDataLinks[i] = DataLink then
    begin
      Dec(FDataLinkCount);
      for j := i to FDataLinkCount - 1 do
        FDataLinks[j] := FDataLinks[j + 1];
      SetLength(FDataLinks, FDataLinkCount);
      Exit;
    end;
end;

procedure TCDB_RecordSet.DeleteRecord(const Index: Integer);
var
  i: Integer;
begin
  FRecords[Index].Free;
  Dec(FRecordCount);
  for i := Index to FRecordCount - 1 do
    FRecords[i] := FRecords[i + 1];
  SetLength(FRecords, FRecordCount);
  Before_AfterDeleteRecord_Event;
  if Assigned(FAfterDeleteRecord) then
    FAfterDeleteRecord(Self);
  ChangeActiveRecord(-1);
end;

destructor TCDB_RecordSet.Destroy;
begin
  Database := nil;
  Active := False;
  while FDataLinkCount > 0 do
    FDataLinks[0].RecordSet := nil;
  SetLength(FDataLinks, 0);
  FFieldDefs.Free;
  inherited;
end;

procedure TCDB_RecordSet.FirstRecord;
begin
  ChangeActiveRecord(0);
end;

function TCDB_RecordSet.GetActive: Boolean;
begin
  if not (csLoading in ComponentState) then
    Result := FActive
  else
    Result := False;
end;

function TCDB_RecordSet.GetDataLink(const Index: Integer): TCDB_DataLink;
begin
  Result := FDataLinks[Index];
end;

function TCDB_RecordSet.GetFieldCount: Integer;
begin
  Result := FFieldDefs.FieldCount;
end;

function TCDB_RecordSet.GetFieldDef(const Index: Integer): TCDB_FieldDef;
begin
  Result := FFieldDefs[Index];
end;

function TCDB_RecordSet.GetLast: TCDB_Record;
begin
  Result := FRecords[FRecordCount - 1];
end;

procedure TCDB_RecordSet.GetOpenDialogProperties(const PropertyName: string; var DefaultExt, Filter, Title, FileName: string);
begin
  FileName := FFileName;
end;

function TCDB_RecordSet.GetRecord(const Index: Integer): TCDB_Record;
begin
  Result := FRecords[Index];
end;

procedure TCDB_RecordSet.LastRecord;
begin
  ChangeActiveRecord(FRecordCount - 1);
end;

procedure TCDB_RecordSet.Loaded;
begin
  inherited;
  Reset;
end;

function TCDB_RecordSet.LoadFromFile: Boolean;
begin
  Result := LoadFromFile(FFileName);
end;

function TCDB_RecordSet.LoadFromFile(const FileName: string): Boolean;
begin
  if Active then
    Active := False;
  FFileName := FileName;
  Result := FileExists(FFileName);
  FLoading := Result;
  if not (FileExists(FFileName) or (FFileName = '')) then
    ShowMessage('File "' + FFileName + '" not found.');
end;

function TCDB_RecordSet.NextRecord: Boolean;
begin
  Result := ChangeActiveRecord(FActiveRecordIndex + 1);
end;

function TCDB_RecordSet.PreviousRecord: Boolean;
begin
  Result := ChangeActiveRecord(FActiveRecordIndex - 1);
end;

procedure TCDB_RecordSet.RecordAdded;
begin
  Before_AfterAddRecord_Event;
  if Assigned(FAfterAddRecord) then
    FAfterAddRecord(Self);
  ChangeActiveRecord(RecordCount - 1);
end;

procedure TCDB_RecordSet.Reset;
begin
  Active := FActive;
end;

function TCDB_RecordSet.SaveToFile: Boolean;
begin
  Result := SaveToFile(FFileName);
end;

function TCDB_RecordSet.SaveToFile(const FileName: string): Boolean;
begin
  Result := FActive and (Length(FileName) > 0);
  if Result then
    FFileName := FileName;
end;

procedure TCDB_RecordSet.SetActive(const Value: Boolean);
begin
  if csLoading in ComponentState then
  begin
    FActive := Value;
    Exit;
  end;
  if Value then
  begin
    if FLoading then
      FActive := True
    else
    begin
      LoadFromFile;
      Exit;
    end;
  end
  else
    FActive := False;
  FLoading := False;
  if not FActive then
  begin
    SetLength(FRecords, 0);
    FRecordCount := 0;
    FFieldDefs.Free;
    FFieldDefs := TCDB_FieldDefs.Create(Self);
  end;
  if not (csDestroying in ComponentState) then
    if FActive then
      FirstRecord
    else
      ChangeActiveRecord(-2);
  AssociateDataLinks;
  if FActive then
  begin
    Before_AfterOpen_Event;
    if Assigned(FAfterOpen) then
      FAfterOpen(Self);
  end
  else
  begin
    if not (csDestroying in ComponentState) then
    begin
      Before_AfterClose_Event;
      if Assigned(FAfterClose) then
        FAfterClose(Self);
    end;
  end;
end;

procedure TCDB_RecordSet.SetActiveRecordIndex(const Value: Integer);
begin
  ChangeActiveRecord(Value);
end;

procedure TCDB_RecordSet.SetDatabase(const Value: TCDB_Database);
begin
  if FDatabase <> Value then
  begin
    if FDatabase <> nil then
      if not (csDestroying in FDatabase.ComponentState) then
        FDatabase.DeleteRecordSet(Self);
    FDatabase := Value;
    if FDatabase <> nil then
      FDatabase.AddRecordSet(Self);
  end;
end;

procedure TCDB_RecordSet.SetFileName(const Value: TCDB_OpenFileName);
begin
  if FActive and not (csLoading in ComponentState) then
    LoadFromFile(Value)
  else
  begin
    FFileName := Value;
    AssociateDataLinks;
    if not (FileExists(FFileName) or (FFileName = '')) then
      ShowMessage('File "' + FFileName + '" not found.');
  end;
end;

procedure TCDB_RecordSet.UpdateDatabase;
begin
  if (FDatabase <> nil) and not FUpdatingDatabase then
  begin
    FUpdatingDatabase := True;
    FDatabase.Changed(Self);
    FUpdatingDatabase := False;
  end;
end;

procedure TCDB_RecordSet.UpdateLinks;
var
  i: Integer;
begin
  if not (csLoading in ComponentState) then
  begin
    for i := 0 to FDataLinkCount - 1 do
      FDataLinks[i].UpdateOutput;
    UpdateDatabase;
  end;
end;

function TCDB_RecordSet.ValidateActiveRecord: Boolean;
begin
  Result := ValidateRecord(FActiveRecordIndex);
end;

function TCDB_RecordSet.ValidateAllRecords: Boolean;
var
  i: Integer;
begin
  Result := True;
  for i := 0 to FRecordCount - 1 do
    if not ValidateRecord(i) then
    begin
      Result := False;
      Exit;
    end;
end;

function TCDB_RecordSet.ValidateRecord(const Index: Integer): Boolean;
var
  i: Integer;
begin
  Result := ValidateRecordIndex(Index);
  if Result then
    for i := 0 to FieldCount - 1 do
      if not FFieldDefs[i].ValidateRecord(Index) then
      begin
        Result := False;
        Before_OnRecordInvalid_Event;
        if Assigned(FOnRecordInvalid) then
          FOnRecordInvalid(Self);
        Exit;
      end;
end;

function TCDB_RecordSet.ValidateRecordIndex(const Index: Integer): Boolean;
begin
  Result := (Index >= 0) and (Index < FRecordCount);
end;

{ TCDB_Database }

procedure TCDB_Database.AddRecordSet(const RecordSet: TCDB_RecordSet);
begin
  Inc(FRecordSetCount);
  SetLength(FRecordSets, FRecordSetCount);
  FRecordSets[FRecordSetCount - 1] := RecordSet;
end;

procedure TCDB_Database.BecomeOwner(const RecordSet: TCDB_RecordSet);
begin
  if RecordSet.Owner <> nil then
    RecordSet.Owner.RemoveComponent(RecordSet);
  if FindComponent(RecordSet.Name) <> nil then
    RecordSet.Name := '';
  InsertComponent(RecordSet);
end;

procedure TCDB_Database.Changed(const Sender: TCDB_RecordSet);
var
  i: Integer;
begin
  for i := 0 to FRecordSetCount - 1 do
    if (FRecordSets[i] <> Sender) and (FRecordSets[i] is TCDB_Query) then
      FRecordSets[i].DatabaseChanged;
end;

function TCDB_Database.DeleteRecordSet(const RecordSet: TCDB_RecordSet): Boolean;
var
  i, n: Integer;
begin
  Result := False;
  for i := 0 to FRecordSetCount - 1 do
    if FRecordSets[i] = RecordSet then
    begin
      if RecordSet.Owner = Self then
        RecordSet.Free;
      Dec(FRecordSetCount);
      for n := i to FRecordSetCount - 1 do
        FRecordSets[n] := FRecordSets[n + 1];
      SetLength(FRecordSets, FRecordSetCount);
      Result := True;
      Exit;
    end;
end;

destructor TCDB_Database.Destroy;
var
  i: Integer;
begin
  Destroying;
  for i := 0 to FRecordSetCount - 1 do
    if FRecordSets[i].Owner = Self then
      FRecordSets[i].Free
    else
      FRecordSets[i].Database := nil;
  SetLength(FRecordSets, 0);
  inherited;
end;

function TCDB_Database.GetRecordSet(const Index: Integer): TCDB_RecordSet;
begin
  Result := FRecordSets[Index];
end;

function TCDB_Database.LoadRecordSet(const FileName: string): Boolean;
var
  Ext: string;
  RS: TCDB_RecordSet;
begin
  Result := False;
  Ext := UpperCase(ExtractFileExt(FileName));
  RS := nil;
  if Ext = '.CDT' then
    RS := TCDB_Table.Create(Self);
  if Ext = '.CDQ' then
    RS := TCDB_Query.Create(Self);
  if RS <> nil then
  begin
    Result := RS.LoadFromFile(FileName);
    if Result then
      AddRecordSet(RS)
    else
      RS.Free;
  end;
end;

function TCDB_Database.RecordSetIndex(const FileName: string): Integer;
var
  S: string;
  i: Integer;
begin
  S := UpperCase(FileName);
  for i := 0 to FRecordSetCount - 1 do
    if UpperCase(FRecordSets[i].FileName) = S then
    begin
      Result := i;
      Exit;
    end;
  Result := -1;
end;

{ TCDB_TableFieldDefs }

procedure TCDB_TableFieldDefs.LoadFieldDefs(const TableFile: TCustomIniFile; const FileName: string);
var
  S: string;
  Lines: TStringArray;
  i: Integer;
  dt: TCDB_DataType;
  ConvertSuccess: Boolean;
begin
  Lines := TStringArray.Create;
  ConvertSuccess := False;
  try
    if TableFile.ReadLines('Field Definitions', Lines, True, True, False) then
      for i := 0 to Lines.Count - 1 do
      begin
        dt := StringToDataType(ReadPart(Lines[i], 2, '|'));
        if dt <> dtError then
        begin
          AddFieldDef(dt);
          with FFieldDefs[FFieldCount - 1] do
          begin
            FieldName := ReadPart(Lines[i], 1, '|');
            Required := StringToBoolean(ReadPart(Lines[i], 3, '|'));
            Duplicates := StringToBoolean(ReadPart(Lines[i], 4, '|'));
            case DataType of
              dtString:
                with TCDB_StringFieldDef(FFieldDefs[FFieldCount - 1]) do
                begin
                  DefaultValue := ReadPart(Lines[i], 5, '|');
                  MaximumLength := StringToInteger(ReadPart(Lines[i], 6, '|'));
                end;
              dtInteger:
                with TCDB_IntegerFieldDef(FFieldDefs[FFieldCount - 1]) do
                begin
                  DefaultValue := StringToInteger(ReadPart(Lines[i], 5, '|'));
                  LowerRange := StringToInteger(ReadPart(Lines[i], 6, '|'), ConvertSuccess);
                  LowerRangeEnabled := ConvertSuccess;
                  UpperRange := StringToInteger(ReadPart(Lines[i], 7, '|'), ConvertSuccess);
                  UpperRangeEnabled := ConvertSuccess;
                  IncrementValue := StringToInteger(ReadPart(Lines[i], 8, '|'));
                end;
              dtCurrency:
                with TCDB_CurrencyFieldDef(FFieldDefs[FFieldCount - 1]) do
                begin
                  DefaultValue := StringToCurrency(ReadPart(Lines[i], 5, '|'));
                  LowerRange := StringToCurrency(ReadPart(Lines[i], 6, '|'), ConvertSuccess);
                  LowerRangeEnabled := ConvertSuccess;
                  UpperRange := StringToCurrency(ReadPart(Lines[i], 7, '|'), ConvertSuccess);
                  UpperRangeEnabled := ConvertSuccess;
                end;
              dtFloat:
                with TCDB_FloatFieldDef(FFieldDefs[FFieldCount - 1]) do
                begin
                  DefaultValue := StringToFloat(ReadPart(Lines[i], 5, '|'));
                  LowerRange := StringToFloat(ReadPart(Lines[i], 6, '|'), ConvertSuccess);
                  LowerRangeEnabled := ConvertSuccess;
                  UpperRange := StringToFloat(ReadPart(Lines[i], 7, '|'), ConvertSuccess);
                  UpperRangeEnabled := ConvertSuccess;
                end;
              dtDateTime:
                with TCDB_DateTimeFieldDef(FFieldDefs[FFieldCount - 1]) do
                begin
                  Format := ReadPart(Lines[i], 6, '|');
                  S := ReadPart(Lines[i], 5, '|');
                  if UpperCase(S) = 'NOW' then
                  begin
                    DefaultEmpty := False;
                    DefaultNow := True;
                    DefaultValue := 0;
                  end
                  else
                  begin
                    DefaultNow := False;
                    try
                      DefaultValue := StrToFloat(S);
                      DefaultEmpty := False;
                    except
                      DefaultValue := 0;
                      DefaultEmpty := True;
                    end;
                  end;
                  try
                    LowerRange := StrToFloat(ReadPart(Lines[i], 7, '|'));
                    LowerRangeEnabled := True;
                  except
                    LowerRange := 0;
                    LowerRangeEnabled := False;
                  end;
                  try
                    UpperRange := StrToFloat(ReadPart(Lines[i], 8, '|'));
                    UpperRangeEnabled := True;
                  except
                    UpperRange := 0;
                    UpperRangeEnabled := False;
                  end;
                end;
              dtBoolean:
                with TCDB_BooleanFieldDef(FFieldDefs[FFieldCount - 1]) do
                  DefaultValue := StringToBoolean(ReadPart(Lines[i], 5, '|'));
              dtStrings:
                with TCDB_StringsFieldDef(FFieldDefs[FFieldCount - 1]) do
                  LoadDefaultValue(ExpandPath(FileName, ReadPart(Lines[i], 5, '|')));
              dtImage:
                with TCDB_ImageFieldDef(FFieldDefs[FFieldCount - 1]) do
                  LoadDefaultValue(ExpandPath(FileName, ReadPart(Lines[i], 5, '|')));
            end;
          end;
        end;
      end;
  finally
    Lines.Free;
  end;
end;

procedure TCDB_TableFieldDefs.SaveFieldDefs(const TableFile: TCustomIniFile);
begin
  //////////////////////////////////////////////////////////////////////////////
end;

{ TCDB_Table }

function TCDB_Table.AddRecord: Boolean;
begin
  Result := inherited AddRecord;
  if not Result then
    Exit;
  FRecords[RecordCount - 1] := TCDB_StoredRecord.Create(Self);
  RecordAdded;
end;

procedure TCDB_Table.GetOpenDialogProperties(const PropertyName: string; var DefaultExt, Filter, Title, FileName: string);
begin
  inherited;
  DefaultExt := '*';
  Filter := 'All Files|*.*';
  Title := 'Open Table File';
end;

function TCDB_Table.LoadFieldNames(const Strings: TStringArray; const DataLinkType: TCDB_LinkDataType): Boolean;
var
  F: TStoredIniFile;
  S: TStringArray;
  i: Integer;
  dt: TCDB_DataType;
  procedure AddFieldName;
  begin
    Strings.Add(ReadPart(S[i], 1, '|'));
  end;
begin
  Strings.Clear;
  Result := FileExists(FFileName);
  if Result then
  begin
    F := TStoredIniFile.Create;
    S := TStringArray.Create;
    try
      F.LoadFromFile(FFileName);
      if F.ReadLines('Field Definitions', S, True, True, False) then
        for i := 0 to S.Count - 1 do
        begin
          dt := StringToDataType(ReadPart(S[i], 2, '|'));
          case DataLinkType of
            ldtString:
              case dt of
                dtString, dtInteger, dtCurrency, dtFloat, dtDateTime, dtBoolean, dtStrings, dtImage: AddFieldName;
              end;
            ldtBoolean:
              case dt of
                dtString, dtInteger, dtCurrency, dtFloat, dtBoolean: AddFieldName;
              end;
            ldtStrings:
              case dt of
                dtStrings: AddFieldName;
              end;
            ldtImage:
              case dt of
                dtImage: AddFieldName;
              end;
          end;
        end;
    finally
      S.Free;
      F.Free;
    end;
    if Strings.Count = 0 then
    begin
      ShowMessage('No fields exist.');
      Result := False;
    end;
  end
  else
    if FFileName = '' then
      ShowMessage('Table filename not specified.')
    else
      ShowMessage('Table file "' + FFileName + '" not found.');
end;

function TCDB_Table.LoadFromFile(const FileName: string): Boolean;
var
  F: TStoredIniFile;
  S: string;
  Lines: TStringArray;
  i, j: Integer;
begin
  Result := inherited LoadFromFile(FileName);
  if Result then
  begin
    F := TStoredIniFile.Create;
    Lines := TStringArray.Create;
    try
      if F.LoadFromFile(FileName) then
      begin
        TCDB_TableFieldDefs(FieldDefs).LoadFieldDefs(F, FileName);
        if FieldCount > 0 then
          if F.ReadLines('Records', Lines, True, True, False) then
          begin
            for i := 0 to Lines.Count - 1 do
            begin
              AddRecord;
              for j := 0 to FieldCount - 1 do
                case FieldDefs[j].DataType of
                  dtString: TCDB_StringField(Records[RecordCount - 1][j]).Value := ReadPart(Lines[i], j + 1, '|');
                  dtInteger: TCDB_IntegerField(Records[RecordCount - 1][j]).Value := StringToInteger(ReadPart(Lines[i], j + 1, '|'));
                  dtCurrency: TCDB_CurrencyField(Records[RecordCount - 1][j]).Value := StringToCurrency(ReadPart(Lines[i], j + 1, '|'));
                  dtFloat: TCDB_FloatField(Records[RecordCount - 1][j]).Value := StringToFloat(ReadPart(Lines[i], j + 1, '|'));
                  dtDateTime:
                    with TCDB_DateTimeField(Records[RecordCount - 1][j]) do
                    begin
                      S := ReadPart(Lines[i], j + 1, '|');
                      try
                        Value := StrToFloat(S);
                        ValueEmpty := False;
                      except
                        Value := 0;
                        ValueEmpty := True;
                      end;
                    end;
                  dtBoolean: TCDB_BooleanField(Records[RecordCount - 1][j]).Value := StringToBoolean(ReadPart(Lines[i], j + 1, '|'));
                  dtStrings: TCDB_StringsField(Records[RecordCount - 1][j]).LoadValue(ExpandPath(FileName, ReadPart(Lines[i], j + 1, '|')));
                  dtImage: TCDB_ImageField(Records[RecordCount - 1][j]).LoadValue(ExpandPath(FileName, ReadPart(Lines[i], j + 1, '|')));
                end;
            end;
            Active := True;
            Exit;
          end;
      end;
    finally
      Lines.Free;
      F.Free;
    end;
  end;
  Active := False;
  Result := False;
end;

function TCDB_Table.SaveToFile(const FileName: string): Boolean;
var
  F: Text;
  i, j: Integer;
  Line: string;
begin
  Result := inherited SaveToFile(FileName) and ValidateAllRecords;
  if Result then
  begin
    AssignFile(F, FileName);
    Rewrite(F);
    for i := 0 to FieldCount - 1 do
      with FieldDefs[i] do
      begin
        Line := FieldName + '|' + DataTypeToString(DataType) + '|' + BooleanToString(Required) + '|' + BooleanToString(Duplicates) + '|';
        case DataType of
          dtString:
            with TCDB_StringFieldDef(FieldDefs[i]) do
              Line := Line + DefaultValue + '|' + IntegerToString(MaximumLength);
          dtInteger:
            with TCDB_IntegerFieldDef(FieldDefs[i]) do
              Line := Line + IntegerToString(DefaultValue) + '|' + IntegerToString(LowerRange) + '|' + IntegerToString(UpperRange) + '|' + IntegerToString(IncrementValue);
          dtCurrency:
            with TCDB_CurrencyFieldDef(FieldDefs[i]) do
              Line := Line + CurrencyToString(DefaultValue) + '|' + CurrencyToString(LowerRange) + '|' + CurrencyToString(UpperRange);
          dtFloat:
            with TCDB_FloatFieldDef(FieldDefs[i]) do
              Line := Line + FloatToString(DefaultValue) + '|' + FloatToString(LowerRange) + '|' + FloatToString(UpperRange);
          dtDateTime:
            with TCDB_DateTimeFieldDef(FieldDefs[i]) do
            begin
              if DefaultNow then
                Line := Line + 'NOW'
              else
                Line := Line + FloatToStr(DefaultValue);
              Line := Line + '|' + Format + '|' + FloatToStr(LowerRange) + '|' + FloatToStr(UpperRange);
            end;
          dtBoolean:
            with TCDB_BooleanFieldDef(FieldDefs[i]) do
              Line := Line + BooleanToString(DefaultValue);
          dtStrings:
            with TCDB_StringsFieldDef(FieldDefs[i]) do
              Line := Line + ExtractRelativePath(FileName, DefaultFileName);
          dtImage:
            with TCDB_ImageFieldDef(FieldDefs[i]) do
              Line := Line + ExtractRelativePath(FileName, DefaultFileName);
        else
          Continue;
        end;
        Writeln(F, Line);
      end;
    Writeln(F);
    for j := 0 to RecordCount - 1 do
    begin
      Line := '';
      for i := 0 to FieldCount - 1 do
      begin
        if Length(Line) > 0 then
          Line := Line + '|';
        case Records[j][i].FieldDef.DataType of
          dtString: Line := Line + TCDB_StringField(Records[j][i]).Value;
          dtInteger: Line := Line + IntegerToString(TCDB_IntegerField(Records[j][i]).Value);
          dtCurrency: Line := Line + CurrencyToString(TCDB_CurrencyField(Records[j][i]).Value);
          dtFloat: Line := Line + FloatToString(TCDB_FloatField(Records[j][i]).Value);
          dtDateTime: Line := Line + FloatToStr(TCDB_DateTimeField(Records[j][i]).Value);
          dtBoolean: Line := Line + BooleanToString(TCDB_BooleanField(Records[j][i]).Value);
          dtStrings: Line := Line + ExtractRelativePath(FileName, TCDB_StringsField(Records[j][i]).FileName);
          dtImage: Line := Line + ExtractRelativePath(FileName, TCDB_ImageField(Records[j][i]).FileName);
        end;
      end;
      Writeln(F, Line);
    end;
    CloseFile(F);
  end;
end;

procedure TCDB_Table.SetActive(const Value: Boolean);
var
  i: Integer;
begin
  if not (csLoading in ComponentState) and not Value then
    for i := 0 to FRecordCount - 1 do
      FRecords[i].Free;
  inherited;
end;

{ TCDB_Query }

function TCDB_Query.AddRecord: Boolean;
begin
  Result := inherited AddRecord;
  if not Result then
    Exit;
  FRecords[RecordCount - 1] := TCDB_Record.Create(Self);
  RecordAdded;
end;

procedure TCDB_Query.AddRecordSet(const RecordSet: TCDB_RecordSet; const Alias: string);
begin
  Inc(FRecordSetCount);
  SetLength(FRecordSets, FRecordSetCount);
  SetLength(FRecordSetAliases, FRecordSetCount);
  FRecordSets[FRecordSetCount - 1] := RecordSet;
  FRecordSetAliases[FRecordSetCount - 1] := Alias;
end;

procedure TCDB_Query.Before_AfterExecute_Event;
begin
end;

procedure TCDB_Query.DatabaseChanged;
begin
  Execute;
  inherited;
end;

destructor TCDB_Query.Destroy;
begin
  SetLength(FFieldRecordSets, 0);
  SetLength(FFieldIndexes, 0);
  SetLength(FFieldNames, 0);
  SetLength(FRecordSets, 0);
  SetLength(FRecordSetAliases, 0);
  inherited;
end;

procedure TCDB_Query.Execute;
var
  i, j, k, n: Integer;
begin
  if FieldCount = 0 then
    Exit;
  j := 1;
  for i := 0 to FieldCount - 1 do
    j := j * FFieldRecordSets[i].RecordCount;
  for i := 0 to j - 1 do
  begin
    AddRecord;
    n := 1;
    for k := 0 to FieldCount - 1 do
    begin
      n := n * FFieldRecordSets[k].RecordCount;
      Records[i].FFields[k] := FFieldRecordSets[k][(i div (j div n)) mod FFieldRecordSets[k].RecordCount][FFieldIndexes[k]];
    end;
  end;
  Before_AfterExecute_Event;
  if Assigned(FAfterExecute) then
    FAfterExecute(Self);
  FirstRecord;
end;

function TCDB_Query.GetFieldDef2(const RecordSetAlias, FieldName: string): TCDB_FieldDef;
var
  i, j: Integer;
begin
  for i := 0 to FRecordSetCount - 1 do
    if FRecordSetAliases[i] = RecordSetAlias then
      for j := 0 to FRecordSets[i].FieldCount - 1 do
        if FRecordSets[i].FieldDefs[j].FieldName = FieldName then
        begin
          Result := FRecordSets[i].FieldDefs[j];
          Exit;
        end;
  Result := nil;
end;

procedure TCDB_Query.GetOpenDialogProperties(const PropertyName: string; var DefaultExt, Filter, Title, FileName: string);
begin
  inherited;
  DefaultExt := '*';
  Filter := 'All Files|*.*';
  Title := 'Open Query File';
end;

function TCDB_Query.GetRecordSet(const Index: Integer): TCDB_RecordSet;
begin
  Result := FRecordSets[Index];
end;

function TCDB_Query.GetRecordSetAlias(const Index: Integer): string;
begin
  Result := FRecordSetAliases[Index];
end;

function TCDB_Query.LoadFieldNames(const Strings: TStringArray; const DataLinkType: TCDB_LinkDataType): Boolean;
var
  i: Integer;
  b: Boolean;
  procedure AddFieldName;
  begin
    Strings.Add(FieldDefs[i].FieldName);
  end;
begin
  Result := False;
  b := Active;
  if not b then
    if not LoadFromFile then
      Exit;
  if FieldCount = 0 then
  begin
    ShowMessage('No fields exist.');
    Exit;
  end;
  for i := 0 to FieldCount - 1 do
    with FieldDefs[i] do
      case DataLinkType of
        ldtString:
          case DataType of
            dtString, dtInteger, dtCurrency, dtFloat, dtDateTime, dtBoolean, dtStrings, dtImage: AddFieldName;
          end;
        ldtBoolean:
          case DataType of
            dtString, dtInteger, dtCurrency, dtFloat, dtBoolean: AddFieldName;
          end;
        ldtStrings:
          case DataType of
            dtStrings: AddFieldName;
          end;
        ldtImage:
          case DataType of
            dtImage: AddFieldName;
          end;
      end;
  Active := b;
  Result := True;
end;

function TCDB_Query.LoadFromFile(const FileName: string): Boolean;
var
  F: TStoredIniFile;
  Key, Value: string;
  Lines: TStringArray;
  i, j: Integer;
  Def: TCDB_FieldDef;
begin
  Result := (inherited LoadFromFile(FileName)) and (FDatabase <> nil);
  if Result then
  begin
    F := TStoredIniFile.Create;
    Lines := TStringArray.Create;
    try
      if F.LoadFromFile(FileName) then
        if F.ReadLines('Record Sets', Lines, True, True, False) then
        begin
          for i := 0 to Lines.Count - 1 do
          begin
            Key := ReadPart(Lines[i], 1, '=');
            Value := ExpandPath(FileName, ReadPart(Lines[i], 2, '='));
            j := FDatabase.RecordSetIndex(Value);
            if j >= 0 then
              AddRecordSet(FDatabase.RecordSets[j], Key)
            else
              if FDatabase.LoadRecordSet(Value) then
                AddRecordSet(FDatabase.RecordSets[FDatabase.RecordSetCount - 1], Key)
              else
              begin
                Result := False;
                Active := False;
                Exit;
              end;
          end;
          if F.ReadLines('Fields', Lines, True, True, False) then
          begin
            for i := 0 to Lines.Count - 1 do
            begin
              Key := ReadPart(Lines[i], 1, '=');
              Value := ReadPart(Lines[i], 2, '=');
              Def := GetFieldDef2(ReadPart(Value, 1, '.'), ReadPart(Value, 2, '.'));
              if Def <> nil then
              begin
                AddFieldDef(Def.DataType);
                SetLength(FFieldIndexes, FieldCount);
                FFieldIndexes[FieldCount - 1] := Def.FieldIndex;
                SetLength(FFieldNames, FieldCount);
                FFieldNames[FieldCount - 1] := Def.FieldName;
                SetLength(FFieldRecordSets, FieldCount);
                FFieldRecordSets[FieldCount - 1] := Def.RecordSet;
                FieldDefs[FieldCount - 1].Assign(Def);
                FieldDefs[FieldCount - 1].FFieldName := Key;
              end
              else
              begin
                Result := False;
                Active := False;
                Exit;
              end;
            end;
            Execute;
            Active := True;
            Exit;
          end;
        end;
    finally
      Lines.Free;
      F.Free;
    end;
  end;
  Active := False;
  Result := False;
end;

procedure TCDB_Query.SetActive(const Value: Boolean);
begin
  if not ((csLoading in ComponentState) and Value) then
  begin
    SetLength(FRecordSets, 0);
    SetLength(FRecordSetAliases, 0);
    FRecordSetCount := 0;
  end;
  inherited;
end;

{ TCDB_BlobPopupMenu }

constructor TCDB_BlobPopupMenu.Create(AOwner: TComponent);
var
  NewItem: TMenuItem;
begin
  inherited;
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Cut';
  NewItem.OnClick := CutMenuItemClick;
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Copy';
  NewItem.OnClick := CopyMenuItemClick;
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Paste';
  NewItem.OnClick := PasteMenuItemClick;
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := '-';
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Clear';
  NewItem.OnClick := ClearMenuItemClick;
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := '-';
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Change Filename';
  NewItem.OnClick := ChangeFileNameMenuItemClick;
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := '-';
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Open...';
  NewItem.OnClick := OpenMenuItemClick;
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Reopen';
  NewItem.OnClick := ReopenMenuItemClick;
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := '-';
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Save';
  NewItem.OnClick := SaveMenuItemClick;
  Items.Add(NewItem);
  NewItem := TMenuItem.Create(Self);
  NewItem.Caption := 'Save As...';
  NewItem.OnClick := SaveAsMenuItemClick;
  Items.Add(NewItem);
  FOpenDialog := TOpenDialog.Create(Self);
  FSaveDialog := TSaveDialog.Create(Self);
end;

procedure TCDB_BlobPopupMenu.Refresh;
var
  i: Integer;
begin
  for i := 0 to Items.Count - 1 do
    Items[i].Enabled := False;
end;

{ TCDB_ImagePopupMenu }

procedure TCDB_ImagePopupMenu.ChangeFileNameMenuItemClick(Sender: TObject);
var
  f: TCDB_ImageField;
begin
  f := FImage.DataLink.CurrentField;
  if f <> nil then
    f.FileName := InputBox('Change filename for "' + f.FieldDef.FieldName + '" field', 'Enter new filename:', f.FileName);
  Refresh;
end;

procedure TCDB_ImagePopupMenu.ClearMenuItemClick(Sender: TObject);
begin
  FImage.Clear;
  Refresh;
end;

procedure TCDB_ImagePopupMenu.CopyMenuItemClick(Sender: TObject);
begin
  FImage.CopyToClipboard;
  Refresh;
end;

constructor TCDB_ImagePopupMenu.Create(AOwner: TComponent);
var
  S: string;
begin
  inherited;
  FImage := TCDB_Image(AOwner);
  if FImage <> nil then
    FImage.PopupMenu := Self;
  S := ExtractFilePath(ParamStr(0));
  with OpenDialog do
  begin
    Filter := 'Bitmap Images (*.bmp)|*.BMP';
    DefaultExt := 'bmp';
    InitialDir := S;
    Title := 'Load Bitmap Image';
  end;
  with SaveDialog do
  begin
    Filter := 'Bitmap Images (*.bmp)|*.BMP';
    DefaultExt := 'bmp';
    InitialDir := S;
    Title := 'Save Bitmap Image';
  end;
end;

procedure TCDB_ImagePopupMenu.CutMenuItemClick(Sender: TObject);
begin
  FImage.CutToClipboard;
  Refresh;
end;

procedure TCDB_ImagePopupMenu.OpenMenuItemClick(Sender: TObject);
var
  f: TCDB_ImageField;
begin
  f := FImage.DataLink.CurrentField;
  if f <> nil then
    if OpenDialog.Execute then
    begin
      f.LoadValue(OpenDialog.FileName);
      Refresh;
    end;
end;

procedure TCDB_ImagePopupMenu.PasteMenuItemClick(Sender: TObject);
begin
  FImage.PasteFromClipboard;
  Refresh;
end;

procedure TCDB_ImagePopupMenu.Refresh;
var
  Item: TMenuItem;
  f: TCDB_ImageField;
  bmp: Graphics.TBitmap;
  b: Boolean;
  function ItemOK(const Caption: string): Boolean;
  begin
    Item := Items.Find(Caption);
    Result := Item <> nil;
  end;
begin
  inherited;
  f := FImage.DataLink.CurrentField;
  if (FImage = nil) or (f = nil) then
    Exit;
  if ItemOK('Cut') then
    Item.Enabled := not f.Empty;
  if ItemOK('Copy') then
    Item.Enabled := not f.Empty;
  if ItemOK('Paste') then
    Item.Enabled := Clipboard.HasFormat(CF_BITMAP);
  if ItemOK('Clear') then
    Item.Enabled := not f.Empty;
  if ItemOK('Change Filename') then
    Item.Enabled := True;
  if ItemOK('Open...') then
    Item.Enabled := True;
  if ItemOK('Reopen') then
  begin
    b := FileExists(f.FileName);
    if b then
    begin
      bmp := Graphics.TBitmap.Create;
      bmp.LoadFromFile(f.FileName);
      b := BitmapsEqual(f.Value.Picture.Bitmap, bmp);
      bmp.Free;
    end;
    Item.Enabled := not b;
  end;
  if ItemOK('Save') then
    Item.Enabled := SaveDialog.FileName = f.FileName;
  if ItemOK('Save As...') then
    Item.Enabled := True;
  OpenDialog.FileName := f.FileName;
end;

procedure TCDB_ImagePopupMenu.ReopenMenuItemClick(Sender: TObject);
var
  f: TCDB_ImageField;
begin
  f := FImage.DataLink.CurrentField;
  if f <> nil then
  begin
    f.LoadValue;
    Refresh;
  end;
end;

procedure TCDB_ImagePopupMenu.SaveAsMenuItemClick(Sender: TObject);
var
  f: TCDB_ImageField;
begin
  f := FImage.DataLink.CurrentField;
  if f <> nil then
    if SaveDialog.Execute then
    begin
      f.SaveValue(SaveDialog.FileName);
      Refresh;
    end;
end;

procedure TCDB_ImagePopupMenu.SaveMenuItemClick(Sender: TObject);
var
  f: TCDB_ImageField;
begin
  f := FImage.DataLink.CurrentField;
  if f <> nil then
  begin
    f.SaveValue;
    Refresh;
  end;
end;

{ TCDB_Label }

constructor TCDB_Label.Create(AOwner: TComponent);
begin
  inherited;
  FDataLink := TCDB_StringDataLink.Create;
  FDataLink.OnUpdateOutput := UpdateOutput;
end;

procedure TCDB_Label.UpdateOutput;
begin
  if csLoading in ComponentState then
    Exit;
  Caption := FDataLink.CurrentValue;
  if (Caption = '') and (csDesigning in ComponentState) then
    if FDataLink.FieldName = '' then
      Caption := Name
    else
      Caption := '[' + FDataLink.FieldName + ']';
end;

procedure TCDB_Label.Loaded;
begin
  inherited;
  UpdateOutput;
end;

destructor TCDB_Label.Destroy;
begin
  FDataLink.Free;
  inherited;
end;

procedure TCDB_Label.SetDataLink(const Value: TCDB_StringDataLink);
begin
  FDataLink.Assign(Value);
end;

procedure TCDB_Label.WndProc(var Message: TMessage);
var
  C: TControl;
begin
  if Message.Msg = CDBM_GETDATALINK then
  begin
    C := TControl(Message.LParam);
    if C <> nil then
      if C is TControl then
        if C = Self then
          Message.Result := Integer(FDataLink);
  end;
  inherited;
end;

{ TCDB_Edit }

procedure TCDB_Edit.Change;
begin
  FDataLink.CurrentValue := Text;
  inherited;
end;

constructor TCDB_Edit.Create(AOwner: TComponent);
begin
  inherited;
  FDataLink := TCDB_StringDataLink.Create;
  FDataLink.OnUpdateOutput := UpdateOutput;
end;

destructor TCDB_Edit.Destroy;
begin
  FDataLink.Free;
  inherited;
end;

procedure TCDB_Edit.DoExit;
begin
  UpdateOutput;
  inherited;
end;

procedure TCDB_Edit.Loaded;
begin
  inherited;
  UpdateOutput;
end;

procedure TCDB_Edit.UpdateOutput;
begin
  if csLoading in ComponentState then
    Exit;
  Text := FDataLink.CurrentValue;
  if (Text = '') and (csDesigning in ComponentState) then
    if FDataLink.FieldName = '' then
      Text := Name
    else
      Text := '[' + FDataLink.FieldName + ']';
end;

procedure TCDB_Edit.SetDataLink(const Value: TCDB_StringDataLink);
begin
  FDataLink.Assign(Value);
end;

procedure TCDB_Edit.WndProc(var Message: TMessage);
var
  C: TControl;
begin
  if Message.Msg = CDBM_GETDATALINK then
  begin
    C := TControl(Message.LParam);
    if C <> nil then
      if C is TControl then
        if C = Self then
          Message.Result := Integer(FDataLink);
  end;
  inherited;
end;

{ TCDB_ComboBox }

procedure TCDB_ComboBox.Change;
begin
  FDataLink.CurrentValue := FLookup.Bound[ItemIndex];
  inherited;
end;

constructor TCDB_ComboBox.Create(AOwner: TComponent);
begin
  inherited;
  FDataLink := TCDB_StringDataLink.Create;
  FLookup := TCDB_ComboBoxLookupDataLink.Create;
  FDataLink.OnUpdateOutput := UpdateOutput;
  FLookup.Displayed.OnUpdateOutput := UpdateLookup;
  FLookup.Bound.OnUpdateOutput := UpdateLookup;
end;

destructor TCDB_ComboBox.Destroy;
begin
  FDataLink.Free;
  FLookup.Free;
  inherited;
end;

procedure TCDB_ComboBox.DoExit;
begin
  UpdateOutput;
  inherited;
end;

procedure TCDB_ComboBox.UpdateLookup;
var
  Values: TStringArray;
  i: Integer;
begin
  inherited;
  Values := TStringArray.Create;
  if FLookup.Active then
    for i := 0 to FLookup.RecordSet.RecordCount - 1 do
      Values.Add(FLookup.Displayed[i]);
  Values.CopyToStrings(Items);
  UpdateOutput;
end;

procedure TCDB_ComboBox.Loaded;
begin
  inherited;
  UpdateLookup;
end;

procedure TCDB_ComboBox.UpdateOutput;
var
  i: Integer;
begin
  if csLoading in ComponentState then
    Exit;
  Text := '';
  if FLookup.Active then
    for i := 0 to FLookup.RecordSet.RecordCount - 1 do
      if FLookup.RecordSet[i][FLookup.Bound.FieldDef.FieldIndex].ValueAsString = FDataLink.CurrentValue then
        Text := FLookup.Displayed[i];
  if (Text = '') and (csDesigning in ComponentState) then
    if FDataLink.FieldName = '' then
      Text := Name
    else
    begin
      Text := '[' + FDataLink.RecordSet.Name + '.' + FDataLink.FieldName;
      if FLookup.Bound.Active then
        Text := Text + ' FROM ' + FLookup.RecordSet.Name + '.' + FLookup.Bound.FieldName;
      if FLookup.Displayed.Active then
        Text := Text + ' AS ' + FLookup.RecordSet.Name + '.' + FLookup.Displayed.FieldName;
      Text := Text + ']';
    end;
end;

procedure TCDB_ComboBox.SetDataLink(const Value: TCDB_StringDataLink);
begin
  FDataLink.Assign(Value);
end;

procedure TCDB_ComboBox.SetLookup(const Value: TCDB_ComboBoxLookupDataLink);
begin
  FLookup.Assign(Value);
end;

procedure TCDB_ComboBox.WndProc(var Message: TMessage);
var
  C: TControl;
begin
  if Message.Msg = CDBM_GETDATALINK then
  begin
    C := TControl(Message.LParam);
    if C <> nil then
      if C is TControl then
        if C = Self then
          Message.Result := Integer(FDataLink);
  end;
  inherited;
end;

{ TCDB_CheckBox }

procedure TCDB_CheckBox.Click;
begin
  FDataLink.CurrentValue := Checked;
  inherited;
end;

constructor TCDB_CheckBox.Create(AOwner: TComponent);
begin
  inherited;
  FDataLink := TCDB_BooleanDataLink.Create;
  FDataLink.OnUpdateOutput := UpdateOutput;
end;

procedure TCDB_CheckBox.UpdateOutput;
begin
  if csLoading in ComponentState then
    Exit;
  Checked := FDataLink.CurrentValue;
  if (csDesigning in ComponentState) and (Caption = Name) then
    if FDataLink.FieldName = '' then
      Caption := Name
    else
      Caption := FDataLink.FieldName;
end;

procedure TCDB_CheckBox.Loaded;
begin
  inherited;
  UpdateOutput;
end;

destructor TCDB_CheckBox.Destroy;
begin
  FDataLink.Free;
  inherited;
end;

procedure TCDB_CheckBox.SetDataLink(const Value: TCDB_BooleanDataLink);
begin
  FDataLink.Assign(Value);
end;

procedure TCDB_CheckBox.WndProc(var Message: TMessage);
var
  C: TControl;
begin
  if Message.Msg = CDBM_GETDATALINK then
  begin
    C := TControl(Message.LParam);
    if C <> nil then
      if C is TControl then
        if C = Self then
          Message.Result := Integer(FDataLink);
  end;
  inherited;
end;

{ TCDB_Memo }

procedure TCDB_Memo.Change;
begin
  FDataLink.CurrentValue := Lines.Text;
  inherited;
end;

constructor TCDB_Memo.Create(AOwner: TComponent);
begin
  inherited;
  FDataLink := TCDB_StringsDataLink.Create;
  FDataLink.OnUpdateOutput := UpdateOutput;
end;

procedure TCDB_Memo.UpdateOutput;
begin
  if HandleAllocated then
    Lines.Text := FDataLink.CurrentValue;
end;

procedure TCDB_Memo.Loaded;
begin
  inherited;
  UpdateOutput;
end;

function TCDB_Memo.LoadFromFile(const FileName: string): Boolean;
begin
  if FDataLink.Active then
    Result := FDataLink.CurrentField.LoadValue(FileName)
  else
    Result := False;
end;

destructor TCDB_Memo.Destroy;
begin
  FDataLink.Free;
  inherited;
end;

procedure TCDB_Memo.SetDataLink(const Value: TCDB_StringsDataLink);
begin
  FDataLink.Assign(Value);
end;

procedure TCDB_Memo.WndProc(var Message: TMessage);
var
  C: TControl;
begin
  if Message.Msg = CDBM_GETDATALINK then
  begin
    C := TControl(Message.LParam);
    if C <> nil then
      if C is TControl then
        if C = Self then
          Message.Result := Integer(FDataLink);
  end;
  inherited;
end;

{ TCDB_Image }

procedure TCDB_Image.Center;
begin
  HorzScrollBar.Position := 0;
  VertScrollBar.Position := 0;
  if FImage.Width < Width then
    FImage.Left := ((Width - FImage.Width) div 2) - 1
  else
    FImage.Left := 0;
  if FImage.Height < Height then
    FImage.Top := ((Height - FImage.Height) div 2) - 1
  else
    FImage.Top := 0;
end;

procedure TCDB_Image.Change;
begin
  FDataLink.CurrentValue := FImage;
end;

procedure TCDB_Image.Clear;
var
  tmp: Graphics.TBitmap;
begin
  if FImage.Picture.Bitmap.Empty then
    Exit;
  tmp := Graphics.TBitmap.Create;
  FImage.Picture.Assign(tmp);
  FImage.Width := 0;
  FImage.Height := 0;
  tmp.Free;
  Change;
end;

function TCDB_Image.CopyToClipboard: Boolean;
begin
  Result := not FImage.Picture.Bitmap.Empty;
  if Result then
    Clipboard.Assign(FImage.Picture.Bitmap);
end;

constructor TCDB_Image.Create(AOwner: TComponent);
begin
  inherited;
  FDataLink := TCDB_ImageDataLink.Create;
  Width := 105;
  Height := 105;
  FImage := TImage.Create(Self);
  FImage.Width := 0;
  FImage.Height := 0;
  FImage.AutoSize := True;
  FImage.Parent := Self;
  Color := clWhite;
  FPopupMenu := TCDB_ImagePopupMenu.Create(Self);
  FDataLink.OnUpdateOutput := UpdateOutput;
end;

function TCDB_Image.CutToClipboard: Boolean;
begin
  Result := CopyToClipboard;
  if Result then
    Clear;
end;

procedure TCDB_Image.UpdateOutput;
var
  p: TImage;
begin
  if csLoading in ComponentState then
    Exit;
  p := FDataLink.CurrentValue;
  if p <> nil then
  begin
    if (p.Picture.Width > 0) and  (p.Picture.Height > 0) then
    begin
      FImage.Picture.Assign(p.Picture);
      Center;
    end
    else
      Clear;
  end
  else
    Clear;
  FPopupMenu.Refresh;
end;

procedure TCDB_Image.Loaded;
begin
  inherited;
  UpdateOutput;
end;

function TCDB_Image.LoadFromFile(const FileName: string): Boolean;
begin
  if FDataLink.Active then
    Result := FDataLink.CurrentField.LoadValue(FileName)
  else
    Result := False;
end;

function TCDB_Image.PasteFromClipboard: Boolean;
begin
  Result := Clipboard.HasFormat(CF_BITMAP);
  if Result then
  begin
    FImage.Picture.Bitmap.Assign(Clipboard);
    Center;
    if FDataLink.Active then
      Change;
  end;
end;

destructor TCDB_Image.Destroy;
begin
  FDataLink.Free;
  inherited;
end;

procedure TCDB_Image.SetDataLink(const Value: TCDB_ImageDataLink);
begin
  FDataLink.Assign(Value);
end;

procedure TCDB_Image.WndProc(var Message: TMessage);
var
  C: TControl;
begin
  if Message.Msg = CDBM_GETDATALINK then
  begin
    C := TControl(Message.LParam);
    if C <> nil then
      if C is TControl then
        if C = Self then
          Message.Result := Integer(FDataLink);
  end;
  inherited;
end;

{ TCDB_Panel }

procedure TCDB_Panel.CMControlListChange(var Message: TCMControlListChange);
begin
  inherited;
  FDataLink.ControlListChange(Message.Control, Message.Inserting);
end;

constructor TCDB_Panel.Create(AOwner: TComponent);
begin
  inherited;
  FDataLink := TCDB_WindowDataLink.Create;
  Width := 300;
  Height := 150;
end;

destructor TCDB_Panel.Destroy;
begin
  FDataLink.Free;
  inherited;
end;

procedure TCDB_Panel.SetDataLink(const Value: TCDB_WindowDataLink);
begin
  FDataLink.Assign(Value);
end;

{ TCDB_GridPanel }

procedure TCDB_GridPanel.CMControlListChange(var Message: TCMControlListChange);
begin
  inherited;
  if Owner is TCDB_ControlGrid then
    TCDB_ControlGrid(Owner).PanelControlListChange(Message.Control, Message.Inserting);
end;

constructor TCDB_GridPanel.Create(AOwner: TComponent);
begin
  inherited;
  ControlStyle := [csAcceptsControls, csCaptureMouse, csClickEvents, csDoubleClicks, csOpaque, csReplicatable];
  SetSubComponent(True);
end;

procedure TCDB_GridPanel.Paint;
begin
  if csDesigning in ComponentState then
  begin
    Canvas.Pen.Color := clBlack;
    Canvas.Pen.Style := psDash;
    Canvas.Brush.Color := Color;
    Canvas.Rectangle(ClientRect);
    ParentColor := True;
  end;
end;

{ TCDB_ControlGrid }

constructor TCDB_ControlGrid.Create(AOwner: TComponent);
begin
  inherited;
  FChanging := False;
  ControlStyle := [csOpaque, csDoubleClicks];
  Width := 300;
  Height := 200;
  FPanel := TCDB_GridPanel.Create(Self);
  FPanel.Parent := Self;
  FPanel.Left := 0;
  FPanel.Top := 0;
  FDataLink := TCDB_WindowDataLink.Create;
  FDataLink.OnUpdateOutput := UpdateOutput;
  FRowCount := 3;
end;

destructor TCDB_ControlGrid.Destroy;
begin
  FPanel.Free;
  FDataLink.Free;
  inherited;
end;

function TCDB_ControlGrid.GetChildParent: TComponent;
begin
  Result := FPanel;
end;

procedure TCDB_ControlGrid.GetChildren(Proc: TGetChildProc; Root: TComponent);
begin
  FPanel.GetChildren(Proc, Root);
end;

function TCDB_ControlGrid.GetPanelBounds(const Index: Integer): TRect;
begin
  Result.Left := 0;
  Result.Top := FPanel.Height * Index;
  Result.Right := FPanel.Width - GetSystemMetrics(SM_CXVSCROLL);
  Result.Bottom := Result.Top + FPanel.Height;
end;

procedure TCDB_ControlGrid.Loaded;
begin
  inherited;
  SetRowCount(FRowCount);
end;

procedure TCDB_ControlGrid.Paint;
var
  SI: TScrollInfo;
  i, j: Integer;
begin
  if csDesigning in ComponentState then
  begin
    with Canvas do
    begin
      Brush.Style := bsSolid;
      Brush.Color := Color;
      FillRect(Rect(0, FPanel.Height, Width - GetSystemMetrics(SM_CXVSCROLL), Height));
      Brush.Style := bsBDiagonal;
      Brush.Color := clBlack;
      Pen.Color := clBlack;
      Rectangle(0, FPanel.Height, Width - GetSystemMetrics(SM_CXVSCROLL), Height);
      ParentColor := True;
    end;
    FPanel.Repaint;
    SI.cbSize := SizeOf(SI);
    SI.fMask := SIF_ALL;
    SI.nMax := 10;
    SI.nMin := 0;
    SI.nPage := 1;
    SI.nPos := 0;
    SI.nTrackPos := 0;
    SetScrollInfo(Handle, SB_VERT, SI, True);
    EnableScrollBar(Handle, SB_VERT, ESB_DISABLE_BOTH);
  end
  else
    if FDataLink.Active then
    begin
      FChanging := True;
      j := FDataLink.RecordIndex;
      if FDataLink.RecordSet.RecordCount > FRowCount then
      begin
        if j < FPanelIndex then
          FPanelIndex := j;
        if FPanelIndex < FRowCount - FDataLink.RecordSet.RecordCount + j then
          FPanelIndex := FRowCount - FDataLink.RecordSet.RecordCount + j;
      end
      else
        FPanelIndex := j;
      for i := 0 to Min(FDataLink.RecordSet.RecordCount - 1, FRowCount - 1) do
        if i <> FPanelIndex then
        begin
          FDataLink.RecordIndex := j - FPanelIndex + i;
          FPanel.PaintTo(Canvas, 0, FPanel.Height * i);
        end;
      FPanel.Top := FPanel.Height * FPanelIndex;
      FDataLink.RecordIndex := j;
      FChanging := False;
      SI.cbSize := SizeOf(SI);
      SI.fMask := SIF_ALL;
      SI.nMax := FDataLink.RecordSet.RecordCount - 1;
      SI.nMin := 0;
      SI.nPage := 1;
      SI.nPos := FDataLink.RecordIndex;
      SI.nTrackPos := FDataLink.RecordIndex;
      SetScrollInfo(Handle, SB_VERT, SI, True);
      EnableScrollBar(Handle, SB_VERT, ESB_ENABLE_BOTH);
    end;
end;

procedure TCDB_ControlGrid.PanelControlListChange(const Control: TControl; const Inserting: Boolean);
begin
  FDataLink.ControlListChange(Control, Inserting);
end;

procedure TCDB_ControlGrid.SetDataLink(const Value: TCDB_WindowDataLink);
begin
  FDataLink.Assign(Value);
end;

procedure TCDB_ControlGrid.SetRowCount(const Value: Integer);
begin
  FRowCount := Value;
  FPanel.Height := Height div FRowCount;
  Invalidate;
end;

procedure TCDB_ControlGrid.UpdateOutput;
begin
  if not FChanging then
    Invalidate;
end;

procedure TCDB_ControlGrid.WMLButtonDown(var Message: TWMLButtonDown);
var
  P: TPoint;
  i, j: Integer;
begin
  if FDataLink.Active then
  begin
    P := SmallPointToPoint(Message.Pos);
    for i := 0 to Min(FDataLink.RecordSet.RecordCount - 1, FRowCount - 1) do
      if PtInRect(GetPanelBounds(i), P) then
      begin
        j := FPanelIndex;
        FPanelIndex := i;
        FDataLink.RecordIndex := FDataLink.RecordIndex - j + i;
        Exit;
      end;
  end;
end;

procedure TCDB_ControlGrid.WMSize(var Message: TWMSize);
begin
  inherited;
  FPanel.Height := Height div FRowCount;
  FPanel.Width := Width - GetSystemMetrics(SM_CXVSCROLL);
  Invalidate;
end;

procedure TCDB_ControlGrid.WMVScroll(var Message: TWMVScroll);
begin
  FChanging := True;
  case Message.ScrollCode of
    SB_LINEDOWN: FDataLink.NextRecord;
    SB_LINEUP: FDataLink.PreviousRecord;
    SB_THUMBPOSITION: FDataLink.RecordIndex := Message.Pos;
  end;
  FChanging := False;
  Invalidate;
end;

end.

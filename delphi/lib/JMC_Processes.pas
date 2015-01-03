unit JMC_Processes;

interface

uses
  Classes,
  ShellApi,
  SysUtils,
  TLHelp32,
  Windows;

type

  { TProcessInfo }

  TProcessInfo = class(TObject)
  private
    FBasePriority: Longint;
    FDefaultHeapID: Cardinal;
    FExecutableFile: string;
    FModuleID: Cardinal;
    FParentProcessID: Cardinal;
    FProcessID: Cardinal;
    FThreadCount: Cardinal;
    FUsageCount: Cardinal;
  public
    constructor Create(var ProcessEntry: TProcessEntry32);
    property BasePriority: Longint read FBasePriority;
    property DefaultHeapID: Cardinal read FDefaultHeapID;
    property ExecutableFile: string read FExecutableFile;
    property ModuleID: Cardinal read FModuleID;
    property ParentProcessID: Cardinal read FParentProcessID;
    property ProcessID: Cardinal read FProcessID;
    property ThreadCount: Cardinal read FThreadCount;
    property UsageCount: Cardinal read FUsageCount;
  end;

  { TProcessInfoArray }

  TProcessInfoArray = class(TObject)
  private
    FProcesses: TList;
  private
    procedure AddProcess(var ProcessEntry: TProcessEntry32);
    function GetCount: Integer;
    function GetProcess(const Index: Integer): TProcessInfo;
  public
    constructor Create;
    destructor Destroy; override;
  public
    procedure Clear;
    function Exists(const FileName: string): Boolean;
    function IndexOf(const FileName: string): Integer;
    procedure Refresh;
    function Terminate(const FileName: string): Boolean;
  public
    property Count: Integer read GetCount;
    property Processes[const Index: Integer]: TProcessInfo read GetProcess; default;
  end;

implementation

{ TProcessInfo }

constructor TProcessInfo.Create(var ProcessEntry: TProcessEntry32);
begin
  FBasePriority := ProcessEntry.pcPriClassBase;
  FDefaultHeapID := ProcessEntry.th32DefaultHeapID;
  FExecutableFile := string(ProcessEntry.szExeFile);
  FModuleID := ProcessEntry.th32ModuleID;
  FParentProcessID := ProcessEntry.th32ParentProcessID;
  FProcessID := ProcessEntry.th32ProcessID;
  FThreadCount := ProcessEntry.cntThreads;
  FUsageCount := ProcessEntry.cntUsage;
end;

{ TProcessInfoArray }

procedure TProcessInfoArray.AddProcess(var ProcessEntry: TProcessEntry32);
begin
  FProcesses.Add(TProcessInfo.Create(ProcessEntry));
end;

procedure TProcessInfoArray.Clear;
var
  i: Integer;
begin
  for i := 0 to Count - 1 do
    Processes[i].Free;
  FProcesses.Clear;
end;

constructor TProcessInfoArray.Create;
begin
  FProcesses := TList.Create;
  Refresh;
end;

destructor TProcessInfoArray.Destroy;
begin
  Clear;
  FProcesses.Free;
  inherited;
end;

function TProcessInfoArray.Exists(const FileName: string): Boolean;
begin
  Result := IndexOf(FileName) >= 0;
end;

function TProcessInfoArray.GetCount: Integer;
begin
  Result := FProcesses.Count;
end;

function TProcessInfoArray.GetProcess(const Index: Integer): TProcessInfo;
begin
  Result := FProcesses[Index];
end;

function TProcessInfoArray.IndexOf(const FileName: string): Integer;
var
  i: Integer;
  S: string;
begin
  S := UpperCase(FileName);
  for i := 0 to Count - 1 do
    if UpperCase(ExtractFileName(Processes[i].ExecutableFile)) = S then
    begin
      Result := i;
      Exit;
    end;
  Result := -1;
end;

procedure TProcessInfoArray.Refresh;
var
  SnapShot: THandle;
  ProcessEntry: TProcessEntry32;
begin
  Clear;
  SnapShot := CreateToolhelp32Snapshot(TH32CS_SNAPPROCESS, 0);
  try
    if SnapShot <> 0 then
    begin
      ProcessEntry.dwSize := SizeOf(TProcessEntry32);
      if Process32First(SnapShot, ProcessEntry) then
      begin
        AddProcess(ProcessEntry);
        while Process32Next(SnapShot, ProcessEntry) do
         AddProcess(ProcessEntry);
      end;
    end;
  finally
    CloseHandle(SnapShot);
  end;
end;

function TProcessInfoArray.Terminate(const FileName: string): Boolean;
var
  Handle: THandle;
  i: Integer;
  ExitCode: Cardinal;
begin
  i := IndexOf(FileName);
  Result := i <> -1;
  if Result then
  begin
    Handle := OpenProcess(1, False, Processes[i].ProcessID);
    try
      Result := Handle <> 0;
      if Result then
      begin
        Result := GetExitCodeProcess(Handle, ExitCode);
        if Result then
        begin
          Result := TerminateProcess(Handle, ExitCode);
          if Result then
            Result := WaitForSingleObject(Handle, INFINITE) = WAIT_OBJECT_0;
        end;
      end;
    finally
      CloseHandle(Handle);
    end;
  end;
end;

end.
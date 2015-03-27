unit JMC_NetworkUtils;

interface

uses
  Windows,
  Classes,
  SysUtils,
  JMC_Strings;

function FindComputers(const Computers: JMC_Strings.TStringArray): Boolean;

implementation

const
  MAXENTRIES = 250;

function FindComputers(const Computers: JMC_Strings.TStringArray): Boolean;
// http://www.infojet.cz/program/delphi/tips/tip0012.html [13 July 2008]
var
  EnumWorkGroupHandle: THandle;
  EnumComputerHandle: THandle;
  EnumError: DWORD;
  Network: TNetResource;
  WorkGroupEntries: DWORD;
  ComputerEntries: DWORD;
  EnumWorkGroupBuffer: array[1..MAXENTRIES] of TNetResource;
  EnumComputerBuffer: array[1..MAXENTRIES] of TNetResource;
  EnumBufferLength: DWORD;
  i: DWORD;
  j: DWORD;
begin
  Computers.Clear;
  FillChar(Network, SizeOf(Network), 0);
  with Network do
  begin
    dwScope := RESOURCE_GLOBALNET;
    dwType := RESOURCETYPE_ANY;
    dwUsage := RESOURCEUSAGE_CONTAINER;
  end;
  EnumError := WNetOpenEnum(RESOURCE_GLOBALNET, RESOURCETYPE_ANY, 0, @Network, EnumWorkGroupHandle);
  if EnumError = NO_ERROR then
  begin
    WorkGroupEntries := MAXENTRIES;
    EnumBufferLength := SizeOf(EnumWorkGroupBuffer);
    EnumError := WNetEnumResource(EnumWorkGroupHandle, WorkGroupEntries, @EnumWorkGroupBuffer, EnumBufferLength);
    if EnumError = NO_ERROR then
    begin
      for i := 1 to WorkGroupEntries do
      begin
        EnumError := WNetOpenEnum(RESOURCE_GLOBALNET, RESOURCETYPE_ANY, 0, @EnumWorkGroupBuffer[i], EnumComputerHandle);
        if EnumError = NO_ERROR then
        begin
          ComputerEntries := MaxEntries;
          EnumBufferLength := SizeOf(EnumComputerBuffer);
          EnumError := WNetEnumResource(EnumComputerHandle, ComputerEntries, @EnumComputerBuffer, EnumBufferLength);
          if EnumError = NO_ERROR then
            for j := 1 to ComputerEntries do
              Computers.Add(EnumComputerBuffer[j].lpRemoteName);
          WNetCloseEnum(EnumComputerHandle);
        end;
      end;
    end;
    WNetCloseEnum(EnumWorkGroupHandle);
  end;
  if EnumError = ERROR_NO_MORE_ITEMS then
    EnumError := NO_ERROR;
  Result := EnumError = NO_ERROR;
end;

end.
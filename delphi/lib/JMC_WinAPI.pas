unit JMC_WinAPI;

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                     Jared Crutchfield's Delphi Library                     //
//                                                                            //
//                           JMC_WinAPI Library Unit                          //
//                                                                            //
//                            Modified 19/01/2012                             //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

interface

uses
  SysUtils,
  Windows,
  Registry,
  ShlObj,
  Controls,
  ComObj,
  ActiveX,
  Forms,
  ShellAPI,
  filectrl;

type
  TJMC_ShortcutLocation = (slDesktop, slPersonal, slPrograms, slSendTo, slStartMenu, slStartup, slTemplates);

function BrowseDirectory(var Directory: string; const Parent: TWinControl; const Caption: string; const Root: WideString = ''; const NewFolderButton: Boolean = False): Boolean;
function CreateShortcut(const FileName: string; const ShortcutName: string; const ShortcutLocation: TJMC_ShortcutLocation; const Params: string = ''): Boolean;
function ShellOpen(const FileName: string): Boolean;

implementation

// Copied from "filectrl" unit.
function SelectDirCB(Wnd: HWND; uMsg: UINT; lParam, lpData: LPARAM): Integer stdcall;
begin
  if (uMsg = BFFM_INITIALIZED) and (lpData <> 0) then
    SendMessage(Wnd, BFFM_SETSELECTION, Integer(True), lpData);
  result := 0;
end;

// Based on "SelectDirectory" function in "filectrl" unit.
function BrowseDirectory(var Directory: string; const Parent: TWinControl; const Caption: string; const Root: WideString = ''; const NewFolderButton: Boolean = False): Boolean;
var
  WindowList: Pointer;
  BrowseInfo: TBrowseInfo;
  Buffer: PChar;
  OldErrorMode: Cardinal;
  RootItemIDList, ItemIDList: PItemIDList;
  ShellMalloc: IMalloc;
  IDesktopFolder: IShellFolder;
  Eaten, Flags: LongWord;
begin
  Result := False;
  if not DirectoryExists(Directory) then
    Directory := '';
  FillChar(BrowseInfo, SizeOf(BrowseInfo), 0);
  if (ShGetMalloc(ShellMalloc) = S_OK) and (ShellMalloc <> nil) then
  begin
    Buffer := ShellMalloc.Alloc(MAX_PATH);
    try
      RootItemIDList := nil;
      if Root <> '' then
      begin
        SHGetDesktopFolder(IDesktopFolder);
        IDesktopFolder.ParseDisplayName(Application.Handle, nil, POleStr(Root), Eaten, RootItemIDList, Flags);
      end;
      with BrowseInfo do
      begin
        hwndOwner := Application.Handle;
        pidlRoot := RootItemIDList;
        pszDisplayName := Buffer;
        lpszTitle := PChar(Caption);
        ulFlags := BIF_RETURNONLYFSDIRS or BIF_STATUSTEXT;
        if NewFolderButton then
          ulFlags := ulFlags or BIF_NEWDIALOGSTYLE;
        if Directory <> '' then
        begin
          lpfn := SelectDirCB;
          lParam := Integer(PChar(Directory));
        end;
      end;
      WindowList := DisableTaskWindows(0);
      OldErrorMode := SetErrorMode(SEM_FAILCRITICALERRORS);
      try
        ItemIDList := ShBrowseForFolder(BrowseInfo);
      finally
        SetErrorMode(OldErrorMode);
        EnableTaskWindows(WindowList);
      end;
      Result :=  ItemIDList <> nil;
      if Result then
      begin
        ShGetPathFromIDList(ItemIDList, Buffer);
        ShellMalloc.Free(ItemIDList);
        Directory := IncludeTrailingPathDelimiter(Buffer);
      end;
    finally
      ShellMalloc.Free(Buffer);
    end;
  end;
end;

function CreateShortcut(const FileName: string; const ShortcutName: string; const ShortcutLocation: TJMC_ShortcutLocation; const Params: string = ''): Boolean;
var
  ShortcutObject: IUnknown;
  ShortcutShellLink: IShellLink;
  ShortcutPersistFile: IPersistFile;
  WideFileName: WideString;
  Registry: TRegistry;
  S: string;
begin
  Result := False;
  if not FileExists(FileName) then
    Exit;
  ShortcutObject := CreateComObject(CLSID_ShellLink);
  ShortcutShellLink := ShortcutObject As IShellLink;
  ShortcutPersistFile := ShortcutObject As IPersistFile;
  with ShortcutShellLink do
  begin
    SetPath(PChar(FileName));
    SetWorkingDirectory(PChar(ExtractFilePath(FileName)));
    SetArguments(PChar(Params));
  end;
  Registry := TRegistry.Create;
  Registry.RootKey := HKEY_CURRENT_USER;
  Registry.OpenKey('\Software\Microsoft\Windows\CurrentVersion\Explorer\Shell Folders', False);
  case ShortcutLocation of
    slDesktop: S := Registry.ReadString('Desktop');
    slPersonal: S := Registry.ReadString('Personal');
    slPrograms: S := Registry.ReadString('Programs');
    slSendTo: S := Registry.ReadString('SendTo');
    slStartMenu: S := Registry.ReadString('Start Menu');
    slStartup: S := Registry.ReadString('Startup');
    slTemplates: S := Registry.ReadString('Templates');
  else
    S := '';
  end;
  Registry.Free;
  S := IncludeTrailingPathDelimiter(S);
  if DirectoryExists(S) then
  begin
    WideFileName := S + ShortcutName + '.lnk';
    ShortcutPersistFile.Save(PWChar(WideFileName), False);
    Result := True;
  end;
end;

function ShellOpen(const FileName: string): Boolean;
begin
  Result := SysUtils.FileExists(FileName);
  if Result then
    ShellAPI.ShellExecute(0, 'open', PChar(FileName), '', nil, Windows.SW_SHOWNORMAL)
end;

end.
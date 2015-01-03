unit JMC_FileSearch;

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//                     Jared Crutchfield's Delphi Library                     //
//                                                                            //
//                        JMC_FileSearch Library Unit                         //
//                                                                            //
//                            Modified 29/09/2011                             //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

interface

uses
  Classes,
  SysUtils,
  Windows,
  Dialogs,
  Forms,
  ComCtrls,
  Graphics,
  ExtCtrls,
  Controls,
  ShellAPI;

type

  TJMC_File = class;
  TJMC_Folder = class;
  TJMC_FileManager = class;

  TJMC_FileAttribute = (JMC_faReadOnly, JMC_faHidden, JMC_faSysFile, JMC_faVolumeID, JMC_faDirectory, JMC_faArchive, JMC_faAnyFile);
  TJMC_FileAttributes = set of TJMC_FileAttribute;

  TJMC_AddFileEvent = procedure(const Sender: TObject; const AddedFile: TJMC_File) of object;
  TJMC_AddFolderEvent = procedure(const Sender: TObject; const AddedFolder: TJMC_Folder) of object;
  TJMC_AllowAddFileEvent = function(const FileName: string): Boolean of object;

  TJMC_File = class(TObject)
  private
    FAttributes: TJMC_FileAttributes;
    FExtension: string;
    FFileName: string;
    FFolder: TJMC_Folder;
    FName: string;
    FSize: Integer;
    FTime: TDateTime;
  public
    constructor Create(const Folder: TJMC_Folder; const SearchRec: TSearchRec);
  public
    property Attributes: TJMC_FileAttributes read FAttributes;
    property Extension: string read FExtension;
    property FileName: string read FFileName;
    property Folder: TJMC_Folder read FFolder;
    property Name: string read FName;
    property Size: Integer read FSize;
    property Time: TDateTime read FTime;
  end;

  TJMC_Folder = class(TObject)
  private
    FAttributes: TJMC_FileAttributes;
    FChildren: TList;
    FFileManager: TJMC_FileManager;
    FFiles: TList;
    FItems: TList;
    FName: string;
    FParent: TJMC_Folder;
    FPath: string;
    FTime: TDateTime;
  private
    function GetChild(const Index: Integer): TJMC_Folder;
    function GetChildCount: Integer;
    function GetFile(const Index: Integer): TJMC_File;
    function GetFileCount: Integer;
    function GetItem(const Index: Integer): TObject;
    function GetItemCount: Integer;
    function GetItemName(const Index: Integer): string;
    procedure SetPath(const Value: string);
  public
    constructor Create(const Parent: TJMC_Folder);
    destructor Destroy; override;
  public
    procedure Clear;
    procedure Fill(const SearchRec: TSearchRec);
    function ItemIndex(const Item: TObject): Integer;
  public
    property Attributes: TJMC_FileAttributes read FAttributes;
    property ChildCount: Integer read GetChildCount;
    property Children[const Index: Integer]: TJMC_Folder read GetChild;
    property FileCount: Integer read GetFileCount;
    property FileManager: TJMC_FileManager read FFileManager write FFileManager;
    property Files[const Index: Integer]: TJMC_File read GetFile;
    property ItemCount: Integer read GetItemCount;
    property ItemNames[const Index: Integer]: string read GetItemName;
    property Items[const Index: Integer]: TObject read GetItem;
    property Name: string read FName;
    property Parent: TJMC_Folder read FParent;
    property Path: string read FPath write SetPath;
    property Time: TDateTime read FTime;
  end;

  TJMC_FileManager = class(TObject)
  private
    FCancelled: Boolean;
    FPaths: TStrings;
    FRootFolders: TList;
  private
    FOnAddFile: TJMC_AddFileEvent;
    FOnAddFolder: TJMC_AddFolderEvent;
    FOnAllowAddFile: TJMC_AllowAddFileEvent;
    FOnFinish: TNotifyEvent;
  private
    function GetRootFolder(const Index: Integer): TJMC_Folder;
    function GetRootFolderCount: Integer;
  public
    constructor Create;
    destructor Destroy; override;
  public
    function AllowAddFile(const FileName: string): Boolean;
    procedure CallAddFile(const AddedFile: TJMC_File);
    procedure CallAddFolder(const AddedFolder: TJMC_Folder);
    procedure Cancel;
    procedure ClearRootFolders;
    procedure Refresh;
  public
    property Cancelled: Boolean read FCancelled;
    property Paths: TStrings read FPaths;
    property RootFolderCount: Integer read GetRootFolderCount;
    property RootFolders[const Index: Integer]: TJMC_Folder read GetRootFolder; default;
  public
    property OnAddFile: TJMC_AddFileEvent read FOnAddFile write FOnAddFile;
    property OnAddFolder: TJMC_AddFolderEvent read FOnAddFolder write FOnAddFolder;
    property OnAllowAddFile: TJMC_AllowAddFileEvent read FOnAllowAddFile write FOnAllowAddFile;
    property OnFinish: TNotifyEvent read FOnFinish write FOnFinish;
  end;

  TJMC_TreeViewManager = class(TObject)
  private
    FFileManager: TJMC_FileManager;
    FTreeView: TTreeView;
  private
    procedure CreateTreeViewFolder(const Folder: TJMC_Folder; const Parent: TTreeNode);
  public
    constructor Create(const TreeView: TTreeView);
    destructor Destroy; override;
  public
    procedure FillTreeView;
  public
    property FileManager: TJMC_FileManager read FFileManager;
    property TreeView: TTreeView read FTreeView;
  end;

function SwapPaths(const CurrentFileNamePath: string; const NewFileNamePath: string; const FileName: string): string;
function Search(const TreeView: TTreeView; const FileName: string): TTreeNode;
procedure TreeViewCollapsed(Sender: TObject; Node: TTreeNode);
procedure TreeViewExpanded(Sender: TObject; Node: TTreeNode);
procedure AssignShellIcons(const ImageList: TImageList);
function ExpandPath(const MasterFileName, SlaveFileName: string): string;
function SettingsFileName: string;

const
  JMC_ICON_FOLDERCLOSED: Integer = 0;
  JMC_ICON_FOLDEROPEN: Integer = 1;
  JMC_ICON_FILE: Integer = 2;
  JMC_ICONID_FOLDERCLOSED: Cardinal = 3;
  JMC_ICONID_FOLDEROPEN: Cardinal = 4;
  JMC_ICONID_FILE: Cardinal = 224;
  JMC_ICON_EXENAME: string = 'shell32.dll';

implementation

function SwapPaths(const CurrentFileNamePath: string; const NewFileNamePath: string; const FileName: string): string;
var
  P1: string;
  P2: string;
  S: string;
begin
  Result := '';
  P1 := IncludeTrailingPathDelimiter(CurrentFileNamePath);
  P2 := IncludeTrailingPathDelimiter(NewFileNamePath);
  if Pos(UpperCase(CurrentFileNamePath), UpperCase(FileName)) <= 0 then
    Exit;
  S := FileName;
  Delete(S, 1, Length(CurrentFileNamePath));
  S := P2 + S;
  Result := S;
end;

function Search(const TreeView: TTreeView; const FileName: string): TTreeNode;
var
  F: TJMC_File;
  N: TTreeNode;
begin
  Result := nil;
  if (TreeView.Items.Count = 0) or (FileExists(FileName) = False) then
    Exit;
  N := TreeView.Items.GetFirstNode;
  while N <> nil do
  begin
    Application.ProcessMessages;
    if Application.Terminated then
      Exit;
    if N.Data <> nil then
      if TObject(N.Data) is TJMC_File then
      begin
        F := TJMC_File(N.Data);
        if UpperCase(FileName) = UpperCase(F.FileName) then
        begin
          Result := N;
          Exit;
        end;
      end;
    N := N.GetNext;
  end;
end;

procedure TreeViewCollapsed(Sender: TObject; Node: TTreeNode);
begin
  Node.ImageIndex := JMC_ICON_FOLDERCLOSED;
  Node.SelectedIndex := JMC_ICON_FOLDERCLOSED;
end;

procedure TreeViewExpanded(Sender: TObject; Node: TTreeNode);
begin
  Node.ImageIndex := JMC_ICON_FOLDEROPEN;
  Node.SelectedIndex := JMC_ICON_FOLDEROPEN;
end;

procedure AssignShellIcons(const ImageList: TImageList);
var
  Buf: Graphics.TPicture;
  P1, P2: HICON;
begin
  Buf := Graphics.TPicture.Create;
  try
    if ExtractIconEx(PChar(JMC_ICON_EXENAME), JMC_ICONID_FOLDERCLOSED, P1, P2, 1) <> 1 then
    begin
      Buf.Icon.Handle := P2;
      ImageList.AddIcon(Buf.Icon);
    end;
    if ExtractIconEx(PChar(JMC_ICON_EXENAME), JMC_ICONID_FOLDEROPEN, P1, P2, 1) <> 1 then
    begin
      Buf.Icon.Handle := P2;
      ImageList.AddIcon(Buf.Icon);
    end;
    if ExtractIconEx(PChar(JMC_ICON_EXENAME), JMC_ICONID_FILE, P1, P2, 1) <> 1 then
    begin
      Buf.Icon.Handle := P2;
      ImageList.AddIcon(Buf.Icon);
    end;
  finally
    Buf.Free;
  end;
end;

function ExpandPath(const MasterFileName, SlaveFileName: string): string;
var
  S: string;
begin
  GetDir(0, S);
  try
    ChDir(ExtractFilePath(MasterFileName));
  except
    Result := '';
    Exit;
  end;
  Result := ExpandFilename(SlaveFileName);
  ChDir(S);
end;

function SettingsFileName: string;
begin
  Result := ChangeFileExt(ParamStr(0), '.ini');
end;

function AttrToJMCAttr(const Attr: Integer): TJMC_FileAttributes;
begin
  Result := [];
  if (Attr and faReadOnly) > 0 then
    Include(Result, JMC_faReadOnly);
  if (Attr and faHidden) > 0 then
    Include(Result, JMC_faHidden);
  if (Attr and faSysFile) > 0 then
    Include(Result, JMC_faSysFile);
  if (Attr and faVolumeID) > 0 then
    Include(Result, JMC_faVolumeID);
  if (Attr and faDirectory) > 0 then
    Include(Result, JMC_faDirectory);
  if (Attr and faArchive) > 0 then
    Include(Result, JMC_faArchive);
  if (Attr and faAnyFile) > 0 then
    Include(Result, JMC_faAnyFile);
end;

{ TJMC_File }

constructor TJMC_File.Create(const Folder: TJMC_Folder; const SearchRec: TSearchRec);
var
  i: Integer;
begin
  FFolder := Folder;
  FFileName := FFolder.Path + SearchRec.Name;
  FName := SearchRec.Name;
  for i := Length(FName) downto 1 do
    if FName[i] = '.' then
    begin
      FExtension := Copy(FName, i + 1, Length(FName) - i);
      FName := Copy(FName, 1, i - 1);
      Break;
    end;
  FSize := SearchRec.Size;
  FAttributes := AttrToJMCAttr(SearchRec.Attr);
  FTime := FileDateToDateTime(SearchRec.Time);
end;

{ TJMC_Folder }

procedure TJMC_Folder.Clear;
var
  i: Integer;
begin
  FParent := nil;
  FPath := '';
  FName := '';
  FAttributes := [];
  FTime := 0;
  FItems.Clear;
  for i := 0 to FileCount - 1 do
    Files[i].Free;
  FFiles.Clear;
  for i := 0 to ChildCount - 1 do
    Children[i].Free;
  FChildren.Clear;
end;

constructor TJMC_Folder.Create(const Parent: TJMC_Folder);
begin
  FParent := Parent;
  if FParent <> nil then
    FFileManager := FParent.FileManager;
  FFiles := TList.Create;
  FChildren := TList.Create;
  FItems := TList.Create;
end;

destructor TJMC_Folder.Destroy;
var
  i: Integer;
begin
  for i := 0 to FileCount - 1 do
    Files[i].Free;
  FFiles.Free;
  for i := 0 to ChildCount - 1 do
    Children[i].Free;
  FChildren.Free;
  FItems.Free;
  inherited;
end;

procedure TJMC_Folder.Fill(const SearchRec: TSearchRec);
var
  F: TSearchRec;
begin
  if FParent <> nil then
  begin
    FPath := FParent.Path + SearchRec.Name + '\';
    FName := SearchRec.Name;
    FAttributes := AttrToJMCAttr(SearchRec.Attr);
    FTime := FileDateToDateTime(SearchRec.Time);
  end
  else
  begin
    FName := FPath;
  end;
  FFileManager.CallAddFolder(Self);
  FillChar(F, SizeOf(F), #0);
  if FindFirst(FPath + '*', faAnyFile, F) = 0 then
    try
      repeat
        if FFileManager.Cancelled then
          Exit;
        if (F.Name = '.') or (F.Name = '..') then
          Continue;
        if DirectoryExists(FPath + F.Name) then
        begin
          FChildren.Add(TJMC_Folder.Create(Self));
          FItems.Add(Children[ChildCount - 1]);
          Children[ChildCount - 1].Fill(F);
        end
        else
        begin
          if FFileManager.AllowAddFile(FPath + F.Name) then
          begin
            FItems.Add(FFiles[FFiles.Add(TJMC_File.Create(Self, F))]);
            FFileManager.CallAddFile(Files[FileCount - 1]);
          end;
        end;
        Application.ProcessMessages;
        if Application.Terminated then
          Exit;
      until FindNext(F) <> 0;
    finally
      SysUtils.FindClose(F);
    end;
end;

function TJMC_Folder.GetChild(const Index: Integer): TJMC_Folder;
begin
  Result := FChildren[Index];
end;

function TJMC_Folder.GetChildCount: Integer;
begin
  Result := FChildren.Count;
end;

function TJMC_Folder.GetFile(const Index: Integer): TJMC_File;
begin
  Result := FFiles[Index];
end;

function TJMC_Folder.GetFileCount: Integer;
begin
  Result := FFiles.Count;
end;

function TJMC_Folder.GetItem(const Index: Integer): TObject;
begin
  Result := FItems[Index];
end;

function TJMC_Folder.GetItemCount: Integer;
begin
  Result := FItems.Count;
end;

function TJMC_Folder.GetItemName(const Index: Integer): string;
var
  Item: TObject;
begin
  Result := '';
  Item := Items[Index];
  if Item <> nil then
    if Item is TJMC_Folder then
      Result := TJMC_Folder(Item).Name
    else
      if Item is TJMC_File then
        Result := TJMC_File(Item).Name;
end;

function TJMC_Folder.ItemIndex(const Item: TObject): Integer;
var
  i: Integer;
begin
  for i := 0 to ItemCount - 1 do
    if Items[i] <> nil then
      if Items[i] = Item then
      begin
        Result := i;
        Exit;
      end;
  Result := -1;
end;

procedure TJMC_Folder.SetPath(const Value: string);
var
  F: SysUtils.TSearchRec;
begin
  Clear;
  if DirectoryExists(Value) then
  begin
    FPath := IncludeTrailingPathDelimiter(Value);
    FillChar(F, SizeOf(F), #0);
    if SysUtils.FindFirst(Value + '*', faAnyFile, F) = 0 then
    begin
      Fill(F);
      SysUtils.FindClose(F);
    end;
  end;
end;

{ TJMC_FileManager }

function TJMC_FileManager.AllowAddFile(const FileName: string): Boolean;
begin
  Result := True;
  if Assigned(FOnAllowAddFile) then
    Result := FOnAllowAddFile(FileName);
end;

procedure TJMC_FileManager.CallAddFile(const AddedFile: TJMC_File);
begin
  if Assigned(FOnAddFile) then
    FOnAddFile(Self, AddedFile);
end;

procedure TJMC_FileManager.CallAddFolder(const AddedFolder: TJMC_Folder);
begin
  if Assigned(FOnAddFolder) then
    FOnAddFolder(Self, AddedFolder);
end;

procedure TJMC_FileManager.Cancel;
begin
  FCancelled := True;
end;

procedure TJMC_FileManager.ClearRootFolders;
var
  i: Integer;
begin
  for i := 0 to RootFolderCount - 1 do
    RootFolders[i].Free;
  FRootFolders.Clear;
end;

constructor TJMC_FileManager.Create;
begin
  FPaths := TStringList.Create;
  FRootFolders := TList.Create;
end;

destructor TJMC_FileManager.Destroy;
var
  i: Integer;
begin
  for i := 0 to RootFolderCount - 1 do
    RootFolders[i].Free;
  FRootFolders.Free;
  FPaths.Free;
  inherited;
end;

function TJMC_FileManager.GetRootFolder(const Index: Integer): TJMC_Folder;
begin
  Result := FRootFolders[Index];
end;

function TJMC_FileManager.GetRootFolderCount: Integer;
begin
  Result := FRootFolders.Count;
end;

procedure TJMC_FileManager.Refresh;
var
  i: Integer;
begin
  FCancelled := False;
  ClearRootFolders;
  for i := 0 to FPaths.Count - 1 do
  begin
    FRootFolders.Add(TJMC_Folder.Create(nil));
    RootFolders[i].FileManager := Self;
    RootFolders[i].Path := FPaths[i];
  end;
  if Assigned(FOnFinish) then
    FOnFinish(Self);
end;

{ TJMC_TreeViewManager }

constructor TJMC_TreeViewManager.Create(const TreeView: TTreeView);
begin
  FTreeView := TreeView;
  FFileManager := TJMC_FileManager.Create;
end;

procedure TJMC_TreeViewManager.CreateTreeViewFolder(const Folder: TJMC_Folder; const Parent: TTreeNode);
var
  FolderNode, FileNode: TTreeNode;
  i: Integer;
begin
  if Parent <> nil then
    FolderNode := FTreeView.Items.AddChild(Parent, Folder.Name)
  else
    FolderNode := FTreeView.Items.Add(Parent, Folder.Name);
  FolderNode.Data := Folder;
  FolderNode.ImageIndex := JMC_ICON_FOLDERCLOSED;
  FolderNode.SelectedIndex := JMC_ICON_FOLDERCLOSED;
  for i := 0 to Folder.ChildCount - 1 do
    CreateTreeViewFolder(Folder.Children[i], FolderNode);
  for i := 0 to Folder.FileCount - 1 do
  begin
    Application.ProcessMessages;
    if Application.Terminated then
      Exit;
    if Length(Folder.Files[i].Extension) > 0 then
      FileNode := FTreeView.Items.AddChild(FolderNode, Folder.Files[i].Name + '.' + Folder.Files[i].Extension)
    else
      FileNode := FTreeView.Items.AddChild(FolderNode, Folder.Files[i].Name);
    FileNode.Data := Folder.Files[i];
    FileNode.ImageIndex := JMC_ICON_FILE;
    FileNode.SelectedIndex := JMC_ICON_FILE;
  end;
end;

destructor TJMC_TreeViewManager.Destroy;
begin
  FFileManager.Free;
  inherited;
end;

procedure TJMC_TreeViewManager.FillTreeView;
var
  i: Integer;
begin
  FTreeView.Items.BeginUpdate;
  FTreeView.Items.Clear;
  for i := 0 to FFileManager.RootFolderCount - 1 do
    CreateTreeViewFolder(FFileManager[i], nil);
  FTreeView.Items.EndUpdate;
end;

end.

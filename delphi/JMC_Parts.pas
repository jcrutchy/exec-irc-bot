unit JMC_Parts;

// gpl2
// by crutchy
// 17-may-2014

interface

uses
  SysUtils;

function ReadPart(const S: string; const Index: Integer; const Sep: Char): string; overload;
function ReadPart(const S: string; const Index: Integer; const Sep: string): string; overload;
function WritePart(var S: string; const Part: string; const Index: Integer; const Sep: Char): Boolean;
function CountParts(const S: string; const Sep: Char): Integer; overload;
function CountParts(const S: string; const Sep: string): Integer; overload;
function SwapParts(var S: string; const Index1, Index2: Integer; const Sep: Char): Boolean;

implementation

function ReadPart(const S: string; const Index: Integer; const Sep: Char): string;
var
  i, n: Integer;
begin
  Result := '';
  n := 1;
  for i := 1 to Length(S) do
  begin
    if S[i] = Sep then
      Inc(n);
    if n > Index then
      Break;
    if (n = Index) and (S[i] <> Sep) then
      Result := Result + S[i];
  end;
  Result := Trim(Result);
end;

function ReadPart(const S: string; const Index: Integer; const Sep: string): string;
var
  i, n: Integer;
begin
  Result := '';
  n := 1;
  i := 1;
  while i <= Length(S) do
  begin
    if Copy(S, i, Length(Sep)) = Sep then
    begin
      Inc(n);
      Inc(i, Length(Sep));
    end;
    if n > Index then
      Break;
    if n = Index then
      Result := Result + S[i];
    Inc(i);
  end;
  Result := Trim(Result);
end;

function WritePart(var S: string; const Part: string; const Index: Integer; const Sep: Char): Boolean;
var
  a, i, n: Integer;
begin
  Result := False;
  a := 0;
  n := 1;
  for i := 1 to Length(S) do
  begin
    if S[i] = Sep then
      Inc(n);
    if n = Index then
    begin
      a := i + 1;
      Break;
    end;
  end;
  if a > 0 then
  begin
    n := 0;
    for i := a to Length(S) do
      if S[i] <> Sep then
        Inc(n)
      else
        Break;
    Delete(S, a, n);
    Insert(Part, S, a);
    Result := True;
  end;
end;

function CountParts(const S: string; const Sep: Char): Integer;
var
  i: Integer;
begin
  if S = '' then
    Result := 0
  else
  begin
    Result := 1;
    for i := 1 to Length(S) do
      if S[i] = Sep then
        Inc(Result);
  end;
end;

function CountParts(const S: string; const Sep: string): Integer;
var
  i, n: Integer;
begin
  if S = '' then
  begin
    Result := 0;
    Exit;
  end;
  n := 1;
  i := 1;
  while i <= Length(S) do
  begin
    if Copy(S, i, Length(Sep)) = Sep then
    begin
      Inc(n);
      Inc(i, Length(Sep));
    end;
    Inc(i);
  end;
  Result := n;
end;

function SwapParts(var S: string; const Index1, Index2: Integer; const Sep: Char): Boolean;
var
  i, n, i1, i2, j1, j2: Integer;
  Part1, Part2, Backup: string;
begin
  Result := not (Index1 = Index2);
  if not Result then
    Exit;
  Backup := S;
  i1 := 0;
  i2 := 0;
  j1 := 0;
  j2 := 0;
  Part1 := '';
  Part2 := '';
  n := 1;
  i := 1;
  repeat
    if (n = Index1) and (i1 = 0) then
      i1 := i;
    if (n = Index1) and (j1 = 0) then
      if S[i] = Sep then
        j1 := i
      else
        if i = Length(S) then
          j1 := i + 1;
    if (n = Index2) and (i2 = 0) then
      i2 := i;
    if (n = Index2) and (j2 = 0) then
      if S[i] = Sep then
        j2 := i
      else
        if i = Length(S) then
          j2 := i + 1;
    if S[i] = Sep then
      Inc(n);
    Inc(i);
  until i > Length(S);
  Result := (i1 > 0) and (i2 > 0) and (j1 > 0) and (j2 > 0);
  if not Result then
    Exit;
  Part1 := Copy(S, i1, j1 - i1);
  Part2 := Copy(S, i2, j2 - i2);
  if i2 > i1 then
  begin
    Delete(S, i2, j2 - i2);
    Delete(S, i1, j1 - i1);
    Insert(Part2, S, i1);
    Insert(Part1, S, i2);
  end
  else
  begin
    Delete(S, i1, j1 - i1);
    Delete(S, i2, j2 - i2);
    Insert(Part1, S, i2);
    Insert(Part2, S, i1);
  end;
  Result := (Length(S) = Length(Backup)) and (i1 > 0) and (i2 > 0);
  if not Result then
    S := Backup;
end;

end.

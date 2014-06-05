object MainForm: TMainForm
  Left = 347
  Top = 240
  HorzScrollBar.Visible = False
  VertScrollBar.Visible = False
  AutoSize = True
  BorderStyle = bsDialog
  Caption = 'Pathfinding'
  ClientHeight = 231
  ClientWidth = 295
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'MS Sans Serif'
  Font.Style = []
  OldCreateOrder = False
  OnCreate = FormCreate
  OnDestroy = FormDestroy
  PixelsPerInch = 96
  TextHeight = 13
  object MapImage: TImage
    Left = 0
    Top = 0
    Width = 295
    Height = 231
    AutoSize = True
    OnMouseDown = MapImageMouseDown
  end
end

object FormMain: TFormMain
  Left = 334
  Top = 178
  Width = 800
  Height = 500
  Caption = 'exec-irc-bot'
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'Courier New'
  Font.Style = []
  OldCreateOrder = False
  OnCreate = FormCreate
  OnDestroy = FormDestroy
  PixelsPerInch = 96
  TextHeight = 14
  object MemoData: TMemo
    Left = 172
    Top = 0
    Width = 620
    Height = 473
    Align = alClient
    ParentColor = True
    ReadOnly = True
    ScrollBars = ssBoth
    TabOrder = 0
    WordWrap = False
  end
  object Panel1: TPanel
    Left = 0
    Top = 0
    Width = 172
    Height = 473
    Align = alLeft
    BevelOuter = bvNone
    TabOrder = 1
  end
end

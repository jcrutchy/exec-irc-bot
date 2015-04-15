object FormMain: TFormMain
  Left = 353
  Top = 157
  Width = 800
  Height = 500
  Caption = 'execstat'
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'Courier New'
  Font.Style = []
  Menu = MainMenu
  OldCreateOrder = False
  OnCreate = FormCreate
  PixelsPerInch = 96
  TextHeight = 14
  object LabelMessage: TLabel
    Left = 0
    Top = 421
    Width = 792
    Height = 14
    Align = alBottom
  end
  object Splitter1: TSplitter
    Left = 0
    Top = 154
    Width = 792
    Height = 3
    Cursor = crVSplit
    Align = alTop
    AutoSnap = False
    MinSize = 100
  end
  object StatusBar1: TStatusBar
    Left = 0
    Top = 435
    Width = 792
    Height = 19
    Panels = <
      item
        Width = 80
      end
      item
        Width = 120
      end
      item
        Width = 70
      end
      item
        Text = '0 errors'
        Width = 80
      end
      item
        Width = 150
      end
      item
        Width = 120
      end
      item
        Width = 120
      end>
  end
  object ProgressBar1: TProgressBar
    Left = 0
    Top = 405
    Width = 792
    Height = 16
    Align = alBottom
    Step = 1
    TabOrder = 1
  end
  object MemoTraffic: TMemo
    Left = 0
    Top = 157
    Width = 792
    Height = 144
    Align = alClient
    Color = clBtnFace
    ReadOnly = True
    ScrollBars = ssBoth
    TabOrder = 2
    WordWrap = False
  end
  object Panel1: TPanel
    Left = 0
    Top = 0
    Width = 792
    Height = 154
    Align = alTop
    BevelOuter = bvNone
    TabOrder = 3
    object Splitter2: TSplitter
      Left = 142
      Top = 0
      Height = 154
      AutoSnap = False
      MinSize = 100
    end
    object Splitter3: TSplitter
      Left = 272
      Top = 0
      Height = 154
      AutoSnap = False
      MinSize = 100
    end
    object ListBoxBuckets: TListBox
      Left = 0
      Top = 0
      Width = 142
      Height = 154
      Align = alLeft
      ItemHeight = 14
      TabOrder = 0
    end
    object ListBoxAliases: TListBox
      Left = 145
      Top = 0
      Width = 127
      Height = 154
      Align = alLeft
      ItemHeight = 14
      TabOrder = 1
    end
    object ListBoxHandles: TListBox
      Left = 275
      Top = 0
      Width = 152
      Height = 154
      Align = alLeft
      ItemHeight = 14
      TabOrder = 2
    end
  end
  object Panel2: TPanel
    Left = 0
    Top = 301
    Width = 792
    Height = 104
    Align = alBottom
    BevelOuter = bvNone
    TabOrder = 4
    object LabeledEditAliasesDest: TLabeledEdit
      Left = 15
      Top = 62
      Width = 157
      Height = 22
      EditLabel.Width = 147
      EditLabel.Height = 14
      EditLabel.Caption = 'target channel / nick'
      TabOrder = 0
      Text = '#journals'
    end
    object LabeledEditAliasesTrailing: TLabeledEdit
      Left = 185
      Top = 62
      Width = 270
      Height = 22
      EditLabel.Width = 56
      EditLabel.Height = 14
      EditLabel.Caption = 'trailing'
      TabOrder = 1
      Text = '~say execstat test'
    end
    object ButtonSend: TButton
      Left = 471
      Top = 59
      Width = 75
      Height = 25
      Caption = 'Send'
      TabOrder = 2
      OnClick = ButtonSendClick
    end
    object Button3: TButton
      Left = 614
      Top = 29
      Width = 81
      Height = 25
      Caption = 'DISCONNECT'
      TabOrder = 3
      OnClick = Button3Click
    end
    object ButtonRunTests: TButton
      Left = 682
      Top = 66
      Width = 75
      Height = 25
      Caption = 'RUN TESTS'
      TabOrder = 4
      OnClick = ButtonRunTestsClick
    end
    object Button1: TButton
      Left = 592
      Top = 65
      Width = 75
      Height = 25
      Caption = 'Button1'
      TabOrder = 5
      OnClick = Button1Click
    end
  end
  object Timer1: TTimer
    Enabled = False
    Interval = 50
    OnTimer = Timer1Timer
    Left = 742
    Top = 11
  end
  object MainMenu: TMainMenu
    Left = 688
    Top = 22
    object MenuFile: TMenuItem
      Caption = '&File'
      object MenuItemExit: TMenuItem
        Caption = 'E&xit'
      end
    end
  end
end

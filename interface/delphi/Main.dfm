object FormMain: TFormMain
  Left = 382
  Top = 215
  Width = 709
  Height = 383
  Caption = 'execstat'
  Color = clBtnFace
  Font.Charset = DEFAULT_CHARSET
  Font.Color = clWindowText
  Font.Height = -11
  Font.Name = 'Courier New'
  Font.Style = []
  OldCreateOrder = False
  OnCreate = FormCreate
  PixelsPerInch = 96
  TextHeight = 14
  object PageControl1: TPageControl
    Left = 0
    Top = 0
    Width = 701
    Height = 321
    ActivePage = TabSheet3
    Align = alClient
    TabOrder = 0
    object TabSheet1: TTabSheet
      Caption = 'processes'
      object ListBoxAliases: TListBox
        Left = 0
        Top = 0
        Width = 157
        Height = 292
        Align = alLeft
        ItemHeight = 14
        TabOrder = 0
      end
    end
    object TabSheet2: TTabSheet
      Caption = 'buckets'
      ImageIndex = 1
      object ListBoxBuckets: TListBox
        Left = 0
        Top = 0
        Width = 195
        Height = 292
        Align = alLeft
        ItemHeight = 14
        TabOrder = 0
      end
    end
    object TabSheet3: TTabSheet
      Caption = 'aliases'
      ImageIndex = 2
      object Splitter2: TSplitter
        Left = 127
        Top = 0
        Height = 292
        AutoSnap = False
        MinSize = 100
      end
      object ListBoxExec: TListBox
        Left = 0
        Top = 0
        Width = 127
        Height = 292
        Align = alLeft
        ItemHeight = 14
        TabOrder = 0
      end
      object Panel2: TPanel
        Left = 130
        Top = 0
        Width = 563
        Height = 292
        Align = alClient
        BevelOuter = bvNone
        TabOrder = 1
        object ButtonSend: TButton
          Left = 478
          Top = 251
          Width = 75
          Height = 25
          Caption = 'Send'
          TabOrder = 0
          OnClick = ButtonSendClick
        end
        object LabeledEditAliasesTrailing: TLabeledEdit
          Left = 192
          Top = 253
          Width = 270
          Height = 22
          EditLabel.Width = 56
          EditLabel.Height = 14
          EditLabel.Caption = 'trailing'
          TabOrder = 1
          Text = '~say execstat test'
        end
        object LabeledEditAliasesDest: TLabeledEdit
          Left = 22
          Top = 253
          Width = 157
          Height = 22
          EditLabel.Width = 147
          EditLabel.Height = 14
          EditLabel.Caption = 'target channel / nick'
          TabOrder = 2
          Text = '#journals'
        end
      end
    end
    object TabSheet4: TTabSheet
      Caption = 'channels'
      ImageIndex = 3
    end
    object TabSheet5: TTabSheet
      Caption = 'nicks'
      ImageIndex = 4
    end
    object TabSheet6: TTabSheet
      Caption = 'messages'
      ImageIndex = 5
      object Memo1: TMemo
        Left = 0
        Top = 0
        Width = 693
        Height = 292
        Align = alClient
        Color = clBtnFace
        ReadOnly = True
        ScrollBars = ssBoth
        TabOrder = 0
        WordWrap = False
      end
      object Button3: TButton
        Left = 572
        Top = 43
        Width = 81
        Height = 25
        Caption = 'DISCONNECT'
        TabOrder = 1
        OnClick = Button3Click
      end
      object ButtonRunTests: TButton
        Left = 576
        Top = 80
        Width = 75
        Height = 25
        Caption = 'RUN TESTS'
        TabOrder = 2
        OnClick = ButtonRunTestsClick
      end
    end
  end
  object StatusBar1: TStatusBar
    Left = 0
    Top = 337
    Width = 701
    Height = 19
    Panels = <
      item
        Width = 70
      end
      item
        Width = 90
      end
      item
        Width = 60
      end
      item
        Width = 500
      end>
  end
  object ProgressBar1: TProgressBar
    Left = 0
    Top = 321
    Width = 701
    Height = 16
    Align = alBottom
    Step = 1
    TabOrder = 2
  end
  object Timer1: TTimer
    Enabled = False
    Interval = 50
    OnTimer = Timer1Timer
    Left = 641
    Top = 38
  end
  object Timer2: TTimer
    Enabled = False
    Interval = 3000
    OnTimer = Timer2Timer
    Left = 642
    Top = 94
  end
end

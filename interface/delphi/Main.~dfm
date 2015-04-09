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
  OnDestroy = FormDestroy
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
      object Panel1: TPanel
        Left = 0
        Top = 0
        Width = 693
        Height = 82
        Align = alTop
        TabOrder = 0
        object Label1: TLabel
          Left = 6
          Top = 6
          Width = 133
          Height = 14
          Caption = '~alias %%trailing%%'
          Font.Charset = DEFAULT_CHARSET
          Font.Color = clWindowText
          Font.Height = -11
          Font.Name = 'Courier New'
          Font.Style = []
          ParentFont = False
        end
        object Label2: TLabel
          Left = 6
          Top = 22
          Width = 91
          Height = 14
          Caption = 'nick: crutchy'
        end
        object Label3: TLabel
          Left = 5
          Top = 36
          Width = 119
          Height = 14
          Caption = 'channel: #Soylent'
        end
        object Label4: TLabel
          Left = 5
          Top = 52
          Width = 140
          Height = 14
          Caption = 'server: irc.sylnt.us'
        end
        object Label5: TLabel
          Left = 3
          Top = 66
          Width = 196
          Height = 14
          Caption = 'started: 2015-04-08 23:52:48'
        end
        object Button1: TButton
          Left = 609
          Top = 49
          Width = 75
          Height = 25
          Caption = 'KILL'
          TabOrder = 0
        end
      end
    end
    object TabSheet2: TTabSheet
      Caption = 'buckets'
      ImageIndex = 1
      object Splitter1: TSplitter
        Left = 119
        Top = 0
        Height = 292
        AutoSnap = False
        MinSize = 100
      end
      object ListBox1: TListBox
        Left = 0
        Top = 0
        Width = 119
        Height = 292
        Align = alLeft
        ItemHeight = 14
        Items.Strings = (
          'bucket1'
          'bucket2')
        TabOrder = 0
      end
      object TreeView1: TTreeView
        Left = 122
        Top = 0
        Width = 571
        Height = 292
        Align = alClient
        Indent = 19
        TabOrder = 1
        Items.Data = {
          02000000200000000000000000000000FFFFFFFFFFFFFFFF0000000001000000
          076C6576656C2031200000000000000000000000FFFFFFFFFFFFFFFF00000000
          00000000076C6576656C2032200000000000000000000000FFFFFFFFFFFFFFFF
          0000000001000000076C6576656C2031200000000000000000000000FFFFFFFF
          FFFFFFFF0000000000000000076C6576656C2032}
      end
    end
    object TabSheet3: TTabSheet
      Caption = 'aliases'
      ImageIndex = 2
      object Splitter2: TSplitter
        Left = 121
        Top = 0
        Height = 292
        AutoSnap = False
        MinSize = 100
      end
      object ListBox2: TListBox
        Left = 0
        Top = 0
        Width = 121
        Height = 292
        Align = alLeft
        ItemHeight = 14
        TabOrder = 0
      end
      object Panel2: TPanel
        Left = 124
        Top = 0
        Width = 569
        Height = 292
        Align = alClient
        BevelOuter = bvNone
        TabOrder = 1
        object Button2: TButton
          Left = 478
          Top = 251
          Width = 75
          Height = 25
          Caption = 'EXECUTE'
          TabOrder = 0
          OnClick = Button2Click
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
    end
  end
  object StatusBar1: TStatusBar
    Left = 0
    Top = 337
    Width = 701
    Height = 19
    Panels = <
      item
        Width = 50
      end
      item
        Width = 70
      end
      item
        Width = 50
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
    Left = 366
    Top = 82
  end
end

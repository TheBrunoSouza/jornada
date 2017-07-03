<?
require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();

$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

$data = explode('-', $_REQUEST['dtConf']);
$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
?>

<script>

    Ext.onReady(function() {
        Ext.define('Deslocamento', {
            extend: 'Ext.data.Model',
            requires : 'Ext.data.Model',
            fields:[
                {name: 'idRelatorio', type: 'string'},
                {name: 'hora_ini', type: 'string', dateFormat: 'H:i'},
                {name: 'hora_fim', type: 'string', dateFormat: 'H:i'},
                {name: 'tempo', type: 'string', dateFormat: 'H:i'},
                {name: 'placa', type: 'string'},
                {name: 'situacao', type: 'string'},
                {name: 'empresa', type: 'string'},
                {name: 'condutor', type: 'string'}
            ]
        });

        var diaMes      = '<?=$data[0]?>',
            diaSemana   = '<?=$data[1]?>';

        if(diaSemana == ' S'){diaSemana = 'Sábado';}

        if(diaSemana == ' Ter'){diaSemana = 'Terça';}

        var dt = Ext.toolbar.TextItem({
            xtype: 'tbtext',
            text: diaMes + '- ' + diaSemana
        });

        var nome = Ext.toolbar.TextItem({
            xtype: 'tbtext',
            text: '<?=$_REQUEST['nomeCon']?>'
        });

        var idCondutor  = '<?=$_REQUEST['idCondutor']?>',
            dtIni = '<?=$_REQUEST['dtIni']?>',
            dtFim = '<?=$_REQUEST['dtFim']?>',
            idEmpresa = '<?=$_REQUEST['idEmpresa']?>',
            nomeCondutor = '<?=$_REQUEST['nomeCon']?>';

        var toolInfo = Ext.create('Ext.toolbar.Toolbar', {
            id: 'toolbarInfo',
            region: 'north',
            items: [
                {
                    xtype: 'button',
                    text: 'Voltar',
                    style: 'margin-top: 8px;',
                    icon: 'imagens/16x16/arrow_left.png',
                    handler: function() {
                        panelLoad('gestorTabId', 'Cartão Ponto', 'relatorios/relCartaoPonto.php', 'idCondutor='+idCondutor+'&dataIni='+dtIni+'&dataFim='+dtFim+'&idEmpresa='+idEmpresa+'&nmCondutor='+nomeCondutor);
                    }
                },
                'Data:', {xtype: dt},
                '-',
                'Condutor:', {xtype: nome},
                '->',
                {
                    xtype: 'button',
                    text: 'Movimentações',
                    iconCls: 'pdf',
                    handler: function() {
                        var color;

                        var tabela      = '',
                            tabelaJust  = '',
                            tabelaAss   = '',
                            td100       = '',
                            tot100      = '';

                        var flagJust    = 0,
                            situacao    = 0,
                            HoraIni     = 0,
                            HoraFim     = 0,
                            tempo       = 0;

                        storeRel.each( function (model) {
                            situacao    += model.get('situacao');
                            HoraIni     += model.get('hora_ini');
                            HoraFim     += model.get('hora_fim');
                            tempo       += model.get('tempo');

                            tabela += '<tr bgcolor="'+color+'"><td>'+ model.get('situacao')+'</td><td>'+ model.get('hora_ini') +'</td><td>'+ model.get('hora_fim')+'</td><td>'+ model.get('tempo') +'</td></tr>';

                            color = (color=='')?"#E1EEF4":'';
                        });

                        tabela += '<tr bgcolor="'+color+'"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';

                        color = '';

                        Ext.getDom('pontoContent').value = tabela;
                        Ext.getDom('formIdEmpresa').value = idEmpresa;
                        Ext.getDom('formIdCondutor').value = Ext.getCmp('idCondutor').value;
                        Ext.getDom('formNmCondutor').value = Ext.getCmp('idCondutor').rawValue;
                        Ext.getDom('formDtIni').value = diaMes;
                        Ext.getDom('formExportPrint').action = 'pdf/pdfMovimentacoes.php';

                        Ext.getDom('formExportPrint').submit();
//                    console.info(tabelaJust);	
                    }
                }
            ]
        });

        var storeRel = Ext.create('Ext.data.Store', {
            model: 'Deslocamento',
            autoLoad : false,
            proxy: {
                type : 'ajax',
                url  : 'json/jsonConsCartao.php',
                reader: {
                    type: 'json',
                    root: 'Relatorio'
                },
                extraParams: {
                    idCondutor: '<?=$_REQUEST['idCondutor']?>',
                    data: '<?=$_REQUEST['data']?>'
                }
            }
        });

        var grid = Ext.create('Ext.grid.Panel', {
            tbar: toolInfo,
            store: storeRel,
            id: 'gridRelDesloc',
            forceFit: true,
            viewConfig: {
                emptyText: '<b>Não há movimentações para este dia</b>',
                deferEmptyText: false
            },
            height: Ext.getBody().getHeight()-57,
            columns: [
                {
                    header: 'Situação',
                    dataIndex: 'situacao'
                },{
                    header: 'Hora Início',
                    dataIndex: 'hora_ini'
                },{
                    header: 'Hora Fim',
                    dataIndex: 'hora_fim'
                },{
                    header: 'Tempo',
                    dataIndex: 'tempo'
                }
            ],
            renderTo: 'contentId',
            forceFit: true
        });
        storeRel.load();
    });
</script>

<div id="contentId"></div>
<div id="gestorRelId" style="width: 100%;"></div>

<form id="formExportPrint" method="post" action="pdf/pdfMovimentacoes.php" target="print">
    <input type="hidden" id="pontoContent"  name="pontoContent" value=""/>
    <input type="hidden" id="formIdEmpresa" name="formIdEmpresa" value=""/>
    <input type="hidden" id="formIdCondutor" name="formIdCondutor" value=""/>
    <input type="hidden" id="formNmCondutor" name="formNmCondutor" value=""/>
    <input type="hidden" id="formDtIni" name="formDtIni" value=""/>
</form>
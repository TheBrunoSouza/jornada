<?
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo       = new OracleCielo();
    $conexao        = $OraCielo->getCon();
    $CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $empExtra100    = $CtrlAcesso->getExtra100($_SESSION, $conexao);

    if(isset($_SESSION)){
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    }else{
        header('Location: http://jornada.cielo.ind.br');
    }

    if(!$empresaUsu) {
        $empresaUsu = $_REQUEST['idEmpresa'];
    }
?>

<script>

    var idCondutor      = '<?=$_REQUEST['idCondutor']?>',
        dtIni           = '<?=$_REQUEST['dataIni']?>',
        dtFim           = '<?=$_REQUEST['dataFim']?>',
        idEmpresa       = '<?=$_REQUEST['idEmpresa']?>',
        nomeCondutor    = '<?=$_REQUEST['nomeCon']?>';

    Ext.define('modelJustPonto', {
        extend: 'Ext.data.Model',
        requires: 'Ext.data.Model',
        fields:[
            {name: 'dataIni', type: 'string'},
            {name: 'dataAlteracao', type: 'string'},
            {name: 'descricao', type: 'string'},
            {name: 'nomeUsuario', type: 'string'}
        ]
    });

    var storeJustifPonto = Ext.create('Ext.data.Store', {
        storeId: 'JustPontoStore',
        autoLoad: true,
        model: 'modelJustPonto',
        proxy: {
            type: 'ajax',
            url: 'json/jsonJustPontoAlt.php',
            reader: {
                type: 'json',
                root: 'logJornada'
            },
            extraParams: {
                dtIni: dtIni,
                dtFim: dtFim,
                idCondutor: '<?=$_REQUEST['idCondutor']?>'
            }
        }
    });

    Ext.define('modelCartaoPontoResumido', {
        extend: 'Ext.data.Model',
        requires: 'Ext.data.Model',
        fields:[
            {name: 'idCondutor', type: 'int'},
            {name: 'nmCondutor', type: 'string'},
            {name: 'diarioDt', type: 'string'},
            {name: 'Data', type: 'string'},
            {name: 'afastamento', type: 'string'},
            {name: 'totalJornada', type: 'string'},
            {name: 'totalExtra', type: 'string'},
            {name: 'totalExtra100', type: 'string'},
            {name: 'totalEspera', type: 'string'},
            {name: 'totalRefeicao', type: 'string'},
            {name: 'totalDescanso', type: 'string'},
            {name: 'totalRepouso', type: 'string'},
            {name: 'totalNoturna', type: 'string'}
        ]
    });

    var storeCartaoResumido = Ext.create('Ext.data.Store', {
        storeId: 'cartaPontoStore',
        autoLoad: true,
        model: 'modelCartaoPontoResumido',
        proxy: {
            type: 'ajax',
            url: 'json/jsonCartaoResumido.php',
            reader: {
                type: 'json',
                root: 'cartaoPonto'
            },
            extraParams: {
                idEmpresa: '<?=($empresaUsu == '' or $empresaUsu == '')?$_REQUEST['idEmpresa']:$empresaUsu?>',
                idCondutor: '<?=$_REQUEST['idCondutor']?>',
                dataIni: '<?=$_REQUEST['dataIni']?>',
                dataFim: '<?=$_REQUEST['dataFim']?>'
            },
        }
    });


    var buttonVoltar = Ext.create('Ext.Button', {
        xtype: 'button',
        text: 'Voltar',
        style: 'margin-top: 8px;',
        icon: 'imagens/16x16/arrow_left.png',
        handler: function() {
            panelLoad(
                'gestorTabId',
                'Cartão Ponto',
                'relatorios/relCartaoPonto.php',
                'idCondutor='+idCondutor+
                '&dataIni='+dtIni+
                '&dataFim='+dtFim+
                '&idEmpresa='+idEmpresa+
                '&nmCondutor='+nomeCondutor
            );
        }
    });

    var nome = Ext.toolbar.TextItem({
        xtype: 'tbtext',
        text: '<?=$_REQUEST['nomeCondutor']?>'
    });

    var totais = Ext.toolbar.TextItem({
        xtype: 'tbtext',
        text: '<?=$_REQUEST['totais']?>'
    });

    var toolbar = Ext.create('Ext.toolbar.Toolbar', {
        id: 'toolbarRelId',
        region: 'north',
        items: [
            {xtype: buttonVoltar},
            'Condutor:', {xtype: nome}
        ]
    });

    var buttonPdf = Ext.create('Ext.Button', {
        text: 'Exportar para PDF',
        iconCls: 'pdf',
        style: 'margin-top: 8px;',
        handler: function() {
            var color = '#E1EEF4';

            var tabela,
                tabelaTotais,
                tabelaJust,
                totalJornada    = 0,
                totalExtra      = 0,
                totalExtra100   = 0,
                totalEspera     = 0,
                totalRefeicao   = 0,
                totalDescanso   = 0,
                totalRepouso    = 0,
                totalNoturna    = 0,
                flagJust        = 0;

            var colorRefeicao   = '',
                colorExtra      = '',
                colorExtra100   = '',
                colorNoturna    = '';

            tabela =
                '<br/>' +
                '<font size="7">' +
                    '<table id="total" cellpadding="4" cellspacing="2" style="text-align:left;">' +
                        '<thead>' +
                            '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                                '<th>Data</th>' +
                                '<th>Jornada</th>' +
                                '<th>Extra</th>' +
                                '<th>Extra100%</th>' +
                                '<th>Espera</th>' +
                                '<th>Refei&ccedil;&atilde;o</th>' +
                                '<th>Descanso</th>' +
                                '<th>Repouso</th>' +
                                '<th>Noturna</th>' +
                            '</tr>' +
                        '</thead>';

            storeCartaoResumido.each( function (rec) {

                //Veriricando se for um afastamento para colocar a descricao do mesmo no lugar do total
                //Se for afastamento, em todos os totalizadores chegam a descricao do afastamento, portanto utilizar 'totalJornada' abaixo fica correto
                if(rec.get('afastamento') == 'T'){
                    tabela +=
                        '<tr bgcolor="'+color+'">' +
                        '<td>'+ rec.get('Data')+'</td>' +
                        '<td>'+ rec.get('totalJornada') +'</td>' +
                        '<td>'+ rec.get('totalJornada') +'</td>' +
                        '<td>'+ rec.get('totalJornada') +'</td>' +
                        '<td>'+ rec.get('totalJornada') +'</td>' +
                        '<td>'+ rec.get('totalJornada') +'</td>' +
                        '<td>'+ rec.get('totalJornada') +'</td>' +
                        '<td>'+ rec.get('totalJornada') +'</td>' +
                        '<td>'+ rec.get('totalJornada') +'</td>' +
                        '</tr>';
                }else{
                    colorRefeicao   = '';
                    colorExtra      = '';
                    colorExtra100   = '';
                    colorNoturna    = '';

                    if(rec.get('totalRefeicao') < 60){if(rec.get('totalJornada') > 0 || rec.get('totalExtra') > 0 || rec.get('totalExtra100') > 0){colorRefeicao = "#ff4c39";}}

                    if(rec.get('totalExtra') > 0){colorExtra = "#91c8f8";}

                    if(rec.get('totalExtra100') > 0){colorExtra100 = "#91c8f8";}

                    if(rec.get('totalNoturna') > 0){colorNoturna = "#91c8f8";}

                    tabela +=
                        '<tr bgcolor="'+color+'">' +
                            '<td>' + rec.get('Data') + '</td>' +
                            '<td>(' + rectime(rec.get('totalJornada')) + ')</td>' +
                            '<td bgcolor="'+colorExtra+'">(' + rectime(rec.get('totalExtra')) + ')</td>' +
                            '<td bgcolor="'+colorExtra100+'">(' + rectime(rec.get('totalExtra100')) + ')</td>' +
                            '<td>(' + rectime(rec.get('totalEspera')) + ')</td>' +
                            '<td bgcolor="'+colorRefeicao+'">(' + rectime(rec.get('totalRefeicao')) + ')</td>' +
                            '<td>(' + rectime(rec.get('totalDescanso')) + ')</td>' +
                            '<td>(' + rectime(rec.get('totalRepouso')) + ')</td>' +
                            '<td bgcolor="'+colorNoturna+'">(' + rectime(rec.get('totalNoturna')) + ')</td>' +
                        '</tr>';
                }
                color = (color == '')?"#E1EEF4":'';
            });

            tabela += '<tbody></tbody></table></font>';

            //tabela += '<tr bgcolor="'+color+'"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';

            tabelaTotais =
                '<br/>' +
                '<font size="7">' +
                    '<table id="total" cellpadding="2" cellspacing="2" style="text-align:center;">' +
                        '<thead>' +
                            '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                                '<th colspan="8">Totais</th>' +
                            '</tr>' +
                            '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                                '<th>Jornada</th>' +
                                '<th>Hora Extra</th>' +
                                '<th>Horas Extra 100 (%)</th>' +
                                '<th>Espera</th>' +
                                '<th>Noturna</th>' +
                                '<th>Atestado</th>' +
                                '<th>Ferias</th>' +
                                '<th>Afastadas</th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tr>' +
                            '<td>('+rectime(<?=$_REQUEST['totalJornada']?>)+')</td>' +
                            '<td>('+rectime(<?=$_REQUEST['totalExtra']?>)+')</td>' +
                            '<td>('+rectime(<?=$_REQUEST['totalExtra100']?>)+')</td>' +
                            '<td>('+rectime(<?=$_REQUEST['totalEspera']?>)+')</td>' +
                            '<td>('+rectime(<?=($_REQUEST['totalNoturna'] == ''?0:$_REQUEST['totalNoturna'])?>)+')</td>' +
                            '<td>('+rectime(<?=($_REQUEST['totalAtestado'] == ''?0:$_REQUEST['totalAtestado'])?>)+')</td>' +
                            '<td>('+rectime(<?=($_REQUEST['totalFerias'] == ''?0:$_REQUEST['totalFerias'])?>)+')</td>' +
                            '<td>('+rectime(<?=($_REQUEST['totalAfastadas'] == ''?0:$_REQUEST['totalAfastadas'])?>)+')</td>' +
                        '</tr>' +
                        '<tbody></tbody>' +
                    '</table>' +
                '</font>';

            //justificativa de alterações
            tabelaJust = '<br/><font size="7">' +
                '<table id="justificativas" cellpadding="2" cellspacing="2" style="text-align:center;">' +
                    '<thead>' +
                        '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                            '<th colspan="4">Alterações/Justificativas</th>' +
                        '</tr>' +
                        '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                            '<th>Data</th>' +
                            '<th>Data Alteração</th>' +
                            '<th>Usuario</th>' +
                            '<th>Justificativa</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody>';

            color = '';

            storeJustifPonto.each( function (modelJust) {
                tabelaJust +=
                    '<tr bgcolor="'+color+'">' +
                        '<td>'+modelJust.get('dataIni')+'</td>' +
                        '<td>'+modelJust.get('dataAlteracao')+'</td>' +
                        '<td>'+modelJust.get('nomeUsuario')+'</td>' +
                        '<td style="text-align:left;">'+modelJust.get('descricao')+'</td>' +
                    '</tr>';

                color = (color == '') ?  "#E1EEF4" : '';
                flagJust = 1;
            });

            tabelaJust+='</tbody></table></font>';

            //controla exbição das justificativas
            tabelaJust = (flagJust == 1) ? tabelaJust : '';

            Ext.getDom('pontoContent').value            = tabela;
            Ext.getDom('tableTotais').value             = tabelaTotais;
            Ext.getDom('tableJustificativa').value      = tabelaJust;
            Ext.getDom('formIdEmpresaHidden').value     = Ext.getCmp('idEmpresaHidden').value;
            Ext.getDom('formIdCondutor').value          = Ext.getCmp('idCondutor').value;
            Ext.getDom('formNmCondutor').value          = Ext.getCmp('idCondutor').rawValue;
            Ext.getDom('formDtIni').value               = Ext.Date.format(Ext.getCmp('idDateIni').value, 'Y-m-d');
            Ext.getDom('formDtFim').value               = Ext.Date.format(Ext.getCmp('idDateFim').value, 'Y-m-d');
            Ext.getDom('formExportPrint').action        = 'pdf/pdfCartaoPontoResumido.php';
            Ext.getDom('formExportPrint').submit();
        }
    });

    Ext.onReady(function () {

        var he100 = ('<?=$empExtra100?>' == 'true') ? false : true;

        var grid = Ext.create('Ext.grid.Panel', {
            tbar: toolbar,
            id: 'gridCartaoId',
            forceFit: true,
            store: storeCartaoResumido,
            height: Ext.getBody().getHeight()-59,
            autoWidth: true,
            renderTo: 'gestorRelId',
            viewConfig: {
                emptyText: '<b>Nenhum registro de jornada encontrado para este filtro</b>',
                deferEmptyText: false,
                getRowClass: function(record, rowIndex, rowParams, store) {
                    if(record.get('afastamento') == "T"){
                        return 'afastamento';
                    }else{
                        return 'gridPointerRow';
                    }
                }
            },
            columns: [
                {text: 'Data',  dataIndex: 'Data'},
                {
                    text: 'Jornada', dataIndex: 'totalJornada', menuDisabled: true, tdCls: 'wrap',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('afastamento') == "T"){
                            return "<div value='' id='jornada_" + row + "'>"+record.get('totalJornada')+"</div>";
                        }else{
                            return "("+rectime(record.get('totalJornada'))+")";
                        }
                    }
                },
                {text: 'Hora Extra', dataIndex: 'totalExtra', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('afastamento') == "T"){
                            return "<div value='' id='extra_" + row + "'>"+record.get('totalExtra')+"</div>";
                        }else{
                            var colorRefeicao = '';
                            if(record.get('totalExtra') > 0){
                                colorRefeicao = "#3338ff";
                            }
                            return "<span style='color: "+colorRefeicao+";'>("+rectime(record.get('totalExtra'))+")</span>";
                        }
                    }
                },
                {text: 'Extra 100%', dataIndex: 'totalExtra100', menuDisabled: true, hidden: he100,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('afastamento') == "T"){
                            return "<div value='' id='extra100_" + row + "'>"+record.get('totalExtra100')+"</div>";
                        }else{
                            var colorRefeicao = '';
                            if(record.get('totalExtra100') > 0){
                                colorRefeicao = "#3338ff";
                            }
                            return "<span style='color: "+colorRefeicao+";'>("+rectime(record.get('totalExtra100'))+")</span>";
                        }
                    }
                },
                {text: 'Espera',  dataIndex: 'totalEspera', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('afastamento') == "T"){
                            return "<div value='' id='espera_" + row + "'>"+record.get('totalEspera')+"</div>";
                        }else{
                            return "("+rectime(record.get('totalEspera'))+")";
                        }
                    }
                },
                {text: 'Refeição', dataIndex: 'totalRefeicao', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('afastamento') == "T"){
                            return "<div value='' id='refeicao_" + row + "'>"+record.get('totalRefeicao')+"</div>";
                        }else{
                            var colorRefeicao = '';
                            if(record.get('totalRefeicao') < 60){
                                if(record.get('totalJornada') > 0 || record.get('totalExtra') > 0 || record.get('totalExtra100') > 0){
                                    colorRefeicao = "#f83223";
                                }
                            }
                            return "<span style='color: "+colorRefeicao+";'>("+rectime(record.get('totalRefeicao'))+")</span>";
                        }
                    }
                },
                {text: 'Descanso',  dataIndex: 'totalDescanso', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('afastamento') == "T"){
                            return "<div value='' id='descanso_" + row + "'>"+record.get('totalDescanso')+"</div>";
                        }else{
                            return "("+rectime(record.get('totalDescanso'))+")";
                        }
                    }
                },
                {text: 'Repouso',  dataIndex: 'totalRepouso', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('afastamento') == "T"){
                            return "<div value='' id='repouso_" + row + "'>"+record.get('totalRepouso')+"</div>";
                        }else{
                            return "("+rectime(record.get('totalRepouso'))+")";
                        }
                    }
                },
                {text: 'Noturna',  dataIndex: 'totalNoturna', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('afastamento') == "T"){
                            return "<div value='' id='noturna_" + row + "'>"+record.get('totalNoturna')+"</div>";
                        }else{
                            var colorRefeicao = '';
                            if(record.get('totalNoturna') > 0){
                                colorRefeicao = "#3338ff";
                            }
                            return "<span style='color: "+colorRefeicao+";'>("+rectime(record.get('totalNoturna'))+")</span>";
                        }
                    }
                }
            ],
            bbar: [
                {xtype: totais},
                '->',
                {xtype: buttonPdf}
            ]
        });
    });

</script>

<div id="gestorRelId" style="width: 100%; height: 100%;"></div>

<form id="formExportPrint" method="post" action="pdf/pdfCartaoPonto.php" target="print">
    <input type="hidden" id="pontoContent" name="pontoContent" value=""/>
    <input type="hidden" id="tableTotais" name="tableTotais" value=""/>
    <input type="hidden" id="tableJustificativa" name="tableJustificativa" value=""/>
    <input type="hidden" id="formIdEmpresaHidden" name="formIdEmpresaHidden" value=""/>
    <input type="hidden" id="formIdCondutor" name="formIdCondutor" value=""/>
    <input type="hidden" id="formNmCondutor" name="formNmCondutor" value=""/>
    <input type="hidden" id="formDtIni" name="formDtIni" value=""/>
    <input type="hidden" id="formDtFim" name="formDtFim" value=""/>
</form>
<?
    //error_reporting(0);
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo       = new OracleCielo();
    $conexao        = $OraCielo->getCon();
    $CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

    if(isset($_SESSION)){
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    }else{
        header('Location: http://jornada.cielo.ind.br');
    }

    if($empresaUsu){
        $loadEmpresa    = 'false';
        $hiddenEmpresa  = 'true';
    }else{
        $empresaUsu     = $_REQUEST['idEmpresa'];
        $loadEmpresa    = 'true';
        $hiddenEmpresa  = 'false';
    }
?>

<script>

    function setMinDateFieldFim(dateMin, dati) {
        dateMin.setMinValue(dati);
    }

    function reloadCartao(){
        var dataIni     = Ext.Date.format(Ext.getCmp('idDateIni').value, 'dmY'),
            dataFim     = Ext.Date.format(Ext.getCmp('idDateFim').value, 'dmY'),
            idCondutor  = Ext.getCmp('idCondutor').value,
            idEmpresa   = Ext.getCmp('idEmpresaHidden').value;

        var mask = new Ext.LoadMask('gestorRelId', {msg: "Carregando..."});
        mask.show();

        Ext.getCmp("gridCartaoId").reconfigure(storeCartao);

        storeCartao.load({
            params:{
                'dtIni': dataIni,
                'dtFim': dataFim,
                'idCondutor': idCondutor,
                'idEmpresa': idEmpresa
            },
            callback: function(records, operation, success) {
                mask.hide();
                if(!operation.error){
                    var result = Ext.decode(operation.response.responseText);


                    if(result.status == 'OK'){

                        var totalJornada    = 0,
                            totalExtra      = 0,
                            totalExtra100   = 0,
                            totalEspera     = 0,
                            totalRefeicao   = 0,
                            totalNoturna    = 0,
                            totalDescanso   = 0,
                            totalRepouso    = 0,
                            totalAfast      = 0,
                            totalCondutores = 0;

                        storeCartao.each(function (rec) {
                            totalJornada    += rec.get('totalJornada');
                            totalExtra      += rec.get('totalExtra');
                            totalExtra100   += rec.get('totalExtra100');
                            totalEspera     += rec.get('totalEspera');
                            totalRefeicao   += rec.get('totalRefeicao');
                            totalNoturna    += rec.get('totalNoturna');
                            totalDescanso   += rec.get('totalDescanso');
                            totalRepouso    += rec.get('totalRepouso');
                            totalAfast      += rec.get('totalAfastamento');
                            totalCondutores += 1;
                        });

                        Ext.getCmp('idTotal').setText(
                            '<b>'       +
                            'Totais: '  +
                            '</b>'      +
                            'Condutores: '      + totalCondutores           + '&nbsp;&nbsp;|&nbsp;&nbsp;'   +
                            'Jornada: '         + rectime(totalJornada)     + '&nbsp;&nbsp;|&nbsp;&nbsp;'   +
                            'Extra: '           + rectime(totalExtra)       + '&nbsp;&nbsp;|&nbsp;&nbsp;'   +
                            'Extra (100%): '    + rectime(totalExtra100)    + '&nbsp;&nbsp;|&nbsp;&nbsp;'   +
                            'Espera: '          + rectime(totalEspera)      + '&nbsp;&nbsp;|&nbsp;&nbsp;'   +
                            'Refeição: '        + rectime(totalRefeicao)    + '&nbsp;&nbsp;|&nbsp;&nbsp;'   +
                            'Descanso: '        + rectime(totalDescanso)    + '&nbsp;&nbsp;|&nbsp;&nbsp;'   +
                            'Repouso: '         + rectime(totalRepouso)     + '&nbsp;&nbsp;|&nbsp;&nbsp;'   +
                            'Noturnas: '        + rectime(totalNoturna)       + '&nbsp;&nbsp;|&nbsp;&nbsp;' +
                            'Afastadas: '       + rectime(totalAfast)
                        );

                    }else{
                        Ext.Msg.show({
                            title: 'Erro!',
                            msg: 'Favor informar o departamento de TI.',
                            icon: Ext.Msg.ERROR,
                            buttons: Ext.Msg.OK
                        });
                    }
                }else{
                    Ext.Msg.alert('Ops!', 'Houve algum problema com a sua comunicação... <br><br>Tente novamente. Se persistir o erro verifique sua conexão de internet ou entre em contato com a equipe de Jornada.');
                }
            }
        });
    }

    Ext.define('Empresas', {
        extend: 'Ext.data.Model',
        fields:[
            {name: 'idEmpresa', type: 'int'},
            {name: 'nmEmpresa', type: 'string'},
            {name: 'respEmpresa', type: 'string'},
            {name: 'telEmpresa', type: 'string'},
            {name: 'emailEmpresa', type: 'string'},
            {name: 'cepEmpresa', type: 'string'},
            {name: 'ufEmpresa', type: 'string'},
            {name: 'cidEmpresa', type: 'string'},
            {name: 'VeicEmpresa', type: 'string'},
            {name: 'he100', type: 'string'}
        ]
    });

    var storeEmpresa = Ext.create('Ext.data.Store', {
        model: 'Empresas',
        autoLoad : <?=$loadEmpresa?>,
        proxy: {
            type: 'ajax',
            url : 'json/jsonEmpresas.php',
            reader: {
                type: 'json',
                root: 'empresas'
            }
        }
    });

    Ext.define('Condutores', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idCondutor', type: 'int'},
            {name: 'nmCondutor', type: 'string'},
            {name: 'cnhCondutor', type: 'string'},
            {name: 'dtNascCondutor', type: 'string'},
            {name: 'cpfCondutor', type: 'string'},
            {name: 'rgCondutor', type: 'string'},
            {name: 'telCondutor', type: 'string'},
            {name: 'celCondutor', type: 'string'},
            {name: 'matCondutor', type: 'string'},
            {name: 'empCondutor', type: 'string'},
            {name: 'sitCondutor', type: 'string'}
        ]
    });

    var storeCondutor = Ext.create('Ext.data.Store', {
        model: 'Condutores',
        autoLoad : true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonCondutores.php',
            reader: {
                type: 'json',
                root: 'condutores'
            },
            extraParams: {
                idEmpresa: '<?=$empresaUsu?>',
                ativo: 'T'
            }
        }
    });

    Ext.define('modelCartaoPonto', {
        extend: 'Ext.data.Model',
        requires: 'Ext.data.Model',
        fields:[
            {name: 'idCondutor', type: 'int'},
            {name: 'nomeCondutor', type: 'string'},
            {name: 'totalRepouso', type: 'int'},
            {name: 'totalJornada', type: 'int'},
            {name: 'totalRefeicao', type: 'int'},
            {name: 'totalDescanso', type: 'int'},
            {name: 'totalEspera', type: 'int'},
            {name: 'totalExtra', type: 'int'},
            {name: 'totalExtra100', type: 'int'},
            {name: 'totalNoturna', type: 'int'},
            {name: 'totalAfastamento', type: 'int'}
        ]
    });

    var storeCartao = Ext.create('Ext.data.Store', {
        storeId: 'cartaPontoStore',
        model: 'modelCartaoPonto',
        proxy: {
            type: 'ajax',
            url: 'json/jsonTotalizadorHoras.php',
            reader: {
                type: 'json',
                root: 'cartaoPonto'
            }
        }
    });

    var dateFim = Ext.create('Ext.form.DateField', {
        id: 'idDateFim',
        fieldLabel: 'Fim',
        labelWidth: 23,
        maxLength: 10,
        minLength: 10,
        maskRe: /[0-9/]/,
        format: "d/m/Y",
        emptyText: 'dd/mm/aaaa',
        maxValue: new Date(),
        width: 125,
        value: <?=($_REQUEST['dtFim']) ? "new Date('".$_REQUEST['dtFim']."')" : 'new Date()'?>
    });

    var dateIni = Ext.create('Ext.form.DateField', {
        id: 'idDateIni',
        fieldLabel: 'Início:',
        labelWidth: 33,
        maxLength: 10,
        minLength: 10,
        maskRe: /[0-9/]/,
        format: "d/m/Y",
        emptyText: 'dd/mm/aaaa',
        maxValue: new Date(),
        width: 135,
        value: <?=($_REQUEST['dtIni']) ? "new Date('".$_REQUEST['dtIni']."')" : "Ext.Date.add(new Date(), Ext.Date.MONTH, -1)"?>,
        listeners: {
            'afterrender': function(me) {
                setMinDateFieldFim(dateFim, me.getSubmitValue());
            },
            'change': function(me) {
                setMinDateFieldFim(dateFim, me.getSubmitValue());
            }
        }
    });

    var comboEmpresa = Ext.create('Ext.form.ComboBox', {
        fieldLabel: 'Empresa',
        labelWidth: 45,
        width: 250,
        queryMode: 'local',
        id: 'idEmpresaPonto',
        name: 'idempresaPonto',
        displayField: 'nmEmpresa',
        valueField: 'idEmpresa',
        value: '',
        store: storeEmpresa,
        readOnly: <?=($hiddenEmpresa)?>,
        hidden: <?=($hiddenEmpresa)?>,
        emptyText: 'Selecione para filtrar condutores',
        listeners:{
            select: function(f, r, i){
                storeCondutor.getProxy().extraParams = { idEmpresa: f.getValue()};
                storeCondutor.load();
                Ext.getCmp("idCondutor").setValue('');

                empresaHidden.setValue(Ext.getCmp('idEmpresaPonto').value);
                reloadCartao();
            }
        }
    });

    var comboCondutor = Ext.create('Ext.form.ComboBox', {
        fieldLabel: 'Condutor:',
        labelWidth: 50,
        width: 250,
        style: 'margin-top: 8px;',
        queryMode: 'local',
        id: 'idCondutor',
        name: 'idCondutor',
        displayField: 'nmCondutor',
        valueField: 'idCondutor',
        store: storeCondutor,
        emptyText: 'Selecione para filtrar...',
        listeners:{
            select: function(f, r, i){
                if(Ext.getCmp('idDateIni').value == '' || Ext.getCmp('idDateIni').value == null || Ext.getCmp('idDateFim').value == '' || Ext.getCmp('idDateFim').value == null || Ext.getCmp('idDateIni').rawValue.length != 10 || Ext.getCmp('idDateFim').rawValue.length != 10){
                    Ext.Msg.show({
                        title:'Atenção:',
                        msg: 'Você deve preencher as data de inicio e fim corretamente',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    reloadCartao();
                }
            }
        }
    });

    var empresaHidden = Ext.create('Ext.form.field.Hidden', {
        name: 'idEmpresaHidden',
        id: 'idEmpresaHidden',
        value: '<?=$empresaUsu?>'
    });

    var buttonFiltrar = Ext.create('Ext.Button', {
        xtype: 'button',
        text: 'Filtrar',
        iconCls: 'filter',
        style: 'margin-top: 8px;',
        handler: function() {
            if(Ext.getCmp('idDateIni').value == '' || Ext.getCmp('idDateIni').value == null || Ext.getCmp('idDateFim').value == '' || Ext.getCmp('idDateFim').value == null || Ext.getCmp('idDateIni').rawValue.length != 10 || Ext.getCmp('idDateFim').rawValue.length != 10){
                Ext.Msg.show({
                    title:'Atenção:',
                    msg: 'Você deve preencher as data de inicio e fim corretamente',
                    icon: Ext.Msg.INFO,
                    buttons: Ext.Msg.OK
                });
            }else{
                reloadCartao();
            }
        }
    });

    var toolbar = Ext.create('Ext.toolbar.Toolbar', {
        id: 'toolbarRelId',
        region: 'north',
        items: [
            ' ',
            {xtype: dateIni},
            '  ',
            {xtype: dateFim},
            '  ',
            {xtype:	comboEmpresa},
            ' ',
            {xtype: comboCondutor},
            {xtype: empresaHidden},
            '  ',
            '-',
            {xtype: buttonFiltrar}
        ]
    });

    var buttonPdf = Ext.create('Ext.Button', {
        text: 'Exportar para PDF',
        style: 'margin-top: 8px;',
        iconCls: 'pdf',
        handler: function() {
            if(Ext.getCmp('idDateIni').value == '' || Ext.getCmp('idDateIni').value == null || Ext.getCmp('idDateFim').value == '' || Ext.getCmp('idDateFim').value == null || Ext.getCmp('idDateIni').rawValue.length != 10 || Ext.getCmp('idDateFim').rawValue.length != 10){
                Ext.Msg.show({
                    title:'Atenção:',
                    msg: 'Você deve preencher as data de inicio e fim corretamente',
                    icon: Ext.Msg.INFO,
                    buttons: Ext.Msg.OK
                });
            }else{
                if(Ext.getCmp('idEmpresaHidden').value === null || Ext.getCmp('idEmpresaHidden').value === ''){
                    Ext.Msg.show({
                        title:'Atenção:',
                        msg: 'Selecione a empresa',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    var color = '#E1EEF4';

                    var tabela,
                        totalJornada    = 0,
                        totalExtra      = 0,
                        totalExtra100   = 0,
                        totalEspera     = 0,
                        totalRefeicao   = 0,
                        totalDescanso   = 0,
                        totalRepouso    = 0,
                        totalNoturna    = 0,
                        totalAfast      = 0,
                        totalCondutores = 0;

                    var colorExtra     = '',
                        colorExtra100  = '',
                        colorNoturna   = '',
                        colorAfastadas = '';


                    tabela =
                        '<br/>' +
                        '<font size="8">' +
                        '<table id="total" cellpadding="4" cellspacing="2" style="text-align:left;">' +
                        '<thead>' +
                        '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                        '<th>Condutor</th>' +
                        '<th>Jornada</th>' +
                        '<th>Extra</th>' +
                        '<th>Extra100%</th>' +
                        '<th>Espera</th>' +
                        '<th>Refei&ccedil;&atilde;o</th>' +
                        '<th>Descanso</th>' +
                        '<th>Repouso</th>' +
                        '<th>Noturna</th>' +
                        '<th>Afastadas</th>' +
                        '</tr>' +
                        '</thead>';

                    storeCartao.each( function (rec) {
                        totalJornada    += rec.get('totalJornada');
                        totalExtra      += rec.get('totalExtra');
                        totalExtra100   += rec.get('totalExtra100');
                        totalEspera     += rec.get('totalEspera');
                        totalRefeicao   += rec.get('totalRefeicao');
                        totalDescanso   += rec.get('totalDescanso');
                        totalRepouso    += rec.get('totalRepouso');
                        totalNoturna    += rec.get('totalNoturna');
                        totalAfast      += rec.get('totalAfastamento');
                        totalCondutores += 1;

                        colorExtra      = '';
                        colorExtra100   = '';
                        colorNoturna    = '';
                        colorAfastadas  = '';

                        if(rec.get('totalExtra') > 0){colorExtra = "#91c8f8";}

                        if(rec.get('totalExtra100') > 0){colorExtra100 = "#91c8f8";}

                        if(rec.get('totalNoturna') > 0){colorNoturna = "#91c8f8";}

                        if(rec.get('totalAfastamento') > 0){colorAfastadas = "#91c8f8";}

                        tabela +=
                            '<tr bgcolor="'+color+'">' +
                                '<td>'+rec.get('nomeCondutor')+'</td>' +
                                '<td>('+ rectime(rec.get('totalJornada'))+')</td>' +
                                '<td bgcolor="'+colorExtra+'">(' + rectime(rec.get('totalExtra')) + ')</td>' +
                                '<td bgcolor="'+colorExtra100+'">(' + rectime(rec.get('totalExtra100')) + ')</td>' +
                                '<td>('+ rectime(rec.get('totalEspera')) +')</td>' +
                                '<td>('+ rectime(rec.get('totalRefeicao')) +')</td>' +
                                '<td>('+ rectime(rec.get('totalDescanso')) +')</td>' +
                                '<td>('+ rectime(rec.get('totalRepouso')) +')</td>' +
                                '<td bgcolor="'+colorNoturna+'">(' + rectime(rec.get('totalNoturna')) + ')</td>' +
                                '<td bgcolor="'+colorAfastadas+'">(' + rectime(rec.get('totalAfastamento')) + ')</td>' +
                            '</tr>';

                        color = (color == '')?"#E1EEF4":'';
                    });

                    tabela += '<tbody></tbody></table></font>';

                    tabela +=
                        '<br/>' +
                        '<font size="8">' +
                        '<table id="total" cellpadding="2" cellspacing="2" style="text-align:center;">' +
                        '<thead>' +
                        '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                        '<th colspan="10">Totais</th>' +
                        '</tr>' +
                        '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                        '<th>Condutores</th>' +
                        '<th>Jornada</th>' +
                        '<th>Hora Extra</th>' +
                        '<th>Horas Extra 100</th>' +
                        '<th>Espera</th>' +
                        '<th>Refei&ccedil;&atilde;o</th>' +
                        '<th>Descanso</th>' +
                        '<th>Repouso</th>' +
                        '<th>Noturna</th>' +
                        '<th>Afastadas</th>' +
                        '</tr>' +
                        '</thead>' +
                        '<tr>' +
                        '<td>'+totalCondutores+'</td>' +
                        '<td>('+rectime(totalJornada)+')</td>' +
                        '<td>('+rectime(totalExtra)+')</td>' +
                        '<td>('+rectime(totalExtra100)+')</td>' +
                        '<td>('+rectime(totalEspera)+')</td>' +
                        '<td>('+rectime(totalRefeicao)+')</td>' +
                        '<td>('+rectime(totalDescanso)+')</td>' +
                        '<td>('+rectime(totalRepouso)+')</td>' +
                        '<td>('+rectime(totalNoturna)+')</td>' +
                        '<td>('+rectime(totalAfast)+')</td>' +
                        '</tr>' +
                        '<tbody></tbody>' +
                        '</table>' +
                        '</font>';

                    Ext.getDom('pontoContent').value            = tabela;
                    Ext.getDom('formIdEmpresaHidden').value     = Ext.getCmp('idEmpresaHidden').value;
                    Ext.getDom('formIdEmpresa').value           = Ext.getCmp('idEmpresaPonto').value;
                    Ext.getDom('formIdCondutor').value          = Ext.getCmp('idCondutor').value;
                    Ext.getDom('formNmCondutor').value          = Ext.getCmp('idCondutor').rawValue;
                    Ext.getDom('formDtIni').value               = Ext.Date.format(Ext.getCmp('idDateIni').value, 'Y-m-d');
                    Ext.getDom('formDtFim').value               = Ext.Date.format(Ext.getCmp('idDateFim').value, 'Y-m-d');

                    Ext.getDom('formExportPrint').action        = 'pdf/pdfTotalizadorHoras.php';

                    Ext.getDom('formExportPrint').submit();
                }
            }
        }
    });

    Ext.onReady(function () {

        var totais = Ext.toolbar.TextItem({
            id: 'idTotal',
            xtype: 'tbtext',
            text: ''
        });

        var grid = Ext.create('Ext.grid.Panel', {
            tbar: toolbar,
            id: 'gridCartaoId',
            forceFit: true,
            height: Ext.getBody().getHeight()-59,
            autoWidth: true,
            renderTo: 'gestorRelId',
            viewConfig: {
                emptyText: '<b>Nenhum registro de jornada encontrado para este filtro</b>',
                deferEmptyText: false,
                getRowClass: function(record, rowIndex, rowParams, store) {
                    return 'gridPointerRow';
                }
            },
            columns: [
                {text: 'Codigo',  dataIndex: 'idCondutor', hidden: true},
                {text: 'Condutor',  dataIndex: 'nomeCondutor'},
                {
                    text: 'Jornada', dataIndex: 'totalJornada', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return "("+rectime(record.get('totalJornada'))+")";
                    }
                },
                {text: 'Hora Extra', dataIndex: 'totalExtra', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {

                        var colorExtra = '';
                        if(record.get('totalExtra') > 0){
                            colorExtra = "#3338ff";
                        }
                        return "<span style='color: "+colorExtra+";'>("+rectime(record.get('totalExtra'))+")</span>";
                    }
                },
                {text: 'Extra 100%', dataIndex: 'totalExtra100', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        var colorExtra100 = '';
                        if(record.get('totalExtra100') > 0){
                            colorExtra100= "#3338ff";
                        }
                        return "<span style='color: "+colorExtra100+";'>("+rectime(record.get('totalExtra100'))+")</span>";
                    }
                },
                {text: 'Espera',  dataIndex: 'totalEspera', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return "("+rectime(record.get('totalEspera'))+")";
                    }
                },
                {text: 'Refeição', dataIndex: 'totalRefeicao', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return "("+rectime(record.get('totalRefeicao'))+")";
                    }
                },
                {text: 'Descanso',  dataIndex: 'totalDescanso', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return "("+rectime(record.get('totalDescanso'))+")";
                    }
                },
                {text: 'Repouso',  dataIndex: 'totalRepouso', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return "("+rectime(record.get('totalRepouso'))+")";
                    }
                },
                {text: 'Noturna',  dataIndex: 'totalNoturna', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        var colorNoturna = '';
                        if(record.get('totalNoturna') > 0){
                            colorNoturna = "#3338ff";
                        }
                        return "<span style='color: "+colorNoturna+";'>("+rectime(record.get('totalNoturna'))+")</span>";
                    }
                },
                {text: 'Afastadas',  dataIndex: 'totalAfastamento', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        var colorAfastadas = '';
                        if(record.get('totalAfastamento') > 0){
                            colorAfastadas= "#3338ff";
                        }
                        return "<span style='color: "+colorAfastadas+";'>("+rectime(record.get('totalAfastamento'))+")</span>";
                    }
                }
            ],
            bbar: [
                {xtype: totais},
//                {xtype: 'tbtext', id: 'idTotalText', height: 30, text:' '},

                '->',
                {xtype: buttonPdf}
            ]
        });

        grid.getSelectionModel().on('selectionchange', function(sm, selectedRecord){
            if(selectedRecord.length != 0) {
                panelLoad(
                    'gestorTabId',
                    'Cartão Ponto',
                    'relatorios/relCartaoPonto.php',
                    'idCondutor='+selectedRecord[0].get('idCondutor')+'&dataIni='+Ext.Date.format(Ext.getCmp('idDateIni').value, 'd/m/Y')+'&dataFim='+Ext.Date.format(Ext.getCmp('idDateFim').value, 'd/m/Y')+'&idEmpresa='+empresaHidden.value);
            }
        });
    });

    <?if($empresaUsu){?>
        reloadCartao();
    <?}?>

    <? if($_REQUEST['idCondutor']){
        if($_REQUEST['idEmpresa']){ ?>
            Ext.getCmp("idEmpresaPonto").setValue(<?=$_REQUEST['idEmpresa']?>);
        <?}else{?>
            Ext.getCmp("idEmpresaPonto").setValue(<?=$_REQUEST['idEmpresaC']?>);
        <?}?>
            Ext.getCmp("idCondutor").setValue(<?=$_REQUEST['idCondutor']?>);
            reloadCartao();
    <?}?>

</script>

<div id="gestorRelId" style="width: 100%; height: 100%;"></div>

<form id="formExportPrint" method="post" action="pdf/pdfCartaoPonto.php" target="print">
    <input type="hidden" id="pontoContent" name="pontoContent" value=""/>
    <input type="hidden" id="justificativaContent" name="justificativaContent" value=""/>
    <input type="hidden" id="formIdEmpresaHidden" name="formIdEmpresaHidden" value=""/>
    <input type="hidden" id="formIdEmpresa" name="formIdEmpresa" value=""/>
    <input type="hidden" id="formIdCondutor" name="formIdCondutor" value=""/>
    <input type="hidden" id="formNmCondutor" name="formNmCondutor" value=""/>
    <input type="hidden" id="formDtIni" name="formDtIni" value=""/>
    <input type="hidden" id="formDtFim" name="formDtFim" value=""/>
</form>
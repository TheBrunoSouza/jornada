<?php
    //error_reporting(0);
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo = new OracleCielo();
    $conexao = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

    //Configurações de permissão (Seta a propriedade hidden do objeto como false ou true)
    $permissao = $CtrlAcesso->checkPermissao(20, '');

    if($permissao){
        if($permissao['add'] == 'T'){
            $hiddenAdicionar = 'false';
        }else{
            $hiddenAdicionar = 'true';
        }

        if($permissao['edit'] == 'T'){
            $hiddenAlterarExcluir = 'false';
        }else{
            $hiddenAlterarExcluir = 'true';
        }
    }

    if(isset($_SESSION)) {
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    } else {
        header('Location: http://jornada.cielo.ind.br');
    }

    $empExtra100 = $CtrlAcesso->getExtra100($_SESSION, $conexao);
    $loadEmpresa = ($empresaUsu) ? 'false' : 'true';
?>
<script>

    var hiddenAdicionar         = <?=$hiddenAdicionar?>;
    var hiddenAlterarExcluir    = <?=$hiddenAlterarExcluir?>;

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

    Ext.define('bancoCondutor', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idBancoCondutor', type: 'int'},
            {name: 'nomeCondutor', type: 'string'},
            {name: 'idCondutor', type: 'int'},
            {name: 'saldoIni', type: 'int'},
            {name: 'acumulado', type: 'int'},
            {name: 'saldoAtual', type: 'int'},
            {name: 'idBancoHoras', type: 'int'},
            {name: 'dataIni', type: 'date'},
            {name: 'dataFim', type: 'date'},
            {name: 'minSemBH', type: 'int'},
            {name: 'minSabBH', type: 'int'},
            {name: 'minTotalBC', type: 'int'},
            {name: 'vencimentoBH', type: 'int'},
            {name: 'descPeriodo', type: 'string'}
        ]
    });

    var storeBancoCondutor = Ext.create('Ext.data.Store', {
        model: 'bancoCondutor',
        autoLoad : <?=($empresaUsu)?'true':'false'?>,
        proxy: {
            type: 'ajax',
            url: 'json/jsonBancoCondutor.php',
            reader: {
                type: 'json',
                root: 'bancoCondutor'
            },
            extraParams: {
                idCondutor: '',
                idEmpresa: '<?=$empresaUsu?>'
            }
        },
        listeners: {
            load: {
                fn: function(){
                    var testeUserCentral = '<?=($empresaUsu)?'true':'false'?>';
                    if(testeUserCentral == 'true'){
                        var total = storeBancoCondutor.totalCount;
                        if(total == 0){
                            if(!Ext.getCmp('lstBancoHora')){

                                Ext.Msg.show({
                                    title: 'Dica:',
                                    msg: 'Sua empresa não possui nenhum Banco de Horas ativo no momento. Deseja realizar o cadastro?',
                                    icon: Ext.Msg.INFO,
                                    buttons: Ext.Msg.YESNO,
                                    fn: function (btn) {
                                        if (btn == 'yes') {

                                            var buttonFiltrar = Ext.create('Ext.Button', {
                                                id: 'buttonFiltrar',
                                                text: 'Próximo',
                                                icon: 'imagens/16x16/arrow_right.png',
                                                handler: function () {
                                                    console.info('ok');
                                                }
                                            });

                                            var buttonCancelar = Ext.create('Ext.Button', {
                                                id: 'buttonCancelar',
                                                text: 'Cancelar',
                                                icon: 'imagens/16x16/cancel.png',
                                                handler: function () {
                                                    console.info('ok');
                                                }
                                            });

                                            var toolbarTeste = Ext.create('Ext.toolbar.Toolbar', {
                                                id: 'teste',
                                                region: 'south',
                                                items: [
                                                    '->',
                                                    {xtype: buttonCancelar},
                                                    {xtype: buttonFiltrar}
                                                ]
                                            });

                                            if (Ext.getCmp('lstBancoHora')) {
                                                Ext.getCmp('lstBancoHora').close();
                                            }

                                            showWindowToolbar(
                                                'formManutBancoHoras',
                                                'Novo Banco de Horas',
                                                'cad/cadBancoHoras.php',
                                                'idEmpresaBH=' + Ext.getCmp('idEmpresaBH').value,
                                                450,
                                                210,
                                                true,
                                                true,
                                                toolbarTeste
                                            );
                                        }
                                    }
                                });

                            }
                        }
                    }
                }
            }
        }
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
                idEmpresa: '<?=($empresaUsu == '' or $empresaUsu == '')?$_REQUEST['idEmpresa']:$empresaUsu?>',
                ativo: 'T'
            }
        }
    });

    var comboCondutor = Ext.create('Ext.form.ComboBox', {
        //fieldLabel: 'Condutor:',
        labelWidth: 50,
        width: 270,
        style: 'margin-top: 8px;',
        queryMode: 'local',
        id: 'idComboCondutor',
        name: 'idComboCondutor',
        displayField: 'nmCondutor',
        valueField: 'idCondutor',
        value: '',
        store: storeCondutor,
        emptyText: 'Filtrar o condutor...',
        listeners:{
            select: function(f, r, i){
                storeBancoCondutor.load({
                    params: {
                        'idCondutor': Ext.getCmp('idComboCondutor').value
                    },
                    callback: function(records, operation, success) {
                        //console.info('entrou callback');
                    }
                });
            }
        }
    });

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
        autoLoad : true,
        proxy: {
            type: 'ajax',
            url : 'json/jsonEmpresas.php',
            reader: {
                type: 'json',
                root: 'empresas'
            }
        }
    });

    var comboEmpresaBH = Ext.create('Ext.form.ComboBox', {
        labelWidth: 45,
        width: 300,
        queryMode: 'local',
        id: 'idEmpresaBH',
        name: 'idempresaBH',
        displayField: 'nmEmpresa',
        valueField: 'idEmpresa',
        value: '<?=($empresaUsu)?$empresaUsu:''?>',
        store: storeEmpresa,
        readOnly: <?=($empresaUsu)?'true':'false'?>,
        hidden: <?=($empresaUsu)?'true':'false'?>,
        emptyText: 'Filtrar a empresa para carregar os condutores...',
        listeners:{
            select: function(f, r, i){
                //Carregando o combo dos condutores
                storeCondutor.getProxy().extraParams = {
                    idEmpresa: f.getValue()
                };
                storeCondutor.load();

                //Carregando store principal
                storeBancoCondutor.getProxy().extraParams = {
                    idEmpresa: f.getValue(),
                    idCondutor: ''
                };
                storeBancoCondutor.load();

                Ext.getCmp("idComboCondutor").setValue('');
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
            storeBancoCondutor.load({
                params: {
                    'idCondutor': Ext.getCmp('idComboCondutor').value,
                    'idEmpresa': Ext.getCmp('idEmpresaBH').value
                },
                callback: function(records, operation, success) {
                    //console.info('entrou callback');
                }
            });
        }
    });

    var novoBancoHoras = Ext.create('Ext.Button', {
        text: 'Novo Banco de Horas',
        icon: 'imagens/16x16/database_add.png',
        disabled: false,
        handler: function() {

            var testeUserCentral = '<?=($empresaUsu)?'true':'false'?>';

            var buttonFiltrar = Ext.create('Ext.Button', {
                id: 'buttonFiltrar',
                text: 'Próximo',
                icon: 'imagens/16x16/arrow_right.png',
                handler: function () {
                    console.info('ok');
                }
            });

            var buttonCancelar = Ext.create('Ext.Button', {
                id: 'buttonCancelar',
                text: 'Cancelar',
                icon: 'imagens/16x16/cancel.png',
                handler: function () {
                    console.info('ok');
                }
            });

            var toolbarTeste = Ext.create('Ext.toolbar.Toolbar', {
                id: 'teste',
                region: 'south',
                items: [
                    '->',
                    {xtype: buttonCancelar},
                    {xtype: buttonFiltrar}
                ]
            });

            if(testeUserCentral == 'false'){
                //Indica que o usuário que está acessando é adm (Sem empresa vinculada)
                if(Ext.getCmp('idEmpresaBH').value == '' || Ext.getCmp('idEmpresaBH').value == null){
                    Ext.Msg.show({
                        title: 'Informação:',
                        msg: 'Selecione a empresa para cadastrar um novo Banco de Horas',
                        icon: Ext.Msg.WARNING,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    showWindowToolbar(
                        'formManutBancoHoras',
                        'Novo Banco de Horas',
                        'cad/cadBancoHoras.php',
                        'idEmpresaBH='+Ext.getCmp('idEmpresaBH').value,
                        450,
                        210,
                        true,
                        true,
                        toolbarTeste
                    );
                }
            }else{
                showWindowToolbar(
                    'formManutBancoHoras',
                    'Novo Banco de Horas',
                    'cad/cadBancoHoras.php',
                    'idEmpresaBH='+Ext.getCmp('idEmpresaBH').value,
                    450,
                    210,
                    true,
                    true,
                    toolbarTeste
                );
            }
        }
    });

    var bancos = Ext.create('Ext.Button', {
        text: 'Listar Bancos',
        icon: 'imagens/16x16/database_table.png',
        handler: function() {
            var testeUserCentral = '<?=($empresaUsu)?'true':'false'?>';
            if(testeUserCentral == 'false'){
                //Indica que o usuário que está acessando é adm (Sem empresa vinculada)
                if(Ext.getCmp('idEmpresaBH').value == '' || Ext.getCmp('idEmpresaBH').value == null){
                    Ext.Msg.show({
                        title:'Informação:',
                        msg: 'Selecione a empresa para listar os Bancos de Horas',
                        icon: Ext.Msg.WARNING,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    showWindow(
                        'lstBancoHora',
                        'Banco de Horas',
                        'lst/lstBancoHora.php',
                        'idEmpresa='+Ext.getCmp('idEmpresaBH').value,
                        900,
                        300,
                        true,
                        true
                    );
                }
            }else{
                showWindow(
                    'lstBancoHora',
                    'Banco de Horas',
                    'lst/lstBancoHora.php',
                    'idEmpresa='+Ext.getCmp('idEmpresaBH').value,
                    900,
                    300,
                    true,
                    true
                );
            }
        }
    });

    var listarBanco = Ext.create('Ext.Button', {
        xtype: 'button',
        text: 'Fechamento',
        icon: 'imagens/16x16/database_gear.png',
        handler: function() {
            var testeUserCentral = '<?=($empresaUsu)?'true':'false'?>';
            if(testeUserCentral == 'false'){
                //Indica que o usuário que está acessando é adm (Sem empresa vinculada)
                if(Ext.getCmp('idEmpresaBH').value == '' || Ext.getCmp('idEmpresaBH').value == null){
                    Ext.Msg.show({
                        title:'Informação:',
                        msg: 'Selecione a empresa para abrir seus fechamentos',
                        icon: Ext.Msg.WARNING,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    panelLoad('gestorTabId', 'Banco de Horas - Fechamento', 'relatorios/relBancoCondutorFechamento.php', 'idEmpresa='+Ext.getCmp('idEmpresaBH').value);
                }
            }else{
                panelLoad('gestorTabId', 'Banco de Horas - Fechamento', 'relatorios/relBancoCondutorFechamento.php', 'idEmpresa='+Ext.getCmp('idEmpresaBH').value);
            }
        }
    });

    var toolbar = Ext.create('Ext.toolbar.Toolbar', {
        id: 'toolbarRelId',
        region: 'north',
        items: [
            ' ',
            {xtype: comboEmpresaBH},
            {xtype: comboCondutor},
            {xtype: empresaHidden},
            ' ',
            ' ',
            '-',
            {xtype: buttonFiltrar},
            '->',
            {xtype: novoBancoHoras},
            {xtype: bancos},
            {xtype: listarBanco}
        ]
    });

    var pdfBancoCondutor = Ext.create('Ext.Button', {
        text: 'Exportar para PDF',
        iconCls: 'pdf',
        style: 'margin-top: 8px;',
        handler: function() {
            var color = '#E1EEF4';
            var colorFont;
            var colorFontAcumulado;
            var colorFontSaldoIni;
            var tabela = '';
            var saldoAtual = '';

            tabela +=
                '<br/>' +
                '<font size="7">' +
                '<table id="total" cellpadding="4" cellspacing="2" style="text-align:left;">' +
                '<thead>' +
                '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                '<th>Condutor</th>' +
                '<th>Saldo Inicial</th>' +
                '<th>Acumulado</th>' +
                '<th>Saldo Atual</th>' +
                '</tr>' +
                '</thead>';

            storeBancoCondutor.each( function (model) {

                if(model.get('saldoAtual') < 0){
                    colorFont = "#f83223";
                }else if(model.get('saldoAtual') > 0){
                    colorFont = "#3338ff";
                }else{
                    colorFont = "#14bf0e";
                }

                if(model.get('saldoIni') < 0){
                    colorFontSaldoIni = "#f83223";
                }else if(model.get('saldoIni') > 0){
                    colorFontSaldoIni = "#3338ff";
                }else{
                    colorFontSaldoIni = "#14bf0e";
                }

                if(model.get('acumulado') < 0){
                    colorFontAcumulado = "#f83223";
                }else if(model.get('acumulado') > 0){
                    colorFontAcumulado = "#3338ff";
                }else{
                    colorFontAcumulado = "#14bf0e";
                }

                tabela += '<tr bgcolor="'+color+'"><td>'+ model.get('nomeCondutor')+'</td><td><span style="color:'+colorFontSaldoIni+';">'+ rectime(model.get('saldoIni'), true) +'</span></td><td><span style="color:'+colorFontAcumulado+';">'+ rectime(model.get('acumulado'), true) +'</span></td><td><span style="color:'+colorFont+';">'+rectime(model.get('saldoAtual'), true)+'</span></td></tr>';

                color = (color == '')?"#E1EEF4":'';
            });

            tabela += '<tbody></tbody></table></font>';

            Ext.getDom('pontoContent').value = tabela;
            Ext.getDom('idEmpresa').value = Ext.getCmp('idEmpresaBH').value;
            Ext.getDom('formExportPrint').action = 'pdf/pdfBancoCondutor.php';
            Ext.getDom('formExportPrint').submit();
        }
    });

    var buttonFeriado = Ext.create('Ext.Button', {
        text: 'Feriados',
        iconCls: 'calendar',
        style: 'margin-top: 8px;',
        handler: function() {
            var testeUserCentral = '<?=($empresaUsu)?'true':'false'?>';
            if(testeUserCentral == 'false'){
                //Indica que o usuário que está acessando é adm (Sem empresa vinculada)
                if(Ext.getCmp('idEmpresaBH').value == '' || Ext.getCmp('idEmpresaBH').value == null){
                    Ext.Msg.show({
                        title:'Informação:',
                        msg: 'Selecione a empresa para abrir seus feriados',
                        icon: Ext.Msg.WARNING,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    showWindow('lstFeriados', 'Feriados', 'lst/lstFeriados.php', "idEmpresa="+Ext.getCmp('idEmpresaBH').value, 600, 300, true, true);
                }
            }else{
                showWindow('lstFeriados', 'Feriados', 'lst/lstFeriados.php', "idEmpresa="+Ext.getCmp('idEmpresaBH').value, 600, 300, true, true);
            }
        }
    });

    Ext.onReady(function (){

        var grid = Ext.create('Ext.grid.Panel', {
            id: 'gridBancoHorasId',
            store: storeBancoCondutor,
            forceFit: true,
            autoWidth: true,
            border: false,
            columnLines: true,
            height: Ext.getBody().getHeight()-96,
            viewConfig: {
                emptyText: '<b>Nenhum registro de banco de horas encontrado</b>',
                deferEmptyText: false,
                getRowClass: function(record, rowIndex, rowParams, store) {
                    return 'gridPointerRow';
                }
            },
            columns: [
                {text: 'ID Banco Condutor',  dataIndex: 'idBancoCondutor', hidden: true},
                {text: 'ID Condutor',  dataIndex: 'idCondutor', hidden: true},
                {text: 'Condutor',  dataIndex: 'nomeCondutor'},
                {text: 'Saldo Inicial', dataIndex: 'saldoIni',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('saldoIni') == 0){
                            return "<div value='' id='saldoIni" + row + "'><span style='color: green;'>"+rectime(record.get('saldoIni'), true)+"</span></div>";
                        }else if(record.get('saldoAtual') < 0){
                            return "<div value='' id='saldoIni" + row + "'><span style='color: red;'>"+rectime(record.get('saldoIni'), true)+"</span></div>";
                        }else{
                            return "<div value='' id='saldoIni" + row + "'><span style='color: blue;'>"+rectime(record.get('saldoIni'), true)+"</span></div>";
                        }
                    }
                },
                {text: 'Acumulado',  dataIndex: 'acumulado',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('acumulado') == 0){
                            return "<div value='' id='acumulado" + row + "'><span style='color: green;'>"+rectime(record.get('acumulado'), true)+"</span></div>";
                        }else if(record.get('acumulado') < 0){
                            return "<div value='' id='acumulado" + row + "'><span style='color: red;'>"+rectime(record.get('acumulado'), true)+"</span></div>";
                        }else{
                            return "<div value='' id='acumulado" + row + "'><span style='color: blue;'>"+rectime(record.get('acumulado'), true)+"</span></div>";
                        }
                    }
                },
                {text: 'Saldo Atual',  dataIndex: 'saldoAtual',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('saldoAtual') == 0){
                            return "<div value='' id='saldoAtual" + row + "'><span style='color: green;'>"+rectime(record.get('saldoAtual'), true)+"</span></div>";
                        }else if(record.get('saldoAtual') < 0){
                            return "<div value='' id='saldoAtual" + row + "'><span style='color: red;'>"+rectime(record.get('saldoAtual'), true)+"</span></div>";
                        }else{
                            return "<div value='' id='saldoAtual" + row + "'><span style='color: blue;'>"+rectime(record.get('saldoAtual'), true)+"</span></div>";
                        }
                    }
                },
                {text: 'Banco Hora',  dataIndex: 'idBancoHoras', hidden: true}
            ],
            bbar: [
                {xtype: buttonFeriado},
                '->',
                {xtype: pdfBancoCondutor}
            ]
        });

        Ext.create('Ext.panel.Panel', {
            tbar: toolbar,
            autoWidth: true,
            height: Ext.getCmp('gestorTabId').getHeight(),
            renderTo: 'gestorRelId',
            items: [
//                {
//                    xtype: 'panel',
//                    html: '<p class="x-grid-empty"> <b> Banco de Horas: <br> Banco de Horas: </b> </p>',
//                    border: false,
//                    style: 'margin-top: 8px; margin-bottom: 8px;',
//                    id: 'gestorPanelCondutorId'
//                },
                {xtype: grid}
            ]
        });

        grid.getSelectionModel().on('selectionchange', function(sm, selectedRecord) {
            var idBancoCondutor = selectedRecord[0].get('idBancoCondutor'),
                idCondutor      = selectedRecord[0].get('idCondutor'),
                nomeCondutor    = selectedRecord[0].get('nomeCondutor'),
                saldoIni        = selectedRecord[0].get('saldoIni'),
                acumulado       = selectedRecord[0].get('acumulado'),
                saldoAtual      = selectedRecord[0].get('saldoAtual'),
                idBancoHoras    = selectedRecord[0].get('idBancoHoras'),
                dataIniBC       = Ext.Date.format(selectedRecord[0].get('dataIni'), 'd/m/Y'),
                dataFimBC       = Ext.Date.format(selectedRecord[0].get('dataFim'), 'd/m/Y'),
                dataIniBF       = '',
                dataFimBF       = '',
                minSemBH        = selectedRecord[0].get('minSemBH'),
                minSabBH        = selectedRecord[0].get('minSabBH'),
                minTotalBC      = selectedRecord[0].get('minTotalBC'),
                vencimentoBH    = selectedRecord[0].get('vencimentoBH'),
                origem          = 'bancoCondutor',
                descPeriodo     = selectedRecord[0].get('descPeriodo'),
                saldoBF             = '',
                totalTrabalhadoBF   = '',
                mediaTrabDia        = '',
                mediaTrabSem        = '',
                diasValorDobrado    = '',
                idEmpresa           = <?($empresaUsu)?$empresaUsu:"'"?>Ext.getCmp('idEmpresaBH').value;

            panelLoad('gestorTabId', 'Banco de Horas - Diario', 'relatorios/relBancoCondutorDia.php',
                'idBancoCondutor='+idBancoCondutor+'&' +
                'idCondutor='+idCondutor+'&' +
                'nomeCondutor='+nomeCondutor+'&' +
                'saldoIni='+saldoIni+'&' +
                'acumulado='+acumulado+'&' +
                'saldoAtual='+saldoAtual+'&' +
                'idBancoHoras='+idBancoHoras+'&' +
                'dataIniBC='+dataIniBC+'&' +
                'dataFimBC='+dataFimBC+'&'+
                'dataIniBF='+dataIniBF+'&' +
                'dataFimBF='+dataFimBF+'&'+
                'minSemBH='+minSemBH+'&'+
                'minSabBH='+minSabBH+'&'+
                'minTotalBC='+minTotalBC+'&'+
                'vencimentoBH='+vencimentoBH+'&'+
                'origem='+origem+'&'+
                "saldoBF="+saldoBF+"&"+
                "totalTrabalhadoBF="+totalTrabalhadoBF+"&"+
                "mediaTrabDia="+mediaTrabDia+"&"+
                "mediaTrabSem="+mediaTrabSem+"&"+
                "diasValorDobrado="+diasValorDobrado+"&"+
                "idEmpresa="+idEmpresa+"&"+
                'descPeriodo='+descPeriodo);
        });
    });

    function setMinDateFieldFim(dateMin, dati) {
        dateMin.setMinValue(dati);
    }

    <?
    if($_REQUEST['idEmpresa']){ ?>
        Ext.getCmp("idEmpresaBH").setValue(<?=$_REQUEST['idEmpresa']?>);
        storeBancoCondutor.reload();
    <?}?>

</script>

<div id="gestorRelId" style="width: 100%; height: 100%;"></div>

<form id="formExportPrint" method="post" action="pdf/pdfBancoHoras.php" target="print">
    <input type="hidden" id="pontoContent" name="pontoContent" value=""/>
    <input type="hidden" id="idEmpresa" name="idEmpresa" value=""/>
</form>
<?
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo       = new OracleCielo();
    $conexaoOra     = $OraCielo->getCon();
    $CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

    $idBC           = $_REQUEST['idBancoCondutor'];
    $idCondutor     = $_REQUEST['idCondutor'];
    $nomeCondutor   = $_REQUEST['nomeCondutor'];
    $saldoIni       = $_REQUEST['saldoIni'];
    $acumulado      = $_REQUEST['acumulado'];
    $saldoAtual     = $_REQUEST['saldoAtual'];
    $idBancoHora    = $_REQUEST['idBancoHora'];
    $dataIniBC      = $_REQUEST['dataIniBC'];
    $dataFimBC      = $_REQUEST['dataFimBC'];
    $minSemBH       = $_REQUEST['minSemBH'];
    $minSabBH       = $_REQUEST['minSabBH'];
    $vencimentoBH   = $_REQUEST['vencimentoBH'];
    $descPeriodo    = $_REQUEST['descPeriodo'];
    $tipoFechamento = $_REQUEST['tipoFechamento'];

    $empresaUsu     = $CtrlAcesso->getUserEmpresa($_SESSION);
    $idEmpresa      = $_REQUEST['idEmpresa'];

    if($idEmpresa == ''){
        $idEmpresa = $empresaUsu;
    }

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

?>
<script>

    var minSemBH                = '<?=$minSemBH?>',
        minSabBH                = '<?=$minSabBH?>',
        hiddenAdicionar         = <?=$hiddenAdicionar?>,
        hiddenAlterarExcluir    = <?=$hiddenAlterarExcluir?>;

    minSemBH = rectime(minSemBH);
    minSabBH = rectime(minSabBH);

    Ext.onReady(function() {
        Ext.define('bancoFechamento', {
            extend: 'Ext.data.Model',
            requires : 'Ext.data.Model',
            fields:[
                {name: 'idBancoFechamento', type: 'int'},
                {name: 'idCondutor', type: 'int'},
                {name: 'nomeCondutor', type: 'string'},
                {name: 'saldoBF', type: 'int'},
                {name: 'saldoIniBF', type: 'int'},
                {name: 'dataIniBF', type: 'string'},
                {name: 'dataFimBF', type: 'string'},
                {name: 'tipoFechamento', type: 'int'},
                {name: 'descTipoFechamentoBF', type: 'string'},
                {name: 'idBancoHoras', type: 'int'},
                {name: 'totalTrabalhadoBF', type: 'int'},
                {name: 'minSemBH', type: 'int'},
                {name: 'minSabBH', type: 'int'},
                {name: 'totalPrevisto', type: 'int'},
                {name: 'mediaTrabDia', type: 'int'},
                {name: 'vencimentoBH', type: 'int'},
                {name: 'descPeriodoBH', type: 'string'},
                {name: 'idEmpresa', type: 'int'},
                {name: 'dataFechamento', type: 'string'},
                {name: 'userNameFechamento', type: 'string'},
                {name: 'obsFechamento', type: 'string'},
                {name: 'mediaTrabSem', type: 'int'},
                {name: 'diasValorDobrado', type: 'int'},
                {name: 'idBancoCondutor', type: 'int'}
            ]
        });

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
                        panelLoad('gestorTabId', 'Banco de Horas', 'relatorios/relBancoCondutor.php', "idEmpresa=<?=$idEmpresa?>");
                    }
                },
                '->',
                {
                    xtype: 'button',
                    text: 'Filtro',
                    style: 'margin-top: 8px;',
                    icon: 'imagens/16x16/search.png',
                    handler: function() {
                        var buttonFiltrar = Ext.create('Ext.Button', {
                            id: 'buttonFiltrar',
                            text: 'Filtrar',
                            icon: 'imagens/16x16/filter.png',
                            handler: function () {
                                console.info('ok');
                            }
                        });

                        var buttonCancelar = Ext.create('Ext.Button', {
                            id: 'buttonCancelar',
                            text: 'Limpar',
                            icon: 'imagens/16x16/clear.png',
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

                        showWindowToolbar(
                            'filterBancoFechamento',
                            'Filtrar Fechamentos',
                            'filter/filterBancoFechamento.php',
                            "idEmpresa=<?=$idEmpresa?>",
                            500,
                            290,
                            true,
                            true,
                            toolbarTeste
                        );
                    }
                }
            ]
        });

        var storeFechamento = Ext.create('Ext.data.Store', {
            model: 'bancoFechamento',
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: 'json/jsonBancoFechamento.php',
                reader: {
                    type: 'json',
                    root: 'bancoFechamento'
                },
                extraParams: {
                    idEmpresa: '<?=$idEmpresa?>',
                    idCondutor: '<?=$idCondutor?>',
                    idBancoHora: '<?=$idBancoHora?>',
                    //tipoFechamento: '<?//=$tipoFechamento?>//',
                    tipoFechamento: '',
                    dataIniBF: '',
                    dataFimBF: '',
                    //dataIniBC: '<?//=$dataIniBC?>//',
                    //dataFimBC: '<?//=$dataFimBC?>//',
                    dataIniBC: '',
                    dataFimBC: '',
                    radioPendente: 'false',
                    radioFechado: 'false',
                    radioTodos: 'true'
                }
            }
        });

        var pdfBancoCondutorFechamento = Ext.create('Ext.Button', {
            text: 'Exportar para PDF',
            style: 'margin-top: 8px;',
            iconCls: 'pdf',
            handler: function() {
                var color = '#E1EEF4',
                    colorSaldoIni,
                    colorTotalTrab,
                    colorSaldoBF,
                    tabela = '';

                tabela +=
                    '<br/>' +
                    '<font size="7">' +
                    '<table id="total" cellpadding="4" cellspacing="2" style="text-align:left;">' +
                    '<thead>' +
                    '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                    '<th>Condutor</th>' +
                    '<th>Inicio</th>' +
                    '<th>Fim</th>' +
                    '<th>Saldo inicial</th>' +
                    '<th>Dias com valor dobrado</th>' +
                    '<th>Pevisto total</th>' +
                    '<th>Total trabalhado</th>' +
                    '<th>Media de trab p/ dia</th>'+
                    '<th>Media de trab p/ semana</th>'+
                    '<th>Saldo final</th>'+
                    '</tr>' +
                    '</thead>';

                storeFechamento.each( function (model) {

                    if(model.get('saldoIniBF') < 0){
                        colorSaldoIni = "#f83223";
                    }else if(model.get('saldoIniBF') == 0){
                        colorSaldoIni = "#32bf0a";
                    }else{
                        colorSaldoIni = "#3338ff";
                    }

                    if(model.get('totalTrabalhadoBF') < 0){
                        colorTotalTrab = "#f83223";
                    }else if(model.get('totalTrabalhadoBF') == 0){
                        colorTotalTrab = "#32bf0a";
                    }else{
                        colorTotalTrab = "#3338ff";
                    }

                    if(model.get('saldoBF') < 0){
                        colorSaldoBF = "#f83223";
                    }else if(model.get('saldoBF') == 0){
                        colorSaldoBF = "#32bf0a";
                    }else{
                        colorSaldoBF = "#3338ff";
                    }

                    tabela += ' <tr bgcolor="'+color+'">' +
                                    '<td>'+ model.get('nomeCondutor')+'</td>' +
                                    '<td>'+ model.get('dataIniBF')+'</td>' +
                                    '<td>'+ model.get('dataFimBF')+'</td>' +
                                    '<td><span style="color: '+colorSaldoIni+'">'+ rectime(model.get('saldoIniBF'), true)+'</span></td>' +
                                    '<td>'+ model.get('diasValorDobrado')+'</td>' +
                                    '<td>'+ rectime(model.get('totalPrevisto'), true)+'</td>' +
                                    '<td><span style="color: '+colorTotalTrab+'">'+ rectime(model.get('totalTrabalhadoBF'), true)+'</span></td>' +
                                    '<td>'+ rectime(model.get('mediaTrabDia'), true)+'</td>' +
                                    '<td>'+ rectime(model.get('mediaTrabSem'), true)+'</td>' +
                                    '<td><span style="color: '+colorSaldoBF+'">'+ rectime(model.get('saldoBF'), true) +'</span></td>' +
                                '</tr>';

                    color = (color == '')?"#E1EEF4":'';
                });

                tabela += '<tbody></tbody></table></font>';

                Ext.getDom('pontoContent').value = tabela;
                Ext.getDom('idEmpresa').value = Ext.getCmp('idEmpresaBH').value;
                Ext.getDom('formExportPrint').action = 'pdf/pdfBancoCondutorFechamento.php';
                Ext.getDom('formExportPrint').submit();
            }
        });

        var grid = Ext.create('Ext.grid.Panel', {
            store: storeFechamento,
            id: 'gridBCFechamento',
            forceFit: true,
            columnLines: true,
            viewConfig: {
                emptyText: '<b>Nenhum registro encontrado</b>',
                deferEmptyText: false,
                getRowClass: function(record, rowIndex, rowParams, store) {
                    return 'gridPointerRow';
                }
            },
            height: Ext.getBody().getHeight()-95,
            columns: [
                {
                    header: 'Condutor',
                    dataIndex: 'nomeCondutor',
                    width: 45
                },{
                    header: 'Início',
                    dataIndex: 'dataIniBF',
                    width: 17
                },
                {
                    header: 'Fim',
                    dataIndex: 'dataFimBF',
                    width: 17
                },
                {
                    header: 'Saldo inicial',
                    dataIndex: 'saldo_ini',
                    width: 20,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('saldoIniBF') == 0){
                            return "<div value='' id='saldoIniBF" + row + "'><span style='color: green;'>"+rectime(record.get('saldoIniBF'), true)+"</span></div>";
                        }else if(record.get('saldoIniBF') < 0){
                            return "<div value='' id='saldoIniBF" + row + "'><span style='color: red;'>"+rectime(record.get('saldoIniBF'), true)+"</span></div>";
                        }else{
                            return "<div value='' id='saldoIniBF" + row + "'><span style='color: blue;'>"+rectime(record.get('saldoIniBF'), true)+"</span></div>";
                        }
                    }
                },
                {
                    header: 'Dias com valor dobrado',
                    dataIndex: 'diasValorDobrado',
                    width: 25
                },
                {
                    header: 'Previsto Total',
                    dataIndex: 'totalPrevisto',
                    width: 15,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return rectime(record.get('totalPrevisto'));
                    }
                },
                {
                    header: 'Total trabalhado',
                    dataIndex: 'totalTrabalhadoBF',
                    width: 20,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        //console.info(record.get('totalTrabalhadoBF') + ' - ' + record.get('totalPrevisto'));
                        if(record.get('totalTrabalhadoBF') > record.get('totalPrevisto')){
                            return "<div value='' id='saldoBF" + row + "'><span style='color: blue;'>"+rectime(record.get('totalTrabalhadoBF'))+"</span></div>";
                        }else if(record.get('totalTrabalhadoBF') < record.get('totalPrevisto')){
                            return "<div value='' id='saldoBF" + row + "'><span style='color: red;'>"+rectime(record.get('totalTrabalhadoBF'))+"</span></div>";
                        }else{
                            return "<div value='' id='saldoBF" + row + "'><span style='color: green;'>"+rectime(record.get('totalTrabalhadoBF'))+"</span></div>";
                        }
                    }
                },
                {
                    header: 'Média de trab p/ dia',
                    dataIndex: 'mediaTrabDia',
                    width: 20,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return rectime(record.get('mediaTrabDia'));
                    }
                },
                {
                    header: 'Média de trab p/ sem',
                    dataIndex: 'mediaTrabDia',
                    width: 20,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return rectime(record.get('mediaTrabSem'));
                    }
                },
                {
                    header: 'Saldo final',
                    dataIndex: 'saldoBF',
                    width: 17,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('saldoBF') == 0){
                            return "<div value='' id='saldoBF" + row + "'><span style='color: green;'>"+rectime(record.get('saldoBF'), true)+"</span></div>";
                        }else if(record.get('saldoBF') < 0){
                            return "<div value='' id='saldoBF" + row + "'><span style='color: red;'>"+rectime(record.get('saldoBF'), true)+"</span></div>";
                        }else{
                            return "<div value='' id='saldoBF" + row + "'><span style='color: blue;'>"+rectime(record.get('saldoBF'), true)+"</span></div>";
                        }
                    }
                },{
                    header: 'Fechamento',
                    xtype: 'actioncolumn',
                    width: 20,
                    align: 'center',
                    renderer: function (val, metadata, record) {
                        //Mostra o ícnoe cancel quando não estiver fechado e CHECK quando estiver fechado pelo usuário
                        if (record.raw.tipoFechamento == null) {
                            this.items[0].icon = 'imagens/16x16/uncheck.png';
                            this.items[0].tooltip = 'Fechar Banco de Horas';
                        } else {
                            this.items[0].icon = 'imagens/16x16/check.png';
                            this.items[0].tooltip = 'Detalhes de Fechamento';
                        }
                    },
                    items: [{
                        handler: function(grid, rowIndex, colIndex) {
                            var buttonSalvar = Ext.create('Ext.Button', {
                                id: 'buttonSalvar',
                                text: 'Salvar',
                                icon: 'imagens/16x16/save.gif',
                                handler: function () {
                                    console.info('ok');
                                }

                            });

                            var buttonCancelar = Ext.create('Ext.Button', {
                                id: 'buttonCancelar',
                                text: 'Cancelar',
                                icon: 'imagens/16x16/cancel.png',
                                handler: function () {
                                    Ext.getCmp('cadFechamento').close();
                                }
                            });

                            var toolbarTeste = Ext.create('Ext.toolbar.Toolbar', {
                                id: 'teste',
                                region: 'south',
                                items: [
                                    '->',
                                    {xtype: buttonCancelar},
                                    {xtype: buttonSalvar}
                                ]
                            });

                            if(grid.getStore().getAt(rowIndex).raw.tipoFechamento == null){
                                showWindowToolbar(
                                    'cadFechamento',
                                    'Fechar banco de horas - '+grid.getStore().getAt(rowIndex).raw.nomeCondutor,
                                    'cad/cadFechamentoBancoHoras.php',
                                    '&idBancoFechamento='+grid.getStore().getAt(rowIndex).raw.idBancoFechamento,
                                    460,
                                    230,
                                    true,
                                    true,
                                    toolbarTeste
                                );
                            }else{
                                showWindow(
                                    'cadFechamento',
                                    'Fechamento  - '+grid.getStore().getAt(rowIndex).raw.nomeCondutor,
                                    'lst/lstCondutorFechamento.php',
                                    '&idBancoFechamento='+grid.getStore().getAt(rowIndex).raw.idBancoFechamento+
                                    '&descTipoFechamentoBF='+grid.getStore().getAt(rowIndex).raw.descTipoFechamentoBF+
                                    '&dataFechamento='+grid.getStore().getAt(rowIndex).raw.dataFechamento+
                                    '&userNameFechamento='+grid.getStore().getAt(rowIndex).raw.userNameFechamento+
                                    '&obsFechamento='+grid.getStore().getAt(rowIndex).raw.obsFechamento,
                                    500,
                                    255,
                                    true,
                                    true
                                );
                            }
                        }
                    }]
                }
            ],
            renderTo: 'contentId',
            bbar: [
                '->',
                {xtype: pdfBancoCondutorFechamento}
            ]
        });

        Ext.create('Ext.panel.Panel', {
            tbar: toolInfo,
            autoWidth: true,
            height: Ext.getCmp('gestorTabId').getHeight(),
            renderTo: 'gestorRelId',
            items: [
                {xtype: grid}
            ]
        });

        grid.getSelectionModel().on('selectionchange', function(sm, selectedRecord) {

            var idBancoFechamento   = selectedRecord[0].get('idBancoFechamento'),
                idCondutor          = selectedRecord[0].get('idCondutor'),
                nomeCondutor        = selectedRecord[0].get('nomeCondutor'),
                dataIniBF           = selectedRecord[0].get('dataIniBF'),
                dataFimBF           = selectedRecord[0].get('dataFimBF'),
                dataIniBC           = '',
                dataFimBC           = '',
                tipoFechamento      = selectedRecord[0].get('tipoFechamento'),
                idBancoHoras        = selectedRecord[0].get('idBancoHoras'),
                totalTrabalhado     = selectedRecord[0].get('totalTrabalhado'),
                minSemBH            = selectedRecord[0].get('minSemBH'),
                minSabBH            = selectedRecord[0].get('minSabBH'),
                totalPrevisto       = selectedRecord[0].get('totalPrevisto'),
                vencimentoBH        = selectedRecord[0].get('vencimentoBH'),
                descPeriodo         = selectedRecord[0].get('descPeriodoBH'),
                saldoBF             = selectedRecord[0].get('saldoBF'),
                totalTrabalhadoBF   = selectedRecord[0].get('totalTrabalhadoBF'),
                mediaTrabDia        = selectedRecord[0].get('mediaTrabDia'),
                mediaTrabSem        = selectedRecord[0].get('mediaTrabSem'),
                diasValorDobrado    = selectedRecord[0].get('diasValorDobrado'),
                saldoIniBF          = selectedRecord[0].get('saldoIniBF'),
                idBancoCondutor     = selectedRecord[0].get('idBancoCondutor'),
                idEmpresa           = selectedRecord[0].get('idEmpresa'),
                origem              = 'bancoFechamento';

            panelLoad('gestorTabId', 'Banco de Horas - Diario', 'relatorios/relBancoCondutorDia.php',
                'idBancoCondutor='+idBancoCondutor+'&' +
                'idCondutor='+idCondutor+'&' +
                'nomeCondutor='+nomeCondutor+'&' +
                'acumulado='+totalTrabalhado+'&' +
                'idBancoHoras='+idBancoHoras+'&' +
                'dataIniBF='+dataIniBF+'&' +
                'dataFimBF=' + dataFimBF + '&'+
                'dataIniBC='+dataIniBC+'&' +
                'dataFimBC='+dataFimBC+'&'+
                'minSemBH='+minSemBH+'&'+
                'minSabBH='+minSabBH+'&'+
                'minTotalBC='+totalPrevisto+'&'+
                'vencimentoBH='+vencimentoBH+'&'+
                "origem="+origem+"&"+
                "saldoBF="+saldoBF+"&"+
                "totalTrabalhadoBF="+totalTrabalhadoBF+"&"+
                "mediaTrabDia="+mediaTrabDia+"&"+
                "mediaTrabSem="+mediaTrabSem+"&"+
                "diasValorDobrado="+diasValorDobrado+"&"+
                "saldoIni="+saldoIniBF+"&"+
                "tipoFechamento="+tipoFechamento+"&"+
                "idBancoFechamento="+idBancoFechamento+"&"+
                "idEmpresa="+idEmpresa+"&"+
                'descPeriodo='+descPeriodo);
        });
    });
</script>

<div id="contentId"></div>
<div id="gestorRelId" style="width: 100%;"></div>

<form id="formExportPrint" method="post" target="print">
    <input type="hidden" id="pontoContent"  name="pontoContent" value=""/>
    <input type="hidden" id="idEmpresa" name="idEmpresa" value=""/>
</form>
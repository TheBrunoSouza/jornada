<?
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo           = new OracleCielo();
    $conexaoOra         = $OraCielo->getCon();
    $ctrlAcesso         = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

    $idBancoCondutor    = $_REQUEST['idBancoCondutor'];
    $idCondutor         = $_REQUEST['idCondutor'];
    $nomeCondutor       = $_REQUEST['nomeCondutor'];
    $saldoIni           = $_REQUEST['saldoIni'];
    $acumulado          = $_REQUEST['acumulado'];
    $saldoAtual         = $_REQUEST['saldoAtual'];
    $idBancoHora        = $_REQUEST['idBancoHoras'];
    $minSemBH           = $_REQUEST['minSemBH'];
    $minSabBH           = $_REQUEST['minSabBH'];
    $minTotalBC         = $_REQUEST['minTotalBC'];
    $descPeriodoBH      = $_REQUEST['descPeriodo'];
    $origem             = $_REQUEST['origem'];
    $saldoBF            = $_REQUEST['saldoBF'];
    $totalTrabalhadoBF  = $_REQUEST['totalTrabalhadoBF'];
    $mediaTrabDia       = $_REQUEST['mediaTrabDia'];
    $mediaTrabSem       = $_REQUEST['mediaTrabSem'];
    $diasValorDobrado   = $_REQUEST['diasValorDobrado'];
    $tipoFechamento     = $_REQUEST['tipoFechamento'];
    $empresaUsu         = $ctrlAcesso->getUserEmpresa($_SESSION);

    if($origem == 'bancoFechamento'){
        $dataIni = $_REQUEST['dataIniBF'];
        $dataFim = $_REQUEST['dataFimBF'];
        $retorno = "relBancoCondutorFechamento.php";
        $title   = "Banco de Horas - Fechamento";
    }else{
        $dataIni = $_REQUEST['dataIniBC'];
        $dataFim = $_REQUEST['dataFimBC'];
        $retorno = "relBancoCondutor.php";
        $title   = "Banco de Horas";
    }
?>
<script>

    var minSemBH                = '<?=$minSemBH?>',
        minSabBH                = '<?=$minSabBH?>',
        minTotalBC              = '<?=$minTotalBC?>',
        hiddenRegerar           = true,
        hiddenRegerarFechado    = true;

    minSemBH    = rectime(minSemBH);
    minSabBH    = rectime(minSabBH);
    minTotalBC  = rectime(minTotalBC);

    if('<?=($retorno == 'relBancoCondutor.php')?true:false?>' == '1'){
        hiddenRegerar = false;
    }else{
        hiddenRegerarFechado = false;
    }
    
    Ext.onReady(function() {
        Ext.define('bancoCondutorDia', {
            extend: 'Ext.data.Model',
            requires : 'Ext.data.Model',
            fields:[
                {name: 'idBancoCondutorDia', type: 'int'},
                {name: 'data', type: 'string'},
                {name: 'idCondutor', type: 'int'},
                {name: 'acumuladoAnt', type: 'int'},
                {name: 'minutosTrab', type: 'int'},
                {name: 'saldo', type: 'int'},
                {name: 'acumuladoTotal', type: 'int'},
                {name: 'acumuladoDia', type: 'int'},
                {name: 'feriado', type: 'string'},
                {name: 'descanso', type: 'string'},
                {name: 'descFeriado', type: 'string'}
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
                        panelLoad(
                            'gestorTabId',
                            '<?=$title?>',
                            'relatorios/<?=$retorno?>',
                            "idEmpresa=<?=$_REQUEST['idEmpresa']?>"
                            //"idCondutor=<?=$idCondutor?>&dataIniBC=<?=$dataIni?>&dataFimBC=<?=$dataFim?>&idBancoHora=<?=$idBancoHora?>&tipoFechamento=<?=$tipoFechamento?>"
                        );
                    } 
                },
                '->',
                {
                    //Regerar quando os dados forem do banco já venceu
                    xtype: 'button',
                    text: 'Regerar',
                    hidden: hiddenRegerarFechado,
                    style: 'margin-top: 8px;',
                    icon: 'imagens/16x16/arrow_refresh.png',
                    handler: function() {
                        Ext.Msg.confirm('Regerar:', 'Ao regerar, o sistema vai verificar se existem informações não processadas de todos os condutores que estão cadastrados neste banco de horas e realizar os cálculos novamente em cima destas. <br><br>Suas observações de fechamento serão perdidas. Deseja continuar?', function (button) {
                            if (button == 'yes') {
                                var mask = new Ext.LoadMask('gestorTabId', {msg: "Regerando..."});
                                mask.show();
                                Ext.Ajax.request({
                                    url: 'exec/execBancoHoras.php',
                                    timeout: 240000,
                                    method: 'POST',
                                    params: {
                                        acao: 'regerarFechado',
                                        idBancoCondutor: '<?=$idBancoCondutor?>',
                                        minSemBH: '<?=$minSemBH?>',
                                        minSabBH: '<?=$minSabBH?>',
                                        minTotalBC: '<?=$minTotalBC?>',
                                        descPeriodoBH: '<?=$descPeriodoBH?>',
                                        dataIni: '<?=$dataIni?>',
                                        dataFim: '<?=$dataFim?>',
                                        idBancoHora: '<?=$idBancoHora?>',
                                        idCondutor: '<?=$idCondutor?>',
                                        saldoBF: rectime('<?=$saldoBF?>', true),
                                        totalTrabalhadoBF: rectime('<?=$totalTrabalhadoBF?>'),
                                        mediaTrabDia: rectime('<?=$mediaTrabDia?>'),
                                        mediaTrabSem: rectime('<?=$mediaTrabSem?>'),
                                        diasValorDobrado: '<?=$diasValorDobrado?>',
                                        saldoIni: rectime('<?=$saldoIni?>', true)
                                    },
                                    success: function (conn, response, options, eOpts) {
                                        mask.hide();
                                        var result = Ext.decode(conn.responseText);
                                        if (result.status == 'ERRO') {
                                            Ext.Msg.show({
                                                title: 'Erro!',
                                                msg: 'Favor informar o departamento de TI.',
                                                icon: Ext.Msg.ERROR,
                                                buttons: Ext.Msg.OK
                                            });
                                        } else {
                                            Ext.Msg.show({
                                                title:'Sucesso!',
                                                msg: result.msg,
                                                icon: Ext.Msg.INFO,
                                                buttons: Ext.Msg.OK
                                            });
                                            storeBancoCondutorDia.load();
                                        }
                                    },
                                    failure: function (conn, response, options, eOpts) {
                                        mask.hide();
                                        Ext.Msg.show({
                                            title: 'Erro!',
                                            msg: 'Entre em contato com o administrador do sistema.',
                                            icon: 'imagens/16x16/accept.png',
                                            buttons: Ext.Msg.OK
                                        });
                                    }
                                });
                            }
                        });
                    }
                },
                {
                    //Regerar quando os dados forem do banco que está executando
                    xtype: 'button',
                    text: 'Regerar',
                    hidden: hiddenRegerar,
                    style: 'margin-top: 8px;',
                    icon: 'imagens/16x16/arrow_refresh_small.png',
                    handler: function() {
                        Ext.Msg.confirm('Regerar:', 'Ao regerar, o sistema vai verificar se existem informações não processadas de todos os condutores que estão cadastrados neste banco de horas e realizar os cálculos novamente em cima destas. <br><br>Deseja continuar?', function (button) {
                            if (button == 'yes') {
                                var mask = new Ext.LoadMask('gestorTabId', {msg: "Regerando..."});
                                mask.show();
                                Ext.Ajax.request({
                                    url: 'exec/execBancoHoras.php',
                                    timeout: 240000,
                                    method: 'POST',
                                    params: {
                                        acao: 'regerar',
                                        idBancoCondutor: '<?=$idBancoCondutor?>',
                                        minSemBH: '<?=$minSemBH?>',
                                        minSabBH: '<?=$minSabBH?>',
                                        minTotalBC: '<?=$minTotalBC?>',
                                        descPeriodoBH: '<?=$descPeriodoBH?>',
                                        dataIni: '<?=$dataIni?>',
                                        dataFim: '<?=$dataFim?>',
                                        idBancoHora: '<?=$idBancoHora?>',
                                        idCondutor: '<?=$idCondutor?>',
                                        saldoBF: rectime('<?=$saldoBF?>', true),
                                        totalTrabalhadoBF: rectime('<?=$totalTrabalhadoBF?>'),
                                        mediaTrabDia: rectime('<?=$mediaTrabDia?>'),
                                        mediaTrabSem: rectime('<?=$mediaTrabSem?>'),
                                        diasValorDobrado: '<?=$diasValorDobrado?>',
                                        saldoIni: rectime('<?=$saldoIni?>', true)
                                    },
                                    success: function (conn, response, options, eOpts) {
                                        mask.hide();
                                        var result = Ext.decode(conn.responseText);
                                        if (result.status == 'ERRO') {
                                            Ext.Msg.show({
                                                title: 'Erro!',
                                                msg: 'Favor informar o departamento de TI.',
                                                icon: Ext.Msg.ERROR,
                                                buttons: Ext.Msg.OK
                                            });
                                        } else {
                                            Ext.Msg.show({
                                                title:'Sucesso!',
                                                msg: result.msg,
                                                icon: Ext.Msg.INFO,
                                                buttons: Ext.Msg.OK
                                            });
                                            storeBancoCondutorDia.load();
                                        }
                                    },
                                    failure: function (conn, response, options, eOpts) {
                                        mask.hide();
                                        Ext.Msg.show({
                                            title: 'Erro!',
                                            msg: 'Entre em contato com o administrador do sistema.',
                                            icon: 'imagens/16x16/accept.png',
                                            buttons: Ext.Msg.OK
                                        });
                                    }
                                });
                            }
                        });
                    }
                }
            ]
        });

        var storeBancoCondutorDia = Ext.create('Ext.data.Store', {
            model: 'bancoCondutorDia',
            autoLoad: false,
            proxy: {
                type: 'ajax',
                url: 'json/jsonBancoCondutorDia.php',
                reader: {
                    type: 'json',
                    root: 'bancoCondutorDia'
                },
                extraParams: {
                    idBancoCondutor: '<?=$idBancoCondutor?>',
                    idCondutor: '<?=$idCondutor?>',
                    dataIni: '<?=$dataIni?>',
                    dataFim: '<?=$dataFim?>',
                    idBancoHoras: '<?=$idBancoHora?>'
                }
            },
            listeners: {
                load: {
                    fn: function(){
                        var total = storeBancoCondutorDia.totalCount;
                        if(total == 0){
                            Ext.Msg.show({
                                title:'Informação:',
                                msg: 'Nenhum registro diário por aqui... <br> <br> Se o banco de horas que está procurando já foi venceu, consulte os Fechamentos.',
                                icon: Ext.Msg.INFO,
                                buttons: Ext.Msg.OK
                            });
                        }
                    }
                }
            }
        });

        var panelGrid1 = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelGrid1',
            title: '<b>Detalhes do Banco de Horas</b>',
            width: '99%',
            height: 150,
            style: 'margin-left: 8px; margin-top: 8px;',
            layout: 'anchor',
            border: true,
            defaults: {
                border: false
            },
            items: [{
                layout: 'column',
                items: [
                    {
                        xtype: 'panel',
                        html: "" +
                            "<table class='x-grid-empty'> " +
                                "<tr align='left'> " +
                                    "<th> Condutor: </th>" +
                                    "<th> <?=$nomeCondutor?></th>" +
                                "</tr> " +
                                "<tr align='left'>" +
                                    "<th> Horas previstas totais: </th> " +
                                    "<th> "+minTotalBC+"</th>" +
                                "</tr> " +
                                "<tr align='left'>" +
                                    "<th> Horas previstas em dias úteis: </th> " +
                                    "<th> "+minSemBH+"</th>"+
                                "</tr> " +
                                "<tr align='left'>" +
                                    "<th> Horas previstas no sábado: </th> " +
                                    "<th> "+minSabBH+"</th>" +
                                "</tr>" +
                                "<tr align='left'>" +
                                    "<th> Período: </th> " +
                                    "<th> <?=$descPeriodoBH?></th>"+
                                "</tr> " +
                                "<tr align='left'>" +
                                    "<th> Início e fim: </th> " +
                                    "<th> <?=$dataIni?> até <?=$dataFim?></th>"+
                                "</tr>"+
                            "</table>",
                        border: false,
                        style: 'margin-top: 1px; margin-bottom: 3px;',
                        id: 'gestorPanelCondutorId'
                    }
                ]
            }]
        });

        var pdfBancoCondutorDia = Ext.create('Ext.Button', {
            text: 'Exportar para PDF',
            style: 'margin-top: 8px;',
            iconCls: 'pdf',
            handler: function() {
                var color = "#E1EEF4",
                    colorFontSaldo,
                    colorFontAcumuladoTotal,
                    colorFontAcumuladoAnt,
                    tabela = '',
                    saldo = '',
                    data,
                    minTrab;

                storeBancoCondutorDia.each( function (model) {

                    if(model.get('saldo') < 0){
                        colorFontSaldo = "#f83223";
                    }else if(model.get('saldo') == 0){
                        colorFontSaldo = "#32bf0a";
                    }else{
                        colorFontSaldo = "#3338ff";
                    }

                    if(model.get('acumuladoTotal') < 0){
                        colorFontAcumuladoTotal = "#f83223";
                    }else if(model.get('acumuladoTotal') == 0){
                        colorFontAcumuladoTotal = "#32bf0a";
                    }else{
                        colorFontAcumuladoTotal = "#3338ff";
                    }

                    if(model.get('acumuladoAnt') < 0){
                        colorFontAcumuladoAnt = "#f83223";
                    }else if(model.get('acumuladoAnt') == 0){
                        colorFontAcumuladoAnt = "#32bf0a";
                    }else{
                        colorFontAcumuladoAnt = "#3338ff";
                    }

                    if(model.get('feriado') == 'T' && model.get('descanso') == 'T'){
                        data = model.get('data') + ' (Feriado/Descanso)';
                        if(model.get('minutosTrab') > 0) {
                            minTrab = rectime(model.get('minutosTrab')) + ' x2';
                        }else{
                            minTrab = rectime(model.get('minutosTrab'));
                        }
                    }else if(model.get('feriado') == 'T'){
                        data = model.get('data') + ' (Feriado)';
                        if(model.get('minutosTrab') > 0) {
                            minTrab = rectime(model.get('minutosTrab')) + ' x2';
                        }else{
                            minTrab = rectime(model.get('minutosTrab'));
                        }
                    }else if(model.get('descanso') == 'T'){
                        if(model.get('minutosTrab') > 0) {
                            minTrab = rectime(model.get('minutosTrab')) + ' x2';
                        }else{
                            minTrab = rectime(model.get('minutosTrab'));
                        }
                        data = model.get('data') + ' (Descanso)';
                    }else{
                        minTrab = rectime(model.get('minutosTrab'));
                        data = model.get('data');
                    }

                    tabela += '' +
                        '<tr bgcolor="' + color + '">' +
                            '<td>'+ data + '</td>' +
                            '<td><span style="color: '+colorFontAcumuladoAnt+'">'+ rectime(model.get('acumuladoAnt'), true) + '</span></td>' +
                            '<td>'+ minTrab + '</td>' +
                            '<td><span style="color: '+colorFontAcumuladoTotal+'">'+ rectime(model.get('acumuladoTotal'), true) + '</span></td>' +
                            '<td><span style="color: '+colorFontSaldo+'">'+ rectime(model.get('saldo'), true) +'</span></td>' +
                        '</tr>';

                    color = (color == '')?"#E1EEF4":'';
                });

                Ext.getDom('pontoContent').value        = tabela;
                Ext.getDom('nomeCondutor').value        = '<?=$nomeCondutor?>';
                Ext.getDom('minSemBH').value            = rectime('<?=$minSemBH?>');
                Ext.getDom('minSabBH').value            = rectime('<?=$minSabBH?>');
                Ext.getDom('minTotalBC').value          = rectime('<?=$minTotalBC?>');
                Ext.getDom('descPeriodoBH').value       = '<?=$descPeriodoBH?>';
                Ext.getDom('dataIni').value             = '<?=$dataIni?>';
                Ext.getDom('dataFim').value             = '<?=$dataFim?>';
                Ext.getDom('idBancoHoras').value        = '<?=$idBancoHora?>';
                Ext.getDom('idCondutor').value          = '<?=$idCondutor?>';
                Ext.getDom('saldoBF').value             = rectime('<?=$saldoBF?>', true);
                Ext.getDom('totalTrabalhadoBF').value   = rectime('<?=$totalTrabalhadoBF?>');
                Ext.getDom('mediaTrabDia').value        = rectime('<?=$mediaTrabDia?>');
                Ext.getDom('mediaTrabSem').value        = rectime('<?=$mediaTrabSem?>');
                Ext.getDom('diasValorDobrado').value    = '<?=$diasValorDobrado?>';
                Ext.getDom('saldoIni').value            = rectime('<?=$saldoIni?>', true);
                Ext.getDom('formExportPrint').action    = 'pdf/pdfBancoCondutorDia.php';
                Ext.getDom('formExportPrint').submit();
            }
        });
	
        var grid = Ext.create('Ext.grid.Panel', {
            store: storeBancoCondutorDia,
            id: 'gridRelDesloc',
            forceFit: true,
            columnLines: true,
            viewConfig: {
                emptyText: '<b>Aguarde o final do dia para acompanhar o registro diário do condutor</b>',
                deferEmptyText: false,
                getRowClass: function(record, rowIndex, rowParams, store) {
                    if(record.get('feriado') == 'T' && record.get('descanso') == 'T'){
                        return 'feriadoBancoHoras';
                    }else if(record.get('feriado') == "T"){
                        return 'feriadoBancoHoras';
                    }else if(record.get('descanso') == "T"){
                        return 'feriadoBancoHoras';
                    }
                }
            },
            height: Ext.getBody().getHeight()-263,
            columns: [
                { 
                    header: 'Data',
                    dataIndex: 'data',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('feriado') == 'T' && record.get('descanso') == 'T'){
                            return "<div value='' id='ex" + row + "'><span>"+record.get('data')+" (Feriado/Descanso)</span></div>";
                        }else if(record.get('feriado') == 'T'){
                            return "<div value='' id='ex" + row + "'><span>"+record.get('data')+" (Feriado)</span></div>";
                        }else if(record.get('descanso') == 'T'){
                            return "<div value='' id='ex" + row + "'><span>"+record.get('data')+" (Descanso)</span></div>";
                        }else{
                            return "<div value='' id='ex" + row + "'><span>"+record.get('data')+"</span></div>";
                        }
                    }
                },{
                    header: 'Acumulado Anterior',
                    dataIndex: 'acumuladoAnt',
                    renderer: function (value, metaData, record, row, col, store, gridView) {

                        if(record.get('acumuladoAnt') == 0){
                            return "<div value='' id='acumuladoAnt" + row + "'><span style='color: green;'>"+rectime(record.get('acumuladoAnt'), true)+"</span></div>";
                        }else if(record.get('acumuladoAnt') < 0){
                            return "<div value='' id='acumuladoAnt" + row + "'><span style='color: red;'>"+rectime(record.get('acumuladoAnt'), true)+"</span></div>";
                        }else{
                            return "<div value='' id='acumuladoAnt" + row + "'><span style='color: blue;'>"+rectime(record.get('acumuladoAnt'), true)+"</span></div>";
                        }
                    }
                },{
                    header: 'Total Trabalhado no Dia',
                    dataIndex: 'minutosTrab',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('minutosTrab') != '0') {
                            if (record.get('feriado') == 'T' || record.get('descanso') == 'T') {
                                return rectime(record.get('minutosTrab')) + " x2";
                            }else{
                                return rectime(record.get('minutosTrab'));
                            }
                        }else{
                            return rectime(record.get('minutosTrab'));
                        }
                    }
                },{
                    header: 'Acumulado no Dia',
                    dataIndex: 'acumuladoDia',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                            return rectime(record.get('acumuladoDia'));
                    }
                },
                {
                    header: 'Acumulado Total',
                    dataIndex: 'acumuladoTotal',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('acumuladoTotal') == 0){
                            return "<div value='' id='acumuladoTotal" + row + "'><span style='color: green;'>"+rectime(record.get('acumuladoTotal'), true)+"</span></div>";
                        }else if(record.get('acumuladoTotal') < 0){
                            return "<div value='' id='acumuladoTotal" + row + "'><span style='color: red;'>"+rectime(record.get('acumuladoTotal'), true)+"</span></div>";
                        }else{
                            return "<div value='' id='acumuladoTotal" + row + "'><span style='color: blue;'>"+rectime(record.get('acumuladoTotal'), true)+"</span></div>";
                        }
                    }
                },
                {
                    header: 'Saldo do Dia',
                    dataIndex: 'saldo',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('saldo') == 0){
                            return "<div value='' id='saldo" + row + "'><span style='color: green;'>"+rectime(record.get('saldo'), true)+"</span></div>";
                        }else if(record.get('saldo') < 0){
                            return "<div value='' id='saldo" + row + "'><span style='color: red;'>"+rectime(record.get('saldo'), true)+"</span></div>";
                        }else{
                            return "<div value='' id='saldo" + row + "'><span style='color: blue;'>"+rectime(record.get('saldo'), true)+"</span></div>";
                        }
                    }
                }
            ],
            renderTo: 'contentId',
            bbar: [
                '->',
                {xtype: pdfBancoCondutorDia}
            ]
        });

        Ext.create('Ext.panel.Panel', {
            tbar: toolInfo,
            autoWidth: true,
            height: Ext.getCmp('gestorTabId').getHeight(),
            renderTo: 'gestorRelId',
            items: [
                {xtype: panelGrid1},
                {xtype: grid}
            ]
        });
        storeBancoCondutorDia.load();
    });
</script>	

<div id="contentId"></div>
<div id="gestorRelId" style="width: 100%;"></div>

<form id="formExportPrint" method="post" action="pdf/pdfMovimentacoes.php" target="print">
    <input type="hidden" id="pontoContent"  name="pontoContent" value=""/>
    <input type="hidden" id="nomeCondutor" name="nomeCondutor" value=""/>
    <input type="hidden" id="minSemBH" name="minSemBH" value=""/>
    <input type="hidden" id="minSabBH" name="minSabBH" value=""/>
    <input type="hidden" id="minTotalBC" name="minTotalBC" value=""/>
    <input type="hidden" id="descPeriodoBH" name="descPeriodoBH" value=""/>
    <input type="hidden" id="dataIni" name="dataIni" value=""/>
    <input type="hidden" id="idBancoHoras" name="idBancoHoras" value=""/>
    <input type="hidden" id="idCondutor" name="idCondutor" value=""/>
    <input type="hidden" id="dataFim" name="dataFim" value=""/>
    <input type="hidden" id="saldoBF" name="saldoBF" value=""/>
    <input type="hidden" id="totalTrabalhadoBF" name="totalTrabalhadoBF" value=""/>
    <input type="hidden" id="mediaTrabDia" name="mediaTrabDia" value=""/>
    <input type="hidden" id="mediaTrabSem" name="mediaTrabSem" value=""/>
    <input type="hidden" id="diasValorDobrado" name="diasValorDobrado" value=""/>
    <input type="hidden" id="saldoIni" name="saldoIni" value=""/>
</form>
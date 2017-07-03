<?php
    header('Content-type: text/html; charset=utf-8');

    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo   = new OracleCielo();
    $conexao    = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
?>
<script type="text/javascript">
    var dateJornada = new Ext.form.DateField({
        id: 'idDateJornada',
        format: "d/m/Y",
        style: 'margin-top: 8px;',
        width: 100,
        value: <?=($_REQUEST['dataSel']) ? "new Date('".$_REQUEST['dataSel']."')" : 'new Date()'?>,
        maxValue: new Date(),
        listeners: {
            'select': function(combo, value) {
                drawChart(Ext.Date.format(value, 'Y-m-d'), '<?=$_REQUEST['idCondutor']?>');
            }
        }
    });

    Ext.define('Eventos', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idEvento', type: 'int'},
            {name: 'descEvento', type: 'string'},
            {name: 'dtHrEvento', type: 'string'},
            {name: 'plcEvento', type: 'string'},
            {name: 'nmCondutor', type: 'string'}
        ]
    });

    var storeEventos = Ext.create('Ext.data.Store', {
        model: 'Eventos',
        autoLoad : true,
        pageSize: 30,
        proxy: {
            type: 'ajax',
            api: {
                read: 'json/jsonEventos.php',
                destroy: 'json/jsonExcluiEventoCondutor.php'
            },
            reader: {
                type: 'json',
                root: 'eventos',
                totalProperty: 'total',
                successProperty: 'success',
                messageProperty: 'msg'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                encode: true,
                root: 'alertas'
            },
            listeners: {
                exception: function(proxy, response, operation){
                    storeEventos.reload();

                    //mostra msg de erro da exclusão pelo sync
                    Ext.MessageBox.show({
                        title: 'Alerta',
                        msg: operation.getError(),
                        icon: Ext.MessageBox.WARNING,
                        buttons: Ext.Msg.OK
                    });
                }
            },
            extraParams: {
                'dtIni': Ext.Date.format(Ext.getCmp('idDateJornada').value, 'dmY'),
                'dtFim': Ext.Date.format(Ext.getCmp('idDateJornada').value, 'dmY'),
                'idCondutor': '<?=$_REQUEST['idCondutor']?>',
                'idEmpresa':  '<?=$empresaUsu?>'
            }
        },
        fields: [
            {name: 'idEvento', type: 'int'},
            {name: 'descEvento', type: 'string'},
            {name: 'dtHrEvento', type: 'string'},
            {name: 'plcEvento', type: 'string'},
            {name: 'nmCondutor', type: 'string'}
        ]
    });

    var relEventosGrid =  Ext.create('Ext.grid.Panel', {
        id: 'relEventosId',
        viewConfig: {
            emptyText: '<b>Nenhum registro de jornada encontrado para este dia</b>',
            deferEmptyText: false
        },
        style: 'margin-bottom: 30px;',
        store: storeEventos,
        autoWidth: true,
        border: false,
        forceFit: true,
        height: 150,
        columns: [
            { text: 'idEvento',  dataIndex: 'idEvento', hidden: true},
            { text: 'Descrição', dataIndex: 'descEvento', menuDisabled: true},
            { text: 'Data Hora',  dataIndex: 'dtHrEvento'},
            { text: 'Placa', dataIndex: 'plcEvento'},
            { text: 'Condutor', dataIndex: 'nmCondutor'}
        ]
    });

    var buttonRegerar = Ext.create('Ext.Button', {
        itemId: 'idButtonRegerar',
        text: 'Regerar Dia',
        iconCls: 'refresh',
        handler: function() {
            Ext.Msg.confirm('Atenção:', 'Você deseja realmente regerar este dia?', function(buttonText) {
                if (buttonText == "yes") {
                    var response = Ext.Ajax.request({
                        url: 'jobs/execRegeraJornada.php',
                        timeout: 240000,
                        method: 'GET',
                        params: {
                            condutor: '<?=$_REQUEST['idCondutor']?>',
                            empresa: '<?=$empresaUsu?>',
                            dataGeracao: Ext.Date.format(Ext.getCmp('idDateJornada').value, 'Y-m-d')
                        },
                        success: function(){    //
                            drawChart(Ext.Date.format(dateJornada.getValue(), 'Y-m-d'), '<?=$_REQUEST['idCondutor']?>');

                            storeEventos.load({
                                params:{
                                    'dtIni': Ext.Date.format(Ext.getCmp('idDateJornada').value, 'dmY'),
                                    'dtFim': Ext.Date.format(Ext.getCmp('idDateJornada').value, 'dmY'),
                                    'idCondutor': '<?=$_REQUEST['idCondutor']?>'
                                },
                                callback:function(records, operation, success) {
                                }
                            });
                        },
                        failure: function(){console.log('failure');}
                    });
                    //console.log(response.responseText);				
                }
            });
        }
    });

    var toolbarJornada = Ext.create('Ext.toolbar.Toolbar', {
        id: 'toolbarChartId',
        region: 'north',
        items: [
            {
                xtype: 'button',
                iconCls: 'page-prev',
                handler: function() {
                    var dt = dateJornada.getValue();
                    dateJornada.setValue(Ext.Date.add(dt, Ext.Date.DAY, -1));
                    drawChart(Ext.Date.format(dateJornada.getValue(), 'Y-m-d'), '<?=$_REQUEST['idCondutor']?>');
                    storeEventos.load({
                        params:{
                            'dtIni': Ext.Date.format(Ext.getCmp('idDateJornada').value, 'dmY'),
                            'dtFim': Ext.Date.format(Ext.getCmp('idDateJornada').value, 'dmY'),
                            'idCondutor': '<?=$_REQUEST['idCondutor']?>'
                        },
                        callback:function(records, operation, success) {
                        }
                    });
                }
            },{
                xtype: dateJornada
            },{
                xtype: 'button',
                iconCls: 'page-next',
                handler: function() {

                    var dt          = dateJornada.getValue(),
                        dtAux1      = new Date(),
                        dtLoadStore = Ext.Date.add(dt, Ext.Date.DAY, +1);

                    if(dt.getDate() == dtAux1.getDate()){
                        Ext.Msg.show({
                            title:'Informação:',
                            msg: 'Não há dados para o próximo dia.',
                            icon: Ext.Msg.INFO,
                            buttons: Ext.Msg.OK
                        });
                    }else{
                        storeEventos.load({
                            params:{
                                'dtIni': Ext.Date.format(dtLoadStore, 'dmY'),
                                'dtFim': Ext.Date.format(dtLoadStore, 'dmY'),
                                'idCondutor': '<?=$_REQUEST['idCondutor']?>'
                            },
                            callback:function(records, operation, success) {
                            }
                        });
                        dateJornada.setValue(Ext.Date.add(dt, Ext.Date.DAY, 1));
                        drawChart(Ext.Date.format(dateJornada.getValue(), 'Y-m-d'), '<?=$_REQUEST['idCondutor']?>');
                    }
                }
            },
            '->',
            {xtype: buttonRegerar}
        ]
    });

    Ext.onReady( function() {

        Ext.create('Ext.panel.Panel', {
            tbar: toolbarJornada,
            autoWidth: true,
            height: Ext.getCmp('gestorTabId').getHeight(),
            renderTo: 'panelJornadaDiv',
            items: [
                {
                    xtype: 'panel',
                    html: '<p class="x-grid-empty"> <b> Condutor: <?=$_REQUEST['idCondutor']." - ".$_REQUEST['nmCondutor']?> </b> </p>',
                    border: false,
                    style: 'margin-top: 8px; margin-bottom: 8px;',
                    id: 'gestorPanelCondutorId'
                },{
                        xtype: relEventosGrid
                },{
                    xtype: 'panel',
                    border: false,
                    id: 'gestorPanelChartId',
                    height: 380,
                    html: '<br><div id=\"gestorPanelChart\" style=\"width: 100%; height: 100%;\"></div>',
                },{
                    xtype: 'panel',
                    border: false,
                    id: 'gestorPanelTitleId'
                },
            ]
        });
    });

    google.setOnLoadCallback(drawChart(Ext.Date.format(dateJornada.getValue(), 'Y-m-d'), '<?=$_REQUEST['idCondutor']?>'));
</script>
<div id="panelJornadaDiv" style="width: 100%;"></div>

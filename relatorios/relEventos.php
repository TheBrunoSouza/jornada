<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');
    require_once('../includes/execute.class.php');

    $OraCielo   = new OracleCielo();
    $conexao    = $OraCielo->getCon();
    $ExecClass  = new ExecClass($conexaoOra);
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
?>

<script>
    Ext.define('Eventos', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idEvento', type: 'int'},
            {name: 'descEvento', type: 'string'},
            {name: 'dtHrEvento', type: 'string'},
            {name: 'plcEvento', type: 'string'},
            {name: 'nmCondutor', type: 'string'},
            {name: 'nmEmpresa', type: 'string'}
        ]
    });

    var storeEventos = Ext.create('Ext.data.Store', {
        model: 'Eventos',
        autoLoad : false,
        pageSize: 30,
        proxy: {
            type: 'ajax',
            timeout: 550000,
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
                exception: function(proxy, response, operation) {
                    //mostra msg de erro da exclusão pelo sync
                    storeEventos.reload();
                    Ext.MessageBox.show({
                        title: 'Alerta',
                        msg: operation.getError().statusText,
                        icon: Ext.MessageBox.WARNING,
                        buttons: Ext.Msg.OK
                    });
                }
            },
            extraParams: {
                //idEmpresa: '<?=$empresaUsu?>',
                origem: 'grid'
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

    var buttonFiltro = Ext.create('Ext.Button', {
        xtype: 'button',
        text: 'Filtrar',
        iconCls: 'filter',
        style: 'margin-top: 6px;',
        handler: function() {
            showWindow(
                'filtro_eventos',
                'Filtrar Eventos',
                'filter/filEventos.php',
                '',
                470,
                308,
                true,
                true
            );
        }
    });

    var buttonExportar = Ext.create('Ext.Button', {
        xtype: 'button',
        text: 'Exportar',
        iconCls: 'pdf',
        handler: function() {
            var color,
                tabela  = '',
                x = 0,
                nomeEmpresa = '',
                nomeCondutor = '';

            storeEventos.each( function (model) {
                tabela += '<tr bgcolor="'+color+'">';
                tabela += '<td>'+ model.get('descEvento') + '</td>';
                tabela += '<td>'+ model.get('dtHrEvento') + '</td>';
                tabela += '<td>'+ model.get('plcEvento') + '</td>';
                tabela += '<td>'+ model.get('nmCondutor') + '</td>';
                tabela += '</tr>';
                x++;
                color = (color == '') ? "#E1EEF4" : '';
                nomeEmpresa = model.get('nmEmpresa');
                nomeCondutor = model.get('nmCondutor');
            });

            if(x > 0) {
                Ext.getDom('relContent').value = tabela;
                Ext.getDom('totalEventos').value = x;
                Ext.getDom('nomeEmpresa').value = nomeEmpresa;
                Ext.getDom('nomeCondutor').value = nomeCondutor;
                Ext.getDom('exportEvent').action = 'pdf/pdfEventos.php';
                Ext.getDom('exportEvent').submit();
            } else {
                Ext.Msg.show({
                    title: 'Atenção:',
                    msg: 'Primeiro faça uma consulta',
                    icon: Ext.Msg.INFO,
                    buttons: Ext.Msg.OK
                });
            }
        }
    });

    var toolbar = Ext.create('Ext.toolbar.Toolbar', {
        id: 'toolbarRelId',
        region: 'north',
        items: [
            {xtype: buttonExportar},
            {xtype: buttonFiltro}
        ]
    });
			  
    Ext.onReady(function () {
        showWindow(
            'filtro_eventos',
            'Filtrar Eventos',
            'filter/filEventos.php',
            '',
            470,
            308,
            true,
            true
        );

        Ext.create('Ext.grid.Panel', {
            tbar: toolbar,
            id: 'relEventosId',
            viewConfig: {
                emptyText: '<b>Nenhum registro de evento encontrado para este filtro</b>',
                deferEmptyText: false
            },
            columnLine: true,
            layout: 'fit',
            title: '',
            store: storeEventos,
            autoWidth: true,
            renderTo: 'gestorRelId',
            forceFit: true,
            autoScroll: true,
            height: Ext.getBody().getHeight()-59,
            columns: [
                { text: 'idEvento',  dataIndex: 'idEvento', hidden: true},
                { text: 'Descrição', dataIndex: 'descEvento', menuDisabled: true},
                { text: 'Data Hora',  dataIndex: 'dtHrEvento'},
                { text: 'Placa', dataIndex: 'plcEvento'},
                { text: 'Condutor', dataIndex: 'nmCondutor'}
            ] 
        });
    });

</script>

<div id="gestorRelId" style="width: 100%; height: 100%;"></div>

<form id="exportEvent" method="post" action="pdf/pdfEventos.php" target="print">
    <input type="hidden" id="relContent" name="relContent" value=""/>
    <input type="hidden" id="nomeEmpresa" name="nomeEmpresa" value=""/>
    <input type="hidden" id="nomeCondutor" name="nomeCondutor" value=""/>
    <input type="hidden" id="dataIniRel" name="dataIniRel" value=""/>
    <input type="hidden" id="dataFimRel" name="dataFimRel" value=""/>
    <input type="hidden" id="totalEventos" name="totalEventos" value=""/>
</form>
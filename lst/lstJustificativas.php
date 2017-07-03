<?php
require_once('../includes/Controles.class.php');

$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
?>

<script>    
Ext.define('modelJornadaLog', {
    extend: 'Ext.data.Model',
    requires : 'Ext.data.Model',
    fields:[
        {name: 'idJornadaLog', type: 'int'}, 
        {name: 'descricaoSituacao', type: 'string'}, 
        {name: 'dataIni', type: 'date', dateFormat: 'H:i'},  
        {name: 'dataFim', type: 'date', dateFormat: 'H:i'},
        {name: 'tempo', type: 'string', dateFormat: 'H:i'},
        {name: 'dataDb', type: 'date'}, 
        {name: 'idCondutor', type: 'int'},
        {name: 'placa', type: 'string'},
        {name: 'dataAlteracao', type: 'date'},
        {name: 'idUsuario', type: 'int'},
        {name: 'nomeUsuario', type: 'string'},
        {name: 'idSituacaoAnterior', type: 'int'},
        {name: 'idJornadaAnterior', type: 'int'},
        {name: 'descricao', type: 'string'},
        {name: 'modificacao', type: 'string'}
    ]
});

var storeJorAntes = Ext.create('Ext.data.Store', {
    model: 'modelJornadaLog',
    autoLoad: false,
    proxy: {
        type: 'ajax',
        api: {
            read: 'json/jsonJustPonto.php'
        },
        reader: {
            type: 'json',
            root: 'logJornada'
        },
        //extraParams:{
            //idCondutor: '<?=$_REQUEST['idCondutor']?>',
            //dtEdt: '<?=$_REQUEST['dtDiario']?>',
            //momento: 'antes'
        //},
        listeners: {
            exception: function(proxy, response, operation){
                console.info('Identificado: ' + operation.error);
                Ext.MessageBox.show({
                    title: 'Erro!',
                    msg: 'Por favor, informe o departamento de TI.',
                    icon: Ext.MessageBox.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        }
    },
    listeners: {
        load: function(store, operation, success){
            Ext.get('idDescJustificativa').update(operation[0].data.descricao + ' Por: <b>' + operation[0].data.nomeUsuario + '<b>');
        }
    }
});

var storeJorDepois = Ext.create('Ext.data.Store', {
    model: 'modelJornadaLog',
    autoLoad: false,
    proxy: {
        type: 'ajax',
        api: {
            read: 'json/jsonJustPonto.php'
        },
        reader: {
            type: 'json',
            root: 'logJornada'
        },
        //extraParams:{
            //idCondutor: '<?=$_REQUEST['idCondutor']?>',
            //dtEdt: '<?=$_REQUEST['dtDiario']?>',
            //momento: 'depois'
        //},
        listeners: {
            exception: function(proxy, response, operation){
                console.info('Identificado erro: ' + operation.error);
                Ext.MessageBox.show({
                    title: 'Erro!',
                    msg: 'Por favor, informe o departamento de TI.',
                    icon: Ext.MessageBox.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        }
    }
});

Ext.define('Alteracoes', {
    extend: 'Ext.data.Model',
    requires : 'Ext.data.Model',
    fields:[
        {name: 'dataAlteracao', type: 'string'}
    ]
});

var storeAlteracoes = Ext.create('Ext.data.Store', {
    model: 'Alteracoes',
    autoLoad : true,
    proxy: {
        type: 'ajax',
        url: 'json/jsonJustPonto.php',
        reader: {
            type: 'json',
            root: 'alteracoes'
        },
        extraParams: {
            idCondutor: '<?=$_REQUEST['idCondutor']?>',
            dtEdt: '<?=$_REQUEST['dtDiario']?>',
            acao: 'consultaAlteracoes'
        }
    }
});

Ext.onReady(function () {
    var mask = new Ext.LoadMask('show_justificativas', {msg: "Carregando Histórico..."});
    mask.show();
    
    var gridAntes = Ext.create('Ext.grid.Panel', {
        id: 'idGridAntes',
        //style: 'margin-left: 4px; margin-bottom: 4px;',
        width: 450,
        store: storeJorAntes,
        height: 300,
        viewConfig: {
            emptyText: 'Nenhum Registro Encontrado'
        },
        columns: [
            {
                header: 'Situação',  
                dataIndex: 'descricaoSituacao'
            },{ 
                header: 'Hora Início',
                dataIndex: 'dataIni',
                renderer: formatHour,
                editor: {
                    xtype: 'timefield',
                    allowBlank: false
                }
            },{
                header: 'Hora Fim',
                dataIndex: 'dataFim',
                renderer: formatHour,
                editor: {
                    xtype: 'timefield',
                    allowBlank: false
                }
            },{
                header: 'Tempo',
                dataIndex: 'tempo'
            }
        ],
        bbar: [
            {xtype: 'tbtext', id: 'idTotalJustAntes', height: 20, text: ' '}   
        ],
        renderTo: 'contentIds',
        forceFit: true
    });
    
    var gridDepois = Ext.create('Ext.grid.Panel', {
        id: 'idGridDepois',
        style: 'margin-left: 4px; margin-bottom: 4px;',
        width: 450,
        height: 300,
        store: storeJorDepois,
        viewConfig: {
            emptyText: 'Nenhum Registro Encontrado'
        },
        columns: [
            {
                header: 'Situação',  
                dataIndex: 'descricaoSituacao'
            },{ 
                header: 'Hora Início',
                dataIndex: 'dataIni',
                renderer: formatHour,
                editor: {
                    xtype: 'timefield',
                    allowBlank: false,
                    format: 'H:i'
                }
            },{
                header: 'Hora Fim',
                dataIndex: 'dataFim',
                renderer: formatHour,
                editor: {
                    xtype: 'timefield',
                    allowBlank: false,
                    format: 'H:i'
                }
            },{
                header: 'Tempo',
                dataIndex: 'tempo'
            }
        ],
        bbar: [
            {xtype: 'tbtext', id: 'idTotalJustDepois', height: 20, text: ' '}   
        ],
        renderTo: 'contentIds',
        forceFit: true
    });
    
    var panelGrid1 = Ext.create('Ext.form.FieldSet', {
        id: 'idPanelGrid1',
        bodyPadding: 5,
        title: '<b>Antes</b>',
        width: '50%',
        layout: 'anchor',
        border: false,
        defaults: {
            border: false
        },
        items: [{
            layout: 'column',
            items: [
                {xtype: gridAntes}        
            ]
        }]
    });
    var panelGrid2 = Ext.create('Ext.form.FieldSet', {
        id: 'idPanelGrid2',
        bodyPadding: 5,
        title: '<b>Depois</b>',
        width: '50%',
        layout: 'anchor',
        border: false,
        defaults: {
            border: false
        },
        items: [{
            layout: 'column',
            items: [
                {xtype: gridDepois}
            ]
        }]
    });
    
    var justificativa = Ext.create('Ext.form.FieldSet', {
        id: 'idJustificativa',
        bodyPadding: 5,
        title: '<b>Justificativa</b>',
        width: '100%',
        //height: '100%',
        layout: 'anchor',
        defaults: {
            border: false
        },
        items: [
             {
                xtype: 'fieldset',
                id: 'idDescJustificativa',
                title: '-',
                width: '100%',
                layout: 'anchor',
                defaults: {
                    border: false
                }
            }
        ]
    });
    
    var comboAlteracoes = Ext.create('Ext.form.ComboBox', {
        id: 'idComboAlteracoes',
        fieldLabel: 'Alterações:',
        labelWidth: 70,
        width: 360,
        style: 'margin-top: 8px; margin-left: 14px',
        queryMode: 'local',
        displayField: 'dataAlteracao',
        valueField: 'dataAlteracao',
        store: storeAlteracoes,
        emptyText: 'Selecione uma data para carregar o histórico...',
        listeners:{
            select: function(f, r, i){
                reloadStore(f.value);
            }
        }
    });
    
    Ext.create('Ext.form.Panel', {
        id: 'id',
        bodyPadding: 5,
        width: '100%',
        height: '100%',
        layout: 'anchor',
        defaults: {
            anchor: '100%',
            border: false
        },
        items: [
            {
                layout: 'column',
                items: [
                    {xtype: panelGrid1},
                    {xtype: panelGrid2}
                ]
            },
            {xtype: justificativa},
            {xtype: comboAlteracoes}
        ],
        renderTo: 'contentIds'
    });
    reloadStore(null);
    mask.hide();
});

function formatHour(value){
    return value ? Ext.Date.dateFormat(value, 'H:i') : 'Não Finalizada';
}

function reloadStore(data){

    var mask2 = new Ext.LoadMask('show_justificativas', {msg: "Carregando..."});
    mask2.show();

    if(data == null){
        data = '';
    }
    
    storeJorAntes.load({
        callback: function(records, operation, success) {
            var result = Ext.decode(operation.response.responseText);
            
            Ext.getCmp('idTotalJustAntes').setText(
                'Total Jornada: '       + rectime(result.totalJornada)  + ' | '   +
                'Total Espera: '        + rectime(result.totalEspera)   + ' | '   +
                'Total Extra: '         + rectime(result.totalExtra)    + ' | '   +
                'Total Extra 100%: '    + rectime(result.totalExtra100)
            );
            mask2.hide();
        },
        params: {
            dataAlteracao: data,
            idCondutor: '<?=$_REQUEST['idCondutor']?>',
            dtEdt: '<?=$_REQUEST['dtDiario']?>',
            momento: 'antes'
        }
    });
    
    storeJorDepois.load({
        callback: function(records, operation, success) {
            var result = Ext.decode(operation.response.responseText);
            
            Ext.getCmp('idTotalJustDepois').setText(
                'Total Jornada: '       + rectime(result.totalJornada)  + ' | '   +
                'Total Espera: '        + rectime(result.totalEspera)   + ' | '   +
                'Total Extra: '         + rectime(result.totalExtra)    + ' | '   +
                'Total Extra 100%: '    + rectime(result.totalExtra100)
            );
            mask2.hide();
        },
        params: {
            dataAlteracao: data,
            idCondutor: '<?=$_REQUEST['idCondutor']?>',
            dtEdt: '<?=$_REQUEST['dtDiario']?>',
            momento: 'depois'
        }
    });
}
</script>

<div id="contentIds" ></div>
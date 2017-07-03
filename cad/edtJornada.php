<?
require_once('../includes/Controles.class.php');

$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
?>

<script>
    
function formatHour(value){
    return value ? Ext.Date.dateFormat(value, 'H:i') : 'Não Finalizada';
}

function validaCampos(hrIni, hrFim, situacao){
    if(Ext.isEmpty(hrIni))
        return "Hora inicial inválida!";
    else if(Ext.isEmpty(hrFim))
        return "Hora final inválida!";
    else if(Ext.isEmpty(situacao))
        return "A Situação Deve Ser Definida!";
    else
        return "";
}

Ext.Loader.setConfig({
    enabled: true
});

Ext.Loader.setPath('Ext.ux', '../ux');

Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.util.*',
    'Ext.state.*',
    'Ext.form.*',
    'Ext.ux.CheckColumn'
]);

Ext.onReady(function(){
    
    Ext.define('modelSituacao', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idJornada', type: 'int'}, 
            {name: 'situacaoID', type: 'int'}, 
            {name: 'sitDescricao', type: 'string'}, 
            {name: 'diarioHrIni', type: 'date', dateFormat: 'H:i'}, 
            {name: 'diarioHrFim', type: 'date', dateFormat: 'H:i'}, 
            {name: 'diarioTempo', type: 'string', dateFormat: 'H:i'},
            {name: 'idCondutor', type: 'int'},
            {name: 'observacao', type: 'string'},
            {name: 'situacaoAnterior', type: 'string'},
            {name: 'jornadaAnterior', type: 'string'}
        ]
    }); 

    var storeSituacoes = Ext.create('Ext.data.Store', {
        fields: ['idSituacao', 'descSituacao'],
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonSituacoes.php',
            reader: {
                type: 'json',
                root: 'situacoes'
            },
            extraParams:{
                tpSituacao:'jornada'
            }
        },
        listeners: { 
            beforeload: function(store, operation){
                store.sort('descSituacao', 'ASC');
            },
            load: function(store, operation, success){
                if(success) storeJornada.load();
            }
        }
    });
    
    var justificativaAtual = "",
        maskT;
    
    var storeJornada = Ext.create('Ext.data.Store', {
        model: 'modelSituacao',
        autoLoad: false,
        autoDestroy: true,
        proxy: {
            type: 'ajax',
            api: {
                read: 'json/jsonEdtJornada.php',
                create: 'exec/execEdtJornada.php?acao=create',
                update: 'exec/execEdtJornada.php?acao=update',
                destroy: 'exec/execEdtJornada.php?acao=delete'
            },
            reader: {
                type: 'json',
                root: 'situacoes'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                root: 'data'
            },
            extraParams:{
                idCondutor: '<?=$_REQUEST['idCondutor']?>',
                dtEdt: '<?=$_REQUEST['dtEdit']?>'
            },
            listeners: {
                exception: function(proxy, response, operation){
//                    console.info('Identificado: ' + operation.error);
                    Ext.getCmp('btnSalvar').enable();
                    //Ext.getCmp('btnCancelar').enable();
                    maskT.hide();
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
            beforeload: function(store, operation){
                store.sort([
                    {
                        property : 'diarioHrIni'//,
                        //direction: 'ASC'
                    },
                    {
                        property : 'diarioHrFim'//,
                        //direction: 'DESC'
                    },
                    {
                        property : 'situacaoID'//,
                        //direction: 'DESC'
                    }
                ]);
            },
            beforesync: function(options, eOpts){
                for (var i in options) {
                    for (var j in options[i]) {
                        options[i][j].data.observacao = justificativaAtual;
                    }
                }
                justificativaAtual = "";
            }
        }
    });

    var rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
        clicksToMoveEditor: 1,
        autoCancel: false
    });
	
    var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    	clicksToEdit: 1
    });
    
    /**
    * Função criada para deixar o codigo mais organizado e evitar erros na construcao das windows.
    */
    function criaButtonSalvarJustif(){
        var buttonSalvarJustif = Ext.create('Ext.Button', {
            id: 'idButtonSalvarJustif',
            text: 'Salvar',
            iconCls: 'save',
            handler: function(){
                var editedRecords = storeJornada.getUpdatedRecords();

                for(var i=0; i < editedRecords.length; i++){
                    var msg = validaCampos(
                        editedRecords[i].data.diarioHrIni,
                        editedRecords[i].data.diarioHrFim,
                        editedRecords[i].data.situacaoID
                    );

                    if(!Ext.isEmpty(msg)){
                        Ext.MessageBox.show({
                            title: 'Campo Inválido',
                            msg: msg,
                            icon: Ext.MessageBox.WARNING,
                            buttons: Ext.Msg.OK
                        });
                        break;
                    }
                }

                if(Ext.isEmpty(msg)){
                    justificativaAtual = Ext.getCmp('txtJustificativa').getValue();

                    if (justificativaAtual == '') {
                        Ext.MessageBox.show({
                            title: 'Atenção',
                            msg: 'Informe a justificativa!',
                            icon: Ext.MessageBox.WARNING,
                            buttons: Ext.Msg.OK
                        });
                        return;
                    }
                    maskT.show();
                    Ext.getCmp('idWindJust').destroy();
                    storeJornada.sync({
                        success: function(operation){
                                storeJornada.load({
                                    callback:function(records){
                                        maskT.hide();
                                        Ext.getCmp('gridCartaoId').getStore().load({
                                            params:{
                                                'dtIni': '<?=$_REQUEST['dtIniFil']?>',
                                                'dtFim': '<?=$_REQUEST['dtFimFil']?>',
                                                'idCondutor':'<?=$_REQUEST['idCondutor']?>'
                                            },
                                            callback:function(){
                                                Ext.MessageBox.show({
                                                    title: 'Informação:',
                                                    msg: 'Alterações realizadas.',
                                                    icon: Ext.MessageBox.INFO,
                                                    buttons: Ext.Msg.OK
                                                });
                                            }
                                        });
                                    }						
                                });
                        }
                    });  
                    Ext.getCmp('btnSalvar').disable();
                }
            }
        });
        return buttonSalvarJustif;
    }
    
    var buttonSalvar = Ext.create('Ext.Button', {
        id: 'btnSalvar',
        text: 'Salvar',
        disabled: true,
        iconCls: 'save',
        handler: function(){
            if (Ext.getCmp('idWindJust')){
                Ext.getCmp('idWindJust').destroy();
                Ext.getCmp('idButtonSalvarJustif').destroy();
            }

            Ext.create('Ext.window.Window', {
                title: 'Justificativa de alteração:',
                width: 400,
                modal: true,
                id: 'idWindJust',
                items: [
                    {
                        xtype: 'form',
                        layout: 'form',
                        id: 'idFormJust',
                        border: false,
                        padding: 5,
                        items: [					
                            {
                                xtype: 'textarea',
                                name: 'txtJustificativa',
                                id: 'txtJustificativa',
                                value: ''
                            }
                        ]
                    }
                ],
                bbar: [
                    {xtype: criaButtonSalvarJustif()},
                    '-',
                    {
                        text: 'Cancelar',
                        iconCls: 'cancel',
                        handler: function(){       
                            Ext.getCmp('idWindJust').destroy();
                        }
                    }
                ]
            }).show();
        }
    });
    
    var buttonRegerar = Ext.create('Ext.Button', {
        itemId: 'regeraDia',
        text: 'Regerar',
        iconCls: 'refresh',
        handler: function() {
            Ext.Msg.confirm('Atenção:', 'Você deseja realmente regerar este dia?', function(buttonText) {
                if (buttonText == "yes") {
                    var mask = new Ext.LoadMask('edit_cartao_ponto', {msg:"Carregando..."});
                    mask.show();
                    var response = Ext.Ajax.request({
                        //async: false,
                        url: 'jobs/execRegeraJornada.php',
                        timeout: 240000,
                        method: 'GET',
                        params: {
                            condutor:'<?=$_REQUEST['idCondutor']?>',
                            empresa:'<?=$empresaUsu?>',
                            dataGeracao:'<?=$_REQUEST['dtEdit']?>'	
                        },
                        success: function(){
                            storeJornada.load({
                                callback:function(){
                                    mask.hide();
                                    var maskCp = new Ext.LoadMask('gestorRelId', {msg:"Carregando..."});
                                    maskCp.show();
                                    Ext.getCmp('gridCartaoId').getStore().load({
                                        params:{
                                            'dtIni': '<?=$_REQUEST['dtIniFil']?>',
                                            'dtFim': '<?=$_REQUEST['dtFimFil']?>',
                                            'idCondutor':'<?=$_REQUEST['idCondutor']?>'
                                        },
                                        callback:function(){
                                            maskCp.hide();
                                        }
                                    });
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
    
    var buttonRemoveSituacao = Ext.create('Ext.Button', {
        itemId: 'removeSituacao',
        text: 'Remover',
        iconCls: 'remove',
        handler: function() {
            var sm = grid.getSelectionModel();
            rowEditing.cancelEdit();
            storeJornada.remove(sm.getSelection());

            if (storeJornada.getCount() > 0) {
                sm.select(0);
            }

            Ext.getCmp('btnSalvar').enable();
        },
        disabled: true
    });
    
    var buttonAdicionaSituacao = Ext.create ('Ext.Button', {
        text: 'Adicionar',
        iconCls: 'add',
        handler : function() {
            cellEditing.cancelEdit();

                var r = Ext.create('modelSituacao', {
                    situacaoID: '5',
                    diarioHrIni: '00:00',
                    diarioHrFim: '23:59'				
                });

                storeJornada.insert(0, r);
                cellEditing.startEditByPosition({row: 0, column: 0});
                Ext.getCmp('btnSalvar').enable();
        }
    });
    
    var grid = Ext.create('Ext.grid.Panel', {
        height: Ext.get('edit_cartao_ponto').getHeight()-32,
        forceFit: true,
        store: storeJornada,
        id: 'gridIdSituacoes',
        columns: [
            {
                header: 'Situação',  
                dataIndex: 'situacaoID', 
                editor: {
                    xtype: 'combobox',
                    typeAhead: false,
                    allowBlank: false,
                    forceSelection: true,
                    selectOnTab: true,
                    queryMode: 'local',
                    triggerAction: 'all',
                    store: storeSituacoes,
                    displayField: 'descSituacao',
                    valueField: 'idSituacao',
                    listClass: 'x-combo-list-small'
                },
                renderer: function(value){
                    if(value!=0 && value!=""){
                        if(storeSituacoes.findRecord("idSituacao", value) != null)
                            return storeSituacoes.findRecord("idSituacao", value).get('descSituacao');
                        else 
                            return "";
                    }
                    else
                        return "";
                }
            },{ 
                header: 'Hora Início',  
                dataIndex: 'diarioHrIni', 
                renderer: formatHour, 
                editor: {
                    xtype: 'timefield', 
                    allowBlank: false, 
                    format: 'H:i'
                }
            },{
                header: 'Hora Fim',
                dataIndex: 'diarioHrFim',
                renderer: formatHour, 
                editor: {
                    xtype: 'timefield',
                    allowBlank: false,
                    format: 'H:i'
                }
            },{
                header: 'Tempo',
                dataIndex: 'diarioTempo'
            }
        ],
        selType: 'cellmodel',
        renderTo: 'contentId',
        //forceFit: true,
         viewConfig: {
            emptyText: '<center>Nenhum Registro Encontrado</center>'
        },
        tbar: [
            {xtype: buttonAdicionaSituacao},
            {xtype: buttonRemoveSituacao},
            '-',
            {xtype: buttonSalvar},
            '->',
            {
                xtype: 'checkbox',
                hidden: true,
                id:'ckbAutoComp',
                name: 'ckbAutoComp',
                fieldLabel: 'Auto-complete',
                checked: false,
                labelWidth: 90
            },
            '->',
            {xtype: buttonRegerar}
        ],
        plugins: [cellEditing],
        listeners: {
            'selectionchange': function(view, records) {
                grid.down('#removeSituacao').setDisabled(!records.length);
            },
            edit: function(editor, e){
                Ext.getCmp('btnSalvar').enable();
                //Ext.getCmp('btnCancelar').enable();
            }
        }
    });
    maskT = new Ext.LoadMask('gridIdSituacoes', {msg:"Carregando..."});
});

</script>

<div id="contentId"></div>
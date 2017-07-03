<?php
    require_once('../includes/Controles.class.php');

    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $idUsuario  = $CtrlAcesso->getUserID($_SESSION);
?>

﻿<script>
    function setMinDateFieldFim(dateMin, dati) {
        dateMin.setMinValue(dati);
    }

    function realizarAjax(){

        Ext.Ajax.request({
            url: 'exec/execFeriasCondutor.php',
            params: {
                dataIni: Ext.Date.format(Ext.getCmp('idDateIniFerias').value, 'd-m-Y'),
                dataFim: Ext.Date.format(Ext.getCmp('idDateFimFerias').value, 'd-m-Y'),
                idCondutor: '<?=$_REQUEST['idCondutor']?>',
                idUsuario: '<?=$idUsuario?>',
                descFerias: Ext.getCmp('txtDescFerias').value
            },
            success: function(conn, response, options, eOpts) {
//                Ext.getCmp('idPanelGridFerias').store.reload();

//                var result = Ext.decode(conn.responseText);
//                if (result.status === false) {
//                    Ext.Msg.show({
//                        title:'Erro!',
//                        msg: 'Favor informar o departamento de TI.',
//                        icon: Ext.Msg.ERROR,
//                        buttons: Ext.Msg.OK
//                    });
//                }else{
//                    Ext.getCmp('gridFeriados').store.reload();
//                    Ext.Msg.show({
//                        title:'Informação:',
//                        msg: 'Sucesso ao salvar o feriado!',
//                        icon: Ext.Msg.INFO,
//                        buttons: Ext.Msg.OK
//                    });
//                }
            },
            failure: function(conn, response, options, eOpts) {
                Ext.Msg.show({
                    title:'Erro!',
                    msg: 'Entre em contato com o administrador do sistema.',
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        });
    }

    Ext.define('modelFerias', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idFerias', type: 'int'},
            {name: 'dataIni', type: 'date', dateFormat: 'H:i'},
            {name: 'dataFim', type: 'date', dateFormat: 'H:i'},
            {name: 'usuario', type: 'int'},
            {name: 'descricao', type: 'string'}
        ]
    });

    var storeFerias = Ext.create('Ext.data.Store', {
        model: 'modelFerias',
        autoLoad: false,
        proxy: {
            type: 'ajax',
            api: {
                read: 'json/jsonFeriasCondutor.php'
            },
            reader: {
                type: 'json',
                root: 'ferias'
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

    Ext.onReady(function () {

        var gridFerias = Ext.create('Ext.grid.Panel', {
            id: 'idGridFerias',
            width: 443,
            store: storeFerias,
            height: 100,
            style: 'margin-bottom: 10px; margin-top: 5px;',
            viewConfig: {
                emptyText: 'Nenhum Registro Encontrado'
            },
            columns: [
                {
                    header: 'Início',
                    dataIndex: 'descricaoSituacao'
                },{
                    header: 'Fim',
                    dataIndex: 'dataIni'
                },{
                    header: 'Usuário',
                    dataIndex: 'usuario'
                },{
                    header: 'Descrição',
                    dataIndex: 'descricao'
                },{
                    xtype: 'actioncolumn',
                    width: 10,
                    align: 'center',
                    menuDisabled: true,
                    items: [
                        {
                            icon: 'imagens/16x16/delete.png',
                            tooltip: 'Excluir',
                            handler: function(grid, rowIndex, colIndex) {

                            }
                        }
                    ]
                }
            ],
            forceFit: true
        });

        var panelGridFerias = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelGridFerias',
            bodyPadding: 5,
            title: '<b>Cadastradas</b>',
//            width: '50%',
            layout: 'anchor',
            border: true,
            defaults: {
                border: false
            },
            items: [{
                layout: 'column',
                items: [
                    {xtype: gridFerias}
                ]
            }]
        });

        var buttonSalvar = new Ext.create('Ext.Button',{
            text: 'Salvar',
            formBind: true,
            disabled: true,
            iconCls: 'save',
            style: 'margin-left: 20px; margin-top:3px;',
            handler: function() {
                Ext.create('Ext.window.Window', {
                    title: 'Descrição:',
                    width: 400,
                    modal: true,
                    buttonAlign: 'center',
                    id: 'idDescFerias',
                    items: [
                        {
                            xtype: 'form',
                            layout: 'form',
                            id: 'idFormDescFerias',
                            border: false,
                            bodyPadding: 5,
                            items: [
                                {
                                    xtype: 'textarea',
                                    emptyText: '(Opcional)',
                                    name: 'txtDescFerias',
                                    id: 'txtDescFerias',
                                    value: ''
                                }
                            ]
                        }
                    ],
                    buttons: [
                       {
                            text: 'OK',
                            iconCls: 'accept',
                            handler: function(){
                                realizarAjax();
                            }
                       },{
                            text: 'Cancelar',
                            iconCls: 'cancel',
                            handler: function(){
                                Ext.getCmp('idDescFerias').destroy();
                            }
                       }
                    ]
                }).show();
            }
        });

        var dateFimFerias = Ext.create('Ext.form.DateField', {
            id: 'idDateFimFerias',
            fieldLabel: 'Data final',
            labelWidth: 70,
            width: 170,
            style: 'margin-left:20px;margin-top:3px;margin-bottom:6px;',
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: 'dd/mm/aaaa',
            submitEmptyText: false,
            allowBlank: false
        });

        var dateIniFerias = Ext.create('Ext.form.DateField', {
            id: 'idDateIniFerias',
            fieldLabel: 'Data inicial:',
            labelWidth: 70,
            width: 170,
            style: 'margin-left:3px;margin-top:3px;margin-bottom:6px;',
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: 'dd/mm/aaaa',
            submitEmptyText: false,
            allowBlank: false,
            listeners: {
                'afterrender': function(me) {
                    setMinDateFieldFim(dateFimFerias, me.getSubmitValue());
                },
                'change': function(me) {
                    setMinDateFieldFim(dateFimFerias, me.getSubmitValue());
                }
            }
        });

        var panelDatepickers = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelDatepickers',
            bodyPadding: 5,
            title: '<b>Nova</b>',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {
                    layout: 'column',
                    columns: 3,
                    items: [
                        {xtype: dateIniFerias},
                        {xtype: dateFimFerias},
                        {xtype: buttonSalvar}
                    ]
                }
            ]
        });

        Ext.create('Ext.form.Panel', {
            id: 'idPanelCadFerias',
            bodyPadding: 5,
            width: '100%',
//            height: '100%',
            buttonAlign : 'center',
            border: true,
            items: [
                {xtype: panelDatepickers},
                {xtype: panelGridFerias}

            ],
            renderTo: 'divCadFerias'
        });

    });
</script>
<div id="divCadFerias"></div>
<?php
    require_once('../includes/Controles.class.php');

    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $loginUsuario  = $CtrlAcesso->getUserLogin($_SESSION);
?>

﻿<script>
    function setMinDateFieldFim(dateMin, dati) {
        dateMin.setMinValue(dati);
    }

    function realizarAjax(acao, idAfastamento, dataIni, dataFim){

        var mask = new Ext.LoadMask('cad_afastamento_condutor', {msg: "Processando afastamento..."});
        mask.show();

        Ext.Ajax.request({
            url: 'exec/execAfastamento.php',
            params: {
                idAfastamento: idAfastamento,
                motivo: Ext.getCmp('idAfastMotivo').value,
                dataIni: (Ext.getCmp('idDateIniAfastamento').value == null)?dataIni:Ext.Date.format(Ext.getCmp('idDateIniAfastamento').value, 'd/m/Y'),
                dataFim: (Ext.getCmp('idDateFimAfastamento').value == null)?dataFim:Ext.Date.format(Ext.getCmp('idDateFimAfastamento').value, 'd/m/Y'),
                idCondutor: '<?=$_REQUEST['idCondutor']?>',
                loginUsuario: '<?=$loginUsuario?>',
                obs: Ext.getCmp('obsAfastamento').value,
                acao: acao
            },
            success: function(conn, response, options, eOpts) {
                mask.hide();
                var result = Ext.decode(conn.responseText);
                if (result.status === false) {
                    Ext.Msg.show({
                        title:'Erro!',
                        msg: 'Favor informar o departamento de TI.',
                        icon: Ext.Msg.ERROR,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    Ext.getCmp('idGridAfastamento').store.reload();
                    Ext.Msg.show({
                        title:'Informação:',
                        msg: result.msg ,
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }
            },
            failure: function(conn, response, options, eOpts) {
                mask.hide();
                Ext.Msg.show({
                    title:'Erro!',
                    msg: 'Entre em contato com o administrador do sistema.',
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        });
    }

    Ext.define('Motivos', {
        extend: 'Ext.data.Model',
        fields:[
            {name: 'idMotivo', type: 'int'},
            {name: 'descricao', type: 'string'}
        ]
    });

    var storeMotivos = Ext.create('Ext.data.Store', {
        model: 'Motivos',
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonAfastMotivos.php',
            reader: {
                type: 'json',
                root: 'motivos'
            }
        }
    });

    Ext.define('modelAfastamento', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idAfastamento', type: 'int'},
            {name: 'idMotivo', type: 'int'},
            {name: 'descMotivo', type: 'string'},
            {name: 'dataIni', type: 'string'},
            {name: 'dataFim', type: 'string'},
            {name: 'obs', type: 'string'},
            {name: 'usuario', type: 'string'},
            {name: 'dataCad', type: 'date', dateFormat: 'd/m/Y'},
            {name: 'condutor', type: 'int'},
        ]
    });

    var storeAfastamento = Ext.create('Ext.data.Store', {
        model: 'modelAfastamento',
        autoLoad: true,
        proxy: {
            type: 'ajax',
            api: {
                read: 'json/jsonAfastamentos.php'
            },
            reader: {
                type: 'json',
                root: 'afastamentos'
            },
            extraParams:{
                idCondutor: '<?=$_REQUEST['idCondutor']?>'
            },
            listeners: {
                exception: function(proxy, response, operation){
//                    console.info('Identificado: ' + operation.error);
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

    Ext.onReady(function () {

        var gridAfastamento = Ext.create('Ext.grid.Panel', {
            id: 'idGridAfastamento',
            width: 550,
            height: 150,
            style: 'margin-bottom: 10px; margin-top: 5px;',
            store: storeAfastamento,
            viewConfig: {
                emptyText: 'Nenhum Registro Encontrado'
            },
            forceFit: true,
            columns: [
                {
                    hidden: true,
                    dataIndex: 'idAfastamento'
                },{
                    header: 'Motivo',
                    dataIndex: 'descMotivo',
                    width: 150,
                },{
                    header: 'Início',
                    dataIndex: 'dataIni',
                    width: 100,
                },{
                    header: 'Fim',
                    dataIndex: 'dataFim',
                    width: 100,
                },{
                    header: 'Observação',
                    dataIndex: 'obs',
                    width: 150,
                },{
                    header: 'Usuário',
                    dataIndex: 'usuario',
                    width: 150,
                },{
                    xtype: 'actioncolumn',
                    width: 30,
                    align: 'center',
                    menuDisabled: true,
                    items: [
                        {
                            icon: 'imagens/16x16/delete.png',
                            tooltip: 'Excluir',
                            handler: function(grid, rowIndex, colIndex) {
                                var rec             = grid.getStore().getAt(rowIndex);
                                var idAfastamento   = rec.raw.idAfastamento;
                                var dataIni         = rec.raw.dataIni;
                                var dataFim         = rec.raw.dataFim;

                                Ext.Msg.confirm('Atenção:', 'Você deseja excluir este afastamento?', function (button) {
                                    if (button == 'yes') {
                                        storeAfastamento.removeAt(rowIndex);
                                        realizarAjax("delete", idAfastamento, dataIni, dataFim);
                                    }
                                })
                            }
                        }
                    ]
                }
            ]
        });

        var panelGridAfastamento = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelGridAfastamento',
            bodyPadding: 5,
            title: '<b>Cadastrados</b>',
            layout: 'anchor',
            border: true,
            defaults: {
                border: false
            },
            items: [{
                layout: 'column',
                items: [
                    {xtype: gridAfastamento}
                ]
            }]
        });

        var buttonSalvar = new Ext.create('Ext.Button',{
            text: 'Salvar',
            formBind: true,
            disabled: true,
            iconCls: 'save',
            handler: function() {
                realizarAjax("create", null);
                this.up('form').getForm().reset();

            }
        });

        var dateFimAfastamento = Ext.create('Ext.form.DateField', {
            id: 'idDateFimAfastamento',
            fieldLabel: 'Data final',
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: 'dd/mm/aaaa',
            submitEmptyText: false,
            allowBlank: false
        });

        var dateIniAfastamento = Ext.create('Ext.form.DateField', {
            id: 'idDateIniAfastamento',
            fieldLabel: 'Data inicial:',
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: 'dd/mm/aaaa',
            submitEmptyText: false,
            allowBlank: false,
            listeners: {
                'afterrender': function(me) {
                    setMinDateFieldFim(dateFimAfastamento, me.getSubmitValue());
                },
                'change': function(me) {
                    setMinDateFieldFim(dateFimAfastamento, me.getSubmitValue());
                }
            }
        });

        var panelDatepickers = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelDatepickers',
            bodyPadding: 5,
            title: '<b>Novo</b>',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {
                    layout: 'column',
                    columns: 2,
                    items: [
                        {xtype: dateIniAfastamento},
                        {xtype: dateFimAfastamento},
                    ]
                }, {
                    xtype: 'form',
                    layout: 'form',
                    id: 'idFormDescAfastamena',
                    border: false,
                    bodyPadding: 5,
                    items: [
                        {
                            xtype: 'combobox',
                            fieldLabel: 'Motivo',
                            queryMode: 'local',
                            id: 'idAfastMotivo',
                            name: 'idAfastMotivo',
                            displayField: 'descricao',
                            valueField: 'idMotivo',
                            value: '',
                            store: storeMotivos,
                            emptyText: 'Selecione a opção',
                            submitEmptyText: false,
                            allowBlank: false,
                        },
                        {xtype: dateIniAfastamento},
                        {xtype: dateFimAfastamento},
                        {
                            fieldLabel: 'Observação',
                            xtype: 'textarea',
                            emptyText: '(Opcional)',
                            name: 'obsAfastamento',
                            id: 'obsAfastamento',
                            value: ''
                        },
                        {xtype: buttonSalvar}
                    ]
                }
            ]
        });

        Ext.create('Ext.form.Panel', {
            id: 'idPanelCadAfastamento',
            bodyPadding: 5,
            width: '100%',
            buttonAlign : 'center',
            border: true,
            items: [
                {xtype: panelDatepickers},
                {xtype: panelGridAfastamento}
            ],
            renderTo: 'divCadAfastamento'
        });
    });
</script>

<div id="divCadAfastamento"></div>
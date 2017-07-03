<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo           = new OracleCielo();
    $conexaoOra         = $OraCielo->getCon();
    $CtrlAcesso         = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
    $idBancoFechamento  = $_REQUEST['idBancoFechamento'];

    if(isset($_SESSION)) {
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    } else {
        header('Location: http://jornada.cielo.ind.br');
    }

?>
<script>
    Ext.define('tiposFechamento', {
        extend: 'Ext.data.Model',
        requires: 'Ext.data.Model',
        fields:[
            {name: 'idTipoFechamento', type: 'int'},
            {name: 'descTipoFechamento', type: 'string'}
        ]
    });

    var storeCondutorBancoHora = Ext.create('Ext.data.Store', {
        model: 'tiposFechamento',
        autoLoad : true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonTiposFechamento.php',
            reader: {
                type: 'json',
                root: 'tiposFechamento'
            }
        }
    });

    Ext.onReady(function () {

        Ext.getCmp('buttonSalvar').handler = function(){
            if(Ext.getCmp('idComboTiposFechamento').value == '' || Ext.getCmp('idComboTiposFechamento').value == null){
                Ext.Msg.show({
                    title: 'Atenção:',
                    msg: 'Você deve selecionar o tipo de fechamento',
                    icon: Ext.Msg.WARNING,
                    buttons: Ext.Msg.OK
                });
            }else{
                Ext.Ajax.request({
                    url: 'exec/execBancoHoras.php',
                    params: {
                        idBancoFechamento: '<?=$idBancoFechamento?>',
                        idTipoFechamento: Ext.getCmp('idComboTiposFechamento').value,
                        obsFechamento: Ext.getCmp('obsFechamento').value,
                        checkTodos: (Ext.getCmp('checkBoxTodos').value == true)?'true':'false',
                        acao: 'updateFechamento'
                    },
                    success: function (conn, response, options, eOpts) {
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
                            Ext.getCmp('cadFechamento').close();
                            Ext.getCmp('gridBCFechamento').getStore().load();
                        }
                    },
                    failure: function (conn, response, options, eOpts) {
                        Ext.Msg.show({
                            title: 'Erro!',
                            msg: 'Entre em contato com o administrador do sistema.',
                            icon: 'imagens/16x16/accept.png',
                            buttons: Ext.Msg.OK
                        });
                    }
                });
            }
        };

        var comboPeriodo = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Tipo',
            queryMode: 'local',
            id: 'idComboTiposFechamento',
            emptyText: 'Selecione a opção',
            allowBlank: false,
            valueField: 'idTipoFechamento',
            displayField: 'descTipoFechamento',
            disabled: false,
            store: storeCondutorBancoHora
        });

        var textObs = Ext.create('Ext.form.field.TextArea', {
            fieldLabel: 'Observação',
            name: 'obsFechamento',
            id: 'obsFechamento',
            emptyText: 'Descreva algo importante para este fechamento...',
            value: ''
        });

        var checkBoxTodos = Ext.create('Ext.form.field.Checkbox', {
            fieldLabel: 'Aplicar esta informação em todos os condutores em aberto',
            labelWidth: 330,
            name: 'obsFechamento',
            id: 'checkBoxTodos'
        });

        var fieldSet = Ext.create('Ext.form.FieldSet', {
            id: 'idDadosBcHorasCondutor',
            bodyPadding: 5,
            title: '<b>Dados Gerais</b>',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {
                    xtype: 'form',
                    layout: 'form',
                    id: 'idFormTipoFechamento',
                    border: false,
                    bodyPadding: 5,
                    items: [
                        {xtype: comboPeriodo},
                        {xtype: textObs}
                    ]
                },
                {xtype: checkBoxTodos}
            ]
        });

        Ext.create('Ext.form.Panel', {
            id: 'idCadFechamento',
            bodyPadding: 5,
            buttonAlign : 'center',
            width: '100%',
            height: '100%',
            layout: 'anchor',
            defaults: {
                anchor: '100%',
                border: false
            },
            items: [
                {xtype: fieldSet}
            ],
            renderTo: 'divCadFechamento'
        });
    });           
</script>
<div id="divCadFechamento"></div>


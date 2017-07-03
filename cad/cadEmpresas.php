<?php
    require_once('../includes/Controles.class.php');

    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    (!$CtrlAcesso->checkUsuario($_SESSION))?exit:null;

    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
?>
<script>    
    function realizarAjax() {

        var acao = '<?=$_REQUEST['acao']?>';

        Ext.Ajax.request({
            url: 'exec/execEmpresas.php',
            method: 'POST',
            params: {
                idEmpresa: Ext.getCmp('idEmpresa').value,
                teclado: Ext.getCmp('teclado').value,
                acao: 'insert'
            },
            success: function(records){
                var result = Ext.decode(records.responseText);
                if(result.status == 'OK'){
                    Ext.Msg.show({
                        title: 'Sucesso!',
                        msg: result.msg,
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                    Ext.getCmp('contentGridIdEmpresas').getStore().load();
                    Ext.getCmp('cadastro_empresa').close();
                }else{
                    Ext.Msg.show({
                        title: 'Erro!',
                        msg: result.msg,
                        icon: Ext.Msg.ERROR,
                        buttons: Ext.Msg.OK
                    });
                }
            },
            failure: function(){
                Ext.Msg.alert('Ops!', 'Houve algum problema com a sua comunicação... <br><br>Tente novamente. Se persistir o erro verifique sua conexão de internet ou entre em contato com a equipe de Jornada.');
            }
        });
    }

    Ext.define('Empresas', {
        extend: 'Ext.data.Model',
        fields:[
            {name: 'idEmpresa', type: 'int'},
            {name: 'nmEmpresa', type: 'string'}
        ]
    });

    var storeEmpresa = Ext.create('Ext.data.Store', {
        model: 'Empresas',
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonMonitEmpresas.php',
            reader: {
                type: 'json',
                root: 'empresas'
            }
        }
    });

    Ext.define('Teclado', {
        extend: 'Ext.data.Model',
        fields: [
            { name: 'id', type: 'int' },
            { name: 'name', type: 'string' }
        ]
    });

    var tecladoStr = Ext.create('Ext.data.Store', {
        model: 'Teclado',
        data: [
            { id: 1, name: 'AJT' },
            { id: 2, name: 'Alpha' }
        ]
    });


    Ext.create('Ext.form.Panel', {
        bodyPadding: 5,
        buttonAlign : 'center',
        width: '100%',
        url: 'exec/execEmpresa.php',
        layout: 'anchor',
        border: false,
        defaults: {
            anchor: '100%'
        },
        items: [
            {
                xtype: 'combobox',
                fieldLabel: 'Empresa',
                queryMode: 'local',
                id: 'idEmpresa',
                name: 'idEmpresa',
                displayField: 'nmEmpresa',
                valueField: 'idEmpresa',
                store: storeEmpresa,
                emptyText: 'Seleciona a opção',
                submitEmptyText: false
            },{
                xtype: 'combobox',
                fieldLabel: 'Tipo Teclado',
                queryMode: 'local',
                valueField: 'id',
                displayField: 'name',
                id: 'teclado',
                name: 'teclado',
                store: tecladoStr,
                emptyText: 'Seleciona a opção'
            }
        ],
        buttons: [
            {
                text: 'Limpar',
                iconCls: 'clear',
                handler: function() {
                    this.up('form').getForm().reset();
                }
            }, {
                text: 'Salvar',
                formBind: true,
                iconCls: 'save',
                disabled: true,
                handler: function() {
                    realizarAjax();
                }
            }
        ],
        renderTo: 'contentId'
    });
</script>

<div id="contentId"></div>
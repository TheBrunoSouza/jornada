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
            url: 'exec/execCondutores.php',
            method: 'POST',
            params: {
                acao: acao,
                idCondutor: Ext.getCmp('idCadCondutor').value,
                nmCondutor: Ext.getCmp('nmCondutor').value,
                cpfCondutor: Ext.getCmp('cpfCondutor').value,
                rgCondutor: Ext.getCmp('rgCondutor').value,
                celularCondutor: Ext.getCmp('celularCondutor').value,
                matriculaCondutor: Ext.getCmp('matriculaCondutor').value,
                dtNascCondutor: Ext.Date.format(Ext.getCmp('dtNascCondutor').value, "d/m/Y"),
                //situacaoCondutor: Ext.getCmp('situacaoCondutor').value,
                //idEmpresaCondutor: Ext.getCmp('idEmpresaCondutor').value,
                profissaoCondutor: Ext.getCmp('profissaoCondutor').value
            },
            success: function(records) {
                console.info(records.responseText);
                var result = Ext.decode(records.responseText);

                if(result.status == 'OK') {
                    Ext.Msg.show({
                        title: 'Sucesso!',
                        msg: result.msg,
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                    Ext.getStore('storeCondutoresId').reload();
                    Ext.getCmp('cadastro_condutor').close();
                } else {
                    Ext.Msg.show({
                        title: 'Erro!',
                        msg: result.msg,
                        icon: Ext.Msg.ERROR,
                        buttons: Ext.Msg.OK
                    });
                }
            },
            failure: function() {
                Ext.Msg.alert('Ops!', 'Houve algum problema com a sua comunicação... <br><br>Tente novamente. Se persistir o erro verifique sua conexão de internet ou entre em contato com a equipe de Jornada.');
            }
        });
    }

    Ext.define('Empresas', {
        extend: 'Ext.data.Model',
        fields:[
            {name: 'idEmpresa', type: 'int'},
            {name: 'nmEmpresa', type: 'string'},
            {name: 'respEmpresa', type: 'string'},
            {name: 'telEmpresa', type: 'string'},
            {name: 'emailEmpresa', type: 'string'},
            {name: 'cepEmpresa', type: 'string'},
            {name: 'ufEmpresa', type: 'string'},
            {name: 'cidEmpresa', type: 'string'},
            {name: 'usuEmpresa', type: 'string'}
        ]
    });

    var storeEmpresa = Ext.create('Ext.data.Store', {
        model: 'Empresas',
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonEmpresas.php',
            reader: {
                type: 'json',
                root: 'empresas'
            },
            extraParams:{
                idEmpresa:'<?=$_REQUEST['idEmpresa']?>'
            }
        }
    });

    Ext.create('Ext.form.Panel', {
        bodyPadding: 5,
        buttonAlign : 'center',
        width: '100%',
        url: 'exec/execCondutores.php',
        layout: 'anchor',
        border: false,
        defaults: {
            anchor: '100%'
        },
        items: [
            {
                xtype: 'hiddenfield',
                name: 'idCadCondutor',
                id: 'idCadCondutor',
                value: '<?=$_REQUEST['idCondutor']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'Nome',
                name: 'nmCondutor',
                id: 'nmCondutor',
                allowBlank: false,
                value: '<?=$_REQUEST['nmCondutor']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'Profissão',
                name: 'profissaoCondutor',
                id: 'profissaoCondutor',
                allowBlank: true,
                value: '<?=$_REQUEST['profissaoCondutor']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'CPF',
                name: 'cpfCondutor',
                id: 'cpfCondutor',
                allowBlank: true,
                value: '<?=$_REQUEST['cpfCondutor']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'RG',
                name: 'rgCondutor',
                id: 'rgCondutor',
                allowBlank: true,
                value: '<?=$_REQUEST['rgCondutor']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'Celular',
                name: 'celularCondutor',
                id: 'celularCondutor', 
                vtype: 'phone',
                allowBlank: true,
                value: '<?=$_REQUEST['celularCondutor']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'Matricula',
                name: 'matriculaCondutor',
                id: 'matriculaCondutor',
                allowBlank: true,
                value: '<?=$_REQUEST['matriculaCondutor']?>'
            },{
                xtype: 'datefield',
                fieldLabel: 'Data Nascimento',
                id: 'dtNascCondutor',
                name: 'dtNascCondutor',
                format: 'd/m/Y',
                submitFormat: 'd/m/Y',
                value: '<?=$_REQUEST['dtNascCondutor']?>'
            }//,
//            {
//                xtype: 'combobox',
//                fieldLabel: 'Empresa',
//                queryMode: 'local',
//                id: 'idEmpresaCondutor',
//                name: 'idEmpresaCondutor',
//                displayField: 'nmEmpresa',
//                valueField: 'idEmpresa',
//                value: '',
//                store: storeEmpresa,
//                emptyText: 'Seleciona a opção',
//                submitEmptyText: false,
//                disabled: <?//=($empresaUsu) ? 'true' : 'false'?>
//            }
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

<!--    --><?// if($idEmpresa or $empresaUsu){?>
//        Ext.getCmp("idEmpresaCondutor").setValue(<?//=$_REQUEST['idEmpresa']?>//);
//    <?//}?>

</script>

<div id="contentId"></div>
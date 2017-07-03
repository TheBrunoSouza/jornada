<?
    require_once('../includes/Controles.class.php');

    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

    (!$CtrlAcesso->checkUsuario($_SESSION)) ? exit : null;

    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    $grupoUsu = $CtrlAcesso->getUserGrupo($_SESSION);
    $idUsu = $CtrlAcesso->getUserID($_SESSION);

    if($idUsu == $_REQUEST['idUsuario'] || $_REQUEST['idUsuario'] == null) {
        $hiddenSenha = false;
    } else {
        $hiddenSenha = true;
    }
?>
<script>
    if('<?=$_REQUEST['acao']?>' == 'update') {
        var hiddenConfirSenha = false;
        var hiddenSenha = true;
    } else {
        var hiddenSenha = false;
        var hiddenConfirSenha = true;
    }

    function realizarAjax(acao) {

        var grupoUsuario = '',
            idEmpresaUsuario = '',
            idUsuario = Ext.getCmp('nomeUsuario').valueField,
            url = 'exec/execUsuarios.php',
            senhaUsuario = Ext.getCmp('pswUsuario').value;

        if(acao == 'insert') {
            grupoUsuario = Ext.getCmp('idGpUsuario').value;
            idEmpresaUsuario = Ext.getCmp('idEmpresaUsuario').value;
        } else if(acao == 'update') {
            grupoUsuario = Ext.getCmp('hiddenIdUsuario').value;
            idEmpresaUsuario = Ext.getCmp('idEmpresaUsuario').valueField;
            senhaUsuario = Ext.getCmp('pswNovaUsuario').value;
        }

        Ext.Ajax.request({
            url: url,
            method: 'POST',
            params: {
                acao: acao,
                idUsuario: idUsuario,
                nomeUsuario: Ext.getCmp('nomeUsuario').value,
                emailUsuario: Ext.getCmp('emailUsuario').value,
                grupoUsuario: grupoUsuario,
                loginUsuario: Ext.getCmp('loginUsuario').value,
                senhaUsuario: senhaUsuario,
                idEmpresaUsuario: idEmpresaUsuario
            },
            success: function(records){
                var result = Ext.decode(records.responseText);

    //            console.info(result);

                if(result.status == 'OK'){
                    Ext.Msg.show({
                        title: 'Sucesso!',
                        msg: result.msg,
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                    Ext.getCmp('cadastro_usuario').close();
                }else if(result.status == 'OK_EXCECAO_USUARIO'){
                    Ext.Msg.show({
                        title: 'Ops!',
                        msg: result.msg,
                        icon: Ext.Msg.WARNING,
                        buttons: Ext.Msg.OK
                    });
                }else if(result.status == 'OK_SENHA'){
                    var icon    = '',
                        msg     = result.msg;
                    if(result.msg == 'Senha confirmada.'){
                        icon = Ext.Msg.INFO;
                        Ext.getCmp('pswNovaUsuario').setDisabled(false);
                    }else{
                        icon = Ext.Msg.ERROR;
                        Ext.getCmp('pswNovaUsuario').setDisabled(true);
                    }
                    Ext.Msg.show({
                        title: 'Informação:',
                        msg: msg,
                        icon: icon,
                        buttons: Ext.Msg.OK
                    });
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
    };
    
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
                idEmpresa: '<?=$empresaUsu?>',
                grupoUsu: '<?=$grupoUsu?>'
            }
        }
    });

    Ext.define('TpUsuarios', {
        extend: 'Ext.data.Model',
        fields:[
            {name: 'idGpUsuario', type: 'int'},
            {name: 'descGpUsuario', type: 'string'}
        ]
    });

    var tpUsuarios = Ext.create('Ext.data.Store', {
        model: 'TpUsuarios',
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonTpUsuarios.php',
            reader: {
                type: 'json',
                root: 'gpusuarios'
            },
            extraParams:{
                idGpUsuario: '<?=$idGpUsuario?>',
                grupoUsu: '<?=$grupoUsu?>'
            }
        }
    });

    Ext.create('Ext.form.Panel', {
        bodyPadding: 5,
        buttonAlign : 'center',
        width: '100%',
        layout: 'anchor',
        border: true,
        defaults: {
            anchor: '100%',
            labelWidth: '22%'
        },
        items: [
            {
                xtype: 'hiddenfield',
                name: 'acao',
                id: 'acao',
                value: '<?=$_REQUEST['acao']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'Nome',
                name: 'nomeUsuario',
                id: 'nomeUsuario',
                allowBlank: false,
                valueField: '<?=$_REQUEST['idUsuario']?>',
                value: '<?=$_REQUEST['nomeUsuario']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'E-mail',
                name: 'emailUsuario',
                id: 'emailUsuario',
                vtype: 'email',
                allowBlank: false,
                value: '<?=$_REQUEST['emailUsuario']?>'
            },{
                xtype: 'combobox',
                fieldLabel: 'Tipo',
                queryMode: 'local',
                id: 'idGpUsuario',
                name: 'idGpUsuario',
                displayField: 'descGpUsuario',
                valueField: 'idGpUsuario',
                value: ('<?=$_REQUEST['descricaoTipoUsuario']?>' == '')?"":'<?=$_REQUEST['descricaoTipoUsuario']?>',
                allowBlank: false,
                store: tpUsuarios,
                emptyText: 'Selecione uma opção...',
                listeners:{
                    select: function(f, r, i){
                        Ext.getCmp('hiddenIdUsuario').value = Ext.getCmp('idGpUsuario').value;
                    },
                    render: function(store) {
                        Ext.getCmp('hiddenIdUsuario').value = Ext.getCmp('idGpUsuario').valueField;
                    }
                }
            },{
                xtype: 'hiddenfield',
                name: 'hiddenIdUsuario',
                id: 'hiddenIdUsuario',
                value: ('<?=$_REQUEST['idTipoUsuario']?>' == '')?'':'<?=$_REQUEST['idTipoUsuario']?>'
            },{
                xtype: 'textfield',
                fieldLabel: 'Login',
                name: 'loginUsuario',
                id: 'loginUsuario',
                allowBlank: false,
                value: '<?=$_REQUEST['loginUsuario']?>'
            },{
                xtype: 'textfield',
                name: 'pswUsuario',
                id: 'pswUsuario',
                hidden: hiddenSenha,
                inputType: 'password',
                value: '',
                fieldLabel: 'Senha'//,
    //            allowBlank: false
            },{
                xtype: 'checkbox',
                fieldLabel: 'Trocar Senha',
                hidden: !hiddenSenha,
                listeners: {
                    change: this.toggleDisabled
                }
            },
            {
                xtype: 'textfield',
                name: 'pswNovaUsuario',
                style: 'margin-top: 6px;',
                id: 'pswNovaUsuario',
                hidden: hiddenConfirSenha,
                disabled: true,
                inputType: 'password',
                fieldLabel: 'Nova senha',
                //allowBlank: false,
                width: 30
            },{
                xtype: 'combobox',
                fieldLabel: 'Empresa',
                queryMode: 'local',
                id: 'idEmpresaUsuario',
                name: 'idEmpresaUsuario',
                displayField: 'nmEmpresa',
                valueField: ('<?=$_REQUEST['idEmpresa']?>' == '')?"idEmpresa":'<?=$_REQUEST['idEmpresa']?>',
                value: ('<?=$_REQUEST['nomeEmpresa']?>' == '')?"":'<?=$_REQUEST['nomeEmpresa']?>',
                store: storeEmpresa,
                emptyText: 'Selecione uma opção...',
                disabled: <?=($empresaUsu)?'true':'false'?>
            }
        ],
        buttons: [
            {
                text: 'Limpar',
                iconCls: 'clear',
                handler: function() {
                    this.up('form').getForm().reset();
                }
            },{
                text: 'Salvar',
                iconCls: 'save',
                formBind: true,
                disabled: true,
                handler: function() {
                    var form = this.up('form').getForm();
                    if(form.isValid()){
                        if('<?=$_REQUEST['acao']?>' == 'insert'){
                            if(Ext.getCmp('pswUsuario').value == '' || Ext.getCmp('pswUsuario').value == null){
                                Ext.Msg.show({
                                    title: 'Atenção:',
                                    msg: 'Escolha uma senha para o usuário.',
                                    icon: Ext.Msg.INFO,
                                    buttons: Ext.Msg.OK
                                });
                            }else{
                                realizarAjax('<?=$_REQUEST['acao']?>');
                            }
                        }else{
                            realizarAjax('<?=$_REQUEST['acao']?>');
                        }
                        Ext.getCmp('contentGridIdUsuarios').store.reload();
                    }else{
                        Ext.Msg.show({
                            title: 'Atenção:',
                            msg: 'Preencha corretamente o formulário.',
                            icon: Ext.Msg.INFO,
                            buttons: Ext.Msg.OK
                        });
                    }
                }
            }
        ],
        renderTo: 'contentId'
    });

function toggleDisabled(checkbox, newValue, oldValue) {
    var toggle = newValue ? false : true,
        pwdNew = Ext.getCmp('pswNovaUsuario');
        pwdNew.setDisabled(toggle);
}

<? if($gpUsuario){?>
Ext.getCmp("idGpUsuario").setValue(<?=$gpUsuario?>);
<? 
}

if($idEmpresa or $empresaUsu){?>
Ext.getCmp("idEmpresaUsuario").setValue(<?=($idEmpresa)?$idEmpresa:$empresaUsu?>);
<? }?>
</script>

<div id="contentId"></div>
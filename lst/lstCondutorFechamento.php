<?php
    require_once('../includes/Controles.class.php');

    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);

    $descTipoFechameto  = $_REQUEST['descTipoFechamentoBF'];
    $dataFechamanto     = $_REQUEST['dataFechamento'];
    $userNameFechamento = $_REQUEST['userNameFechamento'];
    $obsFechamento      = $_REQUEST['obsFechamento'];
    $idBancoFechamento  = $_REQUEST['idBancoFechamento'];

    if($idEmpresa == ''){
        $idEmpresa = $empresaUsu;
    }

    //Configurações de permissão (Seta a propriedade hidden do objeto como false ou true)
    $permissao = $CtrlAcesso->checkPermissao(20, '');

    if($permissao){
        if($permissao['add'] == 'T'){
            $hiddenAdicionar = 'false';
        }else{
            $hiddenAdicionar = 'true';
        }

        if($permissao['edit'] == 'T'){
            $hiddenAlterarExcluir = 'false';
        }else{
            $hiddenAlterarExcluir = 'true';
        }
    }
?>

<script>

    var hiddenAdicionar         = <?=$hiddenAdicionar?>,
        hiddenAlterarExcluir    = <?=$hiddenAlterarExcluir?>;

    Ext.onReady(function () {

        var buttonApagar = new Ext.create('Ext.Button',{
            text: 'Excluir Fechamento',
            formBind: true,
            hidden: false,
            style: 'margin-bottom: 8px; margin-top: 8px;',
            icon: 'imagens/16x16/database_delete.png',
            handler: function() {
                Ext.Msg.confirm('Atenção:', 'Deseja apagar este registro de fechamento?', function (button) {
                    if (button == 'yes') {
                        Ext.Ajax.request({
                            url: 'exec/execBancoHoras.php',
                            params: {
                                idBancoFechamento: '<?=$idBancoFechamento?>',
                                //idTipoFechamento: null,
                                //obsFechamento: null,
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
                                Ext.Msg.alert('Ops!', 'Houve algum problema com a sua comunicação... <br><br>Tente novamente. Se persistir o erro verifique sua conexão de internet ou entre em contato com a equipe de Jornada.');
                            }
                        });
                    }
                });
            }
        });

        var panelGrid1 = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelGrid1',
            bodyPadding: 5,
            title: '<b>Detalhes</b>',
            width: '100%',
            height: '100%',
            layout: 'anchor',
            border: true,
            defaults: {
                border: false
            },
            items: [{
                layout: 'column',
                items: [
                    {
                        xtype: 'panel',
                        html: "" +
                            "<table cellspacing='10' class='x-grid-empty'> " +
                                "<tr align='left'><th> <b>Tipo:</b> </th><th> <?=$descTipoFechameto?> </th></tr>" +
                                "<tr align='left'><th> <b>Data: </b></th> <th> <?=$dataFechamanto?> </th></tr> " +
                                "<tr align='left'><th> <b>Usuário: </b></th> <th> <?=$userNameFechamento?> </th></tr> " +
                                "<tr align='left'><th> <b>Observação: </b></th> <th> <?=$obsFechamento?> </th></tr> " +
                            "</table>",
                        border: false,
                        id: 'gestorPanelCondutorId'
                    }
                ]
            },{xtype: buttonApagar}]
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
                    xtype: panelGrid1
                }

            ],
            renderTo: 'contentIds'
        });
    });


</script>

<div id="contentIds" ></div>
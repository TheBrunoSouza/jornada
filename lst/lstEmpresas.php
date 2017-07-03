<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo = new OracleCielo();
    $conexao = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

    if(isset($_SESSION)){
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    $grupoUsu = $CtrlAcesso->getUserGrupo($_SESSION);

    // $ocultarEmpresa = ($grupoUsu == 6 || $grupoUsu == 1 || $grupoUsu == 2)?'n':'s';
    } else {
        header('Location: http://jornada.cielo.ind.br');
    }

    //(!$CtrlAcesso->checkUsuario($_SESSION))?exit:null;
    $permissao = $CtrlAcesso->checkPermissao('', $_SERVER['PHP_SELF']);
?>
<script>
    Ext.onReady(function () {
        var tbPag = new Ext.Toolbar({
            border: false,
            items: [
                <? if($permissao['add']=='T') { ?>
                {
                    text: 'Nova Empresa',
                    iconCls: 'add',
                    handler: function() {
                        showWindow(
                            'cadastro_empresa',
                            'Cadastro de Empresa',
                            'cad/cadEmpresas.php',
                            'acao=insert',
                            460,
                            125,
                            true,
                            true
                        );
                    }
                },
                <? } ?>
                {
                    text: 'Filtrar',
                    iconCls: 'filter',
                    handler: function (){
                        showWindow(
                            'filtro_empresa',
                            'Filtro de Empresa',
                            'filter/filEmpresas.php',
                            '',
                            460,
                            100,
                            true,
                            true
                        );
                    }
                }
            ]
        });

        Ext.define('Empresas', {
            extend: 'Ext.data.Model',
            requires : 'Ext.data.Model',
            fields:[
                {name: 'idEmpresa',    type: 'int'},
                {name: 'nmEmpresa',    type: 'string'},
                {name: 'respEmpresa',  type: 'string'},
                {name: 'telEmpresa',   type: 'string'},
                {name: 'emailEmpresa', type: 'string'},
                {name: 'usuEmpresa',   type: 'string'},
                {name: 'usuarioCad',   type: 'string'},
                {name: 'dataAtivacao', type: 'string'},
                {name: 'teclado',      type: 'string'}
            ]
        });

        var storeEmp = Ext.create('Ext.data.Store', {
            model: 'Empresas',
            id: 'storeEmpresasId',
            autoLoad : true,
            proxy: {
                type: 'ajax',
                url: 'json/jsonEmpresas.php',
                reader: {
                    type: 'json',
                    root: 'empresas'
                }
            }
        });

        Ext.create('Ext.grid.Panel', {
            id: 'contentGridIdEmpresas',
            border: false,
            store: storeEmp,
            height: Ext.getBody().getHeight()-90,
            columnLines: true,
            tbar: tbPag,
            forceFit: true,
            viewConfig: {
                emptyText: '<b>Nenhum registro de empresa encontrado para este filtro</b>',
                deferEmptyText: false
            },
            columns: [
                {text: 'Id', sortable: true, dataIndex: 'idEmpresa', width:50},
                {text: 'Empresa', sortable: true, dataIndex: 'nmEmpresa', width:290},
                {text: 'Contato', sortable: true, dataIndex: 'usuEmpresa'},
                {text: 'Telefone', sortable: true, dataIndex: 'telEmpresa', width:70},
                {text: 'E-mail', sortable: true, dataIndex: 'emailEmpresa'},
                {text: 'Ativação', sortable: true, dataIndex: 'usuarioCad'},
                {text: 'Data', sortable: true, dataIndex: 'dataAtivacao', width:70},
                {text: 'Tec Alfa', sortable: true, dataIndex: 'teclado', width:38, menuDisabled: true},
                {
                    xtype: 'actioncolumn',
                    width: 35,
                    align: 'center',
                    menuDisabled: true,
                    items: [
                        <?
                        if($permissao['delete'] == 'T') {
                        ?>
                        {
                            icon: 'imagens/16x16/delete.png',
                            tooltip: 'Desativar',
                            handler: function(grid, rowIndex, colIndex) {
                                Ext.Msg.confirm('Atenção', 'Você deseja desativar a empresa?', function (button) {
                                    if (button == 'yes') {
                                        //console.info('vai desativar a empresa');
                                        var rec = grid.getStore().getAt(rowIndex);   
                                        //console.info(rec, rec.get('idEmpresa'));
                                        Ext.Ajax.request({
                                            url: 'exec/execEmpresas.php',
                                            method: 'POST',
                                            params: {
                                                acao: 'desativar',
                                                idEmpresa: rec.get('idEmpresa')
                                            },
                                            success: function(records) {
                                                var result = Ext.decode(records.responseText);

                                                if(result.status == 'OK') {
                                                    Ext.Msg.show({
                                                        title: 'Sucesso!',
                                                        msg: result.msg,
                                                        icon: Ext.Msg.INFO,
                                                        buttons: Ext.Msg.OK
                                                    });
                                                    storeEmp.reload();
                                                    //Ext.getCmp('cadastro_empresa').close();
                                                } else {
                                                    Ext.Msg.show({
                                                        title: 'Erro!',
                                                        msg: result.msg,
                                                        icon: Ext.Msg.ERROR,
                                                        buttons: Ext.Msg.OK
                                                    });
                                                }
                                            },
                                            failure: function(){
                                                Ext.Msg.alert('Ops!', 'Houve algum problema com a comunicação... <br><br>Tente novamente. Se persistir o erro verifique sua conexão de internet ou entre em contato com a equipe de Jornada.');
                                            }
                                        });
                                    }
                                });
                            }
                        }
                        <? }?>
                    ]
                }
            ],
            renderTo: 'tmpId'
        });
    });
</script>
<div id="tmpId" style="width:100%"></div>

<form id="formListCondExportPrint" method="post" action="pdf/pdfCartaoPonto.php" target="print">
    <input type="hidden" id="relContent" name="relContent" value=""/>
    <input type="hidden" id="formListCondIdEmpresa" name="formListCondIdEmpresa" value=""/>
</form>

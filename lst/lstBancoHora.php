<?php
    require_once('../includes/Controles.class.php');

    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);

    $idEmpresa  = $_REQUEST['idEmpresa'];

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

    var hiddenAdicionar         = <?=$hiddenAdicionar?>;
    var hiddenAlterarExcluir    = <?=$hiddenAlterarExcluir?>;

    Ext.define('modelBancoHoras', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idBancoHoras', type: 'int'},
            {name: 'dataIni', type: 'string'},
            {name: 'minSemana', type: 'int'},
            {name: 'userIdCad', type: 'int'},
            {name: 'dataCad', type: 'string'},
            {name: 'minSabado', type: 'int'},
            {name: 'userNameCad', type: 'string'},
            {name: 'vencimento', type: 'string'},
            {name: 'idPeriodo', type: 'int'},
            {name: 'descPeriodo', type: 'string'},
            {name: 'idEmpresa', type: 'int'}
        ]
    });

    var storeBancoHoras = Ext.create('Ext.data.Store', {
        model: 'modelBancoHoras',
        autoLoad : true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonBancoHoras.php',
            reader: {
                type: 'json',
                root: 'bancoHoras'
            },
            extraParams: {
                idEmpresa: '<?=$idEmpresa?>'
            }
        }
    });

    Ext.onReady(function () {

        var gridBancoHoras = Ext.create('Ext.grid.Panel', {
            id: 'idBancoHoras',
            width: 850,
            store: storeBancoHoras,
            height: 200,
            columnLines: true,
            style: 'margin-bottom:10px; margin-top:10px;',
            border: true,
            forceFit: true,
            renderTo: 'contentIds',
            viewConfig: {
                emptyText: '<b>Cadastre o banco de horas clicando no botão (Novo Banco de Horas) no lado direito superior da sua tela</b>',
                deferEmptyText: false
            },
            columns: [
                {
                    header: 'ID',
                    dataIndex: 'idBancoHoras',
                    menuDisabled: true,
                    hidden: true
                },
                {
                    header: 'Data de cadastro',
                    dataIndex: 'dataCad',
                    menuDisabled: true,
                    hidden: true
                },
                {
                    header: 'Data de Início',
                    dataIndex: 'dataIni',
                    menuDisabled: true
                },
                {
                    header: 'Previsto Dias Úteis',
                    //dataIndex: 'minSemana',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return rectime(record.get('minSemana'));
                    }
                },
                {
                    header: 'Previsto Sábados',
                    //dataIndex: 'minSabado',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        return rectime(record.get('minSabado'));
                    }
                },
                {
                    header: 'Cadastro',
                    dataIndex: 'userNameCad'
                },
                {
                    header: 'Período',
                    dataIndex: 'descPeriodo'
                },
                {
                    header: 'Dia de Vencimento',
                    dataIndex: 'vencimento',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('vencimento') < 10){
                            return '0'+record.get('vencimento');
                        }else{
                            return record.get('vencimento');
                        }
                    }
                },
                {
                    xtype: 'actioncolumn',
                    width: 20,
                    align: 'center',
                    items: [{
                        icon: 'imagens/16x16/database_delete.png',
                        tooltip: 'Desativar Banco de Horas',
                        disabled: false,
                        handler: function(grid, rowIndex, colIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            Ext.Msg.confirm('Atenção:', 'Você deseja desativar este banco de horas?', function (button) {
                                if (button == 'yes') {
                                    storeBancoHoras.removeAt(rowIndex);
                                    Ext.Ajax.request({
                                        url: 'exec/execBancoHoras.php',
                                        method: 'POST',
                                        params: {
                                            acao: 'delete',
                                            idBancoHoras: rec.data.idBancoHoras
                                        },
                                        success: function(records) {
                                            var result = Ext.decode(records.responseText);
                                            console.info(result);

                                            if(result.status == 'OK') {
                                                Ext.Msg.show({
                                                    title: 'Sucesso!',
                                                    msg: result.msg,
                                                    icon: Ext.Msg.INFO,
                                                    buttons: Ext.Msg.OK
                                                });
                                                storeBancoHoras.sync();
                                                storeBancoCondutor.reload();
                                            }else{
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
                            })
                        }
                    }]
                }
            ]
        });

        var panelGrid1 = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelGrid1',
            bodyPadding: 5,
            title: '<b>Cadastrados</b>',
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
                    {xtype: gridBancoHoras}
                ]
            }]
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
                        {xtype: panelGrid1}
                    ]
                }
            ],
            renderTo: 'contentIds'
        });
    });


</script>

<div id="contentIds" ></div>
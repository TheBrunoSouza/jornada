<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo = new OracleCielo();
    $conexao = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

    if(isset($_SESSION)){
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
        $grupoUsu = $CtrlAcesso->getUserGrupo($_SESSION);
        $ocultarEmpresa = ($grupoUsu == 6 || $grupoUsu == 1) ? 'n' : 's';
    } else {
        header('Location: http://jornada.cielo.ind.br');
    }

    //(!$CtrlAcesso->checkUsuario($_SESSION))?exit:null;
    $permissao = $CtrlAcesso->checkPermissao('', $_SERVER['PHP_SELF']);
?>
<script>    
    function removerUsuario(idUsuario) {
        Ext.Ajax.request({
            url: 'exec/execUsuarios.php',
            method: 'POST',
            params: {
                acao: 'delete',
                idUsuario: idUsuario
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

    var ocultarEmpresa = true;

    if('<?=$ocultarEmpresa?>' != 's') {
        ocultarEmpresa = false;
    }
    Ext.onReady(function() {
        var tbPag = new Ext.Toolbar({
            border: false,
            items: [
            <? if($permissao['add'] == 'T') { ?>
            {
                text: 'Novo Usuário',
                iconCls: 'add',
                handler: function() {
                    showWindow('cadastro_usuario', 'Cadastro de Usuário', 'cad/cadUsuarios.php', 'acao=insert', 450, 240, true, true);
                }
            },
            <? } ?>
            { 
                xtype: 'button', 
                text: 'Exportar',
                iconCls: 'pdf',
                handler: function() {
                    var color,
                        tabela  = '',
                        x = 0;
                        //emp;
                    storeUsuario.each( function (model) {
                        tabela += '<tr bgcolor="'+color+'">';
                            tabela += '<td style="width:100px;">'+ model.get('idUsuario')+'</td>';
                            tabela += '<td>'+ model.get('nomeUsuario') +'</td>';
                            tabela += '<td>'+ model.get('descricaoTipoUsuario') +'</td>';
                            tabela += '<td style="width:auto;">'+ model.get('nomeEmpresa') +'</td>';
                        tabela += '</tr>';
                        x++;
                        //emp = model.get('nomeEmpresa');
                        color = (color == '') ? "#E1EEF4" : '';
                    });

                    tabela += '<tr bgcolor="'+color+'"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
                    tabela += '<tr bgcolor="'+color+'" style="font-weight: bold;"><td>Total: '+x+'</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
                    if(x > 0) {
                        //Ext.getDom('formListUsrIdEmpresa').value = emp;
                        Ext.getDom('relContent').value = tabela;
                        Ext.getDom('formListUsrExportPrint').action = 'pdf/pdfUsuarios.php';
                        Ext.getDom('formListUsrExportPrint').submit();
                    } else {
                        Ext.Msg.show({
                            title: 'Aviso!',
                            msg: 'Não há registros para geração do relatório em PDF.<br/>Tente filtrar a busca novamente!',
                            icon: Ext.Msg.INFO,
                            buttons: Ext.Msg.OK
                        });
                    }
                } 
            },
            {
                text: 'Filtrar',
                iconCls: 'filter',
                handler: function() {
                    showWindow(
                        'filtro_usuario',
                        'Filtro de Usuário', 
                        'filter/filUsuarios.php', 
                        '', 
                        460, 
                        130,
                        true, 
                        true
                    );
                }
            }]   
        });

        Ext.define('Usuarios', {
            extend: 'Ext.data.Model',
            fields:[
                {name: 'idUsuario', type: 'int'},
                {name: 'nomeUsuario', type: 'string'},
                {name: 'loginUsuario', type: 'string'},
                {name: 'emailUsuario', type: 'string'},
                {name: 'idTipoUsuario', type: 'int'},
                {name: 'descricaoTipoUsuario', type: 'string'},
                {name: 'idEmpresa', type: 'int'},
                {name: 'nomeEmpresa', type: 'string'}
            ]
        });

        var storeUsuario = Ext.create('Ext.data.Store', {
            model: 'Usuarios',
            id: 'storeUsuariosId',
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: 'json/jsonUsuarios.php',
                reader: {
                    type: 'json',
                    root: 'usuarios'
                },
                extraParams: {
                    idEmpresa:'<?=($empresaUsu) ? $empresaUsu : $_REQUEST['idEmpresa']?>',
                    grupoUsu: '<?=$grupoUsu?>',
                    nmUsuario: '<?=$_REQUEST['nmUsuario']?>'
                }
            }
        });

        Ext.create('Ext.grid.Panel', {
            //hideCollapseTool: true,
            id: 'contentGridIdUsuarios',
            border: false,
            store: storeUsuario,
            height: Ext.getBody().getHeight()-94,
            columnLines: true,
            tbar: tbPag,
            forceFit: true,
            viewConfig: {
                emptyText: '<b>Nenhum registro de usuário encontrado para este filtro</b>',
                deferEmptyText: false            
            },
            columns: [
                {text: 'Código', sortable: true, dataIndex: 'idUsuario', width: 25},
                {text: 'Nome', sortable: true, dataIndex: 'nomeUsuario'},
                {text: 'Login', sortable: true, dataIndex: 'loginUsuario'},
                {text: 'E-mail', menuDisabled: true, dataIndex: 'emailUsuario'},
                {hidden: true, dataIndex: 'idTipoUsuario'},
                {text: 'Tipo', sortable: true, dataIndex: 'descricaoTipoUsuario'},
                {hidden: true, dataIndex: 'id_empresa'},
                {text: 'Empresa', hidden: ocultarEmpresa, dataIndex: 'nomeEmpresa'},
                {
                    xtype:'actioncolumn',
                    width: 20,
                    text: 'Ação',
                    align: 'center',
                    menuDisabled: true,
                    items: [
                        <? 
                        $aux = 0;
                        if($permissao['edit']=='T'){
                        ?>
                            {
                                icon: 'imagens/16x16/edit.png',
                                tooltip: 'Editar',
                                handler: function(grid, rowIndex, colIndex) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    showWindow(
                                            'cadastro_usuario',
                                            'Editar usuário', 
                                            'cad/cadUsuarios.php', 
                                            'acao=update'
                                                +'&idUsuario='+rec.raw.idUsuario
                                                +'&nomeUsuario='+rec.raw.nomeUsuario
                                                +'&emailUsuario='+rec.raw.emailUsuario
                                                +'&idTipoUsuario='+rec.raw.idTipoUsuario
                                                +'&descricaoTipoUsuario='+rec.raw.descricaoTipoUsuario
                                                +'&loginUsuario='+rec.raw.loginUsuario
                                                +'&idEmpresa='+rec.raw.idEmpresa
                                                +'&nomeEmpresa='+rec.raw.nomeEmpresa,
                                            500, 
                                            270, 
                                            true, 
                                            true
                                    );
                                }
                            }
                            <? $aux++;
                        }
                        if($permissao['delete']=='T'){
                                if($aux > 0)echo ',';
                        ?>	
                            {
                                icon: 'imagens/16x16/delete.png',
                                tooltip: 'Excluir',
                                handler: function(grid, rowIndex, colIndex) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    Ext.Msg.confirm('Atenção', 'Você deseja excluir o registro?', function (button) {
                                        if (button == 'yes') {
                                            storeUsuario.removeAt(rowIndex);
                                            removerUsuario(rec.raw.idUsuario);
                                            storeUsuario.sync();
                                        }
                                    })
                                }
                            }
                        <? }?>
                    ]
                }
            ],
            renderTo: 'tmpId<?=$situacao?>'
        });
    });
</script>

<div id="tmpId<?=$situacao?>" style="width:100%"></div>

<form id="formListUsrExportPrint" method="post" action="pdf/pdfUsuarios.php" target="print">
    <input type="hidden" id="relContent" name="relContent" value=""/>
    <!--<input type="hidden" id="formListUsrIdEmpresa" name="formListUsrIdEmpresa" value=""/>-->
</form>
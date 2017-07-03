<?
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo = new OracleCielo();
    $conexao = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

    if(isset($_SESSION)){
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
        $grupoUsu = $CtrlAcesso->getUserGrupo($_SESSION);
    } else {
        header('Location: http://jornada.cielo.ind.br');
    }

    $permissao = $CtrlAcesso->checkPermissao('', $_SERVER['PHP_SELF']);
?>
<script>
    Ext.onReady(function () {
        var tbPag = new Ext.Toolbar({
            border: false,
            items: [
                <? if($permissao['add']=='T'){?>
//                {
//                    text: 'Novo Condutor',
//                    iconCls: 'add',
//                    handler: function (){
//                        showWindow(
//                            'cadastro_condutor',
//                            'Cadastro de Condutor',
//                            'cad/cadCondutores.php',
//                            'acao=insert',
//                            400,
//                            400,
//                            true,
//                            true
//                        );
//                    }
//                },
                <? } ?>
                {
                    xtype: 'button',
                    text: 'Exportar',
                    iconCls: 'pdf',
                    handler: function() {
                        var color,
                            tabela  = '',
                            x = 0,
                            emp;
                        storeCond.each( function (model) {
                            var plc = model.get('plcCondutor') ? model.get('plcCondutor') : '-';
                            tabela += '<tr bgcolor="'+color+'"><td>'+ model.get('idCondutor')+'</td><td>'+ model.get('nmCondutor') +'</td><td>'+plc+'</td></tr>';
                            x++;
                            emp = model.get('empCondutor');
                            color = (color == '') ? "#E1EEF4" : '';
                        });

//                        tabela+='<tr bgcolor="'+color+'"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
//                        tabela+='<tr bgcolor="'+color+'" style="font-weight: bold;"><td>Total: '+x+'</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
                        if(x > 0) {
                            Ext.getDom('formListCondIdEmpresa').value = emp;
                            Ext.getDom('totalCondutores').value = x;
                            Ext.getDom('relContent').value = tabela;
                            Ext.getDom('formListCondExportPrint').action = 'pdf/pdfCondutores.php';
                            Ext.getDom('formListCondExportPrint').submit();
                        } else {
                            Ext.Msg.show({
                                title: 'Aviso!',
                                msg: 'Não há registros para geração do relatório em PDF.<br/>Tente filtrar a busca novamente!',
                                icon: Ext.Msg.INFO,
                                buttons: Ext.Msg.OK
                            });
                        }
                    }
                }, {
                    text: 'Filtrar',
                    iconCls: 'filter',
                    handler: function() {
                        showWindow(
                            'filtro_condutor',
                            'Filtro de Condutor',
                            'filter/filCondutores.php',
                            '',
                            460,
                            130,
                            true,
                            true
                        );
                    }
                }
            ]
        });

        Ext.define('Condutores', {
            extend: 'Ext.data.Model',
            requires : 'Ext.data.Model',
            fields:[
                {name: 'idCondutor', type: 'int'},
                {name: 'nmCondutor', type: 'string'},
                {name: 'profissaoCondutor', type: 'string'},
                {name: 'cpfCondutor', type: 'string'},
                {name: 'rgCondutor', type: 'string'},
                {name: 'celularCondutor', type: 'string'},
                {name: 'matriculaCondutor', type: 'string'},
                {name: 'dtNascCondutor', type: 'string'},
                {name: 'empCondutor', type: 'string'},
                {name: 'plcCondutor', type: 'string'},
                {name: 'idEmpresa', type: 'int'}
            ]
        });

        var storeCond = Ext.create('Ext.data.Store', {
            model: 'Condutores',
            id: 'storeCondutoresId',
            autoLoad : true,
            proxy: {
                type: 'ajax',
                url: 'json/jsonCondutores.php',
                reader: {
                    type: 'json',
                    root: 'condutores'
                },
                extraParams: {
                    idEmpresa:'<?=($empresaUsu) ? $empresaUsu : $_REQUEST['idEmpresa']?>',
                    grupoUsu: '<?=$grupoUsu?>',
                    nmCondutor: '<?=$_REQUEST['nmCondutor']?>'
                }
            }
        });

        Ext.create('Ext.grid.Panel', {
            id: 'contentGridIdCondutores',
            border: false,
            store: storeCond,
            height: Ext.getBody().getHeight()-94,
            columnLines: true,
            tbar: tbPag,
            forceFit: true,
            viewConfig: {
                emptyText: '<b>Nenhum registro de condutor encontrado para este filtro</b>',
                deferEmptyText: false
            },
            columns: [
                {text: 'Código', sortable: true, dataIndex: 'idCondutor', width: 50},
                {text: 'Nome', sortable: true, dataIndex: 'nmCondutor', width: 250},
                {text: 'Profissão', sortable: true, dataIndex: 'profissaoCondutor', width: 150},
                {text: 'CPF', sortable: true, dataIndex: 'cpfCondutor'},
                {text: 'RG', sortable: true, dataIndex: 'rgCondutor'},
                {text: 'Celular', menuDisabled: true, dataIndex: 'celularCondutor'},
                {text: 'Matricula', menuDisabled: true, dataIndex: 'matriculaCondutor'},
                {text: 'Nascimento', sortable: true, dataIndex: 'dtNascCondutor', width: 70},
                {text: 'Placa', sortable: true, dataIndex: 'plcCondutor', width: 70},
                //{text: 'Situação', sortable: true, dataIndex: 'sitCondutor'},
                {
                    xtype: 'actioncolumn',
                    text: 'Ação',
                    width: 50,
                    align: 'center',
                    menuDisabled: true,
                    items: [
                        {
                            icon: 'imagens/16x16/date_add.png',
                            tooltip: 'Afastamento',
                            handler: function(grid, rowIndex, colIndex) {
                                var aux = grid.getStore().getAt(rowIndex);
                                showWindow(
                                    'cad_afastamento_condutor',
                                    'Afastamento - '+aux.get('nmCondutor'),
                                    'cad/cadAfastamento.php',
                                    'idCondutor='+aux.get('idCondutor'),
                                    600, //largura
                                    470, //altura
                                    true,
                                    true
                                );
                            }
                        },
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
                                    'cadastro_condutor',
                                    'Alteração de condutor',
                                    'cad/cadCondutores.php',
                                    'acao=update'+
                                            '&idCondutor='+rec.get('idCondutor')+
                                            '&nmCondutor='+rec.get('nmCondutor')+
                                            '&cpfCondutor='+rec.get('cpfCondutor')+
                                            '&rgCondutor='+rec.get('rgCondutor')+
                                            '&celularCondutor='+rec.get('celularCondutor')+
                                            '&matriculaCondutor='+rec.get('matriculaCondutor')+
                                            '&dtNascCondutor='+rec.get('dtNascCondutor')+
                                            '&profissaoCondutor='+rec.get('profissaoCondutor')+
                                            '&idEmpresa='+rec.get('idEmpresa'),
                                    400,
                                    265,
                                    true,
                                    true
                                );
                            }
                        }
                        <? $aux++;
                        }
                        if($permissao['delete']=='T'){
                        if($aux>0)echo ',';
                        ?>
                        {
                            icon: 'imagens/16x16/delete.png',
                            tooltip: 'Desativar',
                            handler: function(grid, rowIndex, colIndex) {
                                var rec = grid.getStore().getAt(rowIndex);
                                Ext.Msg.confirm('Atenção:', 'Você deseja desativar o condutor ' + rec.get('nmCondutor') + '?', function (button) {
                                    if (button == 'yes') {
                                        Ext.Ajax.request({
                                            url: 'exec/execCondutores.php',
                                            method: 'POST',
                                            params: {
                                                acao: 'desativar',
                                                idCondutor: rec.get('idCondutor')
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
                                                    storeCond.reload();
                                                    //Ext.getCmp('cadastro_condutor').close();
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


<form id="formListCondExportPrint" method="post" action="pdf/pdfCondutores.php" target="print">
    <input type="hidden" id="relContent" name="relContent" value=""/>
    <input type="hidden" id="formListCondIdEmpresa" name="formListCondIdEmpresa" value=""/>
    <input type="hidden" id="totalCondutores" name="totalCondutores" value=""/>
</form>

<?
require_once('../includes/Controles.class.php');
require_once('../includes/OracleCieloJornada.class.php');

$OraCielo   = new OracleCielo();
$conexao    = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

//Configurações de permissão -- Seta a propriedade hidden do objeto como false ou true:
$permissaoConfiguracoesFeriado  = $CtrlAcesso->checkPermissao(19, '');
//$permissao                      = 'true';

if($permissaoConfiguracoesFeriado){
    if($permissaoConfiguracoesFeriado['add'] == 'T'){
        $hiddenAdicionar = 'false';
    }else{
        $hiddenAdicionar = 'true';
    }
    
    if($permissaoConfiguracoesFeriado['edit'] == 'T'){
        $hiddenAlterarExcluir = 'false';
    }else{
        $hiddenAlterarExcluir = 'true';
    }
}

?>

<script>
    
var hiddenAdicionar         = <?=$hiddenAdicionar?>;
var hiddenAlterarExcluir    = <?=$hiddenAlterarExcluir?>;
var idEmpresa               = '<?=$_REQUEST['idEmpresa']?>';


Ext.onReady(function(){
    
    function realizarAjax(operacao, idFeriado){
        Ext.Ajax.request({
            url: 'json/jsonFeriados.php',
            params: {
                acao: operacao,
                idEmpresa: idEmpresa,
                idFeriado: idFeriado
            },
            success: function(conn, response, options, eOpts) {
                var result = Ext.decode(conn.responseText);
                if (result.status === false) {
                    Ext.Msg.show({
                        title: 'Erro!',
                        msg: 'Favor informar o departamento de TI.',
                        icon: Ext.Msg.ERROR,
                        buttons: Ext.Msg.OK
                    });
                } else {
                    Ext.Msg.show({
                        title: 'Informação:',
                        msg: 'Feriado excluído!',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }
            },
            failure: function(conn, response, options, eOpts) {
                Ext.Msg.show({
                    title: 'Erro!',
                    msg: 'Entre em contato com o administrador do sistema.',
                    icon: Ext.Msg.ERROR,
                    buttons: Ext.Msg.OK
                });
            }
        });
    }

    Ext.define('modelFeriados', {
        extend: 'Ext.data.Model',
        requires: 'Ext.data.Model',
        fields:[
            {name: 'idFeriado', type: 'string'},
            {name: 'idEmpresa', type: 'string'},
            {name: 'descricao', type: 'string'},
            {name: 'data', type: 'string'}
        ]
    });

    var varStoreFeriados = Ext.create('Ext.data.Store', {
        model: 'modelFeriados',
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonFeriados.php',
            reader: {
                type: 'json',
                root: 'feriados'
            },
            extraParams: {
                idEmpresa: idEmpresa,                
                acao: 'filtrar'
            }
        }
    });
    
    var buttonCadastrarFeriado = Ext.create('Ext.Button', {
        id: 'idButtonCadFeriado',
        text: 'Cadastrar Feriado',
        iconCls: 'add',
        style: 'margin-top: 13px; margin-left: 5px;',
        handler: function (){

            var buttonFiltrar = Ext.create('Ext.Button', {
                id: 'buttonFiltrar',
                text: 'Salvar',
                icon: 'imagens/16x16/add.png',
                handler: function () {
                    console.info('ok');
                }
            });

            var buttonCancelar = Ext.create('Ext.Button', {
                id: 'buttonCancelar',
                text: 'Cancelar',
                icon: 'imagens/16x16/cancel.png',
                handler: function () {
                    console.info('ok');
                }
            });

            var toolbarTeste = Ext.create('Ext.toolbar.Toolbar', {
                id: 'teste',
                region: 'south',
                items: [
                    '->',
                    {xtype: buttonCancelar},
                    {xtype: buttonFiltrar}
                ]
            });

            showWindowToolbar(
                'cad_feriado',
                'Novo Feriado',
                'cad/cadFeriado.php',
                '',
                400,
                200,
                true,
                true,
                toolbarTeste
            );
        }
    });
    
    var tbFeriados = new Ext.Toolbar({
        border: false,
        items:  [
//            {xtype: comboEmpresaFeriado},
            {xtype: buttonCadastrarFeriado}
        ]
    });

    Ext.create('Ext.grid.Panel', {
        store: varStoreFeriados,
        id: 'gridFeriados',
        forceFit: true,
        columnLines: true,
        viewConfig: {
            emptyText: "Nenhum feriado cadastrado para sua empresa."
        },
        tbar: tbFeriados,
        columns: [
            {header: 'idFeriado', dataIndex: 'idFeriado', hidden: true},
            {header: 'idEmpresa', dataIndex: 'idEmpresa', hidden: true},
            {header: 'Descricao', dataIndex: 'descricao'},
            {header: 'Data', dataIndex: 'data'},
            {
                header: 'Apagar',
                xtype:'actioncolumn',
                width: 30,
                align:'center',
                menuDisabled: true,
                items: [
                    {
                        icon: 'imagens/16x16/delete.png',
                        tooltip: 'Remover feriado',
                        hidden: hiddenAlterarExcluir,
                        handler: function(grid, rowIndex, colIndex) {
                            var dadosLinha = grid.getStore().getAt(rowIndex);
                            Ext.Msg.confirm('Atenção:', 'Excluindo este feriado, deve-se regerar o período que será afetado.<br><br> Você deseja excluir este feriado? <br>', function (button) {
                                if (button === 'yes') {
                                    var acao = 'excluir';
                                    realizarAjax(acao, dadosLinha.get('idFeriado'));
                                    varStoreFeriados.removeAt(rowIndex);
                                }
                            });
                        }
                    }
                ]
            }
        ],
        renderTo: 'divFeriados'
    });
});
</script>

<div id="divFeriados"></div>
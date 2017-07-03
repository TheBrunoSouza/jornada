<?
require_once('../includes/Controles.class.php');
require_once('../includes/OracleCieloJornada.class.php');

$OraCielo   = new OracleCielo();
$conexao    = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

//Configurações de permissão:
$permissaoConfiguracoesTempo    = $CtrlAcesso->checkPermissao(17, '');
//$permissao                      = 'true';

if($permissaoConfiguracoesTempo){
    if($permissaoConfiguracoesTempo['add'] == 'T'){
        $permissaoAdicionar = 'false';
    }else{
        $permissaoAdicionar = 'true';
    }
    
    if($permissaoConfiguracoesTempo['edit'] == 'T'){
        $permissaoAlterarExcluir = 'false';
    }else{
        $permissaoAlterarExcluir = 'true';
    }
}

?>

<script>

var permissaoAdicionar      = <?=$permissaoAdicionar?>;
var permissaoAlterarExcluir = <?=$permissaoAlterarExcluir?>;
var idEmpresa               = '<?=$CtrlAcesso->getUserEmpresa($_SESSION)?>';

if(idEmpresa == ''){
    console.info('Usuário sem empresa - Administrador');
}
    
Ext.onReady(function(){
    
    function realizarAjax(operacao, idConfiguracao){
        
//        Ext.Ajax.request({
//            url: 'json/jsonEmpresaConfiguracoes.php',
//            params: {
//                acao: operacao,
//                idEmpresa: idEmpresa,
//                idConfiguracao: idConfiguracao
//            },
//            success: function(conn, response, options, eOpts) {
//                var result = Ext.decode(conn.responseText);
//                if (result.status === false) {
//                    Ext.Msg.show({
//                        title:'Erro!',
//                        msg: 'Favor informar o departamento de TI.',
//                        icon: Ext.Msg.ERROR,
//                        buttons: Ext.Msg.OK
//                    });
//                } else {
//                    Ext.Msg.show({
//                        title:'Informação:',
//                        msg: 'Configuração desativada!',
//                        icon: Ext.Msg.INFO,
//                        buttons: Ext.Msg.OK
//                    });
//                }
//            },
//            failure: function(conn, response, options, eOpts) {
//                Ext.Msg.show({
//                    title:'Erro!',
//                    msg: 'Entre em contato com o administrador do sistema.',
//                    icon: Ext.Msg.ERROR,
//                    buttons: Ext.Msg.OK
//                });
//            }
//        });
    }

    Ext.define('modelEmpresaConfiguracoes', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[	
            {name: 'idConfiguracao', type: 'string'},
            {name: 'idEmpresa', type: 'string'},
            {name: 'descricao', type: 'string'}, 
            {name: 'tempo', type: 'string'}
        ]
    });

    var varStoreEmpresaConfiguracoes = Ext.create('Ext.data.Store', {
        model: 'modelEmpresaConfiguracoes',
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonEmpresaConfiguracoes.php',
            reader: {
                type: 'json',
                root: 'empConfig'
            },
            extraParams: {
                idEmpresa: idEmpresa,
                acao: 'configuracoesAplicadas'
            }
        }
    });
  
    var comboEmpresaConfig = Ext.create('Ext.form.ComboBox', {
	fieldLabel: 'Empresa',
	labelWidth: 50,
	columnWidth: 90,
        width: 430,
        style: 'margin-top: 13px; margin-left: 5px;',
	queryMode: 'local',
	id: 'idComboEmpresaConfig',
	name: 'nameComboEmpresaConfig',
	displayField: 'nmEmpresa',
	valueField: 'idEmpresa',
	store: varStoreEmpresaConfiguracoes,
        allowBlank: false,
        hidden: permissaoAdicionar,
	emptyText: 'Selecione...',
	listeners: {
            select: function(f, r, i){
                var maskCp = new Ext.LoadMask('contentId', { msg: "Carregando..."});
                maskCp.show();
                idEmpresa = Ext.getCmp('idComboEmpresaConfig').value;
                Ext.getCmp('gridConfiguracao').getStore().load({
                    params:{
                        'idEmpresa': idEmpresa
                    },
                    callback:function(){
                        maskCp.hide();
                    }
                });
            }
        }
    });
    
    var buttonAplicarConfig = Ext.create('Ext.Button', {
        text: 'Aplicar nova configuração',
        id: 'idButtonAplicarConfig',
        hidden: permissaoAdicionar,
        iconCls: 'accept',
        handler: function (){
            if(Ext.getCmp('idComboEmpresaConfig').value == null || Ext.getCmp('idComboEmpresaConfig').value == ''){
                Ext.Msg.show({
                    title:'Atenção:',
                    msg: 'Selecione a empresa.',
                    icon: Ext.Msg.INFO,
                    buttons: Ext.Msg.OK
                });
            }else{
                showWindow(
                    'lst_tipos_manutencoes',
                    'Tipos de Configurações:',
                    'lst/lstTiposConfiguracoes.php',
                    'idEmpresa='+Ext.getCmp('idComboEmpresaConfig').value,
                    400,
                    200,
                    true,
                    true
                );
            }
        }
    });
    
//    var buttonCadastrarConfig = Ext.create('Ext.Button', {
//        text: 'Cadastrar Configuração',
//        id: 'idButtonCadastrarConfig',
//        hidden: permissaoAdicionar,
//        iconCls: 'add',
//        handler: function (){
//                showWindow(
//                    'lst_tipos_manutencoes',
//                    'Tipos de Configurações:',
//                    'lst/lstTiposConfiguracoes.php',
//                    'idEmpresa='+Ext.getCmp('idComboEmpresaConfig').value,
//                    400,
//                    200,
//                    true,
//                    true
//                );
//        }
//    });
    
    var tbPag = new Ext.Toolbar({
        border: false,
        hidden: permissaoAdicionar,
        items:  [
            {xtype: comboEmpresaConfig},
            ' ',
            {xtype: buttonAplicarConfig}
        ]
    });

    Ext.create('Ext.grid.Panel', {
        store: varStoreEmpresaConfiguracoes,
        id: 'gridConfiguracao',
        forceFit: true,
        columnLines: true,
        viewConfig: {
            emptyText: 'Entre em contato com o suporte Jornada para configurar seus controladores de tempos...'
        },
        columns: [
            {dataIndex: 'idConfiguracao', hidden: true},
            {dataIndex: 'idEmpresa', hidden: true},
            {header: 'Configuração', dataIndex: 'descricao', menuDisabled: permissaoAlterarExcluir},
            {header: 'Tempo', dataIndex: 'tempo', menuDisabled: permissaoAlterarExcluir},
            { 
                header: 'Ação',
                xtype: 'actioncolumn',
                align: 'center',
                width: 8,
                hidden: permissaoAlterarExcluir,
                menuDisabled: permissaoAlterarExcluir,
                items: [
                    {
                        icon: 'imagens/16x16/edit.png',
                        tooltip: 'Alterar tempos',
                        handler: function(grid, rowIndex, colIndex) {
                            var dadosLinha = grid.getStore().getAt(rowIndex);
                            showWindow(
                                'idWindowEditEmpresaConfig',
                                dadosLinha.get('descricao') + ":",
                                'cad/cadEmpresaConfiguracao.php',
                                'idConfiguracao=' + dadosLinha.get('idConfiguracao') +
                                '&descricaoConfiguracao=' + dadosLinha.get('descricao') +
                                '&tempoConfiguracao=' + dadosLinha.get('tempo') +
                                '&idEmpresa=' + dadosLinha.get('idEmpresa'),
                                350,
                                170,
                                true,
                                true
                            );
                        }
                    },{
                        icon: 'imagens/16x16/delete.png',
                        tooltip: 'Remover configuração',
                        handler: function(grid, rowIndex, colIndex) {
                            var dadosLinha = grid.getStore().getAt(rowIndex);
                            Ext.Msg.confirm('Atenção:', 'Você deseja desativar esta configuração?', function (button) {
                                if (button === 'yes') {
                                    varStoreEmpresaConfiguracoes.removeAt(rowIndex);                                        
                                    var acao = 'desativar';
                                    realizarAjax(acao, dadosLinha.get('idConfiguracao'));
                                }
                            });
                        }
                    }
                ]
            }
        ],
        renderTo: 'contentId',
        tbar: tbPag
    });
});
</script>

<div id="contentId"></div>
<?
require_once('../includes/Controles.class.php');
require_once('../includes/OracleCieloJornada.class.php');

$OraCielo   = new OracleCielo();
$conexao    = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
?>

<script>
idEmpresa = '<?=$_REQUEST['idEmpresa']?>';
    
Ext.onReady(function () {
    
    Ext.define('modelTiposConfiguracoes', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
        fields:[
            {name: 'idConfiguracao', type: 'string'},
            {name: 'descricao', type: 'string'}
        ]
    });

    var varStoreTiposConfiguracoes = Ext.create('Ext.data.Store', {
        model: 'modelTiposConfiguracoes',
        autoLoad: true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonEmpresaConfiguracoes.php',
            reader: {
                type: 'json',
                root: 'tiposConfig'
            },
            extraParams: {
                idEmpresa: idEmpresa,
                acao: 'configuracoesDisponiveis'
            }
        }
    });

    Ext.create('Ext.grid.Panel', {
        id: 'gridTiposConfiguracoes',
        border: false,
        store: varStoreTiposConfiguracoes,
        autoHeight: true,
        columnLines: true,
        forceFit: true,
        renderTo: 'divConteudo',
        viewConfig: {
              emptyText: 'Todas as configurações estão aplicadas para esta empresa.'        
        },
        columns: [
            {text: 'ID', sortable: true, dataIndex: 'idConfiguracao', hidden: true},
            {text: 'Configuração', sortable: true, dataIndex: 'descricao'},
            {
                text: 'Ação',
                xtype: 'actioncolumn',
                width: 10,
                align:'center',
                menuDisabled: true,
                items: [
                    {
                        icon: 'imagens/16x16/accept.png',
                        tooltip: 'Aplicar Configuração',
                        handler: function(grid, rowIndex, colIndex) {
                            var dadosLinha = grid.getStore().getAt(rowIndex);
                            showWindow(
                                "configurar",
                                dadosLinha.get('descricao')+":",
                                "cad/cadEmpresaConfiguracao.php",
                                "idConfiguracao="+dadosLinha.get('idConfiguracao')+"&idEmpresa="+idEmpresa+'&descricaoConfiguracao&tempoConfiguracao',
                                350,
                                170,
                                true,
                                true
                            );
                        }
                    }
                ]
            }
        ]
    });
});

</script>
<div id="divConteudo"></div>
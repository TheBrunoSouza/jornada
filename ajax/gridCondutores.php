<?
require_once('../includes/Controles.class.php');

$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$idEmpresa  = $CtrlAcesso->getUserEmpresa($_SESSION);
?>
<script>
Ext.define('Condutores', {
	extend: 'Ext.data.Model',
	requires : 'Ext.data.Model',
	fields:[
	    {name: 'idCondutor', type: 'int'},
        {name: 'nmCondutor', type: 'string'},
        {name: 'cnhCondutor', type: 'string'},
        {name: 'dtNascCondutor', type: 'string'},
        {name: 'cpfCondutor', type: 'string'},
        {name: 'rgCondutor', type: 'string'},
        {name: 'telCondutor', type: 'string'},
        {name: 'celCondutor', type: 'string'},
        {name: 'matCondutor', type: 'string'},
        {name: 'empCondutor', type: 'string'},
        {name: 'sitCondutor', type: 'string'},
        {name: 'plcCondutor', type: 'string'}
    ]
});

Ext.onReady(function () {
	var storeGrid<?=$_REQUEST['gridIdSituacao']?> = Ext.create('Ext.data.Store', {
		model: 'Condutores',
		//groupField: 'SeasonName',
		autoLoad : true,
		proxy: {
			type : 'ajax',
			url  : 'json/jsonCondutores.php',
			reader: {
				type: 'json',
				root: 'condutores'
			},
			extraParams:{
				gridIdSituacao:'<?=$_REQUEST['gridIdSituacao']?>',
				idEmpresa: '<?=$idEmpresa?>'
			}
		}
	});
		
	Ext.create('Ext.grid.Panel', {
		//hideCollapseTool: true,
		id   : 'contentGridId<?=$_REQUEST['gridIdSituacao']?>',
		border: false,
		store: storeGrid<?=$_REQUEST['gridIdSituacao']?>,
		autoHeight: true,
        width: Ext.getCmp('west-panel').getWidth()+50,
		columnLines: false,
        hideHeaders: true,
		columns: [
			{
				text     : 'Condutores',
				flex     : 1,
				sortable : false,
				menuDisabled: true,
				dataIndex: 'nmCondutor'
			}
		],
		listeners: {
			'select': function(grid, rowIndex, colIndex) {

                var dataSel = Ext.getCmp('idDateJornada') ? Ext.getCmp('idDateJornada') : null;
                if(dataSel !== null) {
                    panelLoad('gestorTabId', 'Diário de Bordo', 'ajax/diariodebordo.php', 'nmCondutor='+rowIndex.data.nmCondutor+'&idCondutor='+rowIndex.data.idCondutor+'&dataSel='+dataSel.value);
                } else {
                    panelLoad('gestorTabId', 'Diário de Bordo', 'ajax/diariodebordo.php', 'nmCondutor='+rowIndex.data.nmCondutor+'&idCondutor='+rowIndex.data.idCondutor);
                }
			}
		},
		viewConfig: {
			stripeRows: true
		},
		renderTo: 'tmpId<?=$_REQUEST['gridIdSituacao']?>'
	});
});

</script>

<div id="tmpId<?=$_REQUEST['gridIdSituacao']?>" style="width:100%"></div>
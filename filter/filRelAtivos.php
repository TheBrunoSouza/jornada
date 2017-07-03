<?
require('../includes/BancoPost.class.php');
$conexao = new BancoPost();

require_once('../includes/Controles.class.php');
$CtrlAcesso=new Controles($_SERVER['REMOTE_ADDR'], $conexao);
//(!$CtrlAcesso->checkUsuario($_SESSION))?exit:null;

$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);

?>
<script>
	var dateIni = new Ext.form.DateField({
						id: 'idDateIni',
						fieldLabel: 'Início:',
						labelWidth: 70,
						format:"d/m/Y",
						width: 180,
						value:Ext.Date.add(new Date, Ext.Date.MONTH, -1)
				});

	var dateFim = new Ext.form.DateField({
						id: 'idDateFim',
						fieldLabel: 'Fim',
						labelWidth: 70,
						format:"d/m/Y",
						width: 180,
						value: new Date()
				}); 
	Ext.define('Empresas', {
		extend: 'Ext.data.Model',
		//requires : 'Ext.data.Model',
		fields:[{name: 'idEmpresa', type: 'int'},
				{name: 'nmEmpresa', type: 'string'},
				{name: 'respEmpresa', type: 'string'},
				{name: 'telEmpresa', type: 'string'},
				{name: 'emailEmpresa', type: 'string'},
				{name: 'cepEmpresa', type: 'string'},
				{name: 'ufEmpresa', type: 'string'},
				{name: 'cidEmpresa', type: 'string'},
				{name: 'usuEmpresa', type: 'string'}]
	});

	var storeEmpresa = Ext.create('Ext.data.Store', {
		model: 'Empresas',
		autoLoad : true,
		proxy: {
			type : 'ajax',
			url  : 'json/jsonEmpresas.php',
			reader: {
				type: 'json',
				root: 'empresas'
			},
			extraParams:{
				idEmpresa:'<?=$empresaUsu?>'
			}
		}
	});

	Ext.create('Ext.form.Panel', {
		//title: 'Simple Form',
		bodyPadding: 5,
		width: '100%',
		layout: 'anchor',
		border: false,
		id: 'filtroRelCondutores',
		defaults: {
			anchor: '100%'
		},
		items: [
			{
				xtype: 'combobox',
				fieldLabel: 'Empresa',
				labelWidth: 70,
				queryMode: 'local',
				id: 'idEmpresa',
				name: 'idEmpresa',
				displayField: 'nmEmpresa',
				valueField: 'idEmpresa',
				store: storeEmpresa,
				emptyText: 'Seleciona a opção',
				submitEmptyText: false,
				disabled: <?=($empresaUsu)?'true':'false'?>
			},{
				xtype: dateIni,
			},{
				xtype: dateFim,
			}
		],
		// Reset and Submit buttons
		buttons: [{
			text: 'Limpar',
			handler: function() {
				this.up('form').getForm().reset();
			}
		}, {
			text: 'Enviar',	
			formBind: true, //only enabled once the form is valid
			disabled: true,
			handler: function() {
				if (Ext.getCmp("idEmpresa").value) {
					//var mask = new Ext.LoadMask('gestorRelId', {msg:"Carregando..."});
					//mask.show();
					Ext.getStore('storeCondutoresId').load({
						params:{
							'dtIni': Ext.Date.format(Ext.getCmp('idDateIni').value, 'Y-m-d'),
							'dtFim': Ext.Date.format(Ext.getCmp('idDateFim').value, 'Y-m-d'),
							'idEmpresa':Ext.getCmp('idEmpresa').value
						},
						callback:function(records, operation, success) {
							//mask.hide();
							Ext.getCmp('filtro_ativo').destroy();
						}
					});
				}
				else
					Ext.Msg.alert('Atenção!', 'Preencha corretamente todos os campos!');
			}
		}],
		renderTo: 'contentId'
	});
	<? if($idEmpresa or $empresaUsu){?>
	Ext.getCmp("idEmpresa").setValue(<?=($idEmpresa)?$idEmpresa:$empresaUsu?>);
	<?
	}
	?>
</script>

<div id="contentId"></div>
<?php
require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

if(isset($_SESSION)){
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
}else{
    header('Location: http://jornada.cielo.ind.br');
}

$loadEmpresa            = ($empresaUsu)?'false':'true';
$dataIniFiltroInicial   = $_REQUEST['dataIni'];
$dataFimFiltroInicial   = $_REQUEST['dataFim'];
$empresaFiltroInicial   = $_REQUEST['empresa'];
$condutorFiltroInicial  = $_REQUEST['condutor'];
$checkAtivos            = $_REQUEST['checkAtivos'];

?>

<script>

Ext.onReady(function () {
    
    Ext.define('EmpresasAvancado', {
        extend: 'Ext.data.Model',
        fields:[
            {name: 'idEmpresa', type: 'int'},
            {name: 'nmEmpresa', type: 'string'},
            {name: 'respEmpresa', type: 'string'},
            {name: 'telEmpresa', type: 'string'},
            {name: 'emailEmpresa', type: 'string'},
            {name: 'cepEmpresa', type: 'string'},
            {name: 'ufEmpresa', type: 'string'},
            {name: 'cidEmpresa', type: 'string'},
            {name: 'VeicEmpresa', type: 'string'},
            {name: 'he100', type: 'string'}
        ]
    });

    Ext.define('CondutoresAvancado', {
        extend: 'Ext.data.Model',
        requires: 'Ext.data.Model',
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
            {name: 'sitCondutor', type: 'string'}
        ]
    });

    var storeEmpresaAvancado = Ext.create('Ext.data.Store', {
        model: 'EmpresasAvancado',
        autoLoad : <?=$loadEmpresa?>,
        proxy: {
            type: 'ajax',
            url: 'json/jsonEmpresas.php',
            reader: {
                type: 'json',
                root: 'empresas'
            }
        }
    });

    var storeCondutorAvancado = Ext.create('Ext.data.Store', {
        model: 'CondutoresAvancado',
        autoLoad : true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonCondutores.php',
            reader: {
                type: 'json',
                root: 'condutores'
            },
            extraParams: {
                idEmpresa: '<?=$empresaUsu?>',
                ativo: 'T'
            }
        }
    });

    var comboCondutorAvan = Ext.create('Ext.form.ComboBox', {
        fieldLabel: 'Nome:',
        labelWidth: 40,
        width: 405,
        style: 'margin-top: 6px; margin-left: 50px;',
        queryMode: 'local',
        id: 'idCondutorAvancado',
        name: 'idCondutor',
        displayField: 'nmCondutor',
        valueField: 'idCondutor',
        store: storeCondutorAvancado,
        allowBlank: false,
        emptyText: 'Selecione um condutor...'
    });
    
    var checkBoxAtivo = Ext.create('Ext.form.Checkbox', {
        xtype: 'checkbox',
        labelWidth: 35,
        id:'idAtivo', 
        name: 'nmAtivo',
        fieldLabel: 'Ativo',
        checked: 'true',
        style: 'margin-top: 6px; margin-bottom: 10px;',
        listeners:{
            change: function(cc, ix, isChecked){
                var aux;
                if(isChecked === true){
                    aux = 'F';
                }else{
                    aux = 'T';
                }
                storeCondutor.getProxy().extraParams = { idEmpresa: '<?=$empresaUsu?>', ativo: aux};
                storeCondutor.load();
                Ext.getCmp("idCondutorAvancado").setValue('');	
            }
        }	
    });

    var comboEmpresaAvan = Ext.create('Ext.form.ComboBox', {
        id: 'idEmpresaPontoAvancado',
        fieldLabel: 'Empresa',
        labelWidth: 55,
        width: 508,
        style: 'margin-top: 4px;',
        queryMode: 'local',
        name: 'idempresaPonto',
        displayField: 'nmEmpresa',
        valueField: 'idEmpresa',
        store: storeEmpresaAvancado,
        readOnly: <?=($empresaUsu)?'true':'false'?>,
        hidden: <?=($empresaUsu)?'true':'false'?>,
        emptyText: 'Selecione uma empresa...',
        listeners:{
            select: function(f, r, i){			  
                storeCondutorAvancado.getProxy().extraParams = { idEmpresa: f.getValue()};
                storeCondutorAvancado.load();
                Ext.getCmp("idCondutor").setValue('');			   

                Ext.getCmp('gridCartaoId').columns[3].setVisible(r[0].get('he100')=='t');				
            }   
        }
    });

    var dateIniAvan = Ext.create('Ext.form.DateField', {
        id: 'idDateIniAvancado',
        fieldLabel: 'Data inicial:',
        labelWidth: 80,
        width: 180,
        style: 'margin-left: 3px; margin-top: 8px;',
        value: Ext.Date.add(new Date(), Ext.Date.MONTH, -1),
        format: "d/m/Y"
    });

    var dateFimAvan = Ext.create('Ext.form.DateField', {
        id: 'idDateFimAvancado',
        fieldLabel: 'Data final',
        labelWidth: 70,
        width: 170,
        style: 'margin-left: 170px; margin-top: 8px;',
        value: new Date(),
        format: "d/m/Y"
    }); 
    
    var horaIni = Ext.create('Ext.form.TextField', {
        id: 'idTxtHoraInicialAvancado',
        fieldLabel: 'Hora Inicial',
        labelWidth: 80,
        maxLength: 5,
        maskRe: /[0-9:]/,
        width: 163,
        style: 'margin-left: 3px; margin-top: 6px;',
        name: 'nameTxtHoraInicial',
        emptyText: '00:00',
        value: ''
    });
    
    var horaFim = Ext.create('Ext.form.TextField', {
        id: 'idTxtHoraFinalAvancado',
        fieldLabel: 'Hora Final',
        labelWidth: 70,
        maxLength: 5,
        maskRe: /[0-9:]/,
        width: 153,
        style: 'margin-left: 187px; margin-top: 6px;',
        name: 'nameTxtHoraFinal',
        emptyText: '00:00',
        value: ''
    });
    
    var buttonLimpar = Ext.create('Ext.Button', {
        xtype: 'button', 
        text: 'Limpar',
        iconCls: 'clear',
        handler: function() {
            Ext.getCmp('idPanelFilAvancadoCartaoPonto').getForm().reset();
        }
    });
    
    var buttonFiltrar = Ext.create('Ext.button.Button', {
        xtype: 'button', 
        text: 'Filtrar',
        formBind: true,
        disabled: true,
        iconCls: 'filter',
        handler: function() {
            reloadCartao();
        }
    });
    
    var panelCondutor = Ext.create('Ext.form.FieldSet', {
        id: 'idPanelCondutor',
        bodyPadding: 5,
        title: 'Condutor',
        width: '100%',
        layout: 'anchor',
        defaults: {
            border: false
        },
        items: [
            {xtype: comboEmpresaAvan},
            {
                layout: 'column',
                items: [
                    {xtype: checkBoxAtivo},
                    {xtype: comboCondutorAvan}                        
                ]
            }
        ]}
    );
    
    Ext.create('Ext.form.Panel', {
        id: 'idPanelFilAvancadoCartaoPonto',
        bodyPadding: 5,
        buttonAlign : 'center',
        width: '100%',
        layout: 'anchor',
        defaults: {
            anchor: '100%',
            border: false
        },
        items: [
            {xtype: panelCondutor},
            {
                layout: 'column',
                items: [
                    {xtype: dateIniAvan},
                    {xtype: dateFimAvan}
                ]
            },
            {
                layout: 'column',
                items: [
                    {xtype: horaIni},
                    {xtype: horaFim}
                ]
            }
        ],
        buttons: [
            {xtype: buttonLimpar},
            {xtype: buttonFiltrar}
        ],
        renderTo: 'divFilAvancado'
    });
});
</script>
<div id="divFilAvancado"></div>
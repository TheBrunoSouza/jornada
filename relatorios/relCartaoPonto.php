<?
//error_reporting(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo       = new OracleCielo();
$conexao        = $OraCielo->getCon();
$CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$empExtra100    = $CtrlAcesso->getExtra100($_SESSION, $conexao);

if(isset($_SESSION)){
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
}else{
    header('Location: http://jornada.cielo.ind.br');
}

if($empresaUsu){
    $loadEmpresa    = 'false';
    $hiddenEmpresa  = 'true';
}else{
    $empresaUsu     = $_REQUEST['idEmpresa'];
    $loadEmpresa    = 'true';
    $hiddenEmpresa  = 'false';
}
?>

<script>

    var totalAtestado       = '',
        totalFerias         = '',
        totalAfastamento    = '',
        txt100              = '',
        totAtestado         = 0,
        totFerias           = 0,
        totAfastamento      = 0;
    var totJornada          = 0,
        totEspera           = 0,
        totHrExtra          = 0,
        totExtra100         = 0,
        totNoturna          = 0;

    function renderTopic(value, p, record) {
        return Ext.String.format(
            '<a href="http://sencha.com/forum/showthread.php?t={2}" target="_blank">{0}</a>',
            value,
            record.data.data,
            record.getId(),
            record.data.data
        );
    }

    function setMinDateFieldFim(dateMin, dati) {
        dateMin.setMinValue(dati);
    }

    function reloadCartao(){
        var dataIni         = '',
            dataFim         = '',
            idCondutor      = '',
            horaIni         = '',
            horaFim         = '';


        if(Ext.getCmp('filtro_avancado_cartao_ponto')){
            dataIni     = Ext.Date.format(Ext.getCmp('idDateIniAvancado').value, 'dmY');
            dataFim     = Ext.Date.format(Ext.getCmp('idDateFimAvancado').value, 'dmY');
            idCondutor  = Ext.getCmp('idCondutorAvancado').value;
            horaIni     = Ext.getCmp('idTxtHoraInicialAvancado').value;
            horaFim     = Ext.getCmp('idTxtHoraFinalAvancado').value;

            Ext.getCmp('filtro_avancado_cartao_ponto').destroy();
        }else{
            dataIni     = Ext.Date.format(Ext.getCmp('idDateIni').value, 'dmY');
            dataFim     = Ext.Date.format(Ext.getCmp('idDateFim').value, 'dmY');
            idCondutor  = Ext.getCmp('idCondutor').value;
        }

        var mask = new Ext.LoadMask('gestorRelId', {msg: "Carregando..."});
        mask.show();

        Ext.getCmp("gridCartaoId").reconfigure(storeCartao);

        storeCartao.load({
            params:{
                'dtIni': dataIni,
                'dtFim': dataFim,
                'idCondutor': idCondutor,
                'horaIni': horaIni,
                'horaFim': horaFim
            },
            callback: function(records, operation, success) {
                mask.hide();

                //console.info(operation);

                if(!operation.error){
                    var result = Ext.decode(operation.response.responseText);

                    if(result.status == 'OK'){

                            totJornada          = 0;
                            totEspera           = 0;
                            totHrExtra          = 0;
                            totExtra100         = 0;
                            totNoturna          = 0;
                            txt100              = '';
                            totalAfastamento    = '';
                            totalFerias         = '';
                            totalAtestado       = '';


                        storeCartao.each(function (rec) {
                            totJornada = rec.get('TotalJornada');
                            totEspera   += rec.get('TmpEspera');
                            totHrExtra  += rec.get('TmpHrExtra');
                            totExtra100 += rec.get('TmpExtra100');
                            totNoturna  += rec.get('TempHrNoturna');
                        });

                        if('<?=$empExtra100?>' == 'true'){
                            txt100 = ' | Extra (100%): ' + rectime(totExtra100);
                        }

                        totAtestado = result.afastamentos[0]['tmpAfastAtestado'];
                        totFerias   = result.afastamentos[0]['tmpAfastFerias'];
                        totAfastamento = result.afastamentos[0]['tmpAfastAfastamento'];

                        //Afastamentos
                        if(totAtestado > 0){
                            totalAtestado = ' | Atestado: ' + rectime(totAtestado);
                        }
                        if(totFerias > 0){
                            totalFerias = ' | Ferias: ' + rectime(totFerias);
                        }
                        if(totAfastamento > 0){
                            totalAfastamento = ' | Afastadas: ' + rectime(totAfastamento);
                        }

                        Ext.getCmp('idTotal').setText(
                            '<b>'           +
                            'Totais: '      +
                            '</b>'          +
                            ' Jornada: '    + rectime(totJornada)   +
                            ' | Espera: '   + rectime(totEspera)    +
                            ' | Extra: '    + rectime(totHrExtra)   +
                            txt100          +
                            totalAtestado   +
                            totalFerias     +
                            totalAfastamento+
                            ' | Noturnas: ' + rectime(totNoturna)
                        );

                    }else{
                        Ext.Msg.show({
                            title: 'Erro!',
                            msg: 'Favor informar o departamento de TI.',
                            icon: Ext.Msg.ERROR,
                            buttons: Ext.Msg.OK
                        });
                    }
                }else{
                    Ext.Msg.alert('Ops!', 'Houve algum problema com a sua comunicação... <br><br>Tente novamente. Se persistir o erro verifique sua conexão de internet ou entre em contato com a equipe de Jornada.');
                }
            }
        });

        storeJustifPonto.load({
            params:{
                'dtIni': Ext.Date.format(Ext.getCmp('idDateIni').value, 'd/m/Y'),
                'dtFim': Ext.Date.format(Ext.getCmp('idDateFim').value, 'd/m/Y'),
                'idCondutor': Ext.getCmp('idCondutor').value
            }
        });
    }

    Ext.define('Empresas', {
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

    var storeEmpresa = Ext.create('Ext.data.Store', {
        model: 'Empresas',
        autoLoad : <?=$loadEmpresa?>,
        proxy: {
            type: 'ajax',
            url : 'json/jsonEmpresas.php',
            reader: {
                type: 'json',
                root: 'empresas'
            }
        }
    });

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
            {name: 'sitCondutor', type: 'string'}
        ]
    });

    var storeCondutor = Ext.create('Ext.data.Store', {
        model: 'Condutores',
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

    Ext.define('modelCartaoPonto', {
        extend: 'Ext.data.Model',
        requires: 'Ext.data.Model',
        fields:[
            {name: 'idCondutor', type: 'int'},
            {name: 'nmCondutor', type: 'string'},
            {name: 'diarioDt', type: 'string'},
            {name: 'Data', type: 'string'},
            {name: 'Dia', type: 'string'},
            {name: 'Jornada', type: 'string'},
            {name: 'HoraExtra', type: 'string'},
            {name: 'HrExtra100', type: 'string'},
            {name: 'Espera', type: 'string'},
            {name: 'Refeicao', type: 'string'},
            {name: 'Descanso', type: 'string'},
            {name: 'Repouso', type: 'string'},
            {name: 'TmpJornada', type: 'int'},
            {name: 'TotalJornada', type: 'int'},
            {name: 'TmpEspera', type: 'int'},
            {name: 'TmpHrExtra', type: 'int'},
            {name: 'TmpExtra100', type: 'int'},
            {name: 'Justif', type: 'string'},
            {name: 'Afastamento', type: 'string'},
            {name: 'DescAfast', type: 'string'},
            {name: 'TempHrNoturna', type: 'int'}
        ]
    });

    var storeCartao = Ext.create('Ext.data.Store', {
        storeId: 'cartaPontoStore',
        model: 'modelCartaoPonto',
        proxy: {
            type: 'ajax',
            url: 'json/jsonCartao.php',
            reader: {
                type: 'json',
                root: 'cartaoPonto'
            }
        }
    });

    //Justificativa de alteração do ponto\\
    Ext.define('modelJustPonto', {
        extend: 'Ext.data.Model',
        requires: 'Ext.data.Model',
        fields:[
            {name: 'dataIni', type: 'string'},
            {name: 'dataAlteracao', type: 'string'},
            {name: 'descricao', type: 'string'},
            {name: 'nomeUsuario', type: 'string'}
        ]
    });

    var storeJustifPonto = Ext.create('Ext.data.Store', {
        storeId: 'JustPontoStore',
        model: 'modelJustPonto',
        proxy: {
            type: 'ajax',
            url: 'json/jsonJustPontoAlt.php',
            reader: {
                type: 'json',
                root: 'logJornada'
            }
        }
    });

    var dateFim = Ext.create('Ext.form.DateField', {
        id: 'idDateFim',
        fieldLabel: 'Fim',
        labelWidth: 23,
        maxLength: 10,
        minLength: 10,
        maskRe: /[0-9/]/,
        format: "d/m/Y",
        emptyText: 'dd/mm/aaaa',
        maxValue: new Date(),
        width: 125,
        value: <?=($_REQUEST['dtFim']) ? "new Date('".$_REQUEST['dtFim']."')" : 'new Date()'?>
    });

    var dateIni = Ext.create('Ext.form.DateField', {
        id: 'idDateIni',
        fieldLabel: 'Início:',
        labelWidth: 33,
        maxLength: 10,
        minLength: 10,
        maskRe: /[0-9/]/,
        format: "d/m/Y",
        emptyText: 'dd/mm/aaaa',
        maxValue: new Date(),
        width: 135,
        value: <?=($_REQUEST['dtIni']) ? "new Date('".$_REQUEST['dtIni']."')" : "Ext.Date.add(new Date(), Ext.Date.MONTH, -1)"?>,
        listeners: {
            'afterrender': function(me) {
                setMinDateFieldFim(dateFim, me.getSubmitValue());
            },
            'change': function(me) {
                setMinDateFieldFim(dateFim, me.getSubmitValue());
            }
        }
    });

    var comboEmpresa = Ext.create('Ext.form.ComboBox', {
        fieldLabel: 'Empresa',
        labelWidth: 45,
        width: 250,
        queryMode: 'local',
        id: 'idEmpresaPonto',
        name: 'idempresaPonto',
        displayField: 'nmEmpresa',
        valueField: 'idEmpresa',
        value: '',
        store: storeEmpresa,
        readOnly: <?=($hiddenEmpresa)?>,
        hidden: <?=($hiddenEmpresa)?>,
        emptyText: 'Selecione para filtrar condutores',
        listeners:{
            select: function(f, r, i){
                storeCondutor.getProxy().extraParams = { idEmpresa: f.getValue()};
                storeCondutor.load();
                Ext.getCmp("idCondutor").setValue('');

                Ext.getCmp('gridCartaoId').columns[3].setVisible(r[0].get('he100')=='t');
                empresaHidden.setValue(Ext.getCmp('idEmpresaPonto').value);
            }
        }
    });

    var comboCondutor = Ext.create('Ext.form.ComboBox', {
        fieldLabel: 'Condutor:',
        labelWidth: 50,
        width: 250,
        style: 'margin-top: 8px;',
        queryMode: 'local',
        id: 'idCondutor',
        name: 'idCondutor',
        displayField: 'nmCondutor',
        valueField: 'idCondutor',
        store: storeCondutor,
        emptyText: 'Selecione para filtrar...',
        listeners:{
            select: function(f, r, i){
                if(Ext.getCmp('idDateIni').value == '' || Ext.getCmp('idDateIni').value == null || Ext.getCmp('idDateFim').value == '' || Ext.getCmp('idDateFim').value == null || Ext.getCmp('idDateIni').rawValue.length != 10 || Ext.getCmp('idDateFim').rawValue.length != 10){
                    Ext.Msg.show({
                        title:'Atenção:',
                        msg: 'Você deve preencher as data de inicio e fim corretamente',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    reloadCartao();
                }
            }
        }
    });

    var empresaHidden = Ext.create('Ext.form.field.Hidden', {
        name: 'idEmpresaHidden',
        id: 'idEmpresaHidden',
        value: '<?=$empresaUsu?>'
    });

    var buttonFiltrar = Ext.create('Ext.Button', {
        xtype: 'button',
        text: 'Filtrar',
        iconCls: 'filter',
        style: 'margin-top: 8px;',
        handler: function() {
            if(Ext.getCmp('idDateIni').value == '' || Ext.getCmp('idDateIni').value == null || Ext.getCmp('idDateFim').value == '' || Ext.getCmp('idDateFim').value == null || Ext.getCmp('idDateIni').rawValue.length != 10 || Ext.getCmp('idDateFim').rawValue.length != 10){
                Ext.Msg.show({
                    title:'Atenção:',
                    msg: 'Você deve preencher as data de inicio e fim corretamente',
                    icon: Ext.Msg.INFO,
                    buttons: Ext.Msg.OK
                });
            }else{
                if(Ext.getCmp('idCondutor').value === null || Ext.getCmp('idCondutor').value === ''){
                    Ext.Msg.show({
                        title:'Atenção:',
                        msg: 'Selecione o condutor',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    reloadCartao();
                }
            }
        }
    });

    var buttonResumir = Ext.create('Ext.Button', {
        xtype: 'button',
        text: 'Resumir',
        icon: 'imagens/16x16/application_view_list.png',
        handler: function() {

            if(Ext.getCmp('idDateIni').value == '' || Ext.getCmp('idDateIni').value == null || Ext.getCmp('idDateFim').value == '' || Ext.getCmp('idDateFim').value == null || Ext.getCmp('idDateIni').rawValue.length != 10 || Ext.getCmp('idDateFim').rawValue.length != 10){
                Ext.Msg.show({
                    title:'Atenção:',
                    msg: 'Você deve preencher as data de inicio e fim corretamente',
                    icon: Ext.Msg.INFO,
                    buttons: Ext.Msg.OK
                });
            }else{
                if(Ext.getCmp('idCondutor').value === null || Ext.getCmp('idCondutor').value === ''){
                    Ext.Msg.show({
                        title:'Atenção:',
                        msg: 'Selecione o condutor',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    panelLoad(
                        'gestorTabId',
                        'Cartão Ponto - Resumo',
                        'relatorios/relCartaoPontoResumido.php',
                        'idCondutor='+Ext.getCmp('idCondutor').value+
                        '&nomeCondutor='+Ext.getCmp('idCondutor').rawValue+
                        '&idEmpresa='+empresaHidden.value+
                        '&totais='+Ext.getCmp('idTotal').text+
                        '&totalJornada='+totJornada+
                        '&totalEspera='+totEspera+
                        '&totalExtra='+totHrExtra+
                        '&totalExtra100='+totExtra100+
                        '&totalEspera='+totEspera+
                        //'&totalRefeicao='+tot+
//                        '&totalDescanso='+tot+
//                        '&totalRepouso='+tot+
                        '&totalAfastadas='+totAfastamento+
                        '&totalFerias='+totFerias+
                        '&totalAtestado='+totAtestado+
                        '&totalNoturna='+totNoturna+
                        '&dataIni='+Ext.Date.format(Ext.getCmp('idDateIni').value, 'd/m/Y')+
                        '&dataFim='+Ext.Date.format(Ext.getCmp('idDateFim').value, 'd/m/Y')
                    );
                }
            }
        }
    });

    var buttonFiltroAvancado = Ext.create('Ext.Button', {
        xtype: 'button',
        text: 'Filtro Avançado',
        icon: 'imagens/16x16/search.png',
        handler: function() {
            var altura = 230;

            if('<?=$empresaUsu?>' !== ''){
                altura = 205;
            }

            showWindow(
                'filtro_avancado_cartao_ponto',
                'Filtro avançado:',
                'filter/filAvancadoCartaoPonto.php',
                'dataIni='+Ext.getCmp('idDateIni').value+'&'+'dataFim='+Ext.getCmp('idDateFim').value,
                555,
                altura,
                true,
                true
            );
        }
    });

    var toolbar = Ext.create('Ext.toolbar.Toolbar', {
        id: 'toolbarRelId',
        region: 'north',
        items: [
            ' ',
            {xtype: dateIni},
            '  ',
            {xtype: dateFim},
            '  ',
            {xtype:	comboEmpresa},
            ' ',
            {xtype: comboCondutor},
            {xtype: empresaHidden},
            '  ',
            '-',
            {xtype: buttonFiltrar},
            '->',
            {xtype: buttonResumir}//,
//            {xtype: buttonFiltroAvancado}
        ]
    });

    var buttonRegerarDiaBbarGrid = Ext.create('Ext.Button', {
        text: 'Regerar dia',
        style: 'margin-top: 8px;',
        iconCls: 'refresh',
        handler: function() {
            if(!Ext.getCmp('idCondutor').value){
                Ext.Msg.show({
                    title:'Atenção:',
                    msg: 'Selecione o condutor.',
                    icon: Ext.Msg.INFO,
                    buttons: Ext.Msg.OK
                });
            }else{
                if (Ext.getCmp('idWindRegera')){
                    Ext.getCmp('idWindRegera').destroy();
                }

                Ext.create('Ext.window.Window', {
                    title: 'Regerar dia de jornada:',
                    width: 200,
                    modal: true,
                    id: 'idWindRegera',
                    items: [{
                        xtype: 'form',
                        layout: 'form',
                        id: 'idFormRegera',
                        border: false,
                        padding: 5,
                        items: [{
                            xtype: 'datefield',
                            anchor: '100%',
                            fieldLabel: 'Data',
                            name: 'idDateRegera',
                            id: 'idDateRegera',
                            labelWidth: 40,
                            format:"d/m/Y",
                            value: new Date()
                        }]
                    }],
                    bbar: [{
                        text: 'Regerar',
                        iconCls: 'refresh',
                        handler: function(){
                            var mask = new Ext.LoadMask('idWindRegera', {msg:"Carregando..."});
                            mask.show();
                            var response = Ext.Ajax.request({
                                //async: false,
                                url: 'jobs/execRegeraJornada.php',
                                method: 'GET',
                                params: {
                                    condutor: Ext.getCmp('idCondutor').value,
                                    dataGeracao: Ext.Date.format(Ext.getCmp('idDateRegera').value, 'Y-m-d')
                                },
                                success: function(){
                                    Ext.getCmp('idWindRegera').destroy();
                                    mask.hide();
                                    var maskCp = new Ext.LoadMask('gestorRelId', {msg:"Carregando..."});
                                    maskCp.show();
                                    storeCartao.load({
                                        params:{
                                            'dtIni': Ext.Date.format(Ext.getCmp('idDateIni').value, 'dmY'),
                                            'dtFim': Ext.Date.format(Ext.getCmp('idDateFim').value, 'dmY'),
                                            'idCondutor':Ext.getCmp('idCondutor').value
                                        },
                                        callback:function(){
                                            maskCp.hide();
                                        }
                                    });
                                },
                                failure: function(){
                                    console.log('failure');
                                    mask.hide();
                                }
                            });
                        }
                    },
                        '-',
                        {
                            text: 'Cancelar',
                            iconCls: 'cancel',
                            handler: function(){
                                Ext.getCmp('idWindRegera').destroy();
                            }
                        }]
                }).show();
            }
        }
    });

    var buttonPdfCartaoPontoBbarGrid = Ext.create('Ext.Button', {
        text: 'Exportar para PDF',
        iconCls: 'pdf',
        handler: function() {
            if(Ext.getCmp('idDateIni').value == '' || Ext.getCmp('idDateIni').value == null || Ext.getCmp('idDateFim').value == '' || Ext.getCmp('idDateFim').value == null || Ext.getCmp('idDateIni').rawValue.length != 10 || Ext.getCmp('idDateFim').rawValue.length != 10){
                Ext.Msg.show({
                    title:'Atenção:',
                    msg: 'Você deve preencher as data de inicio e fim corretamente',
                    icon: Ext.Msg.INFO,
                    buttons: Ext.Msg.OK
                });
            }else{
                if(Ext.getCmp('idCondutor').value === null || Ext.getCmp('idCondutor').value === ''){
                    Ext.Msg.show({
                        title:'Atenção:',
                        msg: 'Selecione o condutor',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    var color = '#E1EEF4';

                    var tabela      = '',
                        tabelaJust  = '',
                        tabelaTotais = '',
                        tabelaAss   = '',
                        td100       = '',
                        tot100      = '',
                        header100   = '';

                    var totJornada  = 0,
                        totEspera   = 0,
                        totHrExtra  = 0,
                        totExtra100 = 0,
                        totNoturna  = 0,
                        flagJust    = 0;

                    if('<?=$empExtra100?>' == 'true'){
                        header100 = '<th>Extra100%</th>';
                    }

                    tabela +=
                        '<br/>' +
                        '<font size="7">' +
                        '<table id="total" cellpadding="4" cellspacing="2" style="text-align:left;">' +
                        '<thead>' +
                        '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                        '<th>Data</th>' +
                        '<th>Jornada</th>' +
                        '<th>Extra</th>' +
                        header100 +
                        '<th>Espera</th>' +
                        '<th>Refei&ccedil;&atilde;o</th>' +
                        '<th>Descanso</th>' +
                        '<th>Repouso</th>' +
                        '</tr>' +
                        '</thead>';

                    storeCartao.each( function (model) {
                        totJornada = model.get('TotalJornada');
                        totEspera   += model.get('TmpEspera');
                        totHrExtra  += model.get('TmpHrExtra');
                        totExtra100 += model.get('TmpExtra100');
                        totNoturna += model.get('TempHrNoturna');

                        var extra100 = '';

                        if('<?=$empExtra100?>' == 'true'){
                            extra100    = '<td>'+ model.get('HrExtra100') +'</td>';
                            td100       = '<td>&nbsp;</td>';
                            tot100      = '<td> Extra (100%): '+ rectime(totExtra100) +'</td>';
                        }

                        tabela +=
                            '<tr bgcolor="'+color+'">' +
                                '<td>'+ model.get('Data')+'</td>' +
                                '<td>'+ model.get('Jornada') +'</td>' +
                                '<td>'+ model.get('HoraExtra') +'</td>'+
                                extra100 +
                                '<td>'+ model.get('Espera') +'</td>' +
                                '<td>'+ model.get('Refeicao') +'</td>' +
                                '<td>'+ model.get('Descanso') +'</td>' +
                                '<td>'+ model.get('Repouso') +'</td>' +
                            '</tr>';

                        color = (color == '')?"#E1EEF4":'';
                    });

                    tabela += '<tbody></tbody></table></font>';

                    tabelaTotais += '<br/><font size="7">' +
                        '<table id="total" cellpadding="2" cellspacing="2" style="text-align:center;">' +
                            '<thead>' +
                                '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                                    '<th colspan="8">Totais</th>' +
                                '</tr>' +
                                '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                                    '<th>Jornada</th>' +
                                    '<th>Hora Extra</th>' +
                                    '<th>Horas Extra 100 (%)</th>' +
                                    '<th>Espera</th>' +
                                    '<th>Noturna</th>' +
                                    '<th>Atestado</th>' +
                                    '<th>Ferias</th>' +
                                    '<th>Afastadas</th>' +
                                '</tr>' +
                            '</thead>' +
                                '<tr>' +
                                    '<td>'+rectime(totJornada)+'</td>' +
                                    '<td>'+rectime(totHrExtra)+'</td>' +
                                    '<td>'+rectime(totExtra100)+'</td>' +
                                    '<td>'+rectime(totEspera)+'</td>' +
                                    '<td>'+rectime(totNoturna)+'</td>' +
                                    '<td>'+rectime(totAtestado)+'</td>' +
                                    '<td>'+rectime(totFerias)+'</td>' +
                                    '<td>'+rectime(totAfastamento)+'</td>' +
                                '</tr>'
                        '<tbody>';
                    tabelaTotais+='</tbody></table></font>';

                    //justificatia de alterações
                    tabelaJust += '<br/><font size="7">' +
                        '<table id="justificativas" cellpadding="2" cellspacing="2" style="text-align:center;">' +
                            '<thead>' +
                                '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                                    '<th colspan="4">Alterações/Justificativas</th>' +
                                '</tr>' +
                                '<tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">' +
                                    '<th>Data</th>' +
                                    '<th>Data Alteração</th>' +
                                    '<th>Usuario</th>' +
                                    '<th>Justificativa</th>' +
                                '</tr>' +
                            '</thead>' +
                        '<tbody>';

                    color = '';

                    storeJustifPonto.each( function (modelJust) {
                        tabelaJust += '<tr bgcolor="'+color+'"><td>'+modelJust.get('dataIni')+'</td><td>'+modelJust.get('dataAlteracao')+'</td><td>'+modelJust.get('nomeUsuario')+'</td><td style="text-align:left;">'+modelJust.get('descricao')+'</td></tr>';
                        color = (color == '') ?  "#E1EEF4" : '';
                        flagJust = 1;
                    });

                    tabelaJust+='</tbody></table></font>';

                    //controla exbição das justificativas
                    tabelaJust = (flagJust == 1) ? tabelaJust : '';

                    Ext.getDom('pontoContent').value            = tabela;
                    Ext.getDom('justificativaContent').value    = tabelaJust;
                    Ext.getDom('tableTotais').value             = tabelaTotais;
                    Ext.getDom('formIdEmpresaHidden').value     = Ext.getCmp('idEmpresaHidden').value;
                    Ext.getDom('formIdEmpresa').value           = Ext.getCmp('idEmpresaPonto').value;
                    Ext.getDom('formIdCondutor').value          = Ext.getCmp('idCondutor').value;
                    Ext.getDom('formNmCondutor').value          = Ext.getCmp('idCondutor').rawValue;
                    Ext.getDom('formDtIni').value               = Ext.Date.format(Ext.getCmp('idDateIni').value, 'Y-m-d');
                    Ext.getDom('formDtFim').value               = Ext.Date.format(Ext.getCmp('idDateFim').value, 'Y-m-d');
                    //Ext.getDom('assinaturaContent').value = tabelaAss;

                    Ext.getDom('formExportPrint').action        = 'pdf/pdfCartaoPonto.php';

                    Ext.getDom('formExportPrint').submit();
                    //console.info('-->>'+tabelaJust);
                }
            }
        }
    });

    Ext.onReady(function () {

        var totais = Ext.toolbar.TextItem({
            id: 'idTotal',
            xtype: 'tbtext',
            text: ''
        });

        var he100 = ('<?=$empExtra100?>' == 'true') ? false : true;

        var grid = Ext.create('Ext.grid.Panel', {
            tbar: toolbar,
            id: 'gridCartaoId',
            forceFit: true,
            height: Ext.getBody().getHeight()-59,
            autoWidth: true,
            renderTo: 'gestorRelId',
            viewConfig: {
                emptyText: '<b>Nenhum registro de jornada encontrado para este filtro</b>',
                deferEmptyText: false,
                getRowClass: function(record, rowIndex, rowParams, store) {
                    if(record.get('Afastamento') == "T"){
                        return 'afastamento';
                    }else{
//                        if(record.get('Dia') == "1"){
//                            return 'domingoCartaoPonto';
//                        }else{
                            return 'gridPointerRow';
//                        }
                    }

                }
            },
            columns: [
                {text: 'Data',  dataIndex: 'Data'},
                {
                    text: 'Jornada', dataIndex: 'Jornada', menuDisabled: true, tdCls: 'wrap',
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('Afastamento') == "T"){
                            return "<div value='' id='jornada_" + row + "'>"+record.get('DescAfast')+"</div>";
                        }else{
                            return record.get('Jornada');
                        }
                    }
                },
                {text: 'Hora Extra', dataIndex: 'HoraExtra', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('Afastamento') == "T"){
                            return "<div value='' id='extra_" + row + "'>"+record.get('DescAfast')+"</div>";
                        }else{
                            return record.get('HoraExtra');
                        }
                    }
                },
                {text: 'Extra 100%', dataIndex: 'HrExtra100', menuDisabled: true, hidden: he100,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('Afastamento') == "T"){
                            return "<div value='' id='extra100_" + row + "'>"+record.get('DescAfast')+"</div>";
                        }else{
                            return record.get('HrExtra100');
                        }
                    }
                },
                {text: 'Espera',  dataIndex: 'Espera', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('Afastamento') == "T"){
                            return "<div value='' id='espera_" + row + "'>"+record.get('DescAfast')+"</div>";
                        }else{
                            return record.get('Espera');
                        }
                    }
                },
                {text: 'Refeição', dataIndex: 'Refeicao', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('Afastamento') == "T"){
                            return "<div value='' id='refeicao_" + row + "'>"+record.get('DescAfast')+"</div>";
                        }else{
                            return record.get('Refeicao');
                        }
                    }
                },
                {text: 'Descanso',  dataIndex: 'Descanso', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('Afastamento') == "T"){
                            return "<div value='' id='descanso_" + row + "'>"+record.get('DescAfast')+"</div>";
                        }else{
                            return record.get('Descanso');
                        }
                    }
                },
                {text: 'Repouso',  dataIndex: 'Repouso', menuDisabled: true,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        if(record.get('Afastamento') == "T"){
                            return "<div value='' id='repouso_" + row + "'>"+record.get('DescAfast')+"</div>";
                        }else{
                            return record.get('Repouso');
                        }
                    }
                },
                {
                    xtype: 'actioncolumn',
                    width: 15,
                    align: 'center',
                    renderer: function (val, metadata, record) {
                        if (record.raw.Afastamento == "T") {
                            this.items[0].icon = null;
                            this.items[0].tooltip = null;
                        } else {
                            this.items[0].icon = 'imagens/16x16/edit.png';
                            this.items[0].tooltip = 'Editar';
                        }
                    },
                    items: [{
//                        icon: 'imagens/16x16/edit.png',
//                        tooltip: 'Editar',
                        handler: function(grid, rowIndex, colIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            if(rec.data.Afastamento == "T"){
//                                console.info('testeok');
                            }else{
                                showWindow(
                                    'edit_cartao_ponto',
                                    'Editar Cartão Ponto ' + rec.get('Data'),
                                    'cad/edtJornada.php',
                                    'idCondutor=' + rec.get('idCondutor') + '&dtEdit=' + rec.get('diarioDt') + '&dtIniFil=' + Ext.Date.format(Ext.getCmp('idDateIni').value, 'dmY') + '&dtFimFil=' + Ext.Date.format(Ext.getCmp('idDateFim').value, 'dmY'),
                                    490,
                                    390,
                                    true,
                                    true
                                );
                            }
                        }
                    }]
                },
                {
                    xtype: 'actioncolumn',
                    width: 15,
                    align: 'center',
                    renderer: function (val, metadata, record) {
                        if (record.raw.Justif == "F") {
                            this.items[0].icon = null;
                            this.items[0].tooltip = null;
                        } else {
                            this.items[0].icon = 'imagens/16x16/information.png';
                            this.items[0].tooltip = 'Exibir alterações';
                        }
                    },
                    items: [{
                        handler: function(grid, rowIndex, colIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            if(rec.data.Justif == "F"){
//                                console.info('testeok');
                            }else {
                                showWindow(
                                    'show_justificativas',
                                    'Justificativas das alterações do dia: '+rec.get('Data'),
                                    'lst/lstJustificativas.php',
                                    'idCondutor='+rec.get('idCondutor')+'&dtDiario='+rec.get('diarioDt'),
                                    990, //Largura
                                    460, //Altura
                                    true,
                                    true
                                );
                            }
                        }
                    }]
                },
            ],
            bbar: [
//                {xtype: 'tbtext', id: 'idTotalText', height: 30, text:' '},
                {xtype: totais},
                '->',
                {xtype: buttonRegerarDiaBbarGrid},
                {xtype: buttonPdfCartaoPontoBbarGrid}//,
                //            {xtype: buttonPdfJustBbarGrid}
            ]
        });

        grid.getSelectionModel().on('selectionchange', function(sm, selectedRecord){
            if(selectedRecord.length != 0) {
                if (selectedRecord[0].get('Afastamento') == 'F') {
                    var idCondutor = selectedRecord[0].get('idCondutor'),
                        nome = selectedRecord[0].get('nmCondutor'),
                        dt = selectedRecord[0].get('diarioDt'),
                        dtConfig = selectedRecord[0].get('Data'),
                        dtIni = Ext.Date.format(Ext.getCmp('idDateIni').value, 'd/m/Y'),
                        dtFim = Ext.Date.format(Ext.getCmp('idDateFim').value, 'd/m/Y');

                    panelLoad('gestorTabId', 'Cartão Ponto - Movimentação', 'relatorios/consCartao.php', 'idCondutor='+idCondutor+'&data='+dt+'&dtConf='+dtConfig+'&nomeCon='+nome+'&dtIni='+dtIni+'&dtFim='+dtFim+'&idEmpresa='+empresaHidden.value);

                }
            }
        });
    });

    <? if($_REQUEST['idCondutor']){
        if($_REQUEST['idEmpresa']){ ?>
            Ext.getCmp("idEmpresaPonto").setValue(<?=$_REQUEST['idEmpresa']?>);
        <?}else{?>
            Ext.getCmp("idEmpresaPonto").setValue(<?=$_REQUEST['idEmpresaC']?>);
        <?}?>
        Ext.getCmp("idCondutor").setValue(<?=$_REQUEST['idCondutor']?>);
        Ext.getCmp('idDateIni').setValue('<?=$_REQUEST['dataIni']?>')
        Ext.getCmp('idDateFim').setValue('<?=$_REQUEST['dataFim']?>')
        reloadCartao();
    <?}?>

</script>

<div id="gestorRelId" style="width: 100%; height: 100%;"></div>

<form id="formExportPrint" method="post" action="pdf/pdfCartaoPonto.php" target="print">
    <input type="hidden" id="pontoContent" name="pontoContent" value=""/>
    <input type="hidden" id="justificativaContent" name="justificativaContent" value=""/>
    <input type="hidden" id="tableTotais" name="tableTotais" value=""/>
    <input type="hidden" id="formIdEmpresaHidden" name="formIdEmpresaHidden" value=""/>
    <input type="hidden" id="formIdEmpresa" name="formIdEmpresa" value=""/>
    <input type="hidden" id="formIdCondutor" name="formIdCondutor" value=""/>
    <input type="hidden" id="formNmCondutor" name="formNmCondutor" value=""/>
    <input type="hidden" id="formDtIni" name="formDtIni" value=""/>
    <input type="hidden" id="formDtFim" name="formDtFim" value=""/>
</form>
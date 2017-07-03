<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');
    require_once('../includes/PrintPdf.class.php');

    $OraCielo           = new OracleCielo();
    $conexao            = $OraCielo->getCon();
    $CtrlAcesso         = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $printPdf           = new PrintPdf();
    $html               = '';
    $nomeCondutor       = $_REQUEST['nomeCondutor'];
    $minSemBH           = $_REQUEST['minSemBH'];
    $minSabBH           = $_REQUEST['minSabBH'];
    $minTotalBC         = $_REQUEST['minTotalBC'];
    $vencimentoBH       = $_REQUEST['vencimentoBH'];
    $descPeriodoBH      = $_REQUEST['descPeriodoBH'];
    $dataIni            = $_REQUEST['dataIni'];
    $dataFim            = $_REQUEST['dataFim'];
    $idBancoHoras       = $_REQUEST['idBancoHoras'];
    $idCondutor         = $_REQUEST['idCondutor'];
    $saldoBF            = $_REQUEST['saldoBF'];
    $totalTrabalhadoBF  = $_REQUEST['totalTrabalhadoBF'];
    $mediaTrabDia       = $_REQUEST['mediaTrabDia'];
    $mediaTrabSem       = $_REQUEST['mediaTrabSem'];
    $diasValorDobrado   = $_REQUEST['diasValorDobrado'];
    $saldoIni           = $_REQUEST['saldoIni'];
    $empresaUsu         = $CtrlAcesso->getUserEmpresa($_SESSION);
    $sqlAux             = "";
    $sqlAux2            = "";

    //Buscando fechamento realizado para este banco de horas
    $sqlAux = "
        SELECT 
            BANCO_TIPO_FECHAMENTO.DESCRICAO AS TIPO_FECHAMENTO,
            to_char(BANCO_FECHAMENTO.DT_FECHAMENTO, 'DD/MM/YYYY') AS DATA_FECHAMENTO,
            USUARIO.NOME AS USUARIO_FECHAMENTO,
            BANCO_FECHAMENTO.OBS_FECHAMENTO AS OBS_FECHAMENTO
        FROM  
            banco_fechamento, banco_tipo_fechamento, USUARIO
        WHERE 
            BANCO_FECHAMENTO.TIPO_FECHAMENTO        = BANCO_TIPO_FECHAMENTO.ID_TIPO_FECHAMENTO
            AND USUARIO.ID_USUARIO                  = BANCO_FECHAMENTO.USER_FECHAMENTO
            AND BANCO_FECHAMENTO.id_banco_horas     = ".$idBancoHoras."
            AND BANCO_FECHAMENTO.condutor           = ".$idCondutor."
            AND trunc(BANCO_FECHAMENTO.data_ini)    = to_date('".$dataIni."', 'DD/MM/YYYY')
            AND trunc(BANCO_FECHAMENTO.data_fim)    = to_date('".$dataFim."', 'DD/MM/YYYY')
    ";

    $respostaAux = oci_parse($conexao, $sqlAux);

    if(!oci_execute($respostaAux)){
        echo ' Em manutenção... Por favor, tente novamente mais tarde.';
        echo $sqlAux;
        exit();
    }else{
        #echo $sqlAux;exit();
        $dados = oci_fetch_assoc($respostaAux);
        #print_r($dados);exit();
    }

    //Buscando dados auxiliares do condutor
    $sqlAux2 = "
            SELECT mc.nome as nome_condutor, me.id_empresa, me.nome as nome_empresa, mc.profissao
            FROM monitoramento.empresa me, monitoramento.condutor mc 
            WHERE me.id_empresa = mc.empresa
                AND mc.id_condutor = '".$idCondutor."'";

    $respostaAux2 = oci_parse($conexao, $sqlAux2);

    if(!oci_execute($respostaAux2)){
        echo ' Em manutenção... Por favor, tente novamente mais tarde.';
        #echo $sqlAux2;
        exit();
    }else{
        $dados2 = oci_fetch_assoc($respostaAux2);
    }

    //Cabecalho de informacoes
    $html.= '
        <font size="7">
            <table width="100%" cellpadding="5" cellspacing="2">
                <tr bgcolor="#E1EEF4">
                      <td><font style="font-weight: bold;">Condutor:&nbsp;</font>'.$nomeCondutor.'</td>
                      <td><font style="font-weight: bold;">Horas previstas totais: &nbsp;</font>'.$minTotalBC.'</td>
                </tr>
                <tr bgcolor="#E1EEF4">
                    <td><font style="font-weight: bold;">Horas previstas em dias uteis:&nbsp;</font>'.$minSemBH.'</td>
                    <td><font style="font-weight: bold;">Horas previstas no sabado:&nbsp;</font>'.$minSabBH.'</td>
                </tr>
                <tr bgcolor="#E1EEF4">
                      <td><font style="font-weight: bold;">Perido:&nbsp;</font>'.$descPeriodoBH.'</td>
                      <td><font style="font-weight: bold;">Inicio e fim:&nbsp;</font>'.$dataIni.' ate '.$dataFim.'</td>
                </tr>
            </table>
        </font>';

    //Corpo
    if(!empty($_REQUEST['pontoContent'])){
        $html.= '
            <br><br>
            <font size="7">
                <table width="100%" cellpadding="5" cellspacing="2" style="text-align:left;">
                    <thead>
                        <tr bgcolor="#006699" style="color: rgb(255, 255, 255); text-align: center; font-weight: bold;">
                            <th>Saldo inicial</th>
                            <th>Dias com valor drobrado</th>
                            <th>Total de horas trabalhadas</th>
                            <th>Media de trabalho por dia</th>
                            <th>Media de trabalho por semana</th>
                            <th>Saldo final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr bgcolor="#E1EEF4">
                            <td>'.$saldoIni.'</td>
                            <td>'.$diasValorDobrado.'</td>
                            <td>'.$totalTrabalhadoBF.'</td>
                            <td>'.$mediaTrabDia.'</td>
                            <td>'.$mediaTrabSem.'</td>
                            <td>'.$saldoBF.'</td>
                        </tr>
                    </tbody>
                </table>
                <br><br>
                <table width="100%" cellpadding="5" cellspacing="2" style="text-align:left;">
                    <thead>
                        <tr bgcolor="#006699" style="color: rgb(255, 255, 255); text-align: center; font-weight: bold;">
                            <th>Data</th>
                            <th>Acumulado anterior</th>
                            <th>Horas trabalhadas</th>
                            <th>Acumulado total</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>'.utf8_decode($_REQUEST['pontoContent']).'</tbody>
                </table>
                <br><br>
                <table width="100%" cellpadding="5" cellspacing="2" style="text-align:left;">
                    <thead>   
                        <tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">
                            <th colspan="4" style="text-align: center;">Fechamento</th>
                        </tr> 
                        <br>
                        <tr bgcolor="#006699" style="color: rgb(255, 255, 255); text-align: center; font-weight: bold;">
                            <th>Tipo</th>
                            <th>Data</th>
                            <th>Usuario</th>
                            <th>Observacao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr bgcolor="#E1EEF4">
                            <td>'.$dados['TIPO_FECHAMENTO'].'</td>
                            <td>'.$dados['DATA_FECHAMENTO'].'</td>
                            <td>'.$dados['USUARIO_FECHAMENTO'].'</td>
                            <td>'.$dados['OBS_FECHAMENTO'].'</td>
                        </tr>
                    </tbody>
                </table>
            </font><br>';
    }

    //Gera linha de aceite e assinatura
    $ass = '
        <font size="7">
            <table width="100%" cellpadding="1" cellspacing="1" style="text-align:center;">
            <tr>
                <tr><td></td></tr>
                    <td>Li e concordo com as anotações amostradas no diário de bordo acima</td>
                </tr>
                <tr><td></td></tr>
                <tr>
                    <td>_________________________________________</td>
                </tr>
                <tr>
                    <td>'.$nomeCondutor.'</td>
                </tr>
            </table>
        </font>
    ';

    $html.= utf8_decode($ass);

    //echo $html;exit();

    $nomeRelatorio 	= utf8_decode("Jornada de Trabalho - ".$dados2['NOME_EMPRESA']);
    $nomeArquivo 	= 'banco_horas_'.$nomeCondutor.'_'.$dataIni.'_'.$dataFim;
    $retorno        = "I";
    $orientacao     = 'L';

    $printPdf->setHeader($nomeRelatorio, 'Registro diário de Banco de Horas - '.$nomeCondutor);
    $printPdf->setTitle($nomeRelatorio);
    $printPdf->setDocument();
    $printPdf->setBodyRelatorio($html, $orientacao);

    echo $printPdf->getPdf($nomeArquivo.'.pdf', $retorno);
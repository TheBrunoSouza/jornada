<?
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');
    require_once('../includes/PrintPdf.class.php');

    $OraCielo   = new OracleCielo();
    $conexao    = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $printPdf   = new PrintPdf();
    $html       = '';
    $idEmpresa  = '';
    $profissao  = '';
    $idCondutor = $_REQUEST['formIdCondutor'];

    if($_REQUEST['formIdEmpresa'] == ''){
        #Clientes em geral
        $idEmpresa = $_REQUEST['formIdEmpresaHidden'];
    }else{
        #Quando o usuário for central e não possui empresa vinculada a ele
        $idEmpresa = $_REQUEST['formIdEmpresa'];
    }

    $sqlAuxiliarDeDados = "
            SELECT  mc.nome as nome_condutor, me.id_empresa, me.nome as nome_empresa, mc.profissao
            FROM    monitoramento.empresa me, monitoramento.condutor mc 
            WHERE   me.id_empresa = mc.empresa
                    AND me.id_empresa = ".$idEmpresa."
                    AND mc.id_condutor = ".$idCondutor;

    $respostaAux = oci_parse($conexao, $sqlAuxiliarDeDados);

    if(!oci_execute($respostaAux)){
        echo ' Em manutenção... Por favor, tente novamente mais tarde.';
        echo $sqlAuxiliarDeDados;
        exit();
    }else{
        $dados = oci_fetch_assoc($respostaAux);
    }

    oci_free_statement($respostaAux);
    oci_close($conexao);

    $html.= '
        <font size="8">
            <table width="100%" cellspacing="2" cellpadding="5">
                <tr>
                    <td><font style="font-weight: bold;">Periodo do Relat&oacute;rio:&nbsp;</font>'.date('d/m/Y', strtotime($_REQUEST['formDtIni'])).' &agrave; '.date('d/m/Y', strtotime($_REQUEST['formDtFim'])).'</td>
                </tr>
            </table>
        </font>';

    $html.= utf8_decode($_REQUEST['pontoContent']);

    $html.= utf8_decode($_REQUEST['justificativaContent']);

    $html.= utf8_decode($_REQUEST['tableTotais']);


    //Gera linha de aceite e assinatura
    $ass = '<font size="8">
        <table cellpadding="1" cellspacing="1" style="text-align:center;">
        <tr>
            <tr><td></td></tr>
                <td>Li e concordo com as anotações amostradas no diário de bordo acima</td>
            </tr>
            <tr><td></td></tr>
            <tr>
                <td>_________________________________________</td>
            </tr>
            <tr>
                <td>'.$_REQUEST['formNmCondutor'].'</td>
            </tr>
        </table></font>
    ';

    $html.= utf8_decode($ass);

    //echo $html;exit();

    if($dados['PROFISSAO'] != '' or $dados['PROFISSAO'] != null){
        $profissao = " (".$dados['PROFISSAO'].")";
    }

    $nomeRelatorio 	= utf8_decode("Jornada de Trabalho - ".$dados['NOME_EMPRESA']);
    $nomeArquivo 	= 'cartao_ponto_'.$_REQUEST['formNmCondutor'].'_'.$_REQUEST['formDtIni'].'_'.$_REQUEST['formDtIni'];
    $retorno        = "I";
    $orientacao     = 'L';

    $printPdf->setHeader($nomeRelatorio, 'Cartão Ponto - '.utf8_decode($_REQUEST['formNmCondutor']).$profissao);
    $printPdf->setTitle($nomeRelatorio);
    $printPdf->setDocument();
    $printPdf->setBodyRelatorio($html, $orientacao);

    echo $printPdf->getPdf($nomeArquivo.'.pdf', $retorno);
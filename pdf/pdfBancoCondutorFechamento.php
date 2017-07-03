<?
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');
    require_once('../includes/PrintPdf.class.php');

    $OraCielo   = new OracleCielo();
    $conexao    = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $printPdf   = new PrintPdf();
    $html       = '';
    $idEmpresa  = $_REQUEST['idEmpresa'];
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);

    if($idEmpresa == ''){
        $idEmpresa = $empresaUsu;
    }

    $sqlNomeEmpresa = "  
        SELECT nome as nome_empresa
        FROM monitoramento.empresa
        WHERE id_empresa = '".$idEmpresa."'";

    $respostaNomeEmpresa = oci_parse($conexao, $sqlNomeEmpresa);

    if(!oci_execute($respostaNomeEmpresa)){
        echo ' Erro ao buscar nome da empresa.';
        echo $sqlNomeEmpresa;
        exit();
    }else{
        $nomeEmpresa = oci_fetch_assoc($respostaNomeEmpresa);
    }

    $html.=utf8_decode($_REQUEST['pontoContent']);
    $html.= utf8_decode($ass);

    //echo $html;exit();

    $nomeRelatorio 	= utf8_decode("Jornada de Trabalho - ".$nomeEmpresa['NOME_EMPRESA']);
    $nomeArquivo 	= 'banco_horas_fechamento_'.$nomeEmpresa['NOME_EMPRESA'];
    $retorno        = "I";
    $orientacao     = 'L';

    $printPdf->setHeader($nomeRelatorio, 'Banco de Horas - Fechamento');
    $printPdf->setTitle($nomeRelatorio);
    $printPdf->setDocument();
    $printPdf->setBodyRelatorio($html, $orientacao);

    echo $printPdf->getPdf($nomeArquivo.'.pdf', $retorno);
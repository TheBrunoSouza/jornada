<?php
    header('Content-Type: text/html; charset=utf-8');

    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');
    require_once('../includes/PrintPdf.class.php');

    $OraCielo = new OracleCielo();
    $conexao = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $printPdf = new PrintPdf();
    $html = '';

    $sqlNomeEmpresa = "SELECT id_empresa, nome
                         FROM monitoramento.empresa
                        WHERE id_empresa = ".$_REQUEST['formIdEmpresa'];

    $respostaEmp = oci_parse($conexao, $sqlNomeEmpresa);

    if(!oci_execute($respostaEmp)) {
        echo ' Erro ao buscar nome da empresa.';
        echo $sqlNomeEmpresa;
        exit();
    } else {
        $dadosEmpresa = oci_fetch_assoc($respostaEmp);
    }

    oci_free_statement($respostaEmp);
    oci_close($conexao);

    $html.= '
        <font size="9">
            <table width="50%" cellspacing="2" cellpadding="2">
                <tr bgcolor="#E1EEF4">
                      <td><font style="font-weight: bold;">Empresa:&nbsp;</font>'.$dadosEmpresa['NOME'].'</td>
                </tr>
                <tr bgcolor="#E1EEF4">
                      <td><font style="font-weight: bold;">Condutor:&nbsp;</font>'.utf8_decode($_REQUEST['formNmCondutor']).'</td>
                </tr>
                <tr bgcolor="#E1EEF4">
                    <td><font style="font-weight: bold;">Periodo do Relat&oacute;rio:&nbsp;</font>'.date('d/m/Y', strtotime($_REQUEST['formDtIni'])).' &agrave; '.date('d/m/Y', strtotime($_REQUEST['formDtFim'])).'</td>
                </tr>
            </table>
        </font>';

    /*if(!empty($_REQUEST['pontoContent'])) {
	$empExtra100 = $CtrlAcesso->getExtra100();
	$th100 = '';
        
	if($empExtra100 == 'true')
            $th100 = "<th>Extra 100%</th>";
	
	$html.= '
            <br>
            <font size="9">
                <table cellpadding="1" cellspacing="1" style="text-align:left;">
                    <thead>
                        <tr bgcolor="#006699" style="color: rgb(255, 255, 255); text-align: center; font-weight: bold;">
                            <th>Data</th>
                            <th>Jornada</th>
                            <th>Hora Extra</th>
                            '.$th100.'
                            <th>Espera</th>
                            <th>Refei&ccedil;&atilde;o</th>
                            <th>Descanso</th>
                            <th>Repouso</th>
                        </tr>
                    </thead>
                    <tbody>'.utf8_decode($_REQUEST['pontoContent']).'</tbody>
                </table>
            </font>';
    }*/

    if(!empty($_REQUEST['justificativaContent'])){
        $html.= '<br>
                <font size="9">
                    <table cellpadding="1" cellspacing="1" style="text-align:left;">
                        <tbody>'.utf8_decode($_REQUEST['justificativaContent']).'</tbody>
                    </table>
                </font>';
    }

    //Gera linha de aceite e assinatura
    $ass = '
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
        </table>
    ';

    $html.= utf8_decode($ass);

    //echo $html;exit();

    $nomeRelatorio = utf8_decode("Jornada de Trabalho");
    $nomeArquivo = 'Cartao_Ponto - Justificativas';
    $retorno = "I";
    $orientacao = 'L';

    $printPdf->setHeader($nomeRelatorio, 'Cartao Ponto - Justificativas');
    $printPdf->setTitle($nomeRelatorio);
    $printPdf->setDocument();
    $printPdf->setBodyRelatorio($html, $orientacao);

    echo $printPdf->getPdf($nomeArquivo.'.pdf', $retorno);
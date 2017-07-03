<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');
    require_once('../includes/PrintPdf.class.php');
    
    $OraCielo = new OracleCielo();
    $conexao = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $printPdf = new PrintPdf();
    $html = '';

    $sqlNomeEmpresa = "
        SELECT id_empresa, nome
          FROM monitoramento.empresa
         WHERE id_empresa = ".$_REQUEST['formIdEmpresa'];

    $respostaEmp = oci_parse($conexao, $sqlNomeEmpresa);

    if(!oci_execute($respostaEmp)) {
        echo ' Erro ao buscar nome da empresa';
        echo $sqlNomeEmpresa;
        exit();
    } else {
        $dadosEmpresa = oci_fetch_assoc($respostaEmp);
    }

    oci_free_statement($respostaEmp);
    oci_close($conexao);

    $html .= '<font size="9">
                <table width="50%" cellspacing="2" cellpadding="2">
                    <tr bgcolor="#E1EEF4">
                        <td><font style="font-weight: bold;">Empresa:&nbsp;</font>'.$dadosEmpresa['NOME'].'</td>
                    </tr>
                    <tr bgcolor="#E1EEF4">
                        <td><font style="font-weight: bold;">Condutor:&nbsp;</font>'.utf8_decode($_REQUEST['formNmCondutor']).'</td>
                    </tr>
                    <tr bgcolor="#E1EEF4">
                        <td><font style="font-weight: bold;">Data do relat&oacute;rio:&nbsp;</font>'.$_REQUEST['formDtIni'].'</td>
                    </tr>
                </table>
            </font>';

    $html .= '<br>
                <font size="9">
                    <table cellpadding="1" cellspacing="1" style="text-align:center;">
                        <thead>
                            <tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">
                                <th>Situa&ccedil;&atilde;o</th>
                                <th>Hora Inicio</th>
                                <th>Hora Fim</th>
                                <th>Tempo</th>
                            </tr>
                        </thead>
                        <tbody>'.utf8_decode($_REQUEST['pontoContent']).'</tbody>
                    </table>
                </font>';
    //}
    //echo $html;

    //gera linha de aceite e assinatura\\
    $ass = '<table cellpadding="1" cellspacing="1" style="text-align:center;">
                <tr>
                    <tr><td></td></tr>
                        <td>Li e concordo com as anotações amostradas no diário de bordo acima</td>
                    </tr>
                    <tr><td></td></tr>
                    <tr>
                        <td>_________________________________________</td>
                    </tr>
                    <tr>
                        <td>'.utf8_decode($_REQUEST['formNmCondutor']).'</td>
                    </tr>
            </table>';
    
    //$ass='';
    
    $html .= utf8_decode($ass);

    //header('Locatio:pdfCartaoPontoPrint.php?htmlString='.$htmlString);
    //$nomeRelatorio 	= utf8_decode("Relatorio de Ponto Jornada");
    $nomeRelatorio = utf8_decode("Jornada de Trabalho");
    $nomeArquivo = 'Movimentacoes';
    $retorno = "I";
    $orientacao = 'L';
    $printPdf->setHeader($nomeRelatorio, 'Movimentações');
    $printPdf->setTitle($nomeRelatorio);
    $printPdf->setDocument();
    $printPdf->setBodyRelatorio($html,$orientacao);
    echo $printPdf->getPdf($nomeArquivo.'.pdf', $retorno);
?>
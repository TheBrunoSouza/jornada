<?php
require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');

/* Versão 0.000002 (Francis) */
class PrintPdf{
	private $con = null;
	private $pdf = null;
	
	function __construct($conexao=null){
		if($conexao!=null){
			$this->con = $conexao;
		}
		$this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	}
	
	function __destruct(){
		
	}
	
	public function setTitle($titulo){
		
		$this->pdf->SetTitle($titulo);
	}
	
	public function setHeader($dataTitulo,$nomeEmpresa=''){
		$this->pdf->setHeaderData('','',utf8_encode($dataTitulo),$nomeEmpresa);
		
	}
	
	public function setDocument(){
		$this->pdf->SetCreator('Jornada de Trabalho');
		
		// seta as fontes usadas no Header e no Rodapé
		$this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
		// seta as fontes que entraram como monospace
		$this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		
		//seta as margens
		$this->pdf->SetMargins(PDF_MARGIN_LEFT, 20, PDF_MARGIN_RIGHT);
		$this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
		//seta o break automático feito na página
		$this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		
		//set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
		//set some language-dependent strings
		$this->pdf->setLanguageArray($l);
		
		// seta a fonte usada no documento
		$this->pdf->SetFont('helvetica', '', 10);
	}
	
	//monta o pdf dos relatorios
	function setBodyRelatorio($html,$orientation){
		
		//adiciona pagina
		$this->pdf->AddPage($orientation);
		//echo "até aqui...";
	
//		$style5 = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 64, 128));
//		$this->pdf->SetLineStyle($style5);
		
		$html2 = utf8_encode($html);
		
		// Imprime o conteúdo do documeto
		$this->pdf->writeHTML($html2, true, false, true, true, '');
		
		// Reseta a partir deste ponto, para partir para a outra página
		$this->pdf->lastPage();
	}
	
		
	//@param nome (string) Defino o nome do arquivo. Ex. arquivo.pdf (o nome deve conter a extensao).
	//@param retorno (string) Define o tipo de retorno. 'I' Mostrar o PDF no Navegador. 'D' Força o Download. default 'I')
	function getPdf($nome='Documento.pdf', $retorno='I'){
		// Fecha o documento e imprime o PDF
		echo "PASSOU AQUI...";
		ob_end_clean();
		return $this->pdf->Output($nome, $retorno);	
	}	
}

?>
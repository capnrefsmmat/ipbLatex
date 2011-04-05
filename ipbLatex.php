<?php
/**
 * IPB LaTeX plugin. Requires writable directories in which to write its images.
 * Author: Alex Reinhart
 */
require_once 'class.latex-vb.php';

class bbcode_latex extends bbcode_parent_class implements bbcodePlugin
{
	public function __construct(ipsRegistry $registry)
	{
		$this->currentBbcode = 'latex';
		parent::__construct($registry);
	}

	protected function _replaceText($txt)
	{
		$_tags = $this->_retrieveTags();
		
		foreach($_tags as $_tag)
		{
			$_tag = strtolower($_tag);
			switch ($_tag)
			{
				case "ce": $this->_latexMode = LATEX_INPUT_CHEM; break;
				case "imath": $this->_latexMode = LATEX_INPUT_INLINE; break;
				case "pgf": $this->_latexMode = LATEX_INPUT_PGF; break;
				default: $this->_latexMode = LATEX_INPUT_NORM;
			}
			$this->curTagType = $_tag;
			$txt = preg_replace_callback("/\[{$_tag}\](.*)\[\/{$_tag}\]/is", array(&$this, '_createImg'), $txt);
		}
		return $txt;
	}

	protected function _createImg($toTex)
	{
		$find = array("&#092;", "&amp;", "<br />", "&lt;", "&gt;", "&quot;", "&#39;");
		$replace = array("\\", "&", "", "<", ">", '"', "'");
		$formula_text = str_replace($find, $replace, $toTex[1]);
                
		$latex = new Latex($this->_latexMode);
		$img = $latex->renderLatex($formula_text);
		
		if (substr($img, 0, 7) == 'Error: ')
			return '[<strong>LaTeX Error:</strong> '.substr($img, 7).']';
                
		return "<img src='$img' class=\"tex tex_" . $this->curTagType . "\" alt=\"" . addslashes(htmlentities($formula_text)) . "\" />";
	}
}
?>

<?php
/**
 * IPB LaTeX plugin. Requires writable directories in which to write its images.
 * Author: Alex Reinhart
 */
require_once 'class.latex-vb.php';

if( !class_exists('bbcode_parent_main_class') )
{
    require_once( IPS_ROOT_PATH . 'sources/classes/text/parser/bbcode/defaults.php' );
}

class bbcode_plugin_latex extends bbcode_parent_main_class
{
    public function __construct(ipsRegistry $registry, $_parent = null)
    {
        $this->currentBbcode = 'latex';
        parent::__construct($registry, $_parent);
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
            $txt = preg_replace_callback("/\[{$_tag}\](.*)\[\/{$_tag}\]/isU", 
                                         array(&$this, '_createImg'), $txt);
        }
        return $txt;
    }

    protected function _createImg($toTex)
    {
        $find = array("&#092;", "&amp;", "<br />", "&lt;", "&gt;", 
                      "&quot;", "&#39;", "&#33;", '<p>', '</p>');
        $replace = array("\\", "&", "", "<", ">", '"', "'", "!", "", "");
        $formula_text = str_replace($find, $replace, $toTex[1]);
                
        $latex = new Latex($this->_latexMode);
        $img = $latex->renderLatex($formula_text);
        
        if (substr($img, 0, 7) == 'Error: ')
            return '[<strong>LaTeX Error:</strong> '.substr($img, 7).']';

        return "<img src='$img' class=\"tex tex_" . $this->curTagType . 
          "\" alt=\"" . htmlentities($formula_text) . "\" />";
    }
}
?>

<?php

/*
 * class.latex.php: Small class for rendering LaTeX formulae
 * Author: David Moxey
 */

// Input environments:
define('LATEX_INPUT_NORM',   1);        // Normal 'displaymath' environment
define('LATEX_INPUT_INLINE', 2);        // For inline text
define('LATEX_INPUT_CHEM',   3);        // Chemical equations (requires mhchem)
define('LATEX_INPUT_PGF',    4);        // PGF pictures.

// Output environments:
define('LATEX_OUTPUT_PNG', 'png');
define('LATEX_OUTPUT_GIF', 'gif');

// Errors:
define('LATEX_ERROR_TOOLONG', -1);      // Message too long
define('LATEX_ERROR_BADWORD', -2);      // String included a bad word
define('LATEX_ERROR_NODIR',   -3);      // Directory(ies) don't exist
define('LATEX_ERROR_NOPERM',  -4);      // Directory(ies) aren't writeable
define('LATEX_ERROR_TOOBIG',  -5);      // Dimensions of resultant image are too big
define('LATEX_ERROR_SYNTAX',  -6);      // Syntax error
define('LATEX_ERROR_COPY',    -7);      // Copy error

class Latex {
  /**************** BEGIN CONFIGURATION *****************/

  /*
   * Path information: this is important!
   * Alter the information below according to your system.
   *     'latex': path to LaTeX application
   *     'dvipng': path to dvipng application
   *
   *     'base': base directory of your latex/ directory, WITHOUT trailing slash
   *     'baseurl': the URL to your latex directory, WITHOUT trailing slash
   *
   * For example, if my LaTeX directory was /home/dave/public_html/latex then:
   *     'base' => '/home/dave/public_html/latex'
   *     'baseurl' => 'http://my.domain.name/~dave/latex'
   */
  var $path = array(
    // Applications
    'latex'   => '/opt/local/bin/latex',
    'dvipng'  => '/opt/local/bin/dvipng',
    'mogrify' => '/opt/local/bin/mogrify',
    'convert' => '/opt/local/bin/convert',
    // Base directory
    'base'    => '/Users/dave/work/tmp/vblatex',
    'baseurl' => '/latex'
  );

  /*
   * Presentation information:
   *    'class': LaTeX class to use in \documentclass call
   *    'fontsize': Font size to use in \documentclass call
   *    'extrapackages': An array of extra packages you might want to use
   *    'format': Default output format. LATEX_OUTPUT_PNG or LATEX_OUTPUT_GIF
   *              for GIF and PNG respectively.
   *    'retina': Create retina images.
   */
  var $display = array(
    'class'         => 'article',
    'fontsize'      => '18pt',
    'extrapackages' => array('amsmath', 'amsfonts', 'amssymb', 'color', 'slashed'),
    'format'        => LATEX_OUTPUT_PNG,
    'retina'        => true
  );

  /*
   * dvipng options:
   *    'background': You can either choose transparent for a transparent
   *                  background, or a colour. The format of the colour must
   *                  be 'rgb 1.0 0.0 0.0'; for example, this defines red. See
   *                  the example below.
   *    'foreground': Not implemented yet.
   *    'density': Density to render image at. 100 is about right for most
   *               applications.
   *    'gamma': Apply gamma to image - 1.0 to leave untouched.
   *    'resample': Use mogrify to resample this image. This ends up improving
   *                image quality by better anti-aliasing lines.
   */
  var $dvipng = array(
    'background' => 'Transparent',
  //'background' => "'rgb 1.0 0.0 0.0'",
    'foreground' => false,
    'density'    => '300',
    'gamma'      => '2.0',
    'resample'   => '130'
  );

  /********* Security Options **********/

  // Maximum lengths of input string for each type
  var $str_length = array(
    LATEX_INPUT_NORM   => 1000,
    LATEX_INPUT_INLINE => 200,
    LATEX_INPUT_CHEM   => 200,
    LATEX_INPUT_PGF    => 8000
  );

  // Maximum image dimensions
  var $image_dim = array(
    LATEX_INPUT_NORM   => array(1000, 800),
    LATEX_INPUT_INLINE => array(500,  200),
    LATEX_INPUT_CHEM   => array(600,  200),
    LATEX_INPUT_PGF    => array(1500, 1500)
  );

  // Barred commands: taken from LatexRender - best not to alter
  var $barred_commands = array(
    'include', 'def', 'command', 'loop', 'repeat', 'open', 'toks', 'output', 'input',
    'catcode', 'name', '^^',
    '\\every', '\\errhelp', '\\errorstopmode', '\\scrollmode', '\\nonstopmode', '\\batchmode',
    '\\read', '\\write', 'csname', '\\newhelp', '\\uppercase', '\\lowercase', '\\relax', '\\aftergroup',
    '\\afterassignment', '\\expandafter', '\\noexpand', '\\special'
  );

  /*********** END CONFIGURATION **********/

  var $tmp_filename;
  var $md5hash;

  var $method = LATEX_INPUT_NORM;
  var $currentdir = '';

  function Latex($inputmethod, $outputmethod=FALSE)
  {
    $this->method = $inputmethod;

    if ($outputmethod)
      $this->display['format'] = $outputmethod;

    if ($this->display['format'] == LATEX_OUTPUT_GIF)
      $this->path['dvipng'] .= " --gif";

    $this->path['tmp'] = $this->path['base'].'/tmp';
    $this->path['img'] = $this->path['base'].'/img';
  }

  function renderLatex($latexstring)
  {
    $this->tmp_filename = md5(rand());
    $this->currentdir = getcwd();

    $path = $this->path;
    $latexstring = trim($latexstring);
    $this->md5hash = md5($latexstring);

    $filename        = $this->md5hash.'-'.$this->method.'.'   .$this->display['format'];
    $filename_retina = $this->md5hash.'-'.$this->method.'@2x.'.$this->display['format'];

    // Check existance of correct folders/permissions
    if (!is_dir($path['base']) || !is_dir($path['tmp']) || !is_dir($path['img']))
      return $this->error(LATEX_ERROR_NODIR, '');

    if (!is_writeable($path['tmp']) || !is_writeable($path['img']))
      return $this->error(LATEX_ERROR_NOPERM, '');

    // Check whether this thing already exists
    if (is_file($path['img'].'/'.$filename)) {
      //@touch($path['img'].'/'.$filename);
      return $this->path['baseurl']."/img/".$filename;
    }

    // Do security checks
    if ($this->str_length[$this->method] != 0 && strlen($latexstring) > $this->str_length[$this->method])
      return $this->error(LATEX_ERROR_TOOLONG, strlen($latexstring));

    foreach ($this->barred_commands as $command) {
      if (stristr($latexstring, $command))
        return $this->error(LATEX_ERROR_BADWORD, $command);
    }

    // Store current directory and
    chdir($path['tmp']);

    // Wrap out string as snug as a bug in a rug and output it to something
    $tmp_file = fopen($path['tmp'].'/'.$this->tmp_filename.'.tex', 'w');
    fwrite($tmp_file, $this->wrapFormula($latexstring));
    fclose($tmp_file);

    // Compile command
    $command = $path['latex']." --interaction=nonstopmode ".$this->tmp_filename.".tex && ".
               $path['dvipng']." -q -D ".$this->dvipng['density']." -T tight -gamma ".$this->dvipng['gamma']." -bg ".$this->dvipng['background']." -o $filename ".$this->tmp_filename.".dvi";
    exec($command);

    if (!is_readable($filename))
      return $this->error(LATEX_ERROR_SYNTAX, '', true);

    // Run through convert utility if required.
    if (isset($this->dvipng['resample'])) {
      if (!$this->display['retina']) {
        $command = $path['mogrify']." -density ".$this->dvipng['density']." -filter Lanczos -resample ".$this->dvipng['resample']." -sharpen 2 $filename";
        exec($command);
      } else {
        $newsample = intval($this->dvipng['resample'])*2;
        $command = $path['convert']." $filename -density ".$this->dvipng['density']." -filter Lanczos -resample $newsample -sharpen 2 $filename_retina";
        exec($command);
        $command = $path['mogrify']." -density ".$this->dvipng['density']." -filter Lanczos -resample ".$this->dvipng['resample']." -sharpen 2 $filename";
        exec($command);
      }
    }

    // Security check: image height/width
    $imageinfo = @getimagesize($filename);

    if ($this->image_dim[$this->method] != NULL && ($imageinfo[0] > $this->image_dim[$this->method][0] || $imageinfo[1] > $this->image_dim[$this->method][1]))
      return $this->error(LATEX_ERROR_TOOBIG, $imageinfo[0].'x'.$imageinfo[1], true);

    $copy = @copy($filename, $path['img'].'/'.$filename);

    if (!$copy)
      return $this->error(LATEX_ERROR_COPY, '', true);

    if ($this->display['retina']) {
      $copy = @copy($filename_retina, $path['img'].'/'.$filename_retina);

      if (!$copy)
        return $this->error(LATEX_ERROR_COPY, '', true);
    }

    $this->cleanTmpDir();

    chdir($this->currentdir);

    return $this->path['baseurl']."/img/".$filename;
  }

  function wrapFormula($latexstring)
  {
    $wrap =  "\documentclass[".$this->display['fontsize']."]{".$this->display['class']."}\n";
    $wrap .= "\pagestyle{empty}\n";

    foreach ($this->display['extrapackages'] as $extra)
      $wrap .= "\usepackage{".$extra."}\n";

    switch ($this->method) {
      case LATEX_INPUT_CHEM:
        $wrap .= "\usepackage[version=3]{mhchem}\n";
        break;
      case LATEX_INPUT_PGF:
        $wrap .= "\usepackage{tikz}\n";
        break;
    }

    $wrap .= "\begin{document}\n";

    switch ($this->method) {
      case LATEX_INPUT_NORM:
        $wrap .= "\\[ $latexstring \\]\n";
        break;
      case LATEX_INPUT_INLINE:
        $wrap .= "$ $latexstring $\n";
        break;
      case LATEX_INPUT_CHEM:
        $wrap .= "\\ce{".$latexstring."}\n";
        break;
      case LATEX_INPUT_PGF:
        $wrap .= "\\begin{tikzpicture}\n";
        $wrap .= $latexstring;
        $wrap .= "\\end{tikzpicture}\n";
        break;
    }

    $wrap .= "\\end{document}\n";

    return $wrap;
  }

  function error($errorcode, $detail, $chdir=false)
  {
    $err = 'Error: ';

    switch ($errorcode) {
      case LATEX_ERROR_TOOLONG:
        $err .= "String is too long (".$detail.", limit ".$this->str_length[$this->method].")";
        break;

      case LATEX_ERROR_BADWORD:
        $err .= 'Restricted command found ('.$detail.')';
        break;

      case LATEX_ERROR_NODIR:
        $err .= 'One or more directories do not exist';
        break;

      case LATEX_ERROR_NOPERM:
        $err .= "Can't write to directory";
        break;

      case LATEX_ERROR_TOOBIG:
        $err .= 'Image is too big ('.$detail.', limit '.implode('x', $this->image_dim[$this->method]).')';
        break;

      case LATEX_ERROR_SYNTAX:
        $err .= 'Syntax error';
        break;

      case LATEX_ERROR_COPY:
        $err .= "Couldn't copy temporary file";
        break;
    }

    if ($chdir) {
      chdir($this->currentdir);
      $this->cleanTmpDir();
    }

    return $err;
  }

  function cleanTmpDir()
  {
    /*
    @unlink($this->path['tmp'].'/'.$this->tmp_filename.'.tex');
    @unlink($this->path['tmp'].'/'.$this->tmp_filename.'.aux');
    @unlink($this->path['tmp'].'/'.$this->tmp_filename.'.log');
    @unlink($this->path['tmp'].'/'.$this->tmp_filename.'.dvi');
    @unlink($this->path['tmp'].'/'.$this->tmp_filename.'.ps');
    @unlink($this->path['tmp'].'/'.$this->tmp_filename.'.'.$this->display['format']);
    @unlink($this->path['tmp'].'/'.$this->md5hash.'-'.$this->method.'.'.$this->display['format']);
    @unlink($this->path['tmp'].'/'.$this->md5hash.'-'.$this->method.'@2x.'.$this->display['format']);
    */
  }
}

?>

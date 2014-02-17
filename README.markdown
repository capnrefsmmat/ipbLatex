ipbLatex
========

A LaTeX display plugin for IPB supporting PNG math rendering and popup code 
display.

Disclaimer
----------

The only testing this has received has been on 
[SFN](http://www.scienceforums.net), and so I cannot guarantee it will work 
everywhere. I have only tested it on IPB 3.1+. This may break your board or 
cause other weird problems, including possibly data loss. You've been warned.

Features
--------

Lots! ipbLatex supports:

- Normal display math -- the $$ math $$ environment in LaTeX.
- Inline math, as in the $math$ environment.
- Chemistry, using mhchem.
- PNG and GIF output.
- A popup showing the raw LaTeX code upon clicking on an image, allowing users
  to find out how a LaTeX equation was made so they can type their own
- Caching of every equation
- As many LaTeX packages as you want to use
- Generation of retina images for HiDPI displays.

There is experimental support for PGF, but it is not enabled and incomplete, so 
you'd have to finish it yourself. (Patches accepted!)

Requirements
------------

Stolen from Dave's vbLatex documentation:

- A working distribution of LaTeX. This kind of goes without saying; if you're 
  using Linux, look into the TeXlive distribution.
- You absolutely must have a copy of dvipng installed. This plugin will not 
  work without it.
- If you want GIF output, then dvipng must have GIF support compiled into it. 
  Things may otherwise break. Versions supplied as a part of many distributions 
  will have this support.
- The mhchem package needs to be accessible by LaTeX; either put the 
  `mhchem.sty` file in the tmp directory (see below) or install the package 
  properly. It is not bundled with ipbLatex by default (although this may 
  change in the future).
- mogrify is recommended for prettier images. If you do not have mogrify, be 
  sure to set `resample` to FALSE as instructed in the configuration. You'll 
  want to change `density` and `gamma` to the recommended values in the comment 
  above if you cannot use mogrify.
- Retina image support requires both mogrify and convert commands.

Download It
-----------

The package is available under Downloads on GitHub, under the LGPLv2. 
License terms are included in the download.

Installation
------------

(Again, partially stolen from Dave’s documentation.)

- Create a folder somewhere web-accessible to hold all of your generated 
  images. You need to create two sub-directories called `tmp` and `img` inside
  this folder, and make them both world-writable.
- Extract the archived ZIP file somewhere. Carefully read through the 
  configuration in `class.latex-vb.php` (at the beginning of the file) and fill 
  in all required sections. Make sure your paths are correct and agree with 
  step 1.
- Upload the `class.latex.php` and `ipbLatex.php` files to the 
  `/admin/sources/classes/bbcode/custom` (IPB 3.0-3.3) or
  `/admin/sources/classes/text/parser/bbcode` (IPB 3.4) directory of your IP.Board 
  installation.
- Upload `img.srcset.min.js` to a web-accessible location.
- Open `templates.txt`. To enable the LaTeX popup feature, which allows your 
  users to see the LaTeX code that generated a formula by clicking it, you need 
  to edit your default templates. You'll have to do this for every style you 
  have installed. Simply follow the directions in `templates.txt`.
- Head to your administration panel, to Look & Feel -> BBCode Management. 
  Scroll to the bottom, at Import New BBCodes. Browse and select the 
  `bbcode.xml` file that came with the plugin. Install it.
- That's it!

By default, the plugin uses the [latex] and [math] tags as synonyms, with 
[imath] and [ce] representing inline math and chemical equations, respectively.
You can add additional synonyms as aliases in IPB's BBCode editor, but note 
that [imath] and [ce] are hardcoded, and they won't work if you change the 
aliases for those.

With ipbLatex installed, you should be able to use LaTeX anywhere that BBCode 
can be used -- personal messages, in the forums, and wherever. All images are 
stored in the `img` directory you create for all time, so you may wish to 
periodically  cull that directory. (If a user previews their post, then changes
an equation before posting it, the old equation will remain for all time.) At 
SFN we commonly see an `img` directory of over 20,000 images, so be careful 
opening it in your FTP client or you may cause it to hang. If images are 
deleted, never fear, ipbLatex will merely recreate once the posts are viewed.

If you need extra LaTeX packages for additional features, you can always add 
more to the `extrapackages` list in the configuration. By default, the AMS math 
packages are loaded, as well as color and slashed.

Support?
--------

I'm a student, so I don't have the time to officially support this. Post in the
[official support thread on IPB forums](http://community.invisionpower.com/topic/322598-download-ipblatex/)
or email me at alex at scienceforums dot net, and I'll try to get back to you.
No guarantees -- I have a finite amount of spare time.

Remember: The img and tmp directories you create in step 1 must be writable by 
your webserver. You must have dvipng.

Finally, thanks to Dave for the excellent software that this has been based on, 
and the support and bugfixes as I have developed ipbLatex. And thanks to 
everyone at SFN for tolerating me as I break things, fix them, and break them 
again.

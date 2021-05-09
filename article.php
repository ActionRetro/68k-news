<?php
require_once('vendor/autoload.php');

$article_url = "";
$article_html = "";
$error_text = "";
$loc = "US";

if( isset( $_GET['loc'] ) ) {
    $loc = urlencode(strtoupper($_GET["loc"]));
}

if( isset( $_GET['a'] ) ) {
    $article_url = $_GET["a"];
} else {
    echo "What do you think you're doing... >:(";
    exit();
}

if (substr( $article_url, 0, 23 ) != "https://news.google.com") {
    echo("That's not news :(");
    die();
}

use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;

$configuration = new Configuration();
$configuration
    ->setArticleByLine(false);

$readability = new Readability($configuration);

if(!$article_html = file_get_contents($article_url)) {
    $error_text .=  "Failed to get the article :( <br>";
}

try {
    $readability->parse($article_html);
    $readable_article = strip_tags($readability->getContent(), '<ol><ul><li><br><p><small><font><b><strong><i><em><blockquote><h1><h2><h3><h4><h5><h6>');
    $readable_article = str_replace( 'strong>', 'b>', $readable_article ); //change <strong> to <b>
    $readable_article = str_replace( 'em>', 'i>', $readable_article ); //change <em> to <i>
    
    $readable_article = clean_str($readable_article);
    
} catch (ParseException $e) {
    $error_text .= 'Sorry! ' . $e->getMessage() . '<br>';
}

//replace chars that old machines probably can't handle
function clean_str($str) {
    $str = str_replace( "‘", "'", $str );    
    $str = str_replace( "’", "'", $str );  
    $str = str_replace( "“", '"', $str ); 
    $str = str_replace( "”", '"', $str );
    $str = str_replace( "–", '-', $str );

    return $str;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 2.0//EN">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 
 <html>
 <head>
     <title><?php echo $readability->getTitle();?></title>
 </head>
 <body>
    <small><a href="/index.php?loc=<?php echo $loc ?>">< Back to <font color="#9400d3">68k.news</font> <?php echo $loc ?> front page</a></small>
    <h1><?php echo clean_str($readability->getTitle());?></h1>
    <p><small><a href="<?php echo urlencode($article_url) ?>" target="_blank">Original source</a> (on modern site) <?php
        $img_num = 0;
        $imgline_html = "| Article images:";
        foreach ($readability->getImages() as $image_url):
            //we can only do png and jpg
            if (strpos($image_url, ".jpg") || strpos($image_url, ".jpeg") || strpos($image_url, ".png") === true) {
                $img_num++;
                $imgline_html .= " <a href='image.php?loc=" . $loc . "&i=" . $image_url . "'>[$img_num]</a> ";
            }
        endforeach;
        if($img_num>0) {
            echo  $imgline_html ;
        }
    ?></small></p>
    <?php if($error_text) { echo "<p><font color='red'>" . $error_text . "</font></p>"; } ?>
    <p><font size="4"><?php echo $readable_article;?></font></p>
    <small><a href="/index.php?loc=<?php echo $loc ?>">< Back to <font color="#9400d3">68k.news</font> <?php echo $loc ?> front page</a></small>
 </body>
 </html>

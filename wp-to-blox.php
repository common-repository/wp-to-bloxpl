<?php
/*
Plugin Name: WP-To-Blox
Plugin URI: http://polishwords.wikidot.com/
Description: Wtyczka republikuje posty z bloga Wordpress na Blox.pl
Author: Tomasz Smykowski
Version: 1.1
Author URI: http://www.polishwords.com.pl

Copyright 2009-2009 Tomasz Smykowski

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

function tag_yt($content)
{
// Youtube
	$tag = "youtube";
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbedX("http://www.youtube.com/v/".$video."&amp;rel=1&ap=%2526fmt%3D18&amp;hd=1");					
		$content = str_replace($replace, $new, $content);
	}
	return $content;
}

function buildEmbedX($code)
{
	$width = 480;
	$height = 295;
	$object = '<object type="application/x-shockwave-flash" width="'.$width.'" height="'.$height.'" data="'.$code.'">';
	$object .= '<param name="movie" value="'.$code.'" />';
	$object .= '<param name="wmode" value="transparent" />';
	$object .= '<param name="quality" value="high" />';
	$object .= '</object>';
	return $object;
}

function forward_to_blox($id)
{
    if ($_POST["original_post_status"] == "publish")
    { 
        //Je¿eli wpis by³ ju¿ opublikowany to nie republikujemy go (aktualizacja wpisu)
        return;
    }

    $title = $_POST['post_title'];
    $text = $_POST['post_content'];

    if ($text == "")
    {
        //pusty tekst np. shedule a nie publish
        return;
    }

    $text = tag_yt($text);
    
    //TMP
    //ob_start();
    //var_dump($_POST);
    //$a=ob_get_contents();
    //ob_end_clean();
    //$a = $_POST["original_post_status"];
    //$text .= $a;
    $tagi = isset($_POST['post_tag']) ? $_POST['post_tag'] : "brak";
    
    $text .= "<br><br>Tagi: " . $tagi;
    
    $login = "";
    $haslo = "";
    $appKey = "PolishwordsWPToBlox" . $login;
    
    require_once("RPC.php");
    
    $params = array(  new XML_RPC_Value($appKey, 'string'),
                      new XML_RPC_Value($login, 'string'),
                      new XML_RPC_Value($haslo, 'string')
                     );
    $msg = new XML_RPC_Message('blogger.getUsersBlogs', $params);
    //$msg->setSendEncoding('ISO-8859-2'); //fix dla krzaczkow;)
    $msg->setSendEncoding("UTF-8");
    $cli = new XML_RPC_Client('/xmlrpc', 'blox.pl');
    $resp = $cli->send($msg);
    if(!$resp) {
         echo 'communication error <strong>' . $cli->errstr ."</strong>";
         exit;
    }

    if(!$resp->faultCode()) {
         $val = $resp->value();
         $data = XML_RPC_decode($val);
    }else{
         echo 'Fault Code: ' . $resp->faultCode() . "<br/>";
         echo 'Fault Reason: ' . $resp->faultString() . "<br/>";
    }
    
    $adres_mojego_bloga = $data[0]['url'];
    $nazwa_bloga = $data[0]['blogName'];
    $blog_id = $data[0]['blogid'];
    
    //Wysylanie
    $post_payload['title']=new XML_RPC_Value($title, 'string');
    $post_payload['description']=new XML_RPC_Value(stripslashes($text), 'string');
    $params = array( new XML_RPC_Value($blog_id, 'string'),
                     new XML_RPC_Value($login, 'string'),
                     new XML_RPC_Value($haslo, 'string'),
                     new XML_RPC_Value($post_payload, 'struct'),
                     new XML_RPC_Value('1','boolean')
                     );
    $msg = new XML_RPC_Message('metaWeblog.newPost', $params);
    $msg->setSendEncoding("UTF-8");
    $cli = new XML_RPC_Client('/xmlrpc', 'http://blox.pl');
    $resp = $cli->send($msg);
    if(!$resp) {
         echo 'communication error <strong>' . $cli->errstr ."</strong>";
         exit;
    }

    if(!$resp->faultCode()) {
         $val = $resp->value();
         $data = XML_RPC_decode($val);
         
         if($data > 0) {
              //ok
         }else{
              echo 'fatal error;)';
         }
    }else{
         echo 'Fault Code: ' . $resp->faultCode() . "<br/>";
         echo 'Fault Reason: ' . $resp->faultString() . "<br/>";
    }

}

add_filter("publish_post", "forward_to_blox", 1, 1);

?>
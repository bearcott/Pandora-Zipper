<?php
//check if accessed correctly
if (!isset($_POST['token']) && !isset($_POST['name']) && !isset($_POST['what'])) die('FAILED: must access with post tokens, dumb bitch.');

session_start();
//set memory limit to 2GB
ini_set('memory_limit', '2000M');
//import dependencies
require_once 'Pandora.php';
require_once 'Zend/Media/Iso14496.php';
require_once 'Zend/Media/Id3v2.php';
//use pandora class in php_pandora_api namespace

//using class from namespace
use php_pandora_api\Pandora;

function getsongs($username, $password, $stationToken) {

    $p = new Pandora('android', 'json');

    //login
    if (!$p->login($username,$password)) {
        die(sprintf("Error: %s\nReq: %s\n Resp: %s", $p->last_error, $p->last_request_data, $p->last_response_data));
    }
    #get station genre
    if (!$station = $p->makeRequest('station.getStation',array('stationToken'=>$stationToken,'includeExtendedAttributes'=> true))) {
        die(sprintf("Error: %s\nReq: %s\n Resp: %s", $p->last_error, $p->last_request_data, $p->last_response_data));
    }
    ///* pandora request function
    //enumerate song urls
    $_SESSION['songs'] = array();

    $i = 0; // # of song urls accumulated
    $maxnum = 28; // total # of songs desired (doesn't matter because pandora has max limit of 24 songs. 6 skips, 7 x 4 = 28)

    $timeout = 0; // # of times makeRequest call
    $maxrequest = 10; // limit on how many requests to make (prevent accidental infinite loop)

    //fake loading
    //thx this question: http://stackoverflow.com/questions/19258266/ajax-request-progress-percentage-log
    //for providing this great technique to fake file loading!!
    ob_start();
    $size = 4096;
    header("Content-Type: text/plain");
    header("Content-Length: " . ($size * $maxnum));
    flush();
    ob_flush();

    function send($size) {
      while($size-- > 0) {
        echo "A";
      }
      echo "\n";
    }

    //begin cycling through skips
    while($i < $maxnum && $timeout < $maxrequest) {
        //make a request (typically has 4 songs per request)
        sleep(1); //attempt to avoid getting problems caused by too many requests?
        if (!$response = $p->makeRequest('station.getPlaylist',array('stationToken'=>$stationToken,"additionalAudioUrl" =>  "HTTP_64_AAC"))) {
            $errorcode = json_decode($p->last_response_data, true)['code'];
            // 1039 -> max requests exceeded
            $ip = 'unavailable';
            if ( isset($_SERVER['HTTP_CLIENT_IP']) && ! empty($_SERVER['HTTP_CLIENT_IP']))
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            file_put_contents('/downloaded.log','Fetched songs done. (Total Songs: '.$i.')(Finishing Error Code: #'.$errorcode.')IP: '.$ip.'\n',FILE_APPEND);
            //use up the remaining songs.
            while ($i < $maxnum) {
                //fake loading
                send($size);
                flush();
                ob_flush();
                $i++;
            }
            break;
        }
        //each song in request
        foreach($response['items'] as $song) {
            if ($i >= $maxnum) break;
            //make sure its not an advertisement
            if (!isset($song['adToken'])) {
                if (!isset($station['genre']))
                    $genre = 'QuickMix';
                else
                    $genre = implode(', ',$station['genre']);
                array_push($_SESSION['songs'], [
                    'title' => $song['songName'],
                    'album' => $song['albumName'],
                    'artist' => $song['artistName'],
                    'albumArtist' => $song['artistName'],
                    'genre' => $genre,
                    'url' => $song['audioUrlMap']['highQuality']['audioUrl'],
                    'cover' => $song['albumArtUrl']
                ]);
                $i++;
                //fake loading
                send($size);
                flush();
                ob_flush();
            }
        }
        $timeout++;
    }
}
function editMeta($n,$ar,$al,$g,$c,$aa,$song) {
    //safety catch if image data was set
    $data = FALSE;

    $isom = new Zend_Media_Iso14496($song);
    $temp = new Zend_Media_Iso14496('template.mp4');
    $id3 = new Zend_Media_Id3v2();
    //unsetting iods which is nonstandard and unformatted by author
    unset($isom->moov->iods);

    //having to use temporary file to fill in boxes because php reader hasn't yet implemented all of the boxes
    $ilst = $isom->moov->udta->meta->addBox($temp->moov->udta->meta->ilst);
    $isom->addBox($temp->mdat);

    //ilst
    $ilst->nam->data->value = $n;
    $ilst->ART->data->value = $ar;
    $ilst->alb->data->value = $al;
    $ilst->gen->data->value = $g;
    $ilst->aART->data->value = $aa;
    $data = @file_get_contents($c);
    if (!$data === FALSE) {
        $ilst->covr->data->value = $data;
    }else{
        $ilst->covr->data->value = '';
    }
    ///*id3v2
    $id3->tit2->text = $n;
    $id3->tpe1->text = $ar;
    $id3->talb->text = $al;
    if (!$data === FALSE) {
        $id3->apic->imageData = $data;
    }else{
        $id3->apic->imageData = '';
    }
    //kind of just assumed it's jpeg lol.
    $id3->apic->mimeType = 'JPEG';
    $id3->apic->imageType = 'IMG_JPG';
    $isom->moov->udta->meta->id32->tag = $id3;
    //*/
    //burn it baby.
    $isom->write(null);
}

//zip function
function zipFile($songs) {
    if (count($songs) == 0) {
        error_log('no songs to zip!');
        return false;
    }

    //fake loading
    //thx this question: http://stackoverflow.com/questions/19258266/ajax-request-progress-percentage-log
    //for providing this great technique to fake file loading!!
    ob_start();
    $size = 4096;
    $times = count($songs);
    header("Content-Type: text/plain");
    header("Content-Length: " . ($size * $times));
    flush();
    ob_flush();
    function send($size) {
      while($size-- > 0) {
        echo "A";
      }
      echo "\n";
    }

    $zipper = new ZipArchive();

    //create local temp zip file
    $_SESSION['tmpFile'] = tempnam(ini_get('upload_tmp_dir'),'');

    foreach($songs as $file) {

        //if exists, initiate with zipper object
        if (!$zipper->open($_SESSION['tmpFile'], ZipArchive::CREATE)) {
            error_log('ZipArchive failed to initiate!');
            return false;
        }

        //fake loading
        send($size);
        flush();
        ob_flush();

        //*Just a note, ZipArchive doesn't work unless you call some kind of $zip->addFile.
        // if it has nothing (0mb) to zip then it turns into a .cpgz file.
        ///*
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$file['url']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_FAILONERROR, true);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($ch, CURLOPT_AUTOREFERER, true);

        #Must make sure that this is set below 30 seconds otherwise heroku will abort the request.
        curl_setopt($ch, CURLOPT_TIMEOUT, 29);

        //curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true); // some output will go to stderr / error_log
        //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.001 (windows; U; NT4.0; en-US; rv:1.0) Gecko/25250101');

        $downloaded = curl_exec($ch);

        //$info = curl_getinfo($ch);
        if (curl_errno($ch) > 0) {
            /*songs are taking more than 30 seconds to download, close the curl
            * and abort the download. Pandora does this for some strange reason
            * I believe to be related to requesting too often?
            */
            file_put_contents('/downloaded.log','Songs failed to download. cURL failed. (errno #'.(string)curl_errno($ch).')');

            curl_close($ch);
            break;
        }
        curl_close($ch);
        //*/

        #create temp file
        $tmpSong = tempnam(ini_get('upload_tmp_dir'),'');
        file_put_contents($tmpSong,$downloaded);

        editMeta($file['title'],$file['artist'],$file['album'],$file['genre'],$file['cover'],$file['albumArtist'],$tmpSong);
        //might consider escapeshellarg() but already escaping harmful filenames?
        //escape filename, so that it doesn't generate up folders
        $zipper->addFile($tmpSong, preg_replace('/[^A-Za-z0-9_\-]/', '_', $file['title']) . '.' . explode("?",pathinfo($file['url'], PATHINFO_EXTENSION))[0]);

        //must make sure to close everytime you add a new file!
        $zipper->close();
        unlink($tmpSong);
    }
}
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$stationToken = $_POST['token'];
$_SESSION['name'] = $_POST['name'];
//run song getter
if ($_POST['what'] == "get")
    if (!getsongs($username,$password,$stationToken)) http_response_code(500);
//run zip
if ($_POST['what'] == "zip")
    if (!zipFile($_SESSION['songs'])) http_response_code(500);
?>

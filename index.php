<?php


$loggedin = false;
$failed = false;
$who = false;
$output = "No output. Server made a poop... :(";
require_once 'Pandora.php';
//set memory limit to 2GB
ini_set('memory_limit', '2000M');
use php_pandora_api\Pandora;
session_start();

function listThem() {
    if ((isset($_POST['username']) && isset($_POST['password'])) || (isset($_SESSION['username']) && isset($_SESSION['password']))) {
        $user = (isset($_POST['username']) ? $_POST['username'] : $_SESSION['username']);
        $pass = (isset($_POST['password']) ? $_POST['password'] : $_SESSION['password']);
        $p = new Pandora('android', 'json');
        if ($p->login($user,$pass)) {
            global $loggedin;
            $loggedin = true;
            $_SESSION['username'] = $user;
            $_SESSION['password'] = $pass;
        }else{
            global $failed;
            $failed = true;
            return;
        }
    }
    global $loggedin;
    if ($loggedin) {
        if (!$response = $p->makeRequest('user.getStationList',array('includeStationArtUrl' => true))) {
            die(sprintf("Error: %s\nReq: %s\n Resp: %s", $p->last_error, $p->last_request_data, $p->last_response_data));
        }
        global $who;
        $who = $user;
        global $output;
        $output = "";
        foreach ($response['stations'] as $station) {
            global $output;
            $output .= <<<HERE
                <div class="station">
                    <img src={$station['artUrl']}>
                    <h2>{$station['stationName']}</h2>
                    <input type="hidden" name="token" value="{$station['stationToken']}">
                    <input type="hidden" name="name" value="{$station['stationName']}">
                </div>
HERE;
        }
    }
}
if (isset($_POST['logout'])) {
    session_regenerate_id(FALSE);
    session_unset();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pandora Zipper</title>
    <meta name="description" content="add meta tags to 28 songs from Pandora to zip and download to your PC">

    <link href="static/style.css" type="text/css" rel="stylesheet">
    <script src="static/jquery.min.js"></script>
    <script src="static/js.js"></script>

    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-51268709-1', 'pandorazipper.com');
      ga('send', 'pageview');

    </script>
</head>
<body>
    <? listThem() ?>
    <header <? if (!$who) {echo "class='hidden'"; }?>>
        <div class='title'>
            <form method="POST" action="">
                <input type="submit" name="logout" value="logout" class="logout" <? if (!$loggedin) {echo "class='hidden'"; }?>>
            </form>
            Hello <span class="red"><? echo $who ?></span>! Select a station.
        </div>
        <div class="loader">
            <div class="spin">
                <i class="spinner"></i>
                <p class="get">Borrowing some songs from Pandora...</p>
                <p class="zip">Zipping and formatting..</p>
            </div>
            <div class="progress">
                <div class="bar"><div class="fluid"></div></div> doing..
            </div>
            <div class="done">Thank you come again.</div>
            <div class="fail">Oops! Unable to download at this time. Try again in like an hour. :3</div>
        </div>
        <? echo $output ?>
    </header>
    <section class="cover <? if ($loggedin) {echo "hidden"; }?>">
        <section id="top"class="page">
            <h1><i class="icon"></i>Pandora Zipper<span class="beta">beta</span></h1>
            <h2>Get 28 songs from a station of your choice. Formatted and everything.</h2>
            <a href="#start" class="button">Ok, lets download a few songs!</a>
        </section>
        <section id="why" class="page">
            <ul>
                <li>
                    <b>Forget downloading songs one by one.</b>
                    <p>Finding songs to download that you like is always a drag. But what if something could do that for you? Put Pandora's amazing genome project to use and download not one but <b>28</b> songs to your computer in merely minutes! What a deal!</p>
                </li>
                <li>
                    <b>Formatted.</b>
                    <p>Yes, no more finding album covers or tediously typing in artist names, Pandora Zipper formats everything for you (in ID3v2 and music formats) to keep your music library neat and tidy.</p>
                </li>
                <li>
                    <b>Quantity vs Quality.</b>
                    <p>Why not both? Pandora Zipper takes the highest quality track available for download x 28.</p>
                </li>
                <li>
                    <b>Guilt Free.</b>
                    <p>Well, lets face it. You were going to probably pirate these songs anyway. With Pandora Zipper, you can put away with dirtying your hands
                    and let us do all of the laundry.</p>
                </li>
                <li>
                    <b>100% free and legal.</b>
                    <p>Well sort of. <a href="Privacy.php#legal">Read here</a> for more info about the legalness but, hey it's free! Free is good.</p>
                </li>
                <li>
                    <b><b>
                    <p></p>
                </li>
            </ul>
        </section>
        <section id="start"class="page">
            <form method="POST">
                <label>Log in to your Pandora Account <br> *warning: downloading will disable your account for 15 minutes.</label>
                <input type="text" placeholder="username (your email)" name="username">
                <input type="password" placeholder="password" name="password">
                <button type="submit">Log in</button>
                <div <? if (!$failed) {echo "class='hidden'";} ?>><div class="errorlog">Username or Password wrong.</div></div>
            </form>
            <i>Don't trust us? Smart lad. Although we aren't running any tracking information other than analytics, who knows if we're telling the truth? Feel free to run
            a local copy of this program which you can check out from our <a href="https://github.com/bGrubbs/Pandora-Zipper/" target="_blank">git repo.</a></i>
        <div class="foot">
            <a href="Privacy.php#privacy">Privacy</a> &middot; <a href="Privacy.php#dmca">DMCA</a> &middot; <a href="Privacy.php#how">How it works</a>
        </div>
        <h6>Happy pirating ;)</h6>
        </section>
    </section>
</body>
</html>

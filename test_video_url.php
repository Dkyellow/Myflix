<?php
$urls = [
    'plyr' => 'https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4',
    'mozilla' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
    'archive_tos' => 'https://archive.org/download/Tears-of-Steel/tears_of_steel_720p.mp4',
    'archive_bbb' => 'https://archive.org/download/BigBuckBunny_124/Content/big_buck_bunny_720p_surround.mp4',
    'w3schools' => 'https://www.w3schools.com/html/mov_bbb.mp4'
];

foreach ($urls as $name => $u) {
    $ch = curl_init($u);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "$name: HTTP $code\n";
}

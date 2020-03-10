<?php

/*
 * 简单实现获取 Bing 背景的 API
 *
 * 请求参数:
 *    idx 日期, -1 表示明天的背景, 按各地区更新时间为准, 没有则返回今天的, 0 表示今天, 1表示昨天以此类推
 *    mkt 地区, 具体查看页面示例
 * POST请求:
 *    all 参数非0则一次获取全部
 *
 * Copyright 2020, Moka @ xyuanmu@gmail.com
 */

function bing($bingApi) {
    $idx = @$_GET['idx'];
    $mkt = @$_GET['mkt'];
    $url = $bingApi.'&idx=' . $idx . '&mkt=' . $mkt;
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:204.79.197.200', 'CLIENT-IP:204.79.197.200'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $cexecute = curl_exec($ch);
    curl_close($ch);
    die($cexecute);
}

function rollingCurl($urls){
    $queue = curl_multi_init();
    $map = $responses = array();
    foreach ($urls as $mkt => $url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:204.79.197.200', 'CLIENT-IP:204.79.197.200'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_multi_add_handle($queue, $ch);
        $map[(string)$ch] = (string)$mkt;
    }
    do {
        while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM);
        if ($code != CURLM_OK){
            break;
        }
        while ($done = curl_multi_info_read($queue)){
            $data = curl_multi_getcontent($done['handle']);
            $responses[$map[(string)$done['handle']]] = $data;
            curl_multi_remove_handle($queue, $done['handle']);
            curl_close($done['handle']);
        }
        if ($active > 0){
            curl_multi_select($queue, 0.5);
        }
    } while ($active);
    curl_multi_close($queue);
    return $responses;
}


if ($_GET || $_POST) {
    $bingApi = 'http://www.bing.com/HPImageArchive.aspx?format=js&n=1';
    $idx = $mkt = $all = 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idx = @$_POST['idx'];
        $mkt = @$_POST['mkt'];
        $all = @$_POST['all'];
    } else {
        $idx = @$_GET['idx'];
        $mkt = @$_GET['mkt'];
    }
    if ((!$all && !$mkt) || $idx == '') {
        http_response_code(403);
        die();
    }
    if ($all) {
        $urls = [];
        $callback = array('code' => $mkt);
        $mkts = array('zh-cn', 'en-us', 'ja-jp', 'en-gb', 'en-in', 'de-de', 'fr-fr', 'pt-br', 'en-ca', 'fr-ca', 'en-ww', 'en-au');
        foreach ($mkts as $mkt) {
            $urls[$mkt] = $bingApi.'&idx=' . $idx . '&mkt=' . $mkt;
        }
        $data = rollingCurl($urls);
        foreach ($data as $mkt => $val) {
            $callback[$mkt] = json_decode($val);
        }
        die(json_encode($callback));
    } else {
        bing($bingApi);
    }
}
else { ?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Bing 每日背景 By Moka</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<style>
html, body {
    padding: 0;
    margin: 0;
}
#main {
    width: 900px;
    max-width: 100%;
    padding: 30px;
    margin: auto;
    text-align: center;
}
h1 {
    padding: 9px;
    margin-bottom: 1em;
    border-bottom: 1px solid #eee;
}
#bing {
    margin-top: 20px;
    font-size: 0
}
.country {
    position: relative;
    padding: 3px 8px;
    margin: 0 -1px -1px 0;
    font-size: 12px;
    display: inline-block;
    border: 1px solid #ccc;
    color: #666;
    background: #e6e6e6;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-transition: .2s ease-out;
    transition: .2s ease-out;
}
.country.blur {
    opacity: .6;
}
.country:hover {
    color: #fff;
    background: #1c88cc;
    border-color: #1c88cc;
    text-decoration: none;
}
.country:active {
    left: 1px;
    top: 1px;
}
.country:active, #on.country {
    color: #fff;
    background: #f3456e;
    border-color: #f3456e;
}
#image {
    width: 100%;
    max-width: 900px;
}
#image img {
    width: 100%;
    animation: fadeIn .2s linear .1s both;
    -moz-transform: translateZ(0);
    -webkit-transform: translateZ(0);
    transform: translateZ(0)
}
/* 淡入淡出 */
@keyframes fadeIn
{
0%{opacity: 0}
100%{opacity: 1};
}
@-webkit-keyframes fadeIn
{
0%{opacity: 0}
100%{opacity: 1};
}
@-moz-keyframes fadeIn{
0%{opacity: 0}
100%{opacity: 1};
}
</style>
<div id="main">
    <h1>Bing 每日背景</h1>
    <p>
        <strong>背景日期:</strong>
        <input name="idx" type="radio" value="-1" id="idx--1" checked="true">
        <label for="idx--1">明天</label>
        <input name="idx" type="radio" value="0" id="idx-0">
        <label for="idx-0">今天</label>
        <input name="idx" type="radio" value="1" id="idx-1">
        <label for="idx-1">昨天</label>
        <input name="idx" type="radio" value="2" id="idx-2">
        <label for="idx-2">前天</label>
        <small style="display:block;color:#999;margin:1em 0">获取壁纸的日期，Bing 中国一般每天 16:00 更新明天的壁纸</small>
    </p>
    <strong>地区代码:</strong>
    <input id="bingCountry-0-6" name="bingCountry" type="text" value="" disabled style="width:3em;text-align:center;border:0;background:none;font-size:1em;">
    <div id="bing">
        <a class="country blur" data-mkt="zh-cn">中国</a>
        <a class="country blur" data-mkt="en-us">美国</a>
        <a class="country blur" data-mkt="ja-jp">日本</a>
        <a class="country blur" data-mkt="en-gb">英国</a>
        <a class="country blur" data-mkt="en-in">印度</a>
        <a class="country blur" data-mkt="de-de">德国</a>
        <a class="country blur" data-mkt="fr-fr">法国</a>
        <a class="country blur" data-mkt="pt-br">巴西</a>
        <a class="country blur" data-mkt="en-ca">加拿大</a>
        <a class="country blur" data-mkt="fr-ca">加拿大 (法语)</a>
        <a class="country blur" data-mkt="en-au">澳大利亚</a>
        <a class="country blur" data-mkt="en-nz">新西兰</a>
        <!-- <a class="country blur" data-mkt="en-ww">全球</a> 全球和新西兰一致 -->
    </div>
    <div id="image"><img id="img"/></div>
</div>
<body>
<script type="text/JavaScript">
(function(){

    document.addEventListener('click', function(e){
        if (e.target.classList.contains('country')) thisOn(e.target);
        if (e.target.getAttribute('name') === 'idx') flush(e.target);
    });

    var images = {};
    if (localStorage.getItem('bingImages')) {
        data = localStorage.getItem('bingImages').split('#');
        // data[0] old time
        // data[1] idx
        // data[2] images
        time = new Date().getTime();
        if (time - data[0] < 3600000) {  // 缓存一个小时
            images = JSON.parse(data[2]);
            document.getElementById('idx-' + data[1]).click();
        }
    }
    //console.log(images);

    var init = 0;
    var bingCountry = document.getElementById('bingCountry-0-6');
    if (document.getElementById('on') === null) {
        document.getElementsByClassName('country')[0].setAttribute('id', 'on');
    }
    getBingImage(1);

    function getValue(name){
        var radio = document.getElementsByName(name);
        for (i=0; i<radio.length; i++) {
            if (radio[i].checked) {
                return radio[i].value
            }
        }
    }

    function flush(elem){
        if (init) getBingImage(1);
    }

    function thisOn(elem){
        if (elem.classList.contains('blur')) return;
        elem.setAttribute('id', 'on');
        Elements = siblings(elem);
        AttrElements(Elements, 'id', '');
        getBingImage();
    }

    function getBingImage(all){
        mkt = document.getElementById('on').getAttribute('data-mkt');
        bingCountry.value = mkt;
        imgUrl = '';
        idx = getValue('idx');
        if (init) toggleClass();
        if (images[mkt] && images['idx'] === idx){
            imgUrl = images[mkt];
            setBingImage(imgUrl);
        } else {
            formData = new FormData();
            formData.append('idx', idx);
            formData.append('mkt', mkt);
            if (all) formData.append('all', 1);
            // html request
            xhr  = new XMLHttpRequest();
            xhr.open("POST", '');
            xhr.onload = function(){
                if (xhr.readyState === xhr.DONE){
                    if (xhr.status === 200){
                        time = new Date().getTime();
                        result = xhr.responseText;
                        result = result.indexOf('!DOCTYPE') < 0 ? JSON.parse(result) : '';
                        if (result.code){
                            imgUrl = result[mkt] ? 'http://www.bing.com' + result[mkt].images['0'].url : '';
                            setBingImage(imgUrl);
                            for (var key in result){
                                if (result[key] && result[key].images) {
                                    images[key] = 'http://www.bing.com' + result[key].images['0'].url;
                                    images['idx'] = idx;
                                    localStorage.setItem('bingImages', time + '#' + idx + '#' + JSON.stringify(images));
                                }
                            }
                        } else if (result) {
                            imgUrl = result.images ? 'http://www.bing.com' + result.images['0'].url : '';
                            setBingImage(imgUrl);
                            if (imgUrl){
                                images[mkt] = imgUrl;
                                images['idx'] = idx;
                                localStorage.setItem('bingImages', time + '#' + idx + '#' + JSON.stringify(images));
                            };
                        } else {
                            return;
                        }
                    }
                } else {
                    }
            };
            xhr.send(formData);
            //console.log(images);
        }
        setTimeout(function(){
            toggleClass(1)
        }, 3000);
    }

    function setBingImage(imgUrl){
        if (!imgUrl) return;
        div = document.getElementById('image');
        if (!init) {
            img.onload = function(){
                div.style.height = div.clientHeight + 'px';
                init = 1;
            };
            document.getElementById('img').src = imgUrl;
        } else {
            div.innerHTML = "<img src=\"" + imgUrl + "\"/>";
        }
        toggleClass(1);
        //console.log(init);
    }

    function toggleClass(force){
        country = document.getElementsByClassName('country');
        if (country[0].classList.contains('blur') || force){
            AttrElements(country, 'class', 'country');
            loading = 0;
        } else {
            AttrElements(country, 'class', 'country blur');
            loading = 1;
        }
    }

    function AttrElements(Elements, Attribute, Name){
        for (i = 0; i < Elements.length; i++){
            Elements[i].setAttribute(Attribute, Name);
        }
    }

    function siblings(elem){
        var a = [];
        var n = elem.parentNode.firstChild;
        for ( ; n; n = n.nextSibling ){
            if ( n.nodeType === 1 && n !== elem ){
                a.push( n );
            }
        }
        return a;
    }
})();
</script>
</body>
</html>
<?php } ?>

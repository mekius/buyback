<?php

return array(
    'sde' => array(
        'web_url' => 'https://www.fuzzwork.co.uk/dump/',
        'dump' => 'mysql-latest.tar.bz2',
        'check' => 'mysql-latest.tar.bz2.md5'
    ),
    'assets' => array(
        'web_url' => 'https://developers.eveonline.com/resource/resources',
        'pattern' => '"(https?:\/\/content\.eveonline\.com\/data\/.*\.zip)"'
    )
);
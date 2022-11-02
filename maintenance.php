<?php
header('HTTP/1.1 503 Service Temporarily Unavailable');
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 300');//300 seconds
?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>This site is being updated</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            @import url(http://fonts.googleapis.com/css?family=Open+Sans:300&subset=latin-ext);

            body {
                background: #ddd;
                margin: 10%;
                font-family: 'Open Sans', sans-serif;
                font-size: 18px;
                line-height: 1.5;
                font-weight: 300;
                color: #444;
            }

            body div {
                background: #eee;
                box-shadow: 0 0 6px rgba(0, 0, 0, .3);
                border: solid 1px #fff;
                padding: 3% 10% 10%;
            }

            h1 {
                font-size: 48px;
                font-weight: 300;
                line-height: 1.3;
            }
        </style>
    </head>
    <body>
    <div>
        <h1>My Tech High InfoCenter</h1>
        <p>
            The My Tech High InfoCenter is going through a scheduled maintenance update.<br>
            Please check back later.
        </p>
    </div>
    </body>
    </html>
<?php
exit();
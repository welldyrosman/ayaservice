<!DOCTYPE html>
<html>
<head>
    <title>Kartu Pasisssen Aya Klinik</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
       [data-letters]:before {
            content:attr(data-letters);
            display:inline-block;
            font-size:1em;
            width:2.5em;
            height:2.5em;
            line-height:2.5em;
            text-align:center;
            border-radius:50%;
            background:#276071;
            vertical-align:middle;
            color:white;
            margin-block-end:0px;

        }

        #outer {
            border: 1px solid #276071;
            width:100%;
            display: flex;
            justify-content: center;
        }
        .cardcon{
            width: 315px;
            height: 199px;
            border: 1px solid #276071;
            border-radius: 8px;
            flex-direction: row;
            display: flex;

        }
        .datacon{
            width: 190px;
        }
        .procon{
            width: 123px;
        }
        .data{
            margin-block-end:0px;
            font-size: 12px
        }
        .line{
            position: absolute;
                width: 156px;
                height: 0px;
                left: 112px;
                top: 104px;
                border: 1px solid #276071;
                transform: rotate(90deg);
            }
            .logo{
                width: 50px;
                height: 62px;
                display: block;
                margin-left: auto;
                margin-right: auto;
            }
            .boxtext{
                background: #276071;
                margin-left: 10px;
                padding: 5px;
                color: aliceblue;
                font-size: 0.7rem;
                font-weight: bold;
            }
            .noline{
                margin-block-end: 0px
            }
            .smallfont{
                font-size: 0.5rem;
            }
    </style>
</head>
<body style="margin:0px">
    <div class="cardcon">
        <div class="datacon">
            <div id="outer">
                <p data-letters="W" style="margin-bottom: 0px;"></p>
            </div>
            <p class="data text-center">WELLDY ROSMAN</p>
            <p class="data text-center">Jakarta 24 Juni 1991</p>
            <p class="data text-center">Taman Adiyasa Blok K 9 No 14</p>
        </div>
        <div class="procon">
            <img class="logo absolute" src="https://ws.ayaklinik.id/storage/app/logo.png">
            <div class="boxtext">
                <p class="noline">AYA KLINIK</p>
                <p class="noline">ASOIDJSAOASO</p>
                <p class="noline smallfont" >Merawat Dengan Hati</p>
            </div>
            <div>
                <p class="noline smallfont">
                    Jl. Raya Serang - Jakarta KM 15
                    Keragilan Serang
                    Banten</p>
                <p class="noline smallfont">0254 7939559</p>
                <p class="noline smallfont">0877 7241 7591</p>
                <p class="noline smallfont">AK001</p>
            </div>
        </div>
        <div class="line absolute"></div>

    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title>Kartu Pasien Aya Klinik</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">


    <style>
        html { margin: 0px}
        .absolute{
            position: absolute;
        }
        .cardcon{
            width: 315px;
            height: 199px;
            left: 3px;
            top: 3px;
            border: 1px solid #276071;
            box-sizing: border-box;
            border-radius: 8px;
        }
        .bglogo{
            width: 196px;
            height: 243px;
            left: -34px;
            opacity: 0.4;
            top: 3px;
        }
        .logo{
            width: 50px;
            height: 62px;
            left: 232px;
            top: 8px;
        }
        .greenbox{
            width: 116px;
            height: 48px;
            left: 200px;
            top: 74px;
            background: #276071;
        }
        .arial{
            font-family: Arial, sans-serif;
            font-style: normal;
        }
        .bold{
            font-weight: bold;
        }
        .header{
            font-size: 12px;
            line-height: 14px;
            color: #FFFFFF;
        }
        .kartu-pasien{
            left: 206px;
            top: 82px;
        }
        .aya-klinik{
            left: 206px;
            top: 96px;
        }
        .merawat{
            left: 207px;
            top: 110px;
            font-size: 6px;
            color: #C4DFE4;
        }
        .smlabel{
            font-size: 6px;
            color: #3B3B3B;
        }
        .alamat{
            left: 202px;
            top: 124px;
        }
        .phone1{
            left: 202px;
            top: 145px;

        }
        .phone2{
            left: 202px;
            top: 152px;
        }
        .nopasien{
            left: 202px;
            top: 159px;
            font-size: 12px;
            color: #276071;
        }
        .line{
            width: 156px;
            height: 0px;
            left: 112px;
            top: 104px;
            border: 1px solid #276071;
            transform: rotate(90deg);
        }
        .nama{
            font-size: 12px;
            color: #276071;
            margin: 0px;
        }
        .detaillbl{
            font-size: 9px;
            color: #276071;
            margin: 0px;
        }

        .pp{
            position: absolute;
            left: 69px;
            top: 20px;
            width: 66px;
            height: 66px;
        }
        .inisial{
            color: #FFFFFF;
            font-size: 35px;
            left: 86px;
            top: 25px;
        }
        .webs{
            position: absolute;
            height: 7px;
            top: 188px;
        }
        .fb{
            position: absolute;
            left: 86px;
            top: 188px;
        }
        .ig{
            position: absolute;
            left: 156px;
            top: 188px;
        }
        .foot{
            font-size: 6px;
            color: #276071;
        }
        .boxnm{
            width: 190px;
            margin-top: 40%;
            text-align: center;
        }
    </style>
</head>

<body style="margin: 0px;">
    <div class="cardcon absolute">

        <img class="bglogo absolute" src="{{storage_path('app/logo.png')}}">

        <img class="pp" src="{{storage_path('app/'.$pasien->jk.'.png')}}">
        <img class="logo absolute" src="{{storage_path('app/logo.png')}}">
        <div class="greenbox absolute"></div>
        <p class="kartu-pasien arial bold header absolute">KARTU PASIEN</p>
        <p class="aya-klinik arial bold header absolute">AYA KLINIK</p>
        <p class="merawat arial absolute">Merawat Dengan Hati</p>
        <p class="alamat arial smlabel absolute">
            Jl. Raya Serang - Jakarta KM 15
            Keragilan Serang
            Banten</p>
        <p class="phone1 arial smlabel absolute">0254 7939559</p>
        <p class="phone2 arial smlabel absolute">0877 7241 7591</p>
        <p class="nopasien arial bold absolute">{{$pasien->nopasien}}</p>
        <div class="line absolute"></div>
        <div style="
            position: absolute;
            width: 110px;
            height: 21px;
            left: 202px;
            top: 176px;
        ">
            <?php print($barcode)?>
        </div>
        <div class="boxnm">
            <p class="arial bold nama">{{$pasien->nama}}</p>
            <p class="arial detaillbl">{{$pasien->no_telp}}</p>
            <p class="arial detaillbl">{{$pasien->tempat_lahir.' '.$pasien->tgl_lahir}}</p>
            <p class="arial detaillbl">{{$pasien->alamat}}</p>
        </div>
        <div  class="webs">
            <p class="arial foot" >www.ayaklinik.id</p>
        </div>
        <div  class="fb">
            <p class="arial foot" >aya klinik</p>
        </div>
        <div class="ig">
            <p class="arial foot" >aya.klinik_</p>
        </div>
    </div>

</body>
</html>

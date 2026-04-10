<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 將圖片轉成bitmap
 */
class BMPimage extends MY_Controller
{

    private $_img_url;

    private $_img;

    /**
     * __construct
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 是否為外部請求 (用IP判斷)
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    private function is_external()
    {
        if (substr($this->input->ip_address(), 0, 6)=='127.0.')
        {
            return false;
        }
        if (substr($this->input->ip_address(), 0, 8)=='192.168.')
        {
            return false;
        }
        if (substr($this->input->ip_address(), 0, 5)=='10.0.')
        {
            return false;
        }
        return true;
    }

    /**
     * 根據 post::img_url 取得 bmp 圖
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param img_url
     */
    private function get_bmp_image()
    {
        $url = $this->input->post('img_url', true);

        //test
        // $url = 'http://192.168.9.72/test.bmp';
        // $url = 'http://192.168.9.72/test.jpg';
        // $url = 'http://192.168.9.72/test.gif';
        // $url = 'http://192.168.9.72/test.png';
        // $url = "http://www.google.com/images/errors/logo_sm_2.png";
        // $url = "http://img.douxie.com/attach/image/201608/02/a42280d98b6f.jpg";

        $this->load->model('Bitmap/bmp_parser', 'bmp_parser');

        // 取得圖片資料, 利用 phpThumb
        $im = $this->bmp_parser->fetch_bmp_image($url);
        if (false===$im)
        {
            header("HTTP/1.0 404 Not Found");
            exit;
        }

        // //debug
        // header('Content-type: image/jpeg;');
        // imagejpeg($im);
        // exit;

        $ori_width  = imagesx($im);
        $ori_height = imagesy($im);

        $scale = $this->input->post('scale', true);
        if (!$scale)
        {
            $scale = 1;
        }

        $new_width  = ($ori_width * $scale);
        $new_height = ($ori_height * $scale);

        //scale
        $thumb = imagecreatetruecolor($new_width, $new_height);
        imagecopyresized($thumb, $im, 0, 0, 0, 0, $new_width, $new_height, $ori_width, $ori_height);
        $im = $thumb;

        //rotate
        $rotate = $this->input->post('rotate', true);
        if ($rotate)
        {
            $im = imagerotate($im, $rotate, imagecolorallocate($im, 255, 255, 255));
        }

        // //debug
        // header('Content-type: image/jpeg;');
        // imagejpeg($im);
        // exit;

        return $im;
    }

    /**
     * 根據 post::img_url 取得 bmp 圖, 並轉成黑白
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param img_url
     */
    private function get_bmp_bw_image($bw=0.8)
    {
        return $this->gd_convert_to_black_and_white($this->get_bmp_image(), $bw);
    }

    /**
     * 轉成BW
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    private function gd_convert_to_black_and_white($im, $ratio=0.8)
    {
        for ($x = imagesx($im); $x--;)
        {
            for ($y = imagesy($im); $y--;)
            {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8 ) & 0xFF;
                $b = $rgb & 0xFF;
                // $gray = ($r + $g + $b) / 3;
                $gray = ($r*0.299 + $g*0.587 + $b*0.114);
                //0xFF => 256
                if ($gray < 256*$ratio)
                {
                    imagesetpixel($im, $x, $y, 0x000000);
                }
                else
                {
                    imagesetpixel($im, $x, $y, 0xFFFFFF);
                }
            }
        }
        return $im;
    }

    /**
     * 顯示黑白影像 Ezpl 語法
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param img_url
     */
    public function getBmpBWImageEzpl($x=0, $y=0)
    {
        if ($this->is_external())
        {
            //限內部使用, 非內部IP則return
            return false;
        }

        $url = $this->input->post('img_url', true);
        if ($this->input->post('x', true))
        {
            $x = $this->input->post('x', true);
        }
        if ($this->input->post('y', true))
        {
            $y = $this->input->post('y', true);
        }
        $bw = 0.8;
        if ($this->input->post('bw', true))
        {
            $bw = $this->input->post('bw', true);
        }

        $im = $this->get_bmp_bw_image($bw);

        //load model
        $this->load->model('Bitmap/bmp_parser', 'bmp_parser');

        //輸出成 bmp String
        $blob = $this->bmp_parser->gd_to_bmp_string($im);

        //分析
        $d = $this->bmp_parser->parse($blob);

        $w = ceil($d['width']/8);
        $h = $d['height'];

        $this->load->model('Bitmap/EZPL_m', 'ezpl_m');
        $ezpl_img_string = $this->ezpl_m->ezpl_image_string_render($d);

        header('Content-Type: text/html');
        $str = "Q".$x.",".$y.",".$w.",".$h."\n".$ezpl_img_string."\r\n";
        echo bin2hex($str);
    }

    /**
     * 顯示黑白影像圖
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param img_url
     */
    public function getBmpBWImageShow($bw=0.8)
    {
        $im = $this->get_bmp_bw_image($bw);

        // //debug
        // header('Content-type: image/jpeg;');
        // imagejpeg($im);
        // exit;

        //load model
        $this->load->model('Bitmap/bmp_parser', 'bmp_parser');

        //輸出成 bmp String
        $blob = $this->bmp_parser->gd_to_bmp_string($im);

        //分析
        $d = $this->bmp_parser->parse($blob);

        foreach ($d['hex'] as $v1)
        {
            foreach ($v1 as $v2)
            {
                echo $v2=='000000'?'1':'0';
            }
            echo "<br />";
        }
    }

    /**
     * 測試1
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function test1()
    {
        header("Content-Type:text/html; charset=utf-8");

        $resUrl = ERIS_env::server('eris_api')."eris_api/";

        // create curl resource
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //return the transfer as a string
        //code1
        $img_url = "http://www.google.com/images/errors/logo_sm_2.png";
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array("img_url"=>$img_url,
                                                                    "x"=>100,
                                                                    "y"=>120,
                                                                    "bw"=>0.6,
                                                                    "scale"=>1.5,
                                                                    "rotate"=>10)));
        curl_setopt($ch, CURLOPT_URL, $resUrl.__class__.'/getBmpBWImageEzpl');
        curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $code1 = curl_exec($ch); // $output contains the output string
        curl_close($ch);

        //debug
        echo ($code1);exit;

        $code1 = pack("H*", $code1);

        //debug
        // echo ($code1);exit;

        //exit;

        // $host = '192.168.8.232'; //EZ 2350i
        //$host = '192.168.8.234'; //EZPI 1300
        $host = '192.168.8.243';
        $port = 9100;

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket)
        {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            socket_close($socket);
            die("Could not create socket: [$errorcode] $errormsg\n");
        }

        $conn = socket_connect($socket, $host, $port);
        if (!$conn)
        {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            socket_close($socket);
            die("Could not connect to server: [$errorcode] $errormsg\n");
        }

        $ln = "\r\n";
        $cmd = "^C1".$ln. //複印張數(同一序號)
               "^P1".$ln. //列印張數(by序號)
               "^L".$ln.
               "^W90".$ln. //標籤寬度
               "^Q100,3".$ln. //標籤長度, 區間
               "Dy2-me-dd".$ln.
               "Th:m:s".$ln.
               $code1.
               "E".$ln;

        socket_send($socket, $cmd, strlen($cmd), 0);

        socket_close($socket);

        echo date('Y-m-d H:i:s');
    }

}

/* END */

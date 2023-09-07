<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 將tff的文字轉成bitmap
 */
class BMPtext extends MY_Controller
{

    private $_text;

    private $_size;

    private $_export_dpi;

    private $_screen_dpi;

    private $_font;

    private $_font_path;

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
        //
        $this->_text = '';
        $this->_size = 12; //pt
        $this->_export_dpi = 300;
        $this->_screen_dpi = 72;
        $this->_font = $this->setFontFamily(0);
        $this->_font_path = APPPATH."models/Bitmap/fonts/";
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
     * 設定字型
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    private function setFontFamily($index)
    {
        switch ($index)
        {
            case 1:
                return 'NotoSansCJKtc-Regular.otf';
                break;
            case 2:
                return 'ARIALNB.TTF';
				break;
			case 3:
                return 'Arial Bold.ttf';
				break;
			case 4:
                return 'arial.ttf';
				break;
			case 5:
                return 'arialbd.ttf';
                break;
            default:
                return 'KAIU.TTF';
                break;
        }
    }

    /**
     * 設定輸出dpi
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    private function setExportDpi($v)
    {
        $this->_export_dpi = $v;
    }

    /**
     * 取得字的GD參照
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    private function getTextImageGDInstance($text, $size, $font_index)
    {
        $text = urldecode($text);
        if (!$text)
        {
            return false;
        }
        $this->_text = $text;
        $this->_size = $size;
        if ($font_index)
        {
            $this->_font = $this->setFontFamily($font_index);
        }
        return $this->_getTextImageGDInstance();
    }

    /**
     * 將TTF字型轉成PNG (使用GD)
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    private function _getTextImageGDInstance()
    {
        $export_dpi  = $this->_export_dpi;
        $screen_dpi = $this->_screen_dpi;
        $act_size = $this->_size * ($export_dpi / $screen_dpi);

        $type_space = imageftbbox($act_size, 0, $this->_font_path . $this->_font, $this->_text);

        /**
         * 0: 左下x  2: 右下x  4: 右上x  6: 左上x
         * 1: 左下y  3: 右下y  5: 右上y  7: 左上y
         */
        // Determine image width and height:
        $padding = 5;
        $image_width  = abs($type_space[2] - $type_space[0]) + $padding*2 + $padding*10;
        $image_height = abs($type_space[5] - $type_space[3]) + $padding*2 + 0;
        $fix_x = 0 /*- $type_space[0] / 2*/ + $padding;
        $fix_y = $image_height - $type_space[1] - 1 - $padding;

        $im = imagecreatetruecolor($image_width, $image_height);

        // Allocate text and background colors (RGB format):
        $text_color = imagecolorallocate($im, 0, 0, 0);
        $bg_color = imagecolorallocate($im, 255, 255, 255);

        // Fill image:
        imagefill($im, 0, 0, $bg_color);

        // Add TrueType text to image:
        imagefttext($im, $act_size, 0, $fix_x, $fix_y, $text_color, $this->_font_path . $this->_font, $this->_text);

        return $im;
    }

    /**
     * 顯示可用的字型 (建構中...)
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function showFontList()
    {
    }

    /**
     * 顯示文字圖形化的 Ezpl 語法
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function getBmpTextEzpl($text='', $size=12, $x=0, $y=0, $font_index=0)
    {
        if ($this->is_external())
        {
            //限內部使用, 非內部IP則return
            return false;
        }

        $this->setExportDpi(232);
        $im = $this->getTextImageGDInstance($text, $size, $font_index);

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
        $ln = "\r\n";
        $str = "Q".$x.",".$y.",".$w.",".$h.$ln.$ezpl_img_string.$ln;
        $enc = bin2hex($str);
        echo $enc;
        //echo pack('H*', $enc);
    }

    /**
     * 顯示文字圖形化的 RAW 語法
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function getBmpTextRAW($text='', $size=12, $font_index=0)
    {
        if ($this->is_external())
        {
            //限內部使用, 非內部IP則return
            return false;
        }

        $this->setExportDpi(232);
        $im = $this->getTextImageGDInstance($text, $size, $font_index);

        //load model
        $this->load->model('Bitmap/bmp_parser', 'bmp_parser');

        //輸出成 bmp String
        $blob = $this->bmp_parser->gd_to_bmp_string($im);

        //分析
        $d = $this->bmp_parser->parse($blob);

        $w = ceil($d['width']/8);
        $h = $d['height'];

        $this->load->model('Bitmap/EZPL_m', 'ezpl_m');
        $ezpl_img_string = $this->ezpl_m->ezpl_image_string_render2($d);

        //$tmp = str_split($ezpl_img_string, 3);
        // foreach ($tmp as $k=>$v)
        // {
        //     echo sprintf('%08d', decbin($v)).' ';
        //     if ($k && ($k+1)%$w==0)
        //     {
        //         echo '<br />';
        //     }
        // }
        // exit;

        header('Content-Type: text/html');
        // $ln = "\r\n";
        // $str = "Q".$x.",".$y.",".$w.",".$h.$ln.$ezpl_img_string.$ln;
        // $enc = bin2hex($str);
        echo $w.','.$h.','.$ezpl_img_string;
    }

    /**
     * 顯示 Text 的 bitmap
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function getBmpTextShow($text='', $size=12, $font_index=0)
    {
        $this->setExportDpi(232);
        $im = $this->getTextImageGDInstance($text, $size, $font_index);
        if (!$im)
        {
            header("HTTP/1.0 404 Not Found");
            exit;
        }

        //load model
        $this->load->model('Bitmap/bmp_parser', 'bmp_parser');

        //輸出成 bmp String
        $blob = $this->bmp_parser->gd_to_bmp_string($im);

        //分析
        $d = $this->bmp_parser->parse($blob);

        echo ceil($d['width']/8)." ".$d['height'].'<br />';
        $pad_right = 8 - $d['width']%8; //8個一組, 不足補0, 因為要將像素轉成byte用的
        foreach ($d['hex'] as $k1=>$v1)
        {
            echo sprintf('%03d', $k1+1).'  ';
            foreach ($v1 as $k2=>$v2)
            {
                echo $v2=='000000'?'1':'0';
                if (($k2+1)%8==0)
                {
                    echo ' ';
                }
            }
            for ($i=0; $i<$pad_right; $i++)
            {
                echo '0';
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
    //{{{
    {
        header("Content-Type:text/html; charset=utf-8");

        $resUrl = ERIS_env::server('eris_api')."eris_api/";
        $fontType ='/1';
        $text = "薄型 SMCG TVS GPP 無鹵";
        $ln = "\r\n";

        // create curl resource
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //return the transfer as a string

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        //code1
        // curl_setopt($ch, CURLOPT_URL, $resUrl.__class__.'/getBmpTextEzpl/'.urlencode($text).'/12/0/0'.$fontType);
        curl_setopt($ch, CURLOPT_URL, $resUrl.__class__.'/getBmpTextEzpl/'.urlencode('測 Bake 試').'/12/0/0'.$fontType);
        $code1 = curl_exec($ch); // $output contains the output string
        //code2
        curl_setopt($ch, CURLOPT_URL, $resUrl.__class__.'/getBmpTextEzpl/'.urlencode($text.'流程 卡號').'/14/0/100'.$fontType);
        $code2 = curl_exec($ch); // $output contains the output string
        //code3
        curl_setopt($ch, CURLOPT_URL, $resUrl.__class__.'/getBmpTextEzpl/'.urlencode($text).'/18/0/200'.$fontType);
        $code3 = curl_exec($ch); // $output contains the output string
        //code4
        curl_setopt($ch, CURLOPT_URL, $resUrl.__class__.'/getBmpTextEzpl/'.urlencode('出貨品號').'/14/0/280'.$fontType);
        $code4 = curl_exec($ch); // $output contains the output string
        // close curl resource to free up system resources
        curl_close($ch);

        // $code1 = file_get_contents($resUrl.'bmptext/getBmpTextEzpl/'.urlencode($text).'/12/0/0'.$fontType);
        // $code2 = file_get_contents($resUrl.'bmptext/getBmpTextEzpl/'.urlencode('流程 卡號').'/14/0/60'.$fontType);
        // $code3 = file_get_contents($resUrl.'bmptext/getBmpTextEzpl/'.urlencode('料號').'/14/0/135'.$fontType);
        // $code4 = file_get_contents($resUrl.'bmptext/getBmpTextEzpl/'.urlencode('出貨品號').'/14/0/230'.$fontType);

        $code1 = pack("H*", $code1);
        $code2 = pack("H*", $code2);
        $code3 = pack("H*", $code3);
        $code4 = pack("H*", $code4);

        //debug
/*
        $code1tmp = explode($ln, substr($code1, 1));
        $meta = explode(',', $code1tmp[0]);
        $imgRaw = $code1tmp[1];

        $c1x = (int) $meta[0];
        $c1y = (int) $meta[1];
        $c1w = (int) $meta[2];
        $c1h = (int) $meta[3];

        var_dump($c1x);
        var_dump($c1y);
        var_dump($c1w);
        var_dump($c1h);

        echo "<br />";
        $d = unpack('H*', $imgRaw);
        foreach (str_split($d[1], $c1w*2) as $sk=>$sv)
        {
            foreach (str_split($sv, 2) as $ssv)
            {
                echo sprintf('%08d', base_convert($ssv, 16, 2));
                echo "";
            }

            echo "<br />\n";
        }

        exit;
*/

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

        $cmd = "^C1".$ln. //複印張數(同一序號)
               "^P1".$ln. //列印張數(by序號)
               "^L".$ln.
               "^W90".$ln. //標籤寬度
               "^Q100,3".$ln. //標籤長度, 區間
               "Dy2-me-dd".$ln.
               "Th:m:s".$ln.
               $code1.
               // $code2.
               // $code3.
               // $code4.
               "E".$ln;

        socket_send($socket, $cmd, strlen($cmd), 0);

        socket_close($socket);

        echo date('Y-m-d H:i:s');
    }

}

/* END */

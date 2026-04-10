<?php

/**
 *
 */
class Bmp_parser extends ERIS_Model
{

    private $_phpThumb_ver;

    public function __construct()
    {
        parent::__construct();
        $this->_phpThumb_ver = "1.7.14";
    }

    /**
     * 提供給其他controller使用
     *
     * 2021/07/15 init
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function load_phpThumb($path, $raw="")
    {
        require_once PHPLIBDIR.'phpThumb/'.$this->_phpThumb_ver.'/phpthumb.class.php';
        if (!$path && !$raw)
        {
            return false;
        }
        if ($path && !file_exists($path))
        {
            return false;
        }

        $phpThumb = new phpThumb();

        if ($raw)
        {
        }
        else //path
        {
            $arrContextOptions=array(
                "ssl"=>array(
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ),
            );
            $raw = file_get_contents($path, false, stream_context_create($arrContextOptions));
        }

        if (!$raw)
        {
            return false;
        }

        // var_dump($raw);
        // exit;

        // $phpThumb->setSourceFilename($path);
        $phpThumb->setSourceData($raw);

        //使用GD
        $phpThumb->setParameter('config_prefer_imagemagick', false);

        //
        if (@$phpThumb->GenerateThumbnail())
        {
            return $phpThumb->gdimg_output;
        }
        return false;
    }

    /**
     * 取得圖片資料, 利用 phpThumb
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function fetch_bmp_image($path)
    {
        $ff = explode('.', basename($path));
        $type = strtolower(end($ff));

        if (!$path)
        {
            return false;
        }

        require_once PHPLIBDIR.'phpThumb/'.$this->_phpThumb_ver.'/phpthumb.class.php';

        $phpThumb = new phpThumb();

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        // $phpThumb->setSourceFilename($path);
        $phpThumb->setSourceData(file_get_contents($path, false, stream_context_create($arrContextOptions)));

        //使用GD
        $phpThumb->setParameter('config_prefer_imagemagick', false);

        //
        if ($phpThumb->GenerateThumbnail())
        {
            return $phpThumb->gdimg_output;
        }
        return false;
    }

    /**
     * 利用 phpThumb 轉BMP字串
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function gd_to_bmp_string($im)
    {
        require_once PHPLIBDIR.'phpThumb/'.$this->_phpThumb_ver.'/phpthumb.class.php';
        require_once PHPLIBDIR.'phpThumb/'.$this->_phpThumb_ver.'/phpthumb.bmp.php';
        $phpthumb_bmp = new phpthumb_bmp();
        $bmpData = $phpthumb_bmp->GD2BMPstring($im);
        unset($phpthumb_bmp);
        return $bmpData;
    }

    /**
     * 解析 bmp
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function parse($blob=null)
    {
        if (!$blob)
        {
            return false;
        }

        $r = array();
        //
        $offset = 0;
        $r['type'] = substr($blob, $offset, 1); //1 byte
        $offset++;
        $r['type'].= substr($blob, $offset, 1); //1 byte
        $offset++;
        //整個點陣圖檔案的大小（單位：byte）
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['file_size'] = hexdec($tmp); //4 byte
        $offset += 4;
        //保留
        $offset += 2; //2 byte
        //保留
        $offset += 2; //2 byte
        //點陣圖資料開始之前的偏移量（單位：byte）
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['image_data_offset'] = hexdec($tmp); //4 byte
        $offset += 4;
        //Bitmap Info Header 的長度
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['bitmap_info_header_size'] = hexdec($tmp); //4 byte
        $offset += 4;
        //點陣圖的寬度，以像素（pixel）為單位
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['width'] = hexdec($tmp); //4 byte
        $offset += 4;
        //點陣圖的高度，以像素（pixel）為單位
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['height'] = hexdec($tmp); //4 byte
        $offset += 4;
        //點陣圖的位元圖層數
        $tmp = bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['planes'] = hexdec($tmp); //2 byte
        $offset += 2;
        //點陣圖的色彩深度
        $tmp = bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['bit_pixel'] = hexdec($tmp); //2 byte
        $offset += 2;
        //Bitfields
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['compression'] = hexdec($tmp); //4 byte
        $offset += 4;
        //點陣圖資料的大小
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['image_size'] = hexdec($tmp); //4 byte
        $offset += 4;
        //水平解析度（單位：像素/公尺）
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['h_resolution'] = hexdec($tmp); //4 byte
        $offset += 4;
        //垂直解析度（單位：像素/公尺）
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['v_resolution'] = hexdec($tmp); //4 byte
        $offset += 4;
        //點陣圖使用的調色盤顏色數
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['used_color'] = hexdec($tmp); //4 byte
        $offset += 4;
        //重要的顏色數
        $tmp = bin2hex(substr($blob, $offset+3, 1)).
               bin2hex(substr($blob, $offset+2, 1)).
               bin2hex(substr($blob, $offset+1, 1)).
               bin2hex(substr($blob, $offset, 1))
        ;
        $r['important_color'] = hexdec($tmp); //4 byte
        $offset += 4;

        //圖素資料(每ROW後有0d0a)
        $image_data = substr($blob, $r['image_data_offset']);

        $r['hex'] = $this->bitmap_array($image_data, $r['width'], $r['height']);

        return $r;
    }

    /**
     * bitmap format
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function bitmap_array($data, $width, $height)
    {
        $mm = floor((strlen($data) - $width * 3 * $height) / $height);

        $bmp_hex = array();
        $offset = 0;
        for ($i=0; $i<$height; $i++)
        {
            $len = $width*3 + $mm; //有冗資料
            $offset = $i * $len;
            $rlen = $len - $mm;
            // echo "$i $offset $len";
            $seg_data = substr($data, $offset, $len - $mm);

            // echo bin2hex($seg_data);
            $row_hex = array();
            for ($j=0; $j < $rlen; $j+=3)
            {
                $tmp = '';
                $dotR = (substr($seg_data, $j, 1));
                $tmp.= bin2hex($dotR);
                $dotG = (substr($seg_data, $j+1, 1));
                $tmp.= bin2hex($dotG);
                $dotB = (substr($seg_data, $j+2, 1));
                $tmp.= bin2hex($dotB);
                $row_hex[] = $tmp;
            }
            $bmp_hex[] = $row_hex;
            // echo br();
        }
        // exit;
        /**
         * bmp 的圖是列反
         */
        return array_reverse($bmp_hex);
    }

}

/* END */

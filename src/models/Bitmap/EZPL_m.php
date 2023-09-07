<?php

/**
 *
 */
class EZPL_m extends ERIS_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 16進位字串轉2進位
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function hex_string_2_bin($s)
    {
        $ns = "";
        foreach (str_split($s, 2) as $v)
        {
            $ns .= chr('0x'.$v);
        }
        return $ns;
    }

    /**
     * 2進位字串轉2進位
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function bin_string_2_bin($s)
    {
        return chr(base_convert($s, 2, 10));
        // $ns = "";
        // foreach (str_split($s, 8) as $v)
        // {
        //     $ns .= chr(base_convert($v, 2, 10));
        // }
        // return $ns;
    }

    /**
     * 轉成 ezpl 形式的 bitmap
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function ezpl_image_string_render($d)
    {
        $pad_right = 8 - $d['width']%8; //8個一組, 不足補0, 因為要將像素轉成byte用的

        // var_dump($d['hex']);

        $ezpl_img_string = ''; //
        foreach ($d['hex'] as $k1=>$v1)
        {
            $tmp = '';
            $row_byte = array();
            foreach ($v1 as $k2=>$v2)
            {
                $tmp .= $v2=='000000'?'1':'0'; //0x00 0x00 0x00 表示RGB都是0 => 黑色
                if (($k2+1)%8==0)
                {
                    $row_byte[] = $this->bin_string_2_bin($tmp);
                    $tmp = '';
                }
            }
            for ($i=0; $i<$pad_right; $i++)
            {
                $tmp.= '0';
            }
            $row_byte[] = $this->bin_string_2_bin($tmp);
            // echo sprintf('%02d', $k1).'=>';
            // echo bin2hex(implode('', $row_byte));
            // echo br();
            $ezpl_img_string .= implode('', $row_byte);
        }
        return $ezpl_img_string;
    }

    /**
     *
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function bin_string_2_bin2($s)
    {
        return sprintf('%03d', base_convert($s, 2, 10));
    }

    /**
     *
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function ezpl_image_string_render2($d)
    {
        $pad_right = 8 - $d['width']%8; //8個一組, 不足補0, 因為要將像素轉成byte用的

        $ezpl_img_string = ''; //
        foreach ($d['hex'] as $k1=>$v1)
        {
            $tmp = '';
            $row_byte = array();
            foreach ($v1 as $k2=>$v2)
            {
                $tmp .= $v2=='000000'?'1':'0'; //0x00 0x00 0x00 表示RGB都是0 => 黑色
                if (($k2+1)%8==0)
                {
                    $row_byte[] = $this->bin_string_2_bin2($tmp);
                    $tmp = '';
                }
            }
            for ($i=0; $i<$pad_right; $i++)
            {
                $tmp.= '0';
            }
            $row_byte[] = $this->bin_string_2_bin2($tmp);
            // echo sprintf('%02d', $k1).'=>';
            // echo bin2hex(implode('', $row_byte));
            // echo br();
            $ezpl_img_string .= implode('', $row_byte);
        }
        return trim($ezpl_img_string);
    }

}

/* END */

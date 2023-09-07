<?php

/**
 *
 */
class Wbmp_parser extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 解析 wbmp
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
        $offset = 0;
        $r['type'] = ord(substr($blob, $offset, 1));
        $offset++;
        $r['fixed_header'] = ord(substr($blob, $offset, 1));
        $offset++;
        $r['width'] = ord(substr($blob, $offset, 1));
        if ($r['width'] > 128)
        {
            $width1 = (ord(substr($blob, $offset, 1)) - 128) * 128;
            $offset++;
            $width2 = ord(substr($blob, $offset, 1));
            $r['width'] = $width1 + $width2;
        }
        $offset++;
        $r['height'] = ord(substr($blob, $offset, 1));
        if ($r['height'] > 128)
        {
            $height1 = (ord(substr($blob, $offset, 1)) - 128) * 128;
            $offset++;
            $height2 = ord(substr($blob, $offset, 1));
            $r['height'] = $height1 + $height2;
        }
        $offset++;
        $r['data'] = substr($blob, $offset);

        $r['hex'] = '';
        $r['bin'] = '';
        if ($r['data'])
        {
            $sr = $this->bitmap_format($r['data'], $r['height']);
            $r['hex'] = $sr['hex'];
            $r['bin'] = $sr['bin'];
        }

        return $r;
    }

    /**
     * bitmap format
     *
     * @return
     * @author e136 yuchen.tseng<yuchen.tseng@eris.com.tw>
     * @param
     */
    public function bitmap_format($data, $height)
    {
        $r = array('hex'=>'', 'bin'=>'');
        $n1 = strlen($data);
        $seg = $n1 / $height;

        $bmp_hex = array();
        $bmp_bin = array();
        for ($i=0; $i<=$n1; $i+=$seg)
        {
            $seg_data = substr($data, $i, $seg);
            $n2 = strlen($seg_data) - 1;
            $row_hex = array();
            $row_bin = array();
            for ($j=0; $j<$n2; $j++)
            {
                $row_hex[] = str_pad(dechex(255 - ord(substr($seg_data, $j, 1))), 2, "0", STR_PAD_LEFT);
                $row_bin[] = str_pad(decbin(255 - ord(substr($seg_data, $j, 1))), 8, "0", STR_PAD_LEFT);
            }
            $bmp_hex[] = implode('', $row_hex);
            $bmp_bin[] = implode('', $row_bin);
        }
        $r['hex'] = $bmp_hex;
        $r['bin'] = $bmp_bin;
        return $r;
    }

}

/* END */

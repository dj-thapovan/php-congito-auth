<?php

class Charset {

    public static function converticonv($data) {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $charset = utf8_encode($value);
                if (isset($_SERVER ["HTTP_USER_AGENT"])) {
                    if (strstr($_SERVER ["HTTP_USER_AGENT"], "Macintosh")) {
                        if ($charset != "UTF-8") {
                            $value = iconv('cp1252', 'utf-8', $value);
                        }
                    } else {
                        if ($charset != "UTF-8") {
                            $value = iconv('windows-1252', 'utf-8', $value);
                        }
                    }
                } else {
                    if ($charset != "UTF-8") {
                        $value = iconv('windows-1252', 'utf-8', $value);
                    }
                }
            }
        });
        return $data;
    }

    public function format_num($num, $precision = 1) {
        if ($num >= 1000 && $num < 1000000) {
            $n_format = number_format($num / 1000, $precision) . 'K';
        } else if ($num >= 1000000 && $num < 1000000000) {
            $n_format = number_format($num / 1000000, $precision) . 'M';
        } else if ($num >= 1000000000) {
            $n_format = number_format($num / 1000000000, $precision) . 'B';
        } else {
            $n_format = $num;
        }
        return $n_format;
    }

    public function utf8_rawurldecode($str) {
        $str = preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", rawurldecode($str));
        return html_entity_decode($str, null, 'UTF-8');
    }

}

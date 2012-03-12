<?php

// Copyright 2011 Tien Le, Ed Kaiser, Wu-chang Feng, Portland State University
// All Rights Reserved

   // Verify a submitted answer.
    function gb_Verify($Dc, $Nc, $A)
    {
        if ($Dc > 1)
        {
            $str = pack("N*", intval($Nc), intval($Dc), intval($A) );
            $output = sha1($str);//hash
            $output2 = hexdec(substr($output, -8));//hex to dec last 8 numbers
            $verify = bcmod((string)$output2, (string)$Dc);
            trigger_error("verified: " . $verify);
            return $verify === "0" ? true : false;//ok or not
        }
        return true;
    }

    function get_Nc($ip)
    {
        $K = 0x32425;// need to be random?
        $time = round(time()/86400);
        $str = pack("N*", intval($K), intval($time));// pack args(key&time) to binary
        $str .= $ip;//concatenate client IP
        $N1 = sha1($str);//hash
        $N2 = (int) hexdec(substr($N1, -8));//hex to dec last 8 numbers
        //printf("ip is %s, N1 is %s, N2 is %d, str is %s\n",$ip,$N1,$N2,$str);
        return $N2;//Nc
    }

    function checkBL($ip)
    {
        // List of DNSBL DNS Servers
        $dns_black_lists = array("zen.sspamhaus.org","bl.spamcop.net");//1st is dead

        $check = 0;
        $normalize = 3;

        // Reverse the IP
        $rev_ip = implode(array_reverse(explode('.', $ip)), '.');
        $response = array();
        foreach ($dns_black_lists as $dns_black_list) {
              $response = (gethostbynamel($rev_ip . '.' . $dns_black_list));//get the record from BL DNS
              if (!empty($response)) { //in the black list
                      $check = $check + 255;//marked down
              }
        }
        // Add special code for inifqrjlujoe.x.x.x.x.dnsbl.httpbl.org
        // [Access key][Octet-Reverse IP][List Specific Domain]
        $response = (gethostbynamel('inifqrjlujoe.' . $rev_ip . '.dnsbl.httpbl.org'));
        $pieces = explode(".",$response[0]);//words in string --> array elements
        // $pieces[1] = days since last malicious activity (unused)
        // $pieces[2] = threat score (255 = extremely threatening)
        // $pieces[3] = type of visitor (unused)
        $check = ($check + $pieces[2])/$normalize;
        return $check;
   }


    function generatePuzzle(&$Dc, &$Nc, $ip)
    {
        $score = 0.0;
        $check = 2; //checkBL($ip) + 1.0;
        // get difficulty level
        $Dc = $check*round(pow(0x80, $score)); //0x80 = hex of 128
        // Nc
        $Nc = get_Nc($ip);
    }
?>

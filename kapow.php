<?php

// Copyright 2011 Tien Le, Ed Kaiser, Wu-chang Feng, Portland State University
// All Rights Reserved

   // Verify a submitted answer.
    function gb_Verify($Dc, $Nc, $A)
    {
        if ($Dc > 1)
        {
            trigger_error("Nc: " . $Nc . ", Dc: " . $Dc . ", A: " . $A);
            $str = pack("N*", intval($Nc), intval($Dc), intval($A) );
            trigger_error("str: " . $str);
            $output = sha1($str);//hash
            trigger_error("output: " . $output);
            $output2 = hexdec(substr($output, -8));//hex to dec last 8 numbers
            trigger_error("output2: " . $output2);
            $verify = bcmod((string)$output2, (string)$Dc);
            trigger_error("verify: " . $verify);
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

   function spamScore($text) {
     $process = proc_open("perl -T /u/justinb/public_html/bin/spamassassin -x", 
     	          array(0 => array("pipe", "r"),
		        1 => array("pipe", "w"),
          	        2 => array("pipe", "w")), $pipes);

     fwrite($pipes[0], $text);
     fclose($pipes[0]);

     echo ("stdout: " . stream_get_contents($pipes[1]) . "<br>\n");
     echo ("stderr: " . stream_get_contents($pipes[2]) . "<br>\n");
     echo ("result: " . proc_close($process));

   }


    function generatePuzzle($score, &$Dc, &$Nc)
    {
      if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && eregi("^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$",$_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      else
        $ip = $_SERVER['REMOTE_ADDR'];

      // get difficulty level
      $Dc = round(pow(0x80, $score)); //0x80 = hex of 128
      // Nc
      $Nc = get_Nc($ip);
    }
?>

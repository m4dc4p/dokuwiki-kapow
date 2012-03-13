<?php
/**
 * All original code Copyright (C) Justin Bailey <jgbailey@gmail.com>.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * The names of its contributors may not be used to endorse or promote
      products derived from this software without specific prior
      written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';
require_once DOKU_PLUGIN.'kapow/kapow.php';

class action_plugin_kapow extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler &$controller) {
      $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'action_act_check');
      $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'add_kapow');
      $controller->register_hook('HTML_EDITFORM_OUTPUT', 'BEFORE', $this, 'add_pow');
      
    }

    private function getPuzzle(&$Dc, &$Nc) {
      if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && eregi("^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$",$_SERVER['HTTP_X_FORWARDED_FOR']))
      {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
      else
      {
        $ip = $_SERVER['REMOTE_ADDR'];
      }

      generatePuzzle($Dc, $Nc, $ip);
    }

    private function verifyPuzzle($Dc, $Nc, $answer) {
      return gb_Verify($Dc, $Nc, $answer);
    }

    public function add_pow(Doku_Event &$event, $param) {
      $this->getPuzzle($Dc, $Nc);
      $form = $event->data;
      $form->addHidden("kapow", "0");
      $form->addHidden("Dc", $Dc);
      $form->addHidden("Nc", $Nc);
      $form->addElement(<<<POW
<script type="text/javascript">
   var save = jQuery('input[name="do\[save\]"]').attr("disabled", "disabled");
   gb_Solve({ 
     Nc: {$Nc}, 
     Dc: {$Dc}, 
     onsolved: function (val) { 
       jQuery('input[name="kapow"]').val(val); 

       save.removeAttr("disabled"); }});
</script>
POW
);
    }

    public function add_kapow(Doku_Event &$event, $param) {
      // Adding a JavaScript File
      $event->data["script"][] = array ("type" => "text/javascript",
                                        "src" => DOKU_BASE . "lib/plugins/kapow/gb_solve.js",
                                        "_data" => "");
    }

    public function action_act_check(Doku_Event &$event, $param) {
      global $ACT;

      if((is_array($ACT) && array_key_exists("save", $ACT)) || $ACT == 'save') {
        trigger_error("array_key_exists('kapow', _REQUEST): ". array_key_exists('kapow', $_REQUEST));
        if(array_key_exists('kapow', $_REQUEST)) {
          if($this->verifyPuzzle($_REQUEST['Dc'], $_REQUEST['Nc'], $_REQUEST['kapow'])) {
            trigger_error("action_act_confirm: valid kapow.");
          }
          else {
            $ACT = 'spammer';
            $event->preventDefault();
            $event->stopPropagation();
            return false;
          }
        }
      }

      return true;
    }

}

// vim:ts=4:sw=4:et:

<?php
/**
 * DokuWiki Plugin kapow (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Justin <foo@bar.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_kapow extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler &$controller) {
      // $controller->register_hook('HTML_EDITFORM_OUTPUT', 'BEFORE', $this, 'handle_editform_output_before');
      $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'action_act_check');
      $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'render_kapow');
    }
    
    public function render_kapow(Doku_Event &$event, $param) {
      trigger_error("tpl_act_kapow: rendering kapow: " . print_r($_REQUEST['wikitext'], true));
      $wikitext = hsc($_REQUEST['wikitext']);
      echo <<<HTML
        Checking security ...<br><br>
        <form accept-charset="utf-8" action="" method="post" id="dw__editform">
        <input type="hidden" value="{$_REQUEST['sectok']}" name="sectok">
        <input type="hidden" value="{$_REQUEST['id']}" name="id">
        <input type="hidden" value="{$_REQUEST['rev']}" name="rev">
        <input type="hidden" value="{$_REQUEST['date']}" name="date">
        <input type="hidden" value="{$_REQUEST['prefix']}" name="prefix">
        <input type="hidden" value="{$_REQUEST['suffix']}" name="suffix">
        <input type="hidden" value="{$_REQUEST['changecheck']}" name="changecheck">
        <input type="hidden" value="{$_REQUEST['target']}" name="target">
        <textarea id="wiki__text" name="wikitext">{$wikitext}</textarea>
        <input type="hidden" value="2" name="kapow">
        <input type="submit" value="Save" name="do[save]">
        <input type="text" tabindex="2" size="50" class="edit" value="{$_REQUEST['summary']}" name="summary" id="edit__summary">
        </form>
HTML;
      $event->stopPropagation();
      $event->preventDefault();
      return false;
    }

    public function handle_editform_output_before(Doku_Event &$event, $param) {
      trigger_error("(before) Adding kapow form.");

      $form = $event->data;
      $form->addHidden("kapowBefore", "1");
    }

    public function action_act_check(Doku_Event &$event, $param) {
      global $ACT;

      trigger_error("action_act_check: ACT: '" . print_r($ACT, true) . "'.");
      trigger_error("action_act_check: SESSION: '" . print_r($_SESSION, true) . "'.");

      if((is_array($ACT) && array_key_exists("save", $ACT)) || $ACT == 'save') {
        if(array_key_exists('kapow', $_SESSION)) {
          if($_REQUEST['kapow'] == 2) {
            trigger_error("action_act_confirm: valid kapow.");
            unset($_SESSION['kapow']); 
          }
          else {
            trigger_error("action_act_confirm: kapow not valid.");
            unset($_SESSION['kapow']);
            send_redirect('', array('mode' => 'edit'));
            return false;
          }
        }
        else {
          $_SESSION['kapow'] = 1;
          trigger_error("action_act_check: SESSION: '" . print_r($_SESSION, true) . "'.");
          trigger_error("action_act_check: start kapow.");
          $event->preventDefault();
          $event->stopPropagation();
          $ACT = 'kapow';
          session_commit();
          return false;
        }
      }

      return true;
    }

}

// vim:ts=4:sw=4:et:

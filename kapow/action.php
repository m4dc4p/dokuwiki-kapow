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
       $controller->register_hook('HTML_EDITFORM_OUTPUT', 'BEFORE', $this, 'handle_editform_output_before');
    }

    public function handle_editform_output_before(Doku_Event &$event, $param) {
      trigger_error("(before) Adding kapow form.");

      $form = $event->data;
      $form->addHidden("kapowBefore", "1");
    }

}

// vim:ts=4:sw=4:et:

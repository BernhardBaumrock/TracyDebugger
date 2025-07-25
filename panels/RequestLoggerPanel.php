<?php
/**
 * Tracy Debugger requestLogger Panel
 *
 * @author Bernhard Baumrock, 24.11.2018
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */

use \Tracy\Dumper;
class RequestLoggerPanel extends BasePanel {

    // settings
    private $name = 'requestLogger';
    private $label = 'Request Logger';
    private $p;
    private $requestData = array();
    private $requestLoggerPages = array();

    // the svg icon shown in the bar and in the panel header
    private $icon = '';

    /**
     * define the tab for the panel in the debug bar
     */
    public function getTab() {
        if(\TracyDebugger::isAdditionalBar()) return;
        \Tracy\Debugger::timer($this->name);

        if(\TracyDebugger::getDataValue('referencePageEdited') && $this->wire('input')->get('id') && $this->wire('process') == 'ProcessPageEdit') {
            $this->p = $this->wire('process')->getPage();
            if($this->p instanceof NullPage) {
                $this->p = $this->wire('pages')->get((int) $this->wire('input')->get('id'));
            }
        }
        else {
            $this->p = $this->wire('page');
        }

        $this->requestData = $this->p->getRequestData('all', true); // true forces array
        $this->requestLoggerPages = \TracyDebugger::getDataValue('requestLoggerPages') ?: array();
        if(isset($this->requestLoggerPages) && in_array($this->p->id, $this->requestLoggerPages)) {
            $this->iconColor = \TracyDebugger::COLOR_WARN;
        }
        else {
            $this->iconColor = \TracyDebugger::COLOR_NORMAL;
        }

        $this->icon = '
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 28.477 28.477" style="enable-background:new 0 0 28.477 28.477;" xml:space="preserve">
            <path fill="'.$this->iconColor.'" d="M14.286,9.502c-1.557,0-2.824,1.263-2.824,2.823c0,0.799,0.338,1.519,0.872,2.032v14.12h4.001v-14.22   c0.479-0.505,0.775-1.183,0.775-1.932C17.11,10.766,15.847,9.502,14.286,9.502z"></path>
            <path fill="'.$this->iconColor.'" d="M14.238,0c-7.124,0-12.9,5.775-12.9,12.899c0,4.538,2.348,8.52,5.891,10.819c0,0,0.326,0.153,0.326-0.401   c0-0.556,0-1.582,0-1.774c0-0.194-0.326-0.397-0.326-0.397c-2.345-1.996-3.86-4.928-3.86-8.246c0-6.004,4.867-10.872,10.87-10.872   S25.111,6.896,25.111,12.9c0,3.229-1.438,6.1-3.673,8.092c0,0-0.324,0.287-0.324,0.516s0,1.393,0,1.788s0.324,0.31,0.324,0.31   c3.438-2.316,5.702-6.248,5.702-10.706C27.14,5.775,21.362,0,14.238,0z"></path>
            <path fill="'.$this->iconColor.'" d="M14.286,4.29c-4.49,0-8.132,3.641-8.132,8.132c0,3.078,1.711,5.752,4.231,7.134c0,0,0.417,0.204,0.417-0.295   s0-1.539,0-1.787c0-0.25-0.417-0.465-0.417-0.465c-1.305-1.11-2.149-2.742-2.149-4.587c0-3.342,2.708-6.05,6.05-6.05   c3.343,0,6.051,2.708,6.051,6.05c0,1.839-0.84,3.466-2.135,4.575c0,0-0.332,0.228-0.332,0.435c0,0.208,0,1.415,0,1.79   c0,0.373,0.332,0.327,0.332,0.327c2.514-1.384,4.217-4.055,4.217-7.126C22.419,7.931,18.778,4.29,14.286,4.29z"></path>
        </svg>
        ';

        return "<span title='{$this->label}'>{$this->icon}</span>" . (\TracyDebugger::getDataValue('showPanelLabels') ? $this->label : '') . (count($this->requestData) > 0 ? ' ' . count($this->requestData) : '');
    }

    /**
     * the panel's HTML code
     */
    public function getPanel() {

        $out = "<h1>{$this->icon} {$this->label}</h1>";
        $out .= '<span class="tracy-icons"><span class="resizeIcons"><a href="#" title="Maximize / Restore" onclick="tracyResizePanel(\'' . $this->className . '\')">⛶</a></span></span>';

        // panel body
        $out .= '<div class="tracy-inner">';
            $out .= $this->generateRequestDumps();
            $out .= \TracyDebugger::generatePanelFooter($this->name, \Tracy\Debugger::timer($this->name), strlen($out), 'requestLoggerPanel');
        $out .= '<br /></div>';

        return parent::loadResources() . $out;
    }

    /**
     * Generate Request Dumps
     *
     * @return string
     */
    private function generateRequestDumps() {

        $out = '<div class="tracy-DumpPanel">';

        $guest = $this->wire('users')->get('guest');
        $currentUser = $this->wire('users')->getCurrentUser();
        $this->wire('users')->setCurrentUser($guest);
        $publicEndpoint = true;

        if(count($this->requestData) > 0) {
            foreach($this->requestData as $datum) {
                $time = date("Y-m-d H:i:s", $datum['time']);
                $out .= "<h2 style='white-space: nowrap;'>{$datum['requestMethod']} @ $time | " . ($datum['remoteHost'] ?: $datum['remoteAddress']) . " | #{$datum['id']}</h2>";
                if(\TracyDebugger::getDataValue('requestLoggerReturnType') == 'object') $datum = json_decode(json_encode($datum), false);
                $out .= Dumper::toHtml($datum, array(Dumper::LIVE => true, Dumper::DEPTH => 9, Dumper::TRUNCATE => 99999, Dumper::COLLAPSE => true));
            }
        }
        elseif($this->p->viewable()) {
            $out .= '<p>There are no requests logged for this page.' . (!in_array($this->p->id, $this->requestLoggerPages) ? ' Logging is not currently enabled.' : '') . '</p>';
        }
        else {
            $publicEndpoint = false;
            $out .= '<p>You are not on a publicly available endpoint, so logging cannot be enabled for this page.</p>';
        }

        if($publicEndpoint) {
            $out .= '<p>';
            if(!in_array($this->p->id, $this->requestLoggerPages)) {
                $out .= '
                <form style="display:inline" method="post" action="'.\TracyDebugger::inputUrl(true).'">
                    <input type="hidden" name="requestLoggerLogPageId" value="'.$this->p->id.'" />
                    <input type="submit" name="tracyRequestLoggerEnableLogging" value="Enable logging on this page" />
                </form>
                ';
            }
            else {
                $out .= '
                <form style="display:inline" method="post" action="'.\TracyDebugger::inputUrl(true).'" onsubmit="return confirm(\'Do you really want to disable logging on this page and clear logged data?\');">
                    <input type="hidden" name="requestLoggerLogPageId" value="'.$this->p->id.'" />
                    <input type="submit" name="tracyRequestLoggerDisableLogging" value="Disable logging & clear data" />
                </form>
                ';
            }
            $out .= '</p>';
        }

        if(count($this->requestLoggerPages) > 0) {
            $out .= '
            <a href="#" rel="otherRequestLoggerPages" class="tracy-toggle tracy-collapsed">Pages with logging enabled</a>
            <div id="otherRequestLoggerPages" class="tracy-collapsed">
                <ul>';
                foreach($this->requestLoggerPages as $pid) {
                    $p = $this->wire('pages')->get($pid);
                    $out .= '<li><a href="'.$p->editUrl.'">'.$p->title.'</a></li>';
                }
            $out .= '
                </ul>
            </div>';
        }

        $this->wire('users')->setCurrentUser($currentUser);

        $out .= '</div>';

        return $out;
    }

}

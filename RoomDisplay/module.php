<?php

declare(strict_types=1);

// General functions
require_once __DIR__ . '/../libs/_traits.php';

/**
 * CLASS RoomDisplay
 */
class RoomDisplay extends IPSModule
{
    use DebugHelper;
    use FormatHelper;
    use ProfileHelper;
    use VariableHelper;
    use WebhookHelper;

    // Modul IDs
    private const GUID_MQTT_IO = '{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}';  // Splitter
    private const GUID_MQTT_TX = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';  // from module to server
    private const GUID_MQTT_RX = '{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}';  // from server to module

    // UI Objects
    private const UI_ARC = 1;
    private const UI_BAR = 2;
    private const UI_BUTTOM = 3;
    private const UI_CHECKBOX = 4;
    private const UI_COLOR = 5;
    private const UI_DROPDOWN = 6;
    private const UI_GAUGE = 7;
    private const UI_IMAGE = 8;
    private const UI_LABEL = 9;
    private const UI_LED = 10;
    private const UI_LINE = 11;
    private const UI_METER = 12;
    private const UI_MESSAGE = 13;
    private const UI_OBJECT = 14;
    private const UI_ROLLER = 15;
    private const UI_SLIDER = 16;
    private const UI_SPINNER = 17;
    private const UI_SWITCH = 18;
    private const UI_TABS = 19;
    private const UI_TOGGLE = 20;

    // Event handler
    private const EH_DOWN = 'down';         // Occurs when a button goes from depressed to pressed (the moment of touch)
    private const EH_UP = 'up';             // The button was released within a short time i.e. a short press has occurred
    private const EH_RELEASE = 'release';   // The button is released after being pressed for over the threshold time
    private const EH_LONG = 'long';         // Event is sent when the button is still being pressed after the threshold time of 400ms
    private const EH_HOLD = 'hold';         // The HOLD event is repeated every 200ms while the button is still pressed
    private const EH_CHANGED = 'changed';   // Event is sent when the value of the object has changed during the event

    // Placeholder
    private const PH_VALUE = '{{val}}';
    private const PH_TEXT = '{{txt}}';

    // Constants
    private const RD_HOST_NAME = 'plate';
    private const RD_PREFIX_TOPIC = 'hasp/';
    private const RD_PREFIX_HOOK = '/hook/plate';

    // Echo maps
    private const RD_STATUS_INFO = [
        ['node', 'Node', 3],
        ['idle', 'Idle', 3],
        ['version', 'Version', 3],
        ['uptime', 'Uptime', 1],
        ['ssid', 'WiFi', 3],
        ['rssi', 'RSSI', 1],
        ['ip', 'IP', 3],
        ['mac', 'MAC', 3],
        ['heapFree', 'Heap Free', 1],
        ['heapFrag', 'Heap Frag', 1],
        ['core', 'Core', 3],
        ['canUpdate', 'Updateable', 0],
        ['page', 'Page', 1],
        ['numPages', 'Pages', 1],
        ['tftDriver', 'TFT Driver', 3],
        ['tftWidth', 'TFT Width', 1],
        ['tftHeight', 'TFT Height', 1],
    ];
    private const RD_MOOD_LIGHT = [
        ['state', 'Status', 5],
        ['brightness', 'Brightness', 3],
        ['color', 'Color', 3],
        ['r', '(R)ed', 1],
        ['g', '(G)reen', 1],
        ['b', '(B)lue', 1],
    ];

    /**
     * Overrides the internal IPSModule::Create($id) function
     */
    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Device-Topic (Name)
        $this->RegisterPropertyString('Hostname', self::RD_HOST_NAME);
        $this->RegisterPropertyString('IP', '');
        // Design Objects
        $this->RegisterPropertyString('Objects', '[]');

        // Settings
        $this->RegisterPropertyBoolean('AutoDimBacklight', false);
        $this->RegisterPropertyBoolean('AutoShutdownBacklight', false);
        $this->RegisterPropertyBoolean('PageOneOnIdle', false);
        $this->RegisterPropertyInteger('ForwardMessageScript', 1);

        // Info Attributes
        $this->RegisterAttributeString('StatusUpdate', '');
        $this->RegisterAttributeString('MoodLight', '');

        // Automatically connect to the MQTT server/splitter instance
        $this->ConnectParent(self::GUID_MQTT_IO);
    }

    /**
     * Overrides the internal IPSModule::Destroy($id) function
     */
    public function Destroy()
    {
        // Unregister Hook
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterHook(self::RD_PREFIX_HOOK . $this->InstanceID);
        }
        // Never delete this line!
        parent::Destroy();
    }

    /**
     * Overrides the internal IPSModule::ApplyChanges($id) function
     */
    public function ApplyChanges()
    {
        // Never delete this line!
        parent::ApplyChanges();
        $mqttTopic = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/';
        $this->SetReceiveDataFilter('.*' . $mqttTopic . '.*');
        $this->SendDebug(__FUNCTION__, 'SetReceiveDataFilter(\'.*' . $mqttTopic . '.*\')', 0);

        // Webhook for backup
        $this->RegisterHook(self::RD_PREFIX_HOOK . $this->InstanceID);

        // Profile "WWXRD.Idle"
        $association = [
            [0, 'Off', '', -1],
            [1, 'Short', '', -1],
            [2, 'Long', '', -1],
        ];
        $this->RegisterProfileInteger('WWXRD.Idle', 'Hourglass', '', '', 0, 0, 0, $association);
        // Profile "WWXRD.Status"
        $association = [
            [0, 'Offline', 'display-slash', 0x00FF00],
            [1, 'Online', 'display', 0xFF0000],
        ];
        $this->RegisterProfileBoolean('WWXRD.Status', 'Display', '', '', $association);
        // Profile "WWXRD.Backlight"
        $this->RegisterProfileInteger('WWXRD.Backlight', 'Light', '', '', 1, 255, 1);
        // Profile "WWXRD.Page"
        $this->RegisterProfileInteger('WWXRD.Page', 'Book', '', '', 1, 12, 1);

        // Maintain variables
        $this->MaintainVariable('Idle', $this->Translate('Idle'), 1, 'WWXRD.Idle', 2, true);
        $this->MaintainVariable('Status', $this->Translate('Status'), 0, 'WWXRD.Status', 1, true);
        $this->MaintainVariable('Backlight', $this->Translate('Backlight'), 1, 'WWXRD.Backlight', 3, true);
        $this->MaintainVariable('Page', $this->Translate('Page'), 1, 'WWXRD.Page', 4, true);
        // Maintain actions
        $this->MaintainAction('Backlight', true);
        $this->MaintainAction('Page', true);

        // Validate object liste
        if ($this->RegisterObjects()) {
            $this->SetStatus(102);
        }
        else {
            $this->SetStatus(200);
        }
    }

    /**
     * Configuration Form.
     *
     * @return JSON configuration string.
     */
    public function GetConfigurationForm()
    {
        // Get form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        $ip = $this->ReadPropertyString('IP');
        // Buttons Backup & Status
        if ($ip != '') {
            $form['actions'][2]['items'][0]['items'][2]['enabled'] = true;
            $form['actions'][2]['items'][0]['items'][3]['enabled'] = true;
        }
        // return form
        return json_encode($form);
    }

    /**
     * RequestAction.
     *
     *  @param string $ident Ident.
     *  @param string $value Value.
     */
    public function RequestAction($ident, $value)
    {
        // Debug output
        $this->SendDebug(__FUNCTION__, $ident . ' => ' . $value);
        switch ($ident) {
            case 'Backlight':
                $this->SendCommand('backlight=' . $value);
                break;
            case 'Page':
                $this->SendCommand('page=' . $value);
                break;
            case 'PagePrev':
                $this->SendCommand('page=prev');
                break;
            case 'PageBack':
                $this->SendCommand('page=back');
                break;
            case 'PageNext':
                $this->SendCommand('page=next');
                break;
            case 'ReloadPages':
                $this->SendCommand('run /pages.jsonl');
                break;
            case 'ClearPages':
                $this->SendCommand('clearpage=all');
                break;
            case 'Restart':
                $this->SendCommand('restart');
                break;
            case 'ScreenShot':
                $this->SendCommand('screenshot');
                break;
            case 'MoodLight':
                $this->MoodLight($value);
                break;
            case 'StatusUpdate':
                $this->StatusUpdate($value);
                break;
            case 'Synchronize':
                $this->Synchronize();
                break;
        }
    }

    /**
     * ReceiveData
     *
     * @param string $json Data package in JSON format
     */
    public function ReceiveData($json)
    {
        $data = json_decode($json);

        $topic = $data->Topic;
        $payload = $data->Payload;
        $this->SendDebug(__FUNCTION__, 'Received Topic: ' . $topic . ' Payload: ' . $payload, 0);

        $prefix = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/LWT';
        // Check whether the topic begins with a specific prefix
        if (stripos($topic, $prefix) !== false) {
            $this->HandleData('LWT', $payload);
            return;
        }

        $prefix = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/state/';
        // Check whether the topic begins with a specific prefix
        if (stripos($topic, $prefix) === false) {
            $this->SendDebug(__FUNCTION__, 'Topic does not match', 0);
            return;
        }

        //Prefix des Topics abschneiden
        $topic = substr($topic, strlen($prefix));
        $this->SendDebug(__FUNCTION__, 'Topic: ' . $topic . ' Payload: ' . $payload, 0);
        $this->HandleData($topic, $payload);
    }

    /**
     * Internal SDK funktion.
     * data[0] = new value
     * data[1] = value changed?
     * data[2] = old value
     * data[3] = timestamp.
     */
    public function MessageSink($timeStamp, $sender, $message, $data)
    {
        $this->SendDebug(__FUNCTION__, 'SenderId: ' . $sender . ' Data: ' . $this->DebugPrint($data), 0);
        // Auf aktualisierungen reagieren.
        if ($message == VM_UPDATE) {
            $objects = json_decode($this->ReadPropertyString('Objects'), true);
            // iterate over all objects
            foreach ($objects as $item => $object) {
                if ($object['Link'] != $sender) {
                    continue;
                }
                $this->SendDebug(__FUNCTION__, $this->DebugPrint($object), 0);
                // process data to specific object
                $this->ProcessData($object, $data[0]);
            }
        }
    }

    /**
     * Send Command to display
     *
     * @param string $command command name/data
     */
    public function SendCommand(string $command)
    {
        $mqttTopic = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/command/';
        $this->SendDebug(__FUNCTION__, 'Topic: ' . $mqttTopic . ' Command: ' . $command, 0);
        $this->SendMQTT($mqttTopic, $command);
    }

    /**
     * Send JSON Lines to display
     *
     * @param array $data JSONL array
     */
    public function SendJSONL(array $data)
    {
        $this->SendCommand('jsonl ' . json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData()
    {
        $this->SendDebug(__FUNCTION__, $_GET);
        $file = isset($_GET['file']) ? $_GET['file'] : '';
        $filename = '';
        $contenttype = '';
        $ip = $this->ReadPropertyString('IP');
        // download the file
        if (empty($ip)) {
            $this->EchoMessage('No IP adress filed!');
            return;
        }
        switch ($file) {
            case 'pages':
                $filename = 'pages.jsonl';
                $contenttype = 'Content-Type: application/json; charset=utf-8';
                break;
            case 'screenshot':
                $filename = 'screenshot.bmp';
                $contenttype = 'Content-Type: image/bmp';
                break;
            default:
                return;
        }

        $url = 'http://' . $ip . '/' . $filename . '?download=true';
        $this->SendDebug(__FUNCTION__, $url);
        $download = file_get_contents($url);
        if ($download === false) {
            $this->EchoMessage('Error during download file!');
            return;
        }
        // output headers so that the file is downloaded rather than displayed
        header($contenttype);
        header('Content-Disposition: attachment; filename=' . $filename);
        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        // output line by line
        fwrite($output, $download);
    }

    protected function SendMQTT($topic, $payload)
    {
        $resultServer = true;
        $resultClient = true;
        //MQTT Server
        $server['DataID'] = self::GUID_MQTT_TX;
        $server['PacketType'] = 3;
        $server['QualityOfService'] = 0;
        $server['Retain'] = false;
        $server['Topic'] = $topic;
        $server['Payload'] = $payload;
        $json = json_encode($server, JSON_UNESCAPED_SLASHES);
        //$this->SendDebug(__FUNCTION__.'MQTT Server', $json, 0);
        $resultServer = @$this->SendDataToParent($json);

        return $resultServer === false;
    }

    private function SetItemProperty(int $page, int $objectId, string $property, string $value)
    {
        $this->SendCommand('p' . $page . 'b' . $objectId . '.' . $property . '=' . $value);
    }

    private function SetItemValue(int $page, int $objectId, int $value)
    {
        $this->SendCommand('p' . $page . 'b' . $objectId . '.val=' . $value);
    }

    private function SetItemText(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.text=' . $value . '"]');
    }

    private function SetItemValStr(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.value_str=' . $value . '"]');
    }

    private function SetItemSrc(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.src=' . $value . '"]');
    }

    private function RegisterObjects()
    {
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        if ($objects == null) {
            $objects = [];
        }
        //$this->SendDebug(__FUNCTION__, $this->DebugPrint($objects));

        // Unregister reference
        foreach ($this->GetReferenceList() as $id) {
            $this->UnregisterReference($id);
        }
        // Unregister all messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        $state = true;
        $count = 1;
        // Check verknüpftes Object
        foreach ($objects as $item => $object) {
            //$this->SendDebug(__FUNCTION__, $this->DebugPrint($object));
            if ($object['Link'] != 1) {
                // Objekt muss existiert!
                if (IPS_ObjectExists($object['Link'])) {
                    // Button  ==> Script, Variable
                    if ($object['Type'] == self::UI_BUTTOM) {
                        // TODO
                    }
                    // Toggle Button ==> Variable
                    if ($object['Type'] == self::UI_TOGGLE) {
                        if (IPS_GetObject($object['Link'])['ObjectType'] != 2) {
                            echo 'Fehler bei ausgewähltem Objekt ' . $count . ':' . PHP_EOL .
                                    'Objekt mit der ID: ' . $object['Link'] . ' ist kein Variable' . PHP_EOL .
                                    'Das Objekt für einen Toggle-Button muss vom Typ "Varaible" sein.' . PHP_EOL;
                            $state = false;
                        }
                    }
                    // Slider ==> Variable
                    if ($object['Type'] == self::UI_SLIDER) {
                        if (IPS_GetObject($object['Link'])['ObjectType'] != 2) {
                            echo 'Fehler bei ausgewähltem Objekt ' . $count . ':' . PHP_EOL .
                                    'Objekt mit der ID: ' . $object['Link'] . ' ist keine Variable' . PHP_EOL .
                                    'Das Objekt für einen Slider muss vom Typ "Varaible" sein.' . PHP_EOL;
                            $state = false;
                        }
                    }
                    // Dropdown ==> Variable
                    if ($object['Type'] == self::UI_DROPDOWN) {
                        if (IPS_GetObject($object['Link'])['ObjectType'] != 2) {
                            echo 'Fehler bei ausgewähltem Objekt ' . $count . ':' . PHP_EOL .
                                    'Objekt mit der ID: ' . $object['Link'] . ' ist keine Variable' . PHP_EOL .
                                    'Das Objekt für einen Dropdown muss vom Typ "Varaible" sein.' . PHP_EOL;
                            $state = false;
                        }
                    }
                    $this->RegisterReference($object['Link']);
                    $this->RegisterMessage($object['Link'], VM_UPDATE);
                }
                else {
                    echo 'Fehler bei ausgewähltem Objekt ' . $count . ':' . PHP_EOL .
                        'Das Objekt mit der ID: ' . $object['Link'] . ' existiert nicht!';
                    $state = false;
                }
            }
            $count++;
        }
        return $state;
    }

    /**
     * Process Data - map data to object
     *
     * @param mixed $object
     * @param mixed $data
     */
    private function ProcessData($object, $data)
    {
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data . ' (' . gettype($data) . ')');
        // calculate IPS value to object value
        $value = $this->EvaluateString($object['Calculation'], $data);
        // Debug
        $this->SendDebug(__FUNCTION__, $this->GetType($object['Type']) . ' :' . $this->SafePrint($value));
        // Arc || LineMeter
        if (($object['Type'] == self::UI_ARC) || ($object['Type'] == self::UI_METER)) {
            if ($object['Caption'] == '') {
                // If the caption is empty, the value is written directly.
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText(strval($value)));
            } else {
                $text = $this->EvaluateString($object['Caption'], $value);
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText(strval($text)));
            }
            if ($object['Value'] == '') {
                // If the caption is empty, the value is written directly.
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            } else {
                $value = $this->EvaluateString($object['Value'], $value);
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            }
        }
        // Dropdown || Gauge ||Switch
        if (($object['Type'] == self::UI_DROPDOWN) ||
            ($object['Type'] == self::UI_GAUGE) ||
            ($object['Type'] == self::UI_SWITCH)) {
            // write "val" property
            $this->SetItemValue($object['Page'], $object['Id'], intval($value));
        }
        // Image
        if ($object['Type'] == self::UI_IMAGE) {
            // write "src" property
            if ($object['Value'] != '') {
                $text = $this->EvaluateString($object['Value'], $value);
                $this->SetItemSrc($object['Page'], $object['Id'], $text);
            } else {
                $this->SetItemSrc($object['Page'], $object['Id'], $value);
            }
        }
        // Label
        if ($object['Type'] == self::UI_LABEL) {
            if ($object['Caption'] == '') {
                // If the caption is empty, the value is written directly.
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText(strval($value)));
            } else {
                $text = $this->EvaluateString($object['Caption'], $value);
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // Button
        if ($object['Type'] == self::UI_BUTTOM) {
            // Text for Button
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value);
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // Checkbox
        if ($object['Type'] == self::UI_CHECKBOX) {
            $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            // Text for Checkbox
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value);
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // Slider
        if ($object['Type'] == self::UI_SLIDER) {
            $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            // Text for Slider
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value);
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // Toggle-Button
        if ($object['Type'] == self::UI_TOGGLE) {
            $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            // Toogle Text for Button
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value);
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }

        // LED Indicator
        if ($object['Type'] == self::UI_LED) {
            $var = IPS_GetVariable($sender);
            // bool variable ?
            if ($var['VariableType'] == 0) {
                // LEDInidactor on/off
                if ($value) {
                    $this->SetItemValue($object['Page'], $object['Id'], 255);
                } else {
                    $this->SetItemValue($object['Page'], $object['Id'], 0);
                }
            } else {
                // LEDInidactor value
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            }
        }
        // Object
        if ($object['Type'] == self::UI_OBJECT) {
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value);
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText($text));
            }
            if ($object['Value'] != '') {
                $text = $this->EvaluateString($object['Value'], $value);
                $this->SetItemProperty($object['Page'], $object['Id'], 'bg_color', $text);
            }
        }
        // Roller
        if ($object['Type'] == self::UI_ROLLER) {
            if ($object['Value'] != '') {
                $value = $this->EvaluateString($object['Value'], $value);
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            }
        }
    }

    private function HandleData(string $topic, string $data)
    {
        $this->SendDebug(__FUNCTION__, 'Topic: ' . $topic . ' ,Payload: ' . $data);
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        // Is idle?
        if ($topic == 'idle') {
            switch ($data) {
                case 'short':
                    $this->SetValue('Idle', 1);
                    break;
                case 'long':
                    $this->SetValue('Idle', 2);
                    break;
                default:
                    $this->SetValue('Idle', 0);
            }
            if ($this->ReadPropertyBoolean('AutoDimBacklight')) {
                switch ($data) {
                    case 'short':
                        $this->SendCommand('backlight=50');
                        break;
                    case 'long':
                        break;
                    default:
                        $this->SendCommand('backlight=255');
                }
            }
            if ($this->ReadPropertyBoolean('AutoShutdownBacklight')) {
                if ($data == 'long') {
                    $this->SendCommand('backlight=0');
                }
            }
            if ($this->ReadPropertyBoolean('PageOneOnIdle') && $data == 'short') {
                $this->SendCommand('page 1');
            }
        }

        // Is backlight?
        if ($topic == 'backlight') {
            $data = json_decode($data);
            $this->SetValue('Backlight', $data->brightness);
        }

        // Is page changed?
        if ($topic == 'page') {
            $this->SetValue('Page', $data);
        }

        // Is object event?
        $match = preg_match('/p(\d{1,2})b(\d{1,3})/', $topic, $matches);
        if ($match) {
            $index = -1;
            // Find the object
            foreach ($objects as $item => $object) {
                if ($object['Page'] == $matches[1] && $object['Id'] == $matches[2]) {
                    $index = $item;
                    break;
                }
            }
            if ($index < 0) {
                $this->SendDebug(__FUNCTION__, 'No registered object!', 0);
                return;
            }
            $object = $objects[$index];
            $data = json_decode($data);
            if (property_exists($data, 'event') && ($object['Link'] != 1)) {
                // Save the infos
                $text = '';
                if (property_exists($data, 'text')) {
                    $text = $data->text;
                }
                $value = -1;
                if (property_exists($data, 'val')) {
                    $value = $data->val;
                }
                // Recalculation necessary?
                if ($object['Recalculation'] != '') {
                    $value = $this->EvaluateString($object['Recalculation'], $value, $text);
                }
                // Type & Value & Text
                $this->SendDebug(__FUNCTION__, $this->GetType($object['Type']) . ': ' . $this->SafePrint($value) . ', ' . $text, 0);
                // Button down || Dropdown changed || Toggle Button, Roller, Slider or Switch up
                if (($object['Type'] == self::UI_BUTTOM && $data->event == self::EH_DOWN) ||
                    ($object['Type'] == self::UI_CHECKBOX && $data->event == self::EH_UP) ||
                    ($object['Type'] == self::UI_DROPDOWN && $data->event == self::EH_CHANGED) ||
                    ($object['Type'] == self::UI_TOGGLE && $data->event == self::EH_UP) ||
                    ($object['Type'] == self::UI_ROLLER && $data->event == self::EH_CHANGED) ||
                    ($object['Type'] == self::UI_SLIDER && $data->event == self::EH_UP) ||
                    ($object['Type'] == self::UI_SWITCH && $data->event == self::EH_UP)) {
                    if (IPS_GetObject($object['Link'])['ObjectType'] == 3) {
                        IPS_RunScriptEx($object['Link'], ['VALUE' => $value, 'TEXT' => $text]);
                        $this->SendDebug(__FUNCTION__, 'IPS_RunScriptEx(' . $object['Link'] . ', [VALUE=>' . $value . ',TEXT=>' . $text . '])', 0);
                    }
                    else {
                        if (HasAction($object['Link']) && $value != -1) {
                            RequestAction($object['Link'], $value);
                            $this->SendDebug(__FUNCTION__, 'RequestAction(' . $object['Link'] . ', ' . $value . ')', 0);
                        }
                        elseif ($value != -1) {
                            SetValue($object['Link'], $value);
                            $this->SendDebug(__FUNCTION__, 'SetValue(' . $object['Link'] . ', ' . $value . ')', 0);
                        }
                        else {
                            $this->SendDebug(__FUNCTION__, 'No return to object: ' . $object['Link'], 0);
                        }
                    }
                }
            }

            if (property_exists($data, 'val') && ($object['Link'] != 1)) {
                // Received Typ = Arc & Value
                if ($object['Type'] == self::UI_ARC) {
                    if (HasAction($object['Link']) && $value != -1) {
                        RequestAction($object['Link'], $data->val);
                        $this->SendDebug(__FUNCTION__, 'RequestAction():' . $object['Link'] . ' Value: ' . $data->val, 0);
                    }
                    elseif ($value != -1) {
                        SetValue($object['Link'], $value);
                        $this->SendDebug(__FUNCTION__, 'SetValue(' . $object['Link'] . ', ' . $value . ')', 0);
                    }
                    else {
                        $this->SendDebug(__FUNCTION__, 'No return toobject: ' . $object['Link'], 0);
                    }
                }
            }
            $scriptid = $this->ReadPropertyInteger('ForwardMessageScript');
            if ($scriptid != 1) {
                IPS_RunScriptEx($scriptid, ['Data' => json_encode(['Topic' => $topic, 'Data' => $data])]);
            }
        }

        if ($topic == 'statusupdate') {
            $this->WriteAttributeString('StatusUpdate', $data);
            $this->SendDebug(__FUNCTION__, 'Status: ' . $data);
        }

        if ($topic == 'moodlight') {
            $this->WriteAttributeString('MoodLight', $data);
            $this->SendDebug(__FUNCTION__, 'Moodlight: ' . $data);
        }

        // Last Will and Testament (LWT)?
        if ($topic == 'LWT') {
            switch ($data) {
                case 'online':
                    $this->SetValueBoolean('Status', true);
                    $this->Online();
                    break;
                default:
                    $this->SetValueBoolean('Status', false);
            }
        }
    }

    /**
     * Online State (LWT)
     *
     */
    private function Online()
    {
        $this->SendDebug(__FUNCTION__, 'Display is online', 0);
        // Sync linked objects with the device objects
        $this->Synchronize();
    }

    /**
     * Status Update - display status information.
     *
     */
    private function StatusUpdate($value)
    {
        if ($value) {
            $this->SendCommand('statusupdate');
        }
        else {
            $info = $this->ReadAttributeString('StatusUpdate');
            $this->EchoMessage($this->PrettyPrint(self::RD_STATUS_INFO, $info));
        }
    }

    /**
     * Mood Light - display moodlight information.
     *
     */
    private function MoodLight($value)
    {
        if ($value) {
            $this->SendCommand('moodlight');
        }
        else {
            $info = $this->ReadAttributeString('MoodLight');
            $this->EchoMessage($this->PrettyPrint(self::RD_MOOD_LIGHT, $info));
        }
    }

    /**
     * Synchronize - from IPS variables to design objects.
     *
     */
    private function Synchronize()
    {
        $this->SendDebug(__FUNCTION__, 'Synchronize', 0);
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        // iterate over all objects
        foreach ($objects as $item => $object) {
            if ($object['Link'] == 1 || $object['Calculation'] == -1) {
                continue;
            }
            // get actual value
            $value = GetValue($object['Link']);
            // process data to specific object
            $this->ProcessData($object, $value);
        }
    }

    /**
     * Evaluate String
     *
     * @param mixed $subject Expression text
     * @param mixed $value Value == {{val}}
     * @param mixed $text Text == {{txt}}
     */
    private function EvaluateString($subject, $value, $text = '')
    {
        // sprintf
        if ((strlen($subject) != 0) && (strpos($subject, '{{') === false)) {
            // sprintf: %s for string, %d for integer %f for float, %% to write a “%”
            $ret = sprintf($subject, $value);
            $this->SendDebug(__FUNCTION__, 'sprintf: ' . $ret . ' <= ' . $subject);
            return $ret;
        }
        // bool to string is bad (empty for false)
        if (is_bool($value)) {
            $value = intval($value);
        }
        // eval - empty(0) is true :(
        if (strlen($subject) != 0) {
            $eval = str_replace(self::PH_VALUE, strval($value), $subject);
            $eval = str_replace(self::PH_TEXT, strval($text), $eval);
            $eval = 'return (' . $eval . ');';
            $this->SendDebug(__FUNCTION__, 'eval: ' . $eval);
            try {
                $code = eval($eval);
                if ($code === false) {
                    $code = '';
                }
            } catch (ParseError $e) {
                // Report error somehow
                $this->SendDebug(__FUNCTION__, 'RD Value: ' . $value . ',RD Type: ' . gettype($value) . ',RD Error' . $e->GetMessage() . ',RD Eval' . $eval . ',RD Subject: ' . $subject);
                $code = '';
            }
            return $code;
        } else {
            return $value;
        }
    }

    /**
     * Encode Text
     *
     * @param mixed $text Text to convert in json format
     */
    private function EncodeText($text)
    {
        // JSON encode converts special characters into Unicode sequences
        $encoded = json_encode($text);
        // Remove the enclosing quotation marks that are added by json_encode
        $encoded = substr($encoded, 1, -1);
        // EReplace double backslashes with single backslashes
        $encoded = str_replace('\\\\', '\\', $encoded);
        return $encoded;
    }

    /**
     * Retrieve UI object type as textual representation.
     *
     * @param mixed $type ID of the UI Object
     */
    private function GetType($type)
    {
        $name = $this->Translate('Unknown');
        switch ($type) {
            case self::UI_ARC: $name = 'Arc';
                break;
            case self::UI_BAR: $name = 'Bar';
                break;
            case self::UI_BUTTOM: $name = 'Button';
                break;
            case self::UI_CHECKBOX: $name = 'Checkbox';
                break;
            case self::UI_COLOR: $name = 'Color Picker';
                break;
            case self::UI_DROPDOWN: $name = 'Dropdown List';
                break;
            case self::UI_GAUGE: $name = 'Gauge';
                break;
            case self::UI_IMAGE: $name = 'Image';
                break;
            case self::UI_LABEL: $name = 'Label';
                break;
            case self::UI_LED: $name = 'LED Indicator';
                break;
            case self::UI_LINE: $name = 'Line';
                break;
            case self::UI_METER: $name = 'Line Meter';
                break;
            case self::UI_MESSAGE: $name = 'Messagebox';
                break;
            case self::UI_OBJECT: $name = 'Object';
                break;
            case self::UI_ROLLER: $name = 'Roller';
                break;
            case self::UI_SLIDER: $name = 'Slider';
                break;
            case self::UI_SPINNER: $name = 'Spinner';
                break;
            case self::UI_SWITCH: $name = 'Switch';
                break;
            case self::UI_TABS: $name = 'Tabs';
                break;
            case self::UI_TOGGLE: $name = 'Toggle Button';
                break;
        }
        return $name;
    }

    /**
     * Show message via popup
     *
     * @param string $caption echo message
     */
    private function EchoMessage(string $caption)
    {
        $this->UpdateFormField('EchoMessage', 'caption', $this->Translate($caption));
        $this->UpdateFormField('EchoPopup', 'visible', true);
    }
}

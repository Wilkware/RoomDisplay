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

    // Min IPS Object ID
    private const IPS_MIN_ID = 10000;

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
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     */
    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Device-Topic (Name)
        $this->RegisterPropertyString('Hostname', self::RD_HOST_NAME);
        $this->RegisterPropertyString('IP', '');
        // Page Layout
        $this->RegisterPropertyString('Layout', '');
        // Design Objects
        $this->RegisterPropertyString('Objects', '[]');

        // Settings
        $this->RegisterPropertyBoolean('AutoDimBacklight', false);
        $this->RegisterPropertyInteger('AutoOffIdle', 255);
        $this->RegisterPropertyInteger('AutoShortIdle', 50);
        $this->RegisterPropertyInteger('AutoLongIdle', 0);
        $this->RegisterPropertyBoolean('AutoShutdownBacklight', false);
        $this->RegisterPropertyInteger('AutoAntiburnCycle', 60);
        $this->RegisterPropertyInteger('AutoAntiburnBacklight', 0);
        $this->RegisterPropertyBoolean('PageOneOnIdle', false);
        $this->RegisterPropertyBoolean('SyncOnIdle', false);
        $this->RegisterPropertyInteger('ForwardMessageScript', 1);

        // Info Attributes
        $this->RegisterAttributeString('StatusUpdate', '');
        $this->RegisterAttributeString('MoodLight', '');
        // Idle Attribute
        $this->RegisterAttributeBoolean('SyncData', true);
        $this->RegisterAttributeBoolean('DisableIdle', false);

        // Register Timer
        $this->RegisterTimer('AntiburnTimer', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "Antiburn", true);');
        $this->RegisterTimer('AntiburnLight', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "Antiburn", false);');

        // Automatically connect to the MQTT server/splitter instance
        $this->ConnectParent(self::GUID_MQTT_IO);
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting IP-Symcon.
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
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
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

        // Reset Timer
        $this->SetTimerInterval('AntiburnTimer', 0);

        // Validate object liste
        if ($this->RegisterObjects()) {
            $this->SetStatus(102);
        }
        else {
            $this->SetStatus(201);
        }
    }

    /**
     * The content can be overwritten in order to transfer a self-created configuration page.
     * This way, content can be generated dynamically.
     * In this case, the "form.json" on the file system is completely ignored.
     *
     * @return JSON Content of the configuration page
     */
    public function GetConfigurationForm()
    {
        // Get form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        $ip = $this->ReadPropertyString('IP');
        // Layout Buttons & Status Buttons
        if ($ip != '') {
            $form['elements'][3]['items'][1]['items'][0]['enabled'] = true;
            $form['elements'][3]['items'][1]['items'][1]['enabled'] = true;
            $form['elements'][3]['items'][1]['items'][2]['enabled'] = true;
            $form['elements'][3]['items'][1]['items'][3]['enabled'] = true;
            $form['actions'][2]['items'][0]['items'][2]['enabled'] = true;
        }
        // return form
        return json_encode($form);
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     *  @param string $ident Ident of the variable
     *  @param string $value The value to be set
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
            case 'Antiburn':
                $this->Antiburn($value);
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
            case 'LayoutLoad':
                $this->UpdateLayout($value, false);
                break;
            case 'LayoutSave':
                $this->UpdateLayout($value, true);
                break;
            case 'LayoutCheck':
                $this->ValidateLayout($value, true);
                break;
            case 'MappingCopy':
                $this->UpdateMapping($value, true);
                break;
            case 'MappingSort':
                $this->UpdateMapping($value, false);
                break;
            case 'MappingTest':
                $this->CheckMapping($value);
                break;
        }
    }

    /**
     * This function is called by IP-Symcon and processes sent data and, if necessary, forwards it to all child instances.
     *
     * @param string $json Data package in JSON format
     */
    public function ReceiveData($json)
    {
        $data = json_decode($json);

        $topic = $data->Topic;
        $payload = $data->Payload;
        $this->SendDebug(__FUNCTION__, 'Received Topic: ' . $topic . ' Payload: ' . $payload, 0);
        // Check whether the topic begins with a specific prefix
        $prefix = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/LWT';
        if (stripos($topic, $prefix) !== false) {
            $this->HandleData('LWT', $payload);
            return;
        }
        // Check whether the topic begins with a specific prefix
        $prefix = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/state/';
        if (stripos($topic, $prefix) === false) {
            $this->SendDebug(__FUNCTION__, 'Topic does not match', 0);
            return;
        }
        // Truncate prefix of the topic
        $topic = substr($topic, strlen($prefix));
        $this->HandleData($topic, $payload);
    }

    /**
     * The content of the function can be overwritten in order to carry out own reactions to certain messages.
     * The function is only called for registered MessageIDs/SenderIDs combinations.
     *
     * data[0] = new value
     * data[1] = value changed?
     * data[2] = old value
     * data[3] = timestamp.
     *
     * @param mixed $timestamp Continuous counter timestamp
     * @param mixed $sender Sender ID
     * @param mixed $message ID of the message
     * @param mixed $data Data of the message
     */
    public function MessageSink($timestamp, $sender, $message, $data)
    {
        $this->SendDebug(__FUNCTION__, 'SenderId: ' . $sender . ' Data: ' . $this->DebugPrint($data), 0);
        // React to updates
        if ($message == VM_UPDATE) {
            if ($this->ReadAttributeBoolean('SyncData')) {
                $objects = json_decode($this->ReadPropertyString('Objects'), true);
                // Iterate over all objects
                foreach ($objects as $item => $object) {
                    if ($object['Link'] != $sender) {
                        continue;
                    }
                    $this->SendDebug(__FUNCTION__, $this->DebugPrint($object), 0);
                    // Process data to specific object
                    $this->ProcessData($object, $data[0]);
                }
            }
        }
    }

    /**
     * Send Command to display.
     *
     * @param string $command Command name/data
     */
    public function SendCommand(string $command)
    {
        $mqttTopic = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/command/';
        $this->SendDebug(__FUNCTION__, 'Topic: ' . $mqttTopic . ' Command: ' . $command, 0);
        $this->SendMQTT($mqttTopic, $command);
    }

    /**
     * Send JSON Lines to display.
     *
     * @param array $data JSONL array
     */
    public function SendJSONL(array $data)
    {
        $this->SendCommand('jsonl ' . json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Send JSON Lines to display.
     *
     * @param array $data JSONL array
     */
    public function DisableIdle(bool $disable)
    {
        $this->WriteAttributeBoolean('DisableIdle', $disable);
        $this->ProcessIdle();
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     *
     */
    protected function ProcessHookData()
    {
        $this->SendDebug(__FUNCTION__, $_GET);
        $file = isset($_GET['file']) ? $_GET['file'] : '';
        $filename = '';
        $contenttype = '';
        $ip = $this->ReadPropertyString('IP');
        // Download the file
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
                $this->SendCommand('screenshot');
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
        // Output headers so that the file is downloaded rather than displayed
        header($contenttype);
        header('Content-Disposition: attachment; filename=' . $filename);
        // Create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        // Output line by line
        fwrite($output, $download);
    }

    /**
     * Check whether idle process is allowed.
     *
     */
    protected function ProcessIdle()
    {
        $disable = $this->ReadAttributeBoolean('DisableIdle');
        if ($disable) {
            $this->SendCommand('idle off');
        }
        return $disable;
    }

    /**
     * Send command to MQTT server.
     *
     * @param mixed $topic Topic name
     * @param mixed $payload Payload data
     */
    protected function SendMQTT(string $topic, string $payload)
    {
        $resultServer = true;
        $resultClient = true;
        // MQTT Server
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

    /**
     * Set a specific item property.
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param string $property Property name
     * @param string $value Property value
     */
    private function SetItemProperty(int $page, int $objectId, string $property, string $value)
    {
        $this->SendCommand('p' . $page . 'b' . $objectId . '.' . $property . '=' . $value);
    }

    /**
     * Set item value (numeric).
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param int $value Property Value
     */
    private function SetItemValue(int $page, int $objectId, int $value)
    {
        $this->SendCommand('p' . $page . 'b' . $objectId . '.val=' . $value);
    }

    /**
     * Set item text (label, caption).
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param string $value Property Value
     */
    private function SetItemText(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.text=' . $value . '"]');
    }

    /**
     * Set item value string.
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param string $value Property Value
     */
    private function SetItemValStr(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.value_str=' . $value . '"]');
    }

    /**
     * Set item src (image).
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param string $value Property Value
     */
    private function SetItemSrc(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.src=' . $value . '"]');
    }

    /**
     * Set display backlight via staet and brightness.
     *
     * @param string $data idle state (short, long or off)
     */
    private function SetBacklight(string $data)
    {
        $state = 'on';
        $brightness = $this->ReadPropertyInteger('Auto' . ucfirst($data) . 'Idle');
        // adjust state & brigthness
        if ($brightness == 0) {
            $state = 'off';
        }
        $this->SendCommand('backlight {"state":"' . $state . '","brightness":' . $brightness . '}');
    }

    /**
     * Check all register objects.
     *
     */
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
        // Check linked object
        foreach ($objects as $item => $object) {
            //$this->SendDebug(__FUNCTION__, $this->DebugPrint($object));
            if ($object['Link'] != 1) {
                // Objekt muss existiert!
                if (IPS_ObjectExists($object['Link'])) {
                    $type = IPS_GetObject($object['Link'])['ObjectType'];
                    // only 2(Variable) and 3(Script)
                    if ($type == 2) {
                        // Variables is supported for everyone
                    } elseif ($type == 3) {
                        if (($object['Type'] == self::UI_BUTTOM) ||
                            ($object['Type'] == self::UI_CHECKBOX) ||
                            ($object['Type'] == self::UI_DROPDOWN) ||
                            ($object['Type'] == self::UI_TOGGLE) ||
                            ($object['Type'] == self::UI_ROLLER) ||
                            ($object['Type'] == self::UI_SLIDER) ||
                            ($object['Type'] == self::UI_SWITCH)) {
                            // Scripts is supported for these types
                        } else {
                            $msg = $this->Translate('The assigned object #%d for page %d with id %d is not supported!');
                            $msg = sprintf($msg, $object['Link'], $object['Page'], $object['Id']);
                            $this->LogMessage($msg, KL_WARNING);
                            $state = false;
                        }
                    }
                    $this->RegisterReference($object['Link']);
                    $this->RegisterMessage($object['Link'], VM_UPDATE);
                }
                else {
                    $msg = $this->Translate('The assigned object #%d for page %d with id %d does not exist!');
                    $msg = sprintf($msg, $object['Link'], $object['Page'], $object['Id']);
                    $this->LogMessage($msg, KL_WARNING);
                    $state = false;
                }
            }
        }
        return $state;
    }

    /**
     * Process map data to object.
     *
     * @param array $object The mapping object
     * @param mixed $data The passed data
     */
    private function ProcessData(array $object, mixed $data)
    {
        $this->SendDebug(__FUNCTION__, 'Data: ' . $data . ' (' . gettype($data) . ')');
        // Calculate IPS value to object value
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
            // Write "val" property
            if ($object['Value'] != '') {
                $value = $this->EvaluateString($object['Value'], $value);
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            } else {
                // If the caption is empty, the value is written directly.
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            }
        }
        // Image
        if ($object['Type'] == self::UI_IMAGE) {
            // Write "src" property
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
        // Spinner
        if ($object['Type'] == self::UI_SPINNER) {
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value);
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText($text));
            }
            if ($object['Value'] != '') {
                $value = intval($this->EvaluateString($object['Value'], $value));
                // set direction (-/+)  0 = clockwise, 1 = counter-clockwise
                $this->SetItemProperty($object['Page'], $object['Id'], 'direction', strval(($value < 0 ? 1 : 0)));
                // set speed, always positiv
                $this->SetItemProperty($object['Page'], $object['Id'], 'speed', strval(abs($value)));
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

    /**
     * Handle received data to object
     *
     * @param string $topic Topic ID
     * @param string $data Payload data
     */
    private function HandleData(string $topic, string $data)
    {
        $this->SendDebug(__FUNCTION__, 'Topic: ' . $topic . ' ,Payload: ' . $data);
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        // Is idle?
        if ($topic == 'idle') {
            switch ($data) {
                case 'short':
                    if ($this->ProcessIdle()) {
                        return;
                    }
                    $this->SetValue('Idle', 1);
                    break;
                case 'long':
                    $this->SetValue('Idle', 2);
                    break;
                default: // off
                    $this->SetValue('Idle', 0);
                    $this->SetTimerInterval('AntiburnTimer', 0);
                    if (!$this->ReadAttributeBoolean('SyncData')) {
                        $this->SendDebug(__FUNCTION__, 'Synchronize()');
                        $this->Synchronize();
                    }
                    $this->WriteAttributeBoolean('SyncData', true);
            }
            if ($this->ReadPropertyBoolean('AutoDimBacklight')) {
                $this->SendDebug(__FUNCTION__, 'SetBacklight($data)');
                $this->SetBacklight($data);
            }
            if ($this->ReadPropertyBoolean('AutoShutdownBacklight') && $data == 'long') {
                $this->SetTimerInterval('AntiburnTimer', 60 * 1000 * $this->ReadPropertyInteger('AutoAntiburnCycle'));
            }
            if ($this->ReadPropertyBoolean('PageOneOnIdle') && $data == 'long') {
                $this->SendCommand('page 1');
            }
            if ($this->ReadPropertyBoolean('SyncOnIdle') && $data == 'long') {
                $this->WriteAttributeBoolean('SyncData', false);
            }
        }

        // Is backlight?
        if ($topic == 'backlight') {
            $data = json_decode($data);
            $brightness = $data->brightness;
            if (isset($data->state) && $data->state == 'off') {
                $brightness = 0;
            }
            $this->SetValue('Backlight', $brightness);
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
            //$this->StatusUpdate(false);
        }

        if ($topic == 'moodlight') {
            $this->WriteAttributeString('MoodLight', $data);
            $this->SendDebug(__FUNCTION__, 'Moodlight: ' . $data);
            //$this->MoodLight(false);
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
     * Switch antiburn on or off.
     *
     * @param bool $value True for on, otherwise false
     */
    private function Antiburn(bool $value)
    {
        // Backlights
        $long = $this->ReadPropertyInteger('AutoLongIdle');
        $anti = $this->ReadPropertyInteger('AutoAntiburnBacklight');

        if ($value) {
            $this->SendDebug(__FUNCTION__, 'Antiburn ON', 0);
            if (($anti < $long) && ($anti != 0)) {
                $this->SendCommand('backlight=' . $anti);
                $this->SetTimerInterval('AntiburnLight', 35 * 1000);
            }
            $this->SendCommand('antiburn=on');
        } else {
            $this->SendDebug(__FUNCTION__, 'Antiburn OFF', 0);
            $this->SetTimerInterval('AntiburnLight', 0);
            if (($anti < $long) && ($anti != 0)) {
                $this->SendCommand('backlight=' . $long);
            }
        }
    }

    /**
     * Online State (LWT).
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
     * @param bool $value True to show status info, otherwise false
     */
    private function StatusUpdate(bool $value)
    {
        $this->SendCommand('statusupdate');
        if ($value) {
            $info = $this->ReadAttributeString('StatusUpdate');
            $this->EchoMessage($this->PrettyPrint(self::RD_STATUS_INFO, $info));
        }
    }

    /**
     * Mood Light - display moodlight information.
     *
     * @param bool $value True to show moodlight info, otherwise false
     */
    private function MoodLight(bool $value)
    {
        $this->SendCommand('moodlight');
        if ($value) {
            $info = $this->ReadAttributeString('MoodLight');
            $this->EchoMessage($this->PrettyPrint(self::RD_MOOD_LIGHT, $info));
        }
    }

    /**
     * Synchronize from IPS variables to design objects.
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
            if (IPS_ObjectExists($object['Link']) && (IPS_GetObject($object['Link'])['ObjectType'] == 2)) {
                // get actual value
                $value = GetValue($object['Link']);
                // process data to specific object
                $this->ProcessData($object, $value);
            }
            else {
                $this->LogMessage('Linked object with #' . $object['Link'] . ' dosent exist!', KL_ERROR);
            }
        }
    }

    /**
     * Load or save the content of page layout file (pages.jsonl).
     *
     * @param string $value Layout as JSONL
     * @param bool $save If true upload, otherwise download from device
     */
    private function UpdateLayout(string $value, bool $save)
    {
        $ip = $this->ReadPropertyString('IP');
        // check ip
        if (empty($ip)) {
            $this->EchoMessage('No IP adress filed!');
            return;
        }
        // save or load
        if ($save) {
            if (empty($value)) {
                $this->EchoMessage('No Layout to upload!');
                return;
            }
            $body[] = implode("\r\n", [
                'Content-Disposition: form-data; name="data"; filename="/pages.jsonl"',
                'Content-Type: application/octet-stream',
                '',
                $value,
            ]);
            // generate safe boundary
            do {
                $boundary = '---------------------' . md5(mt_rand() . microtime());
            } while (preg_grep("/{$boundary}/", $body));

            // add boundary for each parameters
            array_walk($body, function (&$part) use ($boundary)
            {
                $part = "--{$boundary}\r\n{$part}";
            });
            // add final boundary
            $body[] = "--{$boundary}--";
            $body[] = '';
            // send data
            $curl = curl_init('http://' . $ip . '/edit');
            @curl_setopt_array($curl, [
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => implode("\r\n", $body),
                CURLOPT_HTTPHEADER => [
                    'Expect: 100-continue',
                    "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
                ],
            ]);
            $json = curl_exec($curl);
            $this->SendDebug(__FUNCTION__, $json);
            curl_close($curl);
        } else {
            $filename = 'pages.jsonl';
            $url = 'http://' . $ip . '/' . $filename . '?download=true';
            $this->SendDebug(__FUNCTION__, $url);
            $download = file_get_contents($url);
            $this->UpdateFormField('Layout', 'value', $download);
        }
    }

    /**
     * Duplicate a entry and or sort the objects list.
     *
     * @param string $value json encoded list plus index
     * @param bool $copy flag if also copy a entry
     */
    private function UpdateMapping(string $value, bool $copy)
    {
        $list = json_decode($value, true);

        // duplicate/copy
        if ($copy) {
            // how many lines in the list?
            $last = count($list);
            // last line has copy page & id
            sscanf($list[$last - 1], 'p%db%d', $page, $id);
            // copy line to last
            for ($index = 0; $index < $last; $index++) {
                if (($list[$index]['Page'] == $page) && ($list[$index]['Id'] == $id)) {
                    $list[$last - 1] = $list[$index];
                    break;
                }
            }
        }
        // sort
        usort($list, function ($a, $b)
        {
            // compare the first column (Page)
            if ($a['Page'] === $b['Page']) {
                // if identical. compare the second column (Id)
                return $a['Id'] <=> $b['Id'];
            }
            // otherwise, compare only the first column
            return $a['Page'] <=> $b['Page'];
        });
        $this->SendDebug(__FUNCTION__, $list);
        $this->UpdateFormField('Objects', 'values', json_encode($list));
    }

    /**
     * Try< to check the (re-)calulation eval statements
     *
     * @param string $value JSON structure of a selected object mapping
     */
    private function CheckMapping(string $value)
    {
        $data = json_decode($value, true);
        if (empty($data)) {
            $this->EchoMessage('No entry selected from the object list!');
            return;
        }
        if ($data[0]['Link'] < self::IPS_MIN_ID) {
            $this->EchoMessage('Entry does not contain a linked variable!');
            return;
        }
        // Value für {{val}}
        $value = GetValue($data[0]['Link']);
        // Text für {{txt}}
        $text = 'TXT';
        // Calculation
        $ecal = 'ok';
        $cal = $value;
        if ($data[0]['Calculation'] != '') {
            $cal = $this->EvaluateString($data[0]['Calculation'], $value, $text, $ecal);
        }
        // Value
        $eval = 'ok';
        $val = $cal;
        if ($data[0]['Value'] != '') {
            $val = $this->EvaluateString($data[0]['Value'], $cal, $text, $eval);
        }
        // Caption
        $etxt = 'ok';
        $txt = '';
        if ($data[0]['Caption'] != '') {
            $txt = $this->EvaluateString($data[0]['Caption'], $cal, $text, $etxt);
        }
        // Recalculation
        $erec = 'ok';
        $rec = $val;
        if ($data[0]['Recalculation'] != '') {
            $rec = $this->EvaluateString($data[0]['Recalculation'], $val, $text, $etxt);
        }
        // Result
        $msg = $this->Translate("Value of the link:\t\t\t\t%s\nText default value:\t\t\tTXT\n\nValue after calculation:\t\t%s\nEvaluation of the calculation:\t%s\n\nValue (of value):\t\t\t\t%s\nEvaluation of value:\t\t\t%s\n\nValue of caption:\t\t\t\t%s\nEvaluation of caption:\t\t\t%s\n\nValue after recalculation:\t\t%s\nEvaluation of recalculation:\t%s");
        $this->EchoMessage(sprintf($msg, $value, $cal, $ecal, $val, $eval, $txt, $etxt, $rec, $erec));
    }

    /**
     * Validate the passed page layout jsonl.
     *
     * @param string $value Layout as JSONL
     * @param bool $echo If true popup message, otherwise silence
     *
     * @return bool True if every line is a valid JSON object; otherwise false.
     */
    private function ValidateLayout(string $value, bool $echo)
    {
        // split the string into lines
        $lines = explode("\n", trim($value));
        // count the lines
        $counter = 0;
        foreach ($lines as $line) {
            // increment line counter
            $counter++;
            // skip empty lines if they exist in the string
            if (trim($line) === '') {
                continue;
            }
            // check for JSON errors
            $valid = json_validate($line);
            if (!$valid) {
                // return false if any line is not valid JSON
                if ($echo) {
                    $this->EchoMessage($this->Translate('No valid JSON on line: ') . $counter);
                }
                return false;
            }
        }
        if ($echo) {
            $this->EchoMessage('The given string is syntactically valid JSON!');
        }
        // return true if every line is a valid JSON object
        return true;
    }

    /**
     * Evaluate passed string as expression.
     *
     * @param string $subject Expression text
     * @param mixed $value Value == {{val}}
     * @param string $text Text == {{txt}}
     * @param string $error Error message for check expression
     * @return mixed (Re-)formated value/text.
     */
    private function EvaluateString(string $subject, mixed $value, string $text = '', string &$error = 'ok')
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
                $error = $e->GetMessage();
                $this->SendDebug(__FUNCTION__, 'RD Value: ' . $value . ',RD Type: ' . gettype($value) . ',RD Error' . $e->GetMessage() . ',RD Eval' . $eval . ',RD Subject: ' . $subject);
                $code = '';
            }
            return $code;
        } else {
            return $value;
        }
    }

    /**
     * Encode text to valid json format.
     *
     * @param string $text Text to convert in json format
     * @return string Encoded json conform content
     */
    private function EncodeText(string $text)
    {
        // JSON encode converts special characters into Unicode sequences
        $encoded = json_encode($text);
        // Remove the enclosing quotation marks that are added by json_encode
        $encoded = substr($encoded, 1, -1);
        // Replace double backslashes with single backslashes
        $encoded = str_replace('\\\\', '\\', $encoded);
        $this->SendDebug(__FUNCTION__, $encoded);
        return $encoded;
    }

    /**
     * Retrieve UI object type as textual representation.
     *
     * @param int $type ID of the UI Object
     * @return string Clear name of UI element.
     */
    private function GetType(int $type)
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
     * Show message via popup.
     *
     * @param string $caption Echo message text
     */
    private function EchoMessage(string $caption)
    {
        $this->UpdateFormField('EchoMessage', 'caption', $this->Translate($caption));
        $this->UpdateFormField('EchoPopup', 'visible', true);
    }
}

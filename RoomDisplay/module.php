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
    use ProfileHelper;
    use VariableHelper;
    use WebhookHelper;

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

    /**
     * Overrides the internal IPSModule::Create($id) function
     */
    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Device-Topic (Name)
        $this->RegisterPropertyString('Hostname', 'plate');
        // Design Objects
        $this->RegisterPropertyString('Objects', '[]');

        // Settings
        $this->RegisterPropertyBoolean('AutoDimBacklight', false);
        $this->RegisterPropertyBoolean('AutoShutdownBacklight', false);
        $this->RegisterPropertyBoolean('PageOneOnIdle', false);
        $this->RegisterPropertyInteger('ForwardMessageScript', 1);

        // Automatically connect to the MQTT server instance
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
    }

    /**
     * Overrides the internal IPSModule::Destroy($id) function
     */
    public function Destroy()
    {
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterHook('/hook/plate' . $this->InstanceID);
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
        $mqttTopic = 'hasp/' . $this->ReadPropertyString('Hostname') . '/';
        $this->SetReceiveDataFilter('.*' . $mqttTopic . '.*');
        $this->SendDebug(__FUNCTION__, 'SetReceiveDataFilter(\'.*' . $mqttTopic . '.*\')', 0);

        // Webhook for backup
        $this->RegisterHook('/hook/plate' . $this->InstanceID);

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
        $this->MaintainVariable('Status', $this->Translate('Online'), 0, 'WWXRD.Status', 1, true);
        $this->MaintainVariable('Backlight', $this->Translate('Backlight'), 1, 'WWXRD.Backlight', 3, true);
        $this->MaintainVariable('Page', $this->Translate('Page'), 1, 'WWXRD.Page', 4, true);
        // Maintain actions
        $this->MaintainAction('Backlight', true);
        $this->MaintainAction('Page', true);

        // Validate element liste
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
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), false);
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
            case 'StatusUpdate':
                $this->SendCommand('statusupdate');
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

        $prefix = 'hasp/' . $this->ReadPropertyString('Hostname') . '/LWT';
        // Check whether the topic begins with a specific prefix
        if (stripos($topic, $prefix) !== false) {
            $this->HandleData('LWT', $payload);
            return;
        }

        $prefix = 'hasp/' . $this->ReadPropertyString('Hostname') . '/state/';
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
        $this->SendDebug(__FUNCTION__, 'SenderId: ' . $sender . ' Data: ' . $this->PrettyPrint($data), 0);
        // Auf aktualisierungen reagieren.
        if ($message == VM_UPDATE) {
            $objects = json_decode($this->ReadPropertyString('Objects'), true);
            // alle Elemente durchlaufen
            foreach ($objects as $item => $element) {
                if ($element['Object'] != $sender) {
                    continue;
                }
                $this->SendDebug(__FUNCTION__, $this->PrettyPrint($element), 0);
                // Umrechenen
                $value = $this->EvaluateString($data[0], $element['Calculation']);
                // Debug
                $this->SendDebug(__FUNCTION__, $this->GetType($element['Type']) . ' :' . $this->SafePrint($value));
                // Label
                if ($element['Type'] == self::UI_LABEL) {
                    if ($element['Caption'] == '') {
                        // Bei leerer Caption wird der Wert direkt geschrieben.
                        $this->SetItemText($element['Page'], $element['Id'], $this->EncodeText(strval($value)));
                    } else {
                        // sprintf %s bei String, %d bei Integer %f bei Float, %% um ein "%" zu schreiben
                        $this->SetItemText($element['Page'], $element['Id'], $this->EncodeText(sprintf($element['Caption'], ($value))));
                    }
                }
                // Toggel-Button, Slider, LineMeter
                if ($element['Type'] == self::UI_TOGGLE || $element['Type'] == self::UI_SLIDER || $element['Type'] == self::UI_METER) {
                    $this->SetItemValue($element['Page'], $element['Id'], intval($value));
                    // Toogle Text for Button
                    if ($element['Caption'] != '') {
                        $text = $this->EvaluateString($value, $element['Caption']);
                        $this->SetItemValStr($element['Page'], $element['Id'], $text);
                    }
                }
                // Dropdown || Gauge
                if (($element['Type'] == self::UI_DROPDOWN) ||
                    ($element['Type'] == self::UI_GAUGE)) {
                    // Bei leerem Value wird der Wert direkt geschrieben.
                    $this->SetItemValue($element['Page'], $element['Id'], $value);
                }
                // Arc
                if ($element['Type'] == self::UI_ARC) {
                    if ($element['Caption'] == '') {
                        // Bei leerer Caption wird der Wert direkt geschrieben.
                        $this->SetItemValStr($element['Page'], $element['Id'], strval($value));
                    } else {
                        // sprintf %s bei String, %d bei Integer %f bei Float, %% um ein "%" zu schreiben
                        $this->SetItemValStr($element['Page'], $element['Id'], sprintf($element['Caption'], $value));
                    }
                    if ($element['Value'] == '') {
                        // Bei leerem Value wird der Wert direkt geschrieben.
                        $this->SetItemValue($element['Page'], $element['Id'], intval($value));
                    } else {
                        // sprintf %s bei String, %d bei Integer %f bei Float, %% um ein "%" zu schreiben
                        $this->SetItemValue($element['Page'], $element['Id'], intval(sprintf($element['Value'], $value)));
                    }
                }
                // LED Indicator
                if ($element['Type'] == self::UI_LED) {
                    $var = IPS_GetVariable($sender);
                    // Bei Boolscher Variable
                    if ($var['VariableType'] == 0) {
                        // Bei Boolscher Variable LEDInidactor ein
                        if ($value) {
                            $this->SetItemValue($element['Page'], $element['Id'], 255);
                        } else {
                            $this->SetItemValue($element['Page'], $element['Id'], 0);
                        }
                    } else {
                        $this->SetItemValue($element['Page'], $element['Id'], intval($value));
                    }
                }
                // LineMeter
                if ($element['Type'] == self::UI_METER) {
                    $this->SetItemValue($element['Page'], $element['Id'], intval($value));
                    if ($element['Caption'] == '') {
                        // Bei Leerer Caption wird der Wert direkt geschrieben.
                        $this->SendCommand('p' . $element['Page'] . 'b' . $element['Id'] . '.value_str=' . strval($value));
                    } else {
                        // sprintf %s bei String, %d bei Integer %f bei Float, %% um ein "%" zu schreiben
                        $this->SendCommand('p' . $element['Page'] . 'b' . $element['Id'] . '.value_str=' . sprintf($element['Caption'], ($value)));
                    }
                }
                // Object
                if ($element['Type'] == self::UI_OBJECT) {
                    if ($element['Caption'] != '') {
                        $text = $this->EvaluateString($value, $element['Caption']);
                        $this->SetItemValStr($element['Page'], $element['Id'], $text);
                    }
                    if ($element['Value'] != '') {
                        $text = $this->EvaluateString($value, $element['Value']);
                        $this->SetItemProperty($element['Page'], $element['Id'], 'bg_color', $text);
                    }
                }
            }
        }
    }

    public function SetItemProperty(int $page, int $objectId, string $property, string $value)
    {
        $this->SendCommand('p' . $page . 'b' . $objectId . '.' . $property . '=' . $value);
    }

    public function SetItemValue(int $page, int $objectId, int $value)
    {
        $this->SendCommand('p' . $page . 'b' . $objectId . '.val=' . intval($value));
    }

    public function SetItemText(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.text=' . $value . '"]');
    }

    public function SetItemValStr(int $page, int $objectId, string $value)
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.value_str=' . $value . '"]');
    }

    public function SendCommand(string $command)
    {
        $mqttTopic = 'hasp/' . $this->ReadPropertyString('Hostname') . '/command/';
        $this->SendDebug(__FUNCTION__, 'Topic: ' . $mqttTopic . ' Command: ' . $command, 0);
        $this->SendMQTT($mqttTopic, $command);
    }

    public function Restart()
    {
        $this->SendCommand('restart');
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData()
    {
        $filename = 'pages.jsonl';

        // download the file

        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        // output line by line
        foreach ($entry as $fields) {
            fputcsv($output, $fields);
        }
    }

    protected function SendMQTT($topic, $payload)
    {
        $resultServer = true;
        $resultClient = true;
        //MQTT Server
        $server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $server['PacketType'] = 3;
        $server['QualityOfService'] = 0;
        $server['Retain'] = false;
        $server['Topic'] = $topic;
        $server['Payload'] = $payload;
        $json = json_encode($server, JSON_UNESCAPED_SLASHES);
        //$this->SendDebug(__FUNCTION__.'MQTT Server', $json, 0);
        $resultServer = @$this->SendDataToParent($json);

        //MQTT Client
        $buffer['PacketType'] = 3;
        $buffer['QualityOfService'] = 0;
        $buffer['Retain'] = false;
        $buffer['Topic'] = $topic;
        $buffer['Payload'] = $payload;
        $json = json_encode($buffer, JSON_UNESCAPED_SLASHES);

        $client['DataID'] = '{97475B04-67C3-A74D-C970-E9409B0EFA1D}';
        $client['Buffer'] = $json;

        $json = json_encode($client);
        //$this->SendDebug(__FUNCTION__.'MQTT Client', $json, 0);
        $resultClient = @$this->SendDataToParent($json);

        return $resultServer === false && $resultClient === false;
    }

    private function RegisterObjects()
    {
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        if ($objects == null) {
            $objects = [];
        }
        //$this->SendDebug(__FUNCTION__, $this->PrettyPrint($objects));

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
        foreach ($objects as $item => $element) {
            //$this->SendDebug(__FUNCTION__, $this->PrettyPrint($element));
            if ($element['Object'] != 1) {
                // Objekt muss existiert!
                if (IPS_ObjectExists($element['Object'])) {
                    // Button  ==> Script, Variable
                    if ($element['Type'] == self::UI_BUTTOM) {
                        // TODO
                    }
                    // Toggle Button ==> Variable
                    if ($element['Type'] == self::UI_TOGGLE) {
                        if (IPS_GetObject($element['Object'])['ObjectType'] != 2) {
                            echo 'Fehler bei ausgewähltem Objekt ' . $count . ':' . PHP_EOL .
                                    'Objekt mit der ID: ' . $element['Object'] . ' ist kein Variable' . PHP_EOL .
                                    'Das Objekt für einen Toggle-Button muss vom Typ "Varaible" sein.' . PHP_EOL;
                            $state = false;
                        }
                    }
                    // Slider ==> Variable
                    if ($element['Type'] == self::UI_SLIDER) {
                        if (IPS_GetObject($element['Object'])['ObjectType'] != 2) {
                            echo 'Fehler bei ausgewähltem Objekt ' . $count . ':' . PHP_EOL .
                                    'Objekt mit der ID: ' . $element['Object'] . ' ist keine Variable' . PHP_EOL .
                                    'Das Objekt für einen Slider muss vom Typ "Varaible" sein.' . PHP_EOL;
                            $state = false;
                        }
                    }
                    // Dropdown ==> Variable
                    if ($element['Type'] == self::UI_DROPDOWN) {
                        if (IPS_GetObject($element['Object'])['ObjectType'] != 2) {
                            echo 'Fehler bei ausgewähltem Objekt ' . $count . ':' . PHP_EOL .
                                    'Objekt mit der ID: ' . $element['Object'] . ' ist keine Variable' . PHP_EOL .
                                    'Das Objekt für einen Dropdown muss vom Typ "Varaible" sein.' . PHP_EOL;
                            $state = false;
                        }
                    }
                    $this->RegisterReference($element['Object']);
                    $this->RegisterMessage($element['Object'], VM_UPDATE);
                }
                else {
                    echo 'Fehler bei ausgewähltem Objekt ' . $count . ':' . PHP_EOL .
                        'Das Objekt mit der ID: ' . $element['Object'] . ' existiert nicht!';
                    $state = false;
                }
            }
            $count++;
        }
        return $state;
    }

    private function HandleData(string $topic, string $data)
    {
        //$this->SendDebug(__FUNCTION__, 'Topic: ' . $topic . ' ,Payload: ' . $data);
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
            foreach ($objects as $item => $element) {
                if ($element['Page'] == $matches[1] && $element['Id'] == $matches[2]) {
                    $index = $item;
                    break;
                }
            }
            if ($index < 0) {
                $this->SendDebug(__FUNCTION__, 'No registered object!', 0);
                return;
            }
            $element = $objects[$index];
            $data = json_decode($data);
            if (property_exists($data, 'event') && ($element['Object'] != 1)) {
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
                if ($element['Recalculation'] != '') {
                    $value = $this->EvaluateString($value, $element['Recalculation']);
                }
                // Type & Value & Text
                $this->SendDebug(__FUNCTION__, $this->GetType($element['Type']) . ': ' . $this->SafePrint($value) . ', ' . $text, 0);
                // Button down || Dropdown changed || Toggle Button, Roller, Slider or Switch up
                if (($element['Type'] == self::UI_BUTTOM && $data->event == self::EH_DOWN) ||
                    ($element['Type'] == self::UI_DROPDOWN && $data->event == self::EH_CHANGED) ||
                    ($element['Type'] == self::UI_TOGGLE && $data->event == self::EH_UP) ||
                    ($element['Type'] == self::UI_ROLLER && $data->event == self::EH_UP) ||
                    ($element['Type'] == self::UI_SLIDER && $data->event == self::EH_UP) ||
                    ($element['Type'] == self::UI_SWITCH && $data->event == self::EH_UP)) {
                    $this->SendDebug(__FUNCTION__, 'Catch');
                    if (IPS_GetObject($element['Object'])['ObjectType'] == 3) {
                        IPS_RunScriptEx($element['Object'], ['VALUE' => $value, 'TEXT' => $text]);
                        $this->SendDebug(__FUNCTION__, 'IPS_RunScriptEx(' . $element['Object'] . ', [VALUE=>' . $value . ',TEXT=>' . $text . '])', 0);
                    }
                    else {
                        $this->SendDebug(__FUNCTION__, 'Else');
                        if (HasAction($element['Object']) && $value != -1) {
                            RequestAction($element['Object'], $value);
                            $this->SendDebug(__FUNCTION__, 'RequestAction(' . $element['Object'] . ', ' . $value . ')', 0);
                        }
                        elseif ($value != -1) {
                            SetValue($element['Object'], $value);
                            $this->SendDebug(__FUNCTION__, 'SetValue(' . $element['Object'] . ', ' . $value . ')', 0);
                        }
                        else {
                            $this->SendDebug(__FUNCTION__, 'No return toobject: ' . $element['Object'], 0);
                        }
                    }
                }
            }

            if (property_exists($data, 'val') && ($element['Object'] != 1)) {
                // Received Typ = Arc & Value
                if ($element['Type'] == self::UI_ARC) {
                    if (HasAction($element['Object']) && $value != -1) {
                        RequestAction($element['Object'], $data->val);
                        $this->SendDebug(__FUNCTION__, 'RequestAction():' . $element['Object'] . ' Value: ' . $data->val, 0);
                    }
                    elseif ($value != -1) {
                        SetValue($element['Object'], $value);
                        $this->SendDebug(__FUNCTION__, 'SetValue(' . $element['Object'] . ', ' . $value . ')', 0);
                    }
                    else {
                        $this->SendDebug(__FUNCTION__, 'No return toobject: ' . $element['Object'], 0);
                    }
                }
            }
            $scriptid = $this->ReadPropertyInteger('ForwardMessageScript');
            if ($scriptid != 1) {
                IPS_RunScriptEx($scriptid, ['Data' => json_encode(['Topic' => $topic, 'Data' => $data])]);
            }
        }

        if ($topic == 'statusupdate') {
            $this->SendDebug(__FUNCTION__, 'Status: ' . $data);
        }

        // Last Will and Testament (LWT)?
        if ($topic == 'LWT') {
            switch ($data) {
                case 'online':
                    $this->SetValueInteger('Online', 1);
                    $this->Online();
                    break;
                default:
                    $this->SetValueInteger('Online', 0);
            }
        }
    }

    /**
     * Online State (LWT)
     *
     */
    private function Online()
    {
        $this->SendDebug(__FUNCTION__, 'Gerät ist Online', 0);
        // TODO: Sync linked objects with the device objects
    }

    /**
     * Evaluate String
     *
     * @param mixed $value Value
     * @param mixed $subject Expression text
     */
    private function EvaluateString($value, $subject)
    {
        if (!empty($subject)) {
            $eval = str_replace(self::PH_VALUE, strval($value), $subject);
            $eval = 'return (' . $eval . ');';
            $this->SendDebug(__FUNCTION__, 'eval: ' . $eval);
            $code = eval($eval);
            if ($code === false) {
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
}

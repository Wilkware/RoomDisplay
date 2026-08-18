<?php

declare(strict_types=1);

/** Generell funktions */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\RoomDisplay\DebugHelper;
use Wilkware\RoomDisplay\FormatHelper;
use Wilkware\RoomDisplay\VariableHelper;
use Wilkware\RoomDisplay\WidgetHelper;

/**
 * CLASS RoomDisplay
 */
class RoomDisplay extends IPSModuleStrict
{
    // -------------------------------------------------------------------------
    // Traits
    // -------------------------------------------------------------------------

    use DebugHelper;
    use FormatHelper;
    use VariableHelper;
    use WidgetHelper;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** @var int Min IPS Object ID */
    private const IPS_MIN_ID = 10000;

    /** @var string MQTT IO Module ID (Splitter) */
    private const GUID_MQTT_IO = '{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}';

    /** @var string MQTT TX Module ID (from module to server) */
    private const GUID_MQTT_TX = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';

    /** @var string MQTT RX Module ID (from server to module) */
    // private const GUID_MQTT_RX = '{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}';

    /** @var int UI Object IDs */
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
    private const UI_MATRIX = 21;
    private const UI_TAB = 22;
    private const UI_QRCODE = 23;

    /**
     * @var string Event handler
     * Occurs when a button goes from depressed to pressed (the moment of touch)
     */
    private const EH_DOWN = 'down';

    /** @var string Event handler
     * The button was released within a short time i.e. a short press has occurred
     */
    private const EH_UP = 'up';

    /** @var string Event handler
     * Event is sent when the value of the object has changed during the event
     */
    private const EH_CHANGED = 'changed';

    /** @var string Placeholder Value */
    private const PH_VALUE = '{{val}}';

    /** @var string Placeholder Text */
    private const PH_TEXT = '{{txt}}';

    /** @var string Placeholder Formatted Text */
    private const PH_FORMAT = '{{fmt}}';

    /** @var string Host Name */
    private const RD_HOST_NAME = 'plate';

    /** @var string Topic Name */
    private const RD_PREFIX_TOPIC = 'hasp/';

    /** @var string Hook Name */
    private const RD_PREFIX_HOOK = 'plate';

    // -------------------------------------------------------------------------
    // Presentations
    // -------------------------------------------------------------------------

    /**
     * @var array<string,mixed> Idle State Presentation (Value)
     */
    private const WWXRD_PRESENTATION_IDLE = [
        'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
        'USAGE_TYPE'          => 0,
        'THOUSANDS_SEPARATOR' => '',
        'SHOW_PREVIEW'        => true,
        'SUFFIX'              => '',
        'COLOR'               => -1,
        'MAX'                 => 0,
        'MULTILINE'           => false,
        'DECIMAL_SEPARATOR'   => 'Client',
        'PERCENTAGE'          => false,
        'DIGITS'              => 0,
        'INTERVALS'           => '[{"ColorDisplay":-1,"ContentColorDisplay":-1,"IntervalMinValue":0,"IntervalMaxValue":0,"ConstantActive":true,"ConstantValue":"Off","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":-1,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":-1,"ContentColorDisplay":-1,"IntervalMinValue":1,"IntervalMaxValue":1,"ConstantActive":true,"ConstantValue":"Short","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":-1,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":-1,"ContentColorDisplay":-1,"IntervalMinValue":2,"IntervalMaxValue":2,"ConstantActive":true,"ConstantValue":"Long","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":-1,"ContentColorActive":false,"ContentColorValue":-1}]',
        'DISPLAY_TYPE'        => 0,
        'ICON'                => 'Hourglass',
        'INTERVALS_ACTIVE'    => true,
        'PREVIEW_STYLE'       => 1,
        'MIN'                 => 0,
        'CONTENT_COLOR'       => -1,
        'PREFIX'              => '',
    ];

    /**
     * @var array<string,mixed> State Presentation (Value)
     */
    private const WWXRD_PRESENTATION_STATE = [
        'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
        'USAGE_TYPE'          => 0,
        'THOUSANDS_SEPARATOR' => '',
        'SHOW_PREVIEW'        => true,
        'SUFFIX'              => '',
        'COLOR'               => -1,
        'PREFIX'              => '',
        'CONTENT_COLOR'       => -1,
        'MAX'                 => 0,
        'MULTILINE'           => false,
        'DECIMAL_SEPARATOR'   => 'Client',
        'PERCENTAGE'          => false,
        'DIGITS'              => 0,
        'INTERVALS'           => '[]',
        'DISPLAY_TYPE'        => 0,
        'ICON'                => 'Display',
        'INTERVALS_ACTIVE'    => true,
        'PREVIEW_STYLE'       => 1,
        'MIN'                 => 0,
        'OPTIONS'             => '[{"Value":false,"Caption":"Offline","IconActive":true,"IconValue":"display-slash","ColorActive":true,"ColorValue":16711680},{"Value":true,"Caption":"Online","IconActive":true,"IconValue":"display","ColorActive":true,"ColorValue":65280}]',
    ];

    /**
     * @var array<string,mixed> Backlight Presentation (Slider)
     */
    private const WWXRD_PRESENTATION_BACKLIGHT = [
        'PRESENTATION'        => VARIABLE_PRESENTATION_SLIDER,
        'USAGE_TYPE'          => 5,
        'THOUSANDS_SEPARATOR' => '',
        'DECIMAL_SEPARATOR'   => 'Client',
        'PERCENTAGE'          => false,
        'DIGITS'              => 0,
        'INTERVALS'           => '[]',
        'ICON'                => 'Light',
        'INTERVALS_ACTIVE'    => false,
        'MAX'                 => 255,
        'GRADIENT_TYPE'       => 0,
        'MIN'                 => 1,
        'CUSTOM_GRADIENT'     => '[]',
        'PREFIX'              => '',
        'STEP_SIZE'           => 1.0,
        'SUFFIX'              => '',
    ];

    /**
     * @var array<string,mixed> Page Presentation (Slider)
     */
    private const WWXRD_PRESENTATION_PAGE = [
        'PRESENTATION'        => VARIABLE_PRESENTATION_SLIDER,
        'USAGE_TYPE'          => 5,
        'THOUSANDS_SEPARATOR' => '',
        'DECIMAL_SEPARATOR'   => 'Client',
        'PERCENTAGE'          => false,
        'DIGITS'              => 0,
        'INTERVALS'           => '[]',
        'ICON'                => 'Book',
        'INTERVALS_ACTIVE'    => false,
        'MAX'                 => 12,
        'GRADIENT_TYPE'       => 0,
        'MIN'                 => 1,
        'CUSTOM_GRADIENT'     => '[]',
        'PREFIX'              => '',
        'STEP_SIZE'           => 1.0,
        'SUFFIX'              => '',
    ];

    /**
     * @var array<string,mixed> Navigation Presentation (Enumeration)
     */
    private const WWXRD_PRESENTATION_NAVIGATION = [
        'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
        'OPTIONS'      => '[{"Value":"page=prev","Caption":"< Prev ]","IconActive":true,"IconValue":"arrow-left-to-bracket","Color":-1},{"Value":"page=back","Caption":"[ Back ]","IconActive":true,"IconValue":"arrow-up-to-bracket","Color":-1},{"Value":"page=next","Caption":"[ Next >","IconActive":true,"IconValue":"arrow-right-to-bracket","Color":-1}]',
        'LAYOUT'       => 0,
        'ICON'         => 'square-ellipsis',
        'DISPLAY'      => 0,
    ];

    /**
     * @var array<string,mixed> Action Presentation (Enumeration)
     */
    private const WWXRD_PRESENTATION_ACTION = [
        'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
        'OPTIONS'      => '[{"Caption":"Clear Pages","Color":-1,"IconActive":true,"IconValue":"rotate-left","Value":0},{"Caption":"Reload Pages","Color":-1,"IconActive":true,"IconValue":"rotate-right","Value":1},{"Caption":"Synchronize","Color":-1,"IconActive":true,"IconValue":"rotate","Value":2},{"Caption":"Restart","Color":-1,"IconActive":true,"IconValue":"power-off","Value":3}]',
        'LAYOUT'       => 0,
        'ICON'         => 'rectangle-terminal',
        'DISPLAY'      => 0,
    ];

    // -------------------------------------------------------------------------
    // Echo Maps
    // -------------------------------------------------------------------------

    /**
     * @var array<int,array{0:string,1:string,2:int,3:?string}> Status Info Map
     */
    private const RD_STATUS_INFO = [
        ['node', 'Node', 3, null],
        ['idle', 'Idle', 3, null],
        ['version', 'Version', 3, null],
        ['uptime', 'Uptime', 1, null],
        ['ssid', 'WiFi', 3, null],
        ['rssi', 'RSSI', 1, null],
        ['ip', 'IP', 3, null],
        ['mac', 'MAC', 3, null],
        ['heapFree', 'Heap Free', 1, null],
        ['heapFrag', 'Heap Frag', 1, null],
        ['core', 'Core', 3, null],
        ['canUpdate', 'Updateable', 0, null],
        ['page', 'Page', 1, null],
        ['numPages', 'Pages', 1, null],
        ['tftDriver', 'TFT Driver', 3, null],
        ['tftWidth', 'TFT Width', 1, null],
        ['tftHeight', 'TFT Height', 1, null],
    ];

    /**
     * @var array<int,array{0:string,1:string,2:int,3:?string}> Mood Light Map
     */
    private const RD_MOOD_LIGHT = [
        ['state', 'Status', 5, null],
        ['brightness', 'Brightness', 3, null],
        ['color', 'Color', 3, null],
        ['r', '(R)ed', 1, null],
        ['g', '(G)reen', 1, null],
        ['b', '(B)lue', 1, null],
    ];

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
    {
        // Never delete this line!
        parent::Create();

        // Webhook for backup
        $this->RegisterHook(self::RD_PREFIX_HOOK . $this->InstanceID);

        if ((float) IPS_GetKernelVersion() < 8.2) {
            $this->ConnectParent(self::GUID_MQTT_IO);
        }

        // Device-Topic (Name)
        $this->RegisterPropertyString('Hostname', self::RD_HOST_NAME);
        $this->RegisterPropertyString('IP', '');
        // Page Layout
        $this->RegisterPropertyString('Layout', '');
        // Design Objects
        $this->RegisterPropertyString('Objects', '[]');
        // Widgets
        $this->RegisterPropertyBoolean('ClockCheck', false);
        $this->RegisterPropertyInteger('ClockPage', 8);
        $this->RegisterPropertyInteger('ClockHour', 10);
        $this->RegisterPropertyInteger('ClockMinute', 11);
        $this->RegisterPropertyBoolean('EarthCheck', false);
        $this->RegisterPropertyInteger('EarthPage', 9);
        $this->RegisterPropertyInteger('EarthStart', 10);
        $this->RegisterPropertyInteger('EarthColor', 0x00FF00);
        $this->RegisterPropertyBoolean('EarthPrefix', true);
        $this->RegisterPropertyBoolean('EarthSuffix', false);
        $this->RegisterPropertyBoolean('FlipCheck', false);
        $this->RegisterPropertyInteger('FlipPage', 10);
        $this->RegisterPropertyInteger('FlipHour', 15);
        $this->RegisterPropertyInteger('FlipMinute', 25);
        $this->RegisterPropertyBoolean('HaspCheck', false);
        $this->RegisterPropertyInteger('HaspPage', 11);
        $this->RegisterPropertyInteger('HaspMinute', 10);
        // Settings
        $this->RegisterPropertyBoolean('AutoDimBacklight', false);
        $this->RegisterPropertyInteger('AutoOffIdle', 255);
        $this->RegisterPropertyInteger('AutoShortIdle', 50);
        $this->RegisterPropertyInteger('AutoLongIdle', 0);
        $this->RegisterPropertyInteger('AutoDarkModeVariable', 1);
        $this->RegisterPropertyInteger('AutoDarkOffIdle', 50);
        $this->RegisterPropertyInteger('AutoDarkShortIdle', 10);
        $this->RegisterPropertyInteger('AutoDarkLongIdle', 0);
        $this->RegisterPropertyBoolean('AutoShutdownBacklight', false);
        $this->RegisterPropertyInteger('AutoAntiburnCycle', 60);
        $this->RegisterPropertyInteger('AutoAntiburnBacklight', 0);
        $this->RegisterPropertyBoolean('PageOneOnIdle', false);
        $this->RegisterPropertyInteger('PageOnIdle', 1);
        $this->RegisterPropertyInteger('PageOnIdleDynamic', 1);
        $this->RegisterPropertyInteger('PageOnIdleBranch', 1);
        $this->RegisterPropertyInteger('PageOnIdleTrue', 11);
        $this->RegisterPropertyInteger('PageOnIdleFalse', 12);
        $this->RegisterPropertyBoolean('AutoSwitchPage', false);
        $this->RegisterPropertyString('AutoSwitchSelection', '1-12');
        $this->RegisterPropertyInteger('AutoSwitchInterval', 1);
        $this->RegisterPropertyBoolean('SyncOnIdle', false);
        $this->RegisterPropertyInteger('AutoClosePopup', 5);
        $this->RegisterPropertyInteger('ForwardMessageScript', 1);
        $this->RegisterPropertyInteger('VisuOnColor', 0x00FF00);
        $this->RegisterPropertyInteger('VisuOffColor', 0xFF0000);
        $this->RegisterPropertyBoolean('VisuNaviBar', true);
        $this->RegisterPropertyBoolean('VisuActionBar', false);

        // Info Attributes
        $this->RegisterAttributeString('StatusUpdate', '');
        $this->RegisterAttributeString('MoodLight', '');

        // Idle Attribute
        $this->RegisterAttributeBoolean('SyncData', true);
        $this->RegisterAttributeBoolean('DisableIdle', false);

        // Register Timer
        $this->RegisterTimer('AntiburnTimer', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "Antiburn", true);');
        $this->RegisterTimer('AntiburnLight', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "Antiburn", false);');
        $this->RegisterTimer('SwitchPageTimer', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "SwitchPage", true);');
        $this->RegisterTimer('ClockTimer', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "ClockTick", false);');

        // Set visualization type to 1, as we want to offer HTML
        $this->SetVisualizationType(1);
    }

    /**
     * The content can be overwritten in order to transfer a self-created configuration page.
     * This way, content can be generated dynamically.
     * In this case, the "form.json" on the file system is completely ignored.
     *
     * @return string Content of the configuration page.
     */
    public function GetConfigurationForm(): string
    {
        // Get form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        $ip = $this->ReadPropertyString('IP');
        // Layout Buttons & Status Buttons
        if ($ip != '') {
            $form['elements'][2]['items'][1]['items'][0]['enabled'] = true;
            $form['elements'][2]['items'][1]['items'][1]['enabled'] = true;
            $form['elements'][2]['items'][1]['items'][2]['enabled'] = true;
            //$form['elements'][2]['items'][1]['items'][3]['enabled'] = true;
            $form['actions'][2]['items'][0]['items'][2]['enabled'] = true;
        }
        // Extract Version
        $ins = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($ins['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);
        $form['actions'][3]['items'][2]['caption'] = sprintf('v%s.%d', $lib['Version'], $lib['Build']);
        // return form
        return json_encode($form);
    }

    /**
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        // Never delete this line!
        parent::ApplyChanges();
        $mqttTopic = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/';
        $this->SetReceiveDataFilter('.*' . $mqttTopic . '.*');
        $this->LogDebug(__FUNCTION__, 'SetReceiveDataFilter(\'.*' . $mqttTopic . '.*\')');

        //Presentations
        $idle = $this->TranslatePresentation(self::WWXRD_PRESENTATION_IDLE, 'INTERVALS', 'ConstantValue');
        $navi = $this->TranslatePresentation(self::WWXRD_PRESENTATION_NAVIGATION, 'OPTIONS', 'Caption');
        $action = $this->TranslatePresentation(self::WWXRD_PRESENTATION_ACTION, 'OPTIONS', 'Caption');

        // Maintain variables
        $this->MaintainVariable('Idle', $this->Translate('Idle'), 1, $idle, 2, true);
        $this->MaintainVariable('Status', $this->Translate('Status'), 0, self::WWXRD_PRESENTATION_STATE, 1, true);
        $this->MaintainVariable('Backlight', $this->Translate('Backlight'), 1, self::WWXRD_PRESENTATION_BACKLIGHT, 3, true);
        $this->MaintainVariable('Page', $this->Translate('Page'), 1, self::WWXRD_PRESENTATION_PAGE, 4, true);
        $this->MaintainVariable('Navigate', $this->Translate('Navigate'), 3, 'WWXRD.Navigate', 5, true);
        $this->MaintainVariable('Action', $this->Translate('Action'), 1, $action, 6, true);

        // Maintain actions
        $this->MaintainAction('Backlight', true);
        $this->MaintainAction('Page', true);
        $this->MaintainAction('Navigate', true);
        $this->MaintainAction('Action', true);

        // Reset Timer
        $this->SetTimerInterval('AntiburnTimer', 0);

        // Validate object liste
        if ($this->RegisterObjects()) {
            // Send a complete update message to the display, as parameters may have changed
            $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
            // Status all okay
            $this->SetStatus(102);
        }
        else {
            $this->SetStatus(201);
        }
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . $value);
        switch ($ident) {
            case 'Action':
                $this->SetValueInteger($ident, $value);
                switch ($value) {
                    case 0: $this->SendCommand('clearpage=all');
                        break;
                    case 1: $this->SendCommand('run /pages.jsonl');
                        break;
                    case 2: $this->Synchronize();
                        break;
                    case 3: $this->SendCommand('restart');
                        break;
                }
                break;
            case 'Backlight':
                $this->SendCommand('backlight=' . $value);
                break;
            case 'Page':
                $this->SendCommand('page=' . $value);
                break;
            case 'Navigate':
                $this->SetValueString($ident, $value);
                $this->SendCommand($value);
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
            case 'LayoutParse':
                $this->ParseLayout($value);
                break;
            case 'MappingSelect':
                $this->SelectMapping($value);
                break;
            case 'MappingDelete':
                $this->DeleteMapping($value);
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
            case 'MappingMatch':
                $this->MatchMapping($value);
                break;
            case 'MappingTransfer':
                $this->TransferMapping($value);
                break;
            case 'SwitchPage':
                $this->PageSwitch();
                break;
            case 'ShowPage':
                $this->PageShow($value);
                break;
            case 'ClockTick':
                $this->TickClock($value);
                break;
        }
    }

    /**
     * If the HTML-SDK is to be used, this function must be overwritten in order to return the HTML content.
     *
     * @return string Initial display of a representation via HTML SDK
     */
    public function GetVisualizationTile(): string
    {
        // Add a script to set the values when loading, analogous to changes at runtime
        // Although the return from GetFullUpdateMessage is already JSON-encoded, json_encode is still executed a second time
        // This adds quotation marks to the string and any quotation marks within it are escaped correctly
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';
        // Add static HTML from file
        $module = file_get_contents(__DIR__ . '/module.html');
        // Return everything
        // Important: $initialHandling at the end, as the handleMessage function is only defined in the HTML
        return $module . $initialHandling;
    }

    /**
     * This function is called by IP-Symcon and processes sent data and, if necessary, forwards it to
     * all child instances. Data can be sent using the SendDataToChildren function.
     *
     * @param string $json Data package in JSON format
     *
     * @return string Optional response to the parent instance
     */
    public function ReceiveData(string $json): string
    {
        $data = json_decode($json);

        $topic = $data->Topic;
        $payload = hex2bin($data->Payload);
        $this->LogDebug(__FUNCTION__, 'Received Topic: ' . $topic . ' Payload: ' . $payload);
        // Check whether the topic begins with a specific prefix
        $prefix = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/LWT';
        if (stripos($topic, $prefix) !== false) {
            $this->HandleData('LWT', $payload);
            return '';
        }
        // Check whether the topic begins with a specific prefix
        $prefix = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/state/';
        if (stripos($topic, $prefix) === false) {
            $this->LogDebug(__FUNCTION__, 'Topic does not match');
            return '';
        }
        // Truncate prefix of the topic
        $topic = substr($topic, strlen($prefix));
        $this->HandleData($topic, $payload);
        return '';
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
     * @param int   $timestamp Continuous counter timestamp
     * @param int   $sender    Sender ID
     * @param int   $message   ID of the message
     * @param array{0:mixed,1:bool,2:mixed,3:int} $data Data of the message
     * @return void
     */
    public function MessageSink(int $timestamp, int $sender, int $message, array $data): void
    {
        // No connection
        $status = $this->GetValue('Status');
        if (!$status) return;
        // Debug
        $this->LogDebug(__FUNCTION__, 'SenderId: ' . $sender . ' Data: ' . $this->Stringify($data));
        // React to updates
        if ($message == VM_UPDATE) {
            // only if values changed!
            if ($data[1] == true) {
                // Dark Mode activation
                if ($this->ReadPropertyInteger('AutoDarkModeVariable') == $sender) {
                    $idle = GetValue($this->GetIDForIdent('Idle'));
                    $this->SetBacklight(($idle == 0) ? 'off' : (($idle == 1) ? 'short' : 'long'));
                }
                if ($this->ReadAttributeBoolean('SyncData')) {
                    $objects = json_decode($this->ReadPropertyString('Objects'), true);
                    // Iterate over all objects
                    foreach ($objects as $item => $object) {
                        if ($object['Link'] != $sender) {
                            continue;
                        }
                        $this->LogDebug(__FUNCTION__, $this->Stringify($object));
                        // Process data to specific object
                        $this->ProcessData($object, $data[0]);
                    }
                }
            }
        }
    }

    /**
     * Send Command to display.
     *
     * @param string $command Command name/data
     *
     * @return void
     */
    public function SendCommand(string $command): void
    {
        $mqttTopic = self::RD_PREFIX_TOPIC . $this->ReadPropertyString('Hostname') . '/command/';
        $this->LogDebug(__FUNCTION__, 'Topic: ' . $mqttTopic . ' Command: ' . $command);
        $this->SendMQTT($mqttTopic, $command);
    }

    /**
     * Send JSON Lines to display.
     *
     * @param array<string,mixed> $data JSONL array
     *
     * @return void
     */
    public function SendJSONL(array $data): void
    {
        $json = str_replace('\\\\', '\\', json_encode($data, JSON_UNESCAPED_SLASHES));
        $this->SendDebug(__FUNCTION__, $json, 0);
        $this->SendCommand('jsonl ' . $json);
    }

    /**
     * Disable idle mode.
     *
     * @param bool $disable Disable idle mode
     *
     * @return void
     */
    public function DisableIdle(bool $disable): void
    {
        $this->WriteAttributeBoolean('DisableIdle', $disable);
        $this->ProcessIdle();
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     *
     * @return void
     */
    protected function ProcessHookData(): void
    {
        $this->LogDebug(__FUNCTION__, $_GET);
        $file = $_GET['file'] ?? '';
        $filename = '';
        $contenttype = '';
        $download = '';
        $ip = $this->ReadPropertyString('IP');
        // Download the file
        if (empty($ip) && ($file != 'objects')) {
            $this->EchoMessage('No IP adress filed!');
            return;
        }
        // Prepeare file output
        switch ($file) {
            case 'objects':
                $filename = 'objects.json';
                $contenttype = 'Content-Type: application/json; charset=utf-8';
                $download = $_GET['list'] ?? '';
                break;
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
        // Download it
        if ($file != 'objects') {
            $url = 'http://' . $ip . '/' . $filename . '?download=true';
            $this->LogDebug(__FUNCTION__, $url);
            $download = file_get_contents($url);
            if ($download === false) {
                $this->EchoMessage('Error during download file!');
                return;
            }
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
     * @return bool true = disabled, false = enabled
     */
    protected function ProcessIdle(): bool
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
     * @param string $topic Topic name
     * @param string $payload Payload data
     *
     * @return string Result of the call
     */
    protected function SendMQTT(string $topic, string $payload): string
    {
        $resultServer = true;
        $resultClient = true;
        // MQTT Server
        $server['DataID'] = self::GUID_MQTT_TX;
        $server['PacketType'] = 3;
        $server['QualityOfService'] = 0;
        $server['Retain'] = false;
        $server['Topic'] = $topic;
        $server['Payload'] = bin2hex($payload);
        $json = json_encode($server, JSON_UNESCAPED_SLASHES);
        //$this->LogDebug(__FUNCTION__.'MQTT Server', $json, 0);
        $resultServer = @$this->SendDataToParent($json);

        return $resultServer;
    }

    /**
     * Generate a message that updates all elements in the HTML display.
     *
     * @return string JSON encoded message information
     */
    private function GetFullUpdateMessage(): string
    {
        // dataset variable
        $idle = $this->GetValue('Idle');
        $status = $this->GetValue('Status');
        $brightness = $this->GetValue('Backlight');
        $page = $this->GetValue('Page');
        $online = $this->GetColorFormatted($this->ReadPropertyInteger('VisuOnColor'));
        $offline = $this->GetColorFormatted($this->ReadPropertyInteger('VisuOffColor'));
        $navi = $this->ReadPropertyBoolean('VisuNaviBar');
        $action = $this->ReadPropertyBoolean('VisuActionBar');

        // Data
        $result = [
            'status'        => ($status ? 'online' : 'offline'),
            'idle'          => ($idle),
            'brightness'    => ($brightness),
            'page'          => ($page),
            'navi'          => ($navi ? 'yes' : 'no'),
            'action'        => ($action ? 'yes' : 'no'),
            'online'        => ($online),
            'offline'       => ($offline)
        ];
        //$this->LogDebug(__FUNCTION__, $result, 0);
        return json_encode($result);
    }

    /**
     * Set a specific item property.
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param string $property Property name
     * @param string $value Property value
     *
     * @return void
     */
    private function SetItemProperty(int $page, int $objectId, string $property, string $value): void
    {
        $this->SendCommand('p' . $page . 'b' . $objectId . '.' . $property . '=' . $value);
    }

    /**
     * Set item value (numeric).
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param int $value Property Value
     *
     * @return void
     */
    private function SetItemValue(int $page, int $objectId, int $value): void
    {
        $this->SendCommand('p' . $page . 'b' . $objectId . '.val=' . $value);
    }

    /**
     * Set item text (label, caption).
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param string $value Property Value
     *
     * @return void
     */
    private function SetItemText(int $page, int $objectId, string $value): void
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.text=' . $value . '"]');
    }

    /**
     * Set item value string.
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param string $value Property Value
     *
     * @return void
     */
    private function SetItemValStr(int $page, int $objectId, string $value): void
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.value_str=' . $value . '"]');
    }

    /**
     * Set item src (image).
     *
     * @param int $page Page Number (1..12)
     * @param int $objectId UI Object ID
     * @param string $value Property Value
     *
     * @return void
     */
    private function SetItemSrc(int $page, int $objectId, string $value): void
    {
        $this->SendCommand('["' . 'p' . $page . 'b' . $objectId . '.src=' . $value . '"]');
    }

    /**
     * Set display backlight via staet and brightness.
     *
     * @param string $data idle state (short, long or off)
     *
     * @return void
     */
    private function SetBacklight(string $data): void
    {
        $state = 'on';
        $prefix = 'Auto';
        $dmv = $this->ReadPropertyInteger('AutoDarkModeVariable');
        if ($dmv >= self::IPS_MIN_ID) {
            if (!GetValue($dmv)) {
                $prefix .= 'Dark';
            }
        }
        $brightness = $this->ReadPropertyInteger($prefix . ucfirst($data) . 'Idle');
        // adjust state & brigthness
        if ($brightness == 0) {
            $state = 'off';
        }
        $this->SendCommand('backlight {"state":"' . $state . '","brightness":' . $brightness . '}');
    }

    /**
     * Switch to page on idle
     *
     * @return void
     */
    private function SetIdle(): void
    {
        // Default fixed page
        $page = $this->ReadPropertyInteger('PageOnIdle');
        // Dynamic page
        $var = $this->ReadPropertyInteger('PageOnIdleDynamic');
        if ($var >= self::IPS_MIN_ID) {
            $page = GetValue($var);
        }
        // Branch page
        $var = $this->ReadPropertyInteger('PageOnIdleBranch');
        if ($var >= self::IPS_MIN_ID) {
            $switch = GetValue($var);
            if ($switch) {
                $page = $this->ReadPropertyInteger('PageOnIdleTrue');
            } else {
                $page = $this->ReadPropertyInteger('PageOnIdleFalse');
            }
        }
        $this->SendCommand('page=' . $page);
    }

    /**
     * Check all register objects.
     *
     * @return bool true = all ok, false = problems
     */
    private function RegisterObjects(): bool
    {
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        if ($objects == null) {
            $objects = [];
        }
        //$this->LogDebug(__FUNCTION__, $this->DebugPrint($objects));

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
            //$this->LogDebug(__FUNCTION__, $this->DebugPrint($object));
            if ($object['Link'] != 1) {
                // Objekt muss existiert!
                if (IPS_ObjectExists($object['Link'])) {
                    $type = IPS_GetObject($object['Link'])['ObjectType'];
                    // only 2(Variable) and 3(Script)
                    if ($type == 2) {
                        // Variables is supported for everyone
                        $this->RegisterMessage($object['Link'], VM_UPDATE);
                    } elseif ($type == 3) {
                        if (($object['Type'] == self::UI_BUTTOM) ||
                            ($object['Type'] == self::UI_CHECKBOX) ||
                            ($object['Type'] == self::UI_DROPDOWN) ||
                            ($object['Type'] == self::UI_MESSAGE) ||
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
                }
                else {
                    $msg = $this->Translate('The assigned object #%d for page %d with id %d does not exist!');
                    $msg = sprintf($msg, $object['Link'], $object['Page'], $object['Id']);
                    $this->LogMessage($msg, KL_WARNING);
                    $state = false;
                }
            }
        }
        // Register Script & DarkMode
        $script = $this->ReadPropertyInteger('ForwardMessageScript');
        if (IPS_ScriptExists($script)) {
            $this->RegisterReference($script);
        }
        $variable = $this->ReadPropertyInteger('AutoDarkModeVariable');
        if (IPS_VariableExists($variable)) {
            $this->RegisterReference($variable);
            $this->RegisterMessage($variable, VM_UPDATE);
        }
        // Return status
        return $state;
    }

    /**
     * Process map data to object.
     *
     * @param array<string, mixed> $object The mapping object
     * @param mixed $data The passed data
     *
     * @return void
     */
    private function ProcessData(array $object, mixed $data): void
    {
        $this->LogDebug(__FUNCTION__, 'Data: ' . $data . ' (' . gettype($data) . ')');
        $formatted = @GetValueFormatted($object['Link']);
        // Calculate IPS value to object value
        $value = $this->EvaluateString($object['Calculation'], $data, $formatted);
        // Debug
        $this->LogDebug(__FUNCTION__, $this->GetType($object['Type']) . ' :' . $this->Stringify($value));
        // Arc || LineMeter
        if (($object['Type'] == self::UI_ARC) || ($object['Type'] == self::UI_METER)) {
            if ($object['Caption'] == '') {
                // If the caption is empty, the value is written directly.
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText(strval($value)));
            } else {
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText(strval($text)));
            }
            if ($object['Value'] == '') {
                // If the caption is empty, the value is written directly.
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            } else {
                $value = $this->EvaluateString($object['Value'], $value, $formatted);
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            }
        }
        // Dropdown || Gauge ||Switch
        if (($object['Type'] == self::UI_DROPDOWN) ||
            ($object['Type'] == self::UI_GAUGE) ||
            ($object['Type'] == self::UI_SWITCH)) {
            // Write "val" property
            if ($object['Value'] != '') {
                $value = $this->EvaluateString($object['Value'], $value, $formatted);
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
                $text = $this->EvaluateString($object['Value'], $value, $formatted);
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
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // Bar
        if ($object['Type'] == self::UI_BAR) {
            // Text for Bar
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemProperty($object['Page'], $object['Id'], 'bg_color10', $text);
                $this->SetItemProperty($object['Page'], $object['Id'], 'bg_grad_color10', $text);
            }
            if ($object['Value'] == '') {
                // If the value is empty, the value is written directly.
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            } else {
                $value = $this->EvaluateString($object['Value'], $value, $formatted);
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            }
        }
        // Button
        if ($object['Type'] == self::UI_BUTTOM) {
            // Text for Button
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // Checkbox
        if ($object['Type'] == self::UI_CHECKBOX) {
            $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            // Text for Checkbox
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // Messagebox
        if ($object['Type'] == self::UI_MESSAGE) {
            $opt = ['OK'];
            // Buttons for MessageBox (default: OK)
            if ($object['Value'] != '') {
                $opt = $this->EvaluateString($object['Value'], $value, $formatted);
            }
            // Text for Messagebox
            if ($object['Caption'] != '') {
                $close = $this->ReadPropertyInteger('AutoClosePopup') * 1000;
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $msg = ['page' => $object['Page'], 'id' => $object['Id'], 'obj' => 'msgbox', 'text' => $text, 'options' => $opt, 'auto_close' => $close];
                $this->SendJSONL($msg);
            }
        }
        // Slider
        if ($object['Type'] == self::UI_SLIDER) {
            $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            // Text for Slider
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // Spinner
        if ($object['Type'] == self::UI_SPINNER) {
            if ($object['Caption'] != '') {
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText($text));
            }
            if ($object['Value'] != '') {
                $value = intval($this->EvaluateString($object['Value'], $value, $formatted));
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
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemText($object['Page'], $object['Id'], $this->EncodeText($text));
            }
        }
        // LED Indicator
        if ($object['Type'] == self::UI_LED) {
            $var = IPS_GetVariable($object['Link']);
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
                $text = $this->EvaluateString($object['Caption'], $value, $formatted);
                $this->SetItemValStr($object['Page'], $object['Id'], $this->EncodeText($text));
            }
            if ($object['Value'] != '') {
                $text = $this->EvaluateString($object['Value'], $value, $formatted);
                $this->SetItemProperty($object['Page'], $object['Id'], 'bg_color', $text);
            }
        }
        // Roller
        if ($object['Type'] == self::UI_ROLLER) {
            if ($object['Value'] != '') {
                $value = $this->EvaluateString($object['Value'], $value, $formatted);
                $this->SetItemValue($object['Page'], $object['Id'], intval($value));
            }
        }
    }

    /**
     * Handle received data to object
     *
     * @param string $topic Topic ID
     * @param string $data Payload data
     *
     * @return void
     */
    private function HandleData(string $topic, string $data): void
    {
        $this->LogDebug(__FUNCTION__, 'Topic: ' . $topic . ' ,Payload: ' . $data);
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        // Is idle?
        if ($topic == 'idle') {
            if ($this->ReadPropertyBoolean('AutoDimBacklight')) {
                $this->LogDebug(__FUNCTION__, 'SetBacklight');
                $this->SetBacklight($data);
            }
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
                    $this->SetTimerInterval('SwitchPageTimer', 0);
                    $this->SetTimerInterval('ClockTimer', 0);
                    if (!$this->ReadAttributeBoolean('SyncData')) {
                        $this->LogDebug(__FUNCTION__, 'Synchronize()');
                        $this->Synchronize();
                    }
                    $this->WriteAttributeBoolean('SyncData', true);
            }
            if ($data == 'long') {
                if ($this->ReadPropertyBoolean('AutoShutdownBacklight')) {
                    $this->SetTimerInterval('AntiburnTimer', 60 * 1000 * $this->ReadPropertyInteger('AutoAntiburnCycle'));
                }
                if ($this->ReadPropertyBoolean('PageOneOnIdle')) {
                    $this->SetIdle();
                }
                if ($this->ReadPropertyBoolean('AutoSwitchPage')) {
                    $this->SetTimerInterval('SwitchPageTimer', 60 * 1000 * $this->ReadPropertyInteger('AutoSwitchInterval'));
                }
                if ($this->ReadPropertyBoolean('SyncOnIdle')) {
                    $this->WriteAttributeBoolean('SyncData', false);
                }
                if ($this->ReadPropertyBoolean('ClockCheck') || $this->ReadPropertyBoolean('EarthCheck') || $this->ReadPropertyBoolean('FlipCheck') || $this->ReadPropertyBoolean('HaspCheck')) {
                    $this->TickClock(true);
                }
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
                $this->LogDebug(__FUNCTION__, 'No registered object!');
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
                $script = -1;
                if ($object['Recalculation'] != '') {
                    if ($object['Type'] == self::UI_MESSAGE) {
                        $script = $object['Recalculation'];
                    } else {
                        $value = $this->EvaluateString($object['Recalculation'], $value, '', $text);
                    }
                }
                // Type & Value & Text
                $this->LogDebug(__FUNCTION__, $this->GetType($object['Type']) . ': ' . $this->Stringify($value) . ', ' . $text);
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
                        $this->LogDebug(__FUNCTION__, 'IPS_RunScriptEx(' . $object['Link'] . ', [VALUE=>' . $value . ',TEXT=>' . $text . '])');
                    }
                    else {
                        if (HasAction($object['Link']) && $value != -1) {
                            RequestAction($object['Link'], $value);
                            $this->LogDebug(__FUNCTION__, 'RequestAction(' . $object['Link'] . ', ' . $value . ')');
                        }
                        elseif ($value != -1) {
                            SetValue($object['Link'], $value);
                            $this->LogDebug(__FUNCTION__, 'SetValue(' . $object['Link'] . ', ' . $value . ')');
                        }
                        else {
                            $this->LogDebug(__FUNCTION__, 'No return to object: ' . $object['Link']);
                        }
                    }
                }
                // Mesagebox (Button down)
                if ($object['Type'] == self::UI_MESSAGE && $data->event == self::EH_UP) {
                    if (IPS_ScriptExists($script)) {
                        IPS_RunScriptEx($script, ['VALUE' => $value, 'TEXT' => $text]);
                        $this->LogDebug(__FUNCTION__, 'IPS_RunScriptEx(' . $script . ', [VALUE=>' . $value . ',TEXT=>' . $text . '])');
                    }
                }
            }

            if (property_exists($data, 'val') && ($object['Link'] != 1)) {
                $value = $data->val;
                // Received Typ = Arc & Value
                if ($object['Type'] == self::UI_ARC) {
                    if (HasAction($object['Link']) && $value != -1) {
                        RequestAction($object['Link'], $value);
                        $this->LogDebug(__FUNCTION__, 'RequestAction():' . $object['Link'] . ' Value: ' . $value);
                    }
                    elseif ($value != -1) {
                        SetValue($object['Link'], $value);
                        $this->LogDebug(__FUNCTION__, 'SetValue(' . $object['Link'] . ', ' . $value . ')');
                    }
                    else {
                        $this->LogDebug(__FUNCTION__, 'No return to object: ' . $object['Link']);
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
            $this->LogDebug(__FUNCTION__, 'Status: ' . $data);
            //$this->StatusUpdate(false);
        }

        if ($topic == 'moodlight') {
            $this->WriteAttributeString('MoodLight', $data);
            $this->LogDebug(__FUNCTION__, 'Moodlight: ' . $data);
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
     *
     * @return void
     */
    private function Antiburn(bool $value): void
    {
        // Backlights
        $prefix = 'Auto';
        $dmv = $this->ReadPropertyInteger('AutoDarkModeVariable');
        if ($dmv >= self::IPS_MIN_ID) {
            if (!GetValue($dmv)) {
                $prefix .= 'Dark';
            }
        }
        $long = $this->ReadPropertyInteger($prefix . 'LongIdle');
        $anti = $this->ReadPropertyInteger('AutoAntiburnBacklight');

        if ($value) {
            $this->LogDebug(__FUNCTION__, 'Antiburn ON');
            if (($anti < $long) && ($anti != 0)) {
                $this->SendCommand('backlight=' . $anti);
                $this->SetTimerInterval('AntiburnLight', 35 * 1000);
            }
            $this->SendCommand('antiburn=on');
        } else {
            $this->LogDebug(__FUNCTION__, 'Antiburn OFF');
            $this->SetTimerInterval('AntiburnLight', 0);
            if (($anti < $long) && ($anti != 0)) {
                $this->SendCommand('backlight=' . $long);
            }
        }
    }

    /**
     * Online State (LWT).
     *
     * @return void
     */
    private function Online(): void
    {
        $this->LogDebug(__FUNCTION__, 'Display is online');
        // Sync linked objects with the device objects
        $this->Synchronize();
    }

    /**
     * Status Update - display status information.
     *
     * @param bool $value True to show status info, otherwise false
     *
     * @return void
     */
    private function StatusUpdate(bool $value): void
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
     *
     * @return void
     */
    private function MoodLight(bool $value): void
    {
        $this->SendCommand('moodlight');
        if ($value) {
            $info = $this->ReadAttributeString('MoodLight');
            $this->EchoMessage($this->PrettyPrint(self::RD_MOOD_LIGHT, $info));
        }
    }

    /**
     * Synchronize from IPS variables to design objects.
     *
     * @return void
     */
    private function Synchronize(): void
    {
        $this->LogDebug(__FUNCTION__, 'Synchronize');
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        // iterate over all objects
        foreach ($objects as $item => $object) {
            if ($object['Link'] == 1 || $object['Calculation'] == -1) {
                continue;
            }
            if (IPS_ObjectExists($object['Link']))
                if (IPS_GetObject($object['Link'])['ObjectType'] == 2) {
                    // get actual value
                    $value = GetValue($object['Link']);
                    $this->LogDebug(__FUNCTION__, 'ID: ' . $object['Link'] . ' => ' . $value);
                    if ($object['Type'] != self::UI_MESSAGE) {
                        // process data to specific object
                        $this->ProcessData($object, $value);
                    }
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
     *
     * @return void
     */
    private function UpdateLayout(string $value, bool $save): void
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
            $this->LogDebug(__FUNCTION__, $json);
            curl_close($curl);
        } else {
            $filename = 'pages.jsonl';
            $url = 'http://' . $ip . '/' . $filename . '?download=true';
            $this->LogDebug(__FUNCTION__, $url);
            $download = file_get_contents($url);
            $this->UpdateFormField('Layout', 'value', $download);
        }
    }

    /**
     * Duplicate a entry and or sort the objects list.
     *
     * @param string $value json encoded list plus index
     * @param bool $copy flag if also copy a entry
     *
     * @return void
     */
    private function UpdateMapping(string $value, bool $copy): void
    {
        $list = json_decode($value, true);

        // duplicate/copy
        if ($copy) {
            // how many lines in the list?
            $count = count($list);
            $dup = 0;
            for ($index = 0; $index < $count; $index++) {
                if ($list[$index]['_']) {
                    $object = $list[$index];
                    // delete selection flag from original
                    $list[$index]['_'] = false;
                    // copy to the end
                    $list[] = $object;
                    $dup++;
                }
            }
            if ($dup == 0) {
                $this->EchoMessage('No entry selected from the object list!');
                return;
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
        $this->LogDebug(__FUNCTION__, $this->Stringify($list));
        $this->UpdateFormField('Objects', 'values', json_encode($list));
    }

    /**
     * Try to check the (re-)calulation eval statements
     *
     * @param string $value JSON structure of a selected object mapping
     *
     * @return void
     */
    private function CheckMapping(string $value): void
    {
        $lines = json_decode($value, true);
        if (empty($lines)) {
            $this->EchoMessage('No entry selected from the object list!');
            return;
        }
        $count = count($lines);
        // run over the entries
        $msg = $this->Translate("Value of the link:\t\t\t\t%s\nFormatted value:\t\t\t\t%s\nText default value:\t\t\tTXT\n\nValue after calculation:\t\t%s\nEvaluation of the calculation:\t%s\n\nValue (of value):\t\t\t\t%s\nEvaluation of value:\t\t\t%s\n\nValue of caption:\t\t\t\t%s\nEvaluation of caption:\t\t\t%s\n\nValue after recalculation:\t\t%s\nEvaluation of recalculation:\t%s");
        $lastmsg = '';
        $listmsg = '';
        foreach ($lines as $line) {
            if ($line['Link'] < self::IPS_MIN_ID) {
                $lastmsg = 'Hint (Entry does not contain a linked variable!)';
                $listmsg = $listmsg . $this->StringPrint($line, $this->Translate('Hint (No linked object!)'));
                continue;
            }
            if (IPS_ObjectExists($line['Link'])) {
                // 3(Script)
                if (IPS_GetObject($line['Link'])['ObjectType'] == 3) {
                    $lastmsg = 'Hint (The linked object is a script!)';
                    $listmsg = $listmsg . $this->StringPrint($line, $this->Translate('Hint (Object is of type script!)'));
                    continue;
                }
            } else {
                $lastmsg = 'Hint (The linked object no longer exists!)';
                $listmsg = $listmsg . $this->StringPrint($line, $this->Translate('Hint (Object no longer exists!)'));
                continue;
            }
            // Value für {{val}}
            $value = GetValue($line['Link']);
            // Value für {{fmt}}
            $formatted = GetValueFormatted($line['Link']);
            // Text für {{txt}}
            $text = 'TXT';
            // Calculation
            $ecal = 'ok';
            $cal = $value;
            if ($line['Calculation'] != '') {
                $cal = $this->EvaluateString($line['Calculation'], $value, $formatted, $text, $ecal);
            }
            // Value
            $eval = 'ok';
            $val = $cal;
            if ($line['Value'] != '') {
                $val = $this->EvaluateString($line['Value'], $cal, $formatted, $text, $eval);
            }
            // Caption
            $etxt = 'ok';
            $txt = '';
            if ($line['Caption'] != '') {
                $txt = $this->EvaluateString($line['Caption'], $cal, $formatted, $text, $etxt);
            }
            // Recalculation
            $erec = 'ok';
            $rec = $val;
            if ($line['Recalculation'] != '') {
                $rec = $this->EvaluateString($line['Recalculation'], $val, $formatted, $text, $erec);
            }
            $lastmsg = sprintf($msg, $value, $formatted, $cal, $ecal, $val, $eval, $txt, $etxt, $rec, $erec);
            if (stripos($lastmsg, 'error') !== false) {
                $listmsg = $listmsg . $this->StringPrint($line, $this->Translate('Error'));
            }
        }
        // Result
        if ($count == 1) {
            $listmsg = $lastmsg;
        } elseif (empty($listmsg)) {
            $listmsg = 'Successful (No errors found!)';
        }
        $this->LogDebug(__FUNCTION__, $listmsg);
        $this->EchoMessage($listmsg);
    }

    /**
     * Match all entries in the objects list agains the page definition.
     *
     * @param string $value json encoded list
     *
     * @return void
     */
    private function MatchMapping(string $value): void
    {
        $list = json_decode($value, true);

        // read page layout and generate json array
        $lines = explode("\n", trim($this->ReadPropertyString('Layout')));
        $data = [];
        foreach ($lines as $line) {
            // skip empty lines if they exist in the string
            if (trim($line) === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if ($decoded !== null) { // Skip invalid JSON lines
                $data[] = $decoded;
            } else {
                $this->LogDebug(__FUNCTION__, json_last_error_msg() . ' => ' . $line);
            }
        }
        // go through object and look if it exist in page layout
        $nomatch = '';
        foreach ($list as $obj) {
            // search values
            $values = ['page' => $obj['Page'], 'id' => $obj['Id']];
            if ($this->HasSpecificValues($data, $values) == -1) {
                $nomatch .= '[Page: ' . $obj['Page'] . ', Id: ' . $obj['Id'] . '], ';
            }
        }
        $msg = 'All objects are available in the page layout!';
        if (!empty($nomatch)) {
            $msg = $this->Translate("The following objects do not exist in the page layout:\n\n");
            $msg .= $nomatch;
        }
        $this->EchoMessage($msg);
    }

    /**
     * Transfer a list of entries between instances.
     *
     * @param string $value json encoded list (file content)
     *
     * @return void
     */
    private function TransferMapping(string $value): void
    {
        $json = base64_decode($value);
        $list = json_decode($json, true);
        $this->LogDebug(__FUNCTION__, json_last_error_msg());
        $this->LogDebug(__FUNCTION__, $this->Stringify($list));

        if (!empty($list)) {
            $objects = json_decode($this->ReadPropertyString('Objects'), true);
            $list = array_merge($objects, $list);
            $this->UpdateFormField('Objects', 'values', json_encode($list));
        }
    }

    /**
     * Re-invert selection of all entries in the objects list.
     *
     * @param string $value json encoded list
     *
     * @return void
     */
    private function SelectMapping(string $value): void
    {
        $list = json_decode($value, true);

        $select = true;
        if (!empty($list)) {
            $select = !$list[0]['_'];
        }
        foreach ($list as &$object) {
            $object['_'] = $select;
        }
        unset($object);
        //$this->LogDebug(__FUNCTION__, $this->Stringify($list));
        $this->UpdateFormField('Objects', 'values', json_encode($list));
    }

    /**
     * Deletes selection of all entries in the objects list.
     *
     * @param string $value json encoded list
     *
     * @return void
     */
    private function DeleteMapping(string $value): void
    {
        $list = json_decode($value, true);
        $del = 0;
        foreach ($list as $key => $object) {
            if ($object['_']) {
                unset($list[$key]); // remove element
                $del++;
            }
        }
        if ($del == 0) {
            $this->EchoMessage('No entry selected from the object list!');
            return;
        }
        $list = array_values($list);
        $this->UpdateFormField('Objects', 'values', json_encode($list));
    }

    /**
     * Validate the passed page layout jsonl.
     *
     * @param string $value Layout as JSONL
     * @param bool $echo If true popup message, otherwise silence
     *
     * @return bool True if every line is a valid JSON object; otherwise false.
     */
    private function ValidateLayout(string $value, bool $echo): bool
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
            json_decode($line);
            $valid = json_last_error() === JSON_ERROR_NONE;
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
     * Parse the page layout and cratse the assoziated object mapping entries.
     *
     * @param string $value Serialised parse settings (new, exist, delete)
     *
     * @return void
     */
    private function ParseLayout(string $value): void
    {
        // parse option
        $options = unserialize($value);
        $this->LogDebug(__FUNCTION__, $this->Stringify($options));

        // read page layout and generate json array
        $layout = explode("\n", trim($this->ReadPropertyString('Layout')));
        $lines = [];
        foreach ($layout as $line) {
            // skip empty lines if they exist in the string
            if (trim($line) === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if ($decoded !== null) { // Skip invalid JSON lines
                $lines[] = $decoded;
            } else {
                $this->LogDebug(__FUNCTION__, json_last_error_msg() . ' => ' . $line);
            }
        }

        // object list
        $objects = json_decode($this->ReadPropertyString('Objects'), true);
        $this->LogDebug(__FUNCTION__, $this->Stringify($objects));

        // process each line again object list
        $unsupported = $changed = $deleted = $added = 0;
        foreach ($lines as $line) {
            $this->LogDebug(__FUNCTION__, 'LINE: ' . $this->Stringify($line));
            // only object lines
            if (!isset($line['obj'])) {
                $this->LogDebug(__FUNCTION__, 'SKIP: ' . $this->Stringify($line));
                continue;
            }
            // ui-object identify
            $type = $this->SetType($line['obj']);
            if ($type == self::UI_BUTTOM) {
                // Special case toggle button
                if (isset($line['toggle']) && $line['toggle']) {
                    $type = self::UI_TOGGLE;
                }
            }
            $support = $this->SupportedType($type);
            // Is supported?
            if (!$support) {
                $this->LogDebug(__FUNCTION__, 'NOT SUPPORTED: ' . $this->Stringify($line));
                $unsupported++;
                continue;
            }
            // Object still exists?
            $values = ['Page' => $line['page'], 'Id' => $line['id']];
            $index = $this->HasSpecificValues($objects, $values);
            $this->LogDebug(__FUNCTION__, 'Index: ' . $index);
            // new object
            if (($index == -1) && $options['new']) {
                // Objects with own actions?
                if (isset($line['action'])) {
                    continue;
                }
                $objects[] = ['Page' => $line['page'], 'Id' => $line['id'], 'Type' => $type];
                $this->LogDebug(__FUNCTION__, 'NEW: ' . $this->Stringify($line));
                $added++;
            }
            // change object
            if (($index != -1) && $options['change']) {
                if ($objects[$index]['Type'] != $type) {
                    $objects[$index]['Type'] = $type;
                    $this->LogDebug(__FUNCTION__, 'CHANGE: ' . $this->Stringify($line));
                    $changed++;
                }
            }
        }
        if ($options['delete']) {
            foreach ($objects as $key => $object) {
                // search values
                $values = ['page' => $object['Page'], 'id' => $object['Id']];
                if ($this->HasSpecificValues($lines, $values) == -1) {
                    $this->LogDebug(__FUNCTION__, 'DELETE: ' . $this->Stringify($object));
                    unset($objects[$key]); // remove element
                    $deleted++;
                }
            }
            // restore complete numerical indexing
            if ($deleted != 0) {
                $objects = array_values($objects);
            }
        }
        // do it really?
        if ($options['simulate']) {
            $this->LogDebug(__FUNCTION__, 'Simulation run!!!');
        } else {
            $this->LogDebug(__FUNCTION__, $this->Stringify($objects));
            $this->UpdateFormField('Objects', 'values', json_encode($objects));
        }
        // Result output
        $msg = $this->Translate("The import ran with the following result:\n\n\tAdded:\t\t\t%d\n\tCorrected:\t\t%d\n\tDeleted:\t\t\t%d\n\tNot supported:\t%d");
        $this->EchoMessage(sprintf($msg, $added, $changed, $deleted, $unsupported));
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
    private function EvaluateString(string $subject, mixed $value, string $formatted = '', string $text = '', string &$error = 'ok')
    {
        // sprintf
        if ((strlen($subject) != 0) && (strpos($subject, '{{') === false)) {
            // sprintf: %s for string, %d for integer %f for float, %% to write a “%”
            $ret = sprintf($subject, $value);
            $this->LogDebug(__FUNCTION__, 'sprintf: ' . $ret . ' <= ' . $subject);
            return $ret;
        }
        // bool to string is bad (empty for false)
        if (is_bool($value)) {
            $value = intval($value);
        }
        // eval - empty(0) is true :(
        if (strlen($subject) != 0) {
            $eval = str_replace(self::PH_VALUE, strval($value), $subject);
            $eval = str_replace(self::PH_TEXT, $text, $eval);
            $eval = str_replace(self::PH_FORMAT, $formatted, $eval);
            $eval = 'return (' . $eval . ');';
            $this->LogDebug(__FUNCTION__, 'eval: ' . $eval);
            try {
                $code = @eval($eval);
                if ($code === false) {
                    $code = '';
                }
            } catch (ParseError $e) {
                // Report error somehow
                $error = 'Error (' . $e->GetMessage() . ')';
                $this->LogDebug(__FUNCTION__, 'RD Value: ' . $value . ',RD Type: ' . gettype($value) . ',RD Error: ' . $e->GetMessage() . ',RD Eval: ' . $eval . ',RD Subject: ' . $subject);
                $code = '';
            } catch (Throwable $t) {
                // Report error somehow
                $error = 'Error (' . $t->GetMessage() . ')';
                $this->LogDebug(__FUNCTION__, 'RD Value: ' . $value . ',RD Type: ' . gettype($value) . ',RD Error: ' . $t->GetMessage() . ',RD Eval: ' . $eval . ',RD Subject: ' . $subject);
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
        $this->LogDebug(__FUNCTION__, $encoded);
        return $encoded;
    }

    /**
     * Format a single line for string output.
     *
     * @param array<string,mixed> $data Object data
     * @param string $result Result text
     *
     * @return string Formatted line
     */
    private function StringPrint(array $data, string $result): string
    {
        $line = '[' . $data['Page'] . ',' . $data['Id'] . ']';
        $len = strlen($line);
        switch ($len) {
            case 5: $line .= "\t\t\t";
                break;
            case 6:
            case 7: $line .= "\t\t";
                break;
            default:
                $line .= "\t\t";
        }
        $mid = $this->GetType($data['Type']);
        $len = strlen($mid);
        switch ($len) {
            case 3:
            case 4: $mid .= "\t\t\t\t";
                break;
            case 5:
            case 6:
            case 7:
            case 8:
            case 9: $mid .= "\t\t\t";
                break;
            case 10: $mid .= "\t\t";
                break;
            default:
                $mid .= "\t";
        }
        return $line . $mid . $result . "\n";
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
            case self::UI_MATRIX: $name = 'Button Matrix';
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
            case self::UI_TAB: $name = 'Tab';
                break;
            case self::UI_TOGGLE: $name = 'Toggle Button';
                break;
            case self::UI_QRCODE: $name = 'QR-Code';
                break;
        }
        return $name;
    }

    /**
     * Retrieve UI object id of th textual type name.
     *
     * @param string $name Clear name of UI element.
     *
     * @return int ID of the UI Object.
     */
    private function SetType(string $name): int
    {
        $name = strtolower($name);
        $id = -1;
        switch ($name) {
            case 'arc': $id = self::UI_ARC;
                break;
            case 'bar': $id = self::UI_BAR;
                break;
            case 'btn': $id = self::UI_BUTTOM;
                break;
            case 'btnmatrix': $id = self::UI_MATRIX;
                break;
            case 'checkbox': $id = self::UI_CHECKBOX;
                break;
            case 'cpicker': $id = self::UI_COLOR;
                break;
            case 'dropdown': $id = self::UI_DROPDOWN;
                break;
            case 'gauge': $id = self::UI_GAUGE;
                break;
            case 'img': $id = self::UI_IMAGE;
                break;
            case 'label': $id = self::UI_LABEL;
                break;
            case 'led': $id = self::UI_LED;
                break;
            case 'line': $id = self::UI_LINE;
                break;
            case 'linemeter': $id = self::UI_METER;
                break;
            case 'msgbox': $id = self::UI_MESSAGE;
                break;
            case 'obj': $id = self::UI_OBJECT;
                break;
            case 'roller': $id = self::UI_ROLLER;
                break;
            case 'slider': $id = self::UI_SLIDER;
                break;
            case 'spinner': $id = self::UI_SPINNER;
                break;
            case 'switch': $id = self::UI_SWITCH;
                break;
            case 'tabview': $id = self::UI_TABS;
                break;
            case 'tab': $id = self::UI_TAB;
                break;
            case 'toggle': $id = self::UI_TOGGLE;
                break;
            case 'qrcode': $id = self::UI_QRCODE;
                break;
        }
        return $id;
    }

    /**
     * Retrieve the support state of UI object type.
     *
     * @param int $type ID of the UI Object
     * @return bool If supported type true, otherwise false.
     */
    private function SupportedType(int $type)
    {
        $support = false;
        switch ($type) {
            case self::UI_ARC:
            case self::UI_BAR:
            case self::UI_BUTTOM:
            case self::UI_CHECKBOX:
            case self::UI_DROPDOWN:
            case self::UI_GAUGE:
            case self::UI_IMAGE:
            case self::UI_LABEL:
            case self::UI_LED:
            case self::UI_METER:
            case self::UI_MESSAGE:
            case self::UI_OBJECT:
            case self::UI_ROLLER:
            case self::UI_SLIDER:
            case self::UI_SPINNER:
            case self::UI_SWITCH:
            case self::UI_TOGGLE:
                $support = true;
                break;
        }
        return $support;
    }

    /**
     * Get HTML rgb formated color.
     *
     * @param int $color Color value or -1 for transparency
     *
     */
    private function GetColorFormatted(int $color): string
    {
        if ($color != '-1') {
            return '#' . sprintf('%06X', $color);
        } else {
            return '';
        }
    }

    /**
     * Function to check whether an array with certain key-value pairs exists
     *
     * @param array<int,array<string,mixed>> $array Array with all page lines
     * @param array<string,mixed> $values Array of search values
     *
     * @return int Index if values found, otherwise -1.
     */
    private function HasSpecificValues(array $array, array $values): int
    {
        $index = 0;
        foreach ($array as $item) {
            $match = true;
            // check each key-value pair in values
            foreach ($values as $key => $value) {
                if (!isset($item[$key]) || $item[$key] !== $value) {
                    $match = false;
                    break;
                }
            }
            // if all key-value pairs match, return index number
            if ($match) {
                return $index;
            }
            $index++;
        }
        return -1;
    }

    /**
     * Parse page selection string into a integer array and switch then to the next page.
     *
     * @return void
     */
    private function PageSwitch(): void
    {
        $pages = [];
        // Remove spaces and divide the input at the commas
        $input = str_replace(' ', '', $this->ReadPropertyString('AutoSwitchSelection'));
        $parts = explode(',', $input);

        foreach ($parts as $part) {
            // Check whether there is a range (e.g. 3-5)
            if (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];
                if ($start <= $end) {
                    $pages = array_merge($pages, range($start, $end));
                } else {
                    $this->LogDebug(__FUNCTION__, 'Invalid range: ' . $part);
                    return;
                }
            } elseif (ctype_digit($part)) {
                // Add a single page
                $pages[] = (int) $part;
            } else {
                $this->LogDebug(__FUNCTION__, 'Invalid format: ' . $part);
                return;
            }
        }
        // Remove and sort duplicates
        $pages = array_unique($pages);
        sort($pages);
        $current = $this->GetValue('Page');
        foreach ($pages as $page) {
            if ($page > $current) {
                // First page that is larger than the current page
                $this->SendCommand('page=' . $page);
                return;
            }
        }
        // If no larger page was found, return to the first page
        $this->SendCommand('page=' . $pages[0]);
    }

    /**
     * Update time of the widget clock(s) if enabled.
     *
     * @param bool $interval Indicator if to calcolate the seconds to the next minute or not.
     *
     * @return void
     */
    private function TickClock(bool $interval): void
    {
        $this->LogDebug(__FUNCTION__, 'Interval: ' . boolval($interval));

        if ($this->ReadPropertyBoolean('ClockCheck')) {
            $page = $this->ReadPropertyInteger('ClockPage');
            $hour = $this->ReadPropertyInteger('ClockHour');
            $minute = $this->ReadPropertyInteger('ClockMinute');
            $this->AnalougeClock($page, $hour, $minute);
        }
        if ($this->ReadPropertyBoolean('EarthCheck')) {
            $page = $this->ReadPropertyInteger('EarthPage');
            $start = $this->ReadPropertyInteger('EarthStart');
            $color = $this->ReadPropertyInteger('EarthColor');
            $prefix = $this->ReadPropertyBoolean('EarthPrefix');
            $suffix = $this->ReadPropertyBoolean('EarthSuffix');
            $this->QlocktwoEarth($page, $start, $color, $prefix, $suffix);
        }
        if ($this->ReadPropertyBoolean('FlipCheck')) {
            $page = $this->ReadPropertyInteger('FlipPage');
            $hour = $this->ReadPropertyInteger('FlipHour');
            $minute = $this->ReadPropertyInteger('FlipMinute');
            $this->FlipClock($page, $hour, $minute);
        }

        if ($this->ReadPropertyBoolean('HaspCheck')) {
            $page = $this->ReadPropertyInteger('HaspPage');
            $minute = $this->ReadPropertyInteger('HaspMinute');
            $this->HaspClock($page, $minute);
        }

        $sec1min = 60;  // base 1 minute = 60 seconds
        if ($interval) {
            $sec1min = $sec1min - (int) date('s');
        }
        $this->SetTimerInterval('ClockTimer', $sec1min * 1000);
    }

    /**
     * Show message via popup.
     *
     * @param string $file Echo message text
     *
     * @return void
     */
    private function PageShow(string $file): void
    {
        $text = file_get_contents(__DIR__ . '/../docs/' . $file . '.jsonl');
        $this->UpdateFormField('JSONL', 'value', $text);
        $this->UpdateFormField('EchoText', 'visible', true);
    }

    /**
     * Show message via popup
     *
     * @param string $caption echo message
     *
     * @return void
     */
    private function EchoMessage(string $caption): void
    {
        $this->UpdateFormField('EchoMessage', 'caption', $this->Translate($caption));
        $this->UpdateFormField('EchoPopup', 'visible', true);
    }
}

<?php

declare(strict_types=1);

set_time_limit(0);
ini_set("display_errors", "on");
ini_set("error_reporting", (string)E_ALL);
date_default_timezone_set("UTC");

define("START_TIME", microtime(true));

// =========================================================================
// Configuration
// =========================================================================

$config = [
    "DEFAULT_NICK"              => "",
    "USER_NAME"                 => "",
    "FULL_NAME"                 => "",
    "PASSWORD_FILE"             => "",
    "BUCKETS_FILE"              => "",
    "IGNORE_FILE"               => "",
    "EXEC_FILE"                 => "",
    "INIT_CHAN_LIST"            => "",
    "IRC_HOST_CONNECT"          => "",
    "IRC_HOST"                  => "",
    "IRC_PORT"                  => "",
    "OPERATOR_ACCOUNT"          => "",
    "OPERATOR_HOSTNAME"         => "",
    "DEBUG_CHAN"                => "",
    "NICKSERV_IDENTIFY_PROMPT"  => "",
    "ADMIN_ACCOUNTS"            => "",
    "MYSQL_LOG"                 => "",
    "NICKSERV_IDENTIFY"         => "",
    "IFACE_ENABLE"              => "",
    "SSL_PEER_NAME"             => "",
    "SSL_CA_FILE"               => "",
    "BOT_SCHEMA"                => "",
    "LOG_TABLE"                 => "",
    "PHP_PATH"                  => "",
];

if (isset($argv[1])) {
    if (!file_exists($argv[1])) {
        fwrite(STDERR, "INVALID COMMAND LINE ARGUMENT\n");
        exit(1);
    }
    $lines = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $config[trim($parts[0])] = trim($parts[1]);
        }
    }
}

foreach ($config as $key => $val) {
    if (!defined($key)) {
        define($key, $val);
    }
}

if (MYSQL_LOG === "1" && file_exists("scripts/lib_mysql.php")) {
    require_once "scripts/lib_mysql.php";
}

// =========================================================================
// Constants
// =========================================================================

const EXEC_OUTPUT_BUFFER_FILE = "../data/exec_iface";
const EXEC_DELIM              = "|";
const EXEC_DIRECTIVE_DELIM    = " ";
const EXEC_INCLUDE            = "include";
const EXEC_INIT               = "init";
const EXEC_STARTUP            = "startup";
const EXEC_HELP               = "help";

const FILE_DIRECTIVE_DELIM   = ":";
const FILE_DIRECTIVE_EXEC    = "exec";
const FILE_DIRECTIVE_INIT    = "init";
const FILE_DIRECTIVE_STARTUP = "startup";
const FILE_DIRECTIVE_HELP    = "help";

const MAX_MSG_LENGTH          = 458;
const IGNORE_TIME             = 20.0;
const DELTA_TOLERANCE         = 1.5;
const TEMPLATE_DELIM          = "%%";
const DIRECTIVE_QUIT          = "<<quit>>";

const BUCKET_IGNORE_NEXT                 = "<<BOT_IGNORE_NEXT>>";
const BUCKET_USERS                       = "<<EXEC_USERS>>";
const BUCKET_EVENT_HANDLERS              = "<<EXEC_EVENT_HANDLERS>>";
const BUCKET_CONNECTION_ESTABLISHED      = "<<IRC_CONNECTION_ESTABLISHED>>";
const BUCKET_SELF_TRIGGER_EVENTS_FLAG    = "<<SELF_TRIGGER_EVENTS_FLAG>>";
const BUCKET_EXEC_LIST                   = "<<EXEC_LIST>>";
const BUCKET_BOT_NICK                    = "<<BOT_NICK>>";
const BUCKET_ADMIN_ACCOUNTS_LIST         = "<<ADMIN_ACCOUNTS_LIST>>";
const BUCKET_OPERATOR_ACCOUNT            = "<<OPERATOR_ACCOUNT>>";
const BUCKET_OPERATOR_HOSTNAME           = "<<OPERATOR_HOSTNAME>>";
const BUCKET_MEMORY_USAGE                = "<<BOT_MEMORY_USAGE>>";
const BUCKET_OUTPUT_CONTROL              = "<<OUTPUT_CONTROL>>";
const BUCKET_SHUTDOWN                    = "<<SHUTDOWN>>";
const BUCKET_PROCESS_TEMPLATE_PREFIX     = "process_template_";
const BUCKET_ALIAS_ELEMENT_PREFIX        = "alias_element_";

const ALIAS_ALL      = "*";
const ALIAS_INIT     = "<init>";
const ALIAS_STARTUP  = "<startup>";
const ALIAS_QUIT     = "<quit>";

const CMD_BUCKET_GET      = "BUCKET_GET";
const CMD_BUCKET_SET      = "BUCKET_SET";
const CMD_BUCKET_UNSET    = "BUCKET_UNSET";
const CMD_BUCKET_APPEND   = "BUCKET_APPEND";
const CMD_BUCKET_LIST     = "BUCKET_LIST";
const CMD_INTERNAL        = "INTERNAL";
const CMD_PAUSE           = "BOT_IRC_PAUSE";
const CMD_UNPAUSE         = "BOT_IRC_UNPAUSE";
const CMD_INIT            = "INIT";
const CMD_STARTUP         = "STARTUP";
const CMD_DELETE_HANDLER  = "DELETE_HANDLER";

const PREFIX_DELIM           = "/";
const PREFIX_IRC             = PREFIX_DELIM . "IRC";
const PREFIX_EXEC_ADD        = PREFIX_DELIM . "EXEC-ADD";
const PREFIX_EXEC_DEL        = PREFIX_DELIM . "EXEC-DEL";
const PREFIX_EXEC_SAVE       = PREFIX_DELIM . "EXEC-SAVE";
const PREFIX_PRIVMSG         = PREFIX_DELIM . "PRIVMSG";
const PREFIX_BUCKET_GET      = PREFIX_DELIM . CMD_BUCKET_GET;
const PREFIX_BUCKET_SET      = PREFIX_DELIM . CMD_BUCKET_SET;
const PREFIX_BUCKET_UNSET    = PREFIX_DELIM . CMD_BUCKET_UNSET;
const PREFIX_BUCKET_APPEND   = PREFIX_DELIM . CMD_BUCKET_APPEND;
const PREFIX_BUCKET_LIST     = PREFIX_DELIM . CMD_BUCKET_LIST;
const PREFIX_INTERNAL        = PREFIX_DELIM . CMD_INTERNAL;
const PREFIX_PAUSE           = PREFIX_DELIM . CMD_PAUSE;
const PREFIX_UNPAUSE         = PREFIX_DELIM . CMD_UNPAUSE;
const PREFIX_DELETE_HANDLER  = PREFIX_DELIM . CMD_DELETE_HANDLER;
const PREFIX_READER_EXEC_LIST= PREFIX_DELIM . "READER_EXEC_LIST";
const PREFIX_READER_BUCKETS  = PREFIX_DELIM . "READER_BUCKETS";
const PREFIX_READER_HANDLES  = PREFIX_DELIM . "READER_HANDLES";

const ALIAS_INTERNAL_RESTART     = "~restart-internal";
const ALIAS_ADMIN_ALIAS_MACRO    = "~alias-macro";
const ALIAS_ADMIN_QUIT           = "~quit";
const ALIAS_ADMIN_NICK           = "~nick";
const ALIAS_ADMIN_PS             = "~ps";
const ALIAS_ADMIN_KILL           = "~kill";
const ALIAS_ADMIN_KILLALL        = "~killall";
const ALIAS_ADMIN_RESTART        = "~restart";
const ALIAS_ADMIN_REHASH         = "~rehash";
const ALIAS_ADMIN_DEST_OVERRIDE  = "~dest-override";
const ALIAS_ADMIN_DEST_CLEAR     = "~dest-clear";
const ALIAS_ADMIN_IGNORE         = "~ignore";
const ALIAS_ADMIN_UNIGNORE       = "~unignore";
const ALIAS_ADMIN_LIST_IGNORE    = "~ignore-list";
const ALIAS_ADMIN_BUCKETS_DUMP   = "~buckets-dump";
const ALIAS_ADMIN_BUCKETS_SAVE   = "~buckets-save";
const ALIAS_ADMIN_BUCKETS_LOAD   = "~buckets-load";
const ALIAS_ADMIN_BUCKETS_FLUSH  = "~buckets-flush";
const ALIAS_ADMIN_BUCKETS_LIST   = "~buckets-list";
const ALIAS_ADMIN_EXEC_CONFLICTS = "~exec-conflicts";
const ALIAS_ADMIN_EXEC_LIST      = "~exec-list";
const ALIAS_ADMIN_EXEC_TIMERS    = "~exec-timers";
const ALIAS_ADMIN_EXEC_ERRORS    = "~exec-errors";
const ALIAS_LOCK                 = "~lock";
const ALIAS_UNLOCK               = "~unlock";
const ALIAS_LIST                 = "~list";
const ALIAS_LIST_AUTH            = "~list-auth";

const TEMPLATE_TRAILING    = "trailing";
const TEMPLATE_NICK        = "nick";
const TEMPLATE_DESTINATION = "dest";
const TEMPLATE_START       = "start";
const TEMPLATE_ALIAS       = "alias";
const TEMPLATE_DATA        = "data";
const TEMPLATE_ITEMS       = "items";
const TEMPLATE_CMD         = "cmd";
const TEMPLATE_PARAMS      = "params";
const TEMPLATE_TIMESTAMP   = "timestamp";
const TEMPLATE_SERVER      = "server";
const TEMPLATE_USER        = "user";
const TEMPLATE_HOSTNAME    = "hostname";
const TEMPLATE_PREFIX      = "prefix";

const THROTTLE_LOCKOUT_TIME = 10.0;
const ANTI_FLOOD_DELAY      = 0.7;
const RAWMSG_TIME_COUNT     = 5;

// =========================================================================
// Data Models
// =========================================================================

class IrcMessage
{
    public function __construct(
        public string $server = IRC_HOST,
        public float $microtime = 0.0,
        public string $time = '',
        public string $data = '',
        public string $prefix = '',
        public string $params = '',
        public string $trailing = '',
        public string $nick = '',
        public string $user = '',
        public string $hostname = '',
        public string $destination = '',
        public string $cmd = ''
    ) {
        if ($this->microtime === 0.0) {
            $this->microtime = microtime(true);
            $this->time = date('Y-m-d H:i:s', (int)$this->microtime);
        }
    }

    public function toArray(): array
    {
        return [
            'server'      => $this->server,
            'microtime'   => $this->microtime,
            'time'        => $this->time,
            'data'        => $this->data,
            'prefix'      => $this->prefix,
            'params'      => $this->params,
            'trailing'    => $this->trailing,
            'nick'        => $this->nick,
            'user'        => $this->user,
            'hostname'    => $this->hostname,
            'destination' => $this->destination,
            'cmd'         => $this->cmd,
        ];
    }
}

class ProcessHandle
{
    /**
     * @param resource $process
     * @param resource $pipeStdin
     * @param resource $pipeStdout
     * @param resource $pipeStderr
     */
    public function __construct(
        public $process,
        public string $command,
        public int $pid,
        public $pipeStdin,
        public $pipeStdout,
        public $pipeStderr,
        public string $alias,
        public array $bucketLocks,
        public string $template,
        public int $allowEmpty,
        public float $timeout,
        public float $repeat,
        public int $autoPrivmsg,
        public float $start,
        public string $nick,
        public string $cmd,
        public string $destination,
        public string $trailing,
        public array $exec,
        public string $server,
        public array $items
    ) {}

    public function toArray(): array
    {
        return [
            'process'      => $this->process,
            'command'      => $this->command,
            'pid'          => $this->pid,
            'pipe_stdin'   => $this->pipeStdin,
            'pipe_stdout'  => $this->pipeStdout,
            'pipe_stderr'  => $this->pipeStderr,
            'alias'        => $this->alias,
            'bucket_locks' => $this->bucketLocks,
            'template'     => $this->template,
            'allow_empty'  => $this->allowEmpty,
            'timeout'      => $this->timeout,
            'repeat'       => $this->repeat,
            'auto_privmsg' => $this->autoPrivmsg,
            'start'        => $this->start,
            'nick'         => $this->nick,
            'cmd'          => $this->cmd,
            'destination'  => $this->destination,
            'trailing'     => $this->trailing,
            'exec'         => $this->exec,
            'server'       => $this->server,
            'items'        => $this->items,
        ];
    }
}

// =========================================================================
// Main IRC Bot Engine
// =========================================================================

class IrcBot
{
    /** @var resource|null */
    public $socket = null;
    /** @var resource|null */
    public $directStdin = null;
    /** @var resource|null */
    public $outBuffer = null;

    public array $buckets = [];
    public array $bucketLocks = [];
    public array $execList = [];
    public array $execErrors = [];
    public array $ignoreList = [];
    public array $aliasLocks = [];
    public array $destOverrides = [];
    public array $timeDeltas = [];
    /** @var ProcessHandle[] */
    public array $handles = [];
    public array $rawmsgTimes = [];

    public array $init = [];
    public array $startup = [];
    public array $help = [];

    public bool $ircPause = false;
    public float|false $throttleTime = false;
    public string $adminData = "";
    public bool $adminIsSock = false;
    public string $socketBuffer = "";

    public array $internalBucketIndexes = [
        BUCKET_IGNORE_NEXT,
        BUCKET_USERS,
        BUCKET_EVENT_HANDLERS,
        BUCKET_CONNECTION_ESTABLISHED,
        BUCKET_EXEC_LIST,
        BUCKET_BOT_NICK,
        BUCKET_PROCESS_TEMPLATE_PREFIX,
        BUCKET_ALIAS_ELEMENT_PREFIX,
    ];

    public array $operatorAliases = [
        ALIAS_ADMIN_ALIAS_MACRO,
    ];

    public array $adminAliases = [
        ALIAS_ADMIN_QUIT,
        ALIAS_ADMIN_NICK,
        ALIAS_ADMIN_RESTART,
        ALIAS_ADMIN_PS,
        ALIAS_ADMIN_KILL,
        ALIAS_ADMIN_KILLALL,
        ALIAS_ADMIN_REHASH,
        ALIAS_ADMIN_DEST_OVERRIDE,
        ALIAS_ADMIN_DEST_CLEAR,
        ALIAS_ADMIN_BUCKETS_DUMP,
        ALIAS_ADMIN_BUCKETS_SAVE,
        ALIAS_ADMIN_BUCKETS_LOAD,
        ALIAS_ADMIN_BUCKETS_FLUSH,
        ALIAS_ADMIN_BUCKETS_LIST,
        ALIAS_ADMIN_IGNORE,
        ALIAS_ADMIN_UNIGNORE,
        ALIAS_ADMIN_LIST_IGNORE,
        ALIAS_ADMIN_EXEC_CONFLICTS,
        ALIAS_ADMIN_EXEC_LIST,
        ALIAS_ADMIN_EXEC_TIMERS,
        ALIAS_ADMIN_EXEC_ERRORS,
    ];

    public array $reservedAliases = [
        ALIAS_ALL,
        ALIAS_INIT,
        ALIAS_STARTUP,
        ALIAS_QUIT,
    ];

    public array $silentTimeoutCommands = [
        CMD_INTERNAL,
        CMD_BUCKET_GET,
        CMD_BUCKET_SET,
        CMD_BUCKET_UNSET,
        CMD_BUCKET_APPEND,
        CMD_BUCKET_LIST,
        CMD_PAUSE,
        CMD_UNPAUSE,
    ];

    public function run(): void
    {
        $this->initializeBuckets();
        if ($this->execLoad() === false) {
            $this->termEcho("error loading exec file");
            return;
        }

        $this->loadIgnoreList();
        $this->initPipes();
        $this->init();
        $this->socket = $this->initializeSocket();
        $this->initializeIrcConnection();

        while (true) {
            foreach ($this->handles as $i => $handle) {
                if (!$this->handleProcess($handle)) {
                    unset($this->handles[$i]);
                }
            }
            $this->handles = array_values($this->handles);
            $this->handleSocket();
            $this->handleDirectStdin();
            $this->processTimedExecs();
        }
    }

    public function termEcho(string $msg): void
    {
        echo "\033[33m" . date("Y-m-d H:i:s", (int)microtime(true)) . " > \033[31m{$msg}\033[0m\n";
    }

    public function privmsg(string $destination, string $nick, string $msg): void
    {
        if ($destination === '') {
            $this->termEcho("PRIVMSG: DESTINATION NOT SPECIFIED: nick=\"{$nick}\", msg=\"{$msg}\"");
            return;
        }
        if ($msg === '') {
            $this->termEcho("PRIVMSG: NO TEXT TO SEND: nick=\"{$nick}\", destination=\"{$destination}\"");
            return;
        }

        $msg = substr($msg, 0, MAX_MSG_LENGTH);
        $target = $this->destOverrides[$nick][$destination] ?? (str_starts_with($destination, '#') ? $destination : $nick);
        $this->rawmsg(":" . $this->getBotNick() . " PRIVMSG {$target} :{$msg}");
    }

    public function rawmsg(string $msg, bool $obfuscate = false): void
    {
        if ($this->throttleTime !== false) {
            $delta = microtime(true) - $this->throttleTime;
            if ($delta > THROTTLE_LOCKOUT_TIME) {
                $this->throttleTime = false;
            } else {
                $this->termEcho("*** REFUSED OUTGOING MESSAGE DUE TO SERVER THROTTLING: {$msg}");
                return;
            }
        }

        $n = count($this->rawmsgTimes);
        if ($n > 0) {
            $dt = microtime(true) - $this->rawmsgTimes[$n - 1];
            if ($dt > THROTTLE_LOCKOUT_TIME) {
                $this->rawmsgTimes = [];
            } elseif ($n >= RAWMSG_TIME_COUNT) {
                usleep((int)(ANTI_FLOOD_DELAY * 1e6));
            }
        }

        if ($this->socket) {
            fwrite($this->socket, "{$msg}\n");
        }
        $this->rawmsgTimes[] = microtime(true);
        while (count($this->rawmsgTimes) > RAWMSG_TIME_COUNT) {
            array_shift($this->rawmsgTimes);
        }

        if (!$obfuscate) {
            $this->handleData("{$msg}\n", true, false, true);
        } else {
            $this->termEcho("RAWMSG: (obfuscated)");
        }
    }

    public function getBotNick(): string
    {
        return $this->buckets[BUCKET_BOT_NICK] ?? DEFAULT_NICK;
    }

    public function setBotNick(string $nick): void
    {
        $this->buckets[BUCKET_BOT_NICK] = $nick;
    }

    public function initializeIrcConnection(): void
    {
        $this->rawmsg("NICK " . $this->getBotNick());
        $this->rawmsg("USER " . USER_NAME . " hostname servername :" . FULL_NAME);
    }

    /**
     * @return resource
     */
    public function initializeSocket()
    {
        $errNo = 0;
        $errMsg = "";
        if (IRC_PORT === "6697") {
            $contextOptions = [
                "ssl" => [
                    "peer_name"          => SSL_PEER_NAME,
                    "verify_peer"        => true,
                    "verify_peer_name"   => true,
                    "allow_self_signed"  => false,
                    "verify_depth"       => 5,
                    "cafile"             => SSL_CA_FILE,
                    "disable_compression"=> true,
                    "SNI_enabled"        => true,
                ],
            ];
            $context = stream_context_create($contextOptions);
            $socket = stream_socket_client("tls://" . IRC_HOST_CONNECT . ":" . IRC_PORT, $errNo, $errMsg, 30, STREAM_CLIENT_CONNECT, $context);
        } else {
            $socket = stream_socket_client("tcp://" . IRC_HOST_CONNECT . ":" . IRC_PORT, $errNo, $errMsg, 30);
        }

        if (!$socket) {
            $this->termEcho("ERROR CREATING IRC SOCKET: [{$errNo}] {$errMsg}");
            exit(1);
        }

        $this->termEcho("IRC SOCKET CONNECTED");

        stream_set_blocking($socket, false);
        return $socket;
    }

    public function finalizeSocket(): void
    {
        if (NICKSERV_IDENTIFY === "1") {
            $this->rawmsg("NickServ LOGOUT");
        }
        $this->rawmsg("QUIT :");
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function initializeBuckets(): void
    {
        $this->buckets[BUCKET_EVENT_HANDLERS] = base64_encode(serialize([]));
        $this->buckets[BUCKET_CONNECTION_ESTABLISHED] = "0";
        $this->buckets[BUCKET_USERS] = base64_encode(serialize([]));
        $this->buckets[BUCKET_OUTPUT_CONTROL] = base64_encode(serialize([]));
        $this->buckets[BUCKET_BOT_NICK] = DEFAULT_NICK;
    }

    public function init(): void
    {
        $items = $this->parseData(CMD_INIT);
        if ($items) {
            $this->bucketsLoad($items);
            $this->initializeBuckets();
            $this->processExecHelps();
            $this->processExecInits();
            $this->processScripts($items, ALIAS_INIT);
        }
    }

    public function startup(): void
    {
        $this->buckets[BUCKET_CONNECTION_ESTABLISHED] = "1";
        $this->processExecStartups();
        $items = $this->parseData(CMD_STARTUP);
        if ($items) {
            $this->processScripts($items, ALIAS_STARTUP);
        }
    }

    private function loadIgnoreList(): void
    {
        if (file_exists(IGNORE_FILE)) {
            $data = file_get_contents(IGNORE_FILE);
            if ($data !== false) {
                $this->ignoreList = array_values(array_filter(array_map('trim', explode("\n", $data)), fn($v) => $v !== ''));
            }
        }
    }

    private function initPipes(): void
    {
        if (PHP_OS_FAMILY <> 'Windows') {
            $this->directStdin = fopen("php://stdin", "r");
            if ($this->directStdin) {
                stream_set_blocking($this->directStdin, false);
                $this->termEcho("DIRECT STDIN LOADED (NON-WINDOWS)");
            }
            else {
                $this->termEcho("DIRECT STDIN ERROR (NON-WINDOWS)");
            }
        }
        else {
            $this->termEcho("DIRECT STDIN NOT LOADED (WINDOWS)");
        }

        if (file_exists(EXEC_OUTPUT_BUFFER_FILE)) {
            unlink(EXEC_OUTPUT_BUFFER_FILE);
        }

        if (IFACE_ENABLE === "1") {
            if (!posix_mkfifo(EXEC_OUTPUT_BUFFER_FILE, 0700)) {
                $this->termEcho("error creating output buffer file");
                return;
            }
            $this->outBuffer = fopen(EXEC_OUTPUT_BUFFER_FILE, "a+");
            if (!$this->outBuffer) {
                $this->termEcho("error opening output buffer file for writing");
                return;
            }
            stream_set_blocking($this->outBuffer, false);
        }
    }

    public function processExecHelps(): void
    {
        $this->termEcho("help:");
        $helpLines = [];
        $fileLines = [];

        foreach ($this->help as $helpTarget) {
            if (file_exists($helpTarget)) {
                if (is_dir($helpTarget)) {
                    $this->loadDirectory($helpTarget, $fileLines, FILE_DIRECTIVE_HELP);
                } else {
                    $this->loadInclude($helpTarget, $fileLines, FILE_DIRECTIVE_HELP);
                }
            } else {
                $helpLines[] = $helpTarget;
            }
        }

        foreach ($fileLines as $lines) {
            foreach ($lines as $line) {
                $helpLines[] = $line;
            }
        }

        foreach ($helpLines as $hLine) {
            $parts = explode(" ", $hLine, 2);
            if (count($parts) === 2) {
                $alias = trim($parts[0]);
                if (isset($this->execList[$alias]["help"])) {
                    $this->execList[$alias]["help"][] = $parts[1];
                    $this->termEcho("ALIAS HELP: {$alias} => {$parts[1]}");
                }
            }
        }
    }

    public function processExecInits(): void
    {
        $this->termEcho("init:");
        $fileLines = [];
        foreach ($this->init as $initTarget) {
            if (file_exists($initTarget)) {
                if (is_dir($initTarget)) {
                    $this->loadDirectory($initTarget, $fileLines, FILE_DIRECTIVE_INIT);
                } else {
                    $this->loadInclude($initTarget, $fileLines, FILE_DIRECTIVE_INIT);
                }
            }
        }

        foreach ($fileLines as $filename => $commands) {
            foreach ($commands as $cmd) {
                $this->termEcho("FILE INIT: {$filename} => {$cmd}");
                $this->handleData(":" . $this->getBotNick() . " " . CMD_INTERNAL . " :{$cmd}\n", false, false, true);
            }
        }
    }

    public function processExecStartups(): void
    {
        $this->termEcho("startup:");
        $fileLines = [];
        foreach ($this->startup as $startupTarget) {
            if (file_exists($startupTarget)) {
                if (is_dir($startupTarget)) {
                    $this->loadDirectory($startupTarget, $fileLines, FILE_DIRECTIVE_STARTUP);
                } else {
                    $this->loadInclude($startupTarget, $fileLines, FILE_DIRECTIVE_STARTUP);
                }
            }
        }

        foreach ($fileLines as $commands) {
            foreach ($commands as $cmd) {
                $this->termEcho("FILE STARTUP: {$cmd}");
                $this->handleData(":" . $this->getBotNick() . " " . CMD_INTERNAL . " :{$cmd}\n", false, false, true);
            }
        }
    }

    public function getValidDataCmd(): array
    {
        return [
            "#"       => ["101", "110", "111"],
            "USER"    => ["010", "011"],
            "INVITE"  => ["111"],
            "JOIN"    => ["010", "110"],
            "KICK"    => ["110", "111"],
            "KILL"    => ["101"],
            "MODE"    => ["101", "110", "111"],
            "NICK"    => ["010", "101"],
            "NOTICE"  => ["111"],
            "PART"    => ["110", "111"],
            "WHOIS"   => ["010", "110"],
            "PRIVMSG" => ["011", "111"],
            "QUIT"    => ["100", "101"],
            "PONG"    => ["001", "110", "111"],
            CMD_INIT          => ["000"],
            CMD_STARTUP       => ["000"],
            CMD_INTERNAL      => ["100", "101", "110", "111"],
            CMD_BUCKET_GET    => ["001", "101"],
            CMD_BUCKET_SET    => ["001", "101"],
            CMD_BUCKET_UNSET  => ["001", "101"],
            CMD_BUCKET_APPEND => ["001", "101"],
            CMD_BUCKET_LIST   => ["000", "100"],
            CMD_PAUSE         => ["000"],
            CMD_UNPAUSE       => ["000"],
        ];
    }

    public function getList(IrcMessage $items): void
    {
        $msg = " ~list ~list-auth ~lock ~unlock";
        $this->privmsg($items->destination, $items->nick, $msg);
        $aliases = array_keys($this->execList);
        sort($aliases);
        $buffer = "";

        foreach ($aliases as $alias) {
            $conf = $this->execList[$alias];
            if ($conf["accounts_wildcard"] !== "@"
                && $conf["accounts_wildcard"] !== "+"
                && count($conf["accounts"]) === 0
                && strlen($alias) <= 20
                && !in_array($alias, $this->reservedAliases, true)
                && (count($conf["cmds"]) === 0 || in_array("PRIVMSG", $conf["cmds"], true))) {

                if (strlen($buffer . $alias) > (MAX_MSG_LENGTH - 1)) {
                    $this->privmsg($items->destination, $items->nick, " " . trim($buffer));
                    $buffer = $alias;
                } else {
                    $buffer .= $alias;
                }
                $buffer .= " ";
            }
        }
        if (trim($buffer) !== '') {
            $this->privmsg($items->destination, $items->nick, " " . trim($buffer));
        }
    }

    public function getListAuth(IrcMessage $items): void
    {
        $msg = " ~quit ~rehash ~ps ~kill ~killall ~dest-override ~dest-clear ~buckets-dump ~buckets-save ~buckets-load ~buckets-flush ~buckets-list ~restart ~ignore ~unignore";
        $this->privmsg($items->destination, $items->nick, $msg);
        $aliases = array_keys($this->execList);
        sort($aliases);
        $buffer = "";

        foreach ($aliases as $alias) {
            $conf = $this->execList[$alias];
            if (($conf["accounts_wildcard"] === "@" || $conf["accounts_wildcard"] === "+" || count($conf["accounts"]) > 0)
                && strlen($alias) <= 20
                && !in_array($alias, $this->reservedAliases, true)
                && (count($conf["cmds"]) === 0 || in_array("PRIVMSG", $conf["cmds"], true))) {

                if (strlen($buffer . $alias) > (MAX_MSG_LENGTH - 1)) {
                    $this->privmsg($items->destination, $items->nick, " " . trim($buffer));
                    $buffer = $alias;
                } else {
                    $buffer .= $alias;
                }
                $buffer .= " ";
            }
        }
        if (trim($buffer) !== '') {
            $this->privmsg($items->destination, $items->nick, " " . trim($buffer));
        }
    }

    public function handleErrors(string $data): void
    {
        if (!isset($this->buckets[BUCKET_CONNECTION_ESTABLISHED])) {
            return;
        }
        $msg = trim($data, "\n\r\0\x0B");
        $lmsg = strtolower($msg);
        if (DEBUG_CHAN !== '' && !str_contains($lmsg, strtolower(DEBUG_CHAN)) && $this->buckets[BUCKET_CONNECTION_ESTABLISHED] !== "0") {
            if (str_contains($lmsg, "php parse error:")
                || str_contains($lmsg, "php warning:")
                || str_contains($lmsg, "php fatal error:")
                || str_contains($lmsg, "php notice:")) {
                $this->rawmsg(":" . $this->getBotNick() . " PRIVMSG " . DEBUG_CHAN . " :{$msg}");
            }
        }
    }

    public function logItems(IrcMessage $items): void
    {
        if (MYSQL_LOG === "1" && function_exists('sql_insert') && BOT_SCHEMA !== '' && LOG_TABLE !== '') {
            sql_insert($items->toArray(), LOG_TABLE);
        }
    }

    public function handleDirectStdin(): void
    {
        if (!$this->directStdin) {
            return;
        }

        $line = fgets($this->directStdin);
        if ($line === false) {
            return;
        }

        $msg = trim($line);
        if ($msg === '') {
            return;
        }

        $this->termEcho("*** DIRECT STDIN: {$msg}");
        $parts = explode(" ", $msg, 2);
        $prefix = strtoupper($parts[0]);
        $prefixMsg = $parts[1] ?? '';

        if ($prefixMsg !== '') {
            match ($prefix) {
                PREFIX_IRC      => $this->rawmsg($prefixMsg),
                PREFIX_INTERNAL => $this->handleData(":" . $this->getBotNick() . " " . CMD_INTERNAL . " :{$prefixMsg}\n", false, false, true),
                PREFIX_PAUSE    => $this->ircPause = true,
                PREFIX_UNPAUSE  => $this->ircPause = false,
                default         => $this->handleData("{$msg}\n", false, false, true),
            };
            return;
        }
        $this->handleData("{$msg}\n", false, false, true);
    }

    public function handleProcess(ProcessHandle $handle): bool
    {
        $this->handleStdout($handle);
        $this->handleStderr($handle);

        $eof = ($handle->pipeStdout === null);
        if (!$eof) {
            $meta = stream_get_meta_data($handle->pipeStdout);
            $eof = $meta["eof"];
        }

        if ($eof) {
            $this->writeOutBufferProc($handle, "", "proc_end");
            $this->freeBucketLocks($handle->pid);
            if (is_resource($handle->pipeStdin)) fclose($handle->pipeStdin);
            if (is_resource($handle->pipeStdout)) fclose($handle->pipeStdout);
            if (is_resource($handle->pipeStderr)) fclose($handle->pipeStderr);
            proc_close($handle->process);
            return false;
        }

        if ($handle->timeout > 0 && (microtime(true) - $handle->start) > $handle->timeout) {
            $this->writeOutBufferProc($handle, "", "proc_timeout");
            $this->freeBucketLocks($handle->pid);
            $this->killProcess($handle);
            $this->termEcho("process timed out: {$handle->command}");
            return false;
        }

        return true;
    }

    public function freeBucketLocks(int $pid): void
    {
        foreach ($this->bucketLocks as $bucketIndex => $pidArray) {
            $key = array_search($pid, $pidArray, true);
            if ($key !== false) {
                unset($this->bucketLocks[$bucketIndex][$key]);
                $this->bucketLocks[$bucketIndex] = array_values($this->bucketLocks[$bucketIndex]);
                if (count($this->bucketLocks[$bucketIndex]) === 0) {
                    unset($this->bucketLocks[$bucketIndex]);
                    $this->termEcho("BUCKET UNLOCKED: {$bucketIndex} BY {$pid} [NO LONGER LOCKED BY ANY PROCESSES]");
                } else {
                    $this->termEcho("BUCKET UNLOCKED: {$bucketIndex} BY {$pid} [STILL LOCKED BY OTHER PROCESS]");
                }
            }
        }
    }

    public function writeOutBuffer(mixed $buf): void
    {
        if (IFACE_ENABLE !== "1" || !$this->outBuffer) {
            return;
        }
        if (flock($this->outBuffer, LOCK_EX)) {
            $serialized = serialize($buf);
            fwrite($this->outBuffer, $serialized . "\n");
            flock($this->outBuffer, LOCK_UN);
        }
    }

    public function writeOutBufferProc(ProcessHandle $handle, string $buf, string $type): void
    {
        $this->writeOutBuffer([
            "type"   => $type,
            "buf"    => $buf,
            "handle" => $handle->toArray(),
            "time"   => microtime(true),
        ]);
    }

    public function writeOutBufferCommand(IrcMessage $items, string $command): void
    {
        $this->writeOutBuffer([
            "type"    => "command",
            "buf"     => $command,
            "items"   => $items->toArray(),
            "time"    => microtime(true),
        ]);
    }

    public function writeOutBufferData(IrcMessage $items): void
    {
        $this->writeOutBuffer([
            "type"  => "data",
            "buf"   => $items->trailing,
            "items" => $items->toArray(),
            "time"  => microtime(true),
        ]);
    }

    public function writeOutBufferSock(string $buf): void
    {
        $this->writeOutBuffer([
            "type" => "socket",
            "buf"  => $buf,
            "time" => microtime(true),
        ]);
    }

    public function handleReaderStdoutCommand(ProcessHandle $handle, string $prefix): void
    {
        match ($prefix) {
            PREFIX_READER_EXEC_LIST => array_walk($this->execList, fn($val) => $this->writeOutBuffer([
                "type" => "reader_exec_list", "buf" => $val, "time" => microtime(true)
            ])),
            PREFIX_READER_BUCKETS => array_walk($this->buckets, fn($val, $key) => $this->writeOutBuffer([
                "type" => "reader_buckets", "buf" => $val, "index" => $key, "time" => microtime(true)
            ])),
            PREFIX_READER_HANDLES => array_walk($this->handles, fn($h) => $this->writeOutBuffer([
                "type" => "reader_handles", "buf" => $h->toArray(), "time" => microtime(true)
            ])),
            default => null
        };
    }

    public function handleStdout(ProcessHandle $handle): void
    {
        if (!is_resource($handle->pipeStdout)) {
            return;
        }

        $read = [$handle->pipeStdout];
        $write = null;
        $except = null;
        $c = stream_select($read, $write, $except, 0);
        if ($c === false || $c <= 0) {
            return;
        }

        $buf = fgets($handle->pipeStdout);
        if ($buf === false) {
            return;
        }

        $this->writeOutBufferProc($handle, $buf, "stdout");
        if (trim($buf) === DIRECTIVE_QUIT) {
            $this->doquit();
        }

        $msg = rtrim($buf, "\n");
        $this->handleErrors($msg);

        if ($handle->autoPrivmsg === 1) {
            $this->privmsg($handle->destination, $handle->nick, $msg);
            return;
        }

        $parts = explode(" ", $msg, 2);
        $prefix = strtoupper($parts[0]);
        $prefixMsg = $parts[1] ?? '';

        if ($prefixMsg !== '') {
            switch ($prefix) {
                case PREFIX_IRC:
                    $this->rawmsg($prefixMsg);
                    return;
                case PREFIX_EXEC_ADD:
                    $ok = $this->loadExecLine($prefixMsg, $msg, false) !== false;
                    $this->privmsg($handle->destination, $handle->nick, $ok ? "successfully added exec line" : "error adding exec line");
                    return;
                case PREFIX_EXEC_DEL:
                    $alias = strtolower(trim($prefixMsg));
                    if (isset($this->execList[$alias])) {
                        if ($this->execList[$alias]["saved"]) {
                            unset($this->execList[$alias]);
                            $this->privmsg($handle->destination, $handle->nick, "alias \"{$alias}\" deleted from memory (not from file though)");
                        } else {
                            $this->privmsg($handle->destination, $handle->nick, "alias \"{$alias}\" with current configuration doesn't exist in exec file");
                        }
                    } else {
                        $this->privmsg($handle->destination, $handle->nick, "alias \"{$alias}\" not found");
                    }
                    return;
                case PREFIX_EXEC_SAVE:
                    $alias = strtolower(trim($prefixMsg));
                    if (isset($this->execList[$alias])) {
                        if (!$this->execList[$alias]["saved"]) {
                            if (file_put_contents(EXEC_FILE, trim($prefixMsg) . "\n", FILE_APPEND) !== false) {
                                $this->execList[$alias]["saved"] = true;
                                $this->privmsg($handle->destination, $handle->nick, "exec line for alias \"{$alias}\" successfully appended to exec file");
                            } else {
                                $this->privmsg($handle->destination, $handle->nick, "error appending exec file");
                            }
                        } else {
                            $this->privmsg($handle->destination, $handle->nick, "alias \"{$alias}\" with current configuration already exists in exec file");
                        }
                    } else {
                        $this->privmsg($handle->destination, $handle->nick, "alias \"{$alias}\" not found");
                    }
                    return;
                case PREFIX_PRIVMSG:
                    if ($handle->destination !== '' && $handle->nick !== '') {
                        $this->privmsg($handle->destination, $handle->nick, $prefixMsg);
                    }
                    return;
                case PREFIX_BUCKET_GET:
                case PREFIX_BUCKET_SET:
                case PREFIX_BUCKET_UNSET:
                case PREFIX_BUCKET_APPEND:
                    $this->handleBuckets(substr($prefix, 1) . " :{$prefixMsg}\n", $handle);
                    return;
                case PREFIX_INTERNAL:
                    $test = trim($prefixMsg);
                    if (str_starts_with($test, ':')) {
                        $this->handleData("{$test}\n");
                    } else {
                        $nick = $handle->nick ?: $this->getBotNick();
                        $target = $handle->destination !== '' ? " {$handle->destination}" : '';
                        $this->handleData(":{$nick} " . CMD_INTERNAL . "{$target} :{$prefixMsg}\n");
                    }
                    return;
                case PREFIX_PAUSE:
                    $this->ircPause = true;
                    return;
                case PREFIX_UNPAUSE:
                    $this->ircPause = false;
                    return;
                case PREFIX_DELETE_HANDLER:
                    $hParts = explode("=>", $prefixMsg, 2);
                    if (count($hParts) < 2) {
                        $this->termEcho("*** ERROR: INVALID DELETE_HANDLER COMMAND");
                        return;
                    }
                    $hCmd = strtoupper(trim($hParts[0]));
                    $hData = trim($hParts[1]);
                    $handlers = unserialize(base64_decode($this->buckets[BUCKET_EVENT_HANDLERS]));
                    foreach ($handlers as $idx => $ser) {
                        $handler = unserialize($ser);
                        if (isset($handler[$hCmd]) && $handler[$hCmd] === $hData) {
                            unset($handler[$hCmd]);
                            if (count($handler) === 0) {
                                unset($handlers[$idx]);
                                $handlers = array_values($handlers);
                            } else {
                                $handlers[$idx] = serialize($handler);
                            }
                            $this->buckets[BUCKET_EVENT_HANDLERS] = base64_encode(serialize($handlers));
                            $this->termEcho("*** DELETE EVENT-HANDLER: {$hCmd} => {$hData} (SUCCESS)");
                            return;
                        }
                    }
                    $this->termEcho("*** DELETE EVENT-HANDLER: {$hCmd} => {$hData} (FAILED)");
                    return;
            }
        } else {
            match ($prefix) {
                PREFIX_BUCKET_LIST       => $this->handleBuckets(CMD_BUCKET_LIST . "\n", $handle),
                PREFIX_READER_EXEC_LIST  => $this->handleReaderStdoutCommand($handle, PREFIX_READER_EXEC_LIST),
                PREFIX_READER_BUCKETS    => $this->handleReaderStdoutCommand($handle, PREFIX_READER_BUCKETS),
                PREFIX_READER_HANDLES    => $this->handleReaderStdoutCommand($handle, PREFIX_READER_HANDLES),
                default                  => null,
            };
            if (in_array($prefix, [PREFIX_BUCKET_LIST, PREFIX_READER_EXEC_LIST, PREFIX_READER_BUCKETS, PREFIX_READER_HANDLES], true)) {
                return;
            }
        }

        if (!$this->handleBuckets($msg, $handle)) {
            $this->handleData($buf);
        }
    }

    public function handleStderr(ProcessHandle $handle): void
    {
        if (!is_resource($handle->pipeStderr)) {
            return;
        }

        $read = [$handle->pipeStderr];
        $write = null;
        $except = null;
        $c = stream_select($read, $write, $except, 0);
        if ($c === false || $c <= 0) {
            return;
        }

        $buf = fgets($handle->pipeStderr);
        if ($buf === false) {
            return;
        }

        $this->writeOutBufferProc($handle, $buf, "stderr");
        $msg = rtrim($buf, "\n");
        $this->handleErrors("STDERR IN [{$handle->command}]: {$msg}");
        $this->termEcho("STDERR IN [{$handle->command}]: {$msg}");
    }

    public function handleStdin(ProcessHandle $handle, string $data): bool
    {
        if (!is_resource($handle->pipeStdin)) {
            return false;
        }
        $str = base64_encode($data) . PHP_EOL;
        return fwrite($handle->pipeStdin, $str) !== false;
    }

    public function handleBuckets(string $data, ProcessHandle $handle): bool
    {
        $items = $this->parseData($data);
        if ($items === false) {
            return false;
        }

        $trailing = $items->trailing;
        switch ($items->cmd) {
            case CMD_BUCKET_GET:
                $index = $trailing;
                if ($index === BUCKET_EXEC_LIST) {
                    $this->handleStdin($handle, serialize($this->execList));
                    return true;
                }
                if ($index === BUCKET_ADMIN_ACCOUNTS_LIST) {
                    $this->handleStdin($handle, ADMIN_ACCOUNTS);
                    return true;
                }
                if ($index === BUCKET_OPERATOR_ACCOUNT) {
                    $this->handleStdin($handle, OPERATOR_ACCOUNT);
                    return true;
                }
                if ($index === BUCKET_MEMORY_USAGE) {
                    $this->handleStdin($handle, (string)memory_get_usage());
                    return true;
                }
                if ($index === BUCKET_OPERATOR_HOSTNAME) {
                    $this->handleStdin($handle, OPERATOR_HOSTNAME);
                    return true;
                }

                if (str_starts_with($index, BUCKET_ALIAS_ELEMENT_PREFIX)) {
                    $partsStr = substr($index, strlen(BUCKET_ALIAS_ELEMENT_PREFIX));
                    $parts = explode("_", $partsStr);
                    if (count($parts) <= 1) {
                        $this->handleStdin($handle, "");
                        return true;
                    }
                    $alias = array_shift($parts);
                    $key = implode("_", $parts);
                    if (isset($this->execList[$alias][$key])) {
                        $out = is_array($this->execList[$alias][$key]) ? serialize($this->execList[$alias][$key]) : (string)$this->execList[$alias][$key];
                        $this->handleStdin($handle, $out);
                        return true;
                    }
                }

                if (str_starts_with($index, BUCKET_PROCESS_TEMPLATE_PREFIX)) {
                    $processTemplate = substr($index, strlen(BUCKET_PROCESS_TEMPLATE_PREFIX));
                    $hArr = $handle->toArray();
                    if (isset($hArr[$processTemplate])) {
                        $out = is_array($hArr[$processTemplate]) ? serialize($hArr[$processTemplate]) : (string)$hArr[$processTemplate];
                        $this->handleStdin($handle, $out);
                        return true;
                    }
                }

                if (isset($this->buckets[$index])) {
                    if (isset($this->bucketLocks[$index])) {
                        $this->termEcho("BUCKET_GET [{$index}]: BUCKET INDEX LOCKED BY FOLLOWING PID LIST: " . implode(",", $this->bucketLocks[$index]));
                        $this->handleStdin($handle, "");
                        return true;
                    }
                    $this->handleStdin($handle, $this->buckets[$index]);
                } else {
                    $this->handleStdin($handle, "");
                }
                return true;

            case CMD_BUCKET_SET:
                $parts = explode(" ", $trailing, 2);
                if (count($parts) < 2) {
                    $this->termEcho("BUCKET_SET: INVALID TRAILING: '{$trailing}'");
                } else {
                    $index = $parts[0];
                    if (in_array($index, $this->internalBucketIndexes, true)) {
                        $this->termEcho("BUCKET_SET [{$index}]: BUCKET INDEX RESERVED");
                        return true;
                    }
                    if (isset($this->bucketLocks[$index])) {
                        $this->termEcho("BUCKET_SET [{$index}]: BUCKET INDEX LOCKED BY FOLLOWING PID LIST: " . implode(",", $this->bucketLocks[$index]));
                        return true;
                    }
                    $this->buckets[$index] = base64_decode($parts[1]);
                }
                return true;

            case CMD_BUCKET_UNSET:
                $index = $trailing;
                if (isset($this->buckets[$index])) {
                    if (isset($this->bucketLocks[$index])) {
                        $this->termEcho("BUCKET_UNSET [{$index}]: BUCKET INDEX LOCKED BY FOLLOWING PID LIST: " . implode(",", $this->bucketLocks[$index]));
                        return true;
                    }
                    unset($this->buckets[$index]);
                }
                return true;

            case CMD_BUCKET_APPEND:
                $parts = explode(" ", $trailing, 2);
                if (count($parts) < 2) {
                    $this->termEcho("BUCKET_APPEND: INVALID TRAILING: '{$trailing}'");
                } else {
                    $index = $parts[0];
                    if (isset($this->bucketLocks[$index])) {
                        $this->termEcho("BUCKET_APPEND [{$index}]: BUCKET INDEX LOCKED BY FOLLOWING PID LIST: " . implode(",", $this->bucketLocks[$index]));
                        return true;
                    }
                    $val = $parts[1];
                    $bucketArray = [];
                    if (isset($this->buckets[$index])) {
                        $decoded = base64_decode($this->buckets[$index]);
                        $bucketArray = $decoded !== false ? unserialize($decoded) : false;
                        if (!is_array($bucketArray)) {
                            $bucketArray = [];
                        }
                    }
                    $bucketArray[] = $val;
                    $this->buckets[$index] = base64_encode(serialize($bucketArray));
                }
                return true;

            case CMD_BUCKET_LIST:
                $this->handleStdin($handle, implode(" ", array_keys($this->buckets)));
                return true;
        }

        return false;
    }

    public function bucketsDump(IrcMessage $items): void
    {
        $this->termEcho("############ BEGIN BUCKETS DUMP ############");
        var_dump($this->buckets);
        $this->termEcho("############# END BUCKETS DUMP #############");
    }

    public function bucketsSave(IrcMessage $items): void
    {
        $data = base64_encode(serialize($this->buckets));
        if (file_put_contents(BUCKETS_FILE, $data) === false) {
            $this->privmsg($items->destination, $items->nick, "error saving buckets file");
            return;
        }
        $size = round(strlen($data) / 1024, 1);
        $this->privmsg($items->destination, $items->nick, "successfully saved buckets file ({$size} kb)");
    }

    public function bucketsLoad(IrcMessage $items): void
    {
        if (!file_exists(BUCKETS_FILE)) {
            $this->termEcho("*** BUCKETS FILE NOT FOUND");
            return;
        }
        $raw = file_get_contents(BUCKETS_FILE);
        if ($raw === false) {
            $this->privmsg($items->destination, $items->nick, "error reading buckets file");
            return;
        }
        $data = unserialize(base64_decode($raw));
        if ($data === false || !is_array($data)) {
            $this->privmsg($items->destination, $items->nick, "error unserializing buckets file");
            return;
        }
        $this->buckets = $data;
        $this->privmsg($items->destination, $items->nick, "successfully loaded buckets file");
    }

    public function bucketsFlush(IrcMessage $items): void
    {
        $connected = $this->buckets[BUCKET_CONNECTION_ESTABLISHED] ?? "0";
        $this->buckets = [];
        $this->initializeBuckets();
        $this->buckets[BUCKET_CONNECTION_ESTABLISHED] = $connected;
        $this->privmsg($items->destination, $items->nick, "buckets flushed");
    }

    public function bucketsList(IrcMessage $items): void
    {
        $this->privmsg($items->destination, $items->nick, "bucket list output to terminal");
        foreach (array_keys($this->buckets) as $index) {
            $this->termEcho((string)$index);
        }
        $this->privmsg($items->destination, $items->nick, "bucket count: " . count($this->buckets));
    }

    public function handleSocket(): void
    {
        if ($this->ircPause) {
            usleep(200000);
            return;
        }
        if (!$this->socket) {
            return;
        }

        $read = [$this->socket];
        $write = null;
        $except = null;
        $c = stream_select($read, $write, $except, 0, 200000);
        if ($c === false || $c <= 0) {
            return;
        }

        $this->socketBuffer = "";
        do
        {
          $buffer = fread($this->socket, 1024);
          if ($buffer === false) {
            $this->termEcho("connection terminated by remote host");
            $this->doquit();
            return;
          }
          $this->socketBuffer .= $buffer;
        }
        while (strlen($buffer) > 0);

        
        $this->socketBuffer .= $buffer;
        $this->writeOutBufferSock($this->socketBuffer);

        while (($pos = strpos($this->socketBuffer, "\n")) !== false) {
            $line = substr($this->socketBuffer, 0, $pos);
            $this->socketBuffer = substr($this->socketBuffer, $pos + 1);
            $line = trim($line, "\r");
            if ($line === '') {
                continue;
            }

            if (!$this->pingpong($line)) {
                $this->handleData($line . "\n", true);
            }
        }
    }

    public function hasAccountList(string $alias): bool
    {
        if (isset($this->execList[$alias])) {
            return count($this->execList[$alias]["accounts"]) > 0 || $this->execList[$alias]["accounts_wildcard"] !== "";
        }
        return false;
    }

    public function getUsers(string $nick = ""): array
    {
        $users = [];
        if (isset($this->buckets[BUCKET_USERS])) {
            $dec = unserialize(base64_decode($this->buckets[BUCKET_USERS]));
            if (is_array($dec)) {
                $users = $dec;
            }
        }
        if ($nick !== '') {
            $users[$nick] ??= [];
            $users[$nick]["channels"] ??= [];
            $users[$nick]["nicks"] ??= [];
        }
        return $users;
    }

    public function setUsers(array $users): void
    {
        $this->buckets[BUCKET_USERS] = base64_encode(serialize($users));
    }

    public function handleEvents(IrcMessage $items): void
    {
        $cmd = strtoupper(trim($items->cmd));
        $nick = strtolower(trim($items->nick));

        // NOTE: each arm below is an IMMEDIATELY-INVOKED closure ( (function(){...})() ).
        // Previously these were plain `function() {...}` values: match() just returns the
        // chosen arm's value, so the closures were built but never called and this entire
        // block was dead code — no PRIVMSG/JOIN/KICK/NICK/PART/QUIT/KILL/302/330 user
        // tracking ever ran. The trailing `()` on each arm is what actually runs it now.
        match ($cmd) {
            "PRIVMSG" => (function() use ($nick, $items) {
                if ($nick === '') return;
                $users = $this->getUsers($nick);
                $users[$nick]["prefix"] = trim($items->prefix);
                $users[$nick]["user"] = trim($items->user);
                $users[$nick]["hostname"] = trim($items->hostname);
                $users[$nick]["connected"] = true;
                $this->setUsers($users);
            })(),
            "JOIN" => (function() use ($nick, $items) {
                $channel = strtolower(trim($items->params));
                if ($nick === '' || $channel === '') return;
                $users = $this->getUsers($nick);
                $users[$nick]["nicks"][$nick] = microtime(true);
                $users[$nick]["channels"][$channel] = "";
                $users[$nick]["prefix"] = trim($items->prefix);
                $users[$nick]["user"] = trim($items->user);
                $users[$nick]["hostname"] = trim($items->hostname);
                $users[$nick]["connected"] = true;
                $this->setUsers($users);
            })(),
            "KICK" => (function() use ($items) {
                $parts = explode(" ", strtolower(trim($items->trailing)));
                if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                    [$channel, $kickedNick] = $parts;
                    $users = $this->getUsers($kickedNick);
                    if (isset($users[$kickedNick]["channels"][$channel])) {
                        $users[$kickedNick]["prefix"] = trim($items->prefix);
                        $users[$kickedNick]["user"] = trim($items->user);
                        $users[$kickedNick]["hostname"] = trim($items->hostname);
                        unset($users[$kickedNick]["channels"][$channel]);
                        $users[$kickedNick]["connected"] = true;
                        $this->setUsers($users);
                    }
                }
            })(),
            "NICK" => (function() use ($nick, $items) {
                $newNick = strtolower(trim($items->trailing));
                if ($nick === '' || $newNick === '') return;
                $users = $this->getUsers($nick);
                $user = $users[$nick] ?? [];
                unset($users[$nick]);
                $users[$newNick] = $user;
                $users[$newNick]["nicks"][$newNick] = microtime(true);
                $users[$newNick]["prefix"] = trim($items->prefix);
                $users[$newNick]["user"] = trim($items->user);
                $users[$newNick]["hostname"] = trim($items->hostname);
                $users[$newNick]["connected"] = true;
                $this->setUsers($users);
            })(),
            "PART" => (function() use ($nick, $items) {
                $channel = strtolower(trim($items->destination));
                if ($nick === '' || $channel === '') return;
                $users = $this->getUsers($nick);
                if (isset($users[$nick]["channels"][$channel])) {
                    $users[$nick]["prefix"] = trim($items->prefix);
                    $users[$nick]["user"] = trim($items->user);
                    $users[$nick]["hostname"] = trim($items->hostname);
                    unset($users[$nick]["channels"][$channel]);
                    $users[$nick]["connected"] = count($users[$nick]["channels"]) > 0;
                    $this->setUsers($users);
                }
            })(),
            "QUIT", "KILL" => (function() use ($nick, $items) {
                if ($nick === '') return;
                $users = $this->getUsers($nick);
                if (isset($users[$nick])) {
                    $users[$nick]["prefix"] = trim($items->prefix);
                    $users[$nick]["user"] = trim($items->user);
                    $users[$nick]["hostname"] = trim($items->hostname);
                    $users[$nick]["connected"] = false;
                    $users[$nick]["channels"] = [];
                    $this->setUsers($users);
                }
            })(),
            "302" => (function() use ($items) {
                $parts = explode(" ", strtolower(trim($items->trailing)));
                $users = $this->getUsers();
                foreach ($parts as $p) {
                    $uParts = explode("=", $p, 2);
                    if (count($uParts) === 2) {
                        $pParts = explode("@", $uParts[1], 2);
                        if (count($pParts) === 2) {
                            $users[$uParts[0]]["hostname"] = $pParts[1];
                        }
                    }
                }
                $this->setUsers($users);
            })(),
            "330" => (function() use ($items) {
                $parts = explode(" ", strtolower(trim($items->params)));
                if (count($parts) === 3 && $parts[1] !== '' && $parts[2] !== '') {
                    $users = $this->getUsers($parts[1]);
                    $users[$parts[1]]["account"] = $parts[2];
                    $users[$parts[1]]["account_updated"] = microtime(true);
                    $this->setUsers($users);
                }
            })(),
            default => null
        };

        $this->scriptEventHandlers($cmd, $items);
    }

    public function scriptEventHandlers(string $cmd, IrcMessage $items): void
    {
        if ($cmd === "PRIVMSG" && $items->nick === $this->getBotNick() && !isset($this->buckets[BUCKET_SELF_TRIGGER_EVENTS_FLAG])) {
            return;
        }

        if (!isset($this->buckets[BUCKET_EVENT_HANDLERS])) {
            return;
        }
        $eventHandlers = unserialize(base64_decode($this->buckets[BUCKET_EVENT_HANDLERS]));
        if (!is_array($eventHandlers)) {
            return;
        }

        foreach ($eventHandlers as $ser) {
            $data = unserialize($ser);
            if (!is_array($data)) {
                continue;
            }
            foreach ($data as $dataCmd => $value) {
                if ($cmd === $dataCmd) {
                    $value = str_replace(
                        [
                            TEMPLATE_DELIM . TEMPLATE_TRAILING . TEMPLATE_DELIM,
                            TEMPLATE_DELIM . TEMPLATE_NICK . TEMPLATE_DELIM,
                            TEMPLATE_DELIM . TEMPLATE_DESTINATION . TEMPLATE_DELIM,
                            TEMPLATE_DELIM . TEMPLATE_CMD . TEMPLATE_DELIM,
                            TEMPLATE_DELIM . TEMPLATE_PARAMS . TEMPLATE_DELIM,
                        ],
                        [
                            $items->trailing,
                            trim($items->nick),
                            trim($items->destination),
                            trim($items->cmd),
                            trim($items->params),
                        ],
                        (string)$value
                    );
                    $this->handleData("{$value}\n");
                }
            }
        }
    }

    public function handleData(string $data, bool $isSock = false, bool $auth = false, bool $exec = false): void
    {
        if (!$auth) {
            echo "\033[33m" . date("Y-m-d H:i:s", (int)microtime(true)) . " > \033[0m{$data}";
            $this->handleErrors($data);
        } else {
            $this->termEcho("*** auth = true");
        }

        $items = $this->parseData($data);
        if ($items === false) {
            return;
        }

        $this->writeOutBufferData($items);
        if ($items->destination === DEBUG_CHAN) {
            return;
        }

        if (!$auth && $isSock) {
            $this->logItems($items);
        }

        if (in_array($items->nick, $this->ignoreList, true)) {
            return;
        }

        if (isset($this->buckets[BUCKET_IGNORE_NEXT]) && $items->nick === $this->getBotNick()) {
            unset($this->buckets[BUCKET_IGNORE_NEXT]);
            return;
        }

        if ($items->prefix === IRC_HOST && str_contains(strtolower($items->trailing), "throttled")) {
            $this->termEcho("*** THROTTLED BY SERVER - REFUSING ALL OUTGOING MESSAGES TO SERVER FOR " . THROTTLE_LOCKOUT_TIME . " SECONDS ***");
            $this->throttleTime = microtime(true);
            return;
        }

        if ($items->cmd === "330") {
            $this->authenticate($items);
        }
        if ($items->cmd === "376") {
            $this->dojoin(INIT_CHAN_LIST);
        }
        if ($items->cmd === "NICK" && $items->nick === $this->getBotNick()) {
            $this->setBotNick(trim($items->trailing));
        }
        if ($items->cmd === "432") {
            $this->setBotNick(trim($items->params));
        }
        if ($items->cmd === "043") {
            $parts = explode(" ", trim($items->params));
            $this->setBotNick($parts[0]);
        }
        if ($items->cmd === "NOTICE" && $items->nick === "NickServ" && $items->trailing === NICKSERV_IDENTIFY_PROMPT) {
            if (file_exists(PASSWORD_FILE) && NICKSERV_IDENTIFY === "1") {
                $this->rawmsg("NickServ IDENTIFY " . trim(file_get_contents(PASSWORD_FILE)), true);
            }
            $this->startup();
        }

        $args = explode(" ", $items->trailing);
        $alias = $args[0];

        if ($this->isOperatorAlias($alias) || $this->isAdminAlias($alias) || $this->hasAccountList($alias)) {
            if (!$auth && $isSock) {
                $this->termEcho("authenticating \"{$alias}\"...");
                $this->adminData = $items->data;
                $this->adminIsSock = $isSock;
                $this->rawmsg("WHOIS {$items->nick}");
                return;
            }
        }

        $this->handleEvents($items);

        switch ($alias) {
            case ALIAS_ADMIN_NICK:
                if (count($args) === 2) {
                    $this->rawmsg(":" . $this->getBotNick() . " NICK :" . trim($args[1]));
                }
                break;
            case ALIAS_ADMIN_ALIAS_MACRO:
                $macro = implode(" ", array_slice($args, 1));
                $msg = "";
                $this->processAliasConfigMacro($macro, $msg);
                if ($msg !== '') {
                    $this->privmsg($items->destination, $items->nick, "alias config macro: {$msg}");
                }
                break;
            case ALIAS_ADMIN_QUIT:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "quit");
                    $this->processScripts($items, ALIAS_QUIT);
                }
                break;
            case ALIAS_ADMIN_PS:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "ps");
                    $this->ps($items);
                }
                break;
            case ALIAS_ADMIN_KILL:
                if (count($args) === 2) {
                    $this->writeOutBufferCommand($items, "kill");
                    $this->kill($items, (int)$args[1]);
                }
                break;
            case ALIAS_ADMIN_KILLALL:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "killall");
                    $this->killall($items);
                }
                break;
            case ALIAS_LIST:
                if ($this->checkNick($items, $alias) && count($args) === 1) {
                    $this->writeOutBufferCommand($items, "list");
                    $this->getList($items);
                }
                break;
            case ALIAS_LIST_AUTH:
                if ($this->checkNick($items, $alias) && count($args) === 1) {
                    $this->writeOutBufferCommand($items, "listauth");
                    $this->getListAuth($items);
                }
                break;
            case ALIAS_LOCK:
                if ($this->checkNick($items, $alias)) {
                    if (count($args) === 2) {
                        $this->writeOutBufferCommand($items, "lock");
                        $this->aliasLocks[$items->nick][$items->destination] = $args[1];
                        $this->privmsg($items->destination, $items->nick, "alias \"{$args[1]}\" locked for nick \"{$items->nick}\" in \"{$items->destination}\"");
                    } else {
                        $this->privmsg($items->destination, $items->nick, "syntax: " . ALIAS_LOCK . " <alias>");
                    }
                }
                break;
            case ALIAS_UNLOCK:
                if ($this->checkNick($items, $alias) && isset($this->aliasLocks[$items->nick][$items->destination])) {
                    $this->writeOutBufferCommand($items, "unlock");
                    $this->privmsg($items->destination, $items->nick, "alias \"{$this->aliasLocks[$items->nick][$items->destination]}\" unlocked for nick \"{$items->nick}\" in \"{$items->destination}\"");
                    unset($this->aliasLocks[$items->nick][$items->destination]);
                }
                break;
            case ALIAS_ADMIN_DEST_OVERRIDE:
                if (count($args) === 2) {
                    $this->writeOutBufferCommand($items, "dest_override");
                    $this->privmsg($items->destination, $items->nick, "destination override \"{$args[1]}\" set for nick \"{$items->nick}\" in \"{$items->destination}\"");
                    $this->destOverrides[$items->nick][$items->destination] = $args[1];
                } else {
                    $this->privmsg($items->destination, $items->nick, "syntax: " . ALIAS_ADMIN_DEST_OVERRIDE . " <dest>");
                }
                break;
            case ALIAS_ADMIN_DEST_CLEAR:
                if (isset($this->destOverrides[$items->nick][$items->destination])) {
                    $this->writeOutBufferCommand($items, "dest_clear");
                    $override = $this->destOverrides[$items->nick][$items->destination];
                    unset($this->destOverrides[$items->nick][$items->destination]);
                    $this->privmsg($items->destination, $items->nick, "destination override \"{$override}\" cleared for nick \"{$items->nick}\" in \"{$items->destination}\"");
                }
                break;
            case ALIAS_ADMIN_IGNORE:
                if (count($args) === 2) {
                    if (!in_array($args[1], $this->ignoreList, true)) {
                        $this->writeOutBufferCommand($items, "ignore");
                        $this->privmsg($items->destination, $items->nick, $this->getBotNick() . " set to ignore {$args[1]}");
                        $this->ignoreList[] = $args[1];
                        if (file_put_contents(IGNORE_FILE, implode("\n", $this->ignoreList)) === false) {
                            $this->privmsg($items->destination, $items->nick, "error saving ignore file");
                        }
                    }
                } else {
                    $this->privmsg($items->destination, $items->nick, "syntax: " . ALIAS_ADMIN_IGNORE . " <nick>");
                }
                break;
            case ALIAS_ADMIN_UNIGNORE:
                if (count($args) === 2) {
                    $key = array_search($args[1], $this->ignoreList, true);
                    if ($key !== false) {
                        $this->writeOutBufferCommand($items, "unignore");
                        $this->privmsg($items->destination, $items->nick, $this->getBotNick() . " set to listen to {$args[1]}");
                        unset($this->ignoreList[$key]);
                        $this->ignoreList = array_values($this->ignoreList);
                        if (file_put_contents(IGNORE_FILE, implode("\n", $this->ignoreList)) === false) {
                            $this->privmsg($items->destination, $items->nick, "error saving ignore file");
                        }
                    } else {
                        $this->privmsg($items->destination, $items->nick, "{$args[1]} not found in " . $this->getBotNick() . " ignore list");
                    }
                } else {
                    $this->privmsg($items->destination, $items->nick, "syntax: " . ALIAS_ADMIN_UNIGNORE . " <nick>");
                }
                break;
            case ALIAS_ADMIN_LIST_IGNORE:
                $this->writeOutBufferCommand($items, "ignorelist");
                if (count($this->ignoreList) > 0) {
                    $this->privmsg($items->destination, $items->nick, $this->getBotNick() . " ignore list: " . implode(", ", $this->ignoreList));
                } else {
                    $this->privmsg($items->destination, $items->nick, $this->getBotNick() . " isn't ignoring anyone");
                }
                break;
            case ALIAS_ADMIN_REHASH:
                if (count($args) === 1) {
                    if ($this->execLoad() === false) {
                        $this->privmsg($items->destination, $items->nick, "error reloading exec file");
                        $this->doquit();
                    } else {
                        $this->writeOutBufferCommand($items, "rehash");
                        $this->processExecHelps();
                        $this->processExecInits();
                        $this->processExecStartups();
                        $users = $this->getUsers();
                        foreach ($users[$this->getBotNick()]["channels"] ?? [] as $channel => $timestamp) {
                            $this->rawmsg("NAMES {$channel}");
                        }
                        $this->privmsg($items->destination, $items->nick, "successfully reloaded exec file (" . count($this->execList) . " aliases)");
                    }
                }
                break;
            case ALIAS_ADMIN_BUCKETS_DUMP:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "buckets_dump");
                    $this->bucketsDump($items);
                }
                break;
            case ALIAS_ADMIN_BUCKETS_SAVE:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "buckets_save");
                    $this->bucketsSave($items);
                }
                break;
            case ALIAS_ADMIN_BUCKETS_LOAD:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "buckets_load");
                    $this->bucketsLoad($items);
                }
                break;
            case ALIAS_ADMIN_BUCKETS_FLUSH:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "buckets_flush");
                    $this->bucketsFlush($items);
                }
                break;
            case ALIAS_ADMIN_BUCKETS_LIST:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "buckets_list");
                    $this->bucketsList($items);
                }
                break;
            case ALIAS_INTERNAL_RESTART:
                if (count($args) === 1 && $items->cmd === CMD_INTERNAL) {
                    define("RESTART", true);
                    $this->processScripts($items, ALIAS_QUIT);
                }
                break;
            case ALIAS_ADMIN_RESTART:
                if (count($args) === 1) {
                    $this->writeOutBufferCommand($items, "restart");
                    define("RESTART", true);
                    $this->processScripts($items, ALIAS_QUIT);
                }
                break;
            case ALIAS_ADMIN_EXEC_ERRORS:
                if (count($args) === 1) {
                    $n = count($this->execErrors);
                    if ($n > 0) {
                        $this->writeOutBufferCommand($items, "exec_load_errors");
                        $this->privmsg($items->destination, $items->nick, "exec load errors:");
                        $i = 0;
                        foreach ($this->execErrors as $filename => $messages) {
                            $isLastFile = ($i === ($n - 1));
                            $prefixChar = $isLastFile ? "  └─" : "  ├─";
                            $pipeChar   = $isLastFile ? "     " : "  │  ";
                            $this->privmsg($items->destination, $items->nick, $prefixChar . $filename);
                            foreach ($messages as $j => $msgText) {
                                $isLastMsg = ($j === count($messages) - 1);
                                $mPrefix = $isLastMsg ? "└─" : "├─";
                                $this->privmsg($items->destination, $items->nick, "{$pipeChar}{$mPrefix}{$msgText}");
                            }
                            $i++;
                        }
                    } else {
                        $this->privmsg($items->destination, $items->nick, "no errors");
                    }
                }
                break;
            default:
                $this->processScripts($items, "");
                $this->processScripts($items, ALIAS_ALL);
        }
    }

    public function execLoad(): bool|array
    {
        $this->help = [];
        $this->startup = [];
        $this->init = [];
        $this->execErrors = [];
        $this->execList = [];

        if (!file_exists(EXEC_FILE)) {
            return false;
        }

        $data = file_get_contents(EXEC_FILE);
        if ($data === false) {
            return false;
        }

        $this->buckets[BUCKET_EVENT_HANDLERS] = base64_encode(serialize([]));
        $lines = explode("\n", $data);

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') continue;
            $lineParts = explode(EXEC_DIRECTIVE_DELIM, $line, 2);
            $directive = $lineParts[0];
            $trailing = $lineParts[1] ?? '';

            switch ($directive) {
                case EXEC_INCLUDE:
                    $fileLines = [];
                    if (file_exists($trailing)) {
                        if (is_dir($trailing)) {
                            $this->loadDirectory($trailing, $fileLines, FILE_DIRECTIVE_EXEC);
                        } else {
                            $this->loadInclude($trailing, $fileLines, FILE_DIRECTIVE_EXEC);
                        }
                    }
                    foreach ($fileLines as $filename => $execLines) {
                        foreach ($execLines as $eLine) {
                            $this->loadExecLine($eLine, $filename);
                        }
                    }
                    break;
                case EXEC_STARTUP:
                    $this->startup[] = $trailing;
                    break;
                case EXEC_INIT:
                    $this->init[] = $trailing;
                    break;
                case EXEC_HELP:
                    $this->help[] = $trailing;
                    break;
                default:
                    $this->loadExecLine($line, EXEC_FILE);
                    break;
            }
        }

        return $this->execList;
    }

    public function loadInclude(string $filename, array &$lines, string $directive): void
    {
        if (!file_exists($filename)) {
            $this->termEcho("load_include: \"{$filename}\" not found");
            return;
        }
        $data = file_get_contents($filename);
        if ($data === false) {
            $this->termEcho("load_include: unable to read \"{$filename}\"");
            return;
        }

        $dirDelim = $directive . FILE_DIRECTIVE_DELIM;
        $dLen = strlen($dirDelim);
        foreach (explode("\n", $data) as $rawLine) {
            $line = trim($rawLine);
            if (str_starts_with($line, $dirDelim)) {
                $lines[$filename][] = substr($line, $dLen);
            }
        }
    }

    public function processAliasConfigMacro(string $macro, string &$msg, string $filename = ""): bool
    {
        $reserved = ["alias", "timeout", "repeat", "auto", "empty", "accounts", "accounts_wildcard", "cmds", "dests", "bucket_locks", "cmd", "servers", "saved", "line", "file", "help", "enabled"];
        $reservedArrays = ["accounts", "cmds", "dests", "bucket_locks", "servers", "help"];

        $parts = array_values(array_filter(explode(" ", $macro), fn($v) => trim($v) !== ''));
        if (count($parts) < 2) {
            $msg = "needs at least an action and an alias";
            return false;
        }

        $action = strtolower(array_shift($parts));
        $alias = strtolower(array_shift($parts));

        if (count($parts) === 0) {
            switch ($action) {
                case "enable":
                    if (!isset($this->execList[$alias])) {
                        $msg = "alias \"{$alias}\" not found";
                        return false;
                    }
                    $this->execList[$alias]["enabled"] = true;
                    $msg = "alias \"{$alias}\" successfully enabled";
                    return true;
                case "disable":
                    if (!isset($this->execList[$alias])) {
                        $msg = "alias \"{$alias}\" not found";
                        return false;
                    }
                    $this->execList[$alias]["enabled"] = false;
                    $msg = "alias \"{$alias}\" successfully disabled";
                    return true;
                case "add":
                    if (isset($this->execList[$alias])) {
                        $msg = "alias already exists";
                        return false;
                    }
                    $this->execList[$alias] = [
                        "alias"             => $alias,
                        "timeout"           => 5,
                        "repeat"            => 0,
                        "auto"              => 0,
                        "empty"             => 1,
                        "accounts"          => [],
                        "accounts_wildcard" => "",
                        "cmds"              => [],
                        "dests"             => [],
                        "bucket_locks"      => [],
                        "cmd"               => "",
                        "servers"           => [],
                        "saved"             => false,
                        "line"              => "",
                        "file"              => $filename,
                        "help"              => [],
                        "enabled"           => false,
                    ];
                    $msg = "alias \"{$alias}\" successfully added";
                    return true;
                case "delete":
                    if (!isset($this->execList[$alias])) {
                        $msg = "alias not found";
                        return false;
                    }
                    unset($this->execList[$alias]);
                    $msg = "alias \"{$alias}\" successfully deleted";
                    return true;
                default:
                    $msg = "invalid action";
                    return false;
            }
        }

        if (count($parts) === 1) {
            $key = $parts[0];
            switch ($action) {
                case "delete":
                    if (in_array($key, $reserved, true)) {
                        $msg = "unable to delete reserved element \"{$key}\"";
                        return false;
                    }
                    if (!isset($this->execList[$alias])) {
                        $msg = "alias not found";
                        return false;
                    }
                    if (!isset($this->execList[$alias][$key])) {
                        $msg = "element \"{$key}\" not found";
                        return false;
                    }
                    unset($this->execList[$alias][$key]);
                    $msg = "element \"{$key}\" successfully deleted";
                    return true;
                case "rename":
                    if (!isset($this->execList[$alias])) {
                        $msg = "alias not found";
                        return false;
                    }
                    if ($key === $alias) {
                        $msg = "good one you idiot";
                        return false;
                    }
                    $this->execList[$key] = $this->execList[$alias];
                    $this->execList[$key]["enabled"] = false;
                    unset($this->execList[$alias]);
                    $msg = "alias \"{$alias}\" successfully renamed (and disabled)";
                    return true;
                default:
                    $msg = "invalid action";
                    return false;
            }
        }

        $key = array_shift($parts);
        $value = implode(" ", $parts);

        switch ($action) {
            case "add":
                if (!isset($this->execList[$alias])) {
                    $msg = "alias not found";
                    return false;
                }
                if (isset($this->execList[$alias][$key])) {
                    $msg = "element already exists";
                    return false;
                }
                if (in_array($key, $reserved, true)) {
                    $msg = "unable to add reserved element \"{$key}\"";
                    return false;
                }
                $this->execList[$alias][$key] = $value;
                $this->execList[$alias]["enabled"] = false;
                $msg = "element successfully added (and alias disabled)";
                return true;
            case "edit":
                if (!isset($this->execList[$alias])) {
                    $msg = "element not found";
                    return false;
                }
                $valToSet = in_array($key, $reservedArrays, true) ? explode(",", $value) : $value;
                $this->execList[$alias][$key] = $valToSet;
                $this->execList[$alias]["enabled"] = false;
                $msg = "alias \"{$alias}\" element \"{$key}\" successfully updated with value \"{$value}\" (and alias disabled)";
                return true;
            default:
                $msg = "invalid action";
                return false;
        }
    }

    public function loadExecLine(string $line, string $filename, bool $saved = true): bool|array
    {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            return false;
        }

        $msg = "";
        if ($this->processAliasConfigMacro($line, $msg, $filename)) {
            $this->termEcho("EXEC ALIAS CONFIG MACRO SUCCESS: {$line} => {$msg}");
            return true;
        }

        $parts = explode(EXEC_DELIM, $line);
        if (count($parts) < 10) {
            $msg = "not enough parameters: {$line}";
            $this->termEcho($msg);
            $this->execErrors[$filename][] = $msg;
            return false;
        }

        $alias = trim($parts[0]);
        $timeout = trim($parts[1]);
        $repeat = trim($parts[2]);
        $auto = trim($parts[3]);
        $empty = trim($parts[4]);
        $accountsStr = trim($parts[5]);
        $cmdsStr = strtoupper(trim($parts[6]));
        $destsStr = strtolower(trim($parts[7]));
        $locksStr = strtoupper(trim($parts[8]));

        $accountsWildcard = "";
        $accounts = [];
        if ($accountsStr !== '') {
            if (in_array($accountsStr, ["@", "+", "*"], true)) {
                $accountsWildcard = $accountsStr;
            } else {
                $accounts = explode(",", $accountsStr);
                if (!in_array($this->getBotNick(), $accounts, true)) {
                    $accounts[] = $this->getBotNick();
                }
            }
        }

        $cmds = $cmdsStr !== '' ? explode(",", $cmdsStr) : [];
        $dests = $destsStr !== '' ? explode(",", $destsStr) : [];
        $locks = $locksStr !== '' ? explode(" ", $locksStr) : [];
        $cmd = trim(implode("|", array_slice($parts, 9)));

        if ($alias === '' || !is_numeric($timeout) || !is_numeric($repeat) || !in_array($auto, ["0", "1"], true) || !in_array($empty, ["0", "1"], true) || $cmd === '') {
            $msg = "invalid parameter: {$line}";
            $this->termEcho($msg);
            $this->execErrors[$filename][] = $msg;
            return false;
        }

        $cmdParts = explode(" ", $cmd);
        if (count($cmdParts) >= 2 && strtolower($cmdParts[0]) === "php" && str_contains($cmdParts[1], ".php")) {
            $cmdParts[0] = PHP_BINARY;
            $cmdParts[1] = __DIR__ . DIRECTORY_SEPARATOR . $cmdParts[1];
            if (!file_exists($cmdParts[1])) {
                $msg = "php file not found: {$line}";
                $this->termEcho($msg);
                $this->execErrors[$filename][] = $msg;
                return false;
            }
        }
        $cmd=implode(" ", $cmdParts);

        $result = [
            "alias"             => $alias,
            "timeout"           => (float)$timeout,
            "repeat"            => (float)$repeat,
            "auto"              => (int)$auto,
            "empty"             => (int)$empty,
            "accounts"          => $accounts,
            "accounts_wildcard" => $accountsWildcard,
            "cmds"              => $cmds,
            "dests"             => $dests,
            "bucket_locks"      => $locks,
            "cmd"               => $cmd,
            "servers"           => [],
            "saved"             => $saved,
            "line"              => $line,
            "file"              => $filename,
            "help"              => [],
            "enabled"           => true,
        ];

        $this->execList[$alias] = $result;
        $this->termEcho("SUCCESS: {$line}");
        return $result;
    }

    public function doquit(): void
    {
        global $argv;
        $this->termEcho("*** SETTING SHUTDOWN BUCKET ***");
        $this->buckets[BUCKET_SHUTDOWN] = "1";
        $t = microtime(true);
        $shutdownDelay = 10.0;

        while ((microtime(true) - $t) <= $shutdownDelay) {
            usleep(50000);
            $this->termEcho("number of processes remaining: " . count($this->handles));
            foreach ($this->handles as $i => $handle) {
                if (!$this->handleProcess($handle)) {
                    unset($this->handles[$i]);
                }
            }
            $this->handles = array_values($this->handles);
            if (count($this->handles) === 0) {
                $this->termEcho("*** all handles closed ***");
                break;
            }
        }

        if (count($this->handles) > 0) {
            $this->termEcho("*** KILLING REMAINING " . count($this->handles) . " HANDLE(S) ***");
            foreach ($this->handles as $handle) {
                if (is_resource($handle->process)) {
                    $this->killProcess($handle);
                }
            }
        }

        $this->termEcho("QUITTING SCRIPT");
        $this->finalizeSocket();
        if (IFACE_ENABLE === "1" && $this->outBuffer) {
            fclose($this->outBuffer);
        }

        if (defined("RESTART") && RESTART) {
            pcntl_exec(PHP_BINARY, $argv);
        }
        exit(0);
    }

    public function dojoin(string $chanlist): void
    {
        $this->rawmsg("JOIN {$chanlist}");
    }

    public function pingpong(string $data): bool
    {
        if (str_starts_with($data, "PING")) {
            $parts = explode(" ", $data, 2);
            $this->rawmsg("PONG " . trim($parts[1] ?? ''));
            return true;
        }
        return false;
    }

    public function parseData(string $data): IrcMessage|false
    {
        $validMap = $this->getValidDataCmd();
        $sub = trim($data, "\n\r\0\x0B");
        if ($sub === '') {
            return false;
        }

        $msg = new IrcMessage();
        $msg->data = $sub;

        if (str_starts_with($sub, ':')) {
            $i = strpos($sub, ' ');
            if ($i === false) return false;
            $msg->prefix = substr($sub, 1, $i - 1);
            $sub = substr($sub, $i + 1);
        }

        if (($i = strpos($sub, " :")) !== false) {
            $msg->trailing = substr($sub, $i + 2);
            $sub = substr($sub, 0, $i);
        }

        if (($i = strpos($sub, ' ')) !== false) {
            $msg->params = substr($sub, $i + 1);
            $sub = substr($sub, 0, $i);
        }

        $msg->cmd = $sub;
        if ($msg->cmd === '') {
            return false;
        }

        if ($msg->prefix !== '') {
            $prefix = $msg->prefix;
            if (($i = strpos($prefix, '!')) !== false) {
                $msg->nick = substr($prefix, 0, $i);
                $prefix = substr($prefix, $i + 1);
                if (($i = strpos($prefix, '@')) !== false) {
                    $msg->user = substr($prefix, 0, $i);
                    $msg->hostname = substr($prefix, $i + 1);
                }
            } else {
                $msg->nick = $prefix;
            }
        }

        $mask = ($msg->prefix !== '' ? '1' : '0') . ($msg->params !== '' ? '1' : '0') . ($msg->trailing !== '' ? '1' : '0');
        $cmdCheck = is_numeric($msg->cmd) ? '#' : $msg->cmd;

        $paramParts = explode(' ', $msg->params);
        if (count($paramParts) === 2 && (str_starts_with($paramParts[0], '#') || str_starts_with($paramParts[0], '&'))) {
            $msg->destination = $paramParts[0];
        } elseif (count($paramParts) === 1) {
            $msg->destination = $msg->params;
        }

        if (!isset($validMap[$cmdCheck]) || !in_array($mask, $validMap[$cmdCheck], true)) {
            return false;
        }

        return $msg;
    }

    public function processScripts(IrcMessage|false $items, string $reserved = ""): void
    {
        if ($items === false) {
            return;
        }

        $nick = trim($items->nick);
        $destination = trim($items->destination);
        $data = $items->data;
        $cmd = trim($items->cmd);
        $trailing = $items->trailing;

        if ($reserved === "") {
            if (isset($this->aliasLocks[$nick][$destination])) {
                $alias = $this->aliasLocks[$nick][$destination];
            } else {
                $parts = explode(" ", $items->trailing);
                $alias = strtolower(trim($parts[0]));
                $trailing = implode(" ", array_slice($parts, 1));
            }
            if (in_array($alias, $this->reservedAliases, true)) {
                return;
            }
        } else {
            $alias = $reserved;
        }

        if (!isset($this->execList[$alias]) || $this->execList[$alias]["enabled"] !== true) {
            return;
        }

        $execData = $this->execList[$alias];

        if (count($execData["cmds"]) > 0 && !in_array(strtoupper($cmd), $execData["cmds"], true)) {
            $this->termEcho("cmd-restricted alias \"{$alias}\" triggered on non-permitted cmd \"{$cmd}\" by \"{$nick}\"");
            return;
        }

        if (count($execData["dests"]) > 0 && !in_array(strtolower($destination), $execData["dests"], true)) {
            $this->termEcho("dest-restricted alias \"{$alias}\" triggered from non-permitted dest \"{$destination}\" by \"{$nick}\"");
            return;
        }

        if (count($execData["servers"]) > 0 && !in_array($items->server, $execData["servers"], true)) {
            $this->termEcho("server-restricted alias \"{$alias}\" triggered from non-permitted server \"{$items->server}\" by \"{$nick}\"");
            return;
        }

        if (!$this->checkNick($items, $alias) && !in_array($alias, $this->reservedAliases, true)) {
            return;
        }

        if ($execData["empty"] === 0 && $trailing === '' && $destination !== '' && $nick !== '') {
            return;
        }

        $itemsSerialized = base64_encode(serialize($items->toArray()));
        $start = microtime(true);
        $template = str_replace(
            [
                TEMPLATE_DELIM . TEMPLATE_TRAILING . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_NICK . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_DESTINATION . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_START . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_ALIAS . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_DATA . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_ITEMS . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_CMD . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_PARAMS . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_TIMESTAMP . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_SERVER . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_USER . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_HOSTNAME . TEMPLATE_DELIM,
                TEMPLATE_DELIM . TEMPLATE_PREFIX . TEMPLATE_DELIM,
            ],
            [
                escapeshellarg($trailing),
                escapeshellarg($nick),
                escapeshellarg($destination),
                escapeshellarg((string)START_TIME),
                escapeshellarg($alias),
                escapeshellarg($data),
                escapeshellarg($itemsSerialized),
                escapeshellarg($cmd),
                escapeshellarg($items->params),
                escapeshellarg((string)$start),
                escapeshellarg($items->server),
                escapeshellarg($items->user),
                escapeshellarg($items->hostname),
                escapeshellarg($items->prefix),
            ],
            $execData["cmd"]
        );
        $parts = explode(" ", $template);
        if ($parts[0] === "php") {
          $parts[0] = PHP_BINARY;
          $template = implode(" ", $parts);
        }

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];
        $process = proc_open($template, $descriptorspec, $pipes);
        if (!is_resource($process)) {
            $this->termEcho("ERROR SPAWNING PROCESS: {$template}");
            return;
        }

        $status = proc_get_status($process);
        if ($alias !== ALIAS_ALL) {
            $this->termEcho("EXEC [{$status['pid']}]: {$template}");
        }

        foreach ($execData["bucket_locks"] as $lockIndex) {
            $this->bucketLocks[$lockIndex] ??= [];
            $this->bucketLocks[$lockIndex][] = $status["pid"];
            $this->termEcho("BUCKET LOCK ADDED: {$lockIndex} BY PID {$status['pid']}");
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $handle = new ProcessHandle(
            process:      $process,
            command:      $template,
            pid:          $status["pid"],
            pipeStdin:    $pipes[0],
            pipeStdout:   $pipes[1],
            pipeStderr:   $pipes[2],
            alias:        $alias,
            bucketLocks:  $execData["bucket_locks"],
            template:     $execData["cmd"],
            allowEmpty:   $execData["empty"],
            timeout:      (float)$execData["timeout"],
            repeat:       (float)$execData["repeat"],
            autoPrivmsg:  (int)$execData["auto"],
            start:        $start,
            nick:         $items->nick,
            cmd:          $items->cmd,
            destination:  $items->destination,
            trailing:     $trailing,
            exec:         $execData,
            server:       $items->server,
            items:        $items->toArray()
        );

        $this->handles[] = $handle;
        $this->writeOutBufferProc($handle, "", "proc_start");
    }

    public function checkNick(IrcMessage $items, string $alias): bool
    {
        if ($items->nick === $this->getBotNick() || $alias === "*") {
            return true;
        }
        if ($items->cmd !== "PRIVMSG" && $items->cmd !== "NOTICE") {
            return true;
        }

        $lnick = strtolower($items->nick);
        if (!isset($this->timeDeltas[$lnick][$alias]["time"])) {
            $this->timeDeltas[$lnick][$alias] = [
                "time"       => microtime(true),
                "last_delta" => 0.0,
            ];
            return true;
        }

        $lastDelta = $this->timeDeltas[$lnick][$alias]["last_delta"];
        $thisDelta = microtime(true) - $this->timeDeltas[$lnick][$alias]["time"];
        $this->timeDeltas[$lnick][$alias]["last_delta"] = $thisDelta;

        if (abs($lastDelta - $thisDelta) < DELTA_TOLERANCE) {
            $this->timeDeltas[$lnick][$alias]["last"]["ignore_start"] = microtime(true);
            $this->termEcho("ALIAS \"{$alias}\" BY NICK \"{$items->nick}\" IGNORED FOR " . IGNORE_TIME . " SECONDS");
        } else {
            if (isset($this->timeDeltas[$lnick][$alias]["last"]["ignore_start"])) {
                if ((microtime(true) - $this->timeDeltas[$lnick][$alias]["last"]["ignore_start"]) >= IGNORE_TIME) {
                    unset($this->timeDeltas[$lnick][$alias]["last"]["ignore_start"]);
                    $this->termEcho("IGNORE CLEARED FOR ALIAS \"{$alias}\" BY NICK \"{$items->nick}\"");
                }
            }
        }

        return !isset($this->timeDeltas[$lnick][$alias]["last"]["ignore_start"]);
    }

    public function processTimedExecs(): void
    {
        foreach ($this->execList as $alias => $execData) {
            if ($execData["repeat"] <= 0) {
                continue;
            }
            $timeSet = false;
            if (isset($execData["repeat_time"])) {
                $timeSet = true;
                if ((microtime(true) - $execData["repeat_time"]) < $execData["repeat"]) {
                    continue;
                }
            }

            $data = ":exec " . CMD_INTERNAL . " :{$alias}";
            $items = $this->parseData($data);
            if ($items === false) {
                continue;
            }

            $this->execList[$alias]["repeat_time"] = microtime(true);
            if ($timeSet) {
                $this->processScripts($items);
            }
        }
    }

    public function isOperatorAlias(string $alias): bool
    {
        if (in_array($alias, $this->operatorAliases, true)) {
            return true;
        }
        return ($this->execList[$alias]["accounts_wildcard"] ?? '') === "@";
    }

    public function isAdminAlias(string $alias): bool
    {
        if (in_array($alias, $this->adminAliases, true)) {
            return true;
        }
        return ($this->execList[$alias]["accounts_wildcard"] ?? '') === "+";
    }

    public function authenticate(IrcMessage $items): void
    {
        $adminAccounts = explode(",", ADMIN_ACCOUNTS);
        $this->termEcho("\033[32mdetected cmd 330: {$this->adminData}\033[0m");
        $parts = explode(" ", $items->params);

        if ($this->adminData !== '' && count($parts) === 3 && $parts[0] === $this->getBotNick()) {
            $nick = $parts[1];
            $account = $parts[2];
            $adminItems = $this->parseData($this->adminData);
            if ($adminItems) {
                $args = explode(" ", $adminItems->trailing);
                $alias = $args[0];
                if ($adminItems->nick === $nick) {
                    $isOp = ($account === OPERATOR_ACCOUNT && $adminItems->hostname === OPERATOR_HOSTNAME);
                    $isAdmin = ($isOp || in_array($account, $adminAccounts, true));

                    if ($this->isOperatorAlias($alias)) {
                        if (!$isOp) {
                            $this->termEcho("authentication failure: \"{$account}\" attempted to run \"{$alias}\" but is not authorized (1)");
                        } else {
                            $this->rehandleAdminData();
                            return;
                        }
                    } elseif ($this->isAdminAlias($alias)) {
                        if (!$isAdmin) {
                            $this->termEcho("authentication failure: \"{$account}\" attempted to run \"{$alias}\" but is not authorized (2)");
                        } else {
                            $this->rehandleAdminData();
                            return;
                        }
                    } elseif ($this->hasAccountList($alias)) {
                        $allowed = $this->execList[$alias]["accounts"] ?? [];
                        $isWildcard = ($this->execList[$alias]["accounts_wildcard"] ?? '') === "*";
                        if (!$isAdmin && !$isWildcard && !in_array($account, $allowed, true)) {
                            $this->termEcho("authentication failure: \"{$account}\" attempted to run \"{$alias}\" but is not authorized (3)");
                        } else {
                            $this->rehandleAdminData();
                            return;
                        }
                    }
                }
            }
        }
        $this->adminData = "";
        $this->adminIsSock = false;
    }

    private function rehandleAdminData(): void
    {
        $tmpData = $this->adminData;
        $tmpIsSock = $this->adminIsSock;
        $this->adminData = "";
        $this->adminIsSock = false;
        $this->handleData($tmpData, $tmpIsSock, true);
    }

    public function ps(IrcMessage $items): void
    {
        $n = 0;
        foreach ($this->handles as $data) {
            if ($data->alias === "*") {
                continue;
            }
            $n++;
            $this->privmsg($items->destination, $items->nick, "[{$data->pid}] {$data->command}");
        }
        if ($n === 0) {
            $this->privmsg($items->destination, $items->nick, "no child processes currently running");
        }
    }

    public function killall(IrcMessage $items): void
    {
        if (count($this->handles) === 0) {
            $this->privmsg($items->destination, $items->nick, "no child processes currently running");
            return;
        }
        $messages = [];
        foreach ($this->handles as $index => $handle) {
            if ($handle->alias === ALIAS_ALL) {
                continue;
            }
            if ($this->killProcess($handle)) {
                $messages[] = "terminated pid {$handle->pid}: {$handle->command}";
                unset($this->handles[$index]);
            } else {
                $messages[] = "error terminating pid {$handle->pid}: {$handle->command}";
            }
        }
        $this->handles = array_values($this->handles);
        foreach ($messages as $m) {
            $this->privmsg($items->destination, $items->nick, $m);
        }
    }

    public function kill(IrcMessage $items, int $pid): void
    {
        foreach ($this->handles as $index => $handle) {
            if ($handle->pid === $pid) {
                if ($this->killProcess($handle)) {
                    unset($this->handles[$index]);
                    $this->privmsg($items->destination, $items->nick, "successfully terminated process with pid {$pid}");
                } else {
                    $this->privmsg($items->destination, $items->nick, "error terminating process with pid {$pid}");
                }
                return;
            }
        }
        $this->privmsg($items->destination, $items->nick, "unable to find process with pid {$pid}");
    }

    public function killProcess(ProcessHandle $handle): bool
    {
        $this->writeOutBufferProc($handle, "", "proc_kill");
        $psOut = (string)shell_exec("ps -eo pid,ppid");
        $this->killRecurse($handle->pid, explode("\n", $psOut));
        if (is_resource($handle->process)) {
            proc_close($handle->process);
        }
        return true;
    }

    public function killRecurse(int $pid, array $lines): void
    {
        foreach ($lines as $line) {
            $parts = array_values(array_filter(explode(" ", trim($line)), fn($v) => $v !== ''));
            if (count($parts) >= 2) {
                $cpid = (int)$parts[0];
                $ppid = (int)$parts[1];
                if ($ppid === $pid && $cpid > 0) {
                    echo "*** CHILD PROCESS FOUND: {$cpid} (Parent {$ppid})\n";
                    $this->killRecurse($cpid, $lines);
                }
            }
        }
        echo "*** KILLING PROCESS ID {$pid}\n";
        posix_kill($pid, SIGKILL);
    }

    public function loadDirectory(string $dir, array &$lines, string $directive): void
    {
        if (is_dir($dir)) {
            $this->termEcho("load_directory: \"{$dir}\" found");
            $files = scandir($dir);
            if ($files === false) return;
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $fullname = $dir . "/" . $file;
                if (is_dir($fullname)) {
                    $this->loadDirectory($fullname, $lines, $directive);
                } else {
                    $this->loadInclude($fullname, $lines, $directive);
                }
            }
        } else {
            $this->termEcho("load_directory: \"{$dir}\" not found");
        }
    }
}

// =========================================================================
// Entry Point
// =========================================================================

$bot = new IrcBot();
$bot->run();

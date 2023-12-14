<?php

@include("loader.php");

define('PREPEND_PATH', '../../');

if (!getLoggedAdmin()) {
    die("Unauthorized.");
}

class InlineDVPlugin extends AppGiniPlugin
{
    private $tn; // tablename
    private $table;

    function __construct($tn)
    {
        parent::__construct([
            "title" => "Inline-Detail-View Plugin",
            "name" => "inline-dv",
            "logo" => null
        ]);
        $tables = getTableList(true);
        if (!$tables)
            throw new Exception("Unable to get table list", 1);

        $table = $tables[$tn];
        if (!$table) throw new Exception("Unknown table", 1);

        $this->tn = $tn;
        $this->table = $table;
    }

    public function is_installed()
    {
        $code = $this->getHookCode($this->getFileName(), "{$this->tn}_init");
        if (!strlen($code)) return FALSE;
        return strpos($code, "// DO NOT DELETE THIS LINE") !== false;
    }

    protected function getHookCode($hook_file_path, $hook_function)
    {
        /* Check if hook file exists and is writable */
        $hook_code = @file_get_contents($hook_file_path);

        if (!$hook_code) return $this->error('add_to_hook', 'Unable to access hook file');
        /* Find hook function */
        preg_match('/function\s+' . $hook_function . '\s*\(/i', $hook_code, $matches, PREG_OFFSET_CAPTURE);
        if (count($matches) != 1) return $this->error('add_to_hook', 'Could not determine correct function location');

        /* start position of hook function */
        $hf_position = $matches[0][1];

        /* position of next function, or EOF position if this is the last function in the file */
        $nf_position = strlen($hook_code);
        preg_match('/function\s+[a-z0-9_]+\s*\(/i', $hook_code, $matches, PREG_OFFSET_CAPTURE, $hf_position + 10);
        if (count($matches)) $nf_position = $matches[0][1];

        /* hook function code */
        $old_function_code = substr($hook_code, $hf_position, $nf_position - $hf_position);
        return $old_function_code;
    }

    private function getFileName()
    {
        return $this->app_path . DIRECTORY_SEPARATOR . "hooks/{$this->tn}.php";
    }

    public function install()
    {
        $target = $this->getFileName();

        $code_dv = "// DO NOT DELETE THIS LINE
        require_once(\"" . "" . "plugins/{$this->name}/InlineDV.php\");
        \$plugin = new InlineDV(\"{$this->tn}\");
        \$plugin->render(\$selectedID, \$memberInfo, \$html, \$args);";
        $result = $this->replace_to_hook($target, "{$this->tn}_dv", $code_dv, "top");

        $code_init = "// DO NOT DELETE THIS LINE
        require_once(\"" . "" . "plugins/{$this->name}/InlineDV.php\");
        \$options->SeparateDV = 0;";
        $result = $this->replace_to_hook($target, "{$this->tn}_init", $code_init, "top");
    }

    public function uninstall()
    {
        $this->replace_to_hook($this->getFileName(), "{$this->tn}_dv", "// uninstalled", "top");
        $this->replace_to_hook($this->getFileName(), "{$this->tn}_init", "// uninstalled", "top");
    }
}

$tn = isset($_REQUEST["tn"]) ? $_REQUEST["tn"] : null;
$action = isset($_REQUEST["a"]) ? $_REQUEST["a"] : null;

if ($tn && $action) {

    $plugin = new InlineDVPlugin($tn);

    if ($action === "install") {
        $plugin->install();
    } else if ($action === "uninstall") {
        $plugin->uninstall();
    }
}



include(PREPEND_PATH . "header.php");

$tables = getTableList(false);
?>
<style>
    .list-group-item.enabled {
        border-left: 20px solid rgba(0, 128, 0, 0.5);
        font-weight: bold;
        background-color: rgba(0, 128, 0, 0.05);
    }

    .list-group-item.not-enabled {
        border-left: 20px solid silver;
        color: gray;
    }
</style>

<div class="row">
    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
        <ul class="list-group">
            <?php foreach ($tables as $tn => $table) {

                $p = new InlineDVPlugin($tn);
                $is_installed = $p->is_installed();
                $cls = $is_installed ? "enabled" : "not-enabled";
                $action = $is_installed ? "uninstall" : "install";
                $ico_cls = $is_installed ? "text-success" : "text-danger";
                $variation = $is_installed ? "danger" : "success";
                $icon = $is_installed ? "off" : "off";
                $href = "?tn={$tn}&a={$action}";

                $sql = "SELECT `pkValue` FROM `membership_userrecords` WHERE `tableName`='{$tn}' LIMIT 1";
                $pk_max = sqlValue($sql);
                $href_open = $is_installed ? "{$tn}_view.php?SelectedID={$pk_max}" : "{$tn}_view.php";
            ?>
                <li class="list-group-item clearfix <?= $cls ?>">
                    <img src="<?= PREPEND_PATH . $table[2] ?>" class="pull-left" />
                    <span class="pull-right btn-group">
                        <a href="<?= $href ?>" type="button" class="btn btn-default btn-lg">
                            <i class="glyphicon glyphicon-off <?= $ico_cls ?>"></i>
                        </a>
                        <a href="<?= PREPEND_PATH . $href_open ?>" class="btn btn-default btn-lg" target="_app">
                            <i class="glyphicon glyphicon-search"></i>
                        </a>
                    </span>
                    <p><?= $table[0] ?><br /><small class="text-muted"><?= $tn ?></small></p>
                </li>
            <?php } ?>
        </ul>
    </div>
    <div class="hidden-xs col-sm-6 col-md-4 col-lg-4">

        <div class="panel panel-info">
            <div class="panel-heading">
                <h5>Inline-Detail-View Plugin</h5>
            </div>
            <div class="panel-body">
                <p>Click the <i class="glyphicon glyphicon-off"></i> power-buttons to enable or disable inline-dv functionality per table. If enabled, table will be highlighted green.</p>
                <p>The <i class="glyphicon glyphicon-search"></i>-button will open a new browser tab and immediately show the results.</p>
                <p class="text-center">
                    <img class="" src="app-resources/img.png" style="width: 80%;" />
                </p>
            </div>
        </div>
        <div class="text-center"><a href="https://appgini.bizzworxx.de" target="_blank">
                <img src="app-resources/footer.png" width="128" />
            </a>
        </div>
    </div>
</div>

<?php
                                                                    include(PREPEND_PATH . "footer.php");

<?php
namespace regenix\console\commands;

use regenix\console\Commander;
use regenix\console\ConsoleCommand;
use regenix\lang\File;

class LoadCommand extends ConsoleCommand {

    const GROUP = 'load';

    public function __default(){
        $name = $this->args->get(0);
        $this->write('Load src: `%s`', $name);

        $cmd = Commander::current();
        if (!$cmd->apps[$name]){
            $this->writeln('[error: not exists]');
        } else {
            $tmpFile = new File(sys_get_temp_dir() . '/regenix/.current');
            $tmpFile->getParentFile()->mkdirs();
            if (file_put_contents($tmpFile->getPath(), $name))
                $this->writeln('[success]');
            else
                $this->writeln('[error: can`t write to temp directory');
        }
    }

    public function getInlineHelp(){
        return 'loads and sets a current src by the name, example: `load <name>`';
    }
}
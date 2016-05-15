<?php

namespace Assets\Console\Command;

class CollectShell extends AppShell
{

    public function getOptionParser()
    {
        return parent::getOptionParser()
            ->description(__d('assets', 'Scan directory and import record to database'))
            ->addArguments([
                'dir' => [
                    'help' => __d('assets', 'Path to scan'),
                    'required' => true,
                ],
            ])
            ->addOptions([
                'regex' => [
                    'help' => __d('assets', 'File name Regex'),
                    'required' => false,
                    'short' => 'r',
                ],
            ]);
    }

    public function main()
    {
        $dir = $this->args[0];
        $regex = '.*\.(jpg)';
        if (strpos($dir, ',') !== false) {
            $dir = explode(',', $dir);
        }
        if (isset($this->params['regex'])) {
            $regex = $this->params['regex'];
        }
        $Attachment = ClassRegistry::init('Assets.AssetsAttachment');
        $importTask = $Attachment->importTask((array)$dir, $regex);
        if (!empty($importTask['error'])) {
            $this->out('<error>Warnings/Errors:</error>');
            $tasks = $errors = 0;
            foreach ($importTask['error'] as $message) {
                $tasks++;
                if ($message) {
                    $this->err("\t$message");
                    $errors++;
                }
            }
            $this->out();
            if ($tasks - $errors > 0) {
                $this->out('<warning>' .
                    __d('assets', 'Task has {0} tasks and {1} errors?', $tasks, $errors) .
                    '</warning>');
                $continue = $this->in('Continue?', ['Y', 'n'], 'n');
                if ($continue == 'n') {
                    $this->out('Aborted');

                    return $this->_stop();
                }
            }
        }
        $result = $Attachment->runTask($importTask);

        $message = __d('assets', 'Processed {0} files with {1} errors', $result['imports'], $result['errors']);
        if ($result['errors'] == 0) {
            $message = sprintf('<warning>%s</warning>', $message);
            $this->out($message);
        } else {
            $message = sprintf('<error>%s</error>', $message);
            $this->err($message);
        }
    }

}

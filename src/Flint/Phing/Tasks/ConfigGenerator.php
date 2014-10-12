<?php

class ConfigGenerator
{
    protected $tags;
    protected $vars;

    public function __construct(array $tags, array $vars)
    {
        $this->tags         = $tags;
        $this->vars        = $vars;
    }

    protected function get($source, &$list)
    {
        $result = [];
        $list = $this->getFileList($source);

        foreach ($list as $file) {
            if (preg_match('#\.php#i', $file)) {
                $data = include_once($file);
            } else if (preg_match('#\.ini#i', $file)) {
                $data = parse_ini_file($file, true);
            } else {
                $data = [];
            }

            if (!is_array($data)) {
                throw new \LogicException(sprintf('file %s not a config file, data: %s', $file, $data));
            }

            $result = $this->merge($result, $data);
        }

        return $result;
    }

    public function generatePhpFile($source, $dest)
    {
        $list = [];
        $configData = $this->get($source, $list);

        $data = '<?php return '  . var_export($configData, true) . ';' . PHP_EOL;

        $data = strtr($data, $this->vars);

        file_put_contents($dest, $data);

        return $list;
    }

    protected function getTags($fileName)
    {
        $tags = explode('.', basename($fileName));


        return array_slice($tags, 1, -1);
    }

    protected function getFileList($sources)
    {
        $files   = [];
        $sources = is_array($sources) ? $sources : [$sources];
        foreach ($sources as $source) {
            /** @var \SplFileInfo $it */
            $it    = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source));
            while ($it->valid()) {
                $it->next();
                if (
                    !$it->isDot()
                    && $it->isFile()
                    && preg_match('#\/.*?\.(php|ini)#i', $it->getPathname())
                    && strpos($it->getPathname(), '.disabled') === false
                ) {
                    $tags = $this->getTags($it->getPathname());
                    if(sizeof($tags) < 2) {
                        throw new \ErrorException('config file name format: config_name.subsystem_name.environment.[php|ini] ' . $it->getPathname());
                    }

                    $isEqual = true;
                    foreach($tags as $k => $tag) {
                        if ($tag !== 'all' && $this->tags[$k] !== $tag) {
                            $isEqual = false;
                        }
                    }

                    if ($isEqual) {
                        $files[] = $it->getPathname();
                    }
                }
            }
        }

        sort($files);

        return $files;
    }

    protected function merge(array $to, array $from)
    {
        foreach ($from as $key => $value) {
            if (!is_array($value)) {
                if (is_int($key)) {
                    $to[] = $value;
                } else {
                    $to[$key] = $value;
                }
            } else {
                if (!isset($to[$key])) {
                    $to[$key] = array();
                }

                $to[$key] = $this->merge($to[$key], $value);
            }
        }

        return $to;
    }
}

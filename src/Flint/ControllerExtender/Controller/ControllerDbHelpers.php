<?php
namespace Flint\ControllerExtender\Controller;

trait ControllerDbHelpers
{
    private function map($template, $data, $flip = false)
    {
        if (!$template) {
            throw new \InvalidArgumentException('Template array is empty');
        }

        $result = [];
        foreach ($template as $keys) {
            if (is_array($keys)) {
                foreach ($keys as $k => $v) {
                    if ($flip) {
                        $a = $k;
                        $k = $v;
                        $v = $a;
                    }
                    if (array_key_exists($k, $data)) {
                        $result[$v] = $data[$k];
                    } else {
                        $result[$v] = null;
                    }
                }
            } else {
                $result[$keys] = $data[$keys];
            }
        }

        return $result;
    }


    protected function apiToDb($data, $template = null)
    {
        if (!$template) {
            $template = $this->fieldMap;
        }

        return $this->map($template, $data);
    }

    protected function dbToApi($data, $template = null)
    {
        if (!$template) {
            $template = $this->fieldMap;
        }

        return $this->map($template, $data, true);
    }

    protected function dbToApiIndex(array $index, $template = null)
    {
        $result = [];
        foreach ($index as $k => $data) {
            $result[$k] = $this->dbToApi($data, $template);
        }

        return $index;
    }

}

<?php

class SortedFileSet extends \FileSet
{
    protected $fields = [
        'filesIncluded',
        'filesNotIncluded',
        'filesExcluded',
        'dirsIncluded',
        'dirsNotIncluded',
        'dirsExcluded',
        'filesDeselected',
        'dirsDeselected',
    ];

    public function getDirectoryScanner(Project $p)
    {
        $ds = parent::getDirectoryScanner($p);
        $ref = new \ReflectionObject($ds);
        foreach($this->fields as $field) {
            $propRef = $ref->getProperty($field);
            $propRef->setAccessible(true);
            $value = $propRef->getValue($ds);
            if (is_array($value)) {
                sort($value);
                $propRef->setValue($ds, $value);
            }
        }

        return $ds;
    }
}

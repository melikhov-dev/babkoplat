<?php

class FlintPdoExecTask extends PDOSQLExecTask
{
    public function addSortedFileset(SortedFileSet $set) {
        return $this->addFileset($set);
    }
}

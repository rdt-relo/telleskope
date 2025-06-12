<?php

class TmpFileUtils
{
    private static $tmpfiles = [];

    public static function GetTemporaryFile(string $prefix = 'tskp_'): string
    {
        $tmpfilename = tempnam(sys_get_temp_dir(), $prefix);
        static::$tmpfiles[] = $tmpfilename;
        return $tmpfilename;
    }

    public static function CleanupTemporaryFiles(): void
    {
        array_map('unlink',
            array_filter(static::$tmpfiles, 'file_exists')
        );
    }
}

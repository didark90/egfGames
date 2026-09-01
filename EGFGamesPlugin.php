<?php

declare(strict_types=1);

// EGFGames - Course tool plugin: allows teachers to import .egf files and learners to play them.

class EGFGamesPlugin extends Plugin
{
    public $isCoursePlugin  = true;
    public $addCourseTool   = true;

    protected function __construct()
    {
        $settings = [
            'defaultVisibilityInCourseHomepage' => [
                'type'              => 'select',
                'translate_options' => true,
                'options'           => [
                    'visible'       => 'Visible',
                    'hidden'        => 'Hidden',
                ],
            ],
        ];

        parent::__construct('1.0.0', 'Hervé Yvis', $settings);
        $this->isCoursePlugin = true;
        $this->addCourseTool = true;
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }

    public function get_name(): string
    {
        return 'EGFGames';
    }

    public function getToolTitle(): string
    {
        $title = $this->get_lang('plugin_title');

        return !empty($title) ? $title : 'EGFGames';
    }

    public function install(): void
    {
        $this->install_course_fields_in_all_courses(true);
    }

    public function uninstall(): void
    {
        $this->uninstall_course_fields_in_all_courses();
    }

    public function performActionsAfterConfigure(): Plugin
    {
        if ( method_exists($this, 'syncCourseToolLinks') )
        {
            $this->syncCourseToolLinks();
        }

        return $this;
    }

    public function addCourseTool(int $courseId): void
    {
        $this->install_course_fields($courseId, true);
    }

    public function getStorageDir(int $courseId): string
    {
        $dir = api_get_path(SYS_PLUGIN_PATH).'EGFGames/storage/'.$courseId;

        if ( !is_dir($dir) )
        {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * @return list<array{id:string,filename:string,title:string,size:int,mtime:int}>
     */
    public function listGames(int $courseId): array
    {
        $dir        = $this->getStorageDir($courseId);
        $games      = [];

        $files      = glob($dir.'/*.egf') ?: [];

        foreach ($files as $path)
        {
            $filename   = basename($path);
            $id         = pathinfo($filename, PATHINFO_FILENAME);
            $games[]    = [
                'id'        => $id,
                'filename'  => $filename,
                'title'     => $this->readEgfTitle($path, $this->humanizeFilename($filename)),
                'size'      => (int) filesize($path),
                'mtime'     => (int) filemtime($path),
            ];
        }

        usort($games, static fn ($a, $b) => $b['mtime'] <=> $a['mtime']);

        return $games;
    }

    public function getGamePath(int $courseId, string $id): ?string
    {
        $id = $this->sanitizeId($id);

        if ('' === $id)
        {
            return null;
        }

        $path = $this->getStorageDir($courseId).'/'.$id.'.egf';

        return is_file($path) ? $path : null;
    }

    public function saveUploadedGame(int $courseId, array $file): array
    {
        if ( empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']) )
        {
            return ['ok' => false, 'error' => 'upload_invalid'];
        }

        if ( (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK )
        {
            return ['ok' => false, 'error' => 'upload_error'];
        }

        $original   = (string) ($file['name'] ?? '');
        $ext        = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if ('egf' !== $ext)
        {
            return ['ok' => false, 'error' => 'not_egf'];
        }

        if ( !$this->looksLikeEgfZip($file['tmp_name']) )
        {
            return ['ok' => false, 'error' => 'not_egf'];
        }

        $base = $this->sanitizeId( (string) pathinfo($original, PATHINFO_FILENAME) );

        if ('' === $base)
        {
            $base = 'jeu_'.date('Ymd_His');
        }

        $dir            = $this->getStorageDir($courseId);
        $filename       = $base.'.egf';
        $target         = $dir.'/'.$filename;
        $i              = 2;

        while ( is_file($target) )
        {
            $filename   = $base.'_'.$i.'.egf';
            $target     = $dir.'/'.$filename;
            ++$i;
        }

        if ( !move_uploaded_file($file['tmp_name'], $target) )
        {
            return ['ok' => false, 'error' => 'move_failed'];
        }

        @chmod($target, 0664);

        return ['ok' => true, 'filename' => $filename];
    }

    public function deleteGame(int $courseId, string $id): bool
    {
        $path = $this->getGamePath($courseId, $id);

        if (null === $path)
        {
            return false;
        }

        return @unlink($path);
    }

    public function sanitizeId(string $id): string
    {
        $id = basename($id);
        $id = preg_replace('/[^A-Za-z0-9._-]/', '_', $id) ?? '';
        $id = trim($id, '._-');

        return substr($id, 0, 80);
    }

    public function formatSize(int $bytes): string
    {
        $locale     = function_exists('api_get_language_isocode')
            ? strtolower((string) api_get_language_isocode())
            : 'fr';
        $useComma   = !str_starts_with($locale, 'en');
        $dec        = $useComma ? ',' : '.';

        if ($bytes < 1024)
        {
            return $bytes.' '.$this->get_lang('UnitBytes');
        }

        if ($bytes < 1024 * 1024)
        {
            return number_format($bytes / 1024, 1, $dec, ' ').' '.$this->get_lang('UnitKilobytes');
        }

        return number_format($bytes / (1024 * 1024), 1, $dec, ' ').' '.$this->get_lang('UnitMegabytes');
    }

    private function humanizeFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace(['_', '-'], ' ', $name);

        return trim($name) !== '' ? trim($name) : $filename;
    }

    private function looksLikeEgfZip(string $path): bool
    {
        $fh     = @fopen($path, 'rb');

        if (!$fh)
        {
            return false;
        }

        $magic  = fread($fh, 4);
        fclose($fh);

        if ("PK\x03\x04" !== $magic && "PK\x05\x06" !== $magic)
        {
            return false;
        }

        if ( !class_exists('ZipArchive') )
        {
            return true;
        }

        $zip = new ZipArchive();

        if ( true !== $zip->open($path) )
        {
            return false;
        }

        $hasMimetype    = false !== $zip->getFromName('mimetype');
        $hasContainer   = false !== $zip->locateName('META-INF/container.xml');
        $hasCore        = false !== $zip->locateName('egf/egf.xml');
        $zip->close();

        return $hasMimetype || $hasContainer || $hasCore;
    }

    private function readEgfTitle(string $path, string $fallback): string
    {
        if ( !class_exists('ZipArchive') )
        {
            return $fallback;
        }

        $zip = new ZipArchive();

        if ( true !== $zip->open($path) )
        {
            return $fallback;
        }

        $corePath   = 'egf/egf.xml';
        $container  = $zip->getFromName('META-INF/container.xml');

        if ( false !== $container && preg_match('/full-path="([^"]+)"/', $container, $m) )
        {
            $corePath = $m[1];
        }

        $xml = $zip->getFromName($corePath);
        $zip->close();

        if (false === $xml)
        {
            return $fallback;
        }

        if ( preg_match('/<dc:title[^>]*>(.*?)<\/dc:title>/is', $xml, $m) )
        {
            $title = html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);

            return '' !== $title ? $title : $fallback;
        }

        return $fallback;
    }
}

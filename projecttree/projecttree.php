<?php

class ProjectTree {
	public static $confFile = __DIR__ . '/projects.conf';
	private $asset_url;
    
	private array $projects = [];
    private ?array $currentProject = null;
    private $currentFile = '';

    public function __construct(?string $currentFile = null) {
        $this->asset_url = rtrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/') . '/';
	    $this->currentFile = $currentFile;
        if (file_exists(self::$confFile)) {
            $data = unserialize(file_get_contents(self::$confFile));
            $this->projects = is_array($data) ? $data : [];
        }

        if ($currentFile !== null) {
            $this->currentProject = $this->findProjectByFile($currentFile);
        }
    }
    
    public function getHTML() {
	    $ret = '<link rel="stylesheet" type="text/css" href="'.$this->asset_url.'projecttree.css"\>';
	    $ret .= '<div id="project-tree" data-current-file="'.htmlspecialchars($this->currentFile).'"></div>';
	    $ret .= '<script src="'.$this->asset_url.'projecttree.js"></script>';
	    $ret .= '<link rel="stylesheet" type="text/css" href="'.$this->asset_url.'../css/lightbox.css"\>';
	    $ret .= '<script src="'.$this->asset_url.'../javascript/lightbox.js"></script>';
	    return $ret;
    }

    private function findProjectByFile(string $file): ?array {
        foreach ($this->projects as $project) {
            foreach ($project['files'] as $entry) {
                $filePath = is_array($entry) ? ($entry['path'] ?? null) : $entry;
                if ($filePath === $file) {
                    return $project;
                }
            }
        }
        return null;
    }

    public function getCurrentProject(): ?array {
        return $this->currentProject;
    }

    public function getProjectFiles(): array {
        if (!$this->currentProject) return [];
        return array_map(function ($file) {
            return is_array($file) ? ($file['path'] ?? '') : $file;
        }, $this->currentProject['files']);
    }

    public function getAllProjects(): array {
        return $this->projects;
    }

};

?>

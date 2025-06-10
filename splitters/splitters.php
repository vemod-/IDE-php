<?php

class SplitterFactory {
	private string $assetUrl;
	private string $defaultCellClass = 'insidedivpad';

	public function __construct() {
		$this->assetUrl = rtrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/') . '/';
	}

	private function buildSplitter(string $type, string $id, string $oppositeCells): string {
		$splitClass = $type === 'horiz' ? 'horiz_split' : 'vert_split';
		return "<div class=\"$splitClass\" id=\"$id\" onmousedown=\"dragStart(event,this.id,'$oppositeCells')\"></div>";
	}

	private function buildContainer(string $type, string $splitterId, string $oppositeCells): string {
		$containerClass = $type === 'horiz' ? 'horiz_container' : 'vert_container';
		$splitter = $this->buildSplitter($type, $splitterId, $oppositeCells);
		return "<div class=\"$containerClass\">$splitter</div>";
	}

	private function buildAttributes(array $attrs): string {
		$parts = [];
		foreach ($attrs as $key => $val) {
			if ($val !== null && $val !== '') {
				$parts[] = "$key=\"" . htmlspecialchars($val, ENT_QUOTES) . "\"";
			}
		}
		return $parts ? ' ' . implode(' ', $parts) : '';
	}

	private function buildCell(array $attrs, string $content): string {
		$attrs['class'] ??= $this->defaultCellClass;
		return "<td{$this->buildAttributes($attrs)}>$content</td>";
	}

	public function buildNeutralCell(string $id, string $style, string $content): string {
		return $this->buildCell([
			'id' => $id,
			'style' => $style
		], $content);
	}

	public function buildSplitterCell(string $id, string $style, string $splitterId, string $oppositeIds, string $content, string $type): string {
		$attrs = [
			'id' => $id,
			'style' => $style,
		];

		$count = count(explode('%', $oppositeIds));
		if ($type === 'horiz') {
			$attrs['colspan'] = $count;
		} elseif ($type === 'vert') {
			$attrs['rowspan'] = $count;
		}

		$container = $this->buildContainer($type, $splitterId, $oppositeIds);
		return $this->buildCell($attrs, $container . $content);
	}
	public function buildVertCell(string $id, string $style, string $splitterId, string $oppositeIds, string $content): string {
		return $this->buildSplitterCell($id, $style, $splitterId, $oppositeIds, $content, "vert");
	}

	public function buildHorizCell(string $id, string $style, string $splitterId, string $oppositeIds, string $content): string {
		return $this->buildSplitterCell($id, $style, $splitterId, $oppositeIds, $content, "horiz");
	}

	public function buildAssets(): string {
		$ret = <<<HTML
        <script type="text/javascript" src="{$this->assetUrl}splitters.js"></script>
        <link rel="stylesheet" type="text/css" href="{$this->assetUrl}splitters.css">
        HTML;
        $ret .= $this->buildInitScript();
        return $ret;
	}

    private function buildInitScript(): string {
        $imgPath = $this->assetUrl . 'splitterbg.png';
        return <<<JS
        <script>
        function init_splitters() {
            const img_path = "{$imgPath}";
            let elem_array = document.querySelectorAll('.horiz_split');
            for (let i = 0; i < elem_array.length; i++) {
                elem_array[i].style.top = '-4px';
                elem_array[i].onmouseover = function() {
                    this.style.backgroundImage = 'url(' + img_path + ')';
                };
                elem_array[i].onmouseout = function() {
                    this.style.background = 'transparent';
                };
            }

            elem_array = document.querySelectorAll('.vert_split');
            for (let i = 0; i < elem_array.length; i++) {
                elem_array[i].style.left = '-4px';
                elem_array[i].onmouseover = function() {
                    this.style.backgroundImage = 'url(' + img_path + ')';
                };
                elem_array[i].onmouseout = function() {
                    this.style.background = 'transparent';
                };
            }
        }
        document.addEventListener("DOMContentLoaded", init_splitters);
        </script>
        JS;
    }
}
?>
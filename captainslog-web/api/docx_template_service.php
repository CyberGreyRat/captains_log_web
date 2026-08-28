<?php
declare(strict_types=1);

final class DocxTemplateService
{
    private ZipArchive $zip;
    private DOMDocument $doc;
    private DOMXPath $xp;
    private string $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function __construct(private string $path)
    {
        $this->zip = new ZipArchive();
        $result = $this->zip->open($path);
        if ($result !== true) throw new RuntimeException('DOCX konnte nicht geöffnet werden. ZIP-Code: '.$result);
        $xml = $this->zip->getFromName('word/document.xml');
        if ($xml === false) throw new RuntimeException('word/document.xml fehlt.');
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->preserveWhiteSpace = true;
        if (!$this->doc->loadXML($xml, LIBXML_NONET)) throw new RuntimeException('Word-XML ist ungültig.');
        $this->xp = new DOMXPath($this->doc);
        $this->xp->registerNamespace('w', $this->w);
    }

    public function replaceText(string $search, string $replace): void
    {
        foreach ($this->xp->query('//w:t') as $node) {
            if (str_contains((string)$node->nodeValue, $search)) {
                $node->nodeValue = str_replace($search, $replace, (string)$node->nodeValue);
            }
        }
    }

    public function cleanProjectBrackets(): void
    {
        $texts = $this->xp->query('//w:t');
        for ($i = 0; $i < min(25, $texts->length); $i++) {
            $node = $texts->item($i);
            if ($node && in_array(trim((string)$node->nodeValue), ['[', ']'], true)) $node->nodeValue = '';
        }
    }

    public function fillCover(array $customer, array $contractor, array $approval): void
    {
        $partyTables = [];

        foreach ($this->xp->query('//w:tbl') as $table) {
            $firstRowText = trim($this->textOf(
                $this->xp->query('./w:tr[1]', $table)->item(0) ?: $table
            ));

            if (
                str_contains($firstRowText, 'Auftraggeber') &&
                str_contains($firstRowText, 'Auftragnehmer')
            ) {
                $partyTables[] = $table;
            }
        }

        foreach ($partyTables as $table) {
            $rows = $this->xp->query('./w:tr', $table);

            $mapping = [
                1 => [$customer['company'] ?? '', $contractor['company'] ?? ''],
                2 => [$customer['name'] ?? '', $contractor['name'] ?? ''],
                3 => [$customer['street'] ?? '', $contractor['street'] ?? ''],
                4 => [$customer['city'] ?? '', $contractor['city'] ?? ''],
                5 => [$customer['phone'] ?? '', $contractor['phone'] ?? ''],
                6 => [$customer['email'] ?? '', $contractor['email'] ?? ''],
                7 => [$customer['info'] ?? '', $contractor['info'] ?? ''],
            ];

            foreach ($mapping as $rowIndex => [$customerValue, $contractorValue]) {
                $row = $rows->item($rowIndex);
                if (!$row) continue;

                $cells = $this->xp->query('./w:tc', $row);

                // Vorlage: Beschriftung | Wert Auftraggeber | Beschriftung | Wert Auftragnehmer
                // Die Beschriftungszellen 0 und 2 bleiben unverändert.
                if ($cells->length >= 4) {
                    $this->setCellText($cells->item(1), $customerValue);
                    $this->setCellText($cells->item(3), $contractorValue);
                }
            }
        }

        $this->fillApprovalValue('Autor:', $approval['author'] ?? '');
        $this->fillApprovalValue('Projektleiter:', $approval['manager'] ?? '');
        $this->fillApprovalValue(
            'Auftragnehmer:',
            ($contractor['name'] ?? '') !== ''
                ? $contractor['name']
                : ($contractor['company'] ?? '')
        );
    }

    private function setCellText(?DOMNode $cell, string $value): void
    {
        if (!$cell) return;

        $paragraphs = $this->xp->query('./w:p', $cell);
        $paragraph = $paragraphs->item(0);

        if (!$paragraph) {
            $paragraph = $this->doc->createElementNS($this->w, 'w:p');
            $cell->appendChild($paragraph);
        }

        foreach ($this->xp->query('./w:p', $cell) as $existingParagraph) {
            while ($existingParagraph->firstChild) {
                $existingParagraph->removeChild($existingParagraph->firstChild);
            }
        }

        $run = $this->doc->createElementNS($this->w, 'w:r');
        $text = $this->doc->createElementNS($this->w, 'w:t');
        $text->setAttribute('xml:space', 'preserve');
        $text->appendChild($this->doc->createTextNode(trim($value)));
        $run->appendChild($text);
        $paragraph->appendChild($run);
    }

    private function fillApprovalValue(string $label, string $value): void
    {
        if (trim($value) === '') return;

        foreach ($this->xp->query('//w:p') as $paragraph) {
            if (trim($this->textOf($paragraph)) !== $label) continue;

            $cell = $this->xp->query('ancestor::w:tc[1]', $paragraph)->item(0);
            if (!$cell) continue;

            $row = $this->xp->query('ancestor::w:tr[1]', $paragraph)->item(0);
            if (!$row) continue;

            $cells = $this->xp->query('./w:tc', $row);
            $position = -1;

            foreach ($cells as $index => $candidate) {
                if ($candidate->isSameNode($cell)) {
                    $position = $index;
                    break;
                }
            }

            if ($position >= 0 && $cells->item($position + 1)) {
                $this->setCellText($cells->item($position + 1), $value);
            }
        }
    }

    public function fillTableByAnchor(string $anchor, array $rows, int $columns = 5): bool
    {
        foreach ($this->xp->query('//w:tbl') as $table) {
            $first = $this->xp->query('./w:tr[2]/w:tc[1]//w:t', $table)->item(0);
            if ($first && trim((string)$first->nodeValue) === $anchor) {
                $this->replaceBody($table, $rows, $columns);
                return true;
            }
        }
        return false;
    }

    public function fillTableAfterExactHeading(string $heading, array $rows, int $columns): bool
    {
        foreach ($this->xp->query('//w:p') as $paragraph) {
            $style = $this->xp->query('./w:pPr/w:pStyle/@w:val', $paragraph)->item(0)?->nodeValue ?? '';
            if (!preg_match('/^(Heading|berschrift)/i', $style)) continue;
            if (trim($this->textOf($paragraph)) !== $heading) continue;
            $node = $paragraph->nextSibling;
            while ($node) {
                if ($node instanceof DOMElement && $node->namespaceURI === $this->w && $node->localName === 'tbl') {
                    $this->replaceBody($node, $rows, $columns);
                    return true;
                }
                $node = $node->nextSibling;
            }
        }
        return false;
    }

    public function insertProjectDescription(string $description): void
    {
        if (trim($description) === '') return;
        foreach ($this->xp->query('//w:p') as $paragraph) {
            $style = $this->xp->query('./w:pPr/w:pStyle/@w:val', $paragraph)->item(0)?->nodeValue ?? '';
            if (!preg_match('/^(Heading|berschrift)1$/i', $style)) continue;
            if (trim($this->textOf($paragraph)) !== 'Einführung') continue;
            $new = $this->paragraph('Projektbeschreibung: '.$description, false);
            $paragraph->parentNode?->insertBefore($new, $paragraph->nextSibling);
            return;
        }
    }

    public function save(): void
    {
        $xml = $this->doc->saveXML();
        if ($xml === false) throw new RuntimeException('Word-XML konnte nicht gespeichert werden.');
        $this->zip->addFromString('word/document.xml', $xml);
        $this->zip->close();
    }

    private function replaceBody(DOMElement $table, array $rows, int $columns): void
    {
        $existing = [];
        foreach ($this->xp->query('./w:tr', $table) as $row) $existing[] = $row;
        if (!$existing) return;
        for ($i = 1; $i < count($existing); $i++) $table->removeChild($existing[$i]);
        if (!$rows) $rows = [array_fill(0, $columns, '-')];
        foreach ($rows as $row) $table->appendChild($this->row(array_pad(array_slice(array_values($row), 0, $columns), $columns, '')));
    }

    private function row(array $values): DOMElement
    {
        $row = $this->doc->createElementNS($this->w, 'w:tr');
        foreach ($values as $value) {
            $cell = $this->doc->createElementNS($this->w, 'w:tc');
            $cell->appendChild($this->doc->createElementNS($this->w, 'w:tcPr'));
            $cell->appendChild($this->paragraph((string)$value, false));
            $row->appendChild($cell);
        }
        return $row;
    }

    private function paragraph(string $value, bool $bold): DOMElement
    {
        $p = $this->doc->createElementNS($this->w, 'w:p');
        $r = $this->doc->createElementNS($this->w, 'w:r');
        if ($bold) {
            $rp = $this->doc->createElementNS($this->w, 'w:rPr');
            $rp->appendChild($this->doc->createElementNS($this->w, 'w:b'));
            $r->appendChild($rp);
        }
        $parts = preg_split('/\R/u', $value) ?: [$value];
        foreach ($parts as $i => $part) {
            $t = $this->doc->createElementNS($this->w, 'w:t');
            $t->setAttribute('xml:space', 'preserve');
            $t->appendChild($this->doc->createTextNode($part));
            $r->appendChild($t);
            if ($i < count($parts)-1) $r->appendChild($this->doc->createElementNS($this->w, 'w:br'));
        }
        $p->appendChild($r);
        return $p;
    }

    private function textOf(DOMNode $node): string
    {
        $parts=[];foreach($this->xp->query('.//w:t',$node) as $text)$parts[]=$text->nodeValue;return implode('',$parts);
    }
}

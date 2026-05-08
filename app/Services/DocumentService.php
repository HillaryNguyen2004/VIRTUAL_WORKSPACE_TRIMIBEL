<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Repositories\DocumentRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DocumentService
{
    public function __construct(
        private DocumentRepository $repository,
        private DocumentConversionService $converter
    ) {
    }

    /**
     * Create a new document
     */
    public function createDocument(User $user, string $title, string $type = 'docs'): Document
    {
        $initialContent = $type === 'docs'
            ? '<!doctype html><html><body><p></p></body></html>'
            : '';

        $document = Document::create([
            'owner_id' => $user->id,
            'title' => $title,
            'type' => $type,
            'html_path' => 'pending',
            'searchable_text' => '',
            'last_edited_by' => $user->id,
        ]);

        $contentPath = "documents/{$document->id}/content.html";
        Storage::disk()->put($contentPath, $initialContent);
        $document->update(['html_path' => $contentPath]);

        return $document;
    }

    /**
     * Update document title and content
     */
    public function updateDocument(Document $document, User $user, array $data): Document
    {
        if (!$document->html_path) {
            $contentPath = "documents/{$document->id}/content.html";
            Storage::disk()->makeDirectory("documents/{$document->id}");
            $document->update(['html_path' => $contentPath]);
        }

        Storage::disk()->put($document->html_path, $data['content'] ?? '');

        $document->update([
            'title' => $data['title'],
            'searchable_text' => $this->extractSearchableText($data['content'] ?? ''),
            'last_edited_by' => $user->id,
        ]);

        return $document;
    }

    /**
     * Get document content
     */
    public function getDocumentContent(Document $document): string
    {
        if ($document->html_path && Storage::disk()->exists($document->html_path)) {
            return Storage::disk()->get($document->html_path);
        }

        return '';
    }

    /**
     * Import DOCX to HTML
     */
    public function importDocx(Document $document, User $user, $file): Document
    {
        $importPath = $file->store("documents/{$document->id}/imports", config('filesystems.default'));
        $contentPath = $document->html_path ?: "documents/{$document->id}/content.html";
        [$tempDocxRelative, $tempDocxPath] = $this->copyStoredToLocal($importPath);
        [$tempHtmlRelative, $tempHtmlPath] = $this->createLocalTempPath('html');

        $this->converter->importDocxToHtml($tempDocxPath, $tempHtmlPath);

        $html = Storage::disk('local')->exists($tempHtmlRelative)
            ? Storage::disk('local')->get($tempHtmlRelative)
            : '';

        if (trim($html) === '') {
            Storage::disk('local')->delete($tempDocxRelative);
            Storage::disk('local')->delete($tempHtmlRelative);
            throw new \RuntimeException(__('online_docs.import_empty'));
        }

        Storage::disk()->put($contentPath, $html);
        Storage::disk('local')->delete($tempDocxRelative);
        Storage::disk('local')->delete($tempHtmlRelative);

        $document->update([
            'html_path' => $contentPath,
            'docx_path' => $importPath,
            'searchable_text' => $this->extractSearchableText($html),
            'last_edited_by' => $user->id,
        ]);

        return $document;
    }

    /**
     * Export document to DOCX
     */
    public function exportDocx(Document $document): string
    {
        if ($document->docx_path && Storage::disk()->exists($document->docx_path)) {
            return $document->docx_path;
        }

        if (!$document->html_path) {
            $contentPath = "documents/{$document->id}/content.html";
            Storage::disk()->makeDirectory("documents/{$document->id}");
            Storage::disk()->put($contentPath, '');
            $document->update(['html_path' => $contentPath]);
        }

        $exportDir = "documents/{$document->id}/exports";
        Storage::disk()->makeDirectory($exportDir);

        $exportPath = $exportDir . '/' . now()->format('YmdHis') . '.docx';
        [$tempHtmlRelative, $tempHtmlPath] = $this->createLocalTempPath('html');
        Storage::disk('local')->put($tempHtmlRelative, Storage::disk()->get($document->html_path));
        [$tempDocxRelative, $tempDocxPath] = $this->createLocalTempPath('docx');

        $this->converter->exportHtmlToDocx($tempHtmlPath, $tempDocxPath);

        Storage::disk()->put($exportPath, fopen($tempDocxPath, 'rb'));
        Storage::disk('local')->delete($tempHtmlRelative);
        Storage::disk('local')->delete($tempDocxRelative);

        $document->update([
            'docx_path' => $exportPath,
        ]);

        return $exportPath;
    }

    /**
     * Ensure document has a base DOCX file and return its storage path
     */
    public function ensureDocxPath(Document $document): string
    {
        if ($document->docx_path && Storage::disk()->exists($document->docx_path)) {
            $existingSize = Storage::disk()->size($document->docx_path);
            if ($existingSize > 0) {
                return $document->docx_path;
            }
        }

        if (!$document->html_path) {
            $contentPath = "documents/{$document->id}/content.html";
            Storage::disk()->makeDirectory("documents/{$document->id}");
            Storage::disk()->put($contentPath, '');
            $document->update(['html_path' => $contentPath]);
        }

        $docxPath = "documents/{$document->id}/document.docx";
        Storage::disk()->makeDirectory("documents/{$document->id}");

        $currentHtml = Storage::disk()->exists($document->html_path)
            ? Storage::disk()->get($document->html_path)
            : '';

        if (trim($currentHtml) === '') {
            Storage::disk()->put(
                $document->html_path,
                '<!doctype html><html><body><p></p></body></html>'
            );
        }

        [$tempHtmlRelative, $tempHtmlPath] = $this->createLocalTempPath('html');
        Storage::disk('local')->put($tempHtmlRelative, Storage::disk()->get($document->html_path));
        [$tempDocxRelative, $tempDocxPath] = $this->createLocalTempPath('docx');

        $this->converter->exportHtmlToDocx($tempHtmlPath, $tempDocxPath);
        Storage::disk()->put($docxPath, fopen($tempDocxPath, 'rb'));

        Storage::disk('local')->delete($tempHtmlRelative);
        Storage::disk('local')->delete($tempDocxRelative);

        $document->update([
            'docx_path' => $docxPath,
        ]);

        return $docxPath;
    }

    /**
     * Rebuild HTML content and searchable_text from the persisted DOCX file.
     * This is required after OnlyOffice callbacks because OnlyOffice writes DOCX,
     * while search reads searchable_text derived from HTML content.
     */
    public function syncHtmlAndSearchFromDocx(Document $document): void
    {
        if ($document->type !== 'docs') {
            return;
        }

        $docxPath = $this->ensureDocxPath($document);
        if (!Storage::disk()->exists($docxPath)) {
            return;
        }

        $contentPath = $document->html_path ?: "documents/{$document->id}/content.html";
        [$tempDocxRelative, $tempDocxPath] = $this->copyStoredToLocal($docxPath);
        [$tempHtmlRelative, $tempHtmlPath] = $this->createLocalTempPath('html');

        try {
            $this->converter->importDocxToHtml($tempDocxPath, $tempHtmlPath);

            $html = Storage::disk('local')->exists($tempHtmlRelative)
                ? (string) Storage::disk('local')->get($tempHtmlRelative)
                : '';

            if (trim($html) === '') {
                return;
            }

            Storage::disk()->put($contentPath, $html);

            $document->update([
                'html_path' => $contentPath,
                'searchable_text' => $this->extractSearchableText($html),
            ]);
        } catch (\Throwable $error) {
            Log::warning('online_docs.reindex_from_docx_failed', [
                'document_id' => $document->id,
                'message' => $error->getMessage(),
            ]);
        } finally {
            Storage::disk('local')->delete($tempDocxRelative);
            Storage::disk('local')->delete($tempHtmlRelative);
        }
    }

        /**
         * Ensure document has a base PPTX file and return its storage path
         */
        public function ensurePptxPath(Document $document): string
        {
                if ($document->pptx_path && Storage::disk()->exists($document->pptx_path)) {
                    $existingSize = Storage::disk()->size($document->pptx_path);
                    if ($existingSize > 0) {
                                return $document->pptx_path;
                        }
                }

                $pptxPath = "documents/{$document->id}/presentation.pptx";
                Storage::disk()->makeDirectory("documents/{$document->id}");
                [$tempPptxRelative, $tempPptxPath] = $this->createLocalTempPath('pptx');

                $this->createMinimalPptx($tempPptxPath, (string) $document->title);
                Storage::disk()->put($pptxPath, fopen($tempPptxPath, 'rb'));
                Storage::disk('local')->delete($tempPptxRelative);

                $document->update([
                        'pptx_path' => $pptxPath,
                ]);

                return $pptxPath;
        }

    private function createLocalTempPath(string $extension): array
    {
        $normalized = ltrim($extension, '.');
        $relative = 'tmp/online-docs/' . Str::uuid() . ($normalized !== '' ? '.' . $normalized : '');
        Storage::disk('local')->makeDirectory(dirname($relative));

        return [$relative, Storage::disk('local')->path($relative)];
    }

    private function copyStoredToLocal(string $storedPath): array
    {
        [$tempRelative, $tempPath] = $this->createLocalTempPath(pathinfo($storedPath, PATHINFO_EXTENSION));

        $stream = Storage::disk()->readStream($storedPath);
        if ($stream !== false) {
            Storage::disk('local')->put($tempRelative, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } else {
            Storage::disk('local')->put($tempRelative, Storage::disk()->get($storedPath));
        }

        return [$tempRelative, $tempPath];
    }

            private function extractSearchableText(string $html): string
            {
                $text = strip_tags($html);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/\s+/u', ' ', $text) ?? '';

                return trim($text);
            }

        private function createMinimalPptx(string $targetPath, string $title): void
        {
                $dir = dirname($targetPath);
                if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                }

                $zip = new ZipArchive();
                if ($zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                        throw new \RuntimeException('Unable to create PPTX package');
                }

                $safeTitle = trim($title) !== '' ? htmlspecialchars($title, ENT_XML1) : 'Presentation';
                $created = gmdate('Y-m-d\\TH:i:s\\Z');

                $zip->addFromString('[Content_Types].xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>
    <Override PartName="/ppt/presProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presProps+xml"/>
    <Override PartName="/ppt/viewProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.viewProps+xml"/>
    <Override PartName="/ppt/tableStyles.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.tableStyles+xml"/>
    <Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>
    <Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>
    <Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>
    <Override PartName="/ppt/slides/slide1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>
</Types>
XML);

                $zip->addFromString('_rels/.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML);

                $zip->addFromString('docProps/app.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
    <Application>Microsoft Office PowerPoint</Application>
    <Slides>1</Slides>
    <Notes>0</Notes>
    <HiddenSlides>0</HiddenSlides>
    <MMClips>0</MMClips>
    <ScaleCrop>false</ScaleCrop>
    <HeadingPairs>
        <vt:vector size="2" baseType="variant">
            <vt:variant><vt:lpstr>Theme</vt:lpstr></vt:variant>
            <vt:variant><vt:i4>1</vt:i4></vt:variant>
        </vt:vector>
    </HeadingPairs>
    <TitlesOfParts>
        <vt:vector size="1" baseType="lpstr">
            <vt:lpstr>Office Theme</vt:lpstr>
        </vt:vector>
    </TitlesOfParts>
    <Company></Company>
    <LinksUpToDate>false</LinksUpToDate>
    <SharedDoc>false</SharedDoc>
    <HyperlinksChanged>false</HyperlinksChanged>
    <AppVersion>16.0000</AppVersion>
</Properties>
XML);

                $zip->addFromString('docProps/core.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>{$safeTitle}</dc:title>
    <dc:creator>DACN</dc:creator>
    <cp:lastModifiedBy>DACN</cp:lastModifiedBy>
    <dcterms:created xsi:type="dcterms:W3CDTF">{$created}</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">{$created}</dcterms:modified>
</cp:coreProperties>
XML);

                $zip->addFromString('ppt/presentation.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
    <p:sldMasterIdLst>
        <p:sldMasterId id="2147483648" r:id="rId1"/>
    </p:sldMasterIdLst>
    <p:sldIdLst>
        <p:sldId id="256" r:id="rId2"/>
    </p:sldIdLst>
    <p:sldSz cx="12192000" cy="6858000" type="screen16x9"/>
    <p:notesSz cx="6858000" cy="9144000"/>
</p:presentation>
XML);

                $zip->addFromString('ppt/_rels/presentation.xml.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide1.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/presProps" Target="presProps.xml"/>
    <Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/viewProps" Target="viewProps.xml"/>
    <Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/tableStyles" Target="tableStyles.xml"/>
</Relationships>
XML);

                $zip->addFromString('ppt/presProps.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:presentationPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>
XML);

                $zip->addFromString('ppt/viewProps.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:viewPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
    <p:normalViewPr/>
</p:viewPr>
XML);

                $zip->addFromString('ppt/tableStyles.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:tblStyleLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" def="{5C22544A-7EE6-4342-B048-85BDC9FD1C3A}"/>
XML);

                $zip->addFromString('ppt/theme/theme1.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office Theme">
    <a:themeElements>
        <a:clrScheme name="Office">
            <a:dk1><a:srgbClr val="000000"/></a:dk1>
            <a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>
            <a:dk2><a:srgbClr val="1F497D"/></a:dk2>
            <a:lt2><a:srgbClr val="EEECE1"/></a:lt2>
            <a:accent1><a:srgbClr val="4F81BD"/></a:accent1>
            <a:accent2><a:srgbClr val="C0504D"/></a:accent2>
            <a:accent3><a:srgbClr val="9BBB59"/></a:accent3>
            <a:accent4><a:srgbClr val="8064A2"/></a:accent4>
            <a:accent5><a:srgbClr val="4BACC6"/></a:accent5>
            <a:accent6><a:srgbClr val="F79646"/></a:accent6>
            <a:hlink><a:srgbClr val="0000FF"/></a:hlink>
            <a:folHlink><a:srgbClr val="800080"/></a:folHlink>
        </a:clrScheme>
        <a:fontScheme name="Office">
            <a:majorFont><a:latin typeface="Calibri"/></a:majorFont>
            <a:minorFont><a:latin typeface="Calibri"/></a:minorFont>
        </a:fontScheme>
        <a:fmtScheme name="Office"><a:fillStyleLst/><a:lnStyleLst/><a:effectStyleLst/><a:bgFillStyleLst/></a:fmtScheme>
    </a:themeElements>
</a:theme>
XML);

                $zip->addFromString('ppt/slideMasters/slideMaster1.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
    <p:cSld>
        <p:bg><p:bgPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill></p:bgPr></p:bg>
        <p:spTree>
        <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
        <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
        </p:spTree>
    </p:cSld>
    <p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst>
</p:sldMaster>
XML);

                $zip->addFromString('ppt/slideMasters/_rels/slideMaster1.xml.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>
</Relationships>
XML);

                $zip->addFromString('ppt/slideLayouts/slideLayout1.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="title" preserve="1">
    <p:cSld name="Title Slide">
        <p:bg><p:bgPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill></p:bgPr></p:bg>
        <p:spTree>
        <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
        <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
        </p:spTree>
    </p:cSld>
</p:sldLayout>
XML);

                $zip->addFromString('ppt/slideLayouts/_rels/slideLayout1.xml.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>
</Relationships>
XML);

                $zip->addFromString('ppt/slides/slide1.xml', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
    <p:cSld>
        <p:bg><p:bgPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill></p:bgPr></p:bg>
        <p:spTree>
        <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
        <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
        </p:spTree>
    </p:cSld>
</p:sld>
XML);

                $zip->addFromString('ppt/slides/_rels/slide1.xml.rels', <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
</Relationships>
XML);

                $zip->close();
        }

    /**
     * Share document with a user
     */
    public function shareDocument(Document $document, string $email, string $permission): bool
    {
        $user = $this->repository->getUserByEmail($email);

        if (!$user || $user->id === $document->owner_id) {
            return false;
        }

        $document->sharedUsers()->syncWithoutDetaching([
            $user->id => ['permission' => $permission],
        ]);

        return true;
    }

    /**
     * Update share permission
     */
    public function updateSharePermission(Document $document, int $userId, string $permission): bool
    {
        if ($userId === $document->owner_id) {
            return false;
        }

        $document->sharedUsers()->updateExistingPivot($userId, [
            'permission' => $permission,
        ]);

        return true;
    }

    /**
     * Remove share access
     */
    public function removeShare(Document $document, int $userId): bool
    {
        if ($userId === $document->owner_id) {
            return false;
        }

        $document->sharedUsers()->detach($userId);

        return true;
    }
}

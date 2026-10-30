<?php
declare(strict_types=1);

/* ==========================================================================
   Content type definitions for admin/content.php.

   field keys:
     name   column name (bi=true => name_en + name_bn)
     label  label shown in the form
     type   text | textarea | html | date | int | url | check | select | image | file
     req    true / 'en' (English only)   max  max length   null  NULL when empty
     options array|callable   default value   help text
   ========================================================================== */

$yearOptions = function (): array {
    $o = [];
    for ($y = (int)date('Y') + 1; $y >= 2000; $y--) $o[(string)$y] = (string)$y;
    return $o;
};

$projectOptions = function (): array {
    $o = ['' => '— none —'];
    try {
        foreach (db()->query('SELECT id, project_year, title_en FROM projects ORDER BY project_year DESC, id DESC') as $r) {
            $o[(string)$r['id']] = $r['project_year'] . ' · ' . $r['title_en'];
        }
    } catch (Throwable $e) {}
    return $o;
};

$docCats = [];
foreach (document_categories() as $k => $l) $docCats[$k] = $l['en'];

$tagOptions = function (): array {
    $options = [];
    foreach (db()->query('SELECT id, name FROM tags ORDER BY name ASC') as $tag) {
        $options[(string)$tag['id']] = $tag['name'];
    }
    return $options;
};

$seoFields = [
    ['name' => 'meta_title', 'label' => 'SEO title (optional)', 'type' => 'text', 'max' => 190],
    ['name' => 'meta_description', 'label' => 'SEO description (optional)', 'type' => 'textarea', 'max' => 320, 'rows' => 3],
    ['name' => 'meta_keywords', 'label' => 'SEO keywords (optional)', 'type' => 'text', 'max' => 500],
];

return [

    'news' => [
        'title' => 'News & articles', 'singular' => 'article', 'table' => 'news',
        'order' => 'published_at DESC, id DESC', 'slug' => true, 'search' => ['title_en', 'title_bn'],
        'public' => 'news/{slug}',
        'list' => [['title_en', 'Title'], ['category', 'Category'], ['published_at', 'Date', 'date'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'content_language', 'label' => 'Article language', 'type' => 'select', 'options' => ['' => 'Choose / keep legacy', 'en' => 'English', 'bn' => 'বাংলা'], 'null' => true, 'default' => ''],
            ['name' => 'title',   'label' => 'Title',   'type' => 'text', 'bi' => true, 'max' => 190],
            ['name' => 'excerpt', 'label' => 'Short summary', 'type' => 'textarea', 'bi' => true, 'max' => 400, 'rows' => 3],
            ['name' => 'body',    'label' => 'Article text', 'type' => 'html', 'bi' => true],
            ['name' => 'department', 'label' => 'Department', 'type' => 'text', 'bi' => true, 'max' => 80],
            ['name' => 'category',   'label' => 'Category', 'type' => 'text', 'max' => 60, 'default' => 'News'],
            ['name' => 'published_at', 'label' => 'Publish date', 'type' => 'date', 'default' => 'today', 'req' => true],
            ['name' => 'image', 'label' => 'Cover photo', 'type' => 'image'],
            ['name' => 'news_images', 'label' => 'Article images', 'type' => 'multi_images'],
            ['name' => 'news_tags', 'label' => 'Tags', 'type' => 'tags', 'options' => $tagOptions],
            ...$seoFields,
            ['name' => 'status', 'label' => 'Published (visible on the website)', 'type' => 'check', 'default' => 1],
        ],
    ],

    'notices' => [
        'title' => 'Notices', 'singular' => 'notice', 'table' => 'notices',
        'order' => 'is_pinned DESC, notice_date DESC, id DESC', 'slug' => true, 'search' => ['title_en', 'title_bn'],
        'public' => 'notices/{slug}',
        'list' => [['title_en', 'Title'], ['notice_date', 'Date', 'date'], ['expires_at', 'Expires', 'date'], ['is_pinned', 'Pinned', 'bool'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 190],
            ['name' => 'body',  'label' => 'Notice text', 'type' => 'html', 'bi' => true],
            ['name' => 'notice_date', 'label' => 'Notice date', 'type' => 'date', 'default' => 'today', 'req' => true],
            ['name' => 'expires_at',  'label' => 'Hide after (optional)', 'type' => 'date', 'null' => true,
             'help' => 'Leave empty to keep the notice visible. After this date it moves to "Earlier notices".'],
            ['name' => 'is_pinned', 'label' => 'Pin to the top', 'type' => 'check', 'default' => 0],
            ['name' => 'attachment', 'label' => 'Attachment (PDF, Word, Excel)', 'type' => 'file',
             'cols' => ['path' => 'attachment', 'name' => 'attachment_name']],
            ...$seoFields,
            ['name' => 'status', 'label' => 'Published (visible on the website)', 'type' => 'check', 'default' => 1],
        ],
    ],

    'events' => [
        'title' => 'Events', 'singular' => 'event', 'table' => 'events',
        'order' => 'event_date DESC, id DESC', 'slug' => true, 'search' => ['title_en', 'title_bn', 'location_en'],
        'public' => 'events',
        'list' => [['title_en', 'Title'], ['location_en', 'Location'], ['event_date', 'Date', 'date'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'title',    'label' => 'Title', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 190],
            ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'bi' => true, 'max' => 190],
            ['name' => 'body',     'label' => 'Details', 'type' => 'html', 'bi' => true],
            ['name' => 'event_date', 'label' => 'Event date', 'type' => 'date', 'default' => 'today', 'req' => true],
            ['name' => 'is_health_day', 'label' => 'Show the "Health Day" badge', 'type' => 'check', 'default' => 0],
            ['name' => 'image', 'label' => 'Photo', 'type' => 'image'],
            ['name' => 'related_news_url', 'label' => 'Related News Article URL (optional)', 'type' => 'url', 'max' => 2048,
             'help' => 'After publishing a report, paste its full http(s) URL here. The homepage event button will open this article. Leave blank to use the event details page.'],
            ...$seoFields,
            ['name' => 'status', 'label' => 'Published (visible on the website)', 'type' => 'check', 'default' => 1],
        ],
    ],

    'projects' => [
        'title' => 'Projects (year-wise)', 'singular' => 'project', 'table' => 'projects',
        'order' => 'project_year DESC, sort ASC, id DESC', 'slug' => true, 'search' => ['title_en', 'title_bn'],
        'public' => 'projects/{slug}',
        'list' => [['project_year', 'Year'], ['title_en', 'Title'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'project_year', 'label' => 'Year', 'type' => 'select', 'options' => $yearOptions, 'default' => date('Y'), 'req' => true],
            ['name' => 'title',   'label' => 'Project name', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 190],
            ['name' => 'summary', 'label' => 'Short summary', 'type' => 'textarea', 'bi' => true, 'max' => 400, 'rows' => 3],
            ['name' => 'body',    'label' => 'Details', 'type' => 'html', 'bi' => true],
            ['name' => 'image',   'label' => 'Photo', 'type' => 'image'],
            ['name' => 'sort',    'label' => 'Order within the year (small numbers first)', 'type' => 'int', 'default' => 0],
            ...$seoFields,
            ['name' => 'status',  'label' => 'Published (visible on the website)', 'type' => 'check', 'default' => 1],
        ],
    ],

    'documents' => [
        'title' => 'Documents & reports', 'singular' => 'document', 'table' => 'documents',
        'order' => 'doc_year DESC, id DESC', 'search' => ['title_en', 'title_bn', 'file_name'],
        'public' => 'documents',
        'list' => [['title_en', 'Title'], ['category', 'Category', 'category'], ['doc_year', 'Year'], ['file_ext', 'Type'], ['downloads', 'Opens'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 190],
            ['name' => 'description', 'label' => 'Short description', 'type' => 'textarea', 'bi' => true, 'max' => 400, 'rows' => 3],
            ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $docCats, 'default' => 'project-report', 'req' => true],
            ['name' => 'doc_year', 'label' => 'Report year', 'type' => 'select', 'options' => $yearOptions, 'default' => date('Y'), 'req' => true],
            ['name' => 'project_id', 'label' => 'Related project (optional)', 'type' => 'select', 'options' => $projectOptions, 'null' => true],
            ['name' => 'file', 'label' => 'File (PDF, Word, Excel — up to 10 MB)', 'type' => 'file', 'req' => true,
             'cols' => ['path' => 'file_path', 'name' => 'file_name', 'size' => 'file_size', 'ext' => 'file_ext']],
            ['name' => 'status', 'label' => 'Published (visible on the website)', 'type' => 'check', 'default' => 1],
        ],
    ],

    'gallery' => [
        'title' => 'Photos & Videos', 'singular' => 'gallery item', 'table' => 'gallery_items',
        'order' => 'sort ASC, id DESC', 'search' => ['title_en', 'title_bn'],
        'public' => 'gallery',
        'list' => [['media_type', 'Type'], ['category', 'Category'], ['title_en', 'Title'], ['sort', 'Order'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'media_type', 'label' => 'Media type', 'type' => 'select', 'options' => ['photo' => 'Photo album image', 'video' => 'YouTube video'], 'default' => 'photo', 'req' => true],
            ['name' => 'title', 'label' => 'Title / caption', 'type' => 'text', 'bi' => true, 'max' => 190],
            ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => [
                'featured' => 'Featured', 'activities' => 'Activities', 'health' => 'Health',
                'heart-care' => 'Heart Care', 'world-heart-day' => 'World Heart Day', 'cultural' => 'Cultural',
            ], 'default' => 'activities', 'req' => true],
            ['name' => 'album', 'label' => 'Photo album title (photos only)', 'type' => 'text', 'bi' => true, 'max' => 190],
            ['name' => 'youtube_id', 'label' => 'YouTube URL or video ID (videos only)', 'type' => 'text', 'max' => 2048,
             'help' => 'Paste a YouTube watch/share URL or its 11-character video ID. Videos are embedded only when a visitor presses Play.'],
            ['name' => 'image', 'label' => 'Photo (required for photo items)', 'type' => 'image'],
            ['name' => 'sort', 'label' => 'Order (small numbers first)', 'type' => 'int', 'default' => 0],
            ['name' => 'status', 'label' => 'Published (visible on the website)', 'type' => 'check', 'default' => 1],
        ],
    ],

    'publications' => [
        'title' => 'Publications', 'singular' => 'publication', 'table' => 'publications',
        'order' => 'sort ASC, id ASC', 'slug' => true, 'search' => ['title_en', 'title_bn'],
        'public' => 'publications/{slug}',
        'list' => [['cover_image', 'Thumbnail', 'image'], ['title_en', 'Title'], ['sort', 'Order'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'title',   'label' => 'Publication title', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 190],
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'bi' => true, 'max' => 190],
            ['name' => 'summary', 'label' => 'Short description', 'type' => 'textarea', 'bi' => true, 'max' => 400, 'rows' => 3],
            ['name' => 'body',    'label' => 'Publication details', 'type' => 'html', 'bi' => true],
            ['name' => 'cover_image', 'label' => 'Thumbnail image', 'type' => 'image'],
            ['name' => 'publication_pdf', 'label' => 'PDF document', 'type' => 'file', 'extensions' => ['pdf'],
             'cols' => ['path' => 'publication_pdf_path', 'name' => 'publication_pdf_name'],
             'help' => 'PDF only, up to 10 MB.'],
            ['name' => 'international_url', 'label' => 'External / international publication URL', 'type' => 'url', 'max' => 2048,
             'help' => 'Optional. Enter a complete http:// or https:// link.'],
            ['name' => 'sort', 'label' => 'Order (small numbers first)', 'type' => 'int', 'default' => 0],
            ...$seoFields,
            ['name' => 'status', 'label' => 'Published (visible on the website)', 'type' => 'check', 'default' => 1],
        ],
    ],

    'pages' => [
        'title' => 'Pages', 'singular' => 'page', 'table' => 'pages',
        'order' => 'id ASC', 'slug' => true, 'search' => ['title_en', 'title_bn'],
        'public' => '{slug}',
        'list' => [['title_en', 'Title'], ['slug_en', 'Address'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 190],
            ['name' => 'body',  'label' => 'Page text', 'type' => 'html', 'bi' => true,
             'help' => 'Note: the Mission, About and Community pages are designed templates and do not read this text.'],
            ...$seoFields,
            ['name' => 'status', 'label' => 'Published (visible on the website)', 'type' => 'check', 'default' => 1],
        ],
    ],

    'home_cards' => [
        'title' => 'Home cards', 'singular' => 'card', 'table' => 'home_cards',
        'order' => 'slot ASC', 'search' => ['title_en'],
        'note' => 'Managed through the dedicated Homepage card manager.',
        'list' => [['slot', 'Slot'], ['title_en', 'Title'], ['link_path', 'Link'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'slot', 'label' => 'Slot (1–3)', 'type' => 'int', 'default' => 1, 'req' => true],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 60],
            ['name' => 'body',  'label' => 'Text', 'type' => 'text', 'bi' => true, 'max' => 200],
            ['name' => 'link_path', 'label' => 'Link (e.g. events)', 'type' => 'text', 'max' => 190, 'req' => true],
            ['name' => 'image', 'label' => 'Image', 'type' => 'image', 'req' => true],
            ['name' => 'status', 'label' => 'Published', 'type' => 'check', 'default' => 1],
        ],
    ],

    'featured_cards' => [
        'title' => 'Featured cards', 'singular' => 'card', 'table' => 'featured_cards',
        'order' => 'slot ASC', 'search' => ['caption_en'],
        'list' => [['slot', 'Slot'], ['caption_en', 'Caption'], ['link_path', 'Link'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'slot', 'label' => 'Slot (1–3)', 'type' => 'int', 'default' => 1, 'req' => true],
            ['name' => 'caption', 'label' => 'Caption', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 120],
            ['name' => 'link_path', 'label' => 'Link (e.g. events)', 'type' => 'text', 'max' => 190, 'req' => true],
            ['name' => 'image', 'label' => 'Image', 'type' => 'image', 'req' => true],
            ['name' => 'status', 'label' => 'Published', 'type' => 'check', 'default' => 1],
        ],
    ],

    'team' => [
        'title' => 'Team', 'singular' => 'member', 'table' => 'team',
        'order' => 'sort ASC, id ASC', 'search' => ['name_en', 'name_bn', 'role_en'],
        'note' => 'The public Leadership page is a designed template. The homepage leadership quote is edited in Site settings.',
        'list' => [['name_en', 'Name'], ['role_en', 'Role'], ['sort', 'Order'], ['status', 'Status', 'status']],
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'bi' => true, 'req' => 'en', 'max' => 120],
            ['name' => 'role', 'label' => 'Role', 'type' => 'text', 'bi' => true, 'max' => 120],
            ['name' => 'bio',  'label' => 'Biography', 'type' => 'textarea', 'bi' => true, 'max' => 3000, 'rows' => 5],
            ['name' => 'quote', 'label' => 'Quote', 'type' => 'textarea', 'bi' => true, 'max' => 1000, 'rows' => 3],
            ['name' => 'photo', 'label' => 'Photo', 'type' => 'image'],
            ['name' => 'sort', 'label' => 'Order (small numbers first)', 'type' => 'int', 'default' => 0],
            ['name' => 'is_leadership_message', 'label' => 'Featured leadership message', 'type' => 'check', 'default' => 0],
            ['name' => 'status', 'label' => 'Published', 'type' => 'check', 'default' => 1],
        ],
    ],
];

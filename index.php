<?php
declare(strict_types=1);

require __DIR__ . '/config/config.php';
require __DIR__ . '/includes/functions.php';

/* ---- Detect language from the first URL segment ---- */
$path = current_path();
$segs = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));
$segs = array_map('rawurldecode', $segs);

if (!empty($segs) && in_array($segs[0], ['en', 'bn'], true)) {
    $GLOBALS['LANG'] = array_shift($segs);
} else {
    $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    redirect(BASE_PATH . '/' . DEFAULT_LANG . ($path === '/' ? '/' : $path) . $qs);
}

$route = $segs[0] ?? '';
if (str_ends_with($route, '.php')) {            // /en/free-heart-camp.php is the same page as /en/free-heart-camp
    $route = substr($route, 0, -4);
}

/* ---- Form posts (redirect after save) ---- */
handle_public_post($route);

$notFound = function () {
    http_response_code(404);
    render('404', ['pageTitle' => t('not_found_title') . ' — ' . t('site_name')]);
};

/* ---- Routes ---- */
switch ($route) {

    case '':
        render('home', [
            'pageTitle'       => t('site_name') . ' — ' . t('nav_health'),
            'metaDescription' => t('tagline'),
            'isHome'          => true,
        ]);
        break;

    case 'news':
    case 'all-news':
        if (!empty($segs[1])) {
            $item = get_news_item($segs[1]);
            if (!$item) {
                $moved = get_news_redirect($segs[1]);
                if ($moved) redirect(news_url($moved));
                $notFound();
                break;
            }
            $canonicalSlug = (string)($item['slug_en'] ?: $item['slug_bn']);
            $images = news_images((int)$item['id']);
            $meta = content_meta($item, rawurlencode($canonicalSlug) . '.php');
            if ($meta['ogImage'] === '' && $images) $meta['ogImage'] = rtrim(APP_URL, '/') . asset((string)$images[0]['image_path']);
            render('news-single', [
                'item'   => $item,
                'images' => $images,
                'tags'   => news_tags((int)$item['id']),
                ...$meta,
            ]);
        } else {
            $per = 12;
            $pg = paginate(news_count(), $per, (int)($_GET['page'] ?? 1));
            render('news-list', ['items' => news_list($per, $pg['offset']), 'pg' => $pg, 'pageTitle' => t('latest_news')]);
        }
        break;

    case 'events':
        if (!empty($segs[1])) {
            $event = get_event($segs[1]);
            if (!$event) { $notFound(); break; }
            render('event-single', [
                'event' => $event,
                ...content_meta($event, 'events/' . rawurlencode((string)$event['slug_en']), '', 'image'),
            ]);
            break;
        }
        render('events', [
            'items'     => upcoming_events(20),
            'past'      => past_events(12),
            'pageTitle' => t('upcoming_events'),
        ]);
        break;

    case 'notices':
        if (!empty($segs[1])) {
            $notice = get_notice($segs[1]);
            if (!$notice) { $notFound(); break; }
            render('notice-single', [
                'notice' => $notice,
                ...content_meta($notice, 'notices/' . rawurlencode((string)$notice['slug_en']), '', ''),
            ]);
        } else {
            $per = 15;
            $pg = paginate(notices_count(), $per, (int)($_GET['page'] ?? 1));
            render('notices', [
                'items'     => notices_active($per, $pg['offset']),
                'expired'   => $pg['page'] === 1 ? notices_expired(10) : [],
                'pg'        => $pg,
                'pageTitle' => t('nav_notices') . ' — ' . t('site_name'),
            ]);
        }
        break;

    case 'projects':
        if (!empty($segs[1])) {
            $project = get_project($segs[1]);
            if (!$project) { $notFound(); break; }
            render('project-single', [
                'project'   => $project,
                'docs'      => documents_list(null, null, (int)$project['id']),
                ...content_meta($project, 'projects/' . rawurlencode((string)$project['slug_en']), 'summary'),
            ]);
        } else {
            $years = project_years();
            $year = (int)($_GET['year'] ?? 0);
            if (!in_array($year, $years, true)) $year = $years[0] ?? 0;
            render('projects', [
                'years'     => $years,
                'year'      => $year,
                'items'     => $year ? projects_for_year($year) : [],
                'reports'   => $year ? array_merge(documents_list('project-report', $year), documents_list('annual-report', $year)) : [],
                'pageTitle' => t('nav_projects') . ' — ' . t('site_name'),
            ]);
        }
        break;

    case 'documents':
        if (($segs[1] ?? '') === 'file' && ctype_digit($segs[2] ?? '')) {
            $doc = get_document((int)$segs[2]);
            if (!$doc) { $notFound(); break; }
            send_document($doc);
        }
        $cat = (string)($_GET['category'] ?? '');
        $yr  = (int)($_GET['year'] ?? 0);
        render('documents', [
            'items'     => documents_list($cat ?: null, $yr ?: null),
            'years'     => document_years(),
            'cat'       => isset(document_categories()[$cat]) ? $cat : '',
            'yr'        => $yr,
            'pageTitle' => t('nav_documents') . ' — ' . t('site_name'),
        ]);
        break;

    case 'gallery':
        $per = 24;
        $category = (string)($_GET['category'] ?? '');
        if (!array_key_exists($category, gallery_categories())) $category = '';
        $pg = paginate(gallery_count($category), $per, (int)($_GET['page'] ?? 1));
        render('gallery', [
            'items' => gallery_items($per, $pg['offset'], $category),
            'pg' => $pg,
            'category' => $category,
            'pageTitle' => t('gallery_title') . ' — ' . t('site_name'),
        ]);
        break;

    case 'publications':
        if (!empty($segs[1])) {
            $publication = get_publication($segs[1]);
            if (!$publication) { $notFound(); break; }
            render('publication-single', [
                'publication' => $publication,
                ...content_meta($publication, 'publications/' . rawurlencode((string)$publication['slug_en']), 'summary', 'cover_image'),
            ]);
        } else {
            render('publications-list', ['publications' => publications(50), 'pageTitle' => t('nav_health')]);
        }
        break;

    case 'heart-health':
        redirect(url('publications' . (!empty($segs[1]) ? '/' . rawurlencode($segs[1]) : '')));
        break;

    case 'team':
        render('team', ['pageTitle' => 'Our Community']);
        break;

    case 'about':
        render('about', ['pageTitle' => 'Our Leadership']);
        break;

    case 'mission':
        render('mission', ['pageTitle' => t('nav_mission')]);
        break;

    case 'donate':
        render('donate', ['pageTitle' => t('donate')]);
        break;

    case 'join':
        render('join', ['pageTitle' => t('join')]);
        break;

    case 'contact':
        render('contact', ['pageTitle' => 'Contact']);
        break;

    default:
        $page = get_page($route);
        if ($page) {
            render('page', [
                'page' => $page,
                ...content_meta($page, rawurlencode((string)$page['slug_en']), '', ''),
            ]);
            break;
        }
        // Automatic article page: any published news item is reachable at /{lang}/{slug}.php
        $article = $route !== '' && count($segs) === 1 ? get_news_item($route) : null;
        if (!$article && $route !== '' && count($segs) === 1) {
            $moved = get_news_redirect($route);
            if ($moved) redirect(news_url($moved));
        }
        if ($article) {
            $canonicalSlug = (string)($article['slug_en'] ?: $article['slug_bn']);
            $images = news_images((int)$article['id']);
            $meta = content_meta($article, rawurlencode($canonicalSlug) . '.php');
            if ($meta['ogImage'] === '' && $images) $meta['ogImage'] = rtrim(APP_URL, '/') . asset((string)$images[0]['image_path']);
            render('news-single', [
                'item'   => $article,
                'images' => $images,
                'tags'   => news_tags((int)$article['id']),
                ...$meta,
            ]);
        } else {
            $notFound();
        }
}
